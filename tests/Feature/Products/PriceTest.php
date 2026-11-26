<?php

use App\Models\Product;
use Illuminate\Support\Facades\DB;

it('guarda o preço do produto como numeric no banco', function () {
    $tipo = DB::selectOne("select data_type from information_schema.columns where table_name = 'products' and column_name = 'price'")->data_type;

    expect($tipo)->toBe('numeric');
});

it('arredonda o preço para 2 casas decimais ao gravar', function () {
    $product = Product::factory()->create(['price' => 19.999]);

    expect($product->fresh()->price)->toBe(20.0);
});

it('devolve o preço como float', function () {
    $product = Product::factory()->create(['price' => 19.9]);

    expect($product->fresh()->price)->toBe(19.9)
        ->and($product->fresh()->price)->toBeFloat();
});
