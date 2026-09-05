<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->word(),
            'note' => $this->faker->word(),
            'quantity' => $this->faker->randomNumber(),
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(function (StockMovement $movement) {
            $quantity = StockMovementType::getQuantity($movement->type->value, $movement->quantity);
            $movement->update([
                'quantity' => $quantity,
            ]);
        });
    }
}
