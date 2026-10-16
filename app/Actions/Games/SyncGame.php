<?php

namespace App\Actions\Games;

use App\Actions\Tournaments\AdvanceTournament;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Support\ScoreCalculator;

/**
 * 投擲の記録から点数を計算し直して、試合の状態(進行状況・勝者)を更新する。
 */
class SyncGame
{
    public function __construct(private AdvanceTournament $advanceTournament) {}

    /**
     * 試合の状態を更新して、計算結果を返す。
     *
     * @return array<string, mixed>
     */
    public function __invoke(Game $game): array
    {
        $tournament = $game->tournament;

        // 投げる順に並んだ参加者の id と、投げた順の記録を用意する
        $participantIds = $game->participants()->get()->pluck('id')->all();
        $throws = $game->throws()->get()->map(fn ($throw) => [
            'participant_id' => $throw->participant_id,
            'kind' => $throw->kind->value,
            'value' => $throw->value,
        ])->all();

        $state = ScoreCalculator::calculate(
            $participantIds,
            $throws,
            $tournament->target_score,
            $tournament->reset_score,
            $tournament->max_consecutive_misses,
        );

        $wasFinished = $game->status === GameStatus::Finished;

        if ($state['winner_id'] !== null) {
            $game->status = GameStatus::Finished;
            $game->winner_id = $state['winner_id'];
        } else {
            // 記録があれば進行中、なければ待機中(取り消しで、記録がなくなった場合も含む)
            $game->status = $throws === [] ? GameStatus::Waiting : GameStatus::InProgress;
            $game->winner_id = null;
        }

        $game->save();

        // 終了した直後は、次の試合を作る(または大会を終了する)。終了が取り消されたときは、大会を進行中に戻す
        if ($game->status === GameStatus::Finished && ! $wasFinished) {
            $this->advanceTournament->after($game);
        } elseif ($game->status !== GameStatus::Finished && $wasFinished) {
            $this->advanceTournament->reopen($game);
        }

        return $state;
    }
}
