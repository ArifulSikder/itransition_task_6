<?php

use App\Enums\GateType;
use App\Models\Circuit;
use App\Models\CircuitNode;
use Illuminate\Support\Str;

it('rejects a gate without a type', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();

    $this->postJson(route('circuits.nodes.store', $circuit), [
        'uuid' => (string) Str::uuid(),
        'x' => 10,
        'y' => 10,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['type' => 'The type field is required.']);
});

it('places a gate on a circuit', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();
    $uuid = (string) Str::uuid();

    $this->postJson(route('circuits.nodes.store', $circuit), [
        'uuid' => $uuid,
        'type' => 'and',
        'x' => 120,
        'y' => 80,
    ])->assertCreated()
        ->assertJsonPath('uuid', $uuid)
        ->assertJsonPath('type', 'and')
        ->assertJsonPath('label', 'AND')
        ->assertJsonPath('revision', 1);

    $this->assertDatabaseHas('circuit_nodes', [
        'uuid' => $uuid,
        'circuit_id' => $circuit->id,
        'type' => GateType::And->value,
    ]);
});

it('numbers input labels sequentially', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();

    $this->postJson(route('circuits.nodes.store', $circuit), [
        'uuid' => (string) Str::uuid(),
        'type' => 'input',
        'x' => 40,
        'y' => 40,
    ])->assertCreated()->assertJsonPath('label', 'IN 1');

    $this->postJson(route('circuits.nodes.store', $circuit), [
        'uuid' => (string) Str::uuid(),
        'type' => 'input',
        'x' => 40,
        'y' => 120,
    ])->assertCreated()->assertJsonPath('label', 'IN 2');
});

it('moves a gate and toggles an input', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();
    $node = CircuitNode::factory()->input()->for($circuit)->create([
        'x' => 10,
        'y' => 10,
        'value' => false,
    ]);

    $this->patchJson(route('circuits.nodes.update', [$circuit, $node]), [
        'x' => 200,
        'y' => 160,
        'value' => true,
    ])->assertOk()
        ->assertJsonPath('x', 200)
        ->assertJsonPath('y', 160)
        ->assertJsonPath('value', true);
});

it('returns 404 when a node is addressed on the wrong circuit', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();
    $other = Circuit::factory()->create();
    $node = CircuitNode::factory()->for($other)->create();

    $this->patchJson(route('circuits.nodes.update', [$circuit, $node]), [
        'x' => 50,
    ])->assertNotFound();
});

it('deletes a gate', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();
    $node = CircuitNode::factory()->for($circuit)->create();

    $this->deleteJson(route('circuits.nodes.destroy', [$circuit, $node]))
        ->assertOk()
        ->assertJsonPath('revision', 1);

    $this->assertModelMissing($node);
});
