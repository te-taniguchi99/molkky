<?php

namespace App\Models;

use App\Enums\ThrowKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 1投の記録。
 *
 * @property int $id
 * @property int $game_id
 * @property int $participant_id
 * @property int|null $user_id
 * @property ThrowKind $kind
 * @property int $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['game_id', 'participant_id', 'user_id', 'kind', 'value'])]
class GameThrow extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ThrowKind::class,
        ];
    }

    /**
     * この投擲が行われた試合。
     *
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * 投げた参加者。
     *
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
