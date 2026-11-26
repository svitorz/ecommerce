# Fase 7 — Conteúdo honesto, idioma único, vitrine pública e imagens

**Branch:** `melhorias/fase-7` (a partir de `melhorias/fase-6`).
**Pré-requisito:** Fases 0 a 6 concluídas.
**Objetivo:** a loja mostra só informação verdadeira, em inglês, para visitantes e clientes, com imagem por produto. Decisões tomadas em 26/11/2026 (ver [plano-interface.md](plano-interface.md), seção "Decisões"):
- avaliação dos cartões = média e contagem reais; selos fixos, voucher, "Original price" e "Compra verificada" fixa saem;
- interface toda em inglês, inclusive mensagens de validação e de sessão;
- `/` é uma vitrine pública: listagem, produto, categorias e busca funcionam sem login;
- upload de imagem por produto, com placeholder local.

> **Regra de idioma:** textos novos nesta fase são escritos em inglês. Os comentários de código continuam como estão.

---

## 7.1 — Dados verdadeiros nos cartões e nas telas (E1 a E5)

### a) Média e contagem reais nos cartões (E1)

**Problema:** `ProductCard.vue` mostra "5.0" e "(455)" para todo produto. Não há consulta alguma por trás.

**Arquivos:**
- `app/Models/Product.php`;
- `app/Http/Controllers/ProductController.php` (`index`, `searchProduct`);
- `app/Http/Controllers/CategoryController.php` (`getProducts`);
- `app/Services/RecommendedProducts.php` (as duas consultas);
- `resources/js/Pages/Products/Components/ProductCard.vue`;
- `tests/Feature/Products/RatingSummaryTest.php` (novo).

**Mudanças:**
1. Em `Product`, adicione o escopo (com `use Illuminate\Database\Eloquent\Builder;`, já existente):
   ```php
   public function scopeWithRatingSummary(Builder $query): void
   {
       $query->withAvg('ratings as average_rating', 'rating')->withCount('ratings');
   }
   ```
   E inclua `'average_rating' => 'float'` no array de `$casts`.
2. Chame `->withRatingSummary()` nas consultas listadas em "Arquivos" (antes de `paginate`, `get` ou `limit`).
3. Em `ProductController::index`, o array do `through` ganha `'average_rating' => $product->average_rating, 'ratings_count' => $product->ratings_count,`.
4. `ProductCard.vue`: remova o bloco fixo `<p>5.0</p>` e `<p>(455)</p>` e troque por:
   - se `product.ratings_count > 0`: estrelas e média com uma casa (`Number(product.average_rating).toFixed(1)`), e a contagem entre parênteses;
   - senão: o texto `No reviews yet` no lugar.
   Use o componente `StarRating` que já existe. **Antes**, abra `StarRating.vue`: se ele já imprime a nota entre parênteses, não repita o número.

**Testes** (`RatingSummaryTest.php`):
- produto com duas avaliações (4 e 2): `GET products.index` tem `products.data.0.average_rating` igual a `3.0` e `products.data.0.ratings_count` igual a `2`;
- produto sem avaliação: `ratings_count` igual a `0`.

### b) Selos falsos e voucher saem (E2, E3)

**Arquivos:**
- `resources/js/Pages/Products/Components/ProductCard.vue`;
- `resources/js/Pages/Orders/Components/OrderSummary.vue`.

**Mudanças:**
1. `ProductCard.vue`: remova a `<ul>` inteira que contém o texto `Fast Delivery` (o fallback de `categories?.name`) e o texto `Best Price`. Os três `v-if` e o fallback que usavam `product?.categories?.name` saem junto com a `<ul>`.
2. `OrderSummary.vue`: remova o bloco (`<div>`) que contém `for="voucher"`, o campo `id="voucher"` e o botão de aplicar código. Não mexa no resumo de totais.

### c) "Original price" sai (E4)

**Causa:** a linha repete o total, porque não há desconto. Aparece em três lugares.

**Arquivos:**
- `resources/js/Pages/Orders/Components/OrderSummary.vue` (linha ~18);
- `resources/js/Pages/Orders/IndexOrders.vue` (linha ~120);
- `resources/js/Pages/Orders/Form/PaymentForm.vue` (linha ~211).

**Mudanças:** em cada arquivo, remova **só** a `<dl>` (ou `<div>`) cujo `<dt>` é `Original price`. A linha de `Total` e o valor do pedido ficam.

### d) "Compra verificada" só quando for verdade (E5)

**Problema:** `ProductRatings.vue` mostra o selo em toda avaliação, mesmo quando o autor não comprou o produto.

**Arquivos:**
- `app/Http/Controllers/ProductController.php` (`show`);
- `resources/js/Components/ProductRatings.vue`;
- `tests/Feature/Ratings/VerifiedPurchaseTest.php` (novo).

**Mudanças:**
1. Em `show`, depois de `loadMissing('category', 'ratings.user')`, calcule quem comprou o produto e marque cada avaliação:
   ```php
   $buyerIds = Order::query()
       ->whereHas('products', fn ($q) => $q->where('products.id', $product->id))
       ->where('status', '!=', OrderStatus::Canceled->value)
       ->pluck('user_id')
       ->all();

   $product->ratings->each(fn ($rating) => $rating->setAttribute(
       'verified_purchase',
       in_array($rating->user_id, $buyerIds, true),
   ));
   ```
   Adicione os `use` de `Order` e `OrderStatus` no topo do arquivo.
2. `ProductRatings.vue`: o bloco do selo (o `<div>` que contém o ícone e o texto, com o comentário `Badge de compra verificada`) recebe `v-if="rating.verified_purchase"`. O texto muda para `Verified purchase` (é o idioma único da Fase 7, ver 7.2).

**Testes** (`VerifiedPurchaseTest.php`):
- um cliente que comprou o produto (pedido com `transporting`) e avaliou: a página do produto envia `product.ratings.0.verified_purchase` igual a `true`;
- um cliente que avaliou sem comprar: `verified_purchase` igual a `false`;
- um cliente que comprou e cancelou o pedido: `verified_purchase` igual a `false`.

**Commit:** `fix(ui): mostra só dados verdadeiros nos cartões e nas telas de pedido`

---

## 7.2 — Idioma único: inglês (I3)

**Problema:** as mensagens de validação, de sessão e de erro do backend estão em português. Na interface visível, só "Compra verificada" e o texto do selo ainda estão em português (os outros textos já estão em inglês).

**Arquivos (backend):**
- `app/Actions/Order/PlaceOrder.php`;
- `app/Actions/Jetstream/DeleteUser.php`;
- `app/Http/Requests/StoreOrderRequest.php`;
- `app/Http/Controllers/CartController.php`;
- `app/Http/Controllers/OrderController.php`;
- `app/Http/Controllers/ProductController.php`;
- `app/Http/Controllers/CategoryController.php`.

**Arquivos (frontend):** `resources/js/Components/ProductRatings.vue` (o selo, que já sai em 7.1 d).

**Troque cada texto abaixo, sem mudar a chave de erro (o `withErrors([...])` ou o `with(...)` continua igual):**

| Arquivo | Hoje | Passa a ser |
|---------|------|-------------|
| `PlaceOrder.php` | `Seu carrinho está vazio.` | `Your cart is empty.` |
| `PlaceOrder.php` | `Estoque insuficiente para ` + nome + `.` | `Not enough stock for ` + nome + `.` |
| `DeleteUser.php` | `Não é possível excluir a conta com pedidos registrados.` | `You cannot delete an account that has orders.` |
| `StoreOrderRequest.php` | `Número do cartão inválido.` | `Invalid card number.` |
| `StoreOrderRequest.php` | `O CVV deve ter 3 ou 4 dígitos.` | `The CVV must have 3 or 4 digits.` |
| `StoreOrderRequest.php` | `Cartão vencido.` | `The card has expired.` |
| `OrderController.php` (`create`) | `Seu carrinho está vazio.` | `Your cart is empty.` |
| `OrderController.php` (`store`) | `Pedido realizado com sucesso!` | `Order placed successfully!` |
| `OrderController.php` (`update`) | `Este pedido não pode mais ser cancelado.` | `This order can no longer be canceled.` |
| `OrderController.php` (`update`) | `Pedido cancelado.` | `Order canceled.` |
| `ProductController.php` (`destroy`) | `Não é possível excluir produto que já foi pedido.` | `You cannot delete a product that has been ordered.` |
| `CategoryController.php` (`destroy`) | `Não é possível excluir categoria que possui produtos.` | `You cannot delete a category that has products.` |
| `CartController.php` (`store`, `update`) | `Quantidade maior que o estoque disponível.` | `The quantity is more than the available stock.` |
| `CartController.php` (`update`) | `O produto não está no carrinho.` | `This product is not in your cart.` |

Os comentários `//` e os blocos `/** */` em português **não** mudam nesta fase.

**Testes:** acrescente em `tests/Feature/Orders/CheckoutTest.php` um teste que confirma a mensagem nova:
- carrinho vazio: `->assertSessionHasErrors(['cart' => 'Your cart is empty.'])` ao postar em `orders.store`.

**Verificação:** `grep -rnE "Não|não é|Seu |Sua |Quantidade|Pedido |Estoque|vencido|inválido|carrinho está" app routes config` não deve retornar nada fora de comentários.

**Commit:** `refactor(i18n): mensagens de validação e de sessão em inglês`

---

## 7.3 — Vitrine pública (I1)

**Problema:** `/` mostra a página padrão do Laravel (Laracasts, Laravel News). Listagem, produto, categorias e busca exigem login.

**Decisão:** a home é uma vitrine com categorias e produtos em destaque. Visitante navega e busca. Carrinho, favoritos, avaliação e pedido continuam exigindo login; o botão leva ao login quando não há usuário.

**Arquivos:**
- novo `app/Http/Controllers/HomeController.php`;
- novo `resources/js/Pages/Home.vue`;
- `routes/web.php`;
- `app/Models/Product.php` (escopo `withFavoriteFlag` aceita `null`);
- `app/Http/Controllers/ProductController.php`;
- `app/Http/Controllers/CategoryController.php`;
- `app/Services/RecommendedProducts.php` (só o `auth()->id()` já está coberto pelo escopo);
- `resources/js/Layouts/AppLayout.vue`;
- `resources/js/Pages/Products/Components/ProductCard.vue`;
- `resources/js/Pages/Cart/Components/CartButton.vue`;
- `resources/js/Pages/Favorites/FavoriteButton.vue`;
- `resources/js/Pages/Products/ShowProduct.vue`;
- `resources/js/Pages/Welcome.vue` (apagar, se não houver mais referência);
- `resources/js/Components/Welcome.vue` (apagar, **somente** depois de `grep -rn "Components/Welcome\|<Welcome" resources/js` não retornar nada).
- `tests/Feature/Storefront/PublicStorefrontTest.php` (novo).

**Mudanças:**

1. **Escopo aceita visitante.** Em `Product::scopeWithFavoriteFlag`, troque `int $userId` por `?int $userId`. Quando for `null`, não consulte favoritos; marque `isFavorite` como `false` assim:
   ```php
   if ($userId === null) {
       $query->selectRaw('products.*, false as "isFavorite"');

       return;
   }
   ```
   Mantenha o `withExists` para o caso com usuário.

2. **Admin sem usuário.** Em `ProductController` e `CategoryController`, troque `request()->user()->isAdmin()` por `request()->user()?->isAdmin() ?? false` em todos os métodos (`index`, `searchProduct`, `show`, `create`, `edit`, `getProducts`, `index` de categorias). Em `show`, `where('users.id', auth()->id())` vira `->exists()` só quando houver usuário; sem usuário, `isFavorite` é `false`.

3. **Rotas** (`routes/web.php`): troque a rota `/` por `Route::get('/', [HomeController::class, 'index'])->name('home');` e mova para **fora** do grupo `auth` estas rotas, com `->name(...)` que já existem:
   - `Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');`
   - `Route::get('/categories/{category}/products', ...)` (já existe; só sai do grupo);
   - `Route::get('/search/{searchKey}', ...)` (já existe; só sai do grupo);
   - `Route::resource('products', ProductController::class)->only(['index', 'show'])->whereNumber('product');`
   No grupo `auth`, troque `Route::resource('categories', ...)->except(['show'])` por `->except(['index', 'show'])`, e `Route::resource('products', ...)` por `->except(['index', 'show'])`. O `whereNumber` impede que `/products/create` seja lido como produto de id `create` (com ele, a rota pública `show` não captura `create`).

4. **`HomeController`:**
   ```php
   public function index(Cart $cart): Response
   {
       return Inertia::render('Home', [
           'categories' => Category::query()->orderBy('name')->limit(6)->get(),
           'products' => Product::query()
               ->with('category')
               ->withRatingSummary()
               ->withFavoriteFlag(auth()->id())
               ->latest('id')
               ->limit(8)
               ->get()
               ->each(fn (Product $product) => $cart->decorate($product)),
           'isAdmin' => request()->user()?->isAdmin() ?? false,
       ]);
   }
   ```
   Use os `use` necessários (`Category`, `Product`, `Cart`, `Inertia\Inertia`, `Inertia\Response`).

5. **`Home.vue`:** duas seções com o layout do `AppLayout`:
   - "Shop by category": grade de até 6 cards com o nome da categoria e link para `route('categories.products', category.id)`;
   - "Latest products": grade com `ProductCard`, do mesmo jeito que `IndexProducts.vue`.
   O `ProductCard` recebe `:isAdmin`.

6. **Visitante na interface** (`AppLayout.vue`): confirme que tudo que lê `$page.props.auth.user` está protegido por `v-if`. Em especial, o bloco de equipe (`current_team`, `all_teams`) e a foto de perfil precisam estar dentro de `v-if="$page.props.auth.user"`. Para visitante, mostre no menu `Products`, `Categories`, `Log in` (`route('login')`) e `Register` (`route('register')`). Se algum `v-if` faltar, corrija.

7. **Ações que pedem login** (`ProductCard`, `ShowProduct`, `CartButton`, `FavoriteButton`): quando `$page.props.auth.user` for `null`, troque o botão por um link `Log in to buy` ou `Log in to save` para `route('login')`. Com usuário, nada muda.

**Testes** (`PublicStorefrontTest.php`):
- visitante: `GET /` retorna 200 com `->component('Home')`;
- visitante: `GET products.index` retorna 200; `GET products.show` de um produto retorna 200;
- visitante: `GET categories.index` retorna 200; `GET categories.products` retorna 200;
- visitante: busca `GET search` com a chave na query (`call('GET', route('search', 'a'), ['searchKey' => 'a'])`) retorna 200;
- visitante: `POST cart.store` redireciona para o login;
- visitante: `GET products.create` redireciona para o login;
- admin: `GET products.create` ainda retorna 200 (prova que `/products/create` não foi capturado pelo `show`).

**Verificação no navegador:** como visitante (sem login), abrir `/`, `/products`, um produto e `/categories`; conferir no console (`read_console_messages`) que não há erro de propriedade de `null`.

**Commit:** `feat(storefront): vitrine pública e navegação sem login`

---

## 7.4 — Imagem por produto (I2)

**Problema:** todas as imagens são o mesmo iMac, de um servidor externo (`flowbite.s3.amazonaws.com`). Os logos de bandeira do pagamento também vêm de lá.

**Arquivos:**
- nova migração `database/migrations/2026_11_26_000007_add_image_path_to_products_table.php`;
- `app/Models/Product.php`;
- `app/Http/Requests/StoreProductRequest.php` e `UpdateProductRequest.php`;
- `app/Http/Controllers/ProductController.php` (`store`, `update`);
- novo `public/images/product-placeholder.svg`;
- `resources/js/Pages/Products/Forms/ProductForm.vue`;
- `resources/js/Pages/Products/Components/ProductCard.vue`;
- `resources/js/Pages/Products/ShowProduct.vue`;
- `resources/js/Pages/Orders/IndexOrders.vue`;
- `resources/js/Pages/Orders/Form/PaymentForm.vue`;
- `tests/Feature/Products/ProductImageTest.php` (novo).

**Mudanças:**

1. **Migração:** adicione `string('image_path')->nullable()` em `products`. Só isso. `down()` remove a coluna.

2. **`Product`:**
   - inclua `'image_path'` em `$fillable`;
   - adicione `protected $appends = ['image_url'];` (é seguro porque o acessor não consulta o banco nem a sessão; a Fase 4 removeu `$appends` só por causa de `auth()` e da sessão);
   - adicione o acessor:
     ```php
     public function getImageUrlAttribute(): string
     {
         return $this->image_path
             ? Storage::disk('public')->url($this->image_path)
             : asset('images/product-placeholder.svg');
     }
     ```
     Com `use Illuminate\Support\Facades\Storage;`.

3. **Validação:** em `StoreProductRequest` e `UpdateProductRequest`, acrescente `'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],`.

4. **Controller:**
   - `store`: `$data = $request->safe()->except('image');` e, se `$request->hasFile('image')`, `$data['image_path'] = $request->file('image')->store('products', 'public');`. Depois, `Product::create($data)`.
   - `update`: mesma lógica. Se houver arquivo novo, apague o antigo com `Storage::disk('public')->delete($product->image_path)` quando `$product->image_path` existir.
   - Nenhum dos dois devolve o arquivo na resposta.

5. **Formulário** (`ProductForm.vue`): adicione um `<input type="file" accept="image/jpeg,image/png,image/webp">` ligado a um campo `image` do `useForm`, com `InputError` para `form.errors.image`. Como PUT com arquivo não chega ao PHP, quando estiver editando envie por `POST` com `_method: 'PUT'` (use `form.transform((data) => ({ ...data, _method: 'PUT' }))` antes de `form.post(...)`). Leia o `ProductForm.vue` atual antes de mexer, porque ele já escolhe o método pela prop `method`.

6. **Placeholder:** crie `public/images/product-placeholder.svg` com um ícone cinza simples (um retângulo de 400×300 com fundo `#e5e7eb` e um contorno de monitor em `#9ca3af`).

7. **Trocas de imagem:**
   - `ProductCard.vue`: as duas `<img>` do Flowbite viram uma `<img :src="product.image_url" :alt="product.name" class="mx-auto h-full object-contain" />`. Some o par `dark:hidden` / `dark:block`.
   - `ShowProduct.vue` (linhas ~214 e ~219): mesma troca, com `localProduct.image_url`.
   - `IndexOrders.vue` (linhas ~62 e ~67): `product.image_url`.
   - `PaymentForm.vue`: remova as `<img>` dos logos de PayPal, Visa e Mastercard e coloque no lugar um `<span>` com o nome da bandeira em texto (`PayPal`, `Visa`, `Mastercard`), mantendo a mesma classe de tamanho.
   - Para o `ProductController::index`, inclua `'image_url' => $product->image_url,` no array do `through`.

8. **Link do storage:** rode `./vendor/bin/sail artisan storage:link`. Confira se `public/storage` aparece em `.gitignore`; se não aparecer, **não** adicione: avise no relatório.

**Testes** (`ProductImageTest.php`, com `Storage::fake('public')`):
- admin cria produto com imagem JPG: `image_path` começa com `products/` e o arquivo existe no disco fake;
- admin atualiza com nova imagem: a antiga é apagada e a nova existe;
- cliente comum tenta criar com imagem: 403, e nenhum arquivo é gravado;
- arquivo que não é imagem (texto) é rejeitado com erro em `image`;
- produto sem imagem: `image_url` termina em `images/product-placeholder.svg`;
- produto com imagem: `image_url` contém `products/`.

**Commit:** `feat(products): imagem por produto com placeholder local`

---

## 7.5 — Nomes de produto plausíveis e categorias em inglês (I4)

**Problema:** `ProductFactory` gera nomes de pessoas ("Prof. Presley Wiegand II"). As categorias do seeder estão em português.

**Arquivos:**
- `database/seeders/CategorySeeder.php`;
- `database/seeders/ProductSeeder.php`.

**Mudanças:**

1. **Categorias em inglês** (mesma ordem, nomes e descrições traduzidos literalmente, em uma frase cada):

   | Nome em português | Nome em inglês |
   |------------------|----------------|
   | Eletrônicos | Electronics |
   | Roupas e Acessórios | Clothing & Accessories |
   | Beleza e Cuidados Pessoais | Beauty & Personal Care |
   | Casa e Decoração | Home & Decor |
   | Esportes e Lazer | Sports & Leisure |
   | Livros, Filmes e Música | Books, Movies & Music |
   | Brinquedos e Jogos | Toys & Games |
   | Alimentos e Bebidas | Food & Beverages |
   | Automotivo | Automotive |
   | Saúde e Bem-estar | Health & Wellness |

   As descrições também viram inglês. Os 10 nomes precisam ficar exatamente como na tabela, porque `ProductSeeder` procura os nomes de produto por eles.

2. **Nomes de produto** em `ProductSeeder`, em um array constante `NAMES` indexado pelo nome em inglês da categoria:
   - Electronics: Wireless Earbuds, Phone Stand, 4K Smart TV, Portable Charger, 10-inch Tablet, Laptop Sleeve, Smartwatch, Bluetooth Speaker;
   - Clothing & Accessories: Cotton T-Shirt, Denim Jeans, Summer Dress, Leather Belt, Canvas Sneakers, Sunglasses, Wool Scarf, Crossbody Bag;
   - Beauty & Personal Care: Hydrating Face Cream, Matte Lipstick, Argan Oil Shampoo, Sunscreen SPF 50, Electric Shaver, Eau de Parfum, Body Lotion;
   - Home & Decor: Ceramic Vase, Linen Bedsheet Set, LED Desk Lamp, Bamboo Cutting Board, Wall Clock, Throw Pillow;
   - Sports & Leisure: Yoga Mat, Adjustable Dumbbells, Camping Tent, Running Shoes, Water Bottle, Mountain Bike;
   - Books, Movies & Music: Mystery Novel, Cookbook, Classic Vinyl Record, Documentary DVD, Acoustic Guitar, Science Fiction Box Set;
   - Toys & Games: Building Blocks Set, Board Game, 1000-Piece Puzzle, Plush Bear, Remote Control Car;
   - Food & Beverages: Dark Chocolate Bar, Organic Granola, Cold Brew Coffee, Green Tea Box, Mixed Nuts, Artisan Olive Oil;
   - Automotive: Seat Covers, Car Floor Mats, Microfiber Towel Set, Engine Oil 5W-30, Dash Camera;
   - Health & Wellness: Multivitamin, Omega-3 Capsules, First Aid Kit, Digital Thermometer, Resistance Bands.

3. **`ProductSeeder::run`:** para cada categoria, crie **3** produtos com `Product::factory()->for($category)->create(['name' => $name])`, usando nomes distintos dentro da mesma categoria (`Arr::shuffle` e `array_slice(..., 0, 3)`). Isso dá 30 produtos no total, que é o que `SeederTest` espera.

4. A `ProductFactory` **não** muda. Os testes continuam usando nomes aleatórios.

**Testes:** `SeederTest.php` continua passando sem alteração (10 categorias, 30 produtos, 10 pedidos). Acrescente um teste: após `$this->seed()`, `Category::pluck('name')` contém `Electronics`, e nenhum produto tem nome com `Prof.` ou `Jr.`.

**Commit:** `refactor(seeders): categorias em inglês e nomes de produto plausíveis`

---

## Pronto da Fase 7

- [ ] 5 commits no branch `melhorias/fase-7`, na ordem 7.1 a 7.5
- [ ] testes novos passando: `RatingSummaryTest`, `VerifiedPurchaseTest`, `PublicStorefrontTest`, `ProductImageTest` e o teste de idioma em `CheckoutTest`
- [ ] suíte completa sem regressões em relação à Fase 6 (112 passed, 7 skipped, mais os testes novos)
- [ ] `sail pint --test`, `sail npm run format:check` e `sail npm run build` limpos
- [ ] `grep -rnE "Compra|Não é|Seu carrinho|Pedido cancelado|Estoque insuficiente" app resources/js` sem resultados fora de comentários
- [ ] verificação no navegador como visitante (`/`, produtos, produto, categorias, busca) e como cliente, em 1280 e 375 px, nos dois temas
- [ ] imagens do Flowbite não aparecem mais: `grep -rn "flowbite" resources/js` vazio
- [ ] `public/storage` criado e fora do git (ou o aviso no relatório, se não estiver no `.gitignore`)

## Regras que continuam valendo

- Um passo = um commit, na mensagem indicada, com a linha de co-autoria do Haiku.
- Altere apenas os arquivos listados no passo. Se precisar de outro, registre o motivo no relatório.
- Não edite `.env`. Não rode `migrate:fresh`, `db:wipe` nem `docker compose down -v`.
- Comandos via Sail, com `APP_PORT=8080 FORWARD_REDIS_PORT=6380 WWWUSER=$(id -u) WWWGROUP=$(id -g)`.
- Mensagens novas para o usuário e textos novos de interface: **em inglês**.
