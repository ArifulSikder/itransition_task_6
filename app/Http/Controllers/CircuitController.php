<?php

namespace App\Http\Controllers;

use App\Actions\BumpCircuitRevision;
use App\Actions\JoinCircuit;
use App\Http\Requests\StoreCircuitRequest;
use App\Http\Requests\UpdateCircuitRequest;
use App\Http\Resources\CircuitResource;
use App\Models\Circuit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;

class CircuitController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $circuits = Circuit::query()
            ->with(['creator', 'livePresences.participant'])
            ->withCount(['nodes', 'livePresences'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return CircuitResource::collection($circuits);
    }

    public function store(StoreCircuitRequest $request): RedirectResponse|JsonResponse
    {
        $participant = $this->participant($request);

        $circuit = Circuit::query()->create([
            ...$request->safe()->only(['name', 'grid_size']),
            'snap' => $request->boolean('snap', true),
            'participant_id' => $participant->id,
        ]);

        if ($request->expectsJson()) {
            $circuit->load(['creator', 'livePresences.participant'])->loadCount(['nodes', 'livePresences']);

            return CircuitResource::make($circuit)
                ->response()
                ->setStatusCode(201);
        }

        return redirect()->route('circuits.show', $circuit);
    }

    public function show(Request $request, Circuit $circuit, JoinCircuit $joinCircuit): View
    {
        $joinCircuit->handle($circuit, $this->participant($request));

        $circuit->load([
            'creator',
            'nodes',
            'wires.fromNode',
            'wires.toNode',
            'livePresences.participant',
        ]);

        $participant = $this->participant($request);

        return view('app', [
            'page' => 'editor',
            'title' => $circuit->name,
            'pageProps' => [
                'boot' => [
                    'circuit' => [
                        'uuid' => $circuit->uuid,
                        'name' => $circuit->name,
                        'grid_size' => $circuit->grid_size,
                        'snap' => $circuit->snap,
                        'revision' => $circuit->revision,
                    ],
                    'nodes' => $circuit->nodes->map(fn ($node): array => [
                        'uuid' => $node->uuid,
                        'type' => $node->type->value,
                        'x' => $node->x,
                        'y' => $node->y,
                        'label' => $node->label,
                        'value' => $node->value,
                    ])->values(),
                    'wires' => $circuit->wires->map(fn ($wire): array => [
                        'uuid' => $wire->uuid,
                        'from_node_uuid' => $wire->fromNode->uuid,
                        'to_node_uuid' => $wire->toNode->uuid,
                        'from_port' => $wire->from_port,
                        'to_port' => $wire->to_port,
                    ])->values(),
                    'presence' => $circuit->livePresences->map(fn ($presence): array => [
                        'participant_uuid' => $presence->participant->uuid,
                        'name' => $presence->participant->name,
                        'color' => $presence->color,
                        'cursor_x' => $presence->cursor_x,
                        'cursor_y' => $presence->cursor_y,
                    ])->values(),
                    'me' => [
                        'uuid' => $participant->uuid,
                        'name' => $participant->name,
                    ],
                    'routes' => [
                        'sync' => route('circuits.sync', $circuit),
                        'update' => route('circuits.update', $circuit),
                        'nodes' => route('circuits.nodes.store', $circuit),
                        'wires' => route('circuits.wires.store', $circuit),
                        'presence' => route('circuits.presence.update', $circuit),
                        'leave' => route('circuits.presence.destroy', $circuit),
                        'dashboard' => route('dashboard'),
                    ],
                ],
                'gridSizes' => config('circuits.grid_sizes'),
                'canDelete' => $circuit->participant_id === $participant->id,
                'deleteUrl' => route('circuits.destroy', $circuit),
                'csrf' => csrf_token(),
            ],
        ]);
    }

    public function update(
        UpdateCircuitRequest $request,
        Circuit $circuit,
        BumpCircuitRevision $bumpCircuitRevision,
    ): CircuitResource {
        $circuit->fill($request->safe()->only(['name', 'grid_size', 'snap']));
        $circuit->save();

        $bumpCircuitRevision->handle($circuit);
        $circuit->load(['creator', 'livePresences.participant'])->loadCount(['nodes', 'livePresences']);

        return CircuitResource::make($circuit);
    }

    public function destroy(Request $request, Circuit $circuit): JsonResponse|RedirectResponse
    {
        abort_unless($circuit->participant_id === $this->participant($request)->id, 403);

        $circuit->delete();

        if ($request->expectsJson()) {
            return response()->json(status: 204);
        }

        return redirect()->route('dashboard');
    }
}
