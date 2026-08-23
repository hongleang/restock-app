<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StockMovementSeeder extends Seeder
{
    /**
     * @var list<string|null>
     */
    private const SALE_NOTES = [
        null, null, null, null,
        'Sold to walk-in customer',
        'Online order fulfilled',
        'Bulk order for local cafe',
        'POS sale',
    ];

    /**
     * @var list<string>
     */
    private const RESTOCK_NOTES = [
        'Received shipment from supplier',
        'Purchase order delivered',
        'Weekly restock delivery',
        'Emergency top-up order',
        'Restocked from warehouse',
    ];

    /**
     * @var list<string>
     */
    private const ADJUSTMENT_NOTES = [
        'Stock count correction after audit',
        'Damaged items written off',
        'Expired stock removed',
        'Inventory discrepancy correction',
        'Theft/loss adjustment',
    ];

    public function run(): void
    {
        Product::with('shop.user')->chunk(50, function ($products) {
            foreach ($products as $product) {
                $this->seedHistoryFor($product);
            }
        });
    }

    private function seedHistoryFor(Product $product): void
    {
        $owner = $product->shop?->user;
        $timestamp = Carbon::now()->subMonths(6)->subDays(fake()->numberBetween(0, 20));

        $this->record($product, StockMovementType::Restock, 'Initial stock intake', $owner, $timestamp);

        $movementCount = fake()->numberBetween(20, 60);

        for ($i = 0; $i < $movementCount; $i++) {
            $timestamp = $timestamp->copy()->addHours(fake()->numberBetween(3, 60));

            if ($timestamp->greaterThan(Carbon::now())) {
                break;
            }

            $type = fake()->randomElement([
                ...array_fill(0, 7, StockMovementType::Sale),
                ...array_fill(0, 2, StockMovementType::Restock),
                ...array_fill(0, 1, StockMovementType::Adjustment),
            ]);

            $note = match ($type) {
                StockMovementType::Sale => fake()->randomElement(self::SALE_NOTES),
                StockMovementType::Restock => fake()->randomElement(self::RESTOCK_NOTES),
                StockMovementType::Adjustment => fake()->randomElement(self::ADJUSTMENT_NOTES),
            };

            $recordedBy = fake()->boolean(80) ? $owner : null;

            $this->record($product, $type, $note, $recordedBy, $timestamp);
        }
    }

    private function record(
        Product $product,
        StockMovementType $type,
        ?string $note,
        ?User $user,
        Carbon $timestamp,
    ): void {
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'type' => $type,
            'note' => $note,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
