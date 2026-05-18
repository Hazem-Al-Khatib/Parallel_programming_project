<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User; // <-- لا تنسى استدعاء موديل المستخدم

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]
        );

        Product::create([
            'name' => 'Laptop AI Edition',
            'price' => 1200.00,
            'stock' => 10, 
        ]);
    }
}