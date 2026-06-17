@extends('layouts.app')
@section('title', 'Learning Hub')

@section('content')
<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .learning-grid > .card-link {
        opacity: 0;
        animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        animation-delay: calc(var(--sibling-index, 1) * 0.08s);
        animation-delay: calc(sibling-index() * 0.08s);
        text-decoration: none;
        transition: border-color 0.2s, transform 0.2s, background-color 0.2s;
    }

    .learning-grid > .card-link:hover {
        transform: translateY(-2px);
        border-color: var(--accent) !important;
        background: var(--bg-hover) !important;
    }

    .status-pulse {
        width: 10px; height: 10px;
        border-radius: 50%;
        background-color: var(--green);
        box-shadow: 0 0 0 0 rgba(57, 217, 138, 0.7);
        animation: pulse 1.6s infinite;
        display: inline-block;
    }

    @keyframes pulse {
        0%   { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(57, 217, 138, 0.7); }
        70%  { transform: scale(1);    box-shadow: 0 0 0 6px rgba(57, 217, 138, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(57, 217, 138, 0); }
    }

    .status-pulse-offline {
        width: 10px; height: 10px;
        border-radius: 50%;
        background-color: var(--text-secondary);
        display: inline-block;
    }

    .diff-pip {
        width: 6px; height: 6px;
        border-radius: 50%;
        display: inline-block;
    }
    .diff-pip.filled  { background: currentColor; }
    .diff-pip.empty   { background: var(--border); }
</style>

<div class="page-header">
    <div class="flex items-center" style="padding-bottom:12px">
        <div>
            <h1 class="page-title">Learn Ansible</h1>
            <p class="page-subtitle">A comprehensive, interactive curriculum for mastering Ansible automation.</p>
        </div>
    </div>
</div>

<div class="page-body">
    {{-- Active Lab Connection Panel --}}
    <div class="card mb-6" style="border-left: 4px solid {{ $sshStatus['connected'] ? 'var(--green)' : 'var(--text-muted)' }};">
        <div class="card-body" style="padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                @if($sshStatus['connected'])
                    <span class="status-pulse"></span>
                    <div>
                        <div style="font-weight: 600; font-size: 14px; color: var(--text-primary);">
                            Active Lab Status: <span style="color: var(--green);">Online & Connected</span>
                        </div>
                        <div style="font-size: 12px; color: var(--text-secondary); font-family: var(--font-mono);">
                            Ansible Control Node: {{ $sshStatus['host'] }} · Auth: {{ $sshStatus['auth_method'] }} · Latency: {{ $sshStatus['latency_ms'] }}ms
                        </div>
                    </div>
                @else
                    <span class="status-pulse-offline"></span>
                    <div>
                        <div style="font-weight: 600; font-size: 14px; color: var(--text-primary);">
                            Active Lab Status: <span style="color: var(--text-secondary);">Offline / Unconfigured</span>
                        </div>
                        <div style="font-size: 12px; color: var(--text-secondary);">
                            No active Ansible control node session available. Interactive tasks will fall back to guide mode.
                        </div>
                    </div>
                @endif
            </div>

            <div style="display: flex; gap: 12px; font-family: var(--font-mono); font-size: 12px;">
                @if($sshStatus['connected'])
                    <div style="background: var(--bg-surface); border: 1px solid var(--border); padding: 4px 10px; border-radius: var(--radius);">
                        Hosts: <strong style="color: var(--blue);">{{ $hostCount }}</strong>
                    </div>
                    <div style="background: var(--bg-surface); border: 1px solid var(--border); padding: 4px 10px; border-radius: var(--radius);">
                        Playbooks: <strong style="color: var(--yellow);">{{ $playbookCount }}</strong>
                    </div>
                    <a href="{{ route('settings.index') }}" class="btn btn-sm btn-secondary" style="padding: 3px 10px; font-size: 11px;">Configure</a>
                @else
                    <a href="{{ route('settings.index') }}" class="btn btn-sm btn-primary" style="padding: 4px 12px; font-size: 11px;">Setup Lab Connection</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Topics Grid --}}
    @php
        $topics = [
            [
                'slug'        => 'basics',
                'num'         => 1,
                'title'       => 'Core Concepts',
                'desc'        => 'Understand the push-based architecture: Control Nodes, Managed Nodes, SSH, and the agentless approach.',
                'color'       => 'var(--blue)',
                'difficulty'  => 1,
                'tasks'       => 1, 'exercises' => 2, 'quiz' => 2,
                'live_label'  => null,
                'icon_path'   => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
            ],
            [
                'slug'        => 'inventory-adhoc',
                'num'         => 2,
                'title'       => 'Inventory & Ad-Hoc',
                'desc'        => 'Define your infrastructure using hosts files, variables, and groupings. Test commands live using the ad-hoc module runner.',
                'color'       => 'var(--accent)',
                'difficulty'  => 1,
                'tasks'       => 1, 'exercises' => 2, 'quiz' => 2,
                'live_label'  => $sshStatus['connected'] ? 'Connected to Active Nodes' : null,
                'icon_path'   => '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>',
            ],
            [
                'slug'        => 'playbooks',
                'num'         => 3,
                'title'       => 'Playbooks & Modules',
                'desc'        => 'The heart of Ansible. Learn YAML syntax, tasks, variables, and handlers to build complex automated deployments.',
                'color'       => 'var(--yellow)',
                'difficulty'  => 2,
                'tasks'       => 1, 'exercises' => 2, 'quiz' => 2,
                'live_label'  => $sshStatus['connected'] ? $playbookCount . ' Playbooks Found' : null,
                'icon_path'   => '<polygon points="5 3 19 12 5 21 5 3"/>',
            ],
            [
                'slug'        => 'roles',
                'num'         => 4,
                'title'       => 'Roles & Reusability',
                'desc'        => 'Organize your playbooks for scale using standard directory structures and community content via Ansible Galaxy.',
                'color'       => 'var(--orange)',
                'difficulty'  => 2,
                'tasks'       => 1, 'exercises' => 2, 'quiz' => 2,
                'live_label'  => null,
                'icon_path'   => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',
            ],
            [
                'slug'        => 'vars-templates',
                'num'         => 5,
                'title'       => 'Variables & Templates',
                'desc'        => 'Master dynamic configuration using Jinja2 templates, variable precedence, host/group vars, and Ansible Vault for secrets.',
                'color'       => 'var(--red)',
                'difficulty'  => 3,
                'tasks'       => 1, 'exercises' => 2, 'quiz' => 3,
                'live_label'  => null,
                'icon_path'   => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
            ],
        ];
    @endphp

    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:12px;" class="learning-grid">
        @foreach($topics as $topic)
            <a href="{{ route('learning.topic', $topic['slug']) }}" class="card card-link">
                <div class="card-header" style="border-bottom:none; padding-bottom:0;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $topic['color'] }}" stroke-width="2">{!! $topic['icon_path'] !!}</svg>
                        <span class="card-title" style="font-size:14px; color: var(--text-primary);">{{ $topic['num'] }}. {{ $topic['title'] }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <p style="color:var(--text-secondary); font-size:13px; line-height:1.6; margin-bottom:12px;">
                        {{ $topic['desc'] }}
                    </p>
                    <div style="display:flex; align-items:center; justify-content:space-between; font-size:11px; font-family:var(--font-mono);">
                        <div style="display:flex; gap:12px; color:var(--text-muted);">
                            <span>Tasks: {{ $topic['tasks'] }}</span>
                            <span>Ex: {{ $topic['exercises'] }}</span>
                            <span>Quiz: {{ $topic['quiz'] }}</span>
                        </div>
                        {{-- Difficulty pips --}}
                        <div style="display:flex; gap:3px; color:{{ $topic['color'] }};" title="Difficulty: {{ $topic['difficulty'] }}/3">
                            @for($d = 1; $d <= 3; $d++)
                                <span class="diff-pip {{ $d <= $topic['difficulty'] ? 'filled' : 'empty' }}"></span>
                            @endfor
                        </div>
                    </div>
                    @if($topic['live_label'])
                        <div style="font-size:11px; color:{{ $topic['color'] }}; font-family:var(--font-mono); margin-top:6px; display:flex; align-items:center; gap:6px;">
                            <span style="width:6px; height:6px; border-radius:50%; background:{{ $topic['color'] }}; display:inline-block;"></span>
                            {{ $topic['live_label'] }}
                        </div>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
</div>

<script>
    if (!CSS.supports('animation-delay: calc(sibling-index() * 0.1s)')) {
        const staggerList = document.querySelector('.learning-grid');
        if (staggerList) {
            [...staggerList.children].forEach((el, index) => el.style.setProperty('--sibling-index', index + 1));
        }
    }
</script>
@endsection
