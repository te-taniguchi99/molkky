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
        ->and($tournament->user_id)->toBe($user->id);
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

test('a tournament can be deleted together with its participants and games', function () {
    $this->actingAs(User::factory()->create());
    $tournament = Tournament::factory()->create();
    $participant = App\Models\Participant::factory()->create(['tournament_id' => $tournament->id]);
    App\Models\Game::factory()->create(['tournament_id' => $tournament->id]);

    $this->delete(route('tournaments.destroy', $tournament))->assertRedirect(route('tournaments.index'));

    // 大会と、その参加者・試合が、すべて削除されている
    expect(Tournament::count())->toBe(0)
        ->and(App\Models\Participant::count())->toBe(0)
        ->and(App\Models\Game::count())->toBe(0);
});

test('guests can not delete a tournament', function () {
    $tournament = Tournament::factory()->create();

    $this->delete(route('tournaments.destroy', $tournament))->assertRedirect(route('login'));

    expect(Tournament::count())->toBe(1);
});

test('a tournament name must be unique', function () {
    $this->actingAs(User::factory()->create());
    Tournament::factory()->create(['name' => '第1回大会']);

    $this->post(route('tournaments.store'), [
        'name' => '第1回大会',
        'format' => TournamentFormat::RoundRobin->value,
        'participant_type' => ParticipantType::Individual->value,
        'target_score' => 50,
        'reset_score' => 25,
        'max_consecutive_misses' => 3,
    ])->assertSessionHasErrors('name');

    expect(Tournament::count())->toBe(1);
});

test('the date and venue can be set when creating a tournament', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('tournaments.store'), [
        'name' => '開催情報つきの大会',
        'held_at' => '2026-11-03T10:30',
        'venue' => '○○公園',
        'format' => TournamentFormat::RoundRobin->value,
        'participant_type' => ParticipantType::Individual->value,
        'target_score' => 50,
        'reset_score' => 25,
        'max_consecutive_misses' => 3,
    ])->assertSessionHasNoErrors();

    $tournament = Tournament::firstWhere('name', '開催情報つきの大会');

    expect($tournament->venue)->toBe('○○公園')
        ->and($tournament->held_at->format('Y-m-d H:i'))->toBe('2026-11-03 10:30');
});

test('the date and venue can be left empty', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('tournaments.store'), [
        'name' => '日時未定の大会',
        'format' => TournamentFormat::RoundRobin->value,
        'participant_type' => ParticipantType::Individual->value,
        'target_score' => 50,
        'reset_score' => 25,
        'max_consecutive_misses' => 3,
    ])->assertSessionHasNoErrors();

    expect(Tournament::firstWhere('name', '日時未定の大会')->held_at)->toBeNull();
});
