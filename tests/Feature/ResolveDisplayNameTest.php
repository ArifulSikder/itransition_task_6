<?php

use App\Actions\ResolveDisplayName;
use App\Models\Participant;

it('keeps the first claim of a name', function () {
    expect(app(ResolveDisplayName::class)->handle('Ada'))->toBe('Ada');
});

it('increments while earlier names are still reserved', function () {
    Participant::factory()->create(['name' => 'John']);
    Participant::factory()->create(['name' => 'John 2']);

    expect(app(ResolveDisplayName::class)->handle('John'))->toBe('John 3');
});

it('reuses a name after it expires', function () {
    Participant::factory()->stale()->create(['name' => 'John']);

    expect(app(ResolveDisplayName::class)->handle('John'))->toBe('John');
});

it('does not treat a longer name as the same reservation', function () {
    Participant::factory()->create(['name' => 'Johnny']);

    expect(app(ResolveDisplayName::class)->handle('John'))->toBe('John');
});
