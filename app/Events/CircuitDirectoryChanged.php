<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class CircuitDirectoryChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array<string, mixed>  $circuit
     */
    public function __construct(
        public string $action,
        public array $circuit = [],
        public ?string $uuid = null,
    ) {
        $this->uuid ??= $this->circuit['uuid'] ?? null;
    }

    /**
     * @return array<int, PrivateChannel|PresenceChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('dashboard')];

        if ($this->action === 'deleted' && is_string($this->uuid)) {
            $channels[] = new PresenceChannel('circuit.'.$this->uuid);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'circuit.directory';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'uuid' => $this->uuid,
            'circuit' => $this->action === 'deleted' ? null : $this->circuit,
        ];
    }
}
