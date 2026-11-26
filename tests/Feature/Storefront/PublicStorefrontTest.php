<?php

use App\Models\Category;
use App\Models\Product;

it('abre a vitrine para visitante', function () {
    Category::factory()->create();
    Product::factory()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Home')->has('categories')->has('products'));
});

it('libera listagem, produto, categorias e busca para visitante', function () {
    $product = Product::factory()->create();
    $category = Category::factory()->create();

    $this->get(route('products.index'))->assertOk();
    $this->get(route('products.show', $product))->assertOk();
    $this->get(route('categories.index'))->assertOk();
    $this->get(route('categories.products', $category))->assertOk();
    $this->call('GET', route('search', 'a'), ['searchKey' => 'a'])->assertOk();
});

it('bloqueia visitante de adicionar ao carrinho e de favoritar', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1])
        ->assertRedirect(route('login'));

    $this->post(route('favorite.store', $product))
        ->assertRedirect(route('login'));
});

it('redireciona visitante que tenta abrir a tela de criação de produto', function () {
    $this->get(route('products.create'))
        ->assertRedirect(route('login'));
});

it('mantém a tela de criação de produto para admin', function () {
    $this->actingAs(admin())
        ->get(route('products.create'))
        ->assertOk();
});
