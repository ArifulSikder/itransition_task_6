<?php

namespace Database\Factories;

use App\Models\Circuit;
use App\Models\CircuitPresence;
use App\Models\Participant;
use App\Support\PresenceColor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CircuitPresence>
 */
class CircuitPresenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'circuit_id' => Circuit::factory(),
            'participant_id' => Participant::factory(),
            'color' => fake()->randomElement(PresenceColor::PALETTE),
            'cursor_x' => fake()->numberBetween(0, 400),
            'cursor_y' => fake()->numberBetween(0, 300),
            'last_seen_at' => now(),
        ];
    }
}
