<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FavoriteProductsController;
use App\Http\Controllers\GetRecommendedProductsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductRatingController;
use App\Services\Cart;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (Cart $cart) {
        $recommended_products = new GetRecommendedProductsController;

        return Inertia::render('Dashboard', [
            'products' => $recommended_products->handle($cart, 12),
        ]);
    })->name('dashboard');
    // busca específica de categorias.
    Route::get('/categories/{category}/products', [CategoryController::class, 'getProducts'])->name('categories.products');

    // buscas
    Route::get('/search/{searchKey}', [ProductController::class, 'searchProduct'])->name('search');

    Route::resource('categories', CategoryController::class)->except(['show']);

    Route::resource('products', ProductController::class);

    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'update']);

    // carrinho
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/', [CartController::class, 'store'])->name('cart.store');
    Route::put('/cart/{product}/update', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{product}', [CartController::class, 'destroy'])->name('cart.destroy');

    // endereço
    Route::post('/address', [AddressController::class, 'store'])->name('address.store');

    Route::post('/favorites/{product}', [FavoriteProductsController::class, 'store'])->name('favorite.store');
    Route::delete('/favorites/{product}', [FavoriteProductsController::class, 'destroy'])->name('favorite.destroy');

    Route::post('/products/{product}/ratings', [ProductRatingController::class, 'store'])->name('rating.store');
});
