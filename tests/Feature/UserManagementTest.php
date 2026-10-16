<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('authenticated users can see the user list and the create screen', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('users.index'))->assertOk();
    $this->get(route('users.create'))->assertOk();
});

test('a user can be added without a password', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('users.store'), [
        'name' => '谷口',
        'username' => 'Te-Taniguchi',
    ])->assertRedirect(route('users.index'));

    // ログインIDは小文字にそろえて保存され、有効な状態で作られる
    $user = User::firstWhere('username', 'te-taniguchi');
    expect($user)->not->toBeNull()
        ->and($user->is_active)->toBeTrue();
});

test('the login id must be unique when adding a user', function () {
    User::factory()->create(['username' => 'taken-id']);
    $this->actingAs(User::factory()->create());

    $this->post(route('users.store'), [
        'name' => '谷口',
        'username' => 'taken-id',
    ])->assertSessionHasErrors('username');
});

test('a user can be disabled and enabled again', function () {
    $this->actingAs(User::factory()->create());
    $target = User::factory()->create();

    $this->patch(route('users.toggle-active', $target))->assertRedirect(route('users.index'));
    expect($target->fresh()->is_active)->toBeFalse();

    $this->patch(route('users.toggle-active', $target));
    expect($target->fresh()->is_active)->toBeTrue();
});

test('a user can not disable themselves', function () {
    $me = User::factory()->create();

    $this->actingAs($me)->patch(route('users.toggle-active', $me));

    expect($me->fresh()->is_active)->toBeTrue();
});

test('the registration screen is closed', function () {
    $this->get('/register')->assertNotFound();
});
