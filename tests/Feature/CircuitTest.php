<?php

use App\Models\Circuit;

it('returns 401 json when a guest requests the circuit list', function () {
    $this->getJson(route('circuits.index'))
        ->assertUnauthorized();
});

it('creates a circuit and opens it', function () {
    $participant = actingAsParticipant();

    $response = $this->post(route('circuits.store'), [
        'name' => 'ALU slice',
        'grid_size' => 32,
        'snap' => '1',
    ]);

    $circuit = Circuit::query()->first();

    expect($circuit)->not->toBeNull()
        ->and($circuit->name)->toBe('ALU slice')
        ->and($circuit->grid_size)->toBe(32)
        ->and($circuit->snap)->toBeTrue()
        ->and($circuit->participant_id)->toBe($participant->id);

    $response->assertRedirectToRoute('circuits.show', $circuit);
});

it('rejects an invalid grid size', function () {
    actingAsParticipant();

    $this->from(route('dashboard'))
        ->post(route('circuits.store'), [
            'name' => 'Broken',
            'grid_size' => 13,
        ])
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors(['grid_size' => 'The selected grid size is invalid.']);

    $this->assertDatabaseCount('circuits', 0);
});

it('lets a teammate connect to an existing circuit', function () {
    $circuit = Circuit::factory()->create(['name' => 'Shared board']);
    actingAsParticipant();

    $this->get(route('circuits.show', $circuit))
        ->assertOk()
        ->assertSee('data-page="editor"', false)
        ->assertSee('Shared board');

    $this->assertDatabaseHas('circuit_presences', [
        'circuit_id' => $circuit->id,
    ]);
});

it('updates circuit options and bumps the revision', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create([
        'name' => 'Old name',
        'grid_size' => 24,
        'revision' => 0,
    ]);

    $this->patchJson(route('circuits.update', $circuit), [
        'name' => 'Clock divider',
        'grid_size' => 48,
        'snap' => false,
    ])->assertOk()
        ->assertJsonPath('name', 'Clock divider')
        ->assertJsonPath('grid_size', 48)
        ->assertJsonPath('snap', false)
        ->assertJsonPath('revision', 1);
});

it('forbids deleting a circuit created by someone else', function () {
    $circuit = Circuit::factory()->create();
    actingAsParticipant();

    $this->deleteJson(route('circuits.destroy', $circuit))
        ->assertForbidden();

    $this->assertModelExists($circuit);
});

it('lets the creator delete a circuit', function () {
    $participant = actingAsParticipant();
    $circuit = Circuit::factory()->for($participant, 'creator')->create();

    $this->deleteJson(route('circuits.destroy', $circuit))
        ->assertNoContent();

    $this->assertModelMissing($circuit);
});
