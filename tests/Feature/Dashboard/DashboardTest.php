<?php

use App\Models\Product;

it('abre o dashboard para cliente com produtos recomendados', function () {
    Product::factory()->count(2)->create();

    $this->actingAs(customer())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Dashboard')->has('products'));
});

it('mantém acesso à listagem de produtos para cliente comum', function () {
    $this->actingAs(customer())
        ->get(route('products.index'))
        ->assertOk();
});

it('bloqueia cliente comum ao atualizar produto', function () {
    $product = Product::factory()->create();

    $this->actingAs(customer())
        ->put(route('products.update', $product), [
            'name' => 'Novo', 'description' => 'Y', 'price' => '10.50', 'stock' => 5, 'category_id' => $product->category_id,
        ])
        ->assertForbidden();
});
