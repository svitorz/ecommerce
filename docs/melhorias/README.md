# Plano de melhorias — guia do executor

Este diretório contém briefs fechados, um por passo. Quem executa (Haiku) **não toma decisões de design**: tudo o que importa já está decidido aqui. Se algo no código não bater com o brief, **pare e reporte** em vez de improvisar.

| Fase | Arquivo | Objetivo |
|------|---------|----------|
| 0 | [fase-0-fundacao.md](fase-0-fundacao.md) | Testes rodando, lint, CI, seeds e factories confiáveis |
| 1 | [fase-1-seguranca.md](fase-1-seguranca.md) | Autorização correta e fim do armazenamento de cartão |
| 2 | [fase-2-checkout.md](fase-2-checkout.md) | Carrinho robusto e pedido gravado de forma íntegra |

Fases 3–5 (banco, arquitetura, performance) serão detalhadas depois que 0–2 estiverem concluídas.

## Regras para cada passo

1. **Branch:** uma por fase, criada a partir de `main`: `melhorias/fase-0`, `melhorias/fase-1`, `melhorias/fase-2`. A fase seguinte parte da anterior já revisada.
2. **Um passo = um commit.** Mensagem no formato `tipo(escopo): descrição curta` em português (ex.: `fix(orders): filtra pedidos pelo usuário autenticado`). Termine a mensagem com a linha de co-autoria indicada na sessão.
3. **Escopo:** altere apenas os arquivos listados no passo. Se precisar tocar em outro, faça e **registre no relatório** o motivo.
4. **Não edite `.env`** (é local do usuário). Não rode `migrate:fresh`, `db:wipe`, `docker compose down -v` nem nada que apague o banco de desenvolvimento.
5. **Comandos sempre via Sail** (o PHP da máquina não tem driver de banco):
   ```bash
   ./vendor/bin/sail up -d
   ./vendor/bin/sail test                 # suíte completa
   ./vendor/bin/sail test --filter=Nome   # um teste
   ./vendor/bin/sail pint                 # formatação PHP (a partir do passo 0.4)
   ./vendor/bin/sail npm run build        # quando mexer em .vue/.js
   ```
6. **Critério de pronto de todo passo:** testes do passo passando, suíte completa sem regressões em relação ao baseline da Fase 0, `sail pint --test` limpo e, se houve mudança em frontend, `sail npm run build` sem erro.
7. **Relatório ao final de cada passo** (texto curto):
   - arquivos alterados;
   - comandos rodados e o resumo da saída dos testes (`Tests: X passed, Y skipped`);
   - qualquer desvio do brief e por quê;
   - qualquer coisa estranha encontrada no caminho (não corrija, só anote).

## Convenções de código

- PHP: seguir o preset `laravel` do Pint. Tipos de retorno declarados em métodos novos.
- Testes: Pest, em `tests/Feature/<Area>/<Nome>Test.php`. Use os helpers `admin()` e `customer()` de `tests/Pest.php` (criados no passo 0.3).
- Respostas de rotas chamadas pelo Inertia (`router.visit`, `form.post`) **sempre** retornam `back()`, `to_route(...)` ou `Inertia::render(...)` — nunca `void`.
- Mensagens novas para o usuário: em português.
