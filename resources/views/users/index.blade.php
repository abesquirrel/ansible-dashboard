@extends('layouts.app')
@section('title', 'User Management')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px; justify-content: space-between; width: 100%">
        <div>
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle">Configure dashboard access controls and user credentials</p>
        </div>
        <div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Create User
            </a>
        </div>
    </div>
</div>

<div class="page-body">
    {{-- Search / Filter Bar --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-3 items-center">
                <div style="flex: 1">
                    <input type="text" name="search" class="form-input" placeholder="Search by name or email…" value="{{ request('search') }}">
                </div>
                <div style="width: 180px">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="operator" {{ request('role') === 'operator' ? 'selected' : '' }}>Operator</option>
                        <option value="viewer" {{ request('role') === 'viewer' ? 'selected' : '' }}>Viewer</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    @if(request('search') || request('role'))
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="card">
        <div class="card-header">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span class="card-title">Registered Users</span>
        </div>
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="avatar">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                                <div>
                                    <div style="font-weight: 500; color: var(--text-primary)">{{ $user->name }}</div>
                                    <div class="text-mono text-xs text-muted">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge text-mono" style="
                                @if($user->role === 'admin')
                                    background: rgba(57, 217, 138, 0.1); color: var(--green); border: 1px solid rgba(57, 217, 138, 0.2);
                                @elseif($user->role === 'operator')
                                    background: rgba(61, 198, 255, 0.1); color: var(--blue); border: 1px solid rgba(61, 198, 255, 0.2);
                                @else
                                    background: rgba(124, 132, 150, 0.1); color: var(--text-secondary); border: 1px solid rgba(124, 132, 150, 0.2);
                                @endif
                                padding: 2px 6px; border-radius: 4px; font-size: 10px;
                            ">
                                {{ strtoupper($user->role) }}
                            </span>
                        </td>
                        <td x-data="{
                            active: {{ $user->is_active ? 'true' : 'false' }},
                            updating: false,
                            isSelf: {{ $user->id === auth()->id() ? 'true' : 'false' }},
                            async toggle() {
                                if (this.isSelf) return;
                                if (this.updating) return;
                                this.updating = true;
                                try {
                                    const res = await api('{{ route('admin.users.toggle-status', $user) }}', { method: 'POST' });
                                    if (res.success) {
                                        this.active = res.is_active;
                                    } else if (res.error) {
                                        alert(res.error);
                                    }
                                } catch (e) {
                                    alert(e.message || 'An error occurred.');
                                } finally {
                                    this.updating = false;
                                }
                            }
                        }">
                            <button @click="toggle" class="btn btn-sm" :class="active ? 'btn-secondary' : 'btn-danger'" :disabled="isSelf || updating" style="min-width: 90px; justify-content: center">
                                <span x-show="updating" class="spinner" style="width:10px;height:10px;margin-right:4px"></span>
                                <span x-text="active ? 'Active' : 'Inactive'"></span>
                            </button>
                        </td>
                        <td class="text-mono text-xs text-muted">
                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                        </td>
                        <td class="text-xs text-muted">
                            {{ $user->created_at->format('M d, Y H:i') }}
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-secondary">Edit</a>
                                @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Are you sure you want to delete this user?')" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted);font-family:var(--font-mono)">
                            No users found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--border)">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
