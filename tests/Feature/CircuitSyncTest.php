<?php

use App\Models\Circuit;
use App\Models\CircuitNode;

it('returns a full snapshot when the revision is stale', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create(['revision' => 4]);
    $node = CircuitNode::factory()->input()->for($circuit)->create(['label' => 'IN 1']);

    $this->getJson(route('circuits.sync', $circuit).'?revision=0')
        ->assertOk()
        ->assertJsonPath('unchanged', false)
        ->assertJsonPath('revision', 4)
        ->assertJsonPath('nodes.0.uuid', $node->uuid)
        ->assertJsonPath('circuit.name', $circuit->name);
});

it('returns presence without nodes when the revision matches', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create(['revision' => 7]);
    CircuitNode::factory()->for($circuit)->create();

    $response = $this->getJson(route('circuits.sync', $circuit).'?revision=7');

    $response->assertOk()
        ->assertJsonPath('unchanged', true)
        ->assertJsonPath('revision', 7);

    expect($response->json())->not->toHaveKey('nodes');
});

it('updates a remote cursor', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();

    $this->patchJson(route('circuits.presence.update', $circuit), [
        'cursor_x' => 120,
        'cursor_y' => 40,
    ])->assertOk()
        ->assertJsonPath('cursor_x', 120)
        ->assertJsonPath('name', $participant->name);

    $this->assertDatabaseHas('circuit_presences', [
        'circuit_id' => $circuit->id,
        'participant_id' => $participant->id,
        'cursor_x' => 120,
        'cursor_y' => 40,
    ]);
});
