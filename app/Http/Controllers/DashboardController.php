<?php

namespace App\Http\Controllers;

use App\Services\Cart;
use App\Services\RecommendedProducts;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Cart $cart, RecommendedProducts $recommended)
    {
        return Inertia::render('Dashboard', [
            'products' => $recommended->handle($cart, 12),
        ]);
    }
}
