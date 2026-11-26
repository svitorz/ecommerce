<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

function productPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Lamp',
        'description' => 'Desk lamp',
        'price' => '25.50',
        'stock' => 5,
        'category_id' => Category::factory()->create()->id,
    ], $overrides);
}

it('grava a imagem enviada pelo admin no disco público', function () {
    $this->actingAs(admin())->post(route('products.store'), productPayload([
        'image' => UploadedFile::fake()->image('lamp.jpg'),
    ]));

    $product = Product::where('name', 'Lamp')->firstOrFail();

    expect($product->image_path)->toStartWith('products/');
    Storage::disk('public')->assertExists($product->image_path);
});

it('troca a imagem na edição e apaga a antiga', function () {
    Storage::disk('public')->put('products/old.jpg', 'old');
    $product = Product::factory()->create(['image_path' => 'products/old.jpg']);

    // o formulário envia edição como POST com _method=PUT
    $this->actingAs(admin())->post(route('products.update', $product), productPayload([
        '_method' => 'PUT',
        'image' => UploadedFile::fake()->image('new.png'),
    ]));

    $product->refresh();

    Storage::disk('public')->assertMissing('products/old.jpg');
    Storage::disk('public')->assertExists($product->image_path);
    expect($product->image_path)->not->toBe('products/old.jpg');
});

it('bloqueia cliente comum de enviar imagem e não grava arquivo', function () {
    $this->actingAs(customer())->post(route('products.store'), productPayload([
        'image' => UploadedFile::fake()->image('lamp.jpg'),
    ]))->assertForbidden();

    expect(Storage::disk('public')->allFiles('products'))->toBeEmpty();
});

it('rejeita arquivo que não é imagem', function () {
    $this->actingAs(admin())->post(route('products.store'), productPayload([
        'image' => UploadedFile::fake()->create('notes.txt', 10),
    ]))->assertSessionHasErrors('image');
});

it('usa o placeholder local quando o produto não tem imagem', function () {
    $product = Product::factory()->create(['image_path' => null]);

    expect($product->image_url)->toEndWith('images/product-placeholder.svg');
});

it('monta a URL da imagem quando o produto tem imagem', function () {
    $product = Product::factory()->create(['image_path' => 'products/lamp.jpg']);

    expect($product->image_url)->toContain('products/lamp.jpg');
});

it('envia a URL da imagem em cada produto da listagem', function () {
    Product::factory()->create(['image_path' => null]);

    $this->actingAs(customer())
        ->get(route('products.index'))
        ->assertInertia(fn ($page) => $page->where('products.data.0.image_url', asset('images/product-placeholder.svg')));
});
