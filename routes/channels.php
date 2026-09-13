<?php

use App\Models\Participant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('dashboard', function (mixed $participant): bool {
    return $participant instanceof Participant;
});

Broadcast::channel('circuit.{uuid}', function (mixed $participant, string $uuid): array|false {
    if (! $participant instanceof Participant) {
        return false;
    }

    return [
        'id' => $participant->uuid,
        'name' => $participant->name,
    ];
});
