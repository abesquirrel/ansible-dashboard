@extends('layouts.app')
@section('title', 'Learning Hub: Inventory & Ad-hoc')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px">
        <div>
            <h1 class="page-title">2. Inventory & Ad-Hoc Commands</h1>
            <p class="page-subtitle">Defining your fleet and running quick tasks.</p>
        </div>
        <div class="ml-auto flex gap-2">
            <a href="{{ route('learning.topic', 'basics') }}" class="btn btn-sm btn-secondary">Back</a>
            <a href="{{ route('learning.index') }}" class="btn btn-sm btn-secondary">Hub</a>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">The Inventory</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                Ansible needs to know what machines it manages. This list is called an <strong>Inventory</strong>. By default, it's often located at <code>/etc/ansible/hosts</code>, but it's best practice to keep an inventory file inside your project repository.
            </p>
            <div class="grid-2">
                <div>
                    <h4 style="font-size:12px; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase;">INI Format</h4>
                    <div class="code-block">
[webservers]
web1.example.com
web2.example.com

[dbservers]
db1.example.com ansible_user=admin
                    </div>
                </div>
                <div>
                    <h4 style="font-size:12px; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase;">YAML Format</h4>
                    <div class="code-block">
all:
  children:
    webservers:
      hosts:
        web1.example.com:
        web2.example.com:
    dbservers:
      hosts:
        db1.example.com:
          ansible_user: admin
                    </div>
                </div>
            </div>
            <p style="color:var(--text-secondary); line-height:1.6; margin-top:16px;">
                Groups (like <code>[webservers]</code>) allow you to target subsets of your infrastructure easily.
            </p>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">Ad-Hoc Commands</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                Ad-hoc commands are quick, one-off tasks you run without writing a full playbook. They are perfect for fast operations like rebooting servers, checking uptime, or managing a package.
            </p>
            <div class="code-block" style="margin-bottom:16px;">
# Check connectivity to all hosts
ansible all -m ping

# Check disk space on webservers
ansible webservers -m command -a "df -h"

# Install nginx
ansible webservers -m apt -a "name=nginx state=latest" --become
            </div>
            
            <h3 style="font-size:14px; color:var(--accent); margin-bottom:8px;">Breakdown:</h3>
            <ul style="color:var(--text-secondary); line-height:1.6; margin-left:20px;">
                <li><code>all</code> or <code>webservers</code>: The target hosts from your inventory.</li>
                <li><code>-m</code>: The <strong>module</strong> to use (e.g., ping, command, apt).</li>
                <li><code>-a</code>: The arguments passed to the module.</li>
                <li><code>--become</code>: Run with elevated privileges (sudo).</li>
            </ul>
        </div>
    </div>

    <div class="card mb-6" style="border-color:var(--accent);">
        <div class="card-header" style="background:rgba(57,217,138,.05);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                <polygon points="5 3 19 12 5 21 5 3"/>
            </svg>
            <span class="card-title text-green" style="margin-left:8px;">Try it out!</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6;">
                Head over to the <a href="{{ route('inventory.index') }}" style="color:var(--accent); text-decoration:none;">Inventory</a> page in this dashboard. Click the <strong>Ad-hoc</strong> tab and try running the <code>ping</code> module against the <code>all</code> target!
            </p>
        </div>
    </div>

    <div style="text-align:right;">
        <a href="{{ route('learning.topic', 'playbooks') }}" class="btn btn-primary">Next: Playbooks & Modules ➔</a>
    </div>
</div>
@endsection
