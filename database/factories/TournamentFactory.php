<?php

namespace Database\Factories;

use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'format' => TournamentFormat::RoundRobin,
            'participant_type' => ParticipantType::Individual,
            'target_score' => 50,
            'reset_score' => 25,
            'max_consecutive_misses' => 3,
            'status' => TournamentStatus::Preparing,
        ];
    }
}
