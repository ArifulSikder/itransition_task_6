<?php

namespace Database\Factories;

use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->unique()->firstName(),
            'last_seen_at' => now(),
        ];
    }

    public function stale(): static
    {
        return $this->state(fn (array $attributes): array => [
            'last_seen_at' => now()->subHours(2),
        ]);
    }
}
