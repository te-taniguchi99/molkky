<?php

namespace App\Actions\Games;

use App\Enums\GameStatus;
use App\Enums\ThrowKind;
use App\Enums\TournamentStatus;
use App\Models\Game;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 1投を記録する。
 */
class RecordThrow
{
    public function __construct(private SyncGame $syncGame) {}

    /**
     * @throws ValidationException
     */
    public function __invoke(Game $game, int $participantId, ThrowKind $kind, int $value, int $userId): void
    {
        DB::transaction(function () use ($game, $participantId, $kind, $value, $userId) {
            // 同時に入力されても食い違わないように、試合の行をロックして、最新の状態を読み直す
            $game = Game::query()->lockForUpdate()->with('tournament')->findOrFail($game->id);

            if ($game->is_bye
                || $game->status === GameStatus::Finished
                || $game->tournament->status !== TournamentStatus::InProgress) {
                throw ValidationException::withMessages(['throw' => 'この試合には、記録できません。']);
            }

            // 画面に表示されていた手番と、いまの手番が、同じかを確かめる
            // (別の端末で先に入力されたときに、別の人の投擲として記録されるのを防ぐ)
            $current = ($this->syncGame)($game)['current_participant_id'];

            if ($current !== $participantId) {
                throw ValidationException::withMessages([
                    'participant_id' => '手番が変わりました。画面を更新して、もう一度入力してください。',
                ]);
            }

            $game->throws()->create([
                'participant_id' => $participantId,
                'user_id' => $userId,
                'kind' => $kind,
                'value' => $kind === ThrowKind::Miss ? 0 : $value,
            ]);

            // 記録を含めて、点数・勝者を計算し直す
            ($this->syncGame)($game->refresh());
        });
    }
}
