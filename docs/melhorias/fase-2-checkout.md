# Fase 2 — Carrinho e checkout íntegros

**Branch:** `melhorias/fase-2` (a partir de `melhorias/fase-1` já revisada).
**Pré-requisito:** Fases 0 e 1 concluídas. O passo 1.5 já trocou `card_number`/`ccv` por `card_last_four`.
**Objetivo:** o carrinho respeita o estoque e não quebra quando um produto some. O pedido grava os itens com o preço do momento da compra, baixa o estoque dentro de uma transação, rejeita carrinho vazio e limpa o carrinho no fim. O cancelamento devolve o estoque.

> Formato do carrinho na sessão **depois** desta fase: `session('cart') = [['id' => int, 'quantity' => int], ...]`. Preço, nome e categoria sempre vêm do banco, via `getCartItems()`.

---

## 2.1 — Carrinho: estoque, produto removido, DELETE e quantidade exibida

**Problemas:**
- `addToCart` grava o produto inteiro (`toArray()`) na sessão.
- O estoque não é validado ao adicionar nem ao atualizar.
- `getCartItems()` quebra (`$product->id` em `null`) se um produto do carrinho foi apagado.
- A remoção usa `GET`.
- `ProductController@index` lê `$cartProduct->quantity` num **array**, e por isso a quantidade exibida é sempre 1.

**Arquivos:** `app/Http/Traits/CartTrait.php`, `app/Http/Controllers/CartController.php`, `app/Http/Controllers/ProductController.php` (só o `index`), `routes/web.php`, `resources/js/Pages/Cart/Components/CartButton.vue`, `resources/js/Pages/Orders/Components/OrderProductCard.vue`, novo `tests/Feature/Cart/CartTest.php`.

**Mudanças:**
1. `CartTrait::addToCart` muda de assinatura (o único chamador é o `CartController@store`):
   ```php
   public function addToCart(Product $product, int $quantity): void
   {
       $this->initializeCartSession();

       if ($this->isInCart($product)) {
           return;
       }

       session()->push('cart', ['id' => $product->id, 'quantity' => $quantity]);
   }
   ```
2. `CartTrait::getCartItems()`: ignore itens cujo produto não existe mais e regrave a sessão sem eles.
   ```php
   public function getCartItems(): array
   {
       $cart = session()->get('cart', []);

       $products = Product::with('category')
           ->whereIn('id', collect($cart)->pluck('id'))
           ->get()
           ->keyBy('id');

       $existing = array_values(array_filter($cart, fn ($item) => $products->has($item['id'])));

       if (count($existing) !== count($cart)) {
           session()->put('cart', $existing);
       }

       return array_map(function ($item) use ($products) {
           $product = $products->get($item['id']);

           return [
               'id' => $product->id,
               'name' => $product->name,
               'price' => $product->price,
               'quantity' => $item['quantity'],
               'stock' => $product->stock,
               'isFavorite' => $product->isFavorite,
               'category' => $product->category->name,
               'category_id' => $product->category_id,
           ];
       }, $existing);
   }
   ```
3. `CartController@store`:
   ```php
   $product = Product::findOrFail($validated['product_id']);

   if ($validated['quantity'] > $product->stock) {
       return back()->withErrors(['quantity' => 'Quantidade maior que o estoque disponível.']);
   }

   $this->addToCart($product, $validated['quantity']);

   return back()->with(['message' => 'Product successfully added to cart!', 'success' => true]);
   ```
   Remova o `if (!$product)` (o `findOrFail` já cobre esse caso), a linha `$product->quantity = ...` e o `;;`.
4. `CartController@update`:
   - se o produto não está no carrinho: `return back()->withErrors(['cart' => 'O produto não está no carrinho.']);`
   - se `quantity > $product->stock`: `return back()->withErrors(['quantity' => 'Quantidade maior que o estoque disponível.']);`
5. `CartController@destroy` termina com `return back();`.
6. Rota: `Route::delete('/cart/{product}', [CartController::class, 'destroy'])->name('cart.destroy');` (substitui o `GET /cart/destroy/{product}`).
7. Frontend: nas chamadas `router.visit(route("cart.destroy", ...), {...})` de `CartButton.vue` e `OrderProductCard.vue`, adicione `method: "delete",` nas opções.
8. `ProductController@index`: troque `$cartProduct->quantity ?? 1` por `$cartProduct['quantity'] ?? 1`.

**Testes** (`CartTest.php`):
- `POST cart.store` com quantidade 2 resulta em `assertSessionHas('cart', [['id' => $p->id, 'quantity' => 2]])`;
- quantidade acima do estoque gera `assertSessionHasErrors('quantity')`, e o carrinho continua vazio;
- `PUT cart.update` dentro do estoque atualiza a sessão; acima do estoque gera erro em `quantity`;
- `DELETE cart.destroy` remove o item; um `GET` na mesma URL retorna 405;
- um produto apagado depois de entrar no carrinho não quebra: `GET cart.index` retorna 200, a prop `products` vem vazia e a sessão fica sem o item;
- com um único produto no banco e quantidade 3 no carrinho (`withSession`), `GET products.index` tem `->where('products.data.0.quantity', 3)`.

**Verificação extra:** `sail npm run build`.

**Commit:** `fix(cart): valida estoque, tolera produto removido e usa DELETE na remoção`

---

## 2.2 — Checkout com `PlaceOrder` em transação

**Problemas:** o pedido não grava os itens em `orders_products`, não baixa o estoque, não roda em transação, aceita carrinho vazio (gerando pedido de R$ 0) e não limpa o carrinho. Além disso, `createFromFormat('m/Y')` herda o dia atual: num dia 31, `02/2027` vira março.

**Arquivos:**
- nova migration `database/migrations/2026_11_24_000002_add_unit_price_to_orders_products_table.php`;
- `app/Models/Order.php` e `app/Models/Product.php` (pivot);
- `app/Http/Requests/StoreOrderRequest.php` (reescrito);
- novo `app/Actions/Order/PlaceOrder.php`;
- `app/Http/Controllers/OrderController.php`;
- `resources/js/Pages/Orders/Form/PaymentForm.vue`;
- apagar `app/Actions/Payment/StoreNewPayment.php`, `app/Http/Requests/StorePaymentRequest.php` e `app/Http/Requests/UpdateOrderRequest.php`;
- `routes/web.php` (remover import);
- novo `tests/Feature/Orders/CheckoutTest.php`.

**Mudanças:**
1. Migration: `orders_products` ganha `$table->decimal('unit_price', 10, 2)->nullable();` (o `down` remove a coluna). Rode `sail artisan migrate`.
2. Nos relacionamentos `Order::products()` e `Product::orders()`: `->withPivot('quantity', 'unit_price')`.
3. `StoreOrderRequest`:
   ```php
   public function authorize(): bool
   {
       return true;
   }

   public function rules(): array
   {
       return [
           'fullName' => ['required', 'string', 'min:3', 'max:255'],
           'cardNumber' => ['required', 'regex:/^[0-9\s]{16,20}$/'],
           'ccv' => ['required', 'digits_between:3,4'],
           'cardExpiration' => ['required', 'date_format:m/Y', 'after_or_equal:today'],
       ];
   }

   public function messages(): array
   {
       return [
           'cardNumber.regex' => 'Número do cartão inválido.',
           'ccv.digits_between' => 'O CVV deve ter 3 ou 4 dígitos.',
           'cardExpiration.date_format' => 'Use o formato MM/AAAA.',
           'cardExpiration.after_or_equal' => 'Cartão vencido.',
       ];
   }
   ```
4. `app/Actions/Order/PlaceOrder.php`:
   ```php
   <?php

   namespace App\Actions\Order;

   use App\Models\Order;
   use App\Models\Product;
   use App\Models\User;
   use Carbon\Carbon;
   use Illuminate\Support\Facades\DB;
   use Illuminate\Validation\ValidationException;

   class PlaceOrder
   {
       /**
        * @param  array<int, array{id: int, quantity: int}>  $cartItems
        * @param  array{fullName: string, cardNumber: string, cardExpiration: string}  $payment
        */
       public function handle(User $user, array $cartItems, array $payment): Order
       {
           if ($cartItems === []) {
               throw ValidationException::withMessages(['cart' => 'Seu carrinho está vazio.']);
           }

           return DB::transaction(function () use ($user, $cartItems, $payment) {
               $quantities = collect($cartItems)
                   ->mapWithKeys(fn ($item) => [$item['id'] => (int) $item['quantity']]);

               $products = Product::whereIn('id', $quantities->keys())
                   ->lockForUpdate()
                   ->get()
                   ->keyBy('id');

               $subtotal = 0;
               $pivot = [];

               foreach ($quantities as $productId => $quantity) {
                   $product = $products->get($productId);

                   if (! $product || $product->stock < $quantity) {
                       throw ValidationException::withMessages([
                           'cart' => 'Estoque insuficiente para '.($product->name ?? 'um dos produtos').'.',
                       ]);
                   }

                   $subtotal += $product->price * $quantity;
                   $pivot[$productId] = ['quantity' => $quantity, 'unit_price' => $product->price];
               }

               $order = $user->orders()->create([
                   'status' => 'pending_payment',
                   'subtotal' => round($subtotal, 2),
               ]);

               $order->products()->attach($pivot);

               foreach ($pivot as $productId => $item) {
                   $products->get($productId)->decrement('stock', $item['quantity']);
               }

               $order->payments()->create([
                   'full_name' => $payment['fullName'],
                   'card_last_four' => substr(preg_replace('/\D/', '', $payment['cardNumber']), -4),
                   'card_expiration' => Carbon::createFromFormat('!m/Y', $payment['cardExpiration'])->toDateString(),
               ]);

               // Pagamento simulado: aprovado na hora.
               $order->update(['status' => 'transporting']);

               return $order;
           });
       }
   }
   ```
5. `OrderController`:
   - `use CartTrait;` (de `App\Http\Traits\CartTrait`);
   - `store`:
     ```php
     public function store(StoreOrderRequest $request, PlaceOrder $placeOrder): RedirectResponse
     {
         $placeOrder->handle($request->user(), $this->getCartItems(), $request->validated());

         $this->clearCart();

         return to_route('orders.index')->with('success', 'Pedido realizado com sucesso!');
     }
     ```
   - no início de `create`:
     ```php
     if ($this->getCartItems() === []) {
         return to_route('cart.index')->withErrors(['cart' => 'Seu carrinho está vazio.']);
     }
     ```
   - remova os imports sem uso: `StoreNewPayment`, `PaymentController`, `Payment` e o `Request` se sobrar sem uso (o `update` ainda usa `Request`).
6. Apague `StoreNewPayment.php`, `StorePaymentRequest.php` e `UpdateOrderRequest.php`. Antes, confirme com `grep -rn "StoreNewPayment\|StorePaymentRequest\|UpdateOrderRequest" app routes tests` que só o `OrderController` referenciava o primeiro.
7. `routes/web.php`: remova `use App\Http\Controllers\PaymentController;` (a classe não existe).
8. `PaymentForm.vue`: logo no início do `<form>`, `<InputError :message="form.errors.cart" class="mb-4" />`.

**Testes** (`CheckoutTest.php`; monte o carrinho sempre com `withSession(['cart' => [...]])`; payload de cartão válido igual ao do `PaymentDataTest`):
- **sucesso:** produto A (preço `10.00`, estoque 10) com quantidade 2 e produto B (preço `2.50`, estoque 5) com quantidade 1 geram um pedido com:
  - `(float) subtotal === 22.5` e `status === 'transporting'`;
  - `$order->products` com 2 itens e `pivot->quantity`/`pivot->unit_price` corretos;
  - estoques finais 8 e 4;
  - `assertSessionMissing('cart')`;
  - `Payment` com `card_last_four` correto;
- **carrinho vazio:** `assertSessionHasErrors('cart')` e `Order::count() === 0`;
- **estoque insuficiente** (estoque 1, quantidade 3): erro em `cart`, nenhum `Order` nem `Payment` criado, estoque continua 1;
- **preço vem do banco:** um item de sessão com `'price' => 0.01` resulta em `subtotal` igual ao preço do produto;
- **`GET orders.create` com carrinho vazio** redireciona para `cart.index`;
- **data do cartão em dia 31:** com `$this->travelTo(Carbon::parse('2026-01-31'))` e `cardExpiration = '02/2027'`, `payment->card_expiration` começa com `'2027-02-01'`.

O `PaymentDataTest` da Fase 1 deve continuar passando sem alteração.

**Verificação extra:** `sail npm run build`.

**Commit:** `feat(orders): grava itens, baixa estoque e valida carrinho em transação no checkout`

---

## 2.3 — Cancelamento devolve o estoque

**Arquivos:** `app/Http/Controllers/OrderController.php` (`update`), `tests/Feature/Orders/OrderAuthorizationTest.php` (acrescentar casos).

**Mudança:** no `update`, depois das checagens de policy e status, troque `$order->update(['status' => 'canceled']);` por:
```php
DB::transaction(function () use ($order) {
    $order->load('products');

    foreach ($order->products as $product) {
        $product->increment('stock', $product->pivot->quantity);
    }

    $order->update(['status' => 'canceled']);
});
```
Import: `Illuminate\Support\Facades\DB`.

**Testes:**
- um pedido `transporting` com um produto (estoque 5) anexado com quantidade 2 fica com estoque 7 depois do cancelamento;
- uma segunda tentativa de cancelar o mesmo pedido gera erro em `status`, e o estoque continua 7.

**Commit:** `fix(orders): devolve estoque ao cancelar pedido`

---

## 2.4 — Bugs pontuais do `ProductController`

**Problemas:**
- `update` faz `to_route('products.show', $id)` com `$id` booleano, então sempre redireciona para `/products/1`.
- Em `show`, o bloco `->through(function ...)` não tem efeito: é o `through()` de relação has-through do Model, não o do paginator.

**Arquivos:** `app/Http/Controllers/ProductController.php`, novo `tests/Feature/Products/ProductUpdateTest.php`.

**Mudanças:**
1. `update`:
   ```php
   $product->update($request->validated());

   return to_route('products.show', $product);
   ```
2. `show`: substitua toda a chamada `$product->load(...)->through(...)` por `$product->load('category', 'ratings.user');`.

**Testes:**
- um admin atualiza o segundo produto criado e recebe `assertRedirect(route('products.show', $produto))`, não `/products/1`;
- `GET products.show` retorna 200 com `->has('product.ratings')`.

**Commit:** `fix(products): corrige redirect após edição e remove código sem efeito`

---

## Pronto da Fase 2

- [ ] 4 commits no branch `melhorias/fase-2`
- [ ] `CartTest`, `CheckoutTest`, `OrderAuthorizationTest` e `ProductUpdateTest` passando
- [ ] suíte completa sem regressões
- [ ] `sail pint --test`, `sail npm run format:check` e `sail npm run build` limpos
- [ ] migrations aplicadas no banco de dev
- [ ] teste manual sugerido (feito pelo usuário): adicionar 2 produtos ao carrinho, finalizar, ver o pedido com os itens e o estoque reduzido, cancelar e ver o estoque voltar
