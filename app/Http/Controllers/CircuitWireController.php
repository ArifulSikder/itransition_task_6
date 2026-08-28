<?php

namespace App\Http\Controllers;

use App\Actions\BumpCircuitRevision;
use App\Http\Requests\StoreCircuitWireRequest;
use App\Http\Resources\CircuitWireResource;
use App\Models\Circuit;
use App\Models\CircuitWire;
use Illuminate\Http\JsonResponse;

class CircuitWireController extends Controller
{
    public function store(
        StoreCircuitWireRequest $request,
        Circuit $circuit,
        BumpCircuitRevision $bumpCircuitRevision,
    ): JsonResponse {
        $wire = $circuit->wires()->create([
            'uuid' => $request->validated('uuid'),
            'from_node_id' => $request->fromNode()->id,
            'to_node_id' => $request->toNode()->id,
            'from_port' => $request->integer('from_port'),
            'to_port' => $request->integer('to_port'),
        ]);

        $wire->load(['fromNode', 'toNode']);
        $bumpCircuitRevision->handle($circuit);

        return response()->json([
            ...CircuitWireResource::make($wire)->toArray($request),
            'revision' => $circuit->revision,
        ], 201);
    }

    public function destroy(
        Circuit $circuit,
        CircuitWire $wire,
        BumpCircuitRevision $bumpCircuitRevision,
    ): JsonResponse {
        $wire->delete();
        $bumpCircuitRevision->handle($circuit);

        return response()->json(['revision' => $circuit->revision]);
    }
}
