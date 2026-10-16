<?php

use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Game;
use App\Models\Participant;
use App\Models\Tournament;
use App\Models\User;

/**
 * 指定した方式・参加数の大会を作る(参加者は、名前を P1, P2 ... として登録する)。
 */
function makeTournament(TournamentFormat $format, int $count): Tournament
{
    $tournament = Tournament::factory()->create(['format' => $format]);

    foreach (range(1, $count) as $number) {
        Participant::factory()->create(['tournament_id' => $tournament->id, 'name' => 'P'.$number]);
    }

    return $tournament;
}

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('a round robin creates a game for every pair', function () {
    $tournament = makeTournament(TournamentFormat::RoundRobin, 4);

    $this->post(route('tournaments.start', $tournament))->assertRedirect(route('tournaments.show', $tournament));

    // 4 人の総当たりは、6 試合(4 × 3 ÷ 2)
    expect($tournament->games()->count())->toBe(6)
        ->and($tournament->games()->where('is_bye', true)->count())->toBe(0)
        ->and($tournament->fresh()->status)->toBe(TournamentStatus::InProgress);
});

test('a knockout with a power of two creates no byes', function () {
    $tournament = makeTournament(TournamentFormat::Knockout, 4);

    $this->post(route('tournaments.start', $tournament));

    expect($tournament->games()->count())->toBe(2)
        ->and($tournament->games()->where('is_bye', true)->count())->toBe(0);
});

test('a knockout gives byes to the first registered participants', function () {
    $tournament = makeTournament(TournamentFormat::Knockout, 5);

    $this->post(route('tournaments.start', $tournament));

    // 5 人 → 8 枠。不戦勝が 3 人、残り 2 人が 1 試合
    $games = $tournament->games()->with('participants')->orderBy('position')->get();

    expect($games)->toHaveCount(4)
        ->and($games->where('is_bye', true))->toHaveCount(3)
        ->and($games->take(3)->map(fn (Game $game) => $game->participants->first()->name)->all())->toBe(['P1', 'P2', 'P3'])
        ->and($games->last()->participants->pluck('name')->all())->toBe(['P4', 'P5']);

    // 不戦勝の試合は、終了していて、勝者が決まっている
    $bye = $games->first();
    expect($bye->winner_id)->toBe($bye->participants->first()->id);
});

test('a tournament needs at least two participants to start', function () {
    $tournament = makeTournament(TournamentFormat::RoundRobin, 1);

    $this->post(route('tournaments.start', $tournament));

    expect($tournament->games()->count())->toBe(0)
        ->and($tournament->fresh()->status)->toBe(TournamentStatus::Preparing);
});

test('a tournament can not be started twice', function () {
    $tournament = makeTournament(TournamentFormat::RoundRobin, 3);

    $this->post(route('tournaments.start', $tournament));
    $this->post(route('tournaments.start', $tournament));

    // 3 人の総当たりは 3 試合のまま(2 回目で増えない)
    expect($tournament->games()->count())->toBe(3);
});
