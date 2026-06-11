@extends('layouts.app')
@section('title', 'Learning Hub: Playbooks')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px">
        <div>
            <h1 class="page-title">3. Playbooks & Modules</h1>
            <p class="page-subtitle">Infrastructure as Code using YAML.</p>
        </div>
        <div class="ml-auto flex gap-2">
            <a href="{{ route('learning.topic', 'inventory-adhoc') }}" class="btn btn-sm btn-secondary">Back</a>
            <a href="{{ route('learning.index') }}" class="btn btn-sm btn-secondary">Hub</a>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">Anatomy of a Playbook</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                While Ad-Hoc commands are great for quick tasks, <strong>Playbooks</strong> are the core of Ansible's configuration management and deployment capabilities. Playbooks are written in YAML and are designed to be human-readable.
            </p>
            
            <div class="code-block" style="margin-bottom:16px;">
---
- name: Deploy Web Server
  hosts: webservers
  become: yes
  vars:
    http_port: 80

  tasks:
    - name: Install Apache
      apt:
        name: apache2
        state: present
      notify: Restart Apache

    - name: Ensure Apache is running
      service:
        name: apache2
        state: started

  handlers:
    - name: Restart Apache
      service:
        name: apache2
        state: restarted
            </div>

            <h3 style="font-size:14px; color:var(--accent); margin-bottom:8px;">Key Components:</h3>
            <ul style="color:var(--text-secondary); line-height:1.6; margin-left:20px;">
                <li><code>hosts</code>: Specifies which machines from the inventory this play targets.</li>
                <li><code>become: yes</code>: Elevates privileges (runs as root/sudo).</li>
                <li><code>vars</code>: Variables that can be reused throughout the playbook.</li>
                <li><code>tasks</code>: A sequential list of actions to perform. Each task uses a <strong>module</strong> (like <code>apt</code> or <code>service</code>).</li>
                <li><code>handlers</code>: Special tasks that only run when notified by another task (e.g., restarting a service only if a configuration file changed).</li>
            </ul>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">Idempotency</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6;">
                A core concept in Ansible is <strong>Idempotency</strong>. This means an operation produces the same result whether you run it once or ten times. If a task says "ensure apache is installed", Ansible checks if it is installed. If it is, it does nothing and reports <code>ok</code>. If it isn't, it installs it and reports <code>changed</code>. This makes it safe to run playbooks repeatedly over your infrastructure.
            </p>
        </div>
    </div>

    <div style="text-align:right;">
        <a href="{{ route('learning.topic', 'roles') }}" class="btn btn-primary">Next: Roles & Reusability ➔</a>
    </div>
</div>
@endsection
