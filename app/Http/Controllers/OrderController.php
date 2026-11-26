<?php

namespace App\Http\Controllers;

use App\Actions\Order\PlaceOrder;
use App\Enums\OrderStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    private Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia('Orders/IndexOrders', [
            'orders' => $request->user()->orders()
                ->with(['payments', 'products'])
                ->latest('id')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Cart $cart)
    {
        if ($cart->items() === []) {
            return to_route('cart.index')->withErrors(['cart' => 'Seu carrinho está vazio.']);
        }

        return Inertia('Orders/Form/CreateOrder', [
            'amount' => number_format($cart->subtotal(), 2, '.', ''),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request, PlaceOrder $placeOrder, Cart $cart): RedirectResponse
    {
        $placeOrder->handle($request->user(), $cart->items(), $request->validated());

        $cart->clear();

        return to_route('orders.index')->with('success', 'Pedido realizado com sucesso!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('update', $order);

        $request->validate(['status' => ['required', 'in:canceled']]);

        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Canceled], true)) {
            return back()->withErrors(['status' => 'Este pedido não pode mais ser cancelado.']);
        }

        DB::transaction(function () use ($order) {
            $order->load('products');

            foreach ($order->products as $product) {
                $product->increment('stock', $product->pivot->quantity);
            }

            $order->update(['status' => OrderStatus::Canceled]);
        });

        return back()->with('success', 'Pedido cancelado.');
    }
}
