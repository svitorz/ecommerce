<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Factories\OrdersProductsFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::factory()->create([
            'name' => 'Vitor Souza',
            'email' => 'vitor@example.com',
            'password' => Hash::make('12345678'),
        ]);

        User::factory()->create([
            'name' => 'Cliente Teste',
            'email' => 'test@example.com',
            'password' => Hash::make('12345678'),
        ]);

        User::factory()->admin()->create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => Hash::make('12345678'),
        ]);

        $this->call([CategorySeeder::class, ProductSeeder::class]);

        $users = User::all();
        $products = Product::all();

        Order::factory(10)->recycle($users)->create()->each(function (Order $order) use ($products) {
            OrdersProductsFactory::new()->count(3)->recycle($products)->create(['order_id' => $order->id]);
            Payment::factory()->create(['order_id' => $order->id]);
        });

        Address::factory()->create([
            'country' => 'Brasil',
            'state' => 'São Paulo',
            'city' => 'Rio Preto',
            'street' => 'Rua Exemplo',
            'zip_code' => '12335-000',
            'number' => 123,
            'user_id' => $customer->id,
        ]);
    }
}
