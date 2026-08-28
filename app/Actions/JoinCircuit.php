<?php

namespace App\Actions;

use App\Models\Circuit;
use App\Models\CircuitPresence;
use App\Models\Participant;
use App\Support\PresenceColor;

class JoinCircuit
{
    public function handle(Circuit $circuit, Participant $participant): CircuitPresence
    {
        $presence = CircuitPresence::query()->firstOrNew([
            'circuit_id' => $circuit->id,
            'participant_id' => $participant->id,
        ]);

        if (! $presence->exists) {
            $presence->color = (new PresenceColor)->next(
                $circuit->presences()->pluck('color'),
            );
        } elseif ($presence->last_seen_at?->gt(now()->subSeconds(2))) {
            return $presence;
        }

        $presence->last_seen_at = now();
        $presence->save();

        return $presence;
    }
}
