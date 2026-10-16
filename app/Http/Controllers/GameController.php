<?php

namespace App\Http\Controllers;

use App\Enums\GameStatus;
use App\Enums\TournamentStatus;
use App\Models\Game;
use App\Support\GameBoard;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    /**
     * 進行中の試合の一覧を表示する(進行中の大会の、終わっていない試合)。
     */
    public function index(): Response
    {
        $games = Game::query()
            ->whereHas('tournament', fn ($query) => $query->where('status', TournamentStatus::InProgress->value))
            ->where('status', '!=', GameStatus::Finished->value)
            ->where('is_bye', false)
            ->with(['tournament', 'participants.members', 'throws'])
            ->orderBy('tournament_id')
            ->orderBy('round')
            ->orderBy('position')
            ->get()
            ->map(fn (Game $game) => GameBoard::forGame($game));

        return Inertia::render('games/Index', [
            'games' => $games,
        ]);
    }

    /**
     * 試合の点数入力画面を表示する。
     */
    public function show(Game $game): Response
    {
        return Inertia::render('games/Show', [
            'board' => GameBoard::forGame($game),
        ]);
    }
}
