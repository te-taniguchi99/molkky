<?php

namespace App\Models;

use App\Enums\GameStatus;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 試合。
 *
 * @property int $id
 * @property int $tournament_id
 * @property int $round
 * @property int $position
 * @property GameStatus $status
 * @property bool $is_bye
 * @property int|null $winner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tournament_id', 'round', 'position', 'status', 'is_bye', 'winner_id'])]
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GameStatus::class,
            'is_bye' => 'boolean',
        ];
    }

    /**
     * この試合が行われる大会。
     *
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * この試合に出る参加者(投げる順番の順)。
     *
     * @return BelongsToMany<Participant, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'game_participants')
            ->withPivot('throw_order')
            ->withTimestamps()
            ->orderByPivot('throw_order');
    }

    /**
     * この試合の投擲の記録(投げた順)。
     *
     * @return HasMany<GameThrow, $this>
     */
    public function throws(): HasMany
    {
        return $this->hasMany(GameThrow::class)->orderBy('id');
    }

    /**
     * 勝者。
     *
     * @return BelongsTo<Participant, $this>
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'winner_id');
    }
}
