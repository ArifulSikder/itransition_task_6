<?php

namespace App\Http\Controllers;

use App\Actions\BroadcastCircuitState;
use App\Actions\JoinCircuit;
use App\Events\CircuitCursorMoved;
use App\Http\Requests\UpdateCircuitPresenceRequest;
use App\Http\Resources\CircuitPresenceResource;
use App\Models\Circuit;
use App\Models\CircuitPresence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CircuitPresenceController extends Controller
{
    public function update(
        UpdateCircuitPresenceRequest $request,
        Circuit $circuit,
        JoinCircuit $joinCircuit,
        BroadcastCircuitState $broadcastCircuitState,
    ): JsonResponse {
        $wasLive = $this->participantIsLive($circuit, $this->participant($request));
        $presence = $joinCircuit->handle($circuit, $this->participant($request));
        $hasCursor = $request->exists('cursor_x') || $request->exists('cursor_y');

        $presence->fill($request->safe()->only(['cursor_x', 'cursor_y']));
        $presence->last_seen_at = now();
        $presence->save();
        $presence->load('participant');

        if ($hasCursor) {
            broadcast(new CircuitCursorMoved(
                $circuit->uuid,
                CircuitPresenceResource::make($presence)->toArray($request),
            ))->toOthers();
        }

        if (! $wasLive || ! $hasCursor) {
            $broadcastCircuitState->presence($circuit);
        }

        return response()->json(CircuitPresenceResource::make($presence)->toArray($request));
    }

    public function destroy(Request $request, Circuit $circuit, BroadcastCircuitState $broadcastCircuitState): JsonResponse
    {
        CircuitPresence::query()
            ->where('circuit_id', $circuit->id)
            ->where('participant_id', $this->participant($request)->id)
            ->delete();

        $broadcastCircuitState->presence($circuit);

        return response()->json(status: 204);
    }
}
