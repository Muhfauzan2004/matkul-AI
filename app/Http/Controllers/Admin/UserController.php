<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $this->applySearchFilter($query, $request->search, ['name', 'email']);
        }

        $users = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.user.index', compact('users'));
    }

    public function create()
    {
        return view('admin.user.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:super_admin,admin,pemilik',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        // Only super_admin can create super_admin users
        if ($validated['role'] === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat membuat Super Admin.');
        }

        $validated['password'] = bcrypt($validated['password']);

        User::create($validated);

        return redirect()->route('admin.user.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Anda tidak dapat mengedit Super Admin.');
        }

        return view('admin.user.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:super_admin,admin,pemilik',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Anda tidak dapat mengedit Super Admin.');
        }

        if ($validated['role'] === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat memberikan role Super Admin.');
        }

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.user.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $user->delete();

        return redirect()->route('admin.user.index')->with('success', 'User berhasil dihapus.');
    }
}
