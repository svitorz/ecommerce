# Fase 6 — Correções visuais

**Branch:** `melhorias/fase-6` (a partir de `melhorias/fase-5`).
**Pré-requisito:** Fases 0 a 5 concluídas.
**Objetivo:** fazer a interface existente aparecer certa em qualquer tela e tema, sem mudar textos, layout geral ou funcionalidades. O diagnóstico completo está em [plano-interface.md](plano-interface.md) (itens D1 a D10).

> **Fora desta fase:** tradução, remoção de dados falsos, imagens, página inicial e cartão único. Isso é das Fases 7 e 8. Se um texto estiver em português ou inglês, deixe como está, exceto onde o brief manda escrever um texto novo.

## Como verificar a interface (vale para todos os passos)

Não há testes automatizados de frontend. Depois de cada passo com mudança em `.vue`, `.js` ou `tailwind.config.js`:

1. `./vendor/bin/sail npm run build` (o app em `http://localhost:8080` serve o build, não o Vite) e `./vendor/bin/sail npm run format:check`.
2. No navegador embutido, logado como o admin do seeder (`admin@example.com`, senha do `DatabaseSeeder`), abrir as telas indicadas no passo em **1280×800** e **375×812**, nos temas **escuro** e **claro** (`resize_window` com `colorScheme`). Volte o viewport para `desktop` no fim.
3. No relatório, descrever o que foi visto em cada tela. Se algo do critério do passo não bater, **pare e reporte**.

---

## 6.1 — Paleta `primary` no Tailwind (D1)

**Problema:** 49 classes `primary-*` (tons 100, 300, 500, 600, 700, 800 e 900) em 7 componentes não geram CSS, porque `tailwind.config.js` não define a cor `primary`. Botões, selos, anéis de foco e textos de destaque perdem a cor.

**Arquivos:** `tailwind.config.js`.

**Mudanças:**
1. Importe a paleta: `import colors from 'tailwindcss/colors';`.
2. Em `theme.extend`, adicione `colors: { primary: colors.indigo },`. O índigo é a cor que o logo e a navegação já usam.
3. Nenhuma outra mudança. Não troque classes `primary-*` nos componentes.

**Verificação:**
- `grep -o "primary-[0-9]*" public/build/assets/*.css | sort -u` deve listar os 7 tons depois do build;
- telas: produtos, carrinho e checkout. O selo de categoria e o botão do carrinho passam a ter cor de fundo.

**Commit:** `fix(ui): define a paleta primary usada pelos componentes`

---

## 6.2 — Layout quebrado no celular, no checkout e no tema claro (D2, D3, D4 e bordas do endereço)

### a) Grade de produtos com largura 0 no celular (D2)

**Causa:** o botão do `SortDropdown` tem `m-4 float-end ... w-full ... sm:w-auto`. Abaixo de `sm`, ele flutua com largura total, e o contêiner `overflow-hidden` ao lado, que guarda a grade, encolhe para largura 0.

**Arquivos:** `resources/js/Pages/Products/Components/SortDropdown.vue` e `resources/js/Pages/Products/IndexProducts.vue`.

**Mudanças:**
1. No botão `#sortButton`, remova `m-4` e `float-end`. Mantenha todas as outras classes.
2. Em `IndexProducts.vue`, envolva `<SortDropdown ... />` em `<div class="mb-4 flex justify-end px-4 sm:px-0">…</div>`.

**Critério:** em 375 px, `products` mostra os cartões em uma coluna. Confira com `document.querySelector('.grid').getBoundingClientRect().width`, que deve ser maior que 300. Em 1280 px, o botão Sort continua alinhado à direita, acima da grade.

### b) Checkout com o rodapé por cima do formulário (D3)

**Causa:** em `CreateOrder.vue`, os formulários estão **dentro** da `div` da barra de progresso (`h-2.5`, 10 px de altura). O conteúdo transborda essa altura, e o rodapé do layout se posiciona logo depois dos 10 px. O botão Next ainda usa `float-end`.

**Arquivos:** `resources/js/Pages/Orders/Form/CreateOrder.vue`.

**Mudanças:** substitua o `<template>` inteiro por este, sem mudar o `<script setup>`:

```vue
<template>
    <AppLayout title="Checkout">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Confirm your order.
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6 h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                        class="h-2.5 rounded-full bg-primary-600"
                        :style="{ width: step === 1 ? '50%' : '90%' }"
                    ></div>
                </div>

                <div v-if="step === 1">
                    <AddressForm :show="false" :address="$page.props.auth.user.address" />
                </div>
                <div v-if="step === 2">
                    <PaymentForm :amount="amount" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton v-if="step > 1" @click="step--"> Go back </SecondaryButton>
                    <PrimaryButton v-if="step === 1" @click="step++"> Next </PrimaryButton>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
```

O título da aba (`title="Checkout"`) também resolve D8 para esta tela.

**Critério:** em `orders/create`, nos dois tamanhos, o rodapé "Developed by…" fica abaixo do botão Next, sem sobrepor nada. Confira com `document.querySelector('footer').getBoundingClientRect().top`, que deve ser maior que o `bottom` do botão Next.

### c) Títulos invisíveis no tema claro (D4)

**Causa:** os cabeçalhos de três páginas usam `class="text-white"`, sem cor para o tema claro.

**Arquivos:** `resources/js/Pages/Cart/IndexCart.vue` e `resources/js/Pages/Orders/IndexOrders.vue` (`CreateOrder.vue` já foi resolvido em b).

**Mudanças:** no `<h2>` do slot `#header`, troque `class="text-white"` por `class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"`, a mesma classe das outras páginas. Não mude o texto.

**Critério:** no tema claro, o cabeçalho de `cart`, `orders` e `orders/create` mostra o título em cinza escuro.

### d) Campos do endereço sem borda no tema claro

**Causa:** em `AddressForm.vue`, quatro campos usam tipos que não existem: `type="country"`, `type="state"`, `type="city"` e `type="street"`. O plugin `@tailwindcss/forms` só estiliza tipos válidos, então esses campos ficam sem borda. Todos os seis campos também usam `autocomplete="username"`, o que faz o navegador sugerir o e-mail no endereço.

**Arquivos:** `resources/js/Pages/Address/Forms/AddressForm.vue`.

**Mudanças:**

| Campo | `type` | `autocomplete` |
|-------|--------|----------------|
| `zip_code` | `text` (já está) | `postal-code` |
| `country` | `text` | `country-name` |
| `state` | `text` | `address-level1` |
| `city` | `text` | `address-level2` |
| `street` | `text` | `address-line1` |
| `number` | `number` (já está) | `address-line2` |

**Critério:** no tema claro, os seis campos do checkout têm a mesma borda.

**Commit:** `fix(ui): corrige layout da lista no celular, do checkout e do tema claro`

---

## 6.3 — Dados certos na tela (D5, D6, D8, D9 e D10)

### a) Selo de categoria vazio (D5)

**Causa:** `ProductCard.vue:29` lê `product?.categories?.name`, mas a relação se chama `category`. Além disso, `ProductController::index` monta um array sem a categoria, e `CategoryController::getProducts` não carrega a relação.

**Arquivos:**
- `resources/js/Pages/Products/Components/ProductCard.vue`;
- `app/Http/Controllers/ProductController.php`;
- `app/Http/Controllers/CategoryController.php`;
- `tests/Feature/Products/ProductFlagsTest.php`.

**Mudanças:**
1. `ProductCard.vue:29`: `{{ product?.categories?.name }}` passa a ser `{{ product?.category?.name }}`.
2. `ProductController::index`: no array do `through`, adicione `'category' => $product->category?->only(['id', 'name']),`.
3. `CategoryController::getProducts`: adicione `->with('category')` na consulta, antes de `->withFavoriteFlag(...)`.

**Testes** (acrescentar em `ProductFlagsTest.php`):
- `GET products.index` com um produto da categoria "Livros": `->where('products.data.0.category.name', 'Livros')`;
- `GET categories.products` da mesma categoria: `->where('products.data.0.category.name', 'Livros')`.

### b) Erro de endereço no campo errado (D6)

**Arquivos:** `resources/js/Pages/Address/Forms/AddressForm.vue`.

**Mudanças:** os `InputError` de `state`, `city` e `street` usam `form.errors.country`. Troque por `form.errors.state`, `form.errors.city` e `form.errors.street`, nessa ordem.

### c) Títulos de aba vazios (D8)

**Arquivos:** `resources/js/Pages/Cart/IndexCart.vue` e `resources/js/Pages/Orders/IndexOrders.vue`.

**Mudanças:** `<AppLayout>` vira `<AppLayout title="Cart">` e `<AppLayout title="Orders">`, respectivamente.

**Critério:** a aba mostra "Cart - eCommerce", "Orders - eCommerce" e "Checkout - eCommerce".

### d) Link do rodapé quebrado (D9)

**Arquivos:** `resources/js/Layouts/AppLayout.vue`.

**Mudanças:** no rodapé, `href="github.com/svitorz"` passa a ser `href="https://github.com/svitorz"`.

### e) `console.log` no navegador (D10)

**Arquivos:**
- `resources/js/Pages/Orders/IndexOrders.vue`;
- `resources/js/Pages/Cart/IndexCart.vue`;
- `resources/js/Pages/Favorites/FavoriteButton.vue`;
- `resources/js/Pages/Orders/Components/OrderProgress.vue`;
- `resources/js/Pages/Orders/Components/OrderProductCard.vue`.

**Mudanças:**
1. Apague `console.log(props.orders);` em `IndexOrders.vue` e as duas linhas comentadas de `console.log` em `IndexCart.vue`.
2. Em `FavoriteButton.vue`, apague `console.log("Success");` e `console.log("Error");`. Se o bloco ficar vazio, apague o bloco.
3. Nos blocos `catch` (`FavoriteButton`, `OrderProgress`, `OrderProductCard`), troque `console.log(error)` por `console.error(error)`.

**Critério:** `grep -rn "console.log" resources/js` não retorna nada.

**Commit:** `fix(ui): mostra categoria, erros e títulos certos na interface`

---

## 6.4 — Preço com um formato só (D7)

**Problema:** o preço aparece como "169.38", "$169.38", "$ 169.38" e "248.3", conforme a tela. Nenhuma tela formata o número.

**Decisão:** dólar americano no formato `en-US` ("$1,234.50"), coerente com a interface em inglês decidida para a Fase 7 e com o "$" que já aparece hoje. A moeda fica em um único lugar; se o usuário quiser real, basta trocar a linha do formatador.

**Arquivos:**
- novo `resources/js/Utils/formatPrice.js`;
- `resources/js/Pages/Products/Components/ProductCard.vue`;
- `resources/js/Pages/Products/ShowProduct.vue`;
- `resources/js/Pages/Orders/IndexOrders.vue`;
- `resources/js/Pages/Orders/Components/OrderProductCard.vue`;
- `resources/js/Pages/Orders/Components/OrderSummary.vue`;
- `resources/js/Pages/Orders/Form/PaymentForm.vue`.

**Mudanças:**
1. `formatPrice.js`:
   ```js
   const formatter = new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" });

   export function formatPrice(value) {
       const number = Number(value);

       return Number.isFinite(number) ? formatter.format(number) : "";
   }
   ```
2. Em cada arquivo, importe `import { formatPrice } from "@/Utils/formatPrice";` e troque a interpolação, **removendo o `$` literal** que estiver na frente:

   | Arquivo | Hoje | Depois |
   |---------|------|--------|
   | `ProductCard.vue` | `{{ product.price }}` | `{{ formatPrice(product.price) }}` |
   | `ShowProduct.vue` | `${{ localProduct.price }}` | `{{ formatPrice(localProduct.price) }}` |
   | `OrderProductCard.vue` | `${{ product.price }}` | `{{ formatPrice(product.price) }}` |
   | `OrderSummary.vue` (2×) | `$ {{ subtotal }}` | `{{ formatPrice(subtotal) }}` |
   | `PaymentForm.vue` (2×) | `$ {{ amount }}` | `{{ formatPrice(amount) }}` |
   | `IndexOrders.vue` (2×) | `${{ order.subtotal }}` | `{{ formatPrice(order.subtotal) }}` |
   | `IndexOrders.vue` | `${{ product.price }}` | `{{ formatPrice(product.pivot?.unit_price ?? product.price) }}` |

   A última linha também corrige o histórico de pedidos, que hoje mostra o preço **atual** do produto em vez do preço pago (`unit_price`, gravado desde a Fase 2).
3. Confira com `grep -rn "\$ *{{\|{{[^}]*\(price\|subtotal\|amount\)[^}]*}}" resources/js --include='*.vue'`: toda ocorrência restante deve passar por `formatPrice`.

**Critério:** nas telas produtos, produto, carrinho (incluindo "People also bought"), checkout (passo de pagamento) e pedidos, todo preço aparece como `$0.00`, sempre com duas casas.

**Commit:** `fix(ui): formata preços em um único padrão`

---

## Pronto da Fase 6

- [ ] 4 commits no branch `melhorias/fase-6`, na ordem 6.1 a 6.4
- [ ] testes novos de categoria em `ProductFlagsTest` passando
- [ ] suíte completa sem regressões em relação à Fase 5 (110 passed, 7 skipped, mais os testes novos)
- [ ] `sail pint --test`, `sail npm run format:check` e `sail npm run build` limpos
- [ ] verificação no navegador feita em 1280 e 375 px, nos dois temas, com o resultado de cada tela no relatório
- [ ] `grep -rn "console.log" resources/js` vazio
