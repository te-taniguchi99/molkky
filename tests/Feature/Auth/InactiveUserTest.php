<?php

use App\Models\User;

test('active users can access the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('inactive users are logged out and redirected to the login screen', function () {
    // 無効なアカウントを作る
    $user = User::factory()->inactive()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    // ログイン画面へ戻され、ログアウトされていること
    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('is_active defaults to true for new users', function () {
    $user = User::factory()->create();

    expect($user->fresh()->is_active)->toBeTrue();
});
