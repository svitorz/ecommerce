<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductRatingController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'feedback' => ['required', 'string', 'max:128'],
        ]);

        ProductRating::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $request->user()->id],
            $validated,
        );

        return back();
    }
}
