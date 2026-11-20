@extends('layouts.pemilik')
@section('title', 'Kelola Admin')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pemilik/user-index.css') }}">
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="page-title">Kelola <span>Admin</span></div>
        <div style="font-size:0.88rem;color:var(--gray-500);margin-top:4px;font-weight:500;">Manajemen data akun staf admin pengelola operasional apotek harian</div>
    </div>
    <a href="{{ route('pemilik.user.create') }}" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Tambah Admin Baru
    </a>
</div>

{{-- Filter / Search --}}
<div class="filter-card">
    <form action="{{ route('pemilik.user.index') }}" method="GET" class="form-inline" style="flex:1;">
        <div style="position:relative;">
            <i class="fas fa-search" style="position:absolute;left:0.65rem;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:0.8rem;pointer-events:none;"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari nama atau alamat email admin..." value="{{ request('search') }}" style="padding-left:2rem;min-width:260px;" aria-label="Cari nama atau alamat email admin">
        </div>
        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:nowrap;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
            @if(request()->filled('search'))
                <a href="{{ route('pemilik.user.index') }}" class="btn btn-outline btn-sm" style="white-space:nowrap;"><i class="fas fa-times"></i> Reset</a>
            @endif
        </div>
    </form>
    <div style="font-size:0.8rem;color:var(--gray-500);white-space:nowrap;">
        Total: <strong>{{ $users->total() }}</strong> Staf Admin
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Detail Profil Admin</th>
                    <th>Hak Akses</th>
                    <th>No. Telepon</th>
                    <th>Tanggal Terdaftar</th>
                    <th style="width:110px; text-align:center;">Pilihan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="user-info-cell">
                            <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            <div>
                                <div class="user-name">{{ $user->name }}</div>
                                <div class="user-email">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @php
                            $roleColors = [
                                'admin'       => ['bg' => 'var(--warning-light)', 'color' => 'var(--warning-dark)', 'border' => 'var(--warning-light)'],
                                'pemilik'     => ['bg' => 'var(--accent-4)', 'color' => 'var(--primary-dark)', 'border' => 'var(--accent-3)'],
                                'super_admin' => ['bg' => 'var(--danger-light)', 'color' => 'var(--danger-dark)', 'border' => 'var(--danger-light)'],
                            ];
                            $rc = $roleColors[$user->role] ?? ['bg' => 'var(--gray-50)', 'color' => 'var(--gray-700)', 'border' => 'var(--gray-200)'];
                        @endphp
                        <span style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.3rem 0.8rem;border-radius:99px;font-size:0.75rem;font-weight:700;background:{{ $rc['bg'] }};color:{{ $rc['color'] }};border:1px solid {{ $rc['border'] }};box-shadow: 0 2px 4px rgba(0,0,0,0.01);">
                            <i class="fas fa-user-shield" style="font-size:0.65rem;"></i>
                            {{ ucwords(str_replace('_', ' ', $user->role)) }}
                        </span>
                    </td>
                    <td><div class="phone-cell">{{ $user->phone ?? '—' }}</div></td>
                    <td><div class="date-cell">{{ $user->created_at->format('d M Y') }}</div></td>
                    <td>
                        <div class="action-group" style="justify-content:center;">
                            <a href="{{ route('pemilik.user.edit', $user) }}" class="btn-icon btn-icon-amber" title="Edit Admin">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('pemilik.user.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus admin {{ addslashes($user->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon btn-icon-red" title="Hapus Admin">
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
                        <div class="empty-fancy">
                            <div class="empty-icon"><i class="fas fa-users-slash"></i></div>
                            <h4>Belum Ada Admin Terdaftar</h4>
                            <p>Staf admin yang bertugas mengelola apotek belum tersedia. Silakan klik tombol di bawah untuk mendaftarkan staf pertama.</p>
                            <a href="{{ route('pemilik.user.create') }}" class="btn btn-primary btn-sm" style="margin-top:1rem;">
                                <i class="fas fa-user-plus"></i> Daftarkan Admin
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer no-print" style="padding: 1rem 1.5rem; border-top: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); gap: 1rem; flex-wrap: wrap;">
        <div style="font-size: 0.85rem; color: var(--gray-600); font-weight: 500;">
            Menampilkan {{ $users->firstItem() }} - {{ $users->lastItem() }} dari {{ $users->total() }} admin
        </div>
        <div style="display: flex; gap: 0.5rem; margin: 0;">
            @if ($users->onFirstPage())
                <button type="button" class="btn btn-outline" disabled style="opacity: 0.5; cursor: not-allowed; padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 8px; font-family: inherit; display: inline-flex; align-items: center;"><i class="fas fa-chevron-left" style="font-size: 0.7rem; margin-right: 0.25rem;"></i> Sebelumnya</button>
            @else
                <a href="{{ $users->previousPageUrl() }}" class="btn btn-outline" style="text-decoration: none; padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 8px; color: var(--gray-700); display: inline-flex; align-items: center;"><i class="fas fa-chevron-left" style="font-size: 0.7rem; margin-right: 0.25rem;"></i> Sebelumnya</a>
            @endif

            @if ($users->hasMorePages())
                <a href="{{ $users->nextPageUrl() }}" class="btn btn-outline" style="text-decoration: none; padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 8px; color: var(--gray-700); display: inline-flex; align-items: center;">Selanjutnya <i class="fas fa-chevron-right" style="font-size: 0.7rem; margin-left: 0.25rem;"></i></a>
            @else
                <button type="button" class="btn btn-outline" disabled style="opacity: 0.5; cursor: not-allowed; padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 8px; font-family: inherit; display: inline-flex; align-items: center;">Selanjutnya <i class="fas fa-chevron-right" style="font-size: 0.7rem; margin-left: 0.25rem;"></i></button>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
