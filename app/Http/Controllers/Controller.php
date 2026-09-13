<?php

namespace App\Http\Controllers;

use App\Models\Circuit;
use App\Models\CircuitPresence;
use App\Models\Participant;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function participant(Request $request): Participant
    {
        $participant = $request->attributes->get('participant');

        abort_unless($participant instanceof Participant, 401);

        return $participant;
    }

    protected function participantIsLive(Circuit $circuit, Participant $participant): bool
    {
        return CircuitPresence::query()
            ->where('circuit_id', $circuit->id)
            ->where('participant_id', $participant->id)
            ->where(
                'last_seen_at',
                '>=',
                now()->subSeconds((int) config('circuits.presence_ttl_seconds')),
            )
            ->exists();
    }
}
