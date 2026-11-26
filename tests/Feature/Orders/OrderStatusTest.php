<?php

use App\Enums\OrderStatus;
use App\Models\Order;

it('converte o status do pedido para o enum', function () {
    $order = Order::factory()->create(['user_id' => customer()->id, 'status' => 'transporting']);

    expect($order->fresh()->status)->toBe(OrderStatus::Transporting);
});

it('mantém o status como texto na listagem de pedidos', function () {
    $user = customer();
    Order::factory()->create(['user_id' => $user->id, 'status' => 'transporting']);

    $this->actingAs($user)
        ->get(route('orders.index'))
        ->assertInertia(fn ($page) => $page->where('orders.0.status', 'transporting'));
});
