<?php

namespace App\Http\Controllers;

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
}
