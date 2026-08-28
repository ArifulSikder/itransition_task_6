<?php

use App\Models\Circuit;
use App\Models\CircuitNode;
use App\Models\CircuitWire;
use Illuminate\Support\Str;

it('connects two gates with a wire', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();
    $input = CircuitNode::factory()->input()->for($circuit)->create();
    $and = CircuitNode::factory()->for($circuit)->create();
    $uuid = (string) Str::uuid();

    $this->postJson(route('circuits.wires.store', $circuit), [
        'uuid' => $uuid,
        'from_node_uuid' => $input->uuid,
        'to_node_uuid' => $and->uuid,
        'from_port' => 0,
        'to_port' => 0,
    ])->assertCreated()
        ->assertJsonPath('uuid', $uuid)
        ->assertJsonPath('from_node_uuid', $input->uuid)
        ->assertJsonPath('to_node_uuid', $and->uuid);

    $this->assertDatabaseHas('circuit_wires', [
        'uuid' => $uuid,
        'circuit_id' => $circuit->id,
        'from_node_id' => $input->id,
        'to_node_id' => $and->id,
    ]);
});

it('rejects a wire onto a pin that is already used', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();
    $input = CircuitNode::factory()->input()->for($circuit)->create();
    $other = CircuitNode::factory()->input()->for($circuit)->create();
    $and = CircuitNode::factory()->for($circuit)->create();

    CircuitWire::factory()->create([
        'circuit_id' => $circuit->id,
        'from_node_id' => $input->id,
        'to_node_id' => $and->id,
        'to_port' => 0,
    ]);

    $this->postJson(route('circuits.wires.store', $circuit), [
        'uuid' => (string) Str::uuid(),
        'from_node_uuid' => $other->uuid,
        'to_node_uuid' => $and->uuid,
        'from_port' => 0,
        'to_port' => 0,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['to_port' => 'That input pin is already wired.']);
});

it('rejects wiring an input pin as a source', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();
    $output = CircuitNode::factory()->output()->for($circuit)->create();
    $and = CircuitNode::factory()->for($circuit)->create();

    $this->postJson(route('circuits.wires.store', $circuit), [
        'uuid' => (string) Str::uuid(),
        'from_node_uuid' => $output->uuid,
        'to_node_uuid' => $and->uuid,
        'from_port' => 0,
        'to_port' => 0,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['from_port' => 'That gate has no output pin there.']);
});

it('deletes a wire', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();
    $input = CircuitNode::factory()->input()->for($circuit)->create();
    $and = CircuitNode::factory()->for($circuit)->create();
    $wire = CircuitWire::factory()->create([
        'circuit_id' => $circuit->id,
        'from_node_id' => $input->id,
        'to_node_id' => $and->id,
    ]);

    $this->deleteJson(route('circuits.wires.destroy', [$circuit, $wire]))
        ->assertOk();

    $this->assertModelMissing($wire);
});
