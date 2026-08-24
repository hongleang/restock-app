<?php

use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('suppliers.index'))->assertRedirect(route('login'));
});

test('users without a shop cannot view suppliers', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('suppliers.index'))
        ->assertForbidden();
});

test('suppliers index only shows the authenticated users own suppliers', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id, 'first_name' => 'Own']);

    $other = User::factory()->create();
    $otherShop = Shop::factory()->create(['user_id' => $other->id]);
    Supplier::factory()->create(['shop_id' => $otherShop->id, 'first_name' => 'Someone Elses']);

    $this->actingAs($owner)
        ->get(route('suppliers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('suppliers/Index')
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.id', $supplier->id)
        );
});

test('a supplier can be created for the users own shop', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);

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

test('a supplier cannot be created for another users shop', function () {
    $owner = User::factory()->create();
    Shop::factory()->create(['user_id' => $owner->id]);

    $other = User::factory()->create();
    $otherShop = Shop::factory()->create(['user_id' => $other->id]);

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

test('lead time must be within a sane range', function (int $leadTime) {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);

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

test('a supplier owner can update their supplier', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
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

test('a user cannot update another users supplier', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id, 'first_name' => 'Original']);

    $intruder = User::factory()->create();
    Shop::factory()->create(['user_id' => $intruder->id]);

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

test('a supplier owner can delete their supplier', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

    $this->actingAs($owner)
        ->delete(route('suppliers.destroy', $supplier))
        ->assertRedirect(route('suppliers.index'));

    $this->assertModelMissing($supplier);
});

test('a user cannot delete another users supplier', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

    $intruder = User::factory()->create();
    Shop::factory()->create(['user_id' => $intruder->id]);

    $this->actingAs($intruder)
        ->delete(route('suppliers.destroy', $supplier))
        ->assertForbidden();

    $this->assertModelExists($supplier);
});

test('suppliers can be searched by name, contact or email', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
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

test('suppliers can be filtered by shop', function () {
    $owner = User::factory()->create();
    $shopA = Shop::factory()->create(['user_id' => $owner->id]);
    $shopB = Shop::factory()->create(['user_id' => $owner->id]);
    $match = Supplier::factory()->create(['shop_id' => $shopA->id]);
    Supplier::factory()->create(['shop_id' => $shopB->id]);

    $this->actingAs($owner)
        ->get(route('suppliers.index', ['shop_id' => $shopA->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.id', $match->id)
        );
});

test('suppliers are paginated', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    Supplier::factory()->count(20)->create(['shop_id' => $shop->id]);

    $this->actingAs($owner)
        ->get(route('suppliers.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('suppliers.data', 15)
            ->where('suppliers.next_page_url', fn ($url) => $url !== null)
        );
});
