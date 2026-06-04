<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AnsibleSSHService;
use App\Events\TerminalOutput;
use Illuminate\Support\Str;

class TerminalController extends Controller
{
    public function index()
    {
        return view('terminal.index');
    }

    /**
     * Execute a command and return output directly (non-interactive / command mode).
     */
    public function exec(Request $request, AnsibleSSHService $ssh)
    {
        $data = $request->validate([
            'command'    => 'required|string|max:2048',
            'session_id' => 'required|string',
        ]);

        $cmd = $data['command'];

        // Safety: block some dangerous operations unless admin
        $blocked = ['rm -rf /', 'mkfs', 'dd if=/dev/zero'];
        foreach ($blocked as $b) {
            if (stripos($cmd, $b) !== false && !auth()->user()->is_admin) {
                return response()->json(['error' => "Command blocked: {$b}"], 403);
            }
        }

        try {
            $result = $ssh->exec($cmd, auth()->id());
            return response()->json([
                'output'    => $result['output'],
                'exit_code' => $result['exit_code'],
                'duration'  => $result['duration_ms'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Start a streaming session (dispatches to queue, broadcasts via WebSocket).
     */
    public function stream(Request $request)
    {
        $data = $request->validate(['command' => 'required|string']);
        $sessionId = Str::uuid();

        \App\Jobs\StreamingTerminalJob::dispatch($data['command'], $sessionId, auth()->id());

        return response()->json(['session_id' => $sessionId]);
    }
}
