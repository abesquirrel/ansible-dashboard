<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div style="padding-bottom:20px">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Infrastructure overview &amp; recent activity</p>
    </div>
</div>

<div class="page-body">

    
    <div class="stats-grid mb-6">
        <div class="stat-card green">
            <div class="stat-label">Success Today</div>
            <div class="stat-value"><?php echo e($stats['jobs_success']); ?></div>
        </div>
        <div class="stat-card <?php echo e($stats['jobs_running'] > 0 ? 'yellow' : 'blue'); ?>">
            <div class="stat-label">Running</div>
            <div class="stat-value"><?php echo e($stats['jobs_running']); ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-label">Failed Today</div>
            <div class="stat-value"><?php echo e($stats['jobs_failed']); ?></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-label">Total Today</div>
            <div class="stat-value"><?php echo e($stats['jobs_today']); ?></div>
        </div>
    </div>

    <div class="grid-2 mb-6">
        
        <div class="card">
            <div class="card-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="<?php echo e($connectionStatus['connected'] ? '#39d98a' : '#ff4757'); ?>" stroke-width="2">
                    <path d="M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6 0 0 1 6.95 0"/>
                    <line x1="12" y1="20" x2="12.01" y2="20"/>
                </svg>
                <span class="card-title">Control Node</span>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($connectionStatus['connected']): ?>
                    <span class="badge badge-success ml-auto">Connected</span>
                <?php else: ?>
                    <span class="badge badge-failed ml-auto">Disconnected</span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <div class="card-body">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($connectionStatus['connected']): ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <div class="form-label" style="margin-bottom:4px">Host</div>
                        <div class="text-mono text-sm" style="color:var(--text-primary)"><?php echo e($connectionStatus['host']); ?></div>
                    </div>
                    <div>
                        <div class="form-label" style="margin-bottom:4px">User</div>
                        <div class="text-mono text-sm" style="color:var(--text-primary)"><?php echo e($connectionStatus['user']); ?></div>
                    </div>
                    <div>
                        <div class="form-label" style="margin-bottom:4px">Ansible</div>
                        <div class="text-mono text-sm text-green"><?php echo e($connectionStatus['ansible_version'] ?? 'unknown'); ?></div>
                    </div>
                    <div>
                        <div class="form-label" style="margin-bottom:4px">Latency</div>
                        <div class="text-mono text-sm" style="color:var(--blue)"><?php echo e($connectionStatus['latency_ms']); ?>ms</div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-error" style="margin:0">
                    <?php echo e($connectionStatus['error'] ?? 'Connection failed'); ?>

                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        
        <div class="card">
            <div class="card-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
                <span class="card-title">Quick Actions</span>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <a href="<?php echo e(route('playbooks.index')); ?>" class="btn btn-primary">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                        Run Playbook
                    </a>
                    <a href="<?php echo e(route('terminal.index')); ?>" class="btn btn-secondary">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>
                        </svg>
                        Terminal
                    </a>
                    <a href="<?php echo e(route('inventory.index')); ?>" class="btn btn-secondary">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.07 4.93l-1.41 1.41"/>
                        </svg>
                        Inventory
                    </a>
                    <a href="<?php echo e(route('logs.jobs')); ?>" class="btn btn-secondary">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Job History
                    </a>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card mb-6">
        <div class="card-header">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            <span class="card-title">14-Day Activity</span>
        </div>
        <div class="card-body" style="padding:20px">
            <canvas id="trendChart" height="80"></canvas>
        </div>
    </div>

    
    <div class="card">
        <div class="card-header">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            <span class="card-title">Recent Jobs</span>
            <a href="<?php echo e(route('logs.jobs')); ?>" class="btn btn-sm btn-secondary ml-auto">View all</a>
        </div>
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Playbook</th>
                        <th>Status</th>
                        <th>User</th>
                        <th>Summary</th>
                        <th>Duration</th>
                        <th>Time</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentJobs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-mono text-xs text-muted"><?php echo e($job->id); ?></td>
                        <td>
                            <span class="text-mono text-sm" style="color:var(--text-primary)">
                                <?php echo e(basename($job->playbook)); ?>

                            </span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($job->check_mode): ?>
                                <span class="badge" style="background:var(--blue-dim);color:var(--blue);margin-left:6px">CHECK</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo e($job->status); ?>"><?php echo e($job->status); ?></span>
                        </td>
                        <td class="text-sm"><?php echo e($job->user?->name ?? '—'); ?></td>
                        <td>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($job->hosts_ok > 0 || $job->hosts_changed > 0): ?>
                            <span class="text-mono text-xs">
                                <span class="text-green">ok=<?php echo e($job->hosts_ok); ?></span>
                                <span style="color:var(--yellow)"> chg=<?php echo e($job->hosts_changed); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($job->hosts_failed > 0): ?>
                                    <span class="text-red"> fail=<?php echo e($job->hosts_failed); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                            <?php else: ?>
                                <span class="text-muted text-xs">—</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="text-mono text-xs text-muted"><?php echo e($job->duration ?? '—'); ?></td>
                        <td class="text-xs text-muted"><?php echo e($job->created_at->diffForHumans()); ?></td>
                        <td>
                            <a href="<?php echo e(route('jobs.show', $job)); ?>" class="btn btn-sm btn-secondary">View</a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:32px;color:var(--text-muted);font-family:var(--font-mono)">
                            No jobs yet. Run a playbook to get started.
                        </td>
                    </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function() {
    const trend = <?php echo json_encode($jobTrend, 15, 512) ?>;
    const labels  = trend.map(r => r.date);
    const success = trend.map(r => r.success);
    const failed  = trend.map(r => r.failed);

    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Success',
                    data: success,
                    backgroundColor: 'rgba(57,217,138,.7)',
                    borderColor: '#39d98a',
                    borderWidth: 1,
                    borderRadius: 3,
                },
                {
                    label: 'Failed',
                    data: failed,
                    backgroundColor: 'rgba(255,71,87,.5)',
                    borderColor: '#ff4757',
                    borderWidth: 1,
                    borderRadius: 3,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    labels: {
                        color: '#7c8496',
                        font: { family: 'JetBrains Mono', size: 11 }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { color: 'rgba(36,40,48,.8)' },
                    ticks: { color: '#4a5060', font: { family: 'JetBrains Mono', size: 10 } }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: 'rgba(36,40,48,.8)' },
                    ticks: { color: '#4a5060', font: { family: 'JetBrains Mono', size: 10 }, precision: 0 }
                }
            }
        }
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/paul/Git/ansible-dashboard/resources/views/dashboard/index.blade.php ENDPATH**/ ?>