<?php

use App\Models\Participant;

it('renders the name gate for guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-page="home"', false);
});

it('redirects named visitors to the lab', function () {
    actingAsParticipant();

    $this->get(route('home'))
        ->assertRedirectToRoute('dashboard');
});

it('creates a participant from a display name', function () {
    $this->post(route('session.store'), ['name' => 'Ada'])
        ->assertRedirectToRoute('dashboard');

    $this->assertDatabaseHas('participants', ['name' => 'Ada']);
});

it('appends a number when the display name is already in use', function () {
    Participant::factory()->create(['name' => 'John']);

    $this->post(route('session.store'), ['name' => 'John'])
        ->assertRedirectToRoute('dashboard');

    $this->assertDatabaseHas('participants', ['name' => 'John 2']);
});

it('rejects a blank name', function () {
    $this->from(route('home'))
        ->post(route('session.store'), ['name' => '   '])
        ->assertRedirectToRoute('home')
        ->assertSessionHasErrors(['name' => 'The name field is required.']);

    $this->assertDatabaseCount('participants', 0);
});

it('escapes a dangerous display name on the lab', function () {
    $this->post(route('session.store'), ['name' => '<script>alert(1)</script>'])
        ->assertRedirectToRoute('dashboard');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('\\u003Cscript\\u003Ealert(1)\\u003C\\/script\\u003E', false);
});
