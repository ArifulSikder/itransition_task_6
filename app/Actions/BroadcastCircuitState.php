<?php

namespace App\Actions;

use App\Events\CircuitBoardChanged;
use App\Events\CircuitDirectoryChanged;
use App\Events\CircuitPresenceChanged;
use App\Http\Resources\CircuitNodeResource;
use App\Http\Resources\CircuitPresenceResource;
use App\Http\Resources\CircuitResource;
use App\Http\Resources\CircuitWireResource;
use App\Models\Circuit;

class BroadcastCircuitState
{
    public function directory(Circuit $circuit, string $action = 'updated'): void
    {
        $circuit->load(['creator', 'livePresences.participant'])->loadCount(['nodes', 'livePresences']);

        broadcast(new CircuitDirectoryChanged(
            $action,
            CircuitResource::make($circuit)->resolve(),
        ));
    }

    public function deleted(string $uuid): void
    {
        broadcast(new CircuitDirectoryChanged('deleted', uuid: $uuid));
    }

    public function board(Circuit $circuit): void
    {
        $circuit->load([
            'creator',
            'nodes',
            'wires.fromNode',
            'wires.toNode',
            'livePresences.participant',
        ]);

        broadcast(new CircuitBoardChanged(
            $circuit->uuid,
            $circuit->revision,
            CircuitResource::make($circuit)->resolve(),
            CircuitNodeResource::collection($circuit->nodes)->resolve(),
            CircuitWireResource::collection($circuit->wires)->resolve(),
        ))->toOthers();
    }

    public function presence(Circuit $circuit): void
    {
        $circuit->unsetRelation('livePresences');
        $circuit->load(['livePresences.participant'])->loadCount(['livePresences']);

        broadcast(new CircuitPresenceChanged(
            $circuit->uuid,
            CircuitPresenceResource::collection($circuit->livePresences)->resolve(),
            (int) $circuit->live_presences_count,
        ));
    }
}
