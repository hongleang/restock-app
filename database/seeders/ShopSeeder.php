<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const NAMES = [
        'Green Valley Grocers',
        'Corner Market',
        'Sunrise Convenience',
    ];

    public function run(): void
    {
        $owner = User::first();

        if (! $owner) {
            return;
        }

        foreach (self::NAMES as $name) {
            Shop::factory()->create([
                'name' => $name,
                'user_id' => $owner->id,
            ]);
        }
    }
}
