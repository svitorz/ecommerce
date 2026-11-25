<?php

use App\Actions\Jetstream\DeleteUser;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductRating;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

it('impede inserir endereço para usuário inexistente', function () {
    expect(fn () => DB::transaction(fn () => DB::table('addresses')->insert([
        'country' => 'Brasil',
        'state' => 'SP',
        'city' => 'Campinas',
        'street' => 'Rua A',
        'zip_code' => '13000-000',
        'number' => 1,
        'user_id' => 999999,
        'created_at' => now(),
        'updated_at' => now(),
    ])))->toThrow(QueryException::class);
});

it('impede apagar usuário que possui pedido', function () {
    $user = customer();
    Order::factory()->create(['user_id' => $user->id, 'status' => 'transporting']);

    expect(fn () => DB::transaction(fn () => $user->delete()))->toThrow(QueryException::class);
    expect(User::find($user->id))->not->toBeNull();
});

it('remove os favoritos ao apagar o produto', function () {
    $user = customer();
    $product = Product::factory()->create();
    $user->favoriteProducts()->attach($product);

    $product->delete();

    expect(DB::table('favorite_products')->count())->toBe(0);
});

it('remove as avaliações ao apagar o produto', function () {
    $product = Product::factory()->create();
    ProductRating::create([
        'user_id' => customer()->id,
        'product_id' => $product->id,
        'rating' => 4,
        'feedback' => 'Bom',
    ]);

    $product->delete();

    expect(DB::table('product_ratings')->count())->toBe(0);
});

it('não apaga categoria que possui produtos', function () {
    $product = Product::factory()->create();
    $categoria = $product->category;

    $this->actingAs(admin())
        ->delete(route('categories.destroy', $categoria))
        ->assertSessionHasErrors('category');

    expect(Category::find($categoria->id))->not->toBeNull();
});

it('não apaga produto que já foi pedido', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->create(['user_id' => customer()->id, 'status' => 'transporting']);
    $order->products()->attach($product, ['quantity' => 1, 'unit_price' => $product->price]);

    $this->actingAs(admin())
        ->delete(route('products.destroy', $product))
        ->assertSessionHasErrors('product');

    expect(Product::find($product->id))->not->toBeNull();
});

it('impede excluir conta com pedidos pelo fluxo do Jetstream', function () {
    $user = customer();
    Order::factory()->create(['user_id' => $user->id, 'status' => 'transporting']);

    expect(fn () => app(DeleteUser::class)->delete($user))->toThrow(ValidationException::class);
});
