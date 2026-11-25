<?php

namespace App\Http\Controllers;

use App\Http\Traits\CartTrait;

class calculateSubTotalController extends Controller
{
    use CartTrait;

    //
    public function calculate()
    {
        $cartProducts = $this->getCartItems();

        $subtotal = 0;

        foreach ($cartProducts as $product) {
            $subtotal += $product['quantity'] * $product['price'];
        }

        return $subtotal;
    }
}
