<?php

namespace App\Actions\Payment;

use App\Models\Order;
use App\Models\Payment;

class StoreNewPayment
{
    public function handle(Payment $payment, Order $order): bool
    {
        $payment->order_id = $order->id;
        if ($payment->save()) {
            $order->status = 'transporting';
            $order->save();

            return true;
        }

        return false;
    }
}
