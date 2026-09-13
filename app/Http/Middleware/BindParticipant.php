<?php

namespace App\Http\Middleware;

use App\Models\Participant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BindParticipant
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $participant = Participant::query()->find(session('participant_id'));

        if ($participant instanceof Participant) {
            $request->setUserResolver(fn () => $participant);
        }

        return $next($request);
    }
}
