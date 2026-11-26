<?php

use App\Models\Product;
use App\Services\Cart;
use Illuminate\Support\Facades\DB;

/**
 * Conta as consultas feitas em uma tabela específica durante $fn.
 */
function countQueriesOnTable(string $table, callable $fn): int
{
    $count = 0;

    DB::listen(function ($query) use (&$count, $table) {
        if (str_contains($query->sql, "from \"{$table}\"")) {
            $count++;
        }
    });

    $fn();

    return $count;
}

/**
 * Conta todas as consultas feitas durante $fn.
 */
function countAllQueries(callable $fn): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $fn();
    $total = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $total;
}

it('consulta os produtos do carrinho uma única vez em chamadas repetidas de items()', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer());
    session(['cart' => [['id' => $product->id, 'quantity' => 1]]]);

    $cart = app(Cart::class);

    $queries = countQueriesOnTable('products', function () use ($cart) {
        $cart->items();
        $cart->items();
        $cart->subtotal();
    });

    expect($queries)->toBe(1);
});

it('recalcula os itens depois de adicionar um produto', function () {
    $first = Product::factory()->create(['stock' => 10]);
    $second = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer());
    session(['cart' => [['id' => $first->id, 'quantity' => 1]]]);

    $cart = app(Cart::class);
    expect($cart->items())->toHaveCount(1);

    $cart->add($second, 2);

    expect($cart->items())->toHaveCount(2);
});

it('faz no máximo 5 consultas ao abrir o carrinho com 3 itens', function () {
    $products = Product::factory()->count(3)->create(['stock' => 10]);

    $this->actingAs(customer());

    $total = countAllQueries(function () use ($products) {
        $this->withSession(['cart' => $products->map(fn ($p) => ['id' => $p->id, 'quantity' => 1])->all()])
            ->get(route('cart.index'))
            ->assertOk();
    });

    expect($total)->toBeLessThanOrEqual(5);
});

it('faz no máximo 3 consultas ao abrir a tela de pedido com 3 itens', function () {
    $products = Product::factory()->count(3)->create(['stock' => 10]);

    $this->actingAs(customer());

    $total = countAllQueries(function () use ($products) {
        $this->withSession(['cart' => $products->map(fn ($p) => ['id' => $p->id, 'quantity' => 1])->all()])
            ->get(route('orders.create'))
            ->assertOk();
    });

    expect($total)->toBeLessThanOrEqual(3);
});
