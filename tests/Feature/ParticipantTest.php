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

test('teams can have different numbers of members', function () {
    $tournament = Tournament::factory()->create(['participant_type' => ParticipantType::Team]);
    $users = User::factory()->count(5)->create();

    // 3 人のチームと、2 人のチーム(割り切れなくてもよい)
    $this->post(route('participants.store', $tournament), [
        'name' => 'A', 'user_ids' => $users->take(3)->pluck('id')->all(),
    ])->assertSessionHasNoErrors();
    $this->post(route('participants.store', $tournament), [
        'name' => 'B', 'user_ids' => $users->skip(3)->pluck('id')->all(),
    ])->assertSessionHasNoErrors();

    $teams = $tournament->participants()->withCount('members')->orderBy('id')->get();

    expect($teams->pluck('members_count')->all())->toBe([3, 2]);
});

test('a team needs a name and at least one member', function () {
    $tournament = Tournament::factory()->create(['participant_type' => ParticipantType::Team]);

    $this->post(route('participants.store', $tournament), ['name' => 'A'])
        ->assertSessionHasErrors('user_ids');
    $this->post(route('participants.store', $tournament), ['user_ids' => [User::factory()->create()->id]])
        ->assertSessionHasErrors('name');
});

test('a user can not belong to two participants in the same tournament', function () {
    $tournament = Tournament::factory()->create(['participant_type' => ParticipantType::Team]);
    $user = User::factory()->create();

    $this->post(route('participants.store', $tournament), ['name' => 'A', 'user_ids' => [$user->id]]);
    $this->post(route('participants.store', $tournament), ['name' => 'B', 'user_ids' => [$user->id]])
        ->assertSessionHasErrors('user_ids.0');

    expect($tournament->participants()->count())->toBe(1);
});

test('the same user can join different tournaments', function () {
    $user = User::factory()->create();

    foreach ([1, 2] as $_) {
        $tournament = Tournament::factory()->create(['participant_type' => ParticipantType::Team]);
        $this->post(route('participants.store', $tournament), ['name' => 'A', 'user_ids' => [$user->id]])
            ->assertSessionHasNoErrors();
    }

    expect(Participant::count())->toBe(2);
});

test('inactive users can not be selected', function () {
    $tournament = Tournament::factory()->create();
    $inactive = User::factory()->inactive()->create();

    $this->post(route('participants.store', $tournament), ['user_id' => $inactive->id])
        ->assertSessionHasErrors('user_id');
});

test('an individual participant is a single user and takes the user name', function () {
    $tournament = Tournament::factory()->create();
    $user = User::factory()->create(['name' => '谷口']);

    $this->post(route('participants.store', $tournament), ['user_id' => $user->id])
        ->assertSessionHasNoErrors();

    $participant = $tournament->participants()->with('members')->first();

    expect($participant->name)->toBe('谷口')
        ->and($participant->members->pluck('id')->all())->toBe([$user->id]);
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
    $user = User::factory()->create();

    $this->post(route('participants.store', $tournament), ['user_id' => $user->id]);
    $this->delete(route('participants.destroy', $participant));

    expect(Participant::count())->toBe(1);
});
