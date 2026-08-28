<?php

namespace App\Models;

use App\Enums\GateType;
use Database\Factories\CircuitNodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'circuit_id', 'type', 'x', 'y', 'label', 'value'])]
class CircuitNode extends Model
{
    /** @use HasFactory<CircuitNodeFactory> */
    use HasFactory, HasUuids;

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
            'type' => GateType::class,
            'x' => 'integer',
            'y' => 'integer',
            'value' => 'boolean',
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
     * @return HasMany<CircuitWire, $this>
     */
    public function outgoingWires(): HasMany
    {
        return $this->hasMany(CircuitWire::class, 'from_node_id');
    }

    /**
     * @return HasMany<CircuitWire, $this>
     */
    public function incomingWires(): HasMany
    {
        return $this->hasMany(CircuitWire::class, 'to_node_id');
    }
}
