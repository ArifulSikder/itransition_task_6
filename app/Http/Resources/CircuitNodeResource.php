<?php

namespace App\Http\Resources;

use App\Models\CircuitNode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CircuitNode
 */
class CircuitNodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type->value,
            'x' => $this->x,
            'y' => $this->y,
            'label' => $this->label,
            'value' => $this->value,
        ];
    }
}
