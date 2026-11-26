<?php

use App\Models\Category;
use App\Models\Product;

it('marca isFavorite como verdadeiro na listagem para produto favoritado pelo usuário', function () {
    $user = customer();
    $product = Product::factory()->create();
    $product->favoritedByUsers()->attach($user->id);

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertInertia(fn ($page) => $page->where('products.data.0.isFavorite', true));
});

it('marca isFavorite como falso na listagem para produto não favoritado', function () {
    Product::factory()->create();

    $this->actingAs(customer())
        ->get(route('products.index'))
        ->assertInertia(fn ($page) => $page->where('products.data.0.isFavorite', false));
});

it('mostra quantidade e inCart do carrinho na página do produto', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer())
        ->withSession(['cart' => [['id' => $product->id, 'quantity' => 2]]])
        ->get(route('products.show', $product))
        ->assertInertia(fn ($page) => $page
            ->where('product.quantity', 2)
            ->where('product.inCart', true)
            ->where('product.isFavorite', false));
});

it('marca isFavorite como booleano nos produtos de uma categoria', function () {
    $user = customer();
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();
    $product->favoritedByUsers()->attach($user->id);

    $this->actingAs($user)
        ->get(route('categories.products', $category))
        ->assertInertia(fn ($page) => $page->where('products.data.0.isFavorite', true));
});

it('não expõe quantity nem isFavorite no model fora de uma consulta com escopo', function () {
    $product = Product::factory()->create();

    expect($product->toArray())->not->toHaveKey('quantity');
    expect($product->toArray())->not->toHaveKey('isFavorite');
});
