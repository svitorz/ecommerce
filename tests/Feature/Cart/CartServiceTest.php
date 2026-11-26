<?php

use App\Models\Product;
use App\Services\Cart;

it('calcula o subtotal com o preço do banco', function () {
    $a = Product::factory()->create(['price' => 10.00, 'stock' => 10]);
    $b = Product::factory()->create(['price' => 2.50, 'stock' => 5]);

    $this->actingAs(customer());
    session(['cart' => [['id' => $a->id, 'quantity' => 2], ['id' => $b->id, 'quantity' => 1]]]);

    expect(app(Cart::class)->subtotal())->toBe(22.5);
});

it('remove da sessão os produtos apagados e devolve só os existentes', function () {
    $existing = Product::factory()->create(['stock' => 10]);
    $deleted = Product::factory()->create(['stock' => 10]);
    $deletedId = $deleted->id;
    $deleted->delete();

    $this->actingAs(customer());
    session(['cart' => [['id' => $deletedId, 'quantity' => 1], ['id' => $existing->id, 'quantity' => 1]]]);

    $items = app(Cart::class)->items();

    expect($items)->toHaveCount(1)
        ->and($items[0]['id'])->toBe($existing->id)
        ->and(session('cart'))->toBe([['id' => $existing->id, 'quantity' => 1]]);
});

it('marca isFavorite nos itens quando o usuário logado favoritou o produto', function () {
    $user = customer();
    $product = Product::factory()->create(['stock' => 10]);
    $product->favoritedByUsers()->attach($user->id);

    $this->actingAs($user);
    session(['cart' => [['id' => $product->id, 'quantity' => 1]]]);

    expect(app(Cart::class)->items()[0]['isFavorite'])->toBeTrue();
});

it('retorna quantidade 1 para produto fora do carrinho', function () {
    $product = Product::factory()->create();

    $this->actingAs(customer());

    expect(app(Cart::class)->quantityOf($product))->toBe(1);
});

it('define quantity e inCart no produto', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer());
    session(['cart' => [['id' => $product->id, 'quantity' => 4]]]);

    $decorated = app(Cart::class)->decorate($product);

    expect($decorated->quantity)->toBe(4)
        ->and($decorated->inCart)->toBeTrue();
});
