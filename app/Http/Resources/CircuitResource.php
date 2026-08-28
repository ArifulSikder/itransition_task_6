<?php

namespace App\Http\Resources;

use App\Models\Circuit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Circuit
 */
class CircuitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'grid_size' => $this->grid_size,
            'snap' => $this->snap,
            'revision' => $this->revision,
            'url' => route('circuits.show', $this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'creator' => ParticipantResource::make($this->whenLoaded('creator')),
            'node_count' => $this->whenCounted('nodes'),
            'live_count' => $this->whenCounted('livePresences'),
            'presences' => CircuitPresenceResource::collection($this->whenLoaded('livePresences')),
        ];
    }
}
