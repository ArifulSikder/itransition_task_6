<?php

namespace App\Http\Controllers;

use App\Http\Resources\CircuitResource;
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
                'circuits' => CircuitResource::collection($circuits)->resolve(),
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
