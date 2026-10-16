<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'te-taniguchi',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('new users can register without a password when it is not required', function () {
    config(['auth.require_password' => false]);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'no-password-user',
    ]);

    $this->assertAuthenticated();
    expect(App\Models\User::firstWhere('username', 'no-password-user'))->not->toBeNull();
});

test('the login id must use only allowed characters', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'bad id!',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('username');
});

test('the login id must be unique', function () {
    App\Models\User::factory()->create(['username' => 'te-taniguchi']);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'te-taniguchi',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('username');
});
