<?php

namespace App\Models;

use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'name', 'last_seen_at'])]
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory, HasUuids;

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Circuit, $this>
     */
    public function circuits(): HasMany
    {
        return $this->hasMany(Circuit::class);
    }

    /**
     * @return HasMany<CircuitPresence, $this>
     */
    public function presences(): HasMany
    {
        return $this->hasMany(CircuitPresence::class);
    }

    #[Scope]
    protected function recentlyActive(Builder $query): Builder
    {
        return $query->where(
            'last_seen_at',
            '>=',
            now()->subMinutes((int) config('circuits.name_reservation_minutes')),
        );
    }
}
