<?php

namespace App\Http\Resources;

use App\Models\CircuitWire;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CircuitWire
 */
class CircuitWireResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'from_node_uuid' => $this->whenLoaded('fromNode', fn () => $this->fromNode->uuid),
            'to_node_uuid' => $this->whenLoaded('toNode', fn () => $this->toNode->uuid),
            'from_port' => $this->from_port,
            'to_port' => $this->to_port,
        ];
    }
}
