<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::create([
            'product_name' => 'Laptop',
            'quantity_in_stock' => 10,
            'price_per_item' => 55000,
        ]);

        Product::create([
            'product_name' => 'Wireless Mouse',
            'quantity_in_stock' => 25,
            'price_per_item' => 850,
        ]);

        Product::create([
            'product_name' => 'Keyboard',
            'quantity_in_stock' => 15,
            'price_per_item' => 1500,
        ]);
    }
}