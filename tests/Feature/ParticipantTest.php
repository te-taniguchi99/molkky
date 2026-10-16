<?php

use App\Enums\ParticipantType;
use App\Enums\TournamentStatus;
use App\Models\Participant;
use App\Models\Tournament;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the tournament detail screen can be rendered', function () {
    $tournament = Tournament::factory()->create();

    $this->get(route('tournaments.show', $tournament))->assertOk();
});

test('teams of different sizes can be registered in a team tournament', function () {
    $tournament = Tournament::factory()->create([
        'participant_type' => ParticipantType::Team,
        'team_size' => 3,
    ]);

    // 11 人を 3 人ずつに分けると、3・3・3・2 人になる
    foreach ([['A', 3], ['B', 3], ['C', 3], ['D', 2]] as [$name, $count]) {
        $this->post(route('participants.store', $tournament), [
            'name' => $name,
            'member_count' => $count,
        ])->assertSessionHasNoErrors();
    }

    expect($tournament->participants()->orderBy('id')->pluck('member_count')->all())->toBe([3, 3, 3, 2]);
});

test('the team size defaults to the standard size when it is not entered', function () {
    $tournament = Tournament::factory()->create([
        'participant_type' => ParticipantType::Team,
        'team_size' => 4,
    ]);

    $this->post(route('participants.store', $tournament), ['name' => 'A']);

    expect($tournament->participants()->first()->member_count)->toBe(4);
});

test('an individual participant always has one member', function () {
    $tournament = Tournament::factory()->create();

    $this->post(route('participants.store', $tournament), ['name' => '谷口', 'member_count' => 5]);

    expect($tournament->participants()->first()->member_count)->toBe(1);
});

test('the same name can not be registered twice in a tournament', function () {
    $tournament = Tournament::factory()->create();
    Participant::factory()->create(['tournament_id' => $tournament->id, 'name' => '谷口']);

    $this->post(route('participants.store', $tournament), ['name' => '谷口'])
        ->assertSessionHasErrors('name');
});

test('participants can be deleted while the tournament is preparing', function () {
    $tournament = Tournament::factory()->create();
    $participant = Participant::factory()->create(['tournament_id' => $tournament->id]);

    $this->delete(route('participants.destroy', $participant))->assertRedirect(route('tournaments.show', $tournament));

    expect(Participant::count())->toBe(0);
});

test('participants can not be added or deleted after the tournament starts', function () {
    $tournament = Tournament::factory()->create(['status' => TournamentStatus::InProgress]);
    $participant = Participant::factory()->create(['tournament_id' => $tournament->id]);

    $this->post(route('participants.store', $tournament), ['name' => '新しい人']);
    $this->delete(route('participants.destroy', $participant));

    expect(Participant::count())->toBe(1);
});
