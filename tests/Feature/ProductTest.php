<?php

use App\Models\Product;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('viewing products', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('products.index'))->assertRedirect(route('login'));
    });

    it('forbids a user without a shop from viewing products', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertForbidden();
    });

    it('only shows the authenticated user\'s own products', function () {
        [$owner, $shop, $product] = createUserWithProduct();

        [$other, $otherShop] = createUserWithShop();
        Product::factory()->create(['shop_id' => $otherShop->id]);

        $this->actingAs($owner)
            ->get(route('products.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/Index')
                ->has('products.data', 1)
                ->where('products.data.0.id', $product->id)
            );
    });
});

describe('creating products', function () {
    it('creates a product for the user\'s own shop', function () {
        [$owner, $shop] = createUserWithShop();
        $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($owner)->post(route('products.store'), [
            'shop_id' => $shop->id,
            'supplier_id' => $supplier->id,
            'name' => 'Sparkling Water 500ml',
            'sku' => 'BEV-1001',
            'barcode' => '1234567890123',
            'category' => 'Beverages',
            'cost_price' => 1.50,
            'sell_price' => 2.99,
            'current_stock' => 40,
            'reorder_point' => 10,
            'reorder_qty' => 50,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'shop_id' => $shop->id,
            'sku' => 'BEV-1001',
            'name' => 'Sparkling Water 500ml',
        ]);
    });

    it('prevents creating a product for another user\'s shop', function () {
        [$owner] = createUserWithShop();
        [$other, $otherShop] = createUserWithShop();

        $response = $this->actingAs($owner)->post(route('products.store'), [
            'shop_id' => $otherShop->id,
            'name' => 'Sneaky Product',
            'sku' => 'SNK-1',
            'barcode' => '1234567890123',
            'category' => 'Snacks',
            'cost_price' => 1,
            'sell_price' => 2,
            'current_stock' => 1,
            'reorder_point' => 1,
            'reorder_qty' => 1,
        ]);

        $response->assertSessionHasErrors('shop_id');
        $this->assertDatabaseMissing('products', ['sku' => 'SNK-1']);
    });

    it('requires the sku to be unique within the same shop', function () {
        [$owner, $shop] = createUserWithShop();
        Product::factory()->create(['shop_id' => $shop->id, 'sku' => 'DUP-1']);

        $response = $this->actingAs($owner)->post(route('products.store'), [
            'shop_id' => $shop->id,
            'name' => 'Another Product',
            'sku' => 'DUP-1',
            'barcode' => '1234567890123',
            'category' => 'Snacks',
            'cost_price' => 1,
            'sell_price' => 2,
            'current_stock' => 1,
            'reorder_point' => 1,
            'reorder_qty' => 1,
        ]);

        $response->assertSessionHasErrors('sku');
    });

    it('allows the same sku to be reused across different shops', function () {
        [$owner, $shopA] = createUserWithShop();
        $shopB = Shop::factory()->create(['user_id' => $owner->id]);
        Product::factory()->create(['shop_id' => $shopA->id, 'sku' => 'SHARED-1']);

        $response = $this->actingAs($owner)->post(route('products.store'), [
            'shop_id' => $shopB->id,
            'name' => 'Another Product',
            'sku' => 'SHARED-1',
            'barcode' => '1234567890123',
            'category' => 'Snacks',
            'cost_price' => 1,
            'sell_price' => 2,
            'current_stock' => 1,
            'reorder_point' => 1,
            'reorder_qty' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['shop_id' => $shopB->id, 'sku' => 'SHARED-1']);
    });
});

describe('updating products', function () {
    it('allows the owner to update their product', function () {
        [$owner, $shop, $product] = createUserWithProduct();
        $product->update(['name' => 'Old name']);

        $response = $this->actingAs($owner)->put(route('products.update', $product), [
            'shop_id' => $shop->id,
            'supplier_id' => null,
            'name' => 'New name',
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'category' => $product->category,
            'cost_price' => 5,
            'sell_price' => 9,
            'current_stock' => 3,
            'reorder_point' => 1,
            'reorder_qty' => 5,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        expect($product->fresh()->name)->toBe('New name');
    });

    it('prevents a user from updating another user\'s product', function () {
        [$owner, $shop, $product] = createUserWithProduct();
        $product->update(['name' => 'Original name']);

        [$intruder] = createUserWithShop();

        $this->actingAs($intruder)
            ->put(route('products.update', $product), [
                'shop_id' => $shop->id,
                'name' => 'Hacked name',
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'category' => $product->category,
                'cost_price' => 5,
                'sell_price' => 9,
                'current_stock' => 3,
                'reorder_point' => 1,
                'reorder_qty' => 5,
            ])
            ->assertForbidden();

        expect($product->fresh()->name)->toBe('Original name');
    });
});

describe('deleting products', function () {
    it('allows the owner to delete their product', function () {
        [$owner, $shop, $product] = createUserWithProduct();

        $this->actingAs($owner)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertModelMissing($product);
    });

    it('prevents a user from deleting another user\'s product', function () {
        [$owner, $shop, $product] = createUserWithProduct();
        [$intruder] = createUserWithShop();

        $this->actingAs($intruder)
            ->delete(route('products.destroy', $product))
            ->assertForbidden();

        $this->assertModelExists($product);
    });
});

describe('filtering and pagination', function () {
    it('searches products by name or sku', function () {
        [$owner, $shop] = createUserWithShop();
        $match = Product::factory()->create(['shop_id' => $shop->id, 'name' => 'Sparkling Water', 'sku' => 'BEV-1']);
        Product::factory()->create(['shop_id' => $shop->id, 'name' => 'Chocolate Bar', 'sku' => 'SNK-1']);

        $this->actingAs($owner)
            ->get(route('products.index', ['search' => 'sparkling']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.id', $match->id)
                ->where('filters.search', 'sparkling')
            );

        $this->actingAs($owner)
            ->get(route('products.index', ['search' => 'BEV-1']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.id', $match->id)
            );
    });

    it('filters products by category and supplier', function () {
        [$owner, $shop] = createUserWithShop();
        $supplierA = Supplier::factory()->create(['shop_id' => $shop->id]);
        $supplierB = Supplier::factory()->create(['shop_id' => $shop->id]);

        $match = Product::factory()->create([
            'shop_id' => $shop->id,
            'category' => 'Beverages',
            'supplier_id' => $supplierA->id,
        ]);
        Product::factory()->create([
            'shop_id' => $shop->id,
            'category' => 'Snacks',
            'supplier_id' => $supplierB->id,
        ]);

        $this->actingAs($owner)
            ->get(route('products.index', ['category' => 'Beverages']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.id', $match->id)
            );

        $this->actingAs($owner)
            ->get(route('products.index', ['supplier' => $supplierA->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.id', $match->id)
            );
    });

    it('filters products to only show low stock items', function () {
        [$owner, $shop] = createUserWithShop();
        $low = Product::factory()->create(['shop_id' => $shop->id, 'current_stock' => 2, 'reorder_point' => 10]);
        Product::factory()->create(['shop_id' => $shop->id, 'current_stock' => 50, 'reorder_point' => 10]);

        $this->actingAs($owner)
            ->get(route('products.index', ['low_stock' => 1]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.id', $low->id)
                ->where('filters.low_stock', true)
            );
    });

    it('paginates products', function () {
        [$owner, $shop] = createUserWithShop();

        foreach (range(1, 20) as $i) {
            Product::factory()->create(['shop_id' => $shop->id, 'sku' => "SKU-{$i}"]);
        }

        $this->actingAs($owner)
            ->get(route('products.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 15)
                ->where('products.links.next', fn ($url) => $url !== null)
            );
    });
});
