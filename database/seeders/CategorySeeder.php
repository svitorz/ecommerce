<?php

namespace Database\Seeders;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        (new CategoryFactory)->create(['name' => 'Electronics', 'description' => 'Technology products, such as smartphones, laptops, tablets, TVs, accessories like headphones, chargers and smart devices.']);
        (new CategoryFactory)->create(['name' => 'Clothing & Accessories', 'description' => 'Clothing for men, women and children, including t-shirts, pants, dresses, shoes, bags, watches, sunglasses and other fashion accessories.']);
        (new CategoryFactory)->create(['name' => 'Beauty & Personal Care', 'description' => 'Cosmetics, makeup, skin, hair and personal hygiene products, such as perfumes, moisturizers, shampoos and razors.']);
        (new CategoryFactory)->create(['name' => 'Home & Decor', 'description' => 'Furniture, decorative items, kitchen supplies, organization, lighting, bed, table and bath linens.']);
        (new CategoryFactory)->create(['name' => 'Sports & Leisure', 'description' => 'Sports equipment, fitness clothing, outdoor accessories such as camping gear, bicycles and wellness products.']);
        (new CategoryFactory)->create(['name' => 'Books, Movies & Music', 'description' => 'Physical and digital books, DVDs, Blu-rays, vinyl records, musical instruments and other entertainment materials.']);
        (new CategoryFactory)->create(['name' => 'Toys & Games', 'description' => 'Children\'s toys, board games, puzzles, video games and console accessories.']);
        (new CategoryFactory)->create(['name' => 'Food & Beverages', 'description' => 'Food products, such as gourmet items, snacks, healthy food, alcoholic and non-alcoholic beverages.']);
        (new CategoryFactory)->create(['name' => 'Automotive', 'description' => 'Spare parts and vehicle accessories, such as seat covers, floor mats, maintenance products and car cleaning supplies.']);
        (new CategoryFactory)->create(['name' => 'Health & Wellness', 'description' => 'Products such as dietary supplements, vitamins, first aid items, medical and physiotherapy equipment.']);
    }
}
