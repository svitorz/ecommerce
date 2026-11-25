<?php

use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

it('impede favorito duplicado do mesmo usuário e produto', function () {
    $user = customer();
    $product = Product::factory()->create();
    $linha = ['user_id' => $user->id, 'product_id' => $product->id, 'created_at' => now(), 'updated_at' => now()];

    DB::table('favorite_products')->insert($linha);

    expect(fn () => DB::table('favorite_products')->insert($linha))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('impede avaliação duplicada do mesmo usuário e produto', function () {
    $user = customer();
    $product = Product::factory()->create();
    $linha = [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'rating' => 4,
        'feedback' => 'Bom',
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('product_ratings')->insert($linha);

    expect(fn () => DB::table('product_ratings')->insert($linha))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('deixa só uma linha quando o favorito é gravado duas vezes', function () {
    $user = customer();
    $product = Product::factory()->create();

    $this->actingAs($user)->post(route('favorite.store', $product));
    $this->actingAs($user)->post(route('favorite.store', $product));

    expect(DB::table('favorite_products')
        ->where('user_id', $user->id)
        ->where('product_id', $product->id)
        ->count())->toBe(1);
});
