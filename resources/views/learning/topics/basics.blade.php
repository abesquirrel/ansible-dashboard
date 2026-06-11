@extends('layouts.app')
@section('title', 'Learning Hub: Basics')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px">
        <div>
            <h1 class="page-title">1. Ansible Core Concepts</h1>
            <p class="page-subtitle">The foundation of modern configuration management.</p>
        </div>
        <a href="{{ route('learning.index') }}" class="btn btn-sm btn-secondary ml-auto">Back to Hub</a>
    </div>
</div>

<div class="page-body">
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">What is Ansible?</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                Ansible is an open-source IT automation engine that automates provisioning, configuration management, application deployment, and orchestration. Unlike other tools (like Chef or Puppet), Ansible is <strong>agentless</strong>. It doesn't require any special software to be installed on the servers you are managing.
            </p>
            <p style="color:var(--text-secondary); line-height:1.6;">
                Instead, Ansible relies on standard networking protocols—primarily <strong>SSH</strong> for Linux/Unix systems and <strong>WinRM</strong> for Windows.
            </p>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">The Push Architecture</span>
        </div>
        <div class="card-body">
            <div style="padding:20px; background:var(--bg-surface); border-radius:var(--radius); margin-bottom:20px; text-align:center;">
                <code style="color:var(--blue);">Control Node</code>
                <span style="margin: 0 20px; color:var(--text-muted);">━━ SSH ━━▶</span>
                <code style="color:var(--green);">Managed Node(s)</code>
            </div>
            
            <h3 style="font-size:14px; color:var(--accent); margin-bottom:8px;">Control Node</h3>
            <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                The machine where Ansible is installed and from which all commands and playbooks are executed. In this project, the Docker container `ansible-ctrl-app` acts as your Control Node environment.
            </p>

            <h3 style="font-size:14px; color:var(--accent); margin-bottom:8px;">Managed Nodes</h3>
            <p style="color:var(--text-secondary); line-height:1.6;">
                The remote servers you want to control. Ansible connects to these nodes over SSH, pushes small programs called "Ansible Modules" to them, executes those modules, and then removes them.
            </p>
        </div>
    </div>

    <div class="card mb-6" style="border-color:var(--blue);">
        <div class="card-header" style="background:rgba(61,198,255,.05);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
            </svg>
            <span class="card-title text-blue" style="margin-left:8px;">Why is this dashboard useful?</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6;">
                Normally, Ansible is entirely command-line driven. This dashboard (CTRL) provides a visual GUI over the CLI tools. When you view the <strong>Inventory Graph</strong>, the PHP backend runs <code>ansible-inventory --list</code> under the hood and parses the JSON to draw the topology.
            </p>
        </div>
    </div>

    <div style="text-align:right;">
        <a href="{{ route('learning.topic', 'inventory-adhoc') }}" class="btn btn-primary">Next: Inventory & Ad-hoc ➔</a>
    </div>
</div>
@endsection
