<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ProductSeeder extends Seeder
{
    /**
     * Nomes de produto de catálogo, indexados pelo nome (em inglês) da categoria.
     *
     * @var array<string, array<int, string>>
     */
    private const NAMES = [
        'Electronics' => ['Wireless Earbuds', 'Phone Stand', '4K Smart TV', 'Portable Charger', '10-inch Tablet', 'Laptop Sleeve', 'Smartwatch', 'Bluetooth Speaker'],
        'Clothing & Accessories' => ['Cotton T-Shirt', 'Denim Jeans', 'Summer Dress', 'Leather Belt', 'Canvas Sneakers', 'Sunglasses', 'Wool Scarf', 'Crossbody Bag'],
        'Beauty & Personal Care' => ['Hydrating Face Cream', 'Matte Lipstick', 'Argan Oil Shampoo', 'Sunscreen SPF 50', 'Electric Shaver', 'Eau de Parfum', 'Body Lotion'],
        'Home & Decor' => ['Ceramic Vase', 'Linen Bedsheet Set', 'LED Desk Lamp', 'Bamboo Cutting Board', 'Wall Clock', 'Throw Pillow'],
        'Sports & Leisure' => ['Yoga Mat', 'Adjustable Dumbbells', 'Camping Tent', 'Running Shoes', 'Water Bottle', 'Mountain Bike'],
        'Books, Movies & Music' => ['Mystery Novel', 'Cookbook', 'Classic Vinyl Record', 'Documentary DVD', 'Acoustic Guitar', 'Science Fiction Box Set'],
        'Toys & Games' => ['Building Blocks Set', 'Board Game', '1000-Piece Puzzle', 'Plush Bear', 'Remote Control Car'],
        'Food & Beverages' => ['Dark Chocolate Bar', 'Organic Granola', 'Cold Brew Coffee', 'Green Tea Box', 'Mixed Nuts', 'Artisan Olive Oil'],
        'Automotive' => ['Seat Covers', 'Car Floor Mats', 'Microfiber Towel Set', 'Engine Oil 5W-30', 'Dash Camera'],
        'Health & Wellness' => ['Multivitamin', 'Omega-3 Capsules', 'First Aid Kit', 'Digital Thermometer', 'Resistance Bands'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Category::all() as $category) {
            $names = Arr::shuffle(self::NAMES[$category->name] ?? []);

            foreach (array_slice($names, 0, 3) as $name) {
                Product::factory()->for($category)->create(['name' => $name]);
            }
        }
    }
}
