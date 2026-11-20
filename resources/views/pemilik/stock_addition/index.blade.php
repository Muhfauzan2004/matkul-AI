@extends('layouts.pemilik')
@section('title', 'Persetujuan Stok Masuk')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pemilik/stock-addition-index.css') }}">
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="page-title">Persetujuan <span>Stok Masuk</span></div>
        <div style="font-size:0.88rem;color:var(--gray-500);margin-top:4px;font-weight:500;">Konfirmasi atau tolak pengajuan penambahan stok obat yang diajukan oleh admin</div>
    </div>
    @php $pendingCount = \App\Models\StockAddition::where('status', 'pending')->count(); @endphp
    @if($pendingCount > 0)
    <div style="background:var(--accent-3);border:1px solid var(--primary-medium);border-radius:var(--radius-sm);padding:0.65rem 1.15rem;display:flex;align-items:center;gap:0.8rem;box-shadow:var(--shadow);flex-wrap:wrap;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:0.6rem;">
            <span style="width:10px;height:10px;background:var(--primary-medium);border-radius:50%;display:inline-block;animation:badgepulse 1.5s infinite;"></span>
            <span style="font-size:0.85rem;font-weight:700;color:var(--primary-medium);">{{ $pendingCount }} pengajuan butuh keputusan Anda</span>
        </div>
        <form action="{{ route('pemilik.stock.approveAll') }}" method="POST" style="margin:0;">
            @csrf
            @method('PUT')
            <button type="submit" class="btn btn-primary" style="white-space:nowrap; padding:0.45rem 1rem; font-size:0.8rem;" onclick="return confirm('Konfirmasi semua pengajuan penambahan stok ini?')">Konfirmasi Semua</button>
        </form>
    </div>
    @endif
</div>

{{-- Stats Row --}}
<div class="stock-stats">
    <div class="stock-stat-card">
        <div class="stock-stat-icon" style="background: #fef3c7; color: #f59e0b; border: 1px solid #fcd34d;">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="stock-stat-body">
            <h4>{{ $additions->where('status', 'pending')->count() }}</h4>
            <p>Menunggu Persetujuan</p>
        </div>
    </div>
    <div class="stock-stat-card">
        <div class="stock-stat-icon" style="background: #dcfce7; color: #10b981; border: 1px solid #a7f3d0;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stock-stat-body">
            <h4>{{ $additions->where('status', 'approved')->count() }}</h4>
            <p>Disetujui (Halaman Ini)</p>
        </div>
    </div>
    <div class="stock-stat-card">
        <div class="stock-stat-icon" style="background: #fee2e2; color: #ef4444; border: 1px solid #fecaca;">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="stock-stat-body">
            <h4>{{ $additions->where('status', 'rejected')->count() }}</h4>
            <p>Ditolak (Halaman Ini)</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="stock-table">
            <thead>
                <tr>
                    <th>Tanggal Pengajuan</th>
                    <th>Detail Obat & Stok Saat Ini</th>
                    <th>Stok Ditambahkan</th>
                    <th>Catatan Admin</th>
                    <th>Status Pengajuan</th>
                    <th style="width:110px; text-align:center;">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($additions as $item)
                <tr>
                    <td>
                        <div class="date-cell">
                            {{ $item->created_at->format('d M Y') }}
                            <small>{{ $item->created_at->format('H:i') }} WIB</small>
                        </div>
                    </td>
                    <td>
                        <div class="obat-name-cell">
                            {{ $item->obat->nama_obat ?? '— Obat tidak teridentifikasi' }}
                            <small>Stok saat ini: <strong style="color:var(--dark);">{{ $item->obat->stok ?? 0 }}</strong> {{ $item->obat->satuan ?? '' }}</small>
                        </div>
                    </td>
                    <td>
                        <div class="qty-badge">
                            <i class="fas fa-plus" style="font-size:0.7rem;color:var(--primary);opacity:0.8;"></i>
                            {{ $item->jumlah }}
                        </div>
                        <div style="font-size:0.75rem;color:var(--gray-500);margin-top:2px;font-weight:600;">{{ $item->obat->satuan ?? '' }}</div>
                    </td>
                    <td>
                        <div class="catatan-cell">{{ $item->catatan ?: '— Tidak ada catatan.' }}</div>
                    </td>
                    <td>
                        @if($item->status == 'pending')
                            <span class="badge badge-warning badge-pending-pulse">
                                <i class="fas fa-clock" style="font-size:0.65rem;"></i> Pending
                            </span>
                        @elseif($item->status == 'approved')
                            <span class="badge badge-success">
                                <i class="fas fa-check-circle" style="font-size:0.65rem;"></i> Disetujui
                            </span>
                        @else
                            <span class="badge badge-danger">
                                <i class="fas fa-times-circle" style="font-size:0.65rem;"></i> Ditolak
                            </span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($item->status == 'pending')
                            <div style="display:flex;gap:0.5rem;justify-content:center;align-items:center;">
                                <form action="{{ route('pemilik.stock.approve', $item) }}" method="POST">
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn-action-approve" title="Setujui Pengajuan" onclick="return confirm('Setujui penambahan stok {{ addslashes($item->obat->nama_obat ?? '') }}?')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form action="{{ route('pemilik.stock.reject', $item) }}" method="POST">
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn-action-reject" title="Tolak Pengajuan" onclick="return confirm('Tolak penambahan stok ini?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        @else
                            <span style="font-size:0.8rem;color:var(--gray-400);font-style:italic;font-weight:600;"><i class="fas fa-check-double" style="color:var(--primary);"></i> Selesai</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-fancy">
                            <div class="empty-icon"><i class="fas fa-boxes-stacked"></i></div>
                            <h4>Belum Ada Pengajuan Stok</h4>
                            <p>Semua penambahan stok masuk yang diajukan oleh admin apotek akan tampil di halaman ini.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($additions->hasPages())
    <div class="card-footer no-print" style="padding: 1rem 1.5rem; border-top: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); gap: 1rem; flex-wrap: wrap;">
        <div style="font-size: 0.85rem; color: var(--gray-600); font-weight: 500;">
            Menampilkan {{ $additions->firstItem() }} - {{ $additions->lastItem() }} dari {{ $additions->total() }} pengajuan stok
        </div>
        <div style="display: flex; gap: 0.5rem; margin: 0;">
            @if ($additions->onFirstPage())
                <button type="button" class="btn btn-outline" disabled style="opacity: 0.5; cursor: not-allowed; padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 8px; font-family: inherit; display: inline-flex; align-items: center;"><i class="fas fa-chevron-left" style="font-size: 0.7rem; margin-right: 0.25rem;"></i> Sebelumnya</button>
            @else
                <a href="{{ $additions->previousPageUrl() }}" class="btn btn-outline" style="text-decoration: none; padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 8px; color: var(--gray-700); display: inline-flex; align-items: center;"><i class="fas fa-chevron-left" style="font-size: 0.7rem; margin-right: 0.25rem;"></i> Sebelumnya</a>
            @endif

            @if ($additions->hasMorePages())
                <a href="{{ $additions->nextPageUrl() }}" class="btn btn-outline" style="text-decoration: none; padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 8px; color: var(--gray-700); display: inline-flex; align-items: center;">Selanjutnya <i class="fas fa-chevron-right" style="font-size: 0.7rem; margin-left: 0.25rem;"></i></a>
            @else
                <button type="button" class="btn btn-outline" disabled style="opacity: 0.5; cursor: not-allowed; padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 8px; font-family: inherit; display: inline-flex; align-items: center;">Selanjutnya <i class="fas fa-chevron-right" style="font-size: 0.7rem; margin-left: 0.25rem;"></i></button>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection
