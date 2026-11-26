<?php

use App\Models\Product;
use App\Models\ProductRating;

it('grava avaliação com o usuário autenticado', function () {
    $ana = customer();
    $product = Product::factory()->create();

    $this->actingAs($ana)
        ->post(route('rating.store', $product), ['rating' => 4, 'feedback' => 'Muito bom'])
        ->assertRedirect();

    expect(ProductRating::where('user_id', $ana->id)->where('product_id', $product->id)->value('rating'))
        ->toEqual(4.0);
});

it('atualiza a avaliação existente em vez de duplicar', function () {
    $ana = customer();
    $product = Product::factory()->create();

    $this->actingAs($ana)->post(route('rating.store', $product), ['rating' => 3, 'feedback' => 'Ok']);
    $this->actingAs($ana)->post(route('rating.store', $product), ['rating' => 5, 'feedback' => 'Ótimo']);

    expect(ProductRating::count())->toBe(1)
        ->and(ProductRating::first()->rating)->toEqual(5.0);
});

it('rejeita nota acima de 5', function () {
    $ana = customer();
    $product = Product::factory()->create();

    $this->actingAs($ana)
        ->post(route('rating.store', $product), ['rating' => 6, 'feedback' => 'Exagero'])
        ->assertSessionHasErrors('rating');

    expect(ProductRating::count())->toBe(0);
});
