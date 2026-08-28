<?php

namespace App\Http\Controllers;

use App\Models\Circuit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $participant = $this->participant($request);

        $circuits = Circuit::query()
            ->with(['creator', 'livePresences.participant'])
            ->withCount(['nodes', 'livePresences'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return view('app', [
            'page' => 'dashboard',
            'title' => 'Circuits',
            'pageProps' => [
                'participantName' => $participant->name,
                'circuits' => $circuits->map(fn (Circuit $circuit): array => [
                    'uuid' => $circuit->uuid,
                    'name' => $circuit->name,
                    'grid_size' => $circuit->grid_size,
                    'url' => route('circuits.show', $circuit),
                    'node_count' => $circuit->nodes_count,
                    'live_count' => $circuit->live_presences_count,
                    'presences' => $circuit->livePresences->map(fn ($presence): array => [
                        'name' => $presence->participant->name,
                        'color' => $presence->color,
                    ])->values(),
                ])->values(),
                'gridSizes' => config('circuits.grid_sizes'),
                'defaultGridSize' => config('circuits.default_grid_size'),
                'storeUrl' => route('circuits.store'),
                'listUrl' => route('circuits.index'),
                'leaveUrl' => route('session.destroy'),
                'csrf' => csrf_token(),
            ],
        ]);
    }
}
