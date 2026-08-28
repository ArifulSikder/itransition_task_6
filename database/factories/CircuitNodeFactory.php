<?php

namespace Database\Factories;

use App\Enums\GateType;
use App\Models\Circuit;
use App\Models\CircuitNode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CircuitNode>
 */
class CircuitNodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'circuit_id' => Circuit::factory(),
            'type' => GateType::And,
            'x' => fake()->numberBetween(40, 400),
            'y' => fake()->numberBetween(40, 300),
            'label' => 'AND',
            'value' => false,
        ];
    }

    public function input(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => GateType::Input,
            'label' => 'IN 1',
            'value' => false,
        ]);
    }

    public function output(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => GateType::Output,
            'label' => 'OUT 1',
            'value' => false,
        ]);
    }
}
