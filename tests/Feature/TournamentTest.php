<?php

use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Models\Tournament;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('tournaments.index'))->assertRedirect(route('login'));
});

test('authenticated users can see the tournament list', function () {
    $this->actingAs(User::factory()->create());
    Tournament::factory()->create(['name' => 'テスト大会']);

    $this->get(route('tournaments.index'))->assertOk();
});

test('authenticated users can see the create screen', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('tournaments.create'))->assertOk();
});

test('an individual tournament can be created with default rules', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => '第1回大会',
        'format' => TournamentFormat::RoundRobin->value,
        'participant_type' => ParticipantType::Individual->value,
        'target_score' => 50,
        'reset_score' => 25,
        'max_consecutive_misses' => 3,
    ]);

    $response->assertRedirect(route('tournaments.index'));

    $tournament = Tournament::firstWhere('name', '第1回大会');
    expect($tournament)->not->toBeNull()
        ->and($tournament->user_id)->toBe($user->id)
        ->and($tournament->team_size)->toBeNull();
});

test('a team tournament requires the team size', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tournaments.store'), [
            'name' => 'チーム大会',
            'format' => TournamentFormat::Knockout->value,
            'participant_type' => ParticipantType::Team->value,
            'target_score' => 50,
            'reset_score' => 25,
            'max_consecutive_misses' => 3,
        ])
        ->assertSessionHasErrors('team_size');

    expect(Tournament::count())->toBe(0);
});

test('the reset score must be lower than the target score', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tournaments.store'), [
            'name' => '不正な設定',
            'format' => TournamentFormat::RoundRobin->value,
            'participant_type' => ParticipantType::Individual->value,
            'target_score' => 50,
            'reset_score' => 50,
            'max_consecutive_misses' => 3,
        ])
        ->assertSessionHasErrors('reset_score');
});
