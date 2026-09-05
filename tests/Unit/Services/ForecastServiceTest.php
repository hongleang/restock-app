<?php

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\ForecastService;

/**
 * Helper: create a product and back-date N sale movements of a fixed
 * quantity, spread one per day, ending "today". Keeps test setup
 * declarative instead of repeating boilerplate in every test.
 */
function makeProductWithDailySales(
    Shop $shop,
    int $currentStock,
    int $unitsPerDaySold,
    int $daysOfHistory,
    ?Supplier $supplier = null,
): Product {
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'supplier_id' => $supplier?->id,
        'current_stock' => $currentStock,
    ]);

    for ($i = 0; $i < $daysOfHistory; $i++) {
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'type' => StockMovementType::Sale,
            'quantity' => $unitsPerDaySold,
            'created_at' => now()->subDays($i),
        ]);
    }

    return $product;
}

beforeEach(function () {
    $this->forecast = new ForecastService;

    $this->shop = Shop::factory()->create();
});

it('calculates daily sales velocity from sale movements only', function () {
    $product = makeProductWithDailySales(
        shop: $this->shop,
        currentStock: 100,
        unitsPerDaySold: 5,
        daysOfHistory: 30,
    );

    // 30 days of 5 units/day sold = 150 total, over a 30-day window = 5.0/day
    expect($this->forecast->dailySalesVelocity($product, 30))->toBe(5.0);
});

it('ignores restocks and adjustments when calculating velocity', function () {
    $product = makeProductWithDailySales(
        shop: $this->shop,
        currentStock: 100,
        unitsPerDaySold: 4,
        daysOfHistory: 10,
    );

    // Add noise that must NOT affect the velocity calculation
    StockMovement::factory()->create([
        'product_id' => $product->id,
        'type' => StockMovementType::Restock,
        'quantity' => 500,
        'created_at' => now()->subDays(2),
    ]);

    StockMovement::factory()->create([
        'product_id' => $product->id,
        'type' => StockMovementType::Adjustment,
        'quantity' => -50,
        'created_at' => now()->subDays(1),
    ]);

    // Still 4.0/day — restock and adjustment rows must be excluded entirely
    expect($this->forecast->dailySalesVelocity($product, 10))->toBe(4.0);
});

it('ignores sales outside the trailing window', function () {
    $product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'current_stock' => 100,
    ]);

    // 10 units sold 60 days ago — outside a 30-day window, must not count
    StockMovement::factory()->create([
        'product_id' => $product->id,
        'type' => StockMovementType::Sale,
        'quantity' => 50,
        'created_at' => now()->subDays(60),
    ]);

    // 3 units/day for the last 30 days — this IS inside the window
    for ($i = 0; $i < 30; $i++) {
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'type' => StockMovementType::Sale,
            'quantity' => 3,
            'created_at' => now()->subDays($i),
        ]);
    }

    expect($this->forecast->dailySalesVelocity($product, 30))->toBe(3.0);
});

it('returns zero velocity for a product with no sales history', function () {
    $product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'current_stock' => 50,
    ]);

    expect($this->forecast->dailySalesVelocity($product))->toBe(0.0);
});

it('calculates days until stockout from current stock and velocity', function () {
    // 100 in stock, selling 5/day => should run out in 20 days
    $product = makeProductWithDailySales(
        shop: $this->shop,
        currentStock: 100,
        unitsPerDaySold: 5,
        daysOfHistory: 30,
    );

    expect($this->forecast->daysUntilStockout($product, 30))->toBe(20.0);
});

it('returns null days until stockout when velocity is zero', function () {
    $product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'current_stock' => 100,
    ]);

    // No sales at all — velocity is 0, so "days until stockout" is undefined,
    // not infinite. Must return null, not a divide-by-zero error or INF.
    expect($this->forecast->daysUntilStockout($product))->toBeNull();
});

it('flags a product as needing reorder when stockout is within lead time', function () {
    $supplier = Supplier::factory()->create([
        'shop_id' => $this->shop->id,
        'lead_time_days' => 7,
    ]);

    // 30 in stock, selling 5/day => 6 days until stockout, lead time is 7 days
    // 6 <= 7, so this SHOULD be flagged for reorder
    $product = makeProductWithDailySales(
        shop: $this->shop,
        currentStock: 30,
        unitsPerDaySold: 5,
        daysOfHistory: 30,
        supplier: $supplier,
    );

    expect($this->forecast->needsReorder($product))->toBeTrue();
});

it('does not flag a product when stockout is well beyond lead time', function () {
    $supplier = Supplier::factory()->create([
        'shop_id' => $this->shop->id,
        'lead_time_days' => 3,
    ]);

    // 100 in stock, selling 2/day => 50 days until stockout, lead time is 3 days
    // 50 > 3, so this should NOT be flagged
    $product = makeProductWithDailySales(
        shop: $this->shop,
        currentStock: 100,
        unitsPerDaySold: 2,
        daysOfHistory: 30,
        supplier: $supplier,
    );

    expect($this->forecast->needsReorder($product))->toBeFalse();
});

it('reorder list only includes products needing reorder, sorted by urgency', function () {
    $supplier = Supplier::factory()->create([
        'shop_id' => $this->shop->id,
        'lead_time_days' => 10,
    ]);

    // Urgent: ~3 days until stockout (10 / 3.33)
    $urgent = makeProductWithDailySales($this->shop, 10, 3, 30, $supplier)->fresh();

    // Less urgent but still flagged: 7 days until stockout (35 / 5)
    $lessUrgent = makeProductWithDailySales($this->shop, 35, 5, 30, $supplier)->fresh();

    // Not urgent: far beyond lead time, should be excluded entirely
    makeProductWithDailySales($this->shop, 500, 2, 30, $supplier);

    $result = $this->forecast->reorderList($this->shop->id);

    expect($result)->toHaveCount(2)
        ->and($result->first()['product']->id)->toBe($urgent->id)
        ->and($result->last()['product']->id)->toBe($lessUrgent->id);
});
