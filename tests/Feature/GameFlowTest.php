<?php

use App\Enums\GameStatus;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Game;
use App\Models\Participant;
use App\Models\Tournament;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * 大会を作り、参加者を登録して、開始する(試合が自動で作られる)。
 * 点数を短く確認できるように、目標点は 10、戻る得点は 5 にする。
 */
function startedTournament(TournamentFormat $format, int $count, array $overrides = []): Tournament
{
    $tournament = Tournament::factory()->create([
        'format' => $format,
        'target_score' => 10,
        'reset_score' => 5,
        ...$overrides,
    ]);

    foreach (range(1, $count) as $number) {
        Participant::factory()->create(['tournament_id' => $tournament->id, 'name' => 'P'.$number]);
    }

    test()->post(route('tournaments.start', $tournament));

    return $tournament->refresh();
}

/**
 * 1投を記録する(participant は、手番の参加者の名前)。
 */
function throwFor(Game $game, string $participantName, string $kind, int $value = 0)
{
    $participant = Participant::where('tournament_id', $game->tournament_id)->where('name', $participantName)->firstOrFail();

    return test()->post(route('games.throws.store', $game), [
        'participant_id' => $participant->id,
        'kind' => $kind,
        'value' => $value,
    ]);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('throws add points and the turn alternates', function () {
    $tournament = startedTournament(TournamentFormat::RoundRobin, 2);
    $game = $tournament->games()->first();

    throwFor($game, 'P1', 'score', 7)->assertSessionHasNoErrors();
    throwFor($game, 'P2', 'score', 3)->assertSessionHasNoErrors();

    $this->get(route('games.show', $game))->assertInertia(fn (Assert $page) => $page
        ->component('games/Show')
        ->where('board.participants.0.score', 7)
        ->where('board.participants.1.score', 3)
        ->where('board.participants.0.is_current', true));

    expect($game->refresh()->status)->toBe(GameStatus::InProgress);
});

test('a throw out of turn is rejected', function () {
    $tournament = startedTournament(TournamentFormat::RoundRobin, 2);
    $game = $tournament->games()->first();

    // P1 の番なのに、P2 として記録しようとする
    throwFor($game, 'P2', 'score', 5)->assertSessionHasErrors('participant_id');

    expect($game->throws()->count())->toBe(0);
});

test('invalid throw values are rejected', function () {
    $tournament = startedTournament(TournamentFormat::RoundRobin, 2);
    $game = $tournament->games()->first();

    // 得点は 1〜12 だけ
    throwFor($game, 'P1', 'score', 13)->assertSessionHasErrors('value');
    throwFor($game, 'P1', 'score', 0)->assertSessionHasErrors('value');

    expect($game->throws()->count())->toBe(0);
});

test('reaching the target exactly finishes the game and the tournament', function () {
    $tournament = startedTournament(TournamentFormat::RoundRobin, 2);
    $game = $tournament->games()->first();

    throwFor($game, 'P1', 'score', 10);

    $game->refresh();
    expect($game->status)->toBe(GameStatus::Finished)
        ->and($game->winner->name)->toBe('P1')
        ->and($tournament->refresh()->status)->toBe(TournamentStatus::Finished);

    // 終了した試合には、記録できない
    throwFor($game, 'P2', 'score', 1)->assertSessionHasErrors('throw');
});

test('going over the target resets the score', function () {
    $tournament = startedTournament(TournamentFormat::RoundRobin, 2);
    $game = $tournament->games()->first();

    throwFor($game, 'P1', 'score', 12);

    $this->get(route('games.show', $game))->assertInertia(fn (Assert $page) => $page
        ->where('board.participants.0.score', 5));
});

test('three consecutive misses eliminate a participant', function () {
    $tournament = startedTournament(TournamentFormat::RoundRobin, 2);
    $game = $tournament->games()->first();

    throwFor($game, 'P1', 'miss');
    throwFor($game, 'P2', 'score', 1);
    throwFor($game, 'P1', 'miss');
    throwFor($game, 'P2', 'score', 1);
    throwFor($game, 'P1', 'miss');

    expect($game->refresh()->winner->name)->toBe('P2');
});

test('the last throw can be undone', function () {
    $tournament = startedTournament(TournamentFormat::RoundRobin, 2);
    $game = $tournament->games()->first();

    throwFor($game, 'P1', 'score', 7);
    $this->delete(route('games.throws.destroy', $game))->assertSessionHasNoErrors();

    expect($game->throws()->count())->toBe(0)
        ->and($game->refresh()->status)->toBe(GameStatus::Waiting);

    // 記録がないときは、取り消せない
    $this->delete(route('games.throws.destroy', $game))->assertSessionHasErrors('throw');
});

test('undoing the winning throw reopens the game and the tournament', function () {
    $tournament = startedTournament(TournamentFormat::RoundRobin, 2);
    $game = $tournament->games()->first();

    throwFor($game, 'P1', 'score', 10);
    $this->delete(route('games.throws.destroy', $game));

    expect($game->refresh()->status)->toBe(GameStatus::Waiting)
        ->and($game->winner_id)->toBeNull()
        ->and($tournament->refresh()->status)->toBe(TournamentStatus::InProgress);
});

test('a knockout creates the next round when a round finishes', function () {
    $tournament = startedTournament(TournamentFormat::Knockout, 4);
    [$gameA, $gameB] = $tournament->games()->orderBy('position')->get()->all();

    throwFor($gameA, 'P1', 'score', 10); // P1 が P2 に勝つ
    expect($tournament->games()->where('round', 2)->count())->toBe(0);

    throwFor($gameB, 'P3', 'score', 10); // P3 が P4 に勝つ

    // 2回戦が、勝者の P1 と P3 で作られる
    $final = $tournament->games()->where('round', 2)->with('participants')->first();
    expect($final->participants->pluck('name')->all())->toBe(['P1', 'P3'])
        ->and($tournament->refresh()->status)->toBe(TournamentStatus::InProgress);

    throwFor($final, 'P1', 'score', 10);

    expect($tournament->refresh()->status)->toBe(TournamentStatus::Finished);
});

test('a finished game can not be undone once the next round exists', function () {
    $tournament = startedTournament(TournamentFormat::Knockout, 4);
    [$gameA, $gameB] = $tournament->games()->orderBy('position')->get()->all();

    throwFor($gameA, 'P1', 'score', 10);
    throwFor($gameB, 'P3', 'score', 10);

    $this->delete(route('games.throws.destroy', $gameA))->assertSessionHasErrors('throw');

    expect($gameA->refresh()->status)->toBe(GameStatus::Finished);
});

test('the games list shows only unfinished games of started tournaments', function () {
    $started = startedTournament(TournamentFormat::RoundRobin, 3);
    Tournament::factory()->create(); // 準備中の大会は、表示しない

    $this->get(route('games.index'))->assertInertia(fn (Assert $page) => $page
        ->component('games/Index')
        ->has('games', 3));

    // 1 試合が終わると、一覧から消える
    throwFor($started->games()->first(), 'P1', 'score', 10);

    $this->get(route('games.index'))->assertInertia(fn (Assert $page) => $page->has('games', 2));
});

test('the standings are sorted by wins', function () {
    $tournament = startedTournament(TournamentFormat::RoundRobin, 3);

    // 総当たり: P1-P2、P1-P3、P2-P3 の 3 試合。P1 が 2 勝、P2 が 1 勝、P3 が 0 勝
    foreach ($tournament->games()->with('participants')->orderBy('position')->get() as $game) {
        $names = $game->participants->pluck('name')->all();
        $winner = in_array('P1', $names, true) ? 'P1' : 'P2';
        throwFor($game, $names[0], $names[0] === $winner ? 'score' : 'miss', $names[0] === $winner ? 10 : 0);

        if ($names[0] !== $winner) {
            throwFor($game, $names[1], 'score', 10);
        }
    }

    $this->get(route('standings.show', $tournament))->assertInertia(fn (Assert $page) => $page
        ->component('standings/Show')
        ->where('rows.0.name', 'P1')->where('rows.0.wins', 2)->where('rows.0.rank', 1)
        ->where('rows.1.name', 'P2')->where('rows.1.wins', 1)->where('rows.1.rank', 2)
        ->where('rows.2.name', 'P3')->where('rows.2.losses', 2)->where('rows.2.rank', 3));
});

test('the standings screens can be rendered', function () {
    $tournament = startedTournament(TournamentFormat::RoundRobin, 2);

    $this->get(route('standings.index'))->assertOk();
    $this->get(route('standings.show', $tournament))->assertOk();
});
