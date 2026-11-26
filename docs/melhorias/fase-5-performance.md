# Fase 5 — Desempenho: índices, consultas do carrinho e sessão em Redis

**Branch:** `melhorias/fase-5` (a partir de `melhorias/fase-4` já revisada).
**Pré-requisito:** Fases 0 a 4 concluídas.
**Objetivo:** reduzir consultas repetidas nas telas de carrinho e pedido, colocar índice nas chaves estrangeiras que hoje não têm, e tirar sessão e cache do banco relacional. O comportamento visível não muda, exceto a correção do passo 5.1.

## Medição de base

Medido com o banco de desenvolvimento preenchido de forma controlada (3 categorias, 30 produtos, 3 favoritos, 5 pedidos, carrinho com 3 itens), contando consultas por requisição:

| Tela | Consultas hoje | Observação |
|------|----------------|-----------|
| `products.index` | 4 | aceitável |
| `products.show` | 5 | aceitável |
| `categories.products` | 4 | aceitável |
| `dashboard` (carrinho vazio) | 4 | aceitável |
| `dashboard` (com carrinho) | 5 | aceitável |
| `cart.index` | **9** | `Cart::items()` roda 4 vezes (2 consultas cada) |
| `orders.index` | 4 | aceitável |
| `orders.create` | **5** | `items()` roda 2 vezes |
| `search` (cliente) | — | **403**: busca bloqueada para cliente comum (passo 5.1) |

Além disso, as chaves estrangeiras criadas nas migrações não têm índice no PostgreSQL. Faltam índices em `orders.user_id`, `payments.order_id`, `addresses.user_id`, `orders_products.order_id`, `orders_products.product_id`, `products.category_id`, `favorite_products.product_id` e `product_ratings.product_id`. (`favorite_products` e `product_ratings` já têm índice composto `(user_id, product_id)` da Fase 3, que cobre `user_id`.)

Sessão e cache estão em `database` (`SESSION_DRIVER=database`, `CACHE_STORE=database` em `.env.example`), embora o serviço `redis` já esteja no `docker-compose.yml`.

> Sonda temporária usada para medir: foi criada e apagada. Nenhum arquivo dela entra no repositório.

---

## 5.1 — Busca liberada para cliente comum (correção)

**Problema:** `ProductController` aplica `EnsureUserIsAdmin` a todas as ações exceto `show` e `index`. Por isso `searchProduct` também exige admin, e cliente comum recebe 403 ao buscar. O campo de busca é um recurso de cliente.

**Arquivos:**
- `app/Http/Controllers/ProductController.php`;
- novo `tests/Feature/Products/SearchTest.php`.

**Mudanças:**
1. Em `ProductController::middleware()`, troque `except: ['show', 'index']` por `except: ['show', 'index', 'searchProduct']`.
2. Nenhuma outra mudança.

**Testes** (`SearchTest.php`):
- cliente comum: `GET route('search', 'camiseta')` retorna 200 e `->component('Products/IndexProducts')`;
- visitante sem login: `GET route('search', 'camiseta')` redireciona para o login (302);
- admin: `GET route('search', 'camiseta')` retorna 200.

> Na medição, uma requisição de admin nessa mesma sonda retornou 302, e a causa não foi investigada. Se o teste de admin falhar com 302, **pare e reporte** o que a resposta trouxe, sem alterar a rota.

**Commit:** `fix(products): libera busca para cliente comum`

---

## 5.2 — Índices nas chaves estrangeiras

**Problema:** o PostgreSQL não cria índice automaticamente em colunas de chave estrangeira. As buscas por pedidos do usuário, pedidos por produto e produtos por categoria fazem varredura completa.

**Arquivos:**
- novo `database/migrations/2026_11_26_000006_add_indexes_to_foreign_keys_tables.php`;
- novo `tests/Feature/Database/ForeignKeyIndexTest.php`.

**Mudanças:**
1. Na migração, `up()` cria os índices abaixo. Antes de criar cada um, verifique em `pg_indexes` se já existe um índice cuja primeira coluna seja a coluna indicada; se existir, pule esse índice e registre no relatório.
   - `orders` (`user_id`);
   - `payments` (`order_id`);
   - `addresses` (`user_id`);
   - `orders_products` (`order_id`);
   - `orders_products` (`product_id`);
   - `products` (`category_id`);
   - `favorite_products` (`product_id`);
   - `product_ratings` (`product_id`).
2. `down()` remove exatamente os índices que `up()` criou, com `dropIndex` pelo nome padrão do Laravel (`tabela_coluna_index`).
3. Nada mais muda no schema.

**Testes** (`ForeignKeyIndexTest.php`, consulta a `pg_indexes`):
- para cada um dos oito pares tabela/coluna acima, existe pelo menos um índice cuja definição (`indexdef`) contém a coluna entre parênteses. Ex.: `expect(indexdef)->toContain('(user_id)')`. Use uma função de apoio que recebe tabela e coluna.

**Verificação extra:** rode `./vendor/bin/sail artisan migrate:rollback --step=1` e depois `./vendor/bin/sail artisan migrate` no banco de desenvolvimento. Isso é seguro porque só desfaz e refaz o último passo. **Não** rode `migrate:fresh`, `db:wipe` ou `docker compose down -v`.

**Commit:** `perf(db): cria índices nas chaves estrangeiras`

---

## 5.3 — Carrinho calcula os itens uma vez por requisição

**Problema:** `Cart::items()` consulta o banco toda vez que é chamado. Em `cart.index` ele é chamado por `index`, por `subtotal()`, por `RecommendedProducts::handle()` e de novo por `items()` dentro de `subtotal()`, ou seja, 4 vezes. Em `orders.create` são 2. Cada chamada são 2 consultas (produtos e categorias).

**Arquivos:**
- `app/Services/Cart.php`;
- novo `tests/Feature/Cart/CartQueryCountTest.php`.

**Mudanças:**
1. Em `Cart`, adicione `private ?array $itemsCache = null;`.
2. `items()`: se `$this->itemsCache !== null`, devolva o cache. Caso contrário, calcule como hoje, guarde em `$this->itemsCache` e devolva.
3. Invalide o cache (`$this->itemsCache = null;`) em `add()`, `update()`, `remove()` e `clear()`. Também em `initialize()`, se ele alterar a sessão.
4. Não altere o formato dos itens nem a poda da sessão feita na primeira leitura.

> A instância do serviço é a mesma dentro de uma requisição, porque o Laravel resolve `Cart` como o mesmo objeto para os controllers e serviços que o recebem por injeção. Por isso o cache compartilhado é correto. Confirme isso lendo `CartController::index`, que passa `$this->cart` para `RecommendedProducts::handle()`.

**Testes** (`CartQueryCountTest.php`, usando `DB::listen` para contar consultas em `products`):
- chamar `items()` duas vezes seguidas executa apenas uma consulta em `products`;
- depois de `add()`, `items()` volta a consultar o banco e inclui o produto novo;
- `GET cart.index` com 3 itens na sessão faz no máximo **5** consultas no total (medido hoje: 9);
- `GET orders.create` com 3 itens na sessão faz no máximo **3** consultas no total (medido hoje: 5).

**Testes existentes:** `CartTest`, `CartServiceTest`, `CheckoutTest` e `OrderAuthorizationTest` devem continuar passando sem alteração.

**Commit:** `perf(cart): memoiza os itens do carrinho dentro da requisição`

---

## 5.4 — Sessão e cache em Redis

**Problema:** cada requisição grava na tabela `sessions` do banco, e o cache também está lá. Com o Redis já disponível no Docker, essas gravações deixam de competir com os pedidos.

**Arquivos:**
- `.env.example`;
- `config/session.php` e `config/cache.php` **não** mudam: já leem de `env()`.

**Mudanças:**
1. Antes de mudar qualquer coisa, confira que a imagem do Sail tem a extensão do cliente Redis configurado (`REDIS_CLIENT=phpredis`):
   `./vendor/bin/sail php -m | grep -i redis`. Se não aparecer `redis`, **pare e reporte**: a troca exigiria instalar `predis/predis` via Composer, o que está fora deste brief.
2. Em `.env.example`, troque `SESSION_DRIVER=database` por `SESSION_DRIVER=redis` e `CACHE_STORE=database` por `CACHE_STORE=redis`.
3. **Não** edite `.env`. Ele é local do usuário e é ele quem aplica a mudança (ver passo de verificação).

**Verificação (feita pelo executor, sem alterar `.env`):**
- com as variáveis passadas só na linha de comando, `SESSION_DRIVER=redis CACHE_STORE=redis REDIS_HOST=redis ./vendor/bin/sail artisan tinker --execute="session()->put('teste', 1); echo cache()->put('teste', 1, 60) ? 'ok' : 'falha';"` deve imprimir `ok`. Se o comando não aceitar `--execute`, use `echo` com `Cache::put` e `session` por `php artisan tinker` interativo, ou pule a verificação e relate;
- com a mesma configuração, `./vendor/bin/sail test` deve continuar passando (os testes usam o driver `array`, então isso confirma que nada depende do banco para sessão).

**Passo do usuário (não executado pelo Haiku):** no `.env` local, aplicar as mesmas duas variáveis e reiniciar o Sail. Depois, conferir com `./vendor/bin/sail exec redis redis-cli keys '*'`: deve aparecer chave de sessão após navegar pela loja.

**Commit:** `perf(config): sessão e cache no Redis no exemplo de ambiente`

---

## Fora desta fase (decidido, não fazer agora)

- **Paginação do histórico de pedidos (`orders.index`):** hoje o volume por usuário é pequeno e a mudança exige reescrever `IndexOrders.vue`. Revisar quando houver muitos pedidos por usuário.
- **Busca com `LIKE '%termo%'`:** não usa índice por causa do curinga à esquerda. Solução com `pg_trgm` exige extensão no banco. Com o volume atual não compensa; reavaliar se a busca ficar lenta.
- **Bundle do frontend:** as páginas já são carregadas sob demanda (`import.meta.glob` preguiçoso). O bundle principal mede 226 kB (82 kB gzip). Não há ganho claro sem medição de usuário real.
- **`auth.user` com `address` em toda requisição do Inertia:** é uma consulta por requisição, custo baixo. Mantido.

## Pronto da Fase 5

- [ ] 4 commits no branch `melhorias/fase-5` (um por passo, na ordem 5.1 a 5.4)
- [ ] `SearchTest`, `ForeignKeyIndexTest` e `CartQueryCountTest` passando
- [ ] suíte completa sem regressões em relação à Fase 4 (95 passed, 7 skipped)
- [ ] `sail pint --test`, `sail npm run format:check` e `sail npm run build` limpos
- [ ] migração revertida e reaplicada com sucesso (5.2)
- [ ] passo de verificação do Redis (5.4) feito ou relatado como pendente
- [ ] teste manual sugerido (feito pelo usuário): buscar como cliente comum; navegar pelo carrinho e pelo pedido; conferir chaves de sessão no Redis após aplicar o `.env`

## Regras que continuam valendo

- Um passo = um commit, na mensagem indicada, com a linha de co-autoria do Haiku.
- Altere apenas os arquivos listados no passo. Se precisar de outro, registre o motivo no relatório.
- Não edite `.env`. Não rode `migrate:fresh`, `db:wipe` nem `docker compose down -v`.
- Comandos via Sail, com `APP_PORT=8080 FORWARD_REDIS_PORT=6380 WWWUSER=$(id -u) WWWGROUP=$(id -g)`.
