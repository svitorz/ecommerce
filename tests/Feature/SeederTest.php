<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

it('popula o banco com dados coerentes', function () {
    $this->seed();

    expect(User::where('isAdmin', true)->count())->toBe(1)
        ->and(User::count())->toBe(3)
        ->and(Category::count())->toBe(10)
        ->and(Product::count())->toBe(30)
        ->and(Order::count())->toBe(10);
});

it('factories funcionam em banco vazio', function () {
    expect(Product::factory()->create()->category)->not->toBeNull()
        ->and(Order::factory()->create()->user_id)->not->toBeNull();
});

it('popula categorias em inglês e nomes de produto de catálogo', function () {
    $this->seed();

    expect(Category::where('name', 'Electronics')->exists())->toBeTrue()
        ->and(Product::where('name', 'like', 'Prof.%')->exists())->toBeFalse()
        ->and(Product::where('name', 'like', '%Jr.%')->exists())->toBeFalse();
});
