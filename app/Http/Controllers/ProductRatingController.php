<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRating;
use App\Models\User;
use Illuminate\Http\Request;

class ProductRatingController extends Controller
{
    public ProductRating $productRating;

    public function __construct(ProductRating $productRating)
    {
        $this->productRating = $productRating;
    }

    public function store(User $user, Product $product, Request $request)
    {
        $validatedData = $request->validate([
            'rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'feedback' => ['required', 'string', 'max:128'],
        ]);
        $this->productRating->updateOrCreate(
            [
                'product_id' => $product->id,
                'user_id' => $user->id,
            ],
            [
                'rating' => $validatedData['rating'], // Dados para atualizar/criar
                'feedback' => $validatedData['feedback'],
            ]
        );

        return back();
    }
}
