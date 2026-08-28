<?php

namespace App\Models;

use Database\Factories\CircuitPresenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['circuit_id', 'participant_id', 'color', 'cursor_x', 'cursor_y', 'last_seen_at'])]
class CircuitPresence extends Model
{
    /** @use HasFactory<CircuitPresenceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cursor_x' => 'integer',
            'cursor_y' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Circuit, $this>
     */
    public function circuit(): BelongsTo
    {
        return $this->belongsTo(Circuit::class);
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    #[Scope]
    protected function live(Builder $query): Builder
    {
        return $query->where(
            'last_seen_at',
            '>=',
            now()->subSeconds((int) config('circuits.presence_ttl_seconds')),
        );
    }
}
