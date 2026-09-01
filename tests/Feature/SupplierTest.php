<?php

use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('viewing suppliers', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('suppliers.index'))->assertRedirect(route('login'));
    });

    it('forbids a user without a shop from viewing suppliers', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('suppliers.index'))
            ->assertForbidden();
    });

    it('only shows the authenticated user\'s own suppliers', function () {
        [$owner, $shop] = createUserWithShop();
        $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

        [$other, $otherShop] = createUserWithShop();
        Supplier::factory()->create(['shop_id' => $otherShop->id]);

        $this->actingAs($owner)
            ->get(route('suppliers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('suppliers/Index')
                ->has('suppliers.data', 1)
                ->where('suppliers.data.0.id', $supplier->id)
            );
    });
});

describe('creating suppliers', function () {
    it('creates a supplier for the user\'s own shop', function () {
        [$owner, $shop] = createUserWithShop();

        $response = $this->actingAs($owner)->post(route('suppliers.store'), [
            'shop_id' => $shop->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'contact_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '555-1000',
            'lead_time_days' => 5,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'shop_id' => $shop->id,
            'email' => 'jane@example.com',
        ]);
    });

    it('prevents creating a supplier for another user\'s shop', function () {
        [$owner] = createUserWithShop();
        [$other, $otherShop] = createUserWithShop();

        $response = $this->actingAs($owner)->post(route('suppliers.store'), [
            'shop_id' => $otherShop->id,
            'first_name' => 'Sneaky',
            'last_name' => 'Supplier',
            'email' => 'sneaky@example.com',
            'phone' => '555-2000',
            'lead_time_days' => 5,
        ]);

        $response->assertSessionHasErrors('shop_id');
        $this->assertDatabaseMissing('suppliers', ['email' => 'sneaky@example.com']);
    });

    it('rejects a lead time outside a sane range', function (int $leadTime) {
        [$owner, $shop] = createUserWithShop();

        $this->actingAs($owner)->post(route('suppliers.store'), [
            'shop_id' => $shop->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone' => '555-1000',
            'lead_time_days' => $leadTime,
        ])->assertSessionHasErrors('lead_time_days');
    })->with([
        'zero days' => 0,
        'over a year' => 400,
    ]);
});

describe('updating suppliers', function () {
    it('allows the owner to update their supplier', function () {
        [$owner, $shop] = createUserWithShop();
        $supplier = Supplier::factory()->create(['shop_id' => $shop->id, 'first_name' => 'Old']);

        $response = $this->actingAs($owner)->put(route('suppliers.update', $supplier), [
            'shop_id' => $shop->id,
            'first_name' => 'New',
            'last_name' => $supplier->last_name,
            'contact_name' => $supplier->contact_name,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'lead_time_days' => 7,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('suppliers.index'));

        expect($supplier->fresh()->first_name)->toBe('New');
    });

    it('prevents a user from updating another user\'s supplier', function () {
        [$owner, $shop] = createUserWithShop();
        $supplier = Supplier::factory()->create(['shop_id' => $shop->id, 'first_name' => 'Original']);

        [$intruder] = createUserWithShop();

        $this->actingAs($intruder)
            ->put(route('suppliers.update', $supplier), [
                'shop_id' => $shop->id,
                'first_name' => 'Hacked',
                'last_name' => $supplier->last_name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'lead_time_days' => $supplier->lead_time_days,
            ])
            ->assertForbidden();

        expect($supplier->fresh()->first_name)->toBe('Original');
    });
});

describe('deleting suppliers', function () {
    it('allows the owner to delete their supplier', function () {
        [$owner, $shop] = createUserWithShop();
        $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

        $this->actingAs($owner)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'));

        $this->assertModelMissing($supplier);
    });

    it('prevents a user from deleting another user\'s supplier', function () {
        [$owner, $shop] = createUserWithShop();
        $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

        [$intruder] = createUserWithShop();

        $this->actingAs($intruder)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertForbidden();

        $this->assertModelExists($supplier);
    });
});

describe('filtering and pagination', function () {
    it('searches suppliers by name, contact or email', function () {
        [$owner, $shop] = createUserWithShop();
        $match = Supplier::factory()->create([
            'shop_id' => $shop->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
        ]);
        Supplier::factory()->create([
            'shop_id' => $shop->id,
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john@example.com',
        ]);

        $this->actingAs($owner)
            ->get(route('suppliers.index', ['search' => 'jane']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('suppliers.data', 1)
                ->where('suppliers.data.0.id', $match->id)
                ->where('filters.search', 'jane')
            );
    });

    it('filters suppliers by shop', function () {
        [$owner, $shopA] = createUserWithShop();
        $shopB = Shop::factory()->create(['user_id' => $owner->id]);
        $match = Supplier::factory()->create(['shop_id' => $shopA->id]);
        Supplier::factory()->create(['shop_id' => $shopB->id]);

        $this->actingAs($owner)
            ->get(route('suppliers.index', ['shop' => $shopA->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('suppliers.data', 1)
                ->where('suppliers.data.0.id', $match->id)
            );
    });

    it('paginates suppliers', function () {
        [$owner, $shop] = createUserWithShop();
        Supplier::factory()->count(20)->create(['shop_id' => $shop->id]);

        $this->actingAs($owner)
            ->get(route('suppliers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('suppliers.data', 15)
                ->where('suppliers.links.next', fn ($url) => $url !== null)
            );
    });
});
