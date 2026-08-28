<?php

use App\Models\Circuit;
use App\Models\CircuitPresence;
use App\Models\Participant;

it('redirects guests away from the lab', function () {
    $this->get(route('dashboard'))
        ->assertRedirectToRoute('home');
});

it('lists live circuits for a named visitor', function () {
    $participant = actingAsParticipant(Participant::factory()->create(['name' => 'Ada']));
    $circuit = Circuit::factory()->for($participant, 'creator')->create(['name' => 'Half adder']);
    CircuitPresence::factory()->create([
        'circuit_id' => $circuit->id,
        'participant_id' => $participant->id,
        'last_seen_at' => now(),
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-page="dashboard"', false)
        ->assertSee('Half adder')
        ->assertSee('Ada');
});
