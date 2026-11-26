<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriteProductsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductRatingController;
use Illuminate\Support\Facades\Route;

// vitrine pública: visitantes navegam e buscam sem login.
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category}/products', [CategoryController::class, 'getProducts'])->name('categories.products');
Route::get('/search/{searchKey}', [ProductController::class, 'searchProduct'])->name('search');
// só id numérico: impede que /products/create seja lido como produto.
Route::resource('products', ProductController::class)->only(['index', 'show'])->where(['product' => '[0-9]+']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('categories', CategoryController::class)->except(['index', 'show']);

    Route::resource('products', ProductController::class)->except(['index', 'show']);

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
