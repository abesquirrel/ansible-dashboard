@extends('layouts.app')
@section('title', 'Learning Hub: Roles')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px">
        <div>
            <h1 class="page-title">4. Roles & Reusability</h1>
            <p class="page-subtitle">Organizing playbooks for scale.</p>
        </div>
        <div class="ml-auto flex gap-2">
            <a href="{{ route('learning.topic', 'playbooks') }}" class="btn btn-sm btn-secondary">Back</a>
            <a href="{{ route('learning.index') }}" class="btn btn-sm btn-secondary">Hub</a>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">What is a Role?</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                As your infrastructure grows, a single monolithic playbook becomes hard to maintain. <strong>Roles</strong> allow you to automatically load related vars, files, tasks, and handlers based on a known file structure. They let you encapsulate logic so you can easily reuse it across multiple playbooks.
            </p>
            
            <h3 style="font-size:14px; color:var(--accent); margin-bottom:8px;">Standard Role Directory Structure:</h3>
            <div class="code-block" style="margin-bottom:16px;">
my_role/
├── tasks/
│   └── main.yml       # Main list of tasks that the role executes
├── handlers/
│   └── main.yml       # Handlers used within or outside this role
├── templates/         # Jinja2 templates for config files (.j2)
├── files/             # Static files to be deployed
├── vars/
│   └── main.yml       # Variables for the role (high precedence)
├── defaults/
│   └── main.yml       # Default variables (low precedence, easily overridden)
└── meta/
    └── main.yml       # Role dependencies and author info
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">Using a Role in a Playbook</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                Once you define a role, your main playbook becomes incredibly simple. It just maps hosts to roles.
            </p>
            <div class="code-block">
---
- hosts: webservers
  roles:
    - common
    - nginx
    - php-fpm
            </div>
        </div>
    </div>

    <div class="card mb-6" style="border-color:var(--orange);">
        <div class="card-header" style="background:rgba(255,127,80,.05);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
            </svg>
            <span class="card-title text-orange" style="margin-left:8px;">Ansible Galaxy</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6;">
                <strong>Ansible Galaxy</strong> is a free site for finding, downloading, and sharing community-developed roles. Instead of writing an Nginx installation role from scratch, you can download a battle-tested one using:
                <br><br>
                <code>ansible-galaxy install geerlingguy.nginx</code>
            </p>
        </div>
    </div>

    <div style="text-align:right;">
        <a href="{{ route('learning.index') }}" class="btn btn-secondary">Return to Hub</a>
    </div>
</div>
@endsection
