<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\AnsibleSSHService;
use App\Events\TerminalOutput;

class StreamingTerminalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries   = 1;

    public function __construct(
        public readonly string  $command,
        public readonly string  $sessionId,
        public readonly ?int    $userId = null
    ) {}

    public function handle(AnsibleSSHService $ssh): void
    {
        $ssh->execStreaming(
            $this->command,
            function (string $chunk) {
                broadcast(new TerminalOutput($this->sessionId, $chunk));
            },
            $this->userId
        );

        broadcast(new TerminalOutput($this->sessionId, "\r\n\x1b[90m[stream ended]\x1b[0m\r\n"));
    }
}
