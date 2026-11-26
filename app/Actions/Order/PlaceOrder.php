<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    /**
     * @param  array<int, array{id: int, quantity: int}>  $cartItems
     * @param  array{fullName: string, cardNumber: string, cardExpiration: string}  $payment
     */
    public function handle(User $user, array $cartItems, array $payment): Order
    {
        if ($cartItems === []) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        return DB::transaction(function () use ($user, $cartItems, $payment) {
            $quantities = collect($cartItems)
                ->mapWithKeys(fn ($item) => [$item['id'] => (int) $item['quantity']]);

            $products = Product::whereIn('id', $quantities->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $pivot = [];

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);

                if (! $product || $product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'cart' => 'Not enough stock for '.($product->name ?? 'one of the products').'.',
                    ]);
                }

                $subtotal += $product->price * $quantity;
                $pivot[$productId] = ['quantity' => $quantity, 'unit_price' => $product->price];
            }

            $order = $user->orders()->create([
                'status' => OrderStatus::PendingPayment,
                'subtotal' => round($subtotal, 2),
            ]);

            $order->products()->attach($pivot);

            foreach ($pivot as $productId => $item) {
                $products->get($productId)->decrement('stock', $item['quantity']);
            }

            $order->payments()->create([
                'full_name' => $payment['fullName'],
                'card_last_four' => substr(preg_replace('/\D/', '', $payment['cardNumber']), -4),
                'card_expiration' => Carbon::createFromFormat('!m/Y', $payment['cardExpiration'])->toDateString(),
            ]);

            // Pagamento simulado: aprovado na hora.
            $order->update(['status' => OrderStatus::Transporting]);

            return $order;
        });
    }
}
