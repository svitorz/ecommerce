<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Requests\SearchQueryRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller implements HasMiddleware
{
    /**
     * @return array
     *               Insert middleware at this functions.
     */
    public static function middleware(): array
    {
        // impede que usuários comuns façam alterações nos produtos.
        return [
            new Middleware(EnsureUserIsAdmin::class, except: ['show', 'index', 'searchProduct']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Cart $cart)
    {
        $products = Product::query()
            ->with('category')
            ->withFavoriteFlag(auth()->id())
            ->withRatingSummary()
            ->paginate(8)
            ->through(function (Product $product) use ($cart) {
                $cart->decorate($product);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'average_rating' => $product->average_rating,
                    'ratings_count' => $product->ratings_count,
                    'image_url' => $product->image_url,
                    'category' => $product->category?->only(['id', 'name']),
                    'inCart' => $product->inCart,
                    'quantity' => $product->quantity,
                    'isFavorite' => $product->isFavorite,
                ];
            });

        return Inertia('Products/IndexProducts', [
            'products' => $products,
            'isAdmin' => request()->user()?->isAdmin() ?? false,
        ]);
    }

    /**
     * Função responsável por buscar produtos ou categorias de acordo com a pesquisa do usuários'o
     * */
    public function searchProduct(SearchQueryRequest $searchKey, Cart $cart)
    {
        $searchKey = $searchKey->searchKey;
        $products = Product::query()
            ->with('category')
            ->withFavoriteFlag(auth()->id())
            ->withRatingSummary()
            ->where('products.name', 'LIKE', '%'.$searchKey.'%')
            ->orWhere('products.description', 'LIKE', '%'.$searchKey.'%')
            ->orWhereHas('category', function ($query) use ($searchKey) {
                $query->where('name', 'LIKE', '%'.$searchKey.'%');
            })
            ->get()
            ->each(fn (Product $product) => $cart->decorate($product));

        return Inertia('Products/IndexProducts', [
            'isAdmin' => request()->user()?->isAdmin() ?? false,
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
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $id = Product::create($data)->id;

        return to_route('products.show', $id);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product, Cart $cart)
    {
        $product->loadMissing('category', 'ratings.user');
        $product->setAttribute('isFavorite', $product->favoritedByUsers()->where('users.id', auth()->id())->exists());
        $cart->decorate($product);

        // Compra verificada: quem tem pedido não cancelado com este produto.
        $buyerIds = Order::query()
            ->whereHas('products', fn ($q) => $q->where('products.id', $product->id))
            ->where('status', '!=', OrderStatus::Canceled->value)
            ->pluck('user_id')
            ->all();

        $product->ratings->each(fn ($rating) => $rating->setAttribute(
            'verified_purchase',
            in_array($rating->user_id, $buyerIds, true),
        ));

        return Inertia('Products/ShowProduct',
            [
                'product' => $product,
                'isAdmin' => request()->user()?->isAdmin() ?? false,
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return Inertia('Products/Forms/ProductForm', [
            'isAdmin' => request()->user()?->isAdmin() ?? false,
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
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return to_route('products.show', $product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if ($product->orders()->exists()) {
            return back()->withErrors(['product' => 'You cannot delete a product that has been ordered.']);
        }

        $product->delete();

        return to_route('products.index');
    }
}
