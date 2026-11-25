<?php

use App\Models\Category;
use App\Models\Product;

it('bloqueia cliente comum na tela de criação de produto', function () {
    $this->actingAs(customer())
        ->get(route('products.create'))
        ->assertForbidden();
});

it('bloqueia cliente comum na tela de criação de categoria', function () {
    $this->actingAs(customer())
        ->get(route('categories.create'))
        ->assertForbidden();
});

it('bloqueia cliente comum ao criar produto', function () {
    $category = Category::factory()->create();

    $this->actingAs(customer())
        ->post(route('products.store'), [
            'name' => 'X', 'description' => 'Y', 'price' => '10.50', 'stock' => 5, 'category_id' => $category->id,
        ])
        ->assertForbidden();

    expect(Product::count())->toBe(0);
});

it('bloqueia cliente comum ao excluir produto', function () {
    $product = Product::factory()->create();

    $this->actingAs(customer())
        ->delete(route('products.destroy', $product))
        ->assertForbidden();

    expect(Product::find($product->id))->not->toBeNull();
});

it('permite admin acessar a tela de criação de produto', function () {
    $this->actingAs(admin())
        ->get(route('products.create'))
        ->assertOk();
});

it('permite admin criar produto', function () {
    $category = Category::factory()->create();

    $this->actingAs(admin())
        ->post(route('products.store'), [
            'name' => 'X', 'description' => 'Y', 'price' => '10.50', 'stock' => 5, 'category_id' => $category->id,
        ])
        ->assertRedirect();

    expect(Product::where('name', 'X')->exists())->toBeTrue();
});

it('permite cliente comum listar produtos e categorias', function () {
    $this->actingAs(customer());

    $this->get(route('products.index'))->assertOk();
    $this->get(route('categories.index'))->assertOk();
});
