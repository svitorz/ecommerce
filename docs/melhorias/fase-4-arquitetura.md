# Fase 4 — Arquitetura: carrinho como serviço e modelos sem estado de sessão

**Branch:** `melhorias/fase-4` (a partir de `melhorias/fase-3` já revisada).
**Pré-requisito:** Fases 0 a 3 concluídas.
**Objetivo:** tirar do `Product` o acesso à sessão e ao usuário logado, substituir a trait do carrinho por um serviço injetável, e eliminar controllers usados como serviço (`calculateSubTotalController`, `new CartController` dentro de outro controller). O comportamento visível ao usuário não muda, exceto onde indicado como correção.

> Formato do carrinho na sessão continua: `session('cart') = [['id' => int, 'quantity' => int], ...]`.

---

## 4.1 — Produto sem `auth()` nem `appends`

**Problema:** `Product` tem `$appends = ['quantity', 'isFavorite']`. O accessor `isFavorite` chama `auth()` e faz uma consulta por produto a cada serialização. O accessor `quantity` lê um valor que só existe na sessão. Resultado: o model depende do request, e cada lista faz N consultas. Além disso, `ProductController` guarda `Product` numa propriedade pública e usa `$this->product->load(...)` como ponto de entrada de consulta.

**Arquivos:**
- `app/Models/Product.php`;
- `app/Http/Controllers/ProductController.php`;
- `app/Http/Controllers/CategoryController.php` (`getProducts`, e o que mais usa `new Product`);
- `app/Http/Controllers/GetRecommendedProductsController.php`;
- `app/Http/Traits/CartTrait.php` (só as chamadas que dependem de `quantity`/`isFavorite` do model);
- `tests/Feature/Products/ProductFlagsTest.php` (novo).

**Mudanças:**
1. `Product`:
   - remova `protected $appends = ['quantity', 'isFavorite'];`;
   - remova `getIsFavoriteAttribute`, `setIsFavoriteattribute`, `getQuantityAttribute` e `setquantityattribute`;
   - adicione o escopo de consulta:
     ```php
     public function scopeWithFavoriteFlag(Builder $query, int $userId): void
     {
         $query->withExists([
             'favoritedByUsers as isFavorite' => fn (Builder $q) => $q->where('users.id', $userId),
         ]);
     }
     ```
     com `use Illuminate\Database\Eloquent\Builder;`;
   - adicione `protected $casts = ['price' => 'float', 'isFavorite' => 'boolean'];` (substitui o cast de `price` que já existe).
2. `ProductController`:
   - remova `use CartTrait;` e a propriedade `public Product $product` com o construtor que a recebe;
   - `index`: troque `$this->product->load('category')->paginate(8)` por `Product::query()->with('category')->withFavoriteFlag(auth()->id())->paginate(8)`. Dentro do `through`, `quantity` e `inCart` continuam vindo do carrinho (por enquanto via `getCartProduct`/`isInCart` da trait, que serão trocados na 4.2), e `isFavorite` vem de `$product->isFavorite`;
   - `show`: `$product->loadMissing('category', 'ratings.user')`, e antes de retornar, `$product->setAttribute('isFavorite', ...)` consultando o favorito do usuário logado, e `quantity`/`inCart` do carrinho. Use exatamente estes nomes de atributo, porque o frontend os lê;
   - `searchProduct`: troque `$this->product->load('category')->where(...)` por `Product::query()->with('category')->withFavoriteFlag(auth()->id())->where(...)`, mantendo a lógica de busca.
3. `CategoryController::getProducts`: troque `new Product` por `Product::query()` com `withFavoriteFlag(auth()->id())`. Mantenha o restante.
4. Toda lista que devolve produto ao frontend passa a ter `quantity` (do carrinho ou `1`) e `inCart` (booleano). Isso vale para `index`, `searchProduct`, `getProducts` e o dashboard (`GetRecommendedProductsController`). Por enquanto, faça isso com os métodos da trait, sem criar serviço ainda.

**Correção de comportamento** (indicada aqui para o relatório): `inCart` passa a existir em `show` e nas listas. Antes, `CartButton.vue` nunca recebia `inCart` vindo do servidor nessas telas, então o botão não mostrava "Added" para produto já no carrinho.

**Testes** (`ProductFlagsTest.php`):
- `GET products.index` com produto favoritado pelo usuário: `->where('products.data.0.isFavorite', true)`;
- `GET products.show` com cart `[['id' => p, 'quantity' => 2]]` na sessão: `->where('product.quantity', 2)->where('product.inCart', true)->where('product.isFavorite', false)`;
- `GET categories.products` retorna `products.0.isFavorite` como booleano (`where(..., false)` ou `true`, conforme o caso);
- `Product::factory()->create()` sem escopo não tem `quantity` nem `isFavorite` no `toArray()`: `expect($p->toArray())->not->toHaveKey('quantity')`.

Os testes de `CartTest`, `CheckoutTest`, `OrderAuthorizationTest` e `PaymentDataTest` devem continuar passando sem alteração.

**Verificação extra:** `sail npm run build` e `sail npm run format:check`.

**Commit:** `refactor(products): remove sessão e autenticação do model Product`

---

## 4.2 — Serviço de carrinho `App\Services\Cart`

**Problema:** o carrinho é uma trait misturada em controllers (`CartController`, `ProductController`, `OrderController`, `calculateSubTotalController`). `GetRecommendedProductsController` cria `new CartController` para ler os itens. O subtotal é um controller (`calculateSubTotalController`) injetado como serviço.

**Arquivos:**
- novo `app/Services/Cart.php`;
- apagar `app/Http/Traits/CartTrait.php`;
- apagar `app/Http/Controllers/calculateSubTotalController.php`;
- `app/Http/Controllers/CartController.php`;
- `app/Http/Controllers/OrderController.php`;
- `app/Http/Controllers/ProductController.php`;
- `app/Http/Controllers/GetRecommendedProductsController.php`;
- `app/Http/Controllers/CategoryController.php`;
- novo `tests/Feature/Cart/CartServiceTest.php`.

**Mudanças:**
1. `app/Services/Cart.php` (classe sem estado próprio; lê e grava `session('cart')`):
   ```php
   <?php

   namespace App\Services;

   use App\Models\Product;
   use Illuminate\Support\Collection;

   class Cart
   {
       /** Itens do carrinho com dados atuais do banco. Remove da sessão os produtos que não existem mais. */
       public function items(): array { /* ver nota abaixo */ }

       public function add(Product $product, int $quantity): void
       {
           if ($this->has($product)) {
               return;
           }

           session()->push('cart', ['id' => $product->id, 'quantity' => $quantity]);
       }

       public function update(Product $product, int $quantity): void
       {
           $cart = collect(session()->get('cart', []))
               ->map(fn ($item) => $item['id'] === $product->id ? ['id' => $item['id'], 'quantity' => $quantity] : $item)
               ->values()
               ->all();

           session()->put('cart', $cart);
       }

       public function remove(Product $product): void
       {
           session()->put('cart', array_values(array_filter(
               session()->get('cart', []),
               fn ($item) => $item['id'] !== $product->id,
           )));
       }

       public function clear(): void
       {
           session()->forget('cart');
       }

       public function has(Product $product): bool
       {
           return collect(session()->get('cart', []))->contains('id', $product->id);
       }

       /** Quantidade no carrinho; 1 quando o produto não está nele. */
       public function quantityOf(Product $product): int
       {
           $item = collect(session()->get('cart', []))->firstWhere('id', $product->id);

           return $item['quantity'] ?? 1;
       }

       /** Soma quantidade × preço dos itens, com preço do banco. */
       public function subtotal(): float
       {
           return (float) collect($this->items())->sum(fn ($item) => $item['quantity'] * $item['price']);
       }

       /** Anexa `quantity` e `inCart` ao produto para o frontend. */
       public function decorate(Product $product): Product
       {
           $product->setAttribute('quantity', $this->quantityOf($product));
           $product->setAttribute('inCart', $this->has($product));

           return $product;
       }
   }
   ```
   `items()` é a lógica atual de `getCartItems()` (já com a poda de produtos apagados da sessão, da Fase 2), trocando `isFavorite` por `Product::query()->with('category')->withFavoriteFlag(auth()->id())`. Mantenha exatamente o mesmo formato de cada item: `id`, `name`, `price`, `quantity`, `stock`, `isFavorite`, `category`, `category_id`.
2. `CartController`: `use CartTrait` sai; injete `Cart $cart` nos métodos:
   - `index(Cart $cart, GetRecommendedProductsController $recommendedProducts)`: `'products' => $cart->items()`, `'subtotal' => number_format($cart->subtotal(), 2, '.', '')`;
   - `store`: `$cart->add($product, $validated['quantity'])` (validação de estoque como está);
   - `update`: `$cart->update(...)` no lugar de `updateQuantity`, e `$cart->has($product)` no lugar de `isInCart`;
   - `destroy`: `$cart->remove($product)`;
   - remova `getAll()` se não houver rota apontando para ele (confirme com `grep -rn getAll routes app`); se houver, troque o corpo por `return $cart->items();`.
   - remova o `__construct` que chamava `initializeCartSession()`; o serviço já lida com sessão vazia.
3. `OrderController`: remova `use CartTrait;`. Injete `Cart $cart`:
   - `create(Cart $cart)`: `if ($cart->items() === []) ...`; `'amount' => number_format($cart->subtotal(), 2, '.', '')`;
   - `store(StoreOrderRequest $request, PlaceOrder $placeOrder, Cart $cart)`: `$placeOrder->handle($request->user(), $cart->items(), $request->validated()); $cart->clear();`.
4. `GetRecommendedProductsController`: troque `new CartController` por `Cart $cart` injetado no `handle` (`handle(Cart $cart, int $limit = 3)`) e use `$cart->items()`. Dentro de `handle`, aplique `$cart->decorate()` aos produtos retornados e use `withFavoriteFlag(auth()->id())` nas duas consultas.
5. `ProductController::index` e `show`: troque o uso de `getCartProduct`, `isInCart` e `setAttribute` manuais por `$cart->decorate($product)` (e `quantity` da 4.1 fica a cargo do serviço). Injete `Cart $cart` nos métodos.
6. `CategoryController::getProducts` e `searchProduct` (em `ProductController`): aplique `$cart->decorate()` a cada produto da lista (`$products->each(fn ($p) => $cart->decorate($p))`).
7. `calculateSubTotalController.php`: apagar. Procure referências com `grep -rn calculateSubTotal app routes tests` e confirme que só sobram as que você já trocou.
8. `CartTrait.php`: apagar. Confirme com `grep -rn CartTrait app tests` antes.

**Testes** (`CartServiceTest.php`, usando `withSession`):
- `app(Cart::class)->subtotal()` com produtos `10.00 × 2` e `2.50 × 1` retorna `22.5`;
- `items()` remove da sessão o item de produto apagado e retorna só os existentes;
- `items()` retorna `isFavorite` `true` quando o usuário logado favoritou o produto;
- `quantityOf()` retorna `1` para produto fora do carrinho;
- `decorate()` define `quantity` e `inCart` no produto.

Os testes de `CartTest`, `CheckoutTest`, `OrderAuthorizationTest`, `PaymentDataTest` e `ProductFlagsTest` devem continuar passando sem alteração.

**Verificação extra:** `sail npm run build`.

**Commit:** `refactor(cart): troca a trait do carrinho por App\Services\Cart`

---

## 4.3 — Rota do dashboard e nomes PSR

**Problema:** a rota `/dashboard` tem lógica num closure. `isUserAdmin` é uma classe em minúsculas. `Models/OrdersProducts.php` não é usado. `GetRecommendedProductsController` é um serviço, não um controller.

**Arquivos:**
- `routes/web.php`;
- novo `app/Http/Controllers/DashboardController.php`;
- `app/Http/Middleware/isUserAdmin.php` → `app/Http/Middleware/EnsureUserIsAdmin.php`;
- `app/Http/Controllers/ProductController.php`, `CategoryController.php` (troca do nome do middleware);
- `app/Http/Controllers/GetRecommendedProductsController.php` → `app/Services/RecommendedProducts.php`;
- `app/Models/OrdersProducts.php` (apagar, após `grep -rn OrdersProducts app database tests routes`, que deve mostrar só a própria classe e a factory `OrdersProductsFactory`; a factory não usa o model, então pode ficar).

**Mudanças:**
1. Novo `DashboardController::index(Cart $cart, RecommendedProducts $recommended)`: retorna `Inertia::render('Dashboard', ['products' => $recommended->handle($cart, 12)])`. Em `routes/web.php`, troque o closure por `Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');` e remova o import de `GetRecommendedProductsController`.
2. `isUserAdmin` vira `EnsureUserIsAdmin` (mesmo corpo). Troque as referências em `ProductController::middleware()`, `CategoryController::middleware()` e nos imports. Nenhuma outra mudança.
3. `GetRecommendedProductsController` vira `App\Services\RecommendedProducts` (mesma lógica, sem herdar de `Controller`). Ajuste o import em `DashboardController` e `CartController`.
4. Apague `app/Models/OrdersProducts.php`.

**Testes:** `tests/Feature/Dashboard/DashboardTest.php`:
- `GET dashboard` como cliente retorna 200 com `->component('Dashboard')->has('products')`;
- `GET products.index` como não-admin continua retornando 200 (a troca do middleware não pode quebrar o acesso);
- `PUT products/{id}` como não-admin continua retornando 403.

Os testes de `AdminAccessTest` devem continuar passando sem alteração.

**Verificação extra:** `sail pint --test`.

**Commit:** `refactor(app): dashboard em controller, serviço de recomendados e nomes PSR`

---

## Pronto da Fase 4

- [ ] 3 commits no branch `melhorias/fase-4`
- [ ] `ProductFlagsTest`, `CartServiceTest` e `DashboardTest` passando
- [ ] suíte completa sem regressões (`CartTest`, `CheckoutTest`, `OrderAuthorizationTest`, `PaymentDataTest`, `AdminAccessTest` inclusos)
- [ ] `sail pint --test`, `sail npm run format:check` e `sail npm run build` limpos
- [ ] teste manual sugerido (feito pelo usuário): adicionar um produto ao carrinho e abrir a página dele, que deve mostrar "Added"; favoritar um produto e ver o coração preenchido na listagem; o dashboard carrega sem erro
