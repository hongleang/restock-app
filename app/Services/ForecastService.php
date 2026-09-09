<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Collection;

class ForecastService
{
    public function __construct() {}

    public function dailySalesVelocity(Product $product, ?int $days = 30): float
    {
        $quantity = $product
            ->movements()
            ->where('type', StockMovementType::Sale->value)
            ->whereDate('created_at', '>=', now()->subDays($days))
            ->sum('quantity');

        return abs((float) $quantity / (float) $days);
    }

    public function daysUntilStockout(Product $product): ?float
    {
        $velocity = $this->dailySalesVelocity($product);
        $currentStock = $product->current_stock;

        if ($velocity <= 0.0) {
            return null;
        }

        return abs((float) $currentStock / $velocity);
    }

    public function needsReorder(Product $product): bool
    {
        if (! $product->supplier) {
            return false;
        }

        $daysUntilStockout = $this->daysUntilStockout($product);

        if ($daysUntilStockout === null) {
            return false;
        }

        return $daysUntilStockout <= $product->supplier->lead_time_days;
    }

    /*
    * returns a collection of products that need reorder
    */
    public function reorderList(int $shopId): Collection
    {
        $shop = Shop::find($shopId);

        if (! $shop) {
            return collect();
        }

        $products = $shop->products()->with('supplier')->get();

        return $products
            ->map(function ($product) {
                return [
                    'product' => $product,
                    'days_until_stockout' => $this->daysUntilStockout($product)
                ];
            })
            ->filter(fn ($item) => $this->needsReorder($item['product']))
            ->sortBy('days_until_stockout')
            ->values();
    }
}
