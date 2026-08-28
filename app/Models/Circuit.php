<?php

namespace App\Models;

use Database\Factories\CircuitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'participant_id', 'name', 'grid_size', 'snap', 'revision'])]
class Circuit extends Model
{
    /** @use HasFactory<CircuitFactory> */
    use HasFactory, HasUuids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'grid_size' => 24,
        'snap' => true,
        'revision' => 0,
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grid_size' => 'integer',
            'snap' => 'boolean',
            'revision' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }

    /**
     * @return HasMany<CircuitNode, $this>
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(CircuitNode::class);
    }

    /**
     * @return HasMany<CircuitWire, $this>
     */
    public function wires(): HasMany
    {
        return $this->hasMany(CircuitWire::class);
    }

    /**
     * @return HasMany<CircuitPresence, $this>
     */
    public function presences(): HasMany
    {
        return $this->hasMany(CircuitPresence::class);
    }

    /**
     * @return HasMany<CircuitPresence, $this>
     */
    public function livePresences(): HasMany
    {
        return $this->hasMany(CircuitPresence::class)->where(
            'last_seen_at',
            '>=',
            now()->subSeconds((int) config('circuits.presence_ttl_seconds')),
        );
    }
}
