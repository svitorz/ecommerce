<?php

namespace App\Http\Controllers;

use App\Http\Traits\CartTrait;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    use CartTrait;

    public function __construct()
    {
        $this->initializeCartSession();
    }

    public function getAll()
    {
        return $this->getCartItems();
    }

    public function index(calculateSubTotalController $subtotalCalculator, GetRecommendedProductsController $recommendedProducts)
    {
        return Inertia::render('Cart/IndexCart', [
            'products' => (array) $this->getCartItems(), // vem da trait.
            'subtotal' => number_format($subtotalCalculator->calculate(), 2, '.', ''),
            'recommended_products' => (array) $recommendedProducts->handle(),
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
            return back()->withErrors(['quantity' => 'Quantidade maior que o estoque disponível.']);
        }

        $this->addToCart($product, $validated['quantity']);

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
        if (! $this->isInCart($product)) {
            return back()->withErrors(['cart' => 'O produto não está no carrinho.']);
        }

        if ($validated['quantity'] > $product->stock) {
            return back()->withErrors(['quantity' => 'Quantidade maior que o estoque disponível.']);
        }

        $this->updateQuantity($product, $validated['quantity']);

        return back()->with('success', 'Quantity successfully updated.');
    }

    public function destroy(Product $product)
    {
        $this->removeFromCart($product);

        return back();
    }
}
