<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class CircuitBoardChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array<string, mixed>  $circuit
     * @param  list<array<string, mixed>>  $nodes
     * @param  list<array<string, mixed>>  $wires
     */
    public function __construct(
        public string $circuitUuid,
        public int $revision,
        public array $circuit,
        public array $nodes,
        public array $wires,
    ) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel('circuit.'.$this->circuitUuid)];
    }

    public function broadcastAs(): string
    {
        return 'circuit.board';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'revision' => $this->revision,
            'circuit' => $this->circuit,
            'nodes' => $this->nodes,
            'wires' => $this->wires,
            'unchanged' => false,
        ];
    }
}
