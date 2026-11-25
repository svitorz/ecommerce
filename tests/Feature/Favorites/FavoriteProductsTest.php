<?php

use App\Models\Product;

it('favorita produto para o usuário autenticado', function () {
    $ana = customer();
    $product = Product::factory()->create();

    $this->actingAs($ana)
        ->post(route('favorite.store', $product))
        ->assertRedirect();

    expect($ana->favoriteProducts()->pluck('products.id')->all())->toBe([$product->id]);
});

it('não duplica favorito ao favoritar duas vezes', function () {
    $ana = customer();
    $product = Product::factory()->create();

    $this->actingAs($ana)->post(route('favorite.store', $product));
    $this->actingAs($ana)->post(route('favorite.store', $product));

    expect($ana->favoriteProducts()->count())->toBe(1);
});

it('remove favorito com DELETE', function () {
    $ana = customer();
    $product = Product::factory()->create();
    $ana->favoriteProducts()->attach($product);

    $this->actingAs($ana)
        ->delete(route('favorite.destroy', $product))
        ->assertRedirect();

    expect($ana->favoriteProducts()->count())->toBe(0);
});

it('não altera favoritos de outro usuário', function () {
    $ana = customer();
    $bruno = customer();
    $product = Product::factory()->create();

    $this->actingAs($ana)->post(route('favorite.store', $product));

    expect($bruno->favoriteProducts()->count())->toBe(0);
});
