<?php

namespace App\Http\Controllers;

use App\Enums\GameStatus;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use Inertia\Inertia;
use Inertia\Response;

class StandingController extends Controller
{
    /**
     * 順位表を見る大会を選ぶ画面(開始した大会だけ)。
     */
    public function index(): Response
    {
        $tournaments = Tournament::query()
            ->where('status', '!=', TournamentStatus::Preparing->value)
            ->latest()
            ->get()
            ->map(fn (Tournament $tournament) => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'status' => $tournament->status->label(),
            ]);

        return Inertia::render('standings/Index', [
            'tournaments' => $tournaments,
        ]);
    }

    /**
     * 大会の順位表を表示する。勝った数の多い順に並べる。
     */
    public function show(Tournament $tournament): Response
    {
        // 参加者ごとの、勝ち数・負け数を数える(不戦勝の試合は、数えない)
        $stats = $tournament->participants()->orderBy('id')->get()
            ->mapWithKeys(fn ($participant) => [$participant->id => [
                'id' => $participant->id,
                'name' => $participant->name,
                'wins' => 0,
                'losses' => 0,
            ]])
            ->all();

        $finishedGames = $tournament->games()
            ->where('status', GameStatus::Finished->value)
            ->where('is_bye', false)
            ->with('participants')
            ->get();

        foreach ($finishedGames as $game) {
            foreach ($game->participants as $participant) {
                $key = $participant->id === $game->winner_id ? 'wins' : 'losses';
                $stats[$participant->id][$key]++;
            }
        }

        // 勝ち数の多い順。同じなら、負け数の少ない順
        $rows = collect($stats)
            ->sortBy([['wins', 'desc'], ['losses', 'asc'], ['id', 'asc']])
            ->values();

        // 順位をつける(勝ち数と負け数が同じなら、同じ順位。次の順位は、人数分とばす)
        $ranked = [];
        foreach ($rows as $index => $row) {
            $previous = $ranked[$index - 1] ?? null;
            $sameAsPrevious = $previous !== null
                && $previous['wins'] === $row['wins']
                && $previous['losses'] === $row['losses'];

            $ranked[] = [...$row, 'rank' => $sameAsPrevious ? $previous['rank'] : $index + 1];
        }

        return Inertia::render('standings/Show', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'status' => $tournament->status->label(),
            ],
            'rows' => $ranked,
        ]);
    }
}
