<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Shop::all()->each(function (Shop $shop) {
            Supplier::factory()
                ->count(fake()->numberBetween(3, 5))
                ->state(fn () => [
                    'lead_time_days' => fake()->numberBetween(1, 14),
                ])
                ->create([
                    'shop_id' => $shop->id,
                ]);
        });
    }
}
