<?php

use App\Models\Product;
use App\Models\ProductRating;

it('envia média e contagem das avaliações na listagem', function () {
    $product = Product::factory()->create();

    ProductRating::create(['product_id' => $product->id, 'user_id' => customer()->id, 'rating' => 4, 'feedback' => 'Good']);
    ProductRating::create(['product_id' => $product->id, 'user_id' => customer()->id, 'rating' => 2, 'feedback' => 'Okay']);

    $this->actingAs(customer())
        ->get(route('products.index'))
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.ratings_count', 2)
            ->where('products.data.0.average_rating', 3));
});

it('envia contagem zero para produto sem avaliação', function () {
    Product::factory()->create();

    $this->actingAs(customer())
        ->get(route('products.index'))
        ->assertInertia(fn ($page) => $page->where('products.data.0.ratings_count', 0));
});
