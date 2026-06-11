@extends('layouts.app')
@section('title', 'Learning Hub')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px">
        <div>
            <h1 class="page-title">Learn Ansible</h1>
            <p class="page-subtitle">A comprehensive, interactive curriculum for mastering Ansible.</p>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="grid-2">
        <a href="{{ route('learning.topic', 'basics') }}" class="card" style="text-decoration:none; transition: transform .15s; display:block;" onmouseover="this.style.transform='translateY(-2px)'; this.style.borderColor='var(--accent)';" onmouseout="this.style.transform='none'; this.style.borderColor='var(--border)';">
            <div class="card-header">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
                </svg>
                <span class="card-title">1. Core Concepts</span>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); font-size:13px; line-height:1.6;">
                    Understand the architecture: Control Nodes, Managed Nodes, SSH, and why Ansible's push-based model is so powerful.
                </p>
            </div>
        </a>

        <a href="{{ route('learning.topic', 'inventory-adhoc') }}" class="card" style="text-decoration:none; transition: transform .15s; display:block;" onmouseover="this.style.transform='translateY(-2px)'; this.style.borderColor='var(--accent)';" onmouseout="this.style.transform='none'; this.style.borderColor='var(--border)';">
            <div class="card-header">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                    <polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>
                </svg>
                <span class="card-title">2. Inventory & Ad-Hoc</span>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); font-size:13px; line-height:1.6;">
                    Define your infrastructure using hosts files, variables, and groupings. Test commands live using the ad-hoc module runner.
                </p>
            </div>
        </a>

        <a href="{{ route('learning.topic', 'playbooks') }}" class="card" style="text-decoration:none; transition: transform .15s; display:block;" onmouseover="this.style.transform='translateY(-2px)'; this.style.borderColor='var(--accent)';" onmouseout="this.style.transform='none'; this.style.borderColor='var(--border)';">
            <div class="card-header">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--yellow)" stroke-width="2">
                    <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
                <span class="card-title">3. Playbooks & Modules</span>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); font-size:13px; line-height:1.6;">
                    The heart of Ansible. Learn YAML syntax, tasks, variables, and handlers to build complex automated deployments.
                </p>
            </div>
        </a>

        <a href="{{ route('learning.topic', 'roles') }}" class="card" style="text-decoration:none; transition: transform .15s; display:block;" onmouseover="this.style.transform='translateY(-2px)'; this.style.borderColor='var(--accent)';" onmouseout="this.style.transform='none'; this.style.borderColor='var(--border)';">
            <div class="card-header">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                </svg>
                <span class="card-title">4. Roles & Reusability</span>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); font-size:13px; line-height:1.6;">
                    Organize your playbooks for scale using standard directory structures and community content via Ansible Galaxy.
                </p>
            </div>
        </a>
    </div>
</div>
@endsection
