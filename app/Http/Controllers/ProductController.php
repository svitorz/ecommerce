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
        $products = Product::query()
            ->with('category')
            ->withFavoriteFlag(auth()->id())
            ->paginate(8)
            ->through(function (Product $product) {
                $cartProduct = $this->getCartProduct($product);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'inCart' => $this->isInCart($product),
                    'quantity' => $cartProduct['quantity'] ?? 1,
                    'isFavorite' => $product->isFavorite,
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
        $products = Product::query()
            ->with('category')
            ->withFavoriteFlag(auth()->id())
            ->where('products.name', 'LIKE', '%'.$searchKey.'%')
            ->orWhere('products.description', 'LIKE', '%'.$searchKey.'%')
            ->orWhereHas('category', function ($query) use ($searchKey) {
                $query->where('name', 'LIKE', '%'.$searchKey.'%');
            })
            ->get()
            ->each(fn (Product $product) => $this->decorateWithCart($product));

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
        $id = Product::create($request->validated())->id;

        return to_route('products.show', $id);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->loadMissing('category', 'ratings.user');
        $product->setAttribute('isFavorite', $product->favoritedByUsers()->where('users.id', auth()->id())->exists());
        $this->decorateWithCart($product);

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
        $product->update($request->validated());

        return to_route('products.show', $product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if ($product->orders()->exists()) {
            return back()->withErrors(['product' => 'Não é possível excluir produto que já foi pedido.']);
        }

        $product->delete();

        return to_route('products.index');
    }
}
