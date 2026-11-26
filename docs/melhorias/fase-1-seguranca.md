# Fase 1 — Segurança

**Branch:** `melhorias/fase-1` (a partir de `melhorias/fase-0` já revisada).
**Pré-requisito:** Fase 0 concluída (factories, helpers `admin()`/`customer()`, banco de testes).
**Objetivo:** cada usuário só acessa e altera o que é dele, só admin gerencia o catálogo e o sistema para de guardar número de cartão e CVV.

> Os trechos de código abaixo mostram a forma final esperada. Como a Fase 0 aplicou o Pint, a formatação atual dos arquivos pode diferir um pouco; o que vale é o comportamento.

---

## 1.1 — Acesso de admin: 403, Policies reais e rotas sem método

**Problemas:**
- O middleware `isUserAdmin` responde **401** (não autenticado) quando o correto é **403** (sem permissão).
- `User::isAdmin()` checa `Auth::check()` em vez do próprio usuário.
- `ProductPolicy` e `CategoryPolicy` são stubs que retornam `false`. Como o Laravel as descobre automaticamente, qualquer `can()` futuro negaria tudo.
- `Route::resource('categories')` registra `categories.show`, mas o controller não tem `show()`, o que gera erro 500.

**Arquivos:** `app/Models/User.php`, `app/Http/Middleware/isUserAdmin.php`, `app/Policies/ProductPolicy.php`, `app/Policies/CategoryPolicy.php`, `app/Http/Requests/{StoreProductRequest,UpdateProductRequest,StoreCategoryRequest,UpdateCategoryRequest}.php`, `routes/web.php`, novo `tests/Feature/Admin/AdminAccessTest.php`.

**Mudanças:**
1. `User`:
   - em `casts()`, adicione `'isAdmin' => 'boolean'`;
   - `isAdmin()` passa a ser `return (bool) $this->isAdmin;`;
   - remova o `use Illuminate\Support\Facades\Auth;` se ficar sem uso.
2. `isUserAdmin::handle()`:
   ```php
   abort_unless($request->user()?->isAdmin(), 403);

   return $next($request);
   ```
3. `ProductPolicy` e `CategoryPolicy` (mesma estrutura nas duas; remova `restore`/`forceDelete` e o `use Response` sem uso):
   ```php
   public function viewAny(User $user): bool { return true; }
   public function view(User $user, Product $product): bool { return true; }
   public function create(User $user): bool { return $user->isAdmin(); }
   public function update(User $user, Product $product): bool { return $user->isAdmin(); }
   public function delete(User $user, Product $product): bool { return $user->isAdmin(); }
   ```
4. Nos quatro FormRequests, `authorize()` passa a ser `return $this->user()->isAdmin();`.
5. `routes/web.php`: `Route::resource('categories', CategoryController::class)->except(['show']);`
   Antes, confirme com `grep -rn "categories.show" resources/js` que nada usa essa rota (hoje nada usa).

**Testes** (`tests/Feature/Admin/AdminAccessTest.php`):
- um cliente recebe 403 em `GET products.create`, `GET categories.create`, `POST products.store` e `DELETE products.destroy`, e o produto continua no banco depois do DELETE;
- um admin recebe 200 em `GET products.create`;
- um admin cria um produto via `POST products.store` com `['name' => 'X', 'description' => 'Y', 'price' => '10.50', 'stock' => 5, 'category_id' => Category::factory()->create()->id]`, recebe redirect e o produto existe;
- um cliente recebe 200 em `GET products.index` e em `GET categories.index`.

**Commit:** `fix(auth): retorna 403 para não-admins e implementa policies de catálogo`

---

## 1.2 — Pedidos: cada usuário vê e cancela só os próprios

**Problemas:** `OrderController@index` lista os pedidos **de todos os usuários**, e `update` deixa qualquer usuário cancelar qualquer pedido, inclusive um já concluído. A `OrderPolicy` é um stub. As rotas `orders.show`, `orders.edit` e `orders.destroy` existem com métodos vazios ou inexistentes.

**Arquivos:** `app/Models/User.php`, `app/Policies/OrderPolicy.php`, `app/Http/Controllers/OrderController.php`, `routes/web.php`, novo `tests/Feature/Orders/OrderAuthorizationTest.php`.

**Mudanças:**
1. `User`: adicione
   ```php
   public function orders(): HasMany
   {
       return $this->hasMany(Order::class);
   }
   ```
2. `OrderPolicy` (remova `restore`/`forceDelete`):
   - `viewAny`, `create`: `true`;
   - `view`, `update`: `$user->id === $order->user_id`;
   - `delete`: `false`.
3. `OrderController@index`:
   ```php
   public function index(Request $request)
   {
       return Inertia('Orders/IndexOrders', [
           'orders' => $request->user()->orders()
               ->with(['payments', 'products'])
               ->latest('id')
               ->get(),
       ]);
   }
   ```
4. `OrderController@update`:
   ```php
   public function update(Request $request, Order $order): RedirectResponse
   {
       Gate::authorize('update', $order);

       $request->validate(['status' => ['required', 'in:canceled']]);

       if (in_array($order->status, ['completed', 'canceled'], true)) {
           return back()->withErrors(['status' => 'Este pedido não pode mais ser cancelado.']);
       }

       $order->update(['status' => 'canceled']);

       return back()->with('success', 'Pedido cancelado.');
   }
   ```
   Imports: `Illuminate\Http\RedirectResponse` e `Illuminate\Support\Facades\Gate`.
5. Remova os métodos vazios `show()` e `destroy()` do `OrderController`.
6. `routes/web.php`: `Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'update']);`
   Antes, confirme com `grep -rnE "orders\.(show|edit|destroy)" resources/js` que nada usa essas rotas.

**Testes** (`tests/Feature/Orders/OrderAuthorizationTest.php`; sempre defina `status` explicitamente nas factories):
- com 2 pedidos do usuário A e 1 do usuário B, A acessa `orders.index` e `assertInertia(fn ($page) => $page->component('Orders/IndexOrders')->has('orders', 2))`;
- B faz `PUT orders.update` no pedido de A com `status=canceled` e recebe 403; o status continua o mesmo;
- o dono cancela um pedido `pending_payment` e o status vira `canceled`;
- o dono tenta cancelar um pedido `completed`, recebe `assertSessionHasErrors('status')` e o status continua `completed`;
- `status=completed` enviado pelo dono gera erro de validação.

**Commit:** `fix(orders): restringe listagem e cancelamento ao dono do pedido`

---

## 1.3 — Favoritos: usar o usuário autenticado

**Problemas:**
- As rotas `/favorite/{user}/{product}` recebem o usuário pela URL, então qualquer pessoa altera os favoritos de outra.
- O controller retorna `void`, o que quebra o Inertia.
- O botão de desfavoritar envia `POST`, que cai no `store` em vez do `destroy`. Hoje **não dá para remover um favorito**.

**Arquivos:** `routes/web.php`, `app/Http/Controllers/FavoriteProductsController.php`, `resources/js/Pages/Favorites/FavoriteButton.vue`, novo `tests/Feature/Favorites/FavoriteProductsTest.php`.

**Mudanças:**
1. Rotas (substitua as duas atuais e importe o controller no topo do arquivo):
   ```php
   Route::post('/favorites/{product}', [FavoriteProductsController::class, 'store'])->name('favorite.store');
   Route::delete('/favorites/{product}', [FavoriteProductsController::class, 'destroy'])->name('favorite.destroy');
   ```
2. Controller (remova o método `isFavorite`; confirme com `grep -rn "isFavorite(" app` que não há outro uso):
   ```php
   public function store(Request $request, Product $product): RedirectResponse
   {
       $request->user()->favoriteProducts()->syncWithoutDetaching([$product->id]);

       return back();
   }

   public function destroy(Request $request, Product $product): RedirectResponse
   {
       $request->user()->favoriteProducts()->detach($product->id);

       return back();
   }
   ```
3. `FavoriteButton.vue`:
   - nas duas chamadas, `route("favorite.store", { product: localProduct.value.id })` e `route("favorite.destroy", { product: localProduct.value.id })`, sem `user`;
   - em `removeFromFavorites`, `method: "DELETE"`;
   - remova `usePage` e `page` se ficarem sem uso;
   - corrija a mensagem de erro de `addToFavorites` para `"Error adding product to favorites."`.

**Testes:**
- `POST favorite.store` cria o vínculo para o usuário logado;
- dois `POST` seguidos geram um único registro em `favorite_products`;
- `DELETE favorite.destroy` remove o vínculo;
- o favorito de A não aparece em `B->favoriteProducts`.

**Verificação extra:** `sail npm run build`.

**Commit:** `fix(favorites): usa usuário autenticado e corrige remoção de favoritos`

---

## 1.4 — Avaliações: usar o usuário autenticado

**Problema:** `/rating/{user}/{product}` permite avaliar um produto em nome de outro usuário.

**Arquivos:** `routes/web.php`, `app/Http/Controllers/ProductRatingController.php`, `resources/js/Pages/Products/ShowProduct.vue`, novo `tests/Feature/Ratings/ProductRatingTest.php`.

**Mudanças:**
1. Rota (substitua a atual e importe o controller):
   ```php
   Route::post('/products/{product}/ratings', [ProductRatingController::class, 'store'])->name('rating.store');
   ```
2. Controller (remova o construtor e a propriedade):
   ```php
   public function store(Request $request, Product $product): RedirectResponse
   {
       $validated = $request->validate([
           'rating' => ['required', 'numeric', 'min:0', 'max:5'],
           'feedback' => ['required', 'string', 'max:128'],
       ]);

       ProductRating::updateOrCreate(
           ['product_id' => $product->id, 'user_id' => $request->user()->id],
           $validated,
       );

       return back();
   }
   ```
3. `ShowProduct.vue`, em `submitRating`:
   - `route("rating.store", { product: localProduct.id })`, sem `user` e sem `data`;
   - remova do `onSuccess` as linhas `localProduct.inCart = false;` e `localProduct.quantity = 1;` (sobraram de um copiar e colar).

**Testes:**
- o POST cria a avaliação com o `user_id` do usuário logado;
- um segundo POST do mesmo usuário atualiza a avaliação: continua 1 registro, com a nota nova;
- `rating = 6` gera `assertSessionHasErrors('rating')`.

**Verificação extra:** `sail npm run build`.

**Commit:** `fix(ratings): grava avaliação sempre para o usuário autenticado`

---

## 1.5 — Parar de armazenar número do cartão e CVV

**Problema:** a tabela `payments` guarda o número completo do cartão e o CVV, o que viola o PCI-DSS. O formulário vem pré-preenchido com um nome e um cartão fixos, e o CVV é `type="number"`, onde `maxlength` não funciona.

**Decisão:** guardar apenas `card_last_four`. A validação do cartão continua, como se os dados fossem enviados a um gateway, mas número e CVV nunca são persistidos. (A ideia 1, gateway simulado com interface, fica para depois e só se for aprovada.)

**Arquivos:** nova migration `database/migrations/2026_11_24_000001_remove_card_data_from_payments_table.php`, `app/Models/Payment.php`, `database/factories/PaymentFactory.php`, `app/Http/Controllers/OrderController.php`, `resources/js/Pages/Orders/Form/PaymentForm.vue`, novo `tests/Feature/Orders/PaymentDataTest.php`.

**Mudanças:**
1. Migration:
   ```php
   public function up(): void
   {
       Schema::table('payments', function (Blueprint $table) {
           $table->string('card_last_four', 4)->nullable();
       });

       DB::table('payments')->orderBy('id')->each(function ($payment) {
           $digits = preg_replace('/\D/', '', (string) $payment->card_number);

           DB::table('payments')
               ->where('id', $payment->id)
               ->update(['card_last_four' => substr($digits, -4) ?: null]);
       });

       Schema::table('payments', function (Blueprint $table) {
           $table->dropColumn(['card_number', 'ccv']);
       });
   }

   public function down(): void
   {
       Schema::table('payments', function (Blueprint $table) {
           $table->string('card_number')->nullable();
           $table->integer('ccv')->nullable();
           $table->dropColumn('card_last_four');
       });
   }
   ```
   **Não** altere a migration original de `payments`.
2. `Payment::$fillable`: `['full_name', 'card_last_four', 'card_expiration', 'order_id']`.
3. `PaymentFactory`: troque `ccv` e `card_number` por `'card_last_four' => (string) fake()->numberBetween(1000, 9999),`.
4. `OrderController@store`:
   - regra do CVV: `'ccv' => 'required|digits_between:3,4'`;
   - na criação do `Payment`, troque `ccv` e `card_number` por
     `'card_last_four' => substr(preg_replace('/\D/', '', $validated['cardNumber']), -4),`
     e remova a linha `'order_id' => $order` (o `StoreNewPayment` já define esse campo).
5. `PaymentForm.vue`:
   - valores iniciais do `useForm` vazios: `fullName: ""`, `cardNumber: ""`, `ccv: ""`, `cardExpiration: ""`;
   - `submit()` passa a ser `form.post(route("orders.store"));`, e o import de `router` sai;
   - em `formatInput`, troque `formattedDate.value = value;` por `form.cardExpiration = value;` e remova o `ref` `formattedDate` (e o import de `ref`, se ficar sem uso);
   - input do CVV: `type="text" inputmode="numeric" maxlength="4"`;
   - importe `InputError from "@/Components/InputError.vue"` e coloque `<InputError :message="form.errors.X" class="mt-2" />` logo abaixo de cada um dos quatro inputs, com `X` = `fullName`, `cardNumber`, `cardExpiration`, `ccv`.
6. Rode a migration no banco de dev: `./vendor/bin/sail artisan migrate`.

**Testes** (`PaymentDataTest.php`):
- o schema não tem `card_number` nem `ccv` (`Schema::hasColumn` retorna `false` para cada um) e tem `card_last_four`;
- checkout com `withSession(['cart' => [['id' => $product->id, 'quantity' => 1]]])` e o payload
  `['fullName' => 'Fulano de Tal', 'cardNumber' => '4111 1111 1111 1234', 'ccv' => '123', 'cardExpiration' => now()->addYear()->format('m/Y')]`
  gera um `Payment` com `card_last_four === '1234'`, e `json_encode(Payment::first()->toArray())` não contém `'41111111'`;
- `ccv = '12'` gera erro de validação em `ccv`.

**Verificação extra:** `sail npm run build`.

**Commit:** `fix(payments): deixa de armazenar número do cartão e CVV`

---

## Pronto da Fase 1

- [ ] 5 commits no branch `melhorias/fase-1`
- [ ] testes novos em `tests/Feature/{Admin,Orders,Favorites,Ratings}` passando
- [ ] suíte completa sem regressões em relação ao baseline
- [ ] `sail pint --test`, `sail npm run format:check` e `sail npm run build` limpos
- [ ] migration aplicada no banco de dev
