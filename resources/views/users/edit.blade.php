@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<div class="page-header">
    <div style="padding-bottom:20px">
        <h1 class="page-title">Edit User</h1>
        <p class="page-subtitle">Modify profile settings, role levels or reset user passwords</p>
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
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                <span class="card-title">Modify Credentials</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="form-group" style="margin-top: 24px; border-top: 1px solid var(--border); padding-top: 20px">
                        <label class="form-label">New Password (Optional)</label>
                        <input type="password" name="password" class="form-input" placeholder="Leave blank to keep current password">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-input" placeholder="Repeat new password">
                    </div>

                    <div class="form-group" style="margin-top: 24px; border-top: 1px solid var(--border); padding-top: 20px">
                        <label class="form-label">Role</label>
                        @if($user->id === auth()->id())
                            <div class="code-block" style="font-size:12px; margin-bottom: 8px">ADMINISTRATOR (Self)</div>
                            <input type="hidden" name="role" value="admin">
                            <div class="form-hint">You cannot change your own administrator role.</div>
                        @else
                            <select name="role" class="form-select" required>
                                <option value="viewer" {{ old('role', $user->role) === 'viewer' ? 'selected' : '' }}>Viewer (Read-only)</option>
                                <option value="operator" {{ old('role', $user->role) === 'operator' ? 'selected' : '' }}>Operator (Run playbooks, view inventory)</option>
                                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Administrator (Full settings/user access)</option>
                            </select>
                        @endif
                    </div>

                    <div class="form-group">
                        @if($user->id === auth()->id())
                            <input type="hidden" name="is_active" value="1">
                            <label class="flex items-center gap-2 text-sm text-mono text-muted" style="cursor:not-allowed; margin-top: 20px">
                                <input type="checkbox" disabled checked>
                                <span>Activate User Account Immediately (Self is always active)</span>
                            </label>
                        @else
                            <label class="flex items-center gap-2 text-sm text-mono" style="cursor:pointer; margin-top: 20px">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                <span>Activate User Account Immediately</span>
                            </label>
                            <div class="form-hint">Inactive accounts are restricted from logging in.</div>
                        @endif
                    </div>

                    <div class="flex gap-2" style="margin-top: 24px">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
