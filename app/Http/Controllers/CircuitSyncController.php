<?php

namespace App\Http\Controllers;

use App\Actions\JoinCircuit;
use App\Http\Resources\CircuitNodeResource;
use App\Http\Resources\CircuitPresenceResource;
use App\Http\Resources\CircuitResource;
use App\Http\Resources\CircuitWireResource;
use App\Models\Circuit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CircuitSyncController extends Controller
{
    public function __invoke(Request $request, Circuit $circuit, JoinCircuit $joinCircuit): JsonResponse
    {
        $joinCircuit->handle($circuit, $this->participant($request));

        $clientRevision = $request->integer('revision', -1);

        $circuit->load([
            'creator',
            'livePresences.participant',
        ]);

        $payload = [
            'revision' => $circuit->revision,
            'circuit' => CircuitResource::make($circuit)->resolve(),
            'presence' => CircuitPresenceResource::collection($circuit->livePresences)->resolve(),
        ];

        if ($clientRevision === $circuit->revision) {
            return response()->json([
                ...$payload,
                'unchanged' => true,
            ]);
        }

        $circuit->load(['nodes', 'wires.fromNode', 'wires.toNode']);

        return response()->json([
            ...$payload,
            'unchanged' => false,
            'nodes' => CircuitNodeResource::collection($circuit->nodes)->resolve(),
            'wires' => CircuitWireResource::collection($circuit->wires)->resolve(),
        ]);
    }
}
