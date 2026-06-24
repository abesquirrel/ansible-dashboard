<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->get('role'));
        }

        $users = $query->latest()->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => 'required|string|min:6|confirmed',
            'role'      => 'required|in:admin,operator,viewer',
            'is_active' => 'nullable|boolean',
        ]);

        User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => $data['role'],
            'is_admin'  => $data['role'] === 'admin',
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password'  => 'nullable|string|min:6|confirmed',
            'role'      => 'required|in:admin,operator,viewer',
            'is_active' => 'nullable|boolean',
        ]);

        // Safety: Prevent self-deactivation
        if ($user->id === Auth::id() && !$request->has('is_active')) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        // Safety: Prevent self-demotion (changing own role away from admin)
        if ($user->id === Auth::id() && $data['role'] !== 'admin') {
            return back()->with('error', 'You cannot change your own administrator role.');
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];
        $user->is_admin = $data['role'] === 'admin';
        $user->is_active = $request->has('is_active');

        if ($request->filled('password')) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'You cannot deactivate your own account.'], 400);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success'   => true,
            'is_active' => $user->is_active,
            'message'   => 'User status updated successfully.'
        ]);
    }
}
