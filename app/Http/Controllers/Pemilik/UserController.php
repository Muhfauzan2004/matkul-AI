<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['admin']); // Hanya tampilkan admin, bukan super_admin
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }
        $users = $query->orderBy('name')->paginate(10);
        return view('pemilik.user.index', compact('users'));
    }


    public function create()
    {
        return view('pemilik.user.create');
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

        if ($validated['role'] === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat membuat Super Admin.');
        }

        $validated['password'] = bcrypt($validated['password']);
        User::create($validated);
        return redirect()->route('pemilik.user.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Anda tidak dapat mengedit Super Admin.');
        }
        return view('pemilik.user.edit', compact('user'));
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
        return redirect()->route('pemilik.user.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Anda tidak dapat menghapus Super Admin.');
        }
        $user->delete();
        return redirect()->route('pemilik.user.index')->with('success', 'User berhasil dihapus.');
    }
}
