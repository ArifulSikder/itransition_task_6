<?php

namespace Database\Factories;

use App\Models\Circuit;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Circuit>
 */
class CircuitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'participant_id' => Participant::factory(),
            'name' => fake()->words(3, true),
            'grid_size' => 24,
            'snap' => true,
            'revision' => 0,
        ];
    }
}
