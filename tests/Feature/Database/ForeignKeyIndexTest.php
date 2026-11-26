<?php

use Illuminate\Support\Facades\DB;

/**
 * Verdadeiro quando alguma definição de índice da tabela começa pela coluna informada.
 */
function hasIndexStartingWith(string $table, string $column): bool
{
    $definitions = DB::select(
        'select indexdef from pg_indexes where schemaname = current_schema() and tablename = ?',
        [$table],
    );

    foreach ($definitions as $definition) {
        if (preg_match('/\(\s*"?'.preg_quote($column, '/').'"?\s*[,)]/', $definition->indexdef)) {
            return true;
        }
    }

    return false;
}

it('cria índice para a chave estrangeira de pedidos do usuário', function () {
    expect(hasIndexStartingWith('orders', 'user_id'))->toBeTrue();
});

it('cria índice para a chave estrangeira de pagamentos do pedido', function () {
    expect(hasIndexStartingWith('payments', 'order_id'))->toBeTrue();
});

it('cria índice para a chave estrangeira de endereços do usuário', function () {
    expect(hasIndexStartingWith('addresses', 'user_id'))->toBeTrue();
});

it('cria índice para a chave estrangeira de itens do pedido por pedido', function () {
    expect(hasIndexStartingWith('orders_products', 'order_id'))->toBeTrue();
});

it('cria índice para a chave estrangeira de itens do pedido por produto', function () {
    expect(hasIndexStartingWith('orders_products', 'product_id'))->toBeTrue();
});

it('cria índice para a chave estrangeira de produtos por categoria', function () {
    expect(hasIndexStartingWith('products', 'category_id'))->toBeTrue();
});

it('cria índice para a chave estrangeira de favoritos por produto', function () {
    expect(hasIndexStartingWith('favorite_products', 'product_id'))->toBeTrue();
});

it('cria índice para a chave estrangeira de avaliações por produto', function () {
    expect(hasIndexStartingWith('product_ratings', 'product_id'))->toBeTrue();
});
