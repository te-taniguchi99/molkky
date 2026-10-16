<?php

namespace App\Models;

use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * 大会の参加者(個人戦は個人、チーム戦はチーム)。
 *
 * @property int $id
 * @property int $tournament_id
 * @property string $name
 * @property int $member_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tournament_id', 'name', 'member_count'])]
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    /**
     * この参加者が出る大会。
     *
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * この参加者が出る試合。
     *
     * @return BelongsToMany<Game, $this>
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_participants')
            ->withPivot('throw_order')
            ->withTimestamps();
    }
}
