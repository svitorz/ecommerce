<?php

namespace App\Services;

use App\Models\Product;

class Cart
{
    /**
     * Inicia a chave do carrinho na sessão quando ela ainda não existe.
     */
    public function initialize(): void
    {
        if (! session()->has('cart')) {
            session()->put('cart', []);
        }
    }

    /**
     * Itens do carrinho com dados atuais do banco. Remove da sessão os produtos que não existem mais.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        $cart = session()->get('cart', []);

        $products = Product::query()
            ->with('category')
            ->withFavoriteFlag(auth()->id())
            ->whereIn('id', collect($cart)->pluck('id'))
            ->get()
            ->keyBy('id');

        $existing = array_values(array_filter($cart, fn ($item) => $products->has($item['id'])));

        if (count($existing) !== count($cart)) {
            session()->put('cart', $existing);
        }

        return array_map(function ($item) use ($products) {
            $product = $products->get($item['id']);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $item['quantity'],
                'stock' => $product->stock,
                'isFavorite' => $product->isFavorite,
                'category' => $product->category->name,
                'category_id' => $product->category_id,
            ];
        }, $existing);
    }

    public function add(Product $product, int $quantity): void
    {
        if ($this->has($product)) {
            return;
        }

        session()->push('cart', ['id' => $product->id, 'quantity' => $quantity]);
    }

    public function update(Product $product, int $quantity): void
    {
        $cart = collect(session()->get('cart', []))
            ->map(fn ($item) => $item['id'] === $product->id ? ['id' => $item['id'], 'quantity' => $quantity] : $item)
            ->values()
            ->all();

        session()->put('cart', $cart);
    }

    public function remove(Product $product): void
    {
        session()->put('cart', array_values(array_filter(
            session()->get('cart', []),
            fn ($item) => $item['id'] !== $product->id,
        )));
    }

    public function clear(): void
    {
        session()->forget('cart');
    }

    public function has(Product $product): bool
    {
        return collect(session()->get('cart', []))->contains('id', $product->id);
    }

    /**
     * Quantidade no carrinho; 1 quando o produto não está nele.
     */
    public function quantityOf(Product $product): int
    {
        $item = collect(session()->get('cart', []))->firstWhere('id', $product->id);

        return $item['quantity'] ?? 1;
    }

    /**
     * Soma quantidade × preço dos itens, com preço do banco.
     */
    public function subtotal(): float
    {
        return (float) collect($this->items())->sum(fn ($item) => $item['quantity'] * $item['price']);
    }

    /**
     * Anexa `quantity` e `inCart` ao produto para o frontend.
     */
    public function decorate(Product $product): Product
    {
        $product->setAttribute('quantity', $this->quantityOf($product));
        $product->setAttribute('inCart', $this->has($product));

        return $product;
    }
}
