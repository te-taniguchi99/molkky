<?php

namespace App\Actions\Tournaments;

use App\Enums\GameStatus;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Game;

/**
 * 試合が終わったあとの、大会の進行(次の回戦の作成、大会の終了)を行う。
 */
class AdvanceTournament
{
    /**
     * 試合が終了したときに呼ぶ。
     */
    public function after(Game $game): void
    {
        $tournament = $game->tournament;

        // 総当たり戦: すべての試合が終わったら、大会を終了する
        if ($tournament->format === TournamentFormat::RoundRobin) {
            $unfinished = $tournament->games()->where('status', '!=', GameStatus::Finished->value)->exists();

            if (! $unfinished) {
                $tournament->update(['status' => TournamentStatus::Finished]);
            }

            return;
        }

        // トーナメント戦: 同じ回戦の試合が、すべて終わっているかを調べる
        $roundGames = $tournament->games()->where('round', $game->round)->orderBy('position')->get();

        if ($roundGames->contains(fn (Game $roundGame) => $roundGame->status !== GameStatus::Finished)) {
            return;
        }

        // 次の回戦が、すでに作られているときは、何もしない
        if ($tournament->games()->where('round', '>', $game->round)->exists()) {
            return;
        }

        $winnerIds = $roundGames->pluck('winner_id')->all();

        // 勝者が 1 つだけなら、優勝が決まったので、大会を終了する
        if (count($winnerIds) === 1) {
            $tournament->update(['status' => TournamentStatus::Finished]);

            return;
        }

        // 勝者を、並び順に 2 つずつ組み合わせて、次の回戦を作る
        foreach (array_chunk($winnerIds, 2) as $index => $pair) {
            $nextGame = Game::create([
                'tournament_id' => $tournament->id,
                'round' => $game->round + 1,
                'position' => $index + 1,
                'status' => GameStatus::Waiting,
            ]);

            foreach ($pair as $order => $participantId) {
                $nextGame->participants()->attach($participantId, ['throw_order' => $order + 1]);
            }
        }
    }

    /**
     * 終了した試合が、取り消しで進行中に戻ったときに呼ぶ。
     */
    public function reopen(Game $game): void
    {
        $tournament = $game->tournament;

        if ($tournament->status === TournamentStatus::Finished) {
            $tournament->update(['status' => TournamentStatus::InProgress]);
        }
    }
}
