<?php

namespace App\Http\Controllers;

use App\Models\PlaybookJob;
use App\Models\AuditLog;
use App\Models\ScheduledJob;
use App\Services\AnsibleSSHService;

class DashboardController extends Controller
{
    public function index(AnsibleSSHService $ssh)
    {
        $connectionStatus = cache()->remember('ssh_status', 60, fn () => $ssh->testConnection());

        $stats = [
            'jobs_today'    => PlaybookJob::whereDate('created_at', today())->count(),
            'jobs_running'  => PlaybookJob::whereIn('status', ['queued','running'])->count(),
            'jobs_failed'   => PlaybookJob::where('status', 'failed')->whereDate('created_at', today())->count(),
            'jobs_success'  => PlaybookJob::where('status', 'success')->whereDate('created_at', today())->count(),
        ];

        $recentJobs = PlaybookJob::with('user')
            ->latest()
            ->limit(10)
            ->get();

        $jobTrend = PlaybookJob::selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(status="success") as success, SUM(status="failed") as failed')
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $scheduledJobs = ScheduledJob::where('enabled', true)->count();

        return view('dashboard.index', compact(
            'connectionStatus', 'stats', 'recentJobs', 'jobTrend', 'scheduledJobs'
        ));
    }

    public function connectionStatus(AnsibleSSHService $ssh)
    {
        cache()->forget('ssh_status');
        return response()->json($ssh->testConnection());
    }
}
