<?php

namespace Database\Factories;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'round' => 1,
            'position' => 1,
            'status' => GameStatus::Waiting,
            'is_bye' => false,
        ];
    }
}
