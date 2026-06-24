@extends('layouts.app')
@section('title', 'Create User')

@section('content')
<div class="page-header">
    <div style="padding-bottom:20px">
        <h1 class="page-title">Create User</h1>
        <p class="page-subtitle">Add a new user with specific role and system access level</p>
    </div>
</div>

<div class="page-body">
    <div style="max-width: 600px">
        @if ($errors->any())
            <div class="alert alert-error" style="margin-bottom: 20px">
                <ul style="margin: 0; padding-left: 16px; font-family: var(--font-mono)">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <line x1="19" y1="8" x2="19" y2="14"/>
                    <line x1="16" y1="11" x2="22" y2="11"/>
                </svg>
                <span class="card-title">New User Credentials</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-input" value="{{ old('name') }}" placeholder="e.g. John Doe" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input" value="{{ old('email') }}" placeholder="e.g. john@example.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-input" placeholder="Min. 6 characters" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-input" placeholder="Repeat password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="viewer" {{ old('role') === 'viewer' ? 'selected' : '' }}>Viewer (Read-only)</option>
                            <option value="operator" {{ old('role', 'operator') === 'operator' ? 'selected' : '' }}>Operator (Run playbooks, view inventory)</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator (Full settings/user access)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm text-mono" style="cursor:pointer; margin-top: 20px">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                            <span>Activate User Account Immediately</span>
                        </label>
                        <div class="form-hint">Inactive accounts are restricted from logging in.</div>
                    </div>

                    <div class="flex gap-2" style="margin-top: 24px">
                        <button type="submit" class="btn btn-primary">Create User</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
