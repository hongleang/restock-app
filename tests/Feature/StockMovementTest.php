<?php

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

describe('viewing stock movements', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('stock-movements.index'))->assertRedirect(route('login'));
    });

    it('forbids a user without a shop from viewing stock movements', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('stock-movements.index'))
            ->assertForbidden();
    });

    it('only shows movements for the authenticated user\'s own products', function () {
        [$owner, $shop, $product] = createUserWithProduct();
        $movement = StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Restock,
        ]);

        [$other, $otherShop, $otherProduct] = createUserWithProduct();
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
});

describe('recording stock movements', function () {
    it('records a stock movement for the user\'s own product', function () {
        [$owner, $shop, $product] = createUserWithProduct();

        $response = $this->actingAs($owner)->post(route('stock-movements.store'), [
            'product_id' => $product->id,
            'type' => StockMovementType::Restock->value,
            'note' => 'Received shipment from supplier',
            'quantity' => 10,
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

    it('applies the type\'s sign convention to the recorded quantity', function (StockMovementType $type, int $input, int $expected) {
        [$owner, $shop, $product] = createUserWithProduct();

        $this->actingAs($owner)->post(route('stock-movements.store'), [
            'product_id' => $product->id,
            'type' => $type->value,
            'quantity' => $input,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => $type->value,
            'quantity' => $expected,
        ]);
    })->with([
        'restock always increases stock, even given a negative quantity' => [StockMovementType::Restock, -10, 10],
        'sale always decreases stock, even given a positive quantity' => [StockMovementType::Sale, 10, -10],
        'adjustment keeps a negative quantity as-is' => [StockMovementType::Adjustment, -10, -10],
        'adjustment keeps a positive quantity as-is' => [StockMovementType::Adjustment, 10, 10],
    ]);

    it('records the acting user as the mover, regardless of input', function () {
        [$owner, $shop, $product] = createUserWithProduct();
        $impersonated = User::factory()->create();

        $this->actingAs($owner)->post(route('stock-movements.store'), [
            'product_id' => $product->id,
            'user_id' => $impersonated->id,
            'type' => StockMovementType::Sale->value,
            'quantity' => 10,
        ]);

        $movement = StockMovement::query()->latest()->first();

        expect($movement->user_id)->toBe($owner->id);
    });

    it('prevents recording a stock movement for a product outside the user\'s shops', function () {
        [$owner] = createUserWithShop();
        [$other, $otherShop, $otherProduct] = createUserWithProduct();

        $this->actingAs($owner)
            ->post(route('stock-movements.store'), [
                'product_id' => $otherProduct->id,
                'type' => StockMovementType::Sale->value,
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseMissing('stock_movements', ['product_id' => $otherProduct->id]);
    });

    it('rejects an invalid stock movement type', function () {
        [$owner, $shop, $product] = createUserWithProduct();

        $this->actingAs($owner)
            ->post(route('stock-movements.store'), [
                'product_id' => $product->id,
                'type' => 'not-a-real-type',
            ])
            ->assertSessionHasErrors('type');
    });
});

describe('updating stock movements', function () {
    it('allows the owner to update a stock movement', function () {
        [$owner, $shop, $product] = createUserWithProduct();
        $movement = StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Sale,
            'note' => 'Original note',
        ]);

        $response = $this->actingAs($owner)->put(route('stock-movements.update', $movement), [
            'type' => StockMovementType::Adjustment->value,
            'note' => 'Corrected note',
            'quantity' => 10,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('stock-movements.index'));

        $movement->refresh();
        expect($movement->type)->toBe(StockMovementType::Adjustment);
        expect($movement->note)->toBe('Corrected note');
        expect($movement->quantity)->toBe(10);
    });

    it('re-applies the type\'s sign convention to quantity when updating', function (StockMovementType $type, int $input, int $expected) {
        [$owner, $shop, $product] = createUserWithProduct();
        $movement = StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Sale,
        ]);

        $this->actingAs($owner)->put(route('stock-movements.update', $movement), [
            'type' => $type->value,
            'quantity' => $input,
        ]);

        expect($movement->fresh()->quantity)->toBe($expected);
    })->with([
        'restock always increases stock, even given a negative quantity' => [StockMovementType::Restock, -10, 10],
        'sale always decreases stock, even given a positive quantity' => [StockMovementType::Sale, 10, -10],
        'adjustment keeps a negative quantity as-is' => [StockMovementType::Adjustment, -10, -10],
        'adjustment keeps a positive quantity as-is' => [StockMovementType::Adjustment, 10, 10],
    ]);

    it('prevents reassigning a movement to a different product when updating', function () {
        [$owner, $shop, $product] = createUserWithProduct();
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

    it('prevents a user from updating another user\'s stock movement', function () {
        [$owner, $shop, $product] = createUserWithProduct();
        $movement = StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Sale,
            'note' => 'Original note',
        ]);

        [$intruder] = createUserWithShop();

        $this->actingAs($intruder)
            ->put(route('stock-movements.update', $movement), [
                'type' => StockMovementType::Adjustment->value,
                'note' => 'Hacked note',
            ])
            ->assertForbidden();

        expect($movement->fresh()->note)->toBe('Original note');
    });
});

describe('deleting stock movements', function () {
    it('allows the owner to delete their stock movement', function () {
        [$owner, $shop, $product] = createUserWithProduct();
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

    it('prevents a user from deleting another user\'s stock movement', function () {
        [$owner, $shop, $product] = createUserWithProduct();
        $movement = StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Sale,
        ]);

        [$intruder] = createUserWithShop();

        $this->actingAs($intruder)
            ->delete(route('stock-movements.destroy', $movement))
            ->assertForbidden();

        $this->assertModelExists($movement);
    });
});

describe('filtering and pagination', function () {
    it('filters movements by product', function () {
        [$owner, $shop, $productA] = createUserWithProduct();
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

    it('filters movements by type', function () {
        [$owner, $shop, $product] = createUserWithProduct();
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

    it('filters movements by a date range', function () {
        [$owner, $shop, $product] = createUserWithProduct();

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

    it('ignores an invalid date filter rather than erroring', function () {
        [$owner, $shop, $product] = createUserWithProduct();
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Sale,
        ]);

        $this->actingAs($owner)
            ->get(route('stock-movements.index', ['from' => 'not-a-date']))
            ->assertOk();
    });

    it('paginates movements', function () {
        [$owner, $shop, $product] = createUserWithProduct();
        StockMovement::factory()->count(20)->create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Sale,
        ]);

        $this->actingAs($owner)
            ->get(route('stock-movements.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('movements.data', 15)
                ->where('movements.links.next', fn ($url) => $url !== null)
            );
    });
});

describe('importing stock movements from CSV', function () {
    it('prevents guests from importing stock movements', function () {
        $this->post(route('stock-movements.import'))->assertRedirect(route('login'));
    });

    it('imports a csv of stock movements', function () {
        [$owner, $shop] = createUserWithShop();
        $productA = Product::factory()->create(['shop_id' => $shop->id, 'sku' => 'BEV-1']);
        $productB = Product::factory()->create(['shop_id' => $shop->id, 'sku' => 'SNK-1']);

        $csv = <<<'CSV'
        sku,type,quantity,note
        BEV-1,sale,1,Sold to walk-in customer
        SNK-1,restock,1,Received shipment
        CSV;

        $file = UploadedFile::fake()->createWithContent('movements.csv', $csv);

        $response = $this->actingAs($owner)->post(route('stock-movements.import'), [
            'shop_id' => $shop->id,
            'file' => $file,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('stock-movements.index'));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $productA->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Sale->value,
            'note' => 'Sold to walk-in customer',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $productB->id,
            'user_id' => $owner->id,
            'type' => StockMovementType::Restock->value,
            'note' => 'Received shipment',
        ]);
    });

    it('scopes csv import to the selected shop and rejects another user\'s shop', function () {
        [$owner] = createUserWithShop();
        [$other, $otherShop] = createUserWithShop();

        $file = UploadedFile::fake()->createWithContent('movements.csv', "sku,type,quantity\nANY,sale,1");

        $this->actingAs($owner)
            ->post(route('stock-movements.import'), [
                'shop_id' => $otherShop->id,
                'file' => $file,
            ])
            ->assertSessionHasErrors('shop_id');
    });

    it('requires sku and type columns for csv import', function () {
        [$owner, $shop] = createUserWithShop();

        $file = UploadedFile::fake()->createWithContent('movements.csv', "name,quantity\nWidget,5");

        $this->actingAs($owner)
            ->post(route('stock-movements.import'), [
                'shop_id' => $shop->id,
                'file' => $file,
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('stock_movements', 0);
    });

    it('skips rows with an unknown sku or invalid type without failing the import', function () {
        [$owner, $shop] = createUserWithShop();
        $product = Product::factory()->create(['shop_id' => $shop->id, 'sku' => 'BEV-1']);

        $csv = <<<'CSV'
        sku,type,quantity
        BEV-1,sale,1
        UNKNOWN-SKU,sale,1
        BEV-1,not-a-type,1
        CSV;

        $file = UploadedFile::fake()->createWithContent('movements.csv', $csv);

        $this->actingAs($owner)
            ->post(route('stock-movements.import'), [
                'shop_id' => $shop->id,
                'file' => $file,
            ])
            ->assertSessionHasNoErrors();

        expect(StockMovement::query()->where('product_id', $product->id)->count())->toBe(1);
    });

    it('backdates imported movements using the optional date column', function () {
        [$owner, $shop] = createUserWithShop();
        $product = Product::factory()->create(['shop_id' => $shop->id, 'sku' => 'BEV-1']);

        $csv = <<<'CSV'
        sku,type,quantity,date
        BEV-1,restock,1,2026-01-15
        CSV;

        $file = UploadedFile::fake()->createWithContent('movements.csv', $csv);

        $this->actingAs($owner)->post(route('stock-movements.import'), [
            'shop_id' => $shop->id,
            'file' => $file,
        ]);

        $movement = StockMovement::query()->where('product_id', $product->id)->first();

        expect($movement->created_at->toDateString())->toBe('2026-01-15');
    });
});
