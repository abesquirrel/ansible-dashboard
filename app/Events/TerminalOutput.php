<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TerminalOutput implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $sessionId,
        public readonly string $data
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("terminal.{$this->sessionId}");
    }

    public function broadcastAs(): string
    {
        return 'terminal.data';
    }
}
