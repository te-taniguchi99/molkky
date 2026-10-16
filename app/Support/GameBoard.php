<?php

namespace App\Support;

use App\Enums\GameStatus;
use App\Enums\ThrowKind;
use App\Models\Game;

/**
 * 試合の「いまの盤面」(各参加者の点数・手番・勝者・直近の記録)を、画面に渡す形にまとめる。
 * 点数の計算は ScoreCalculator に任せ、ここでは表示用に整えるだけにする。
 */
class GameBoard
{
    /**
     * @return array<string, mixed>
     */
    public static function forGame(Game $game): array
    {
        $game->loadMissing(['tournament', 'participants.members', 'throws']);
        $tournament = $game->tournament;

        $state = ScoreCalculator::calculate(
            $game->participants->pluck('id')->all(),
            $game->throws->map(fn ($throw) => [
                'participant_id' => $throw->participant_id,
                'kind' => $throw->kind->value,
                'value' => $throw->value,
            ])->all(),
            $tournament->target_score,
            $tournament->reset_score,
            $tournament->max_consecutive_misses,
        );

        $names = $game->participants->pluck('name', 'id');

        return [
            'id' => $game->id,
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'target_score' => $tournament->target_score,
                'reset_score' => $tournament->reset_score,
                'max_consecutive_misses' => $tournament->max_consecutive_misses,
            ],
            'round' => $game->round,
            'status' => $game->status->label(),
            'is_finished' => $game->status === GameStatus::Finished,
            'is_bye' => $game->is_bye,
            'winner_name' => $game->winner_id === null ? null : $names[$game->winner_id] ?? null,
            'current_participant_id' => $state['current_participant_id'],
            'participants' => $game->participants->map(fn ($participant) => [
                'id' => $participant->id,
                'name' => $participant->name,
                'members' => $participant->members->pluck('name')->all(),
                'score' => $state['participants'][$participant->id]['score'],
                'misses' => $state['participants'][$participant->id]['misses'],
                'eliminated' => $state['participants'][$participant->id]['eliminated'],
                'is_current' => $state['current_participant_id'] === $participant->id,
                'is_winner' => $state['winner_id'] === $participant->id,
            ])->all(),
            // 直近 10 件の記録(新しい順)
            'history' => $game->throws->reverse()->take(10)->map(fn ($throw) => [
                'id' => $throw->id,
                'name' => $names[$throw->participant_id] ?? '',
                'label' => match ($throw->kind) {
                    ThrowKind::Score => $throw->value.'点',
                    ThrowKind::Miss => 'ミス',
                },
            ])->values()->all(),
            'has_throws' => $game->throws->isNotEmpty(),
        ];
    }
}
