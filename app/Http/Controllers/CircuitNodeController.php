<?php

namespace App\Http\Controllers;

use App\Actions\BumpCircuitRevision;
use App\Enums\GateType;
use App\Http\Requests\StoreCircuitNodeRequest;
use App\Http\Requests\UpdateCircuitNodeRequest;
use App\Http\Resources\CircuitNodeResource;
use App\Models\Circuit;
use App\Models\CircuitNode;
use Illuminate\Http\JsonResponse;

class CircuitNodeController extends Controller
{
    public function store(
        StoreCircuitNodeRequest $request,
        Circuit $circuit,
        BumpCircuitRevision $bumpCircuitRevision,
    ): JsonResponse {
        $type = GateType::from($request->validated('type'));

        $node = $circuit->nodes()->create([
            'uuid' => $request->validated('uuid'),
            'type' => $type,
            'x' => $request->integer('x'),
            'y' => $request->integer('y'),
            'label' => $request->validated('label') ?: $this->nextLabel($circuit, $type),
            'value' => $type === GateType::Input ? $request->boolean('value') : false,
        ]);

        $bumpCircuitRevision->handle($circuit);

        return response()->json([
            ...CircuitNodeResource::make($node)->toArray($request),
            'revision' => $circuit->revision,
        ], 201);
    }

    public function update(
        UpdateCircuitNodeRequest $request,
        Circuit $circuit,
        CircuitNode $node,
        BumpCircuitRevision $bumpCircuitRevision,
    ): JsonResponse {
        $attributes = $request->safe()->only(['x', 'y', 'label']);

        if ($node->type === GateType::Input && $request->exists('value')) {
            $attributes['value'] = $request->boolean('value');
        }

        $node->fill($attributes);
        $node->save();

        $bumpCircuitRevision->handle($circuit);

        return response()->json([
            ...CircuitNodeResource::make($node)->toArray($request),
            'revision' => $circuit->revision,
        ]);
    }

    public function destroy(
        Circuit $circuit,
        CircuitNode $node,
        BumpCircuitRevision $bumpCircuitRevision,
    ): JsonResponse {
        $node->delete();
        $bumpCircuitRevision->handle($circuit);

        return response()->json(['revision' => $circuit->revision]);
    }

    private function nextLabel(Circuit $circuit, GateType $type): string
    {
        if ($type !== GateType::Input && $type !== GateType::Output) {
            return $type->label();
        }

        $used = $circuit->nodes()->where('type', $type)->pluck('label');
        $index = 1;

        do {
            $label = $type->label().' '.$index;
            $index++;
        } while ($used->contains($label));

        return $label;
    }
}
