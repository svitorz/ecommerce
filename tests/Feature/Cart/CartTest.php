<?php

use App\Models\Product;

it('grava no carrinho o id e a quantidade pedidos', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer())
        ->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2])
        ->assertSessionHas('cart', [['id' => $product->id, 'quantity' => 2]]);
});

it('impede adicionar quantidade acima do estoque', function () {
    $product = Product::factory()->create(['stock' => 2]);

    $this->actingAs(customer())
        ->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 3])
        ->assertSessionHasErrors('quantity')
        ->assertSessionHas('cart', []);
});

it('atualiza a quantidade dentro do estoque', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer())
        ->withSession(['cart' => [['id' => $product->id, 'quantity' => 1]]])
        ->put(route('cart.update', $product), ['quantity' => 3])
        ->assertSessionHas('cart', [['id' => $product->id, 'quantity' => 3]]);
});

it('impede atualizar quantidade acima do estoque', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer())
        ->withSession(['cart' => [['id' => $product->id, 'quantity' => 1]]])
        ->put(route('cart.update', $product), ['quantity' => 11])
        ->assertSessionHasErrors('quantity');
});

it('impede atualizar produto que não está no carrinho', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer())
        ->put(route('cart.update', $product), ['quantity' => 2])
        ->assertSessionHasErrors('cart');
});

it('remove item do carrinho com DELETE', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer())
        ->withSession(['cart' => [['id' => $product->id, 'quantity' => 1]]])
        ->delete(route('cart.destroy', $product))
        ->assertRedirect()
        ->assertSessionHas('cart', []);
});

it('não remove item do carrinho com GET', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer())
        ->get('/cart/'.$product->id)
        ->assertStatus(405);
});

it('não quebra quando um produto do carrinho foi apagado', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $user = customer();

    $this->actingAs($user)->withSession(['cart' => [['id' => $product->id, 'quantity' => 1]]]);
    $product->delete();

    $this->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Cart/IndexCart')->has('products', 0))
        ->assertSessionHas('cart', []);
});

it('mostra a quantidade do carrinho na listagem de produtos', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer())
        ->withSession(['cart' => [['id' => $product->id, 'quantity' => 3]]])
        ->get(route('products.index'))
        ->assertInertia(fn ($page) => $page->where('products.data.0.quantity', 3));
});
