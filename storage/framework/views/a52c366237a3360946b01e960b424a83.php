<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Ansible Dashboard'); ?> · CTRL</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/xterm/5.3.0/xterm.min.css">

    <style>
        :root {
            --bg-base:      #0a0c0f;
            --bg-panel:     #0f1117;
            --bg-surface:   #161920;
            --bg-hover:     #1d2129;
            --border:       #242830;
            --border-bright:#2e3440;

            --text-primary:  #e8eaf0;
            --text-secondary:#7c8496;
            --text-muted:    #4a5060;
            --text-code:     #a8e6cf;

            --green:    #39d98a;
            --green-dim:#1f5e3e;
            --red:      #ff4757;
            --red-dim:  #5c1f28;
            --yellow:   #ffd32a;
            --yellow-dim:#4d3d0a;
            --blue:     #3dc6ff;
            --blue-dim: #0f3550;
            --orange:   #ff7f50;

            --accent:   #39d98a;
            --phosphor: #39d98a;

            --font-mono: 'JetBrains Mono', 'Fira Code', monospace;
            --font-ui:   'IBM Plex Sans', system-ui, sans-serif;

            --radius: 4px;
            --radius-lg: 8px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            background: var(--bg-base);
            color: var(--text-primary);
            font-family: var(--font-ui);
            font-size: 14px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Layout ── */
        .app-shell {
            display: grid;
            grid-template-columns: 220px 1fr;
            grid-template-rows: 48px 1fr;
            grid-template-areas:
                "topbar topbar"
                "sidebar main";
            height: 100vh;
            overflow: hidden;
        }

        /* ── Topbar ── */
        .topbar {
            grid-area: topbar;
            background: var(--bg-panel);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 20px;
            z-index: 100;
        }

        .topbar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .topbar-logo-icon {
            width: 28px; height: 28px;
            border: 1.5px solid var(--accent);
            display: grid;
            place-items: center;
            border-radius: 3px;
        }

        .topbar-logo-icon svg { color: var(--accent); }

        .topbar-logo-text {
            font-family: var(--font-mono);
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .topbar-sep {
            width: 1px;
            height: 24px;
            background: var(--border);
            margin: 0 4px;
        }

        .conn-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-secondary);
        }

        .conn-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--text-muted);
        }
        .conn-dot.connected { background: var(--green); box-shadow: 0 0 6px var(--green); }
        .conn-dot.error     { background: var(--red); box-shadow: 0 0 6px var(--red); }

        .topbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .avatar {
            width: 28px; height: 28px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 600;
            color: var(--accent);
        }

        .btn-topbar {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 4px 10px;
            font-family: var(--font-mono);
            font-size: 11px;
            cursor: pointer;
            border-radius: var(--radius);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all .15s;
        }
        .btn-topbar:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        /* ── Sidebar ── */
        .sidebar {
            grid-area: sidebar;
            background: var(--bg-panel);
            border-right: 1px solid var(--border);
            overflow-y: auto;
            padding: 16px 0;
        }

        .sidebar-section {
            margin-bottom: 8px;
        }

        .sidebar-label {
            font-family: var(--font-mono);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 6px 16px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 13px;
            border-left: 2px solid transparent;
            transition: all .15s;
        }

        .sidebar-link:hover {
            color: var(--text-primary);
            background: var(--bg-surface);
            border-left-color: var(--border-bright);
        }

        .sidebar-link.active {
            color: var(--accent);
            background: rgba(57, 217, 138, 0.06);
            border-left-color: var(--accent);
        }

        .sidebar-link svg {
            width: 15px; height: 15px;
            flex-shrink: 0;
            opacity: .7;
        }
        .sidebar-link.active svg { opacity: 1; }

        .sidebar-badge {
            margin-left: auto;
            background: var(--red);
            color: #fff;
            font-family: var(--font-mono);
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 10px;
        }

        .sidebar-badge.running {
            background: var(--yellow-dim);
            color: var(--yellow);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }

        /* ── Main ── */
        .main {
            grid-area: main;
            overflow-y: auto;
            background: var(--bg-base);
        }

        .page-header {
            padding: 24px 28px 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 0;
        }

        .page-title {
            font-family: var(--font-mono);
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .page-subtitle {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
            font-family: var(--font-mono);
        }

        .page-tabs {
            display: flex;
            gap: 0;
            margin-top: 16px;
        }

        .page-tab {
            padding: 8px 16px;
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--text-secondary);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all .15s;
        }
        .page-tab:hover { color: var(--text-primary); }
        .page-tab.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        .page-body { padding: 24px 28px; }

        /* ── Cards ── */
        .card {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .card-header {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title {
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .card-body { padding: 18px; }

        /* ── Stats ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px 20px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
        }

        .stat-card.green::before  { background: var(--green); }
        .stat-card.red::before    { background: var(--red); }
        .stat-card.blue::before   { background: var(--blue); }
        .stat-card.yellow::before { background: var(--yellow); }

        .stat-label {
            font-family: var(--font-mono);
            font-size: 10px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .stat-value {
            font-family: var(--font-mono);
            font-size: 32px;
            font-weight: 600;
            line-height: 1;
        }

        .stat-card.green .stat-value  { color: var(--green); }
        .stat-card.red .stat-value    { color: var(--red); }
        .stat-card.blue .stat-value   { color: var(--blue); }
        .stat-card.yellow .stat-value { color: var(--yellow); }

        /* ── Table ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .data-table th {
            font-family: var(--font-mono);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted);
            text-align: left;
            padding: 10px 16px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-surface);
        }

        .data-table td {
            padding: 10px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-secondary);
            vertical-align: middle;
        }

        .data-table tr:last-child td { border-bottom: none; }

        .data-table tr:hover td {
            background: var(--bg-surface);
        }

        /* ── Status badges ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-family: var(--font-mono);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 3px;
        }
        .badge::before {
            content: '';
            width: 5px; height: 5px;
            border-radius: 50%;
        }

        .badge-success { background: var(--green-dim);  color: var(--green);  }
        .badge-success::before { background: var(--green); }

        .badge-failed  { background: var(--red-dim);    color: var(--red);    }
        .badge-failed::before { background: var(--red); }

        .badge-running { background: var(--yellow-dim); color: var(--yellow); animation: pulse 2s infinite; }
        .badge-running::before { background: var(--yellow); }

        .badge-queued  { background: var(--bg-surface); color: var(--text-secondary); }
        .badge-queued::before { background: var(--text-muted); }

        .badge-aborted { background: var(--bg-surface); color: var(--text-muted); }
        .badge-aborted::before { background: var(--text-muted); }

        .badge-error   { background: var(--red-dim); color: var(--orange); }
        .badge-error::before { background: var(--orange); }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 14px;
            border-radius: var(--radius);
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            transition: all .15s;
        }

        .btn-primary {
            background: var(--green);
            color: var(--bg-base);
            border-color: var(--green);
        }
        .btn-primary:hover { background: #2fc87a; }

        .btn-secondary {
            background: transparent;
            border-color: var(--border-bright);
            color: var(--text-secondary);
        }
        .btn-secondary:hover {
            border-color: var(--text-secondary);
            color: var(--text-primary);
        }

        .btn-danger {
            background: transparent;
            border-color: var(--red);
            color: var(--red);
        }
        .btn-danger:hover { background: var(--red-dim); }

        .btn-sm { padding: 4px 10px; font-size: 11px; }

        /* ── Form controls ── */
        .form-group { margin-bottom: 16px; }

        .form-label {
            display: block;
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text-primary);
            font-family: var(--font-mono);
            font-size: 13px;
            padding: 8px 12px;
            outline: none;
            transition: border-color .15s;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(57,217,138,.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%234a5060'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            padding-right: 28px;
        }

        .form-textarea { resize: vertical; min-height: 80px; }

        .form-hint {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
            font-family: var(--font-mono);
        }

        /* ── Code / Terminal ── */
        .code-block {
            background: var(--bg-base);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px;
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--text-code);
            overflow-x: auto;
            white-space: pre;
        }

        .term-wrap {
            background: #000;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .term-bar {
            background: var(--bg-panel);
            border-bottom: 1px solid var(--border);
            padding: 8px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .term-dot {
            width: 10px; height: 10px; border-radius: 50%;
        }
        .term-dot.r { background: #ff5f57; }
        .term-dot.y { background: #febc2e; }
        .term-dot.g { background: #28c840; }

        .term-title {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-muted);
            margin: 0 auto;
        }

        /* ── Grid helpers ── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .flex    { display: flex; }
        .items-center { align-items: center; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .ml-auto { margin-left: auto; }
        .mb-4  { margin-bottom: 16px; }
        .mb-6  { margin-bottom: 24px; }
        .text-mono { font-family: var(--font-mono); }
        .text-sm   { font-size: 12px; }
        .text-xs   { font-size: 11px; }
        .text-muted { color: var(--text-muted); }
        .text-green { color: var(--green); }
        .text-red   { color: var(--red); }
        .text-blue  { color: var(--blue); }
        .text-yellow { color: var(--yellow); }

        /* ── Alerts ── */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius);
            font-family: var(--font-mono);
            font-size: 12px;
            border: 1px solid;
            margin-bottom: 16px;
        }
        .alert-success { background: var(--green-dim); border-color: var(--green); color: var(--green); }
        .alert-error   { background: var(--red-dim);   border-color: var(--red);   color: var(--red); }
        .alert-info    { background: var(--blue-dim);  border-color: var(--blue);  color: var(--blue); }

        /* ── Scrollbars ── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border-bright); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* ── Scan line overlay ── */
        .main::before {
            content: '';
            position: fixed;
            top: 0; left: 220px; right: 0; bottom: 0;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                rgba(0,0,0,.03) 2px,
                rgba(0,0,0,.03) 4px
            );
            pointer-events: none;
            z-index: 0;
        }

        .page-body, .page-header { position: relative; z-index: 1; }

        /* ── Loading spinner ── */
        .spinner {
            width: 16px; height: 16px;
            border: 2px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin .6s linear infinite;
            display: inline-block;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .app-shell {
                grid-template-columns: 1fr;
                grid-template-areas: "topbar" "main";
            }
            .sidebar { display: none; }
            .main::before { left: 0; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
<div class="app-shell">

    
    <header class="topbar">
        <a href="<?php echo e(route('dashboard')); ?>" class="topbar-logo">
            <div class="topbar-logo-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>
                </svg>
            </div>
            <span class="topbar-logo-text">CTRL</span>
        </a>

        <div class="topbar-sep"></div>

        <div class="conn-badge" id="conn-badge" title="SSH Control Node">
            <div class="conn-dot <?php echo e(isset($connectionStatus['connected']) && $connectionStatus['connected'] ? 'connected' : 'error'); ?>" id="conn-dot"></div>
            <span id="conn-host"><?php echo e(config('ansible.ssh.user')); ?>{{ config('ansible.ssh.host') }}</span>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($connectionStatus['ansible_version'])): ?>
            <div class="topbar-sep"></div>
            <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted)">
                <?php echo e($connectionStatus['ansible_version']); ?>

            </span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="topbar-right">
            <a href="<?php echo e(route('terminal.index')); ?>" class="btn-topbar">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>
                </svg>
                Terminal
            </a>
            <div class="topbar-sep"></div>
            <div class="topbar-user">
                <div class="avatar"><?php echo e(strtoupper(substr(auth()->user()->name, 0, 2))); ?></div>
                <span><?php echo e(auth()->user()->name); ?></span>
            </div>
            <form method="POST" action="<?php echo e(route('logout')); ?>" style="margin:0">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn-topbar">Sign out</button>
            </form>
        </div>
    </header>

    
    <nav class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-label">Overview</div>
            <a href="<?php echo e(route('dashboard')); ?>" class="sidebar-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                Dashboard
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-label">Ansible</div>
            <a href="<?php echo e(route('playbooks.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('playbooks.*') ? 'active' : ''); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
                Playbooks
            </a>
            <a href="<?php echo e(route('inventory.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('inventory.*') ? 'active' : ''); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/>
                </svg>
                Inventory
            </a>
            <a href="<?php echo e(route('terminal.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('terminal.*') ? 'active' : ''); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>
                </svg>
                Terminal
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-label">History</div>
            <a href="<?php echo e(route('logs.jobs')); ?>" class="sidebar-link <?php echo e(request()->routeIs('logs.jobs') ? 'active' : ''); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                Job History
                <?php $running = \App\Models\PlaybookJob::whereIn('status',['queued','running'])->count(); ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($running > 0): ?>
                    <span class="sidebar-badge running"><?php echo e($running); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </a>
            <a href="<?php echo e(route('logs.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('logs.index') ? 'active' : ''); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/>
                    <line x1="8" y1="17" x2="16" y2="17"/>
                </svg>
                Audit Log
            </a>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->is_admin): ?>
        <div class="sidebar-section">
            <div class="sidebar-label">Admin</div>
            <a href="<?php echo e(route('settings.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('settings.*') ? 'active' : ''); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.07 4.93l-1.41 1.41M20 12h-2M17.66 17.66l-1.41-1.41M12 20v-2M6.34 17.66l1.41-1.41M4 12h2M6.34 6.34L7.75 7.75"/>
                </svg>
                Settings
            </a>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div style="position:absolute;bottom:16px;left:0;right:0;padding:0 16px;">
            <div style="font-family:var(--font-mono);font-size:10px;color:var(--text-muted);">
                CTRL v1.0.0<br>
                <span style="color:var(--green-dim)">Ansible Dashboard</span>
            </div>
        </div>
    </nav>

    
    <main class="main">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
            <div style="padding:16px 28px 0">
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
            <div style="padding:16px 28px 0">
                <div class="alert alert-error"><?php echo e(session('error')); ?></div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xterm/5.3.0/xterm.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xterm/5.3.0/addon-fit/xterm-addon-fit.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.0/cdn.min.js" defer></script>

<script>
// Global CSRF setup
window.CSRF_TOKEN = '<?php echo e(csrf_token()); ?>';
window.REVERB_HOST = '<?php echo e(env("REVERB_HOST", "localhost")); ?>';
window.REVERB_PORT = '<?php echo e(env("REVERB_PORT", 8080)); ?>';
window.REVERB_KEY  = '<?php echo e(env("REVERB_APP_KEY")); ?>';

// API helper
async function api(url, opts = {}) {
    const resp = await fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.CSRF_TOKEN,
            ...opts.headers
        },
        ...opts
    });
    return resp.json();
}

// Connection status polling
(function() {
    const dot = document.getElementById('conn-dot');
    setInterval(async () => {
        try {
            const s = await api('/status');
            dot.className = 'conn-dot ' + (s.connected ? 'connected' : 'error');
        } catch {}
    }, 30000);
})();
</script>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/paul/Git/ansible-dashboard/resources/views/layouts/app.blade.php ENDPATH**/ ?>