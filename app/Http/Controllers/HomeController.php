<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\Cart;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Vitrine pública: categorias e produtos em destaque.
     */
    public function index(Cart $cart): Response
    {
        return Inertia::render('Home', [
            'categories' => Category::query()->orderBy('name')->limit(6)->get(),
            'products' => Product::query()
                ->with('category')
                ->withRatingSummary()
                ->withFavoriteFlag(auth()->id())
                ->latest('id')
                ->limit(8)
                ->get()
                ->each(fn (Product $product) => $cart->decorate($product)),
            'isAdmin' => request()->user()?->isAdmin() ?? false,
        ]);
    }
}
