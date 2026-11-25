<?php

namespace App\Http\Controllers;

use App\Actions\Payment\StoreNewPayment;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function create(calculateSubTotalController $subtotalCalculator)
    {
        return Inertia('Orders/Form/CreateOrder', [
            'amount' => number_format($subtotalCalculator->calculate(), 2, '.', ''),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, calculateSubTotalController $subtotalCalculator, StoreNewPayment $storePayment)
    {
        $validated = $request->validate([
            'fullName' => 'required|string|min:3|max:255',
            'ccv' => 'required|digits_between:3,4',
            'cardNumber' => 'required|regex:/^[0-9\s]{16,20}$/', // Aceita números e espaços
            'cardExpiration' => 'required|date_format:m/Y|after_or_equal:today',
        ]);
        $order = Order::create(
            [
                'status' => 'pending_payment',
                'subtotal' => number_format($subtotalCalculator->calculate(), 2, '.', ''),
                'user_id' => auth()->user()->id,
            ]
        );
        $payment = new Payment([
            'full_name' => $validated['fullName'],
            'card_last_four' => substr(preg_replace('/\D/', '', $validated['cardNumber']), -4),
            'card_expiration' => \Carbon\Carbon::createFromFormat('m/Y', $validated['cardExpiration'])->startOfMonth()->format('Y-m-d'),

        ]);
        $storePayment->handle($payment, $order);

        return to_route('orders.index');
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
