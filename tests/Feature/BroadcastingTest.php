<?php

use App\Events\CircuitBoardChanged;
use App\Events\CircuitCursorMoved;
use App\Events\CircuitDirectoryChanged;
use App\Events\CircuitPresenceChanged;
use App\Models\Circuit;
use App\Models\CircuitNode;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

it('broadcasts a directory event when a circuit is created', function () {
    Event::fake([CircuitDirectoryChanged::class]);
    actingAsParticipant();

    $this->postJson(route('circuits.store'), [
        'name' => 'Live board',
        'grid_size' => 24,
        'snap' => true,
    ])->assertCreated();

    Event::assertDispatched(CircuitDirectoryChanged::class, fn (CircuitDirectoryChanged $event) => $event->action === 'created'
        && $event->circuit['name'] === 'Live board');
});

it('broadcasts a board snapshot when a gate is placed', function () {
    Event::fake([CircuitBoardChanged::class, CircuitDirectoryChanged::class]);
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();

    $this->postJson(route('circuits.nodes.store', $circuit), [
        'uuid' => (string) Str::uuid(),
        'type' => 'and',
        'x' => 40,
        'y' => 40,
    ])->assertCreated();

    Event::assertDispatched(CircuitBoardChanged::class, fn (CircuitBoardChanged $event) => $event->circuitUuid === $circuit->uuid
        && count($event->nodes) === 1);
});

it('broadcasts presence when joining a circuit', function () {
    Event::fake([CircuitPresenceChanged::class]);
    $circuit = Circuit::factory()->create();
    actingAsParticipant();

    $this->get(route('circuits.show', $circuit))->assertOk();

    Event::assertDispatched(CircuitPresenceChanged::class, fn (CircuitPresenceChanged $event) => $event->circuitUuid === $circuit->uuid
        && $event->liveCount === 1);
});

it('broadcasts a cursor move over the circuit channel', function () {
    Event::fake([CircuitCursorMoved::class, CircuitPresenceChanged::class]);
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();

    $this->patchJson(route('circuits.presence.update', $circuit), [
        'cursor_x' => 80,
        'cursor_y' => 24,
    ])->assertOk();

    Event::assertDispatched(CircuitCursorMoved::class, fn (CircuitCursorMoved $event) => $event->circuitUuid === $circuit->uuid
        && $event->presence['cursor_x'] === 80);
});

it('broadcasts a deleted circuit so other screens can drop it', function () {
    Event::fake([CircuitDirectoryChanged::class]);
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();

    $this->deleteJson(route('circuits.destroy', $circuit))->assertNoContent();

    Event::assertDispatched(CircuitDirectoryChanged::class, fn (CircuitDirectoryChanged $event) => $event->action === 'deleted'
        && $event->uuid === $circuit->uuid);
});

it('still returns a sync snapshot after a remote change', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create(['revision' => 2]);
    $node = CircuitNode::factory()->for($circuit)->create();

    $this->getJson(route('circuits.sync', $circuit).'?revision=0')
        ->assertOk()
        ->assertJsonPath('unchanged', false)
        ->assertJsonPath('nodes.0.uuid', $node->uuid);
});
