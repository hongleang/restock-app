<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the confirm password screen', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/ConfirmPassword'),
    );
});

it('requires authentication to confirm the password', function () {
    $response = $this->get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});
