<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'admin@restock.com',
        ])->create();

        $this->call([
            ShopSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
