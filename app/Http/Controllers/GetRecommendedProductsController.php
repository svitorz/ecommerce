<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;
use App\Models\Product;

class GetRecommendedProductsController extends Controller
{
    /**
     * Retorna produtos com base nas escolhas do usuário.
     */
    public function handle(int $limit = 3)
    {
        $cartController = new CartController;
        // Obtenha os produtos no carrinho
        $cartItems = $cartController->getCartItems();

        if (empty($cartItems)) {
            return Product::distinct()->with('category')->limit($limit)->get()->toArray();
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
            ->limit($limit) // Limite de 3 produtos, mas pode ser ajustado
            ->get();

        return $recommendedProducts->toArray();
    }
}
