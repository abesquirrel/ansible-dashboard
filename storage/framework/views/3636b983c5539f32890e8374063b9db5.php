<?php $__env->startSection('title', 'Playbooks'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px">
        <div>
            <h1 class="page-title">Playbooks</h1>
            <p class="page-subtitle">Run, schedule and monitor Ansible playbooks</p>
        </div>
    </div>
</div>

<div class="page-body">
<div class="grid-2 gap-4" style="gap:24px;align-items:start">

    
    <div>
        <div class="card mb-4" x-data="playbookRunner()">
            <div class="card-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                    <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
                <span class="card-title">Run Playbook</span>
            </div>
            <div class="card-body">

                <div class="form-group">
                    <label class="form-label">Playbook</label>
                    <select class="form-select" x-model="form.playbook" @change="loadContent">
                        <option value="">Select playbook…</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $playbooks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($pb); ?>"><?php echo e(basename($pb)); ?> <span style="color:var(--text-muted)"><?php echo e(dirname($pb)); ?></span></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                    <div class="form-hint">From <?php echo e(config('ansible.playbooks_dir')); ?></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Inventory</label>
                    <input type="text" class="form-input" x-model="form.inventory"
                        placeholder="<?php echo e(config('ansible.inventory_default')); ?>">
                    <div class="form-hint">Leave blank to use default inventory</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Limit (host pattern)</label>
                    <input type="text" class="form-input" x-model="form.limit" placeholder="all, webservers, 192.168.1.10">
                </div>

                <div class="form-group">
                    <label class="form-label">Tags (comma-separated)</label>
                    <input type="text" class="form-input" x-model="form.tagsRaw" placeholder="deploy,configure">
                </div>

                <div class="form-group">
                    <label class="form-label">Extra Variables</label>
                    <textarea class="form-textarea" x-model="form.extraVarsRaw"
                        placeholder='key=value&#10;env=production'
                        style="min-height:60px;font-family:var(--font-mono);font-size:12px"></textarea>
                    <div class="form-hint">One per line: key=value</div>
                </div>

                <div class="flex gap-3 mb-4">
                    <label class="flex items-center gap-2 text-sm text-mono" style="cursor:pointer">
                        <input type="checkbox" x-model="form.checkMode"> Check mode (--check)
                    </label>
                    <label class="flex items-center gap-2 text-sm text-mono" style="cursor:pointer">
                        <input type="checkbox" x-model="form.verbose"> Verbose (-v)
                    </label>
                </div>

                
                <div class="form-group" x-show="form.playbook">
                    <label class="form-label">Command Preview</label>
                    <div class="code-block" x-text="commandPreview()" style="font-size:11px;overflow-x:auto;white-space:pre-wrap;word-break:break-all"></div>
                </div>

                <div class="flex gap-2">
                    <button class="btn btn-primary" @click="run" :disabled="running || !form.playbook">
                        <span x-show="!running">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="5 3 19 12 5 21 5 3"/>
                            </svg>
                        </span>
                        <span x-show="running" class="spinner" style="width:13px;height:13px"></span>
                        <span x-text="running ? 'Queuing…' : 'Run Playbook'"></span>
                    </button>
                    <button class="btn btn-secondary" @click="resetForm">Reset</button>
                </div>

                <div x-show="message" class="alert" :class="error ? 'alert-error' : 'alert-success'" style="margin-top:12px;margin-bottom:0" x-text="message"></div>
            </div>
        </div>

        
        <div class="card" x-data="{ content: '' }" x-show="content">
            <div class="card-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <span class="card-title">Playbook Preview</span>
            </div>
            <div class="card-body" style="padding:0">
                <pre id="pb-preview" style="
                    font-family:var(--font-mono);font-size:11px;line-height:1.7;
                    color:var(--text-code);background:var(--bg-base);
                    padding:16px;overflow-x:auto;max-height:400px;overflow-y:auto;
                    margin:0;white-space:pre;"></pre>
            </div>
        </div>
    </div>

    
    <div>
        <div class="card">
            <div class="card-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                <span class="card-title">Recent Jobs</span>
                <a href="<?php echo e(route('logs.jobs')); ?>" class="btn btn-sm btn-secondary ml-auto">All Jobs</a>
            </div>
            <div style="overflow-x:auto" id="jobs-table">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Playbook</th>
                            <th>Status</th>
                            <th>Summary</th>
                            <th>Time</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $jobs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-mono text-xs text-muted"><?php echo e($job->id); ?></td>
                            <td class="text-mono text-sm" style="color:var(--text-primary)"><?php echo e(basename($job->playbook)); ?></td>
                            <td><span class="badge badge-<?php echo e($job->status); ?>"><?php echo e($job->status); ?></span></td>
                            <td class="text-mono text-xs">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($job->hosts_ok > 0): ?>
                                    <span class="text-green">ok=<?php echo e($job->hosts_ok); ?></span>
                                    <span class="text-yellow"> chg=<?php echo e($job->hosts_changed); ?></span>
                                <?php else: ?> —
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="text-xs text-muted"><?php echo e($job->created_at->diffForHumans()); ?></td>
                            <td><a href="<?php echo e(route('jobs.show', $job)); ?>" class="btn btn-sm btn-secondary">View</a></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted);font-family:var(--font-mono)">No jobs yet</td></tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($jobs->hasPages()): ?>
            <div style="padding:12px 16px;border-top:1px solid var(--border)">
                <?php echo e($jobs->links()); ?>

            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function playbookRunner() {
    return {
        form: {
            playbook: '',
            inventory: '',
            limit: '',
            tagsRaw: '',
            extraVarsRaw: '',
            checkMode: false,
            verbose: false,
        },
        running: false,
        message: '',
        error: false,

        commandPreview() {
            if (!this.form.playbook) return '';
            let parts = ['ansible-playbook'];
            const inv = this.form.inventory || '<?php echo e(config('ansible.inventory_default')); ?>';
            parts.push('-i', inv);
            if (this.form.checkMode) parts.push('--check');
            if (this.form.verbose) parts.push('-v');
            if (this.form.limit) parts.push('--limit', this.form.limit);
            if (this.form.tagsRaw) parts.push('--tags', this.form.tagsRaw);
            if (this.form.extraVarsRaw) {
                this.form.extraVarsRaw.trim().split('\n').forEach(line => {
                    if (line.trim()) parts.push('--extra-vars', `"${line.trim()}"`);
                });
            }
            parts.push(this.form.playbook);
            return parts.join(' ');
        },

        async loadContent() {
            if (!this.form.playbook) return;
            const r = await api('/playbooks/content?path=' + encodeURIComponent(this.form.playbook));
            if (r.content) {
                document.getElementById('pb-preview').textContent = r.content;
            }
        },

        async run() {
            if (!this.form.playbook) return;
            this.running = true;
            this.message = '';

            const extraVars = {};
            this.form.extraVarsRaw.trim().split('\n').forEach(line => {
                const [k, ...v] = line.split('=');
                if (k && v.length) extraVars[k.trim()] = v.join('=').trim();
            });

            const tags = this.form.tagsRaw.split(',').map(t => t.trim()).filter(Boolean);

            try {
                const r = await api('/playbooks/run', {
                    method: 'POST',
                    body: JSON.stringify({
                        playbook:   this.form.playbook,
                        inventory:  this.form.inventory,
                        extra_vars: extraVars,
                        tags,
                        limit:      this.form.limit,
                        check_mode: this.form.checkMode,
                        verbose:    this.form.verbose,
                    })
                });

                if (r.job_id) {
                    this.message = `Job #${r.job_id} queued — redirecting to output…`;
                    this.error = false;
                    setTimeout(() => window.location.href = `/jobs/${r.job_id}`, 1000);
                } else {
                    this.message = r.message || 'Error running playbook';
                    this.error = true;
                }
            } catch (e) {
                this.message = e.message;
                this.error = true;
            } finally {
                this.running = false;
            }
        },

        resetForm() {
            this.form = { playbook:'', inventory:'', limit:'', tagsRaw:'', extraVarsRaw:'', checkMode:false, verbose:false };
            this.message = '';
        }
    };
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/paul/Git/ansible-dashboard/resources/views/playbooks/index.blade.php ENDPATH**/ ?>