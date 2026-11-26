<?php

namespace App\Services;

use App\Models\Product;

class RecommendedProducts
{
    /**
     * Retorna produtos com base nas escolhas do usuário.
     */
    public function handle(Cart $cart, int $limit = 3): array
    {
        // Obtenha os produtos no carrinho
        $cartItems = $cart->items();

        if (empty($cartItems)) {
            return Product::distinct()
                ->with('category')
                ->withFavoriteFlag(auth()->id())
                ->limit($limit)
                ->get()
                ->each(fn (Product $product) => $cart->decorate($product))
                ->toArray();
        }

        // Inicializa um array para armazenar as categorias dos produtos no carrinho
        $categories = [];

        // Preenche o array com as categorias dos produtos no carrinho
        foreach ($cartItems as $item) {
            $categories[] = $item['category_id']; // Assumindo que o produto tem o campo 'category_id'
        }

        // Remover duplicatas para evitar que busquemos produtos de categorias repetidas
        $categories = array_unique($categories);

        // Buscar produtos recomendados
        // Excluindo os produtos que já estão no carrinho e filtrando pelas categorias
        $recommendedProducts = Product::whereIn('category_id', $categories)
            ->whereNotIn('id', array_column($cartItems, 'id')) // Exclui produtos já no carrinho
            ->with('category')
            ->withFavoriteFlag(auth()->id())
            ->limit($limit) // Limite de 3 produtos, mas pode ser ajustado
            ->get()
            ->each(fn (Product $product) => $cart->decorate($product));

        return $recommendedProducts->toArray();
    }
}
