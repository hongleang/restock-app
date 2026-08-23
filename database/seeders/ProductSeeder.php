<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Shop;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const CATALOG = [
        'Beverages' => ['Sparkling Water 500ml', 'Orange Juice 1L', 'Cola 330ml Can', 'Iced Tea 500ml', 'Coffee Beans 250g'],
        'Dairy' => ['Whole Milk 1L', 'Cheddar Cheese Block', 'Greek Yogurt 500g', 'Butter 250g', 'Free Range Eggs (12)'],
        'Bakery' => ['Sourdough Loaf', 'Croissant', 'Bagel (6-pack)', 'Blueberry Muffin', 'White Sandwich Bread'],
        'Snacks' => ['Potato Chips', 'Chocolate Bar', 'Mixed Nuts 200g', 'Pretzels', 'Granola Bar (6-pack)'],
        'Household' => ['Dish Soap', 'Paper Towels (6-pack)', 'Laundry Detergent 2L', 'Trash Bags (30-pack)', 'All-Purpose Cleaner'],
        'Personal Care' => ['Shampoo 400ml', 'Toothpaste', 'Bar Soap (3-pack)', 'Hand Sanitizer 250ml', 'Deodorant'],
        'Stationery' => ['Notebook A5', 'Ballpoint Pens (10-pack)', 'Printer Paper (500 sheets)', 'Sticky Notes', 'Highlighters (5-pack)'],
        'Frozen Foods' => ['Frozen Pizza', 'Ice Cream Tub 1L', 'Frozen Mixed Vegetables', 'Frozen Chicken Nuggets', 'Frozen Waffles'],
    ];

    public function run(): void
    {
        Shop::all()->each(function (Shop $shop) {
            $suppliers = Supplier::where('shop_id', $shop->id)->get();

            foreach (self::CATALOG as $category => $names) {
                foreach ($names as $name) {
                    $costPrice = fake()->randomFloat(2, 1, 40);
                    $margin = fake()->randomElement([1.15, 1.2, 1.3, 1.4, 1.5, 1.6]);

                    Product::factory()->create([
                        'name' => $name,
                        'sku' => Str::upper(Str::substr($category, 0, 3)).'-'.fake()->unique()->numberBetween(1000, 9999),
                        'barcode' => fake()->ean13(),
                        'category' => $category,
                        'cost_price' => $costPrice,
                        'sell_price' => round($costPrice * $margin, 2),
                        'current_stock' => fake()->numberBetween(0, 150),
                        'reorder_point' => fake()->numberBetween(5, 25),
                        'reorder_qty' => fake()->numberBetween(20, 80),
                        'shop_id' => $shop->id,
                        'supplier_id' => $suppliers->isNotEmpty() ? $suppliers->random()->id : null,
                    ]);
                }
            }
        });
    }
}
