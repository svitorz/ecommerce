<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Carbon\Carbon;

function checkoutCardPayload(array $overrides = []): array
{
    return array_merge([
        'fullName' => 'Fulano de Tal',
        'cardNumber' => '4111 1111 1111 1234',
        'ccv' => '123',
        'cardExpiration' => now()->addYear()->format('m/Y'),
    ], $overrides);
}

it('finaliza o pedido gravando itens, preço do momento e baixando o estoque', function () {
    $a = Product::factory()->create(['price' => 10.00, 'stock' => 10]);
    $b = Product::factory()->create(['price' => 2.50, 'stock' => 5]);

    $this->actingAs(customer())
        ->withSession(['cart' => [['id' => $a->id, 'quantity' => 2], ['id' => $b->id, 'quantity' => 1]]])
        ->post(route('orders.store'), checkoutCardPayload())
        ->assertRedirect(route('orders.index'))
        ->assertSessionMissing('cart');

    $order = Order::with('products', 'payments')->first();

    expect((float) $order->subtotal)->toBe(22.5)
        ->and($order->status)->toBe('transporting')
        ->and($order->products)->toHaveCount(2);

    $pivotA = $order->products->firstWhere('id', $a->id)->pivot;
    $pivotB = $order->products->firstWhere('id', $b->id)->pivot;

    expect((int) $pivotA->quantity)->toBe(2)
        ->and((float) $pivotA->unit_price)->toBe(10.0)
        ->and((int) $pivotB->quantity)->toBe(1)
        ->and((float) $pivotB->unit_price)->toBe(2.5)
        ->and($a->fresh()->stock)->toBe(8)
        ->and($b->fresh()->stock)->toBe(4)
        ->and($order->payments->card_last_four)->toBe('1234');
});

it('rejeita carrinho vazio sem criar pedido', function () {
    $this->actingAs(customer())
        ->withSession(['cart' => []])
        ->post(route('orders.store'), checkoutCardPayload())
        ->assertSessionHasErrors('cart');

    expect(Order::count())->toBe(0);
});

it('rejeita pedido com estoque insuficiente sem alterar nada', function () {
    $product = Product::factory()->create(['price' => 10.00, 'stock' => 1]);

    $this->actingAs(customer())
        ->withSession(['cart' => [['id' => $product->id, 'quantity' => 3]]])
        ->post(route('orders.store'), checkoutCardPayload())
        ->assertSessionHasErrors('cart');

    expect(Order::count())->toBe(0)
        ->and(Payment::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(1);
});

it('usa o preço do banco e ignora o preço gravado na sessão', function () {
    $product = Product::factory()->create(['price' => 10.00, 'stock' => 10]);

    $this->actingAs(customer())
        ->withSession(['cart' => [['id' => $product->id, 'quantity' => 2, 'price' => 0.01]]])
        ->post(route('orders.store'), checkoutCardPayload())
        ->assertSessionHasNoErrors();

    expect((float) Order::first()->subtotal)->toBe(20.0);
});

it('redireciona a tela de pagamento para o carrinho quando ele está vazio', function () {
    $this->actingAs(customer())
        ->withSession(['cart' => []])
        ->get(route('orders.create'))
        ->assertRedirect(route('cart.index'));
});

it('grava o vencimento do cartão no primeiro dia do mês mesmo em dia 31', function () {
    $this->travelTo(Carbon::parse('2026-01-31'));
    $product = Product::factory()->create(['price' => 10.00, 'stock' => 10]);

    $this->actingAs(customer())
        ->withSession(['cart' => [['id' => $product->id, 'quantity' => 1]]])
        ->post(route('orders.store'), checkoutCardPayload(['cardExpiration' => '02/2027']))
        ->assertSessionHasNoErrors();

    expect(Payment::first()->card_expiration)->toStartWith('2027-02-01');
});
