<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Shop;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'sku' => $this->faker->word(),
            'barcode' => $this->faker->word(),
            'category' => $this->faker->word(),
            'cost_price' => $this->faker->randomFloat(2, 5, 100),
            'sell_price' => fn ($attributes) => $attributes['cost_price'] * $this->faker->randomElements([1.1, 1.15, 1.2, 1.5])[0],
            'current_stock' => $this->faker->randomNumber(),
            'reorder_point' => $this->faker->randomNumber(),
            'reorder_qty' => $this->faker->randomNumber(),

            'shop_id' => Shop::factory(),
            'supplier_id' => Supplier::factory(),
        ];
    }
}
