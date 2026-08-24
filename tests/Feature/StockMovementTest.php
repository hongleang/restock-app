<?php

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('stock-movements.index'))->assertRedirect(route('login'));
});

test('users without a shop cannot view stock movements', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('stock-movements.index'))
        ->assertForbidden();
});

test('stock movements index only shows movements for the authenticated users own products', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $movement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Restock,
    ]);

    $other = User::factory()->create();
    $otherShop = Shop::factory()->create(['user_id' => $other->id]);
    $otherProduct = Product::factory()->create(['shop_id' => $otherShop->id]);
    StockMovement::factory()->create([
        'product_id' => $otherProduct->id,
        'user_id' => $other->id,
        'type' => StockMovementType::Sale,
    ]);

    $this->actingAs($owner)
        ->get(route('stock-movements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('stock-movements/Index')
            ->has('movements.data', 1)
            ->where('movements.data.0.id', $movement->id)
        );
});

test('a stock movement can be recorded for the users own product', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($owner)->post(route('stock-movements.store'), [
        'product_id' => $product->id,
        'type' => StockMovementType::Restock->value,
        'note' => 'Received shipment from supplier',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('stock-movements.index'));

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => StockMovementType::Restock->value,
        'note' => 'Received shipment from supplier',
    ]);
});

test('the acting user is recorded as the mover, regardless of input', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $impersonated = User::factory()->create();

    $this->actingAs($owner)->post(route('stock-movements.store'), [
        'product_id' => $product->id,
        'user_id' => $impersonated->id,
        'type' => StockMovementType::Sale->value,
    ]);

    $movement = StockMovement::query()->latest()->first();

    expect($movement->user_id)->toBe($owner->id);
});

test('a stock movement cannot be recorded for a product outside the users shops', function () {
    $owner = User::factory()->create();
    Shop::factory()->create(['user_id' => $owner->id]);

    $other = User::factory()->create();
    $otherShop = Shop::factory()->create(['user_id' => $other->id]);
    $otherProduct = Product::factory()->create(['shop_id' => $otherShop->id]);

    $this->actingAs($owner)
        ->post(route('stock-movements.store'), [
            'product_id' => $otherProduct->id,
            'type' => StockMovementType::Sale->value,
        ])
        ->assertSessionHasErrors('product_id');

    $this->assertDatabaseMissing('stock_movements', ['product_id' => $otherProduct->id]);
});

test('the type must be a valid stock movement type', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $this->actingAs($owner)
        ->post(route('stock-movements.store'), [
            'product_id' => $product->id,
            'type' => 'not-a-real-type',
        ])
        ->assertSessionHasErrors('type');
});

test('a movement owner can update the type and note', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $movement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Sale,
        'note' => 'Original note',
    ]);

    $response = $this->actingAs($owner)->put(route('stock-movements.update', $movement), [
        'type' => StockMovementType::Adjustment->value,
        'note' => 'Corrected note',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('stock-movements.index'));

    $movement->refresh();
    expect($movement->type)->toBe(StockMovementType::Adjustment);
    expect($movement->note)->toBe('Corrected note');
});

test('updating a movement cannot reassign it to a different product', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $otherProduct = Product::factory()->create(['shop_id' => $shop->id]);
    $movement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Sale,
    ]);

    $this->actingAs($owner)->put(route('stock-movements.update', $movement), [
        'product_id' => $otherProduct->id,
        'type' => StockMovementType::Adjustment->value,
    ]);

    expect($movement->fresh()->product_id)->toBe($product->id);
});

test('a user cannot update another users stock movement', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $movement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Sale,
        'note' => 'Original note',
    ]);

    $intruder = User::factory()->create();
    Shop::factory()->create(['user_id' => $intruder->id]);

    $this->actingAs($intruder)
        ->put(route('stock-movements.update', $movement), [
            'type' => StockMovementType::Adjustment->value,
            'note' => 'Hacked note',
        ])
        ->assertForbidden();

    expect($movement->fresh()->note)->toBe('Original note');
});

test('a movement owner can delete their stock movement', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $movement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Sale,
    ]);

    $this->actingAs($owner)
        ->delete(route('stock-movements.destroy', $movement))
        ->assertRedirect(route('stock-movements.index'));

    $this->assertModelMissing($movement);
});

test('a user cannot delete another users stock movement', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $movement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Sale,
    ]);

    $intruder = User::factory()->create();
    Shop::factory()->create(['user_id' => $intruder->id]);

    $this->actingAs($intruder)
        ->delete(route('stock-movements.destroy', $movement))
        ->assertForbidden();

    $this->assertModelExists($movement);
});

test('movements can be filtered by product', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $productA = Product::factory()->create(['shop_id' => $shop->id]);
    $productB = Product::factory()->create(['shop_id' => $shop->id]);
    $match = StockMovement::factory()->create([
        'product_id' => $productA->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Sale,
    ]);
    StockMovement::factory()->create([
        'product_id' => $productB->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Sale,
    ]);

    $this->actingAs($owner)
        ->get(route('stock-movements.index', ['product_id' => $productA->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('movements.data', 1)
            ->where('movements.data.0.id', $match->id)
        );
});

test('movements can be filtered by type', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $match = StockMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Adjustment,
    ]);
    StockMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Sale,
    ]);

    $this->actingAs($owner)
        ->get(route('stock-movements.index', ['type' => StockMovementType::Adjustment->value]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('movements.data', 1)
            ->where('movements.data.0.id', $match->id)
            ->where('filters.type', StockMovementType::Adjustment->value)
        );
});

test('movements can be filtered by a date range', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $inRange = StockMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Sale,
        'created_at' => '2026-06-15',
    ]);
    StockMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Sale,
        'created_at' => '2026-01-01',
    ]);

    $this->actingAs($owner)
        ->get(route('stock-movements.index', ['from' => '2026-06-01', 'to' => '2026-06-30']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('movements.data', 1)
            ->where('movements.data.0.id', $inRange->id)
        );
});

test('an invalid date filter is ignored rather than erroring', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    StockMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Sale,
    ]);

    $this->actingAs($owner)
        ->get(route('stock-movements.index', ['from' => 'not-a-date']))
        ->assertOk();
});

test('movements are paginated', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    StockMovement::factory()->count(20)->create([
        'product_id' => $product->id,
        'user_id' => $owner->id,
        'type' => StockMovementType::Sale,
    ]);

    $this->actingAs($owner)
        ->get(route('stock-movements.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('movements.data', 15)
            ->where('movements.next_page_url', fn ($url) => $url !== null)
        );
});
