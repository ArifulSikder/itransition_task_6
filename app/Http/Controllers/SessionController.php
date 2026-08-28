<?php

namespace App\Http\Controllers;

use App\Actions\ResolveDisplayName;
use App\Http\Requests\StoreSessionRequest;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (session()->has('participant_id') && Participant::query()->find(session('participant_id'))) {
            return redirect()->route('dashboard');
        }

        return view('app', [
            'page' => 'home',
            'title' => 'Circuits',
            'pageProps' => [
                'errors' => $request->session()->get('errors')?->getBag('default')?->get('name') ?? [],
                'storeUrl' => route('session.store'),
                'csrf' => csrf_token(),
                'oldName' => old('name', ''),
            ],
        ]);
    }

    public function store(StoreSessionRequest $request, ResolveDisplayName $resolveDisplayName): RedirectResponse
    {
        $participant = Participant::query()->create([
            'name' => $resolveDisplayName->handle($request->validated('name')),
            'last_seen_at' => now(),
        ]);

        $request->session()->regenerate();
        $request->session()->put('participant_id', $participant->id);

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('participant_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
