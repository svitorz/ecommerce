# Fase 3 — Integridade do banco

**Branch:** `melhorias/fase-3` (a partir de `melhorias/fase-2` já revisada).
**Pré-requisito:** Fases 0, 1 e 2 concluídas.
**Objetivo:** o banco impede dados inválidos mesmo quando a aplicação falha. Preço vira `decimal(10,2)`, favorito e avaliação ficam únicos por usuário e produto, as relações ganham chaves estrangeiras com regra explícita de exclusão, e o status do pedido vira enum.

> Antes de qualquer migration desta fase, rode as verificações de dados do passo 3.2 e 3.3 no banco de dev. Se alguma consulta retornar linhas, **pare e reporte**. Não apague nem corrija dados automaticamente.

---

## 3.1 — Preço como `decimal(10,2)`

**Problema:** `products.price` é `float`. Valor monetário em ponto flutuante acumula erro de arredondamento.

**Arquivos:**
- nova migration `database/migrations/2026_11_24_000003_change_price_to_decimal_on_products_table.php`;
- `app/Models/Product.php`;
- novo `tests/Feature/Products/PriceTest.php`.

**Mudanças:**
1. Migration:
   ```php
   public function up(): void
   {
       Schema::table('products', function (Blueprint $table) {
           $table->decimal('price', 10, 2)->change();
       });
   }

   public function down(): void
   {
       Schema::table('products', function (Blueprint $table) {
           $table->float('price')->change();
       });
   }
   ```
   Rode `sail artisan migrate`.
2. `Product`: logo depois de `$fillable`, adicione:
   ```php
   protected $casts = ['price' => 'float'];
   ```
   O cast mantém o tipo `float` na aplicação, então o frontend não muda. O banco guarda o valor exato.

**Testes** (`PriceTest.php`):
- a coluna `price` de `products` tem tipo `numeric`: `DB::selectOne("select data_type from information_schema.columns where table_name = 'products' and column_name = 'price'")->data_type` é `'numeric'`;
- `Product::factory()->create(['price' => 19.999])->fresh()->price` é `20.0` (arredondado para 2 casas pelo banco);
- `Product::factory()->create(['price' => 19.9])->fresh()->price` é `19.9` e a instância é `float`.

**Commit:** `refactor(products): guarda preço como decimal(10,2)`

---

## 3.2 — Favorito e avaliação únicos por usuário e produto

**Problema:** `favorite_products` e `product_ratings` aceitam linhas duplicadas para o mesmo par usuário/produto. O código hoje já evita duplicar (`syncWithoutDetaching` e `updateOrCreate`), mas nada no banco garante isso, e uma corrida entre dois cliques passa.

**Verificação prévia** (rode no banco de dev via `sail artisan tinker --execute`):
```sql
select user_id, product_id, count(*) from favorite_products group by user_id, product_id having count(*) > 1;
select user_id, product_id, count(*) from product_ratings group by user_id, product_id having count(*) > 1;
```
Se alguma retornar linhas, pare e reporte.

**Arquivos:**
- nova migration `database/migrations/2026_11_24_000004_add_unique_to_favorites_and_ratings_tables.php`;
- novo `tests/Feature/Database/IntegrityTest.php` (este arquivo também recebe os testes do passo 3.3).

**Mudanças:**
1. Migration:
   ```php
   public function up(): void
   {
       Schema::table('favorite_products', function (Blueprint $table) {
           $table->unique(['user_id', 'product_id']);
       });

       Schema::table('product_ratings', function (Blueprint $table) {
           $table->unique(['user_id', 'product_id']);
       });
   }

   public function down(): void
   {
       Schema::table('favorite_products', function (Blueprint $table) {
           $table->dropUnique(['user_id', 'product_id']);
       });

       Schema::table('product_ratings', function (Blueprint $table) {
           $table->dropUnique(['user_id', 'product_id']);
       });
   }
   ```
2. Nenhuma mudança nos controllers. `FavoriteProductsController` e `ProductRatingController` já são idempotentes.

**Testes** (`IntegrityTest.php`):
- inserir o mesmo par `user_id`/`product_id` em `favorite_products` lança `UniqueConstraintViolationException`;
- o mesmo vale para `product_ratings`;
- `POST favorite.store` duas vezes para o mesmo produto deixa uma linha só.

**Commit:** `fix(favorites): impede favorito e avaliação duplicados no banco`

---

## 3.3 — Chaves estrangeiras e regra de exclusão

**Problema:** as tabelas `addresses`, `orders`, `payments`, `favorite_products`, `product_ratings` e `products` não têm chave estrangeira. Hoje, apagar um produto deixa favoritos e avaliações órfãos, e nada impede pedido com usuário inexistente.

**Verificação prévia** (rode no banco de dev; cada consulta deve retornar `0`):
```sql
select count(*) from addresses a left join users u on u.id = a.user_id where u.id is null;
select count(*) from orders o left join users u on u.id = o.user_id where u.id is null;
select count(*) from payments p left join orders o on o.id = p.order_id where o.id is null;
select count(*) from favorite_products f left join users u on u.id = f.user_id where u.id is null;
select count(*) from favorite_products f left join products p on p.id = f.product_id where p.id is null;
select count(*) from product_ratings r left join users u on u.id = r.user_id where u.id is null;
select count(*) from product_ratings r left join products p on p.id = r.product_id where p.id is null;
select count(*) from products p left join categories c on c.id = p.category_id where c.id is null;
```
Se algum resultado for diferente de `0`, pare e reporte.

**Regra de exclusão escolhida** (decisão deste brief):

| Coluna | Referência | Ao apagar o pai |
|--------|-----------|-----------------|
| `addresses.user_id` | `users.id` | cascade |
| `orders.user_id` | `users.id` | restrict |
| `payments.order_id` | `orders.id` | cascade |
| `favorite_products.user_id` | `users.id` | cascade |
| `favorite_products.product_id` | `products.id` | cascade |
| `product_ratings.user_id` | `users.id` | cascade |
| `product_ratings.product_id` | `products.id` | cascade |
| `products.category_id` | `categories.id` | restrict |
| `orders_products.product_id` | `products.id` | restrict (troca o cascade atual) |

Pedido é registro financeiro: não some junto com o usuário, o produto ou a categoria.

**Arquivos:**
- nova migration `database/migrations/2026_11_24_000005_add_foreign_keys_to_tables.php`;
- `app/Http/Controllers/CategoryController.php` (`destroy`);
- `app/Http/Controllers/ProductController.php` (`destroy`);
- `app/Actions/Jetstream/DeleteUser.php`;
- `tests/Feature/Database/IntegrityTest.php` (acrescentar casos).

**Mudanças:**
1. Migration. `up()` adiciona as chaves; `orders_products` troca a chave atual por uma `restrict`:
   ```php
   public function up(): void
   {
       Schema::table('addresses', function (Blueprint $table) {
           $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
       });

       Schema::table('orders', function (Blueprint $table) {
           $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
       });

       Schema::table('payments', function (Blueprint $table) {
           $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
       });

       Schema::table('favorite_products', function (Blueprint $table) {
           $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
           $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
       });

       Schema::table('product_ratings', function (Blueprint $table) {
           $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
           $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
       });

       Schema::table('products', function (Blueprint $table) {
           $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
       });

       Schema::table('orders_products', function (Blueprint $table) {
           $table->dropForeign(['product_id']);
           $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
       });
   }
   ```
   `down()` faz o inverso: remove as chaves com `dropForeign(['coluna'])` em cada tabela e recria `orders_products.product_id` com `cascadeOnDelete()`.
   Rode `sail artisan migrate`.
2. `CategoryController::destroy` hoje não retorna nada e, com a chave `restrict`, apagar categoria com produto gera erro 500. Substitua o corpo por:
   ```php
   public function destroy(Category $category)
   {
       if ($category->product()->exists()) {
           return back()->withErrors(['category' => 'Não é possível excluir categoria que possui produtos.']);
       }

       $category->delete();

       return back();
   }
   ```
3. `ProductController::destroy`: pedido com produto impede a exclusão. Substitua o corpo por:
   ```php
   public function destroy(Product $product)
   {
       if ($product->orders()->exists()) {
           return back()->withErrors(['product' => 'Não é possível excluir produto que já foi pedido.']);
       }

       $product->delete();

       return to_route('products.index');
   }
   ```
4. `app/Actions/Jetstream/DeleteUser.php`: antes de `$user->delete()`, adicione:
   ```php
   if ($user->orders()->exists()) {
       throw ValidationException::withMessages([
           'password' => 'Não é possível excluir a conta com pedidos registrados.',
       ]);
   }
   ```
   Adicione `use Illuminate\Validation\ValidationException;`. Não altere mais nada no arquivo.

**Testes** (`IntegrityTest.php`):
- inserir `addresses` com `user_id` inexistente lança `QueryException`;
- apagar um usuário com pedido lança `QueryException` e o usuário continua no banco;
- apagar um produto com favorito remove o favorito (cascade): `DB::table('favorite_products')->count()` vira `0`;
- apagar um produto com avaliação remove a avaliação (cascade);
- `DELETE categories.destroy` em categoria com produto retorna `assertSessionHasErrors('category')` e a categoria continua no banco;
- `DELETE products.destroy` em produto com pedido retorna `assertSessionHasErrors('product')` e o produto continua no banco;
- `app(DeleteUser::class)->delete($usuarioComPedido)` lança `ValidationException`.

Todos os testes de admin usam o helper `admin()`; os de cliente usam `customer()`.

**Verificação extra:** `DeleteAccountTest` (da Fase 0) deve continuar passando sem alteração.

**Commit:** `fix(db): adiciona chaves estrangeiras e regra de exclusão`

---

## 3.4 — Status do pedido como enum

**Problema:** os status `pending_payment`, `transporting`, `completed` e `canceled` aparecem como strings soltas em `PlaceOrder`, `OrderController` e nos testes. Um erro de digitação passa sem aviso.

**Arquivos:**
- novo `app/Enums/OrderStatus.php`;
- `app/Models/Order.php`;
- `app/Actions/Order/PlaceOrder.php`;
- `app/Http/Controllers/OrderController.php`;
- novo `tests/Feature/Orders/OrderStatusTest.php`.

**Mudanças:**
1. `app/Enums/OrderStatus.php`:
   ```php
   <?php

   namespace App\Enums;

   enum OrderStatus: string
   {
       case PendingPayment = 'pending_payment';
       case Transporting = 'transporting';
       case Completed = 'completed';
       case Canceled = 'canceled';
   }
   ```
2. `Order`: adicione o método
   ```php
   protected function casts(): array
   {
       return ['status' => OrderStatus::class];
   }
   ```
   com `use App\Enums\OrderStatus;`.
3. `PlaceOrder`: troque `'pending_payment'` por `OrderStatus::PendingPayment` e `'transporting'` por `OrderStatus::Transporting`.
4. `OrderController::update`: troque `in_array($order->status, ['completed', 'canceled'], true)` por `in_array($order->status, [OrderStatus::Completed, OrderStatus::Canceled], true)`, e `$order->update(['status' => 'canceled'])` por `$order->update(['status' => OrderStatus::Canceled])`. A regra `in:canceled` da validação continua como string.
5. Não há migration. A coluna `orders.status` já é um enum com os mesmos 4 valores.
6. O frontend não muda: o enum serializa para a mesma string.

**Testes** (`OrderStatusTest.php`):
- `Order::factory()->create(['status' => 'transporting'])->status` é `OrderStatus::Transporting`;
- `GET orders.index` com um pedido `transporting` retorna `->where('orders.0.status', 'transporting')`;
- os testes de `OrderAuthorizationTest` e `CheckoutTest` continuam passando sem alteração.

**Commit:** `refactor(orders): usa enum OrderStatus para o status do pedido`

---

## Pronto da Fase 3

- [ ] 4 commits no branch `melhorias/fase-3`
- [ ] `PriceTest`, `IntegrityTest` e `OrderStatusTest` passando
- [ ] suíte completa sem regressões (`DeleteAccountTest`, `CheckoutTest`, `CartTest`, `OrderAuthorizationTest` inclusos)
- [ ] `sail pint --test` e `sail npm run format:check` limpos
- [ ] migrations aplicadas no banco de dev (as consultas de verificação de 3.2 e 3.3 retornaram vazio)
- [ ] teste manual sugerido (feito pelo usuário): apagar uma categoria e um produto com pedido deve mostrar mensagem de erro, não 500
