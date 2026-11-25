<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteProductsController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $request->user()->favoriteProducts()->syncWithoutDetaching([$product->id]);

        return back();
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $request->user()->favoriteProducts()->detach($product->id);

        return back();
    }
}
