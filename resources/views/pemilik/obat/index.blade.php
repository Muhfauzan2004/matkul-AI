@extends('layouts.pemilik')
@section('title', '')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pemilik/obat-index.css') }}">
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="page-title">Persetujuan <span>Obat Baru</span></div>
        <div style="font-size:0.88rem;color:var(--gray-500);margin-top:4px;font-weight:500;">Tinjau dan konfirmasi pendaftaran obat baru yang diusulkan oleh admin</div>
    </div>
    @php $pendingCount = \App\Models\Obat::where('is_approved', false)->count(); @endphp
    @if($pendingCount > 0)
    <div style="background:var(--accent-3);border:1px solid var(--primary-medium);border-radius:var(--radius-sm);padding:0.65rem 1.15rem;display:flex;align-items:center;gap:0.6rem;box-shadow:var(--shadow);">
        <span style="width:10px;height:10px;background:var(--primary-medium);border-radius:50%;display:inline-block;animation:badgepulse 1.5s infinite;"></span>
        <span style="font-size:0.85rem;font-weight:700;color:var(--primary-medium);">{{ $pendingCount }} obat butuh keputusan Anda</span>
    </div>
    @endif
</div>

{{-- Filter / Search Card --}}
<div class="filter-card">
    <form action="{{ route('pemilik.obat.index') }}" method="GET" style="display:flex;align-items:center;gap:0.75rem;flex:1;flex-wrap:wrap;">
        
        {{-- Search input --}}
        <div style="position:relative; flex:1; min-width:240px; max-width:350px;">
            <i class="fas fa-search" style="position:absolute;left:0.9rem;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:0.9rem;pointer-events:none;"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode obat..." value="{{ request('search') }}" style="padding-left:2.4rem;" aria-label="Cari nama atau kode obat">
        </div>

        {{-- Category Dropdown --}}
        <div style="min-width:180px;">
            <select name="kategori_id" class="form-control" style="cursor:pointer;" aria-label="Filter Kategori">
                <option value="">Semua Kategori</option>
                @foreach($kategoris as $kategori)
                    <option value="{{ $kategori->id }}" {{ request('kategori_id') == $kategori->id ? 'selected' : '' }}>
                        {{ $kategori->nama_kategori }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Status Dropdown --}}
        <div style="min-width:160px;">
            <select name="status" class="form-control" style="cursor:pointer;" aria-label="Filter Status">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
            </select>
        </div>

        {{-- Cari Button --}}
        <div style="display:flex;gap:0.5rem;align-items:center;">
            <button type="submit" class="btn" style="background:var(--primary);color:white;box-shadow:0 4px 12px rgba(0,119,182,0.12);"><i class="fas fa-search"></i> Cari</button>
            @if(request()->filled('search') || request()->filled('kategori_id') || request()->filled('status'))
                <a href="{{ route('pemilik.obat.index') }}" class="btn btn-outline" style="white-space:nowrap;"><i class="fas fa-times"></i> Reset</a>
            @endif
        </div>
    </form>

    {{-- total obat ditemukan --}}
    <div style="font-size:0.85rem;color:var(--gray-500);font-weight:600;white-space:nowrap;background:var(--gray-100);padding:0.4rem 0.85rem;border-radius:99px;border:1px solid var(--gray-200);">
        <i class="fas fa-capsules" style="color:var(--primary);margin-right:0.25rem;"></i>
        Total: <strong>{{ $obats->total() }}</strong> obat ditemukan
    </div>
</div>


<div class="card">
    <div class="table-wrapper">
        <table class="obat-table">
            <thead>
                <tr>
                    <th style="width:100px;">Kode Obat</th>
                    <th>Nama & Deskripsi Obat</th>
                    <th>Kategori</th>
                    <th>Stok Diajukan</th>
                    <th>Harga Jual (Umum/BPJS)</th>
                    <th>Status</th>
                    <th style="width:140px; text-align:center;">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($obats as $obat)
                <tr>
                    <td data-label="Kode Obat"><span class="kode-pill">{{ $obat->kode }}</span></td>
                    <td data-label="Nama Obat">
                        <div class="obat-name">
                            {{ $obat->nama_obat }}
                            <small>{{ $obat->deskripsi ? Str::limit($obat->deskripsi, 55) : '— Tidak ada deskripsi tambahan.' }}</small>
                        </div>
                    </td>
                    <td data-label="Kategori">
                        <span class="badge badge-primary">
                            <i class="fas fa-tag" style="font-size:0.65rem;opacity:0.7;"></i>
                            {{ $obat->kategori->nama_kategori ?? '-' }}
                        </span>
                    </td>
                    <td data-label="Stok Diajukan">
                        <span style="font-weight:800;color:var(--dark);font-size:1.05rem;">{{ $obat->stok }}</span>
                        <small style="color:var(--gray-500);font-size:0.8rem;font-weight:600;"> {{ $obat->satuan }}</small>
                    </td>
                    <td data-label="Harga Jual">
                        <div class="price-col">
                            <span style="color:var(--gray-800);">Rp {{ number_format($obat->harga_umum, 0, ',', '.') }}</span>
                            <small style="color:var(--accent-1);"><i class="fas fa-shield-halved" style="font-size:0.65rem;"></i> BPJS: Rp {{ number_format($obat->harga_jual_bpjs, 0, ',', '.') }}</small>
                        </div>
                    </td>
                    <td data-label="Status">
                        @if($obat->is_approved)
                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Aktif</span>
                        @else
                            <span class="badge badge-warning badge-pending-pulse"><i class="fas fa-hourglass-half"></i> Pending</span>
                        @endif
                    </td>
                    <td data-label="Tindakan" style="text-align:center;">
                        @if(!$obat->is_approved)
                            <div style="display:flex; gap:0.5rem; justify-content:center; align-items:center; flex-wrap:wrap; width:100%;">
                                <form action="{{ route('pemilik.obat.approve', $obat) }}" method="POST" style="display:inline-block; margin:0;">
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn-approve" onclick="return confirm('Konfirmasi dan aktifkan obat {{ addslashes($obat->nama_obat) }}?')">
                                        <i class="fas fa-check"></i> Setujui
                                    </button>
                                </form>
                                <form action="{{ route('pemilik.obat.reject', $obat) }}" method="POST" style="display:inline-block; margin:0;">
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn-reject" onclick="return confirm('Apakah Anda yakin ingin menolak usulan obat {{ addslashes($obat->nama_obat) }}? Pengajuan ini akan dihapus.')">
                                        <i class="fas fa-times"></i> Tolak
                                    </button>
                                </form>
                            </div>
                        @else
                            <span style="font-size:0.8rem;color:var(--gray-400);font-style:italic;font-weight:600;"><i class="fas fa-check" style="color:var(--accent-1);"></i> Terkonfirmasi</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-fancy">
                            <div class="empty-icon"><i class="fas fa-capsules"></i></div>
                            <h4>Tidak Ada Usulan Obat</h4>
                            <p>
                                @if(request('status') === 'pending')
                                    Saat ini tidak ada usulan obat baru yang menunggu keputusan Anda.
                                @elseif(request('status') === 'approved')
                                    Belum ada usulan obat baru yang telah Anda konfirmasi.
                                @else
                                    Belum ada data obat yang terdaftar di dalam sistem apotek ini.
                                @endif
                            </p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($obats->hasPages())
    <div class="card-footer no-print" style="padding: 1rem 1.5rem; border-top: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); gap: 1rem; flex-wrap: wrap;">
        <div style="font-size: 0.85rem; color: var(--gray-600); font-weight: 500;">
            Menampilkan {{ $obats->firstItem() }} - {{ $obats->lastItem() }} dari {{ $obats->total() }} obat
        </div>
        <div style="display: flex; gap: 0.5rem; margin: 0;">
            @if ($obats->onFirstPage())
                <button type="button" class="btn btn-outline" disabled style="opacity: 0.5; cursor: not-allowed; padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 8px; font-family: inherit; display: inline-flex; align-items: center;"><i class="fas fa-chevron-left" style="font-size: 0.7rem; margin-right: 0.25rem;"></i> Sebelumnya</button>
            @else
                <a href="{{ $obats->previousPageUrl() }}" class="btn btn-outline" style="text-decoration: none; padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 8px; color: var(--gray-700); display: inline-flex; align-items: center;"><i class="fas fa-chevron-left" style="font-size: 0.7rem; margin-right: 0.25rem;"></i> Sebelumnya</a>
            @endif

            @if ($obats->hasMorePages())
                <a href="{{ $obats->nextPageUrl() }}" class="btn btn-outline" style="text-decoration: none; padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 8px; color: var(--gray-700); display: inline-flex; align-items: center;">Selanjutnya <i class="fas fa-chevron-right" style="font-size: 0.7rem; margin-left: 0.25rem;"></i></a>
            @else
                <button type="button" class="btn btn-outline" disabled style="opacity: 0.5; cursor: not-allowed; padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 8px; font-family: inherit; display: inline-flex; align-items: center;">Selanjutnya <i class="fas fa-chevron-right" style="font-size: 0.7rem; margin-left: 0.25rem;"></i></button>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
