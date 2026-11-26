<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductRating;

function rateProduct(Product $product, $user): void
{
    ProductRating::create(['product_id' => $product->id, 'user_id' => $user->id, 'rating' => 5, 'feedback' => 'Nice']);
}

it('marca como compra verificada a avaliação de quem comprou o produto', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $buyer = customer();
    $order = Order::factory()->create(['user_id' => $buyer->id, 'status' => OrderStatus::Transporting]);
    $order->products()->attach($product->id, ['quantity' => 1, 'unit_price' => 10]);
    rateProduct($product, $buyer);

    $this->actingAs(customer())
        ->get(route('products.show', $product))
        ->assertInertia(fn ($page) => $page->where('product.ratings.0.verified_purchase', true));
});

it('não marca compra verificada para quem avaliou sem comprar', function () {
    $product = Product::factory()->create();
    rateProduct($product, customer());

    $this->actingAs(customer())
        ->get(route('products.show', $product))
        ->assertInertia(fn ($page) => $page->where('product.ratings.0.verified_purchase', false));
});

it('não marca compra verificada quando o pedido foi cancelado', function () {
    $product = Product::factory()->create();
    $buyer = customer();
    $order = Order::factory()->create(['user_id' => $buyer->id, 'status' => OrderStatus::Canceled]);
    $order->products()->attach($product->id, ['quantity' => 1, 'unit_price' => 10]);
    rateProduct($product, $buyer);

    $this->actingAs(customer())
        ->get(route('products.show', $product))
        ->assertInertia(fn ($page) => $page->where('product.ratings.0.verified_purchase', false));
});
