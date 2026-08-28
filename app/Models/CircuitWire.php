<?php

namespace App\Models;

use Database\Factories\CircuitWireFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uuid', 'circuit_id', 'from_node_id', 'from_port', 'to_node_id', 'to_port'])]
class CircuitWire extends Model
{
    /** @use HasFactory<CircuitWireFactory> */
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
            'from_port' => 'integer',
            'to_port' => 'integer',
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
     * @return BelongsTo<CircuitNode, $this>
     */
    public function fromNode(): BelongsTo
    {
        return $this->belongsTo(CircuitNode::class, 'from_node_id');
    }

    /**
     * @return BelongsTo<CircuitNode, $this>
     */
    public function toNode(): BelongsTo
    {
        return $this->belongsTo(CircuitNode::class, 'to_node_id');
    }
}
