<?php

use App\Models\Product;

it('redireciona para o produto editado e não para o id 1', function () {
    Product::factory()->create();
    $segundo = Product::factory()->create();

    $this->actingAs(admin())
        ->put(route('products.update', $segundo), [
            'name' => 'Produto editado',
            'description' => 'Descrição editada',
            'price' => '19.90',
            'stock' => 4,
            'category_id' => $segundo->category_id,
        ])
        ->assertRedirect(route('products.show', $segundo));

    expect($segundo->fresh()->name)->toBe('Produto editado');
});

it('mostra o produto com as avaliações carregadas', function () {
    $product = Product::factory()->create();

    $this->actingAs(customer())
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Products/ShowProduct')->has('product.ratings'));
});
