<?php

namespace App\Http\Controllers;

use App\Http\Middleware\isUserAdmin;
use App\Http\Requests\SearchQueryRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Traits\CartTrait;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProductController extends Controller implements HasMiddleware
{
    use CartTrait;

    public Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return array
     *               Insert middleware at this functions.
     */
    public static function middleware(): array
    {
        // impede que usuários comuns façam alterações nos produtos.
        return [
            new Middleware(isUserAdmin::class, except: ['show', 'index']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = $this->product->load('category')->paginate(8)->through(function ($product) {
            $cartProduct = $this->getCartProduct($product);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => $product->stock,
                'inCart' => $this->isInCart($product),
                'quantity' => $cartProduct->quantity ?? 1,
                'isFavorite' => $product->favoritedByUsers()->where('user_id', auth()->user()->id)->exists(),
            ];
        });

        return Inertia('Products/IndexProducts', [
            'products' => $products,
            'isAdmin' => request()->user()->isAdmin(),
        ]);
    }

    /**
     * Função responsável por buscar produtos ou categorias de acordo com a pesquisa do usuários'o
     * */
    public function searchProduct(SearchQueryRequest $searchKey)
    {
        $searchKey = $searchKey->searchKey;
        $products = $this->product->load('category')
            ->where('products.name', 'LIKE', '%'.$searchKey.'%')
            ->orWhere('products.description', 'LIKE', '%'.$searchKey.'%')
            ->orWhereHas('category', function ($query) use ($searchKey) {
                $query->where('name', 'LIKE', '%'.$searchKey.'%');
            })
            ->get();

        return Inertia('Products/IndexProducts', [
            'isAdmin' => request()->user()->isAdmin(),
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia('Products/Forms/ProductForm', [
            'categories' => Category::distinct()->get(),
            'route' => route('products.store'),
            'method' => 'POST',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $id = $this->product->create($request->validated())->id;

        return to_route('products.show', $id);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load('category', 'ratings.user')->through(function ($product) {
            $cartProduct = $this->getCartProduct($product);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => $product->stock,
                'inCart' => $this->isInCart($product),
                'quantity' => $cartProduct->quantity ?? 1,
                'isFavorite' => $product->favoritedByUsers()->where('user_id', auth()->user()->id)->exists(),
            ];
        });

        return Inertia('Products/ShowProduct',
            [
                'product' => $product,
                'isAdmin' => request()->user()->isAdmin(),
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return Inertia('Products/Forms/ProductForm', [
            'isAdmin' => request()->user()->isAdmin(),
            'product' => $product,
            'categories' => Category::distinct()->get(),
            'route' => route('products.update', $product->id),
            'method' => 'PUT',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $id = $product->update($request->validated());

        return to_route('products.show', $id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return to_route('products.index');
    }
}
