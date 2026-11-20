@extends('layouts.admin')
@section('title', 'Kategori Obat')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/kategori.css') }}">
@endpush
@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <div class="page-title">Kategori <span>Obat</span></div>
        <div class="page-subtitle">Kelola kategori untuk pengelompokan inventaris obat</div>
    </div>
    <a href="{{ route('admin.kategori.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Kategori
    </a>
</div>

{{-- STAT CARDS --}}
<div class="kat-stat-grid">
    <div class="kat-stat">
        <div class="ks-accent" style="background: var(--primary);"></div>
        <div class="ks-icon" style="background: var(--accent-4); color: var(--primary);">
            <i class="fas fa-tags"></i>
        </div>
        <div style="padding-left: 0.25rem;">
            <div class="ks-label">Total Kategori</div>
            <div class="ks-value">{{ $kategoris->total() }}</div>
        </div>
    </div>
    <div class="kat-stat">
        <div class="ks-accent" style="background: var(--success);"></div>
        <div class="ks-icon" style="background: var(--success-light); color: var(--success);">
            <i class="fas fa-pills"></i>
        </div>
        <div style="padding-left: 0.25rem;">
            <div class="ks-label">Total Obat Terdaftar</div>
            <div class="ks-value">{{ $kategoris->sum('obat_count') }}</div>
        </div>
    </div>
    <div class="kat-stat">
        <div class="ks-accent" style="background: var(--warning);"></div>
        <div class="ks-icon" style="background: var(--warning-light); color: var(--warning-dark);">
            <i class="fas fa-star"></i>
        </div>
        <div style="padding-left: 0.25rem;">
            <div class="ks-label">Kategori Terpopuler</div>
            @php $topKat = $kategoris->sortByDesc('obat_count')->first(); @endphp
            <div class="ks-value" style="font-size: 0.85rem; margin-top: 3px; line-height: 1.2;">
                {{ $topKat ? Str::limit($topKat->nama_kategori, 20) : '—' }}
            </div>
        </div>
    </div>
</div>

{{-- SEARCH BAR --}}
<div class="filter-card" style="margin-bottom: 1rem;">
    <form action="{{ route('admin.kategori.index') }}" method="GET" class="form-inline" style="flex: 1;">
        <div class="search-input-wrapper" style="flex: 1; max-width: 380px;">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari nama kategori..." value="{{ request('search') }}" aria-label="Cari nama kategori">
        </div>
        @if(request('search'))
            <a href="{{ route('admin.kategori.index') }}" class="btn btn-outline btn-sm">
                <i class="fas fa-times"></i> Reset
            </a>
        @endif
    </form>
    <div style="font-size: 0.8rem; color: var(--gray-500); white-space: nowrap;">
        {{ $kategoris->total() }} kategori ditemukan
    </div>
</div>

{{-- KATEGORI LIST --}}
<div class="card">
    @if($kategoris->isEmpty())
        <div class="empty-fancy">
            <div class="empty-icon"><i class="fas fa-tags"></i></div>
            <h4>Belum ada kategori</h4>
            <p>Buat kategori pertama untuk mengelompokkan obat-obatan Anda</p>
            <a href="{{ route('admin.kategori.create') }}" class="btn btn-primary btn-sm" style="margin-top: 0.75rem;">
                <i class="fas fa-plus"></i> Tambah Kategori
            </a>
        </div>
    @else
        {{-- Table Header --}}
        <div style="padding: 0.65rem 1.25rem; background: var(--gray-50); border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; gap: 1rem;">
            <div style="width: 38px; flex-shrink: 0;"></div>
            <div style="flex: 1; font-size: 0.7rem; font-weight: 700; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.06em;">Nama Kategori</div>
            <div style="width: 120px; font-size: 0.7rem; font-weight: 700; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.06em; text-align: center;">Jumlah Obat</div>
            <div style="width: 80px; font-size: 0.7rem; font-weight: 700; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.06em; text-align: center;">Aksi</div>
        </div>

        @foreach($kategoris as $kat)
        <div class="kat-list-item">
            {{-- Icon --}}
            <div class="kat-list-icon">
                <i class="fas fa-tag"></i>
            </div>

            {{-- Info --}}
            <div class="kat-list-info">
                <div class="kat-list-name">{{ $kat->nama_kategori }}</div>
                <div class="kat-list-desc">
                    <span class="kat-list-slug">{{ $kat->slug }}</span>
                    @if($kat->deskripsi)
                        · {{ Str::limit($kat->deskripsi, 60) }}
                    @endif
                </div>
            </div>

            {{-- Obat Count --}}
            <div style="width: 120px; text-align: center;">
                <span class="kat-obat-badge">
                    <i class="fas fa-pills" style="font-size: 0.65rem;"></i>
                    {{ $kat->obat_count }}
                </span>
            </div>

            {{-- Actions --}}
            <div class="kat-list-actions" style="width: 80px; justify-content: center;">
                <a href="{{ route('admin.kategori.edit', $kat) }}" class="btn-icon btn-icon-amber" title="Edit Kategori">
                    <i class="fas fa-edit"></i>
                </a>
                <form action="{{ route('admin.kategori.destroy', $kat) }}" method="POST"
                      onsubmit="return confirm('Hapus kategori {{ $kat->nama_kategori }}? Obat di dalamnya tidak akan terhapus.')">
                    @csrf @method('DELETE')
                    <button class="btn-icon btn-icon-red" title="Hapus Kategori">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        @endforeach

        @if($kategoris->hasPages())
        <div style="padding: 0.85rem 1.25rem; border-top: 1px solid var(--gray-200); background: var(--gray-50);">
            {{ $kategoris->withQueryString()->links('pagination::bootstrap-4') }}
        </div>
        @endif
    @endif
</div>

@endsection
