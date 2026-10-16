<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'username' => $user->username,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password when the password is required', function () {
    // パスワードを確認する設定にする
    config(['auth.require_password' => true]);

    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->username, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});

test('any non-empty password is accepted when the password is not checked', function () {
    config(['auth.require_password' => false]);

    $user = User::factory()->create();

    // 本当のパスワードとは違う、適当な文字を送信する
    $response = $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'anything',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('an empty password is rejected even when the password is not checked', function () {
    config(['auth.require_password' => false]);

    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => '',
    ])->assertSessionHasErrors('password');

    $this->assertGuest();
});

test('users can not authenticate with an unknown login id', function () {
    config(['auth.require_password' => false]);

    $this->post(route('login.store'), [
        'username' => 'unknown-user',
        'password' => 'anything',
    ]);

    $this->assertGuest();
});

test('inactive users can not log in', function () {
    config(['auth.require_password' => false]);

    $user = User::factory()->inactive()->create();

    $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'anything',
    ]);

    $this->assertGuest();
});
