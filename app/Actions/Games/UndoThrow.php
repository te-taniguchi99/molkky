<?php

namespace App\Actions\Games;

use App\Enums\GameStatus;
use App\Models\Game;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 最後の1投を取り消す(1つ前に戻す)。
 */
class UndoThrow
{
    public function __construct(private SyncGame $syncGame) {}

    /**
     * @throws ValidationException
     */
    public function __invoke(Game $game): void
    {
        DB::transaction(function () use ($game) {
            $game = Game::query()->lockForUpdate()->with('tournament')->findOrFail($game->id);

            $last = $game->throws()->reorder('id', 'desc')->first();

            if ($last === null) {
                throw ValidationException::withMessages(['throw' => '取り消せる記録がありません。']);
            }

            // 終了した試合を取り消すと、次の回戦の組み合わせが変わってしまうため、
            // 次の回戦がすでに作られているときは、取り消せない
            if ($game->status === GameStatus::Finished
                && $game->tournament->games()->where('round', '>', $game->round)->exists()) {
                throw ValidationException::withMessages([
                    'throw' => '次の回戦の試合がすでに作られているため、取り消せません。',
                ]);
            }

            $last->delete();

            ($this->syncGame)($game->refresh());
        });
    }
}
