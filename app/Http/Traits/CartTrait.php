<?php

namespace App\Http\Traits;

use App\Models\Product;

trait CartTrait
{
    /**
     * Inicia o carrinho dentro das sessões.
     */
    public function initializeCartSession(): void
    {
        if (! session()->has('cart')) {
            session()->put('cart', []);
        }
    }

    /**
     * Adiciona um item ao carrinho do usuário.
     */
    public function addToCart(Product $product): void
    {
        $this->initializeCartSession();

        if ($this->isInCart($product)) {
            return;
        }

        $productArray = $product->toArray();
        $productArray['quantity'] = $product->quantity;

        $cart = session()->get('cart', []);
        $cart[] = $productArray;

        session()->put('cart', $cart);
    }

    /**
     * Remove um item do carrinho.
     */
    public function removeFromCart(Product $product): void
    {
        $this->initializeCartSession();

        $cart = session()->get('cart', []);
        $cart = array_filter($cart, function ($item) use ($product) {
            return $item['id'] !== $product->id;
        });

        session()->put('cart', array_values($cart));
    }

    /**
     * Atualiza a quantidade de um item no carrinho.
     */
    public function updateQuantity(Product $product, int $quantity): void
    {
        $this->initializeCartSession();

        $cart = session()->get('cart', []);
        foreach ($cart as &$item) {
            if ($item['id'] === $product->id) {
                $item['quantity'] = $quantity;
                break;
            }
        }

        session()->put('cart', $cart);
    }

    /**
     * Remove todos os produtos do carrinho.
     */
    public function clearCart(): void
    {
        session()->forget('cart');
    }

    /**
     * Retorna todos os produtos do carrinho.
     */
    public function getCartItems()
    {
        $cart = session()->get('cart', []);
        $productIds = collect($cart)->pluck('id');

        $products = Product::with('category', 'favoritedByUsers')
            ->whereIn('id', $productIds)
            ->get();

        return collect($cart)->map(function ($item) use ($products) {
            $product = $products->firstWhere('id', $item['id']);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $item['quantity'],
                'isFavorite' => $product->isFavorite,
                'category' => $product->category->name,
                'category_id' => $product->category_id,
            ];
        })->toArray();
    }

    /**
     * Verifica se um produto está no carrinho.
     */
    public function isInCart(Product $product): bool
    {
        $cart = session()->get('cart', []);

        return collect($cart)->contains('id', $product->id);
    }

    /**
     * Retorna um produto específico do carrinho.
     */
    public function getCartProduct(Product $product): ?array
    {
        $cart = session()->get('cart', []);

        foreach ($cart as $item) {
            if (isset($item['id']) && $item['id'] === $product->id) {
                return $item;
            }
        }

        return null;
    }
}
