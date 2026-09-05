<?php

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a user who owns a shop.
 *
 * @return array{0: User, 1: Shop}
 */
function createUserWithShop(): array
{
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);

    return [$user, $shop];
}

/**
 * Create a user who owns a shop containing one product.
 *
 * @return array{0: User, 1: Shop, 2: Product}
 */
function createUserWithProduct(): array
{
    [$user, $shop] = createUserWithShop();
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    return [$user, $shop, $product];
}
