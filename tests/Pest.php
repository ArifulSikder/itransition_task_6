<?php

use App\Models\Participant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

function actingAsParticipant(?Participant $participant = null): Participant
{
    $participant ??= Participant::factory()->create();

    test()->withSession(['participant_id' => $participant->id]);

    return $participant;
}
