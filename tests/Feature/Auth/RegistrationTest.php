<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

it('renders the registration screen', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

it('registers a new user', function () {
    $response = $this->post(route('register.store'), [
        'first_name' => 'John',
        'last_name' => 'John',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
