<?php

namespace App\Http\Resources;

use App\Models\CircuitPresence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CircuitPresence
 */
class CircuitPresenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'participant_uuid' => $this->participant->uuid,
            'name' => $this->participant->name,
            'color' => $this->color,
            'cursor_x' => $this->cursor_x,
            'cursor_y' => $this->cursor_y,
        ];
    }
}
