<?php

use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Participant;
use App\Models\Tournament;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * 更新の送信内容(必要な項目を、まとめて用意する)。
 */
function updatePayload(array $overrides = []): array
{
    return [
        'name' => '更新後の大会',
        'held_at' => '2026-12-01T09:00',
        'venue' => '△△体育館',
        'format' => TournamentFormat::Knockout->value,
        'participant_type' => ParticipantType::Team->value,
        'target_score' => 40,
        'reset_score' => 20,
        'max_consecutive_misses' => 2,
        ...$overrides,
    ];
}

test('the edit screen can be rendered', function () {
    $tournament = Tournament::factory()->create();

    $this->get(route('tournaments.edit', $tournament))->assertOk();
});

test('everything can be updated while the tournament is preparing', function () {
    $tournament = Tournament::factory()->create();

    $this->put(route('tournaments.update', $tournament), updatePayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('tournaments.show', $tournament));

    $tournament->refresh();

    expect($tournament->name)->toBe('更新後の大会')
        ->and($tournament->venue)->toBe('△△体育館')
        ->and($tournament->format)->toBe(TournamentFormat::Knockout)
        ->and($tournament->participant_type)->toBe(ParticipantType::Team)
        ->and($tournament->target_score)->toBe(40)
        ->and($tournament->max_consecutive_misses)->toBe(2);
});

test('the same name as itself is allowed but another tournament name is not', function () {
    $tournament = Tournament::factory()->create(['name' => '大会A']);
    Tournament::factory()->create(['name' => '大会B']);

    $this->put(route('tournaments.update', $tournament), updatePayload(['name' => '大会A']))
        ->assertSessionHasNoErrors();

    $this->put(route('tournaments.update', $tournament), updatePayload(['name' => '大会B']))
        ->assertSessionHasErrors('name');
});

test('the participant type can not be changed once participants exist', function () {
    $tournament = Tournament::factory()->create(['participant_type' => ParticipantType::Individual]);
    Participant::factory()->create(['tournament_id' => $tournament->id]);

    $this->put(route('tournaments.update', $tournament), updatePayload());

    $tournament->refresh();

    // 名前や場所は変わるが、参加形態は変わらない(方式とルールは、準備中なので変わる)
    expect($tournament->name)->toBe('更新後の大会')
        ->and($tournament->participant_type)->toBe(ParticipantType::Individual)
        ->and($tournament->target_score)->toBe(40);
});

test('the rules can not be changed after the tournament starts', function () {
    $tournament = Tournament::factory()->create(['status' => TournamentStatus::InProgress]);

    // 開始後は、画面から方式やルールが送られてこないため、名前・日時・場所だけを送る
    $this->put(route('tournaments.update', $tournament), [
        'name' => '開始後の更新',
        'venue' => '場所だけ変更',
    ])->assertSessionHasNoErrors();

    $tournament->refresh();

    expect($tournament->name)->toBe('開始後の更新')
        ->and($tournament->venue)->toBe('場所だけ変更')
        ->and($tournament->target_score)->toBe(50);

    // 送られてきても、無視する
    $this->put(route('tournaments.update', $tournament), updatePayload(['name' => '開始後の更新2']));

    expect($tournament->refresh()->target_score)->toBe(50)
        ->and($tournament->format)->toBe(TournamentFormat::RoundRobin);
});
