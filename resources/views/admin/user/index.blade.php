@extends('layouts.admin')
@section('title', 'Kelola User')
@push('styles')
<style>
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-weight: 800;
        font-size: 0.95rem;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(5, 150, 105, 0.22);
    }
    .user-info-cell {
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }
    .user-name {
        font-weight: 700;
        color: var(--gray-800);
        font-size: 0.9rem;
    }
    .user-email {
        font-size: 0.78rem;
        color: var(--gray-500);
    }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="page-title">Kelola <span>User</span></div>
        <div class="page-subtitle">Manajemen seluruh akun pengguna (Super Admin, Admin, dan Pemilik)</div>
    </div>
    <a href="{{ route('admin.user.create') }}" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Tambah User Baru
    </a>
</div>

<div class="filter-card">
    <form action="{{ route('admin.user.index') }}" method="GET" class="form-inline" style="flex:1;">
        <div class="search-input-wrapper" style="flex:1; max-width:300px;">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari nama atau email user..." value="{{ request('search') }}" style="width:100%;" aria-label="Cari nama atau email user">
        </div>
        <select name="role" class="form-control" style="width:auto; min-width:160px;" onchange="this.form.submit()" aria-label="Filter Role">
            <option value="">Semua Role</option>
            <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="pemilik" {{ request('role') === 'pemilik' ? 'selected' : '' }}>Pemilik</option>
        </select>
        <div style="display:flex; gap:0.5rem; align-items:center;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
            @if(request()->anyFilled(['search', 'role']))
                <a href="{{ route('admin.user.index') }}" class="btn btn-outline btn-sm" style="white-space:nowrap;">
                    <i class="fas fa-times"></i> Reset
                </a>
            @endif
        </div>
    </form>
    <div style="font-size:0.82rem; color:var(--gray-500); font-weight:600; white-space:nowrap;">
        Total: <strong>{{ $users->total() }}</strong> user
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Role</th>
                    <th>No. Telepon</th>
                    <th>Terdaftar</th>
                    <th style="width:110px; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="user-info-cell">
                            <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            <div>
                                <div class="user-name">
                                    {{ $user->name }}
                                    @if($user->id === auth()->id())
                                        <span class="badge badge-secondary" style="font-size:0.62rem; margin-left:4px;">Anda</span>
                                    @endif
                                </div>
                                <div class="user-email">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @php
                            $roleBadge = match($user->role) {
                                'super_admin' => 'badge-danger',
                                'pemilik'     => 'badge-primary',
                                default       => 'badge-warning',
                            };
                        @endphp
                        <span class="badge {{ $roleBadge }}">
                            {{ ucwords(str_replace('_', ' ', $user->role)) }}
                        </span>
                    </td>
                    <td>{{ $user->phone ?: '—' }}</td>
                    <td style="font-size:0.82rem; color:var(--gray-500); white-space:nowrap;">
                        {{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}
                    </td>
                    <td>
                        <div class="action-group" style="justify-content:center;">
                            <a href="{{ route('admin.user.edit', $user) }}" class="btn-icon btn-icon-amber" title="Edit User">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('admin.user.destroy', $user) }}" method="POST" style="margin:0;" onsubmit="return confirm('Yakin ingin menghapus user {{ addslashes($user->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon btn-icon-red" title="Hapus User">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fas fa-users-slash"></i>
                            <p>Tidak ada user yang ditemukan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div style="padding:1rem 1.25rem; border-top:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; background:var(--gray-50);">
        <div style="font-size:0.82rem; color:var(--gray-600);">
            Menampilkan {{ $users->firstItem() }} - {{ $users->lastItem() }} dari {{ $users->total() }} user
        </div>
        <div>
            {{ $users->links() }}
        </div>
    </div>
    @endif
</div>

@endsection
