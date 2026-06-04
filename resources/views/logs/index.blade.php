@extends('layouts.app')
@section('title', 'Audit Log')

@section('content')
<div class="page-header">
    <div style="padding-bottom:20px">
        <h1 class="page-title">Audit Log</h1>
        <p class="page-subtitle">All SSH commands executed through the dashboard</p>
    </div>
</div>

<div class="page-body">
    <div class="card mb-4">
        <div class="card-body" style="padding:14px 18px">
            <form method="GET" class="flex gap-2 items-center flex-wrap">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" style="width:220px" placeholder="Search commands…">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input" style="width:160px">
                <select name="exit_code" class="form-select" style="width:140px">
                    <option value="">Any exit code</option>
                    <option value="0" {{ request('exit_code')==='0'?'selected':'' }}>Success (0)</option>
                    <option value="1" {{ request('exit_code')==='1'?'selected':'' }}>Failed (1)</option>
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <a href="{{ route('logs.index') }}" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Command</th>
                        <th>Source</th>
                        <th>Exit</th>
                        <th>Duration</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-mono text-xs text-muted" style="white-space:nowrap">
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="text-sm">{{ $log->user?->name ?? '—' }}</td>
                        <td>
                            <code style="font-family:var(--font-mono);font-size:11px;color:var(--text-code);word-break:break-all">
                                {{ Str::limit($log->command, 120) }}
                            </code>
                        </td>
                        <td>
                            <span class="badge" style="background:var(--bg-surface);color:var(--text-secondary)">
                                {{ $log->source }}
                            </span>
                        </td>
                        <td>
                            <span class="text-mono text-xs" style="color:{{ $log->exit_code === 0 ? 'var(--green)' : 'var(--red)' }}">
                                {{ $log->exit_code ?? '—' }}
                            </span>
                        </td>
                        <td class="text-mono text-xs text-muted">
                            {{ $log->duration_ms ? $log->duration_ms.'ms' : '—' }}
                        </td>
                        <td class="text-mono text-xs text-muted">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted);font-family:var(--font-mono)">No log entries found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div style="padding:14px 18px;border-top:1px solid var(--border)">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
