<?php

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('viewing the dashboard', function () {
    it('redirects guests to the login page', function () {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    });

    it('forbids a user without a shop from viewing the dashboard', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    });

    it('allows an authenticated user with a shop to visit the dashboard', function () {
        [$owner] = createUserWithShop();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
    });
});

describe('the reorder list', function () {
    it('includes a product that will run out of stock before the supplier can restock it', function () {
        [$owner, $shop] = createUserWithShop();
        $supplier = Supplier::factory()->create(['shop_id' => $shop->id, 'lead_time_days' => 14]);
        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'supplier_id' => $supplier->id,
            'current_stock' => 20,
        ]);
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Sale,
            'quantity' => -300,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('reorderList', 1)
                ->where('reorderList.0.product.id', $product->id)
                ->where('reorderList.0.urgency', 'urgent')
            );
    });

    it('excludes a product with enough stock to outlast the supplier lead time', function () {
        [$owner, $shop] = createUserWithShop();
        $supplier = Supplier::factory()->create(['shop_id' => $shop->id, 'lead_time_days' => 14]);
        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'supplier_id' => $supplier->id,
            'current_stock' => 1000,
        ]);
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Sale,
            'quantity' => -300,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('reorderList', 0));
    });

    it('excludes a product with no sales history, regardless of stock level', function () {
        [$owner, $shop] = createUserWithShop();
        $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
        Product::factory()->create([
            'shop_id' => $shop->id,
            'supplier_id' => $supplier->id,
            'current_stock' => 1,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('reorderList', 0));
    });

    it('excludes a product with no assigned supplier, regardless of stock level', function () {
        [$owner, $shop, $product] = createUserWithProduct();
        $product->update(['current_stock' => 1, 'supplier_id' => null]);
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Sale,
            'quantity' => -300,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('reorderList', 0));
    });

    it('only includes products from the authenticated user\'s own shop', function () {
        [$owner, $shop] = createUserWithShop();
        $supplier = Supplier::factory()->create(['shop_id' => $shop->id, 'lead_time_days' => 14]);
        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'supplier_id' => $supplier->id,
            'current_stock' => 20,
        ]);
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Sale,
            'quantity' => -300,
        ]);

        [$other, $otherShop] = createUserWithShop();
        $otherSupplier = Supplier::factory()->create(['shop_id' => $otherShop->id, 'lead_time_days' => 14]);
        $otherProduct = Product::factory()->create([
            'shop_id' => $otherShop->id,
            'supplier_id' => $otherSupplier->id,
            'current_stock' => 20,
        ]);
        StockMovement::factory()->create([
            'product_id' => $otherProduct->id,
            'user_id' => $other->id,
            'type' => StockMovementType::Sale,
            'quantity' => -300,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('reorderList', 1)
                ->where('reorderList.0.product.id', $product->id)
            );
    });

    it('classifies urgency based on days until stockout', function (int $currentStock, int $dailySales, string $expectedUrgency) {
        [$owner, $shop] = createUserWithShop();
        $supplier = Supplier::factory()->create(['shop_id' => $shop->id, 'lead_time_days' => 30]);
        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'supplier_id' => $supplier->id,
            'current_stock' => $currentStock,
        ]);
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Sale,
            'quantity' => -($dailySales * 30),
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('reorderList', 1)
                ->where('reorderList.0.urgency', $expectedUrgency)
            );
    })->with([
        'urgent: under 5 days of stock left' => [20, 10, 'urgent'],
        'less urgent: between 5 and 7 days of stock left' => [60, 10, 'less_urgent'],
        'not urgent: more than 7 days left, but still within the lead time' => [200, 10, 'not_urgent'],
    ]);
});
