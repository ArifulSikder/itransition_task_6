<?php

namespace Database\Factories;

use App\Models\Circuit;
use App\Models\CircuitNode;
use App\Models\CircuitWire;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CircuitWire>
 */
class CircuitWireFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'circuit_id' => Circuit::factory(),
            'from_node_id' => CircuitNode::factory()->input(),
            'from_port' => 0,
            'to_node_id' => CircuitNode::factory(),
            'to_port' => 0,
        ];
    }
}
