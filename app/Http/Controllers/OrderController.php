<?php

namespace App\Http\Controllers;

use App\Actions\Order\PlaceOrder;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Traits\CartTrait;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    use CartTrait;

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
    public function create(calculateSubTotalController $subtotalCalculator)
    {
        if ($this->getCartItems() === []) {
            return to_route('cart.index')->withErrors(['cart' => 'Seu carrinho está vazio.']);
        }

        return Inertia('Orders/Form/CreateOrder', [
            'amount' => number_format($subtotalCalculator->calculate(), 2, '.', ''),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $placeOrder->handle($request->user(), $this->getCartItems(), $request->validated());

        $this->clearCart();

        return to_route('orders.index')->with('success', 'Pedido realizado com sucesso!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('update', $order);

        $request->validate(['status' => ['required', 'in:canceled']]);

        if (in_array($order->status, ['completed', 'canceled'], true)) {
            return back()->withErrors(['status' => 'Este pedido não pode mais ser cancelado.']);
        }

        $order->update(['status' => 'canceled']);

        return back()->with('success', 'Pedido cancelado.');
    }
}
