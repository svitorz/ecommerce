<?php

use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;

it('não possui mais número de cartão nem CVV no banco', function () {
    expect(Schema::hasColumn('payments', 'card_number'))->toBeFalse()
        ->and(Schema::hasColumn('payments', 'ccv'))->toBeFalse()
        ->and(Schema::hasColumn('payments', 'card_last_four'))->toBeTrue();
});

it('grava apenas os 4 últimos dígitos do cartão no checkout', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer())
        ->withSession(['cart' => [['id' => $product->id, 'quantity' => 1]]])
        ->post(route('orders.store'), [
            'fullName' => 'Fulano de Tal',
            'cardNumber' => '4111 1111 1111 1234',
            'ccv' => '123',
            'cardExpiration' => now()->addYear()->format('m/Y'),
        ])
        ->assertRedirect();

    $payment = Payment::first();

    expect($payment->card_last_four)->toBe('1234')
        ->and(json_encode($payment->toArray()))->not->toContain('41111111');
});

it('rejeita CVV com menos de 3 dígitos', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(customer())
        ->withSession(['cart' => [['id' => $product->id, 'quantity' => 1]]])
        ->post(route('orders.store'), [
            'fullName' => 'Fulano de Tal',
            'cardNumber' => '4111 1111 1111 1234',
            'ccv' => '12',
            'cardExpiration' => now()->addYear()->format('m/Y'),
        ])
        ->assertSessionHasErrors('ccv');
});
