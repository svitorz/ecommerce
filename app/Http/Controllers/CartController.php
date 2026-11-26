<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart;
use App\Services\RecommendedProducts;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    public function __construct(private Cart $cart)
    {
        $this->cart->initialize();
    }

    public function index(RecommendedProducts $recommendedProducts)
    {
        return Inertia::render('Cart/IndexCart', [
            'products' => $this->cart->items(),
            'subtotal' => number_format($this->cart->subtotal(), 2, '.', ''),
            'recommended_products' => (array) $recommendedProducts->handle($this->cart),
            'is_admin' => auth()->user()->isAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        $product = Product::findOrFail($validated['product_id']);

        if ($validated['quantity'] > $product->stock) {
            return back()->withErrors(['quantity' => 'The quantity is more than the available stock.']);
        }

        $this->cart->add($product, $validated['quantity']);

        return back()->with([
            'message' => 'Product successfully added to cart!',
            'success' => true,
        ]);
    }

    public function update(Product $product, Request $request)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // verifica se o item está no carrinho
        if (! $this->cart->has($product)) {
            return back()->withErrors(['cart' => 'This product is not in your cart.']);
        }

        if ($validated['quantity'] > $product->stock) {
            return back()->withErrors(['quantity' => 'The quantity is more than the available stock.']);
        }

        $this->cart->update($product, $validated['quantity']);

        return back()->with('success', 'Quantity successfully updated.');
    }

    public function destroy(Product $product)
    {
        $this->cart->remove($product);

        return back();
    }
}
