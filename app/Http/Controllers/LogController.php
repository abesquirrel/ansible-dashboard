<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PlaybookJob;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        if ($request->search) {
            $query->where('command', 'like', "%{$request->search}%");
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->exit_code !== null) {
            $query->where('exit_code', $request->exit_code);
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('logs.index', compact('logs'));
    }

    public function jobHistory(Request $request)
    {
        $jobs = PlaybookJob::with('user')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->playbook, fn ($q) => $q->where('playbook', 'like', "%{$request->playbook}%"))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('logs.jobs', compact('jobs'));
    }
}
