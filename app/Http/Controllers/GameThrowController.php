<?php

namespace App\Http\Controllers;

use App\Actions\Games\RecordThrow;
use App\Actions\Games\UndoThrow;
use App\Enums\ThrowKind;
use App\Http\Requests\StoreThrowRequest;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;

class GameThrowController extends Controller
{
    /**
     * 1投を記録する。
     */
    public function store(StoreThrowRequest $request, Game $game, RecordThrow $recordThrow): RedirectResponse
    {
        $validated = $request->validated();

        $recordThrow(
            $game,
            (int) $validated['participant_id'],
            ThrowKind::from($validated['kind']),
            (int) ($validated['value'] ?? 0),
            $request->user()->id,
        );

        return to_route('games.show', $game);
    }

    /**
     * 最後の1投を取り消す(1つ前に戻す)。
     */
    public function destroy(Game $game, UndoThrow $undoThrow): RedirectResponse
    {
        $undoThrow($game);

        return to_route('games.show', $game);
    }
}
