# Fase 0 — Fundação

**Branch:** `melhorias/fase-0` (a partir de `main`).
**Objetivo:** ter uma suíte de testes que roda de verdade, dados de seed/factory confiáveis, formatação automática e CI. Nenhuma mudança de comportamento da aplicação.

---

## 0.1 — Banco de testes e TestCase

**Problema:** o `docker-compose.yml` monta `./vendor/laravel/sail/database/db/create-testing-database.sql`, que não existe (o Docker criou um diretório vazio no lugar). Por isso o banco `testing` usado pelo `phpunit.xml` nunca é criado. Além disso, testes que renderizam páginas quebram sem o manifest do Vite.

**Arquivos:** `docker-compose.yml`, `tests/TestCase.php`.

**Mudanças:**

1. Em `docker-compose.yml`, serviço `db`, troque a linha do volume por:
   ```yaml
   - './vendor/laravel/sail/database/pgsql/create-testing-database.sql:/docker-entrypoint-initdb.d/10-create-testing-database.sql'
   ```
   Não mexa em mais nada no compose.
2. Em `tests/TestCase.php`:
   ```php
   abstract class TestCase extends BaseTestCase
   {
       protected function setUp(): void
       {
           parent::setUp();

           $this->withoutVite();
       }
   }
   ```
3. O script de init só roda num volume vazio, e o volume `sail-db` já existe. Crie o banco manualmente (é idempotente; um erro "already exists" é aceitável):
   ```bash
   ./vendor/bin/sail up -d
   ./vendor/bin/sail exec db sh -c 'createdb -U "$POSTGRES_USER" testing || true'
   ```
   **Não** recrie o volume.

**Verificação:**
```bash
./vendor/bin/sail test
```
**Registre no relatório o resultado como baseline** (quantos passaram, falharam e foram pulados, com o nome de cada teste que falhou). Falhas aqui não bloqueiam o passo, desde que não sejam de conexão com o banco. Se `sail up` falhar por conflito de porta, pare e reporte.

**Commit:** `chore(test): cria banco de testes no Postgres e desativa Vite nos testes`

---

## 0.2 — `.env.example` coerente com o Sail + Postgres

**Problema:** o `.env.example` mistura configurações de MySQL e Postgres (`DB_PORT=3306`, `DB_USERNAME=root`, `DB_HOST=127.0.0.1`). Quem clona o projeto não consegue subir a aplicação.

**Arquivo:** `.env.example` (somente ele).

**Mudanças (substitua os valores destas chaves, mantenha o resto):**
```dotenv
APP_DEBUG=true

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=ecommerce
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_HOST=redis
```

**Verificação:** `git diff .env.example` mostra só essas linhas.

**Commit:** `chore(env): ajusta .env.example para Sail com Postgres`

---

## 0.3 — Factories independentes, seeders corrigidos e helpers de teste

**Problema:** as factories usam `inRandomOrder()->first()`, então quebram num banco vazio (ou seja, em todo teste). O seeder não cria nenhum admin. O `PaymentSeeder` não tem `use App\Models\Payment` e o `OrderSeeder` está vazio; nenhum dos dois é chamado.

**Arquivos:**
`database/factories/{UserFactory,OrderFactory,ProductFactory,OrdersProductsFactory,PaymentFactory,AddressFactory}.php`,
`database/seeders/{DatabaseSeeder,ProductSeeder}.php`,
apagar `database/seeders/OrderSeeder.php` e `database/seeders/PaymentSeeder.php`,
`tests/Pest.php`, novo `tests/Feature/SeederTest.php`.

**Mudanças:**

1. `UserFactory`: adicione o estado
   ```php
   public function admin(): static
   {
       return $this->state(fn (array $attributes) => ['isAdmin' => true]);
   }
   ```
2. `OrderFactory`: `'user_id' => User::factory(),` (remova o `inRandomOrder`).
3. `ProductFactory`: `'category_id' => Category::factory(),` e `'price' => fake()->randomFloat(2, 5, 500),`.
4. `OrdersProductsFactory`: `'product_id' => Product::factory(),` (remova o `inRandomOrder`; o `order_id` já usa factory).
5. `PaymentFactory`: `'order_id' => \App\Models\Order::factory(),` e
   `'card_expiration' => fake()->dateTimeBetween('now', '+5 years')->format('Y-m-01'),`
   (o código atual interpreta `11/27` como ano 0027).
6. `AddressFactory::definition()`:
   ```php
   return [
       'country' => 'Brasil',
       'state' => fake()->state(),
       'city' => fake()->city(),
       'street' => fake()->streetName(),
       'zip_code' => fake()->postcode(),
       'number' => fake()->numberBetween(1, 9999),
       'user_id' => \App\Models\User::factory(),
   ];
   ```
7. `ProductSeeder::run()`:
   ```php
   Product::factory()->count(30)->recycle(Category::all())->create();
   ```
   (adicione `use App\Models\Category;`).
8. `DatabaseSeeder::run()` deve ficar assim:
   ```php
   $customer = User::factory()->create([
       'name' => 'Vitor Souza',
       'email' => 'vitor@example.com',
       'password' => Hash::make('12345678'),
   ]);

   User::factory()->create([
       'name' => 'Cliente Teste',
       'email' => 'test@example.com',
       'password' => Hash::make('12345678'),
   ]);

   User::factory()->admin()->create([
       'name' => 'Administrador',
       'email' => 'admin@example.com',
       'password' => Hash::make('12345678'),
   ]);

   $this->call([CategorySeeder::class, ProductSeeder::class]);

   $users = User::all();
   $products = Product::all();

   Order::factory(10)->recycle($users)->create()->each(function (Order $order) use ($products) {
       OrdersProductsFactory::new()->count(3)->recycle($products)->create(['order_id' => $order->id]);
       Payment::factory()->create(['order_id' => $order->id]);
   });

   Address::factory()->create([
       'country' => 'Brasil',
       'state' => 'São Paulo',
       'city' => 'Rio Preto',
       'street' => 'Rua Exemplo',
       'zip_code' => '12335-000',
       'number' => 123,
       'user_id' => $customer->id,
   ]);
   ```
   Ajuste os `use` (adicione `App\Models\Payment`, `App\Models\Product`).
9. Apague `OrderSeeder.php` e `PaymentSeeder.php`. Antes, confirme com `grep -rn "OrderSeeder\|PaymentSeeder" app database routes tests` que ninguém os referencia.
10. Em `tests/Pest.php`, remova a função `something()` e adicione no fim:
    ```php
    use App\Models\User;

    function admin(): User
    {
        return User::factory()->admin()->create();
    }

    function customer(): User
    {
        return User::factory()->create();
    }
    ```
    O `use` deve ficar no topo do arquivo, logo após `<?php`.
11. Novo `tests/Feature/SeederTest.php`:
    ```php
    <?php

    use App\Models\Category;
    use App\Models\Order;
    use App\Models\Product;
    use App\Models\User;

    it('popula o banco com dados coerentes', function () {
        $this->seed();

        expect(User::where('isAdmin', true)->count())->toBe(1)
            ->and(User::count())->toBe(3)
            ->and(Category::count())->toBe(10)
            ->and(Product::count())->toBe(30)
            ->and(Order::count())->toBe(10);
    });

    it('factories funcionam em banco vazio', function () {
        expect(Product::factory()->create()->category)->not->toBeNull()
            ->and(Order::factory()->create()->user_id)->not->toBeNull();
    });
    ```

**Verificação:** `./vendor/bin/sail test --filter=SeederTest` passa, e a suíte completa não piora em relação ao baseline.

**Commit:** `test: torna factories independentes, cria admin no seed e adiciona helpers`

---

## 0.4 — Pint (formatação PHP)

**Arquivos:** novo `pint.json`, `composer.json` (scripts) e, no segundo commit, os arquivos que o Pint reformatar.

**Mudanças:**
1. `pint.json`:
   ```json
   { "preset": "laravel" }
   ```
2. Em `composer.json`, dentro de `"scripts"`, adicione:
   ```json
   "lint": "pint --test",
   "format": "pint"
   ```
3. **Commit A:** `chore(lint): configura Pint` (só `pint.json` e `composer.json`).
4. Rode `./vendor/bin/sail pint`. **Commit B, separado:** `style: aplica Pint em todo o código PHP`. Nenhuma outra mudança neste commit.

**Verificação:** `./vendor/bin/sail pint --test` passa e a suíte continua igual.

---

## 0.5 — Prettier (formatação do frontend)

**Arquivos:** `package.json`, novo `.prettierrc.json` e, no segundo commit, os arquivos de `resources/js` reformatados.

**Mudanças:**
1. `./vendor/bin/sail npm install -D prettier@^3`
2. `.prettierrc.json`:
   ```json
   { "tabWidth": 4, "semi": true, "singleQuote": false, "printWidth": 100 }
   ```
3. Em `package.json`, `"scripts"`:
   ```json
   "format": "prettier --write resources/js",
   "format:check": "prettier --check resources/js"
   ```
4. **Commit A:** `chore(lint): configura Prettier`.
5. Rode `./vendor/bin/sail npm run format`. **Commit B, separado:** `style: aplica Prettier em resources/js`.

**Verificação:** `sail npm run format:check` e `sail npm run build` passam.

---

## 0.6 — CI no GitHub Actions

**Arquivo:** novo `.github/workflows/ci.yml`.

**Conteúdo:**
```yaml
name: CI

on:
  push:
    branches: [main, master]
  pull_request:

jobs:
  backend:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:17
        env:
          POSTGRES_DB: testing
          POSTGRES_USER: sail
          POSTGRES_PASSWORD: password
        ports: ['5432:5432']
        options: >-
          --health-cmd pg_isready
          --health-interval 5s
          --health-timeout 5s
          --health-retries 10
    env:
      DB_CONNECTION: pgsql
      DB_HOST: 127.0.0.1
      DB_PORT: 5432
      DB_DATABASE: testing
      DB_USERNAME: sail
      DB_PASSWORD: password
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_pgsql
          coverage: none
      - run: composer install --no-interaction --prefer-dist --no-progress
      - run: cp .env.example .env && php artisan key:generate
      - run: vendor/bin/pint --test
      - run: vendor/bin/pest

  frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      # resources/js/app.js importa o Ziggy de vendor/, então o build precisa do composer
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          coverage: none
      - run: composer install --no-interaction --prefer-dist --no-progress --no-scripts
      - uses: actions/setup-node@v4
        with:
          node-version: 22
          cache: npm
      - run: npm ci
      - run: npm run format:check
      - run: npm run build
```

**Verificação:** valide o YAML localmente com `python3 -c "import yaml,sys; yaml.safe_load(open('.github/workflows/ci.yml'))"`. **Não faça push**; o push é decisão do usuário.

**Commit:** `ci: adiciona pipeline com Pint, Pest, Prettier e build`

---

## Pronto da Fase 0

- [ ] `sail test` roda contra o banco `testing`, com baseline registrado
- [ ] `SeederTest` passando
- [ ] `sail pint --test` e `sail npm run format:check` limpos
- [ ] `ci.yml` criado (sem push)
- [ ] 8 commits no branch `melhorias/fase-0` (0.4 e 0.5 têm dois cada)
