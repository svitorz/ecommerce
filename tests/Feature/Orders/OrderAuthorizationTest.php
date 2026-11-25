<?php

use App\Models\Order;

it('lista apenas os pedidos do usuário autenticado', function () {
    $ana = customer();
    $bruno = customer();

    Order::factory(2)->create(['user_id' => $ana->id, 'status' => 'transporting']);
    Order::factory()->create(['user_id' => $bruno->id, 'status' => 'transporting']);

    $this->actingAs($ana)
        ->get(route('orders.index'))
        ->assertInertia(fn ($page) => $page->component('Orders/IndexOrders')->has('orders', 2));
});

it('impede outro usuário de cancelar pedido alheio', function () {
    $ana = customer();
    $bruno = customer();
    $order = Order::factory()->create(['user_id' => $ana->id, 'status' => 'pending_payment']);

    $this->actingAs($bruno)
        ->put(route('orders.update', $order), ['status' => 'canceled'])
        ->assertForbidden();

    expect($order->fresh()->status)->toBe('pending_payment');
});

it('permite o dono cancelar pedido pendente', function () {
    $ana = customer();
    $order = Order::factory()->create(['user_id' => $ana->id, 'status' => 'pending_payment']);

    $this->actingAs($ana)
        ->put(route('orders.update', $order), ['status' => 'canceled'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe('canceled');
});

it('impede cancelar pedido já concluído', function () {
    $ana = customer();
    $order = Order::factory()->create(['user_id' => $ana->id, 'status' => 'completed']);

    $this->actingAs($ana)
        ->put(route('orders.update', $order), ['status' => 'canceled'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('completed');
});

it('rejeita status diferente de canceled', function () {
    $ana = customer();
    $order = Order::factory()->create(['user_id' => $ana->id, 'status' => 'transporting']);

    $this->actingAs($ana)
        ->put(route('orders.update', $order), ['status' => 'completed'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('transporting');
});
