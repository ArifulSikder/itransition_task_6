<?php

namespace App\Http\Controllers;

use App\Actions\JoinCircuit;
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
    ): JsonResponse {
        $presence = $joinCircuit->handle($circuit, $this->participant($request));

        $presence->fill($request->safe()->only(['cursor_x', 'cursor_y']));
        $presence->last_seen_at = now();
        $presence->save();
        $presence->load('participant');

        return response()->json(CircuitPresenceResource::make($presence)->toArray($request));
    }

    public function destroy(Request $request, Circuit $circuit): JsonResponse
    {
        CircuitPresence::query()
            ->where('circuit_id', $circuit->id)
            ->where('participant_id', $this->participant($request)->id)
            ->delete();

        return response()->json(status: 204);
    }
}
