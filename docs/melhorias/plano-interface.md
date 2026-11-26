# Plano de melhorias da interface visual

Diagnóstico feito em 26/11/2026 no app local (`localhost:8080`), logado como o admin do seeder, nos tamanhos 1280×800 e 375×812, nos temas escuro e claro. Os itens abaixo têm evidência no código ou na tela. Este documento é a proposta. Os briefs fechados de cada fase são escritos depois que o plano é aprovado.

## Diagnóstico

### Quebrado (aparece errado para qualquer usuário)

| # | Problema | Evidência |
|---|----------|-----------|
| D1 | A cor `primary` não existe no Tailwind | 49 classes `primary-*` em 7 componentes (`ProductCard`, `CartButton`, `OrderProgress`, `OrderSummary`, `PaymentForm`, `SortDropdown`, `ProductRatings`). Não geram CSS, então botões, selos e anéis de foco perdem a cor. (Os quatro campos do endereço sem borda no tema claro têm outra causa: `type` inválido. Ver o brief da Fase 6, passo 6.2 d.) |
| D2 | Lista de produtos vazia no celular | Em 375 px, a grade tem os 8 cartões, mas largura 0. O `SortDropdown` flutua com `float-end w-full`, e o contêiner `overflow-hidden` ao lado encolhe até sumir. |
| D3 | Rodapé sobreposto ao checkout | Em `orders/create`, "Developed by…" aparece por cima do título "Address Information" (desktop e celular). Os formulários estão dentro da `div` da barra de progresso, que tem 10 px de altura. |
| D4 | Texto invisível no tema claro | `CreateOrder.vue` tem `<h2 class="text-white">` sem cor para o tema claro. O cabeçalho do carrinho também aparece vazio no tema claro. |
| D5 | Selo de categoria sempre vazio | `ProductCard` lê `product.categories.name`, mas a relação se chama `category`, e `products.index` nem envia a categoria. |
| D6 | Erro de endereço no campo errado | Em `AddressForm.vue`, estado, cidade e rua exibem `form.errors.country`. |
| D7 | Preço sem padrão | Aparece como "169.38" (lista), "$169.38" (produto) e "248.3" (recomendados): sem moeda, com o símbolo do dólar ou sem duas casas. |
| D8 | Título de aba vazio | Carrinho, checkout e pedidos mostram "- eCommerce". |
| D9 | Link do rodapé quebrado | `href="github.com/svitorz"` é relativo e leva para `/github.com/svitorz`. |
| D10 | `console.log` em produção | `IndexOrders.vue` imprime todos os pedidos. Há mais em `FavoriteButton`, `OrderProgress` e `OrderProductCard`. |

### Enganoso (mostra informação que não é verdade)

| # | Problema | Evidência |
|---|----------|-----------|
| E1 | Avaliação falsa nos cartões | `ProductCard.vue:142-143` fixa "5.0 (455)" para todo produto. A página do produto mostra a média real ("0.0, 0 reviews"). |
| E2 | Selos fixos | "Fast Delivery" e "Best Price" aparecem em todo produto. |
| E3 | Caixa de voucher sem função | O carrinho tem "Do you have a voucher or gift card?" sem nenhuma lógica por trás. |
| E4 | "Original price" sem desconto | O resumo do pedido repete o total como preço original. |
| E5 | "Compra verificada" em toda avaliação | Mostrado mesmo quando o autor não comprou o produto. |

### Inacabado ou inconsistente

| # | Problema | Evidência |
|---|----------|-----------|
| I1 | Página inicial é a padrão do Laravel | `/` mostra Laracasts, Laravel News e documentação. |
| I2 | Todas as imagens são o mesmo iMac | As imagens vêm do S3 do Flowbite (dependência externa). O banco não tem coluna de imagem. |
| I3 | Idioma misturado | Botões e títulos em inglês ("Add to Cart", "Shopping Cart", "Confirm your order."), enquanto categorias, mensagens de erro e o seeder estão em português. |
| I4 | Nomes de produto parecem nomes de pessoa | `ProductFactory` usa `fake()->name()` ("Prof. Presley Wiegand II"). |
| I5 | Três variações do cartão de produto | Grade, carrossel e recomendados têm marcação e espaçamento diferentes. No carrossel do dashboard, o botão "Add to Cart" quebra em 3 linhas. |
| I6 | Estados vazios sem tratamento | A seção "Comments" fica em branco quando não há avaliações. Não existe tela para carrinho vazio nem para "sem pedidos". |
| I7 | Página de produto com cartão dentro de cartão | O cabeçalho mostra a categoria em vez de um caminho (Produtos › Categoria › Produto). |
| I8 | Filtro de pedidos sem estilo | Rádios crus no topo da página. O cliente vê "Product ID". |
| I9 | Acessibilidade | Imagens com `alt=""` e foco pouco visível em vários botões. |

## Proposta de fases

### Fase 6 — Correções visuais (D1–D10)
Sem decisão de design: só faz o que existe funcionar.
- 6.1 Definir a paleta `primary` no `tailwind.config.js` (proposta: índigo, que o logo e a navegação já usam), o que conserta D1 de uma vez.
- 6.2 Layout: grade no celular (D2), rodapé do checkout (D3) e texto no tema claro (D4), trocando `float` por `flex`/`grid`.
- 6.3 Dados na tela: categoria no cartão (D5), erros do endereço (D6), títulos de aba (D8), link do rodapé (D9), `console.log` (D10).
- 6.4 Preço: um único `formatPrice()` com `Intl.NumberFormat` em todos os lugares (D7).

**Critério de pronto:** capturas em 375 e 1280 px, nos dois temas, das telas produtos, produto, carrinho, checkout, pedidos e dashboard, sem regressão na suíte.

### Fase 7 — Conteúdo honesto e consistente (E1–E5, I1–I4)
Depende de decisões suas (ver perguntas abaixo).
- 7.1 Remover ou tornar reais os elementos falsos (E1–E5).
- 7.2 Idioma único na interface (I3).
- 7.3 Página inicial da loja (I1).
- 7.4 Imagens de produto (I2).
- 7.5 Seeder com nomes de produto plausíveis por categoria (I4).

### Fase 8 — Polimento de experiência (I5–I9)
- 8.1 Um `ProductCard` único para grade, carrossel e recomendados; o dashboard troca o carrossel espremido por uma grade "Recomendados".
- 8.2 Estados vazios: carrinho, pedidos e avaliações.
- 8.3 Página de produto sem cartão duplicado, com caminho de navegação.
- 8.4 Pedidos: filtro de status como abas e sem "Product ID".
- 8.5 Acessibilidade básica: `alt` descritivo, foco visível e contraste conferido nos dois temas.

## Decisões (26/11/2026)

1. **Elementos falsos (E1–E5):** a avaliação fixa vira a média e a contagem reais. Selos fixos, voucher, "Original price" e "Compra verificada" saem.
2. **Idioma (I3):** **inglês** em toda a interface. Consequências para o brief da Fase 7:
   - traduzir as mensagens em português criadas nas Fases 1 a 5 (carrinho, checkout, exclusão de categoria e produto, conta com pedidos) e conferir os testes que comparam texto;
   - traduzir os nomes e descrições das categorias no seeder;
   - trocar a convenção do `README.md` ("Mensagens novas para o usuário: em português") por "em inglês". Os briefs e commits continuam em português.
3. **Página inicial (I1):** vitrine pública. Consequências:
   - listagem, produto e busca passam a aceitar visitante, então as rotas saem do grupo `auth`;
   - `withFavoriteFlag(auth()->id())` recebe `int` e quebra com visitante, então o escopo precisa aceitar `null` (sem favorito);
   - carrinho, favoritos, avaliação e pedido continuam exigindo login, e os botões levam ao login quando não há usuário.
4. **Imagens (I2):** upload por produto. Coluna `image_path` anulável, arquivo em `storage/app/public/products`, `storage:link`, validação de imagem (tipo e tamanho) no formulário de admin, e placeholder local quando não houver imagem. As imagens do Flowbite saem, inclusive as bandeiras de cartão do `PaymentForm`.

## Ordem sugerida

Fase 6 primeiro: ela é independente das decisões e deixa a base visual correta. Depois a Fase 7, onde entram as decisões, e por último a Fase 8. A tradução para o inglês (7.2) deve vir antes da Fase 8, para que os componentes novos já nasçam no idioma final.
