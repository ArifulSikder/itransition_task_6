<?php

namespace App\Http\Middleware;

use App\Models\Participant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureParticipant
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $participant = Participant::query()->find(session('participant_id'));

        if ($participant === null) {
            session()->forget('participant_id');

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Enter a name to continue.'], 401);
            }

            return redirect()->route('home');
        }

        $heartbeatSeconds = (int) config('circuits.heartbeat_seconds');

        if ($participant->last_seen_at === null || $participant->last_seen_at->lt(now()->subSeconds($heartbeatSeconds))) {
            $participant->forceFill(['last_seen_at' => now()])->save();
        }

        $request->attributes->set('participant', $participant);
        View::share('participant', $participant);

        return $next($request);
    }
}
