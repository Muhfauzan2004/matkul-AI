@extends('layouts.pemilik')
@section('title', '')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/report.css') }}">
<style>
    /* Premium Refined Styles specifically for the Owner Stock Report View */
    .report-title-gradient {
        background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
    }
    
    .period-badge-premium {
        background: var(--accent-4);
        color: var(--primary-dark);
        padding: 0.35rem 0.85rem;
        border-radius: 30px;
        font-size: 0.82rem;
        font-weight: 600;
        border: 1px solid var(--accent-3);
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .kpi-container-premium {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .kpi-card-premium {
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius);
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow-md);
        transition: var(--transition);
    }
    
    .kpi-card-premium:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    
    .kpi-card-premium::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 6px;
        height: 100%;
    }
    
    .kpi-card-premium.blue::before { background: linear-gradient(180deg, var(--primary-light), var(--primary-dark)); }
    .kpi-card-premium.warning::before { background: linear-gradient(180deg, var(--warning), var(--warning-dark)); }
    .kpi-card-premium.success::before { background: linear-gradient(180deg, var(--success), var(--success-dark)); }
    
    .kpi-info-premium {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        z-index: 2;
    }
    
    .kpi-label-premium {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--gray-600);
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    
    .kpi-value-premium {
        font-size: 1.85rem;
        font-weight: 800;
        color: var(--gray-800);
        display: flex;
        align-items: baseline;
        gap: 0.35rem;
        line-height: 1.2;
    }
    
    .kpi-unit-premium {
        font-size: 0.85rem;
        color: var(--gray-600);
        font-weight: 500;
        text-transform: lowercase;
    }
    
    .kpi-icon-premium {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        transition: var(--transition);
        z-index: 2;
    }
    
    .kpi-card-premium:hover .kpi-icon-premium {
        transform: scale(1.15) rotate(8deg);
    }
    
    .kpi-card-premium.blue .kpi-icon-premium {
        background: var(--accent-4);
        color: var(--primary-dark);
        box-shadow: 0 4px 10px rgba(0, 180, 216, 0.15);
    }
    
    .kpi-card-premium.warning .kpi-icon-premium {
        background: var(--warning-light);
        color: var(--warning-dark);
        box-shadow: 0 4px 10px rgba(245, 158, 11, 0.15);
    }
    
    .kpi-card-premium.success .kpi-icon-premium {
        background: var(--success-light);
        color: var(--success-dark);
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.15);
    }
    
    .filter-panel-premium {
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-left: 5px solid var(--primary);
        border-radius: var(--radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
    }
    
    .form-grid-premium {
        display: flex;
        flex-wrap: wrap;
        gap: 1.25rem;
        align-items: flex-end;
    }
    
    .form-group-premium {
        flex: 1;
        min-width: 200px;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }
    
    .form-label-premium {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--gray-600);
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    .form-control-premium {
        width: 100%;
        padding: 0.65rem 1rem;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        font-family: inherit;
        font-size: 0.88rem;
        color: var(--gray-800);
        transition: var(--transition);
        background: var(--gray-50);
    }
    
    .form-control-premium:focus {
        outline: none;
        border-color: var(--primary);
        background: var(--white);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }
    
    .btn-premium-group {
        display: flex;
        gap: 0.75rem;
        align-self: flex-end;
    }
    
    .btn-premium-primary {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: var(--white);
        border: none;
        box-shadow: 0 4px 10px rgba(0, 119, 182, 0.2);
    }
    
    .btn-premium-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 119, 182, 0.3);
        color: var(--white);
    }
    
    .btn-premium-outline {
        background: var(--white);
        color: var(--gray-600);
        border: 1.5px solid var(--gray-300);
    }
    
    .btn-premium-outline:hover {
        background: var(--gray-50);
        border-color: var(--gray-400);
        color: var(--gray-800);
    }
    
    .card-premium {
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        margin-bottom: 2.5rem;
        transition: var(--transition);
    }
    
    .card-premium:hover {
        box-shadow: var(--shadow-lg);
    }
    
    .card-header-premium {
        padding: 1.25rem 1.5rem;
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    
    .card-title-premium {
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--gray-800);
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    
    .card-badge-premium {
        font-size: 0.72rem;
        color: var(--gray-700);
        font-weight: 700;
        background: var(--white);
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        border: 1.5px solid var(--gray-200);
        box-shadow: var(--shadow);
    }
    
    .table-premium {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    
    .table-premium th {
        background: var(--gray-50);
        padding: 1rem 1.5rem;
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 700;
        color: var(--gray-700);
        border-bottom: 1.5px solid var(--gray-200);
    }
    
    .table-premium td {
        padding: 1.1rem 1.5rem;
        font-size: 0.88rem;
        color: var(--gray-700);
        border-bottom: 1px solid var(--gray-100);
        vertical-align: middle;
    }
    
    .table-premium tr:last-child td {
        border-bottom: none;
    }
    
    .table-premium tr {
        transition: var(--transition);
    }
    
    .table-premium tr:hover td {
        background: rgba(0, 119, 182, 0.015);
    }
    
    .kode-badge-premium {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 0.78rem;
        letter-spacing: 0.02em;
        background: var(--accent-4);
        color: var(--primary-dark);
        padding: 0.25rem 0.65rem;
        border-radius: 6px;
        border: 1px solid var(--accent-3);
    }
    
    .category-badge-premium {
        font-size: 0.75rem;
        font-weight: 600;
        background: rgba(0, 180, 216, 0.08);
        color: var(--primary-dark);
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
        border: 1px solid rgba(0, 180, 216, 0.15);
    }
    
    .quantity-badge-plus {
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--success-dark);
        background: var(--success-light);
        padding: 0.25rem 0.75rem;
        border-radius: 30px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .quantity-badge-minus {
        font-size: 0.9rem;
        font-weight: 800;
        color: #b91c1c;
        background: #fee2e2;
        padding: 0.25rem 0.75rem;
        border-radius: 30px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        border: 1px solid rgba(239, 68, 68, 0.25);
    }
    
    .stok-level-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 700;
    }
    
    .stok-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    
    .stok-dot.danger { background: var(--danger); box-shadow: 0 0 8px var(--danger); }
    .stok-dot.success { background: var(--success); box-shadow: 0 0 8px var(--success); }
    .stok-dot.warning { background: var(--warning); box-shadow: 0 0 8px var(--warning); }
    
    .empty-state-premium {
        padding: 4rem 2rem;
        text-align: center;
    }
    
    .empty-state-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: var(--gray-50);
        color: var(--gray-500);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-bottom: 1.25rem;
        border: 1px dashed var(--gray-300);
    }
    
    .empty-state-title {
        font-weight: 700;
        color: var(--gray-700);
        font-size: 1.05rem;
        margin-bottom: 0.35rem;
    }
    
    .empty-state-desc {
        color: var(--gray-600);
        font-size: 0.85rem;
        max-width: 340px;
        margin: 0 auto;
        line-height: 1.5;
    }
    
    .page-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 1.5rem !important;
        flex-wrap: wrap !important;
        gap: 0.75rem !important;
    }
    
    .export-btn-group {
        margin-left: auto !important;
    }
    
    @media (max-width: 640px) {
        .page-header {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
        .export-btn-group {
            align-self: flex-end !important;
            margin-top: 0.5rem !important;
        }
    }
    
    /* Layout formatting for print mode */
    @media print {
        .filter-panel-premium, .no-print, .btn-premium-group {
            display: none !important;
        }
        .card-premium {
            box-shadow: none !important;
            border: 1.5px solid var(--gray-300);
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .table-premium td, .table-premium th {
            padding: 0.65rem 0.85rem;
            font-size: 0.76rem;
        }
        .kpi-container-premium {
            gap: 1rem;
        }
        .kpi-card-premium {
            box-shadow: none !important;
            border: 1.5px solid var(--gray-300);
            border-radius: 8px;
            padding: 1rem;
        }
    }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="page-title"><span class="report-title-gradient">Laporan Stok & Inventaris Masuk</span></div>
        <div style="margin-top: 6px;">
            <span class="period-badge-premium">
                <i class="far fa-calendar-alt"></i> Periode: 
                <strong>{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</strong>
                — 
                <strong>{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</strong>
            </span>
        </div>
    </div>
    <div class="export-btn-group no-print">
        <button onclick="window.print()" class="btn-export btn-export-print" style="border-radius: var(--radius-sm); padding: 0.55rem 1.1rem; box-shadow: 0 4px 10px rgba(0, 180, 216, 0.15);">
            <i class="fas fa-print"></i> Cetak Laporan
        </button>
    </div>
</div>

{{-- KPI Cards --}}
<div class="kpi-container-premium">
    <div class="kpi-card-premium blue">
        <div class="kpi-info-premium">
            <div class="kpi-label-premium">Barang Baru Terdaftar</div>
            <div class="kpi-value-premium">
                {{ $totalNewObat }}
                <span class="kpi-unit-premium">obat</span>
            </div>
        </div>
        <div class="kpi-icon-premium">
            <i class="fas fa-prescription-bottle-alt"></i>
        </div>
    </div>
    <div class="kpi-card-premium warning">
        <div class="kpi-info-premium">
            <div class="kpi-label-premium">Transaksi Restok</div>
            <div class="kpi-value-premium">
                {{ $totalRestockAdditions }}
                <span class="kpi-unit-premium">kali</span>
            </div>
        </div>
        <div class="kpi-icon-premium">
            <i class="fas fa-dolly-flatbed"></i>
        </div>
    </div>
    <div class="kpi-card-premium success">
        <div class="kpi-info-premium">
            <div class="kpi-label-premium">Total Unit Ditambahkan</div>
            <div class="kpi-value-premium">
                {{ $totalQtyRestocked }}
                <span class="kpi-unit-premium">pcs</span>
            </div>
        </div>
        <div class="kpi-icon-premium">
            <i class="fas fa-boxes"></i>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="filter-panel-premium no-print">
    <h3 style="font-size:0.9rem; font-weight:800; color:var(--gray-800); margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem;">
        <i class="fas fa-calendar-check" style="color:var(--primary);"></i> Filter Rentang Tanggal Laporan
    </h3>
    <form action="{{ route('pemilik.reports.stock') }}" method="GET">
        <div class="form-grid-premium">
            <div class="form-group-premium">
                <label class="form-label-premium" for="start_date">
                    <i class="far fa-calendar-play" style="color:var(--gray-500);"></i> Dari Tanggal
                </label>
                <input type="date" id="start_date" name="start_date" class="form-control-premium" value="{{ $startDate }}" required>
            </div>
            <div class="form-group-premium">
                <label class="form-label-premium" for="end_date">
                    <i class="far fa-calendar-stop" style="color:var(--gray-500);"></i> Sampai Tanggal
                </label>
                <input type="date" id="end_date" name="end_date" class="form-control-premium" value="{{ $endDate }}" required>
            </div>
            <div class="btn-premium-group">
                <button type="submit" class="btn btn-premium-primary" style="padding: 0.65rem 1.25rem; font-weight:700; border-radius: var(--radius-sm); transition: var(--transition);">
                    <i class="fas fa-filter"></i> Terapkan Filter
                </button>
                <a href="{{ route('pemilik.reports.stock') }}" class="btn btn-premium-outline" style="padding: 0.65rem 1.25rem; font-weight:700; border-radius: var(--radius-sm); text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem; transition: var(--transition);">
                    <i class="fas fa-undo"></i> Reset Bulan Ini
                </a>
            </div>
        </div>
    </form>
</div>

{{-- TABEL 1: PENCATATAN BARANG BARU MASUK --}}
<div class="card-premium">
    <div class="card-header-premium">
        <div class="card-title-premium">
            <i class="fas fa-plus-circle" style="color:var(--primary); font-size:1.1rem;"></i> 
            <span>Pencatatan Barang Baru Terdaftar</span>
        </div>
        <span class="card-badge-premium">
            <i class="fas fa-tag" style="margin-right:2px; color:var(--primary);"></i> {{ $totalNewObat }} item obat baru
        </span>
    </div>
    <div class="table-wrapper">
        <table class="table-premium">
            <thead>
                <tr>
                    <th style="width: 120px;">Kode</th>
                    <th>Nama Obat</th>
                    <th>Kategori</th>
                    <th>Harga Umum</th>
                    <th>Harga Barang</th>
                    <th style="width: 150px;">Stok Terdaftar</th>
                    <th>Tanggal Terdaftar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($newObat as $obat)
                <tr>
                    <td><span class="kode-badge-premium">{{ $obat->kode }}</span></td>
                    <td style="font-weight:700; color:var(--gray-800);">{{ $obat->nama_obat }}</td>
                    <td>
                        <span class="category-badge-premium">{{ $obat->kategori->nama_kategori ?? 'Umum' }}</span>
                    </td>
                    <td style="font-weight:700; color:var(--gray-800);">Rp {{ number_format($obat->harga_umum, 0, ',', '.') }}</td>
                    <td style="color:var(--gray-600); font-weight: 500;">Rp {{ number_format($obat->harga_barang ?? 0, 0, ',', '.') }}</td>
                    <td>
                        <div class="stok-level-indicator">
                            <span class="stok-dot {{ $obat->stok < 10 ? 'danger' : 'success' }}"></span>
                            <span style="font-weight: 800; color:var(--gray-800);">{{ $obat->stok }}</span>
                            <span style="font-weight: 400; font-size:0.75rem; color:var(--gray-600);">{{ $obat->satuan }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:600; color:var(--gray-700);">{{ $obat->created_at->format('d/m/Y') }}</div>
                        <div style="font-size:0.72rem; color:var(--gray-600); margin-top:2px;"><i class="far fa-clock" style="margin-right:2px;"></i>{{ $obat->created_at->format('H:i') }} WIB</div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state-premium">
                            <div class="empty-state-icon">
                                <i class="fas fa-prescription-bottle"></i>
                            </div>
                            <div class="empty-state-title">Tidak Ada Barang Terdaftar</div>
                            <div class="empty-state-desc">Pendaftaran obat baru tidak ditemukan dalam periode filter tanggal ini.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($newObat->hasPages())
    <div class="card-footer no-print" style="padding: 1rem 1.5rem; border-top: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); gap: 1rem; flex-wrap: wrap;">
        <div style="font-size: 0.8rem; color: var(--gray-600); font-weight: 600;">
            Menampilkan {{ $newObat->firstItem() }} - {{ $newObat->lastItem() }} dari {{ $newObat->total() }} obat baru
        </div>
        <div class="btn-premium-group" style="display: flex; gap: 0.5rem; margin: 0;">
            @if ($newObat->onFirstPage())
                <button type="button" class="btn btn-premium-outline" disabled style="opacity: 0.5; cursor: not-allowed; padding: 0.4rem 0.85rem; font-size: 0.78rem; border-radius: var(--radius-sm); font-family: inherit;"><i class="fas fa-chevron-left"></i> Sebelumnya</button>
            @else
                <a href="{{ $newObat->previousPageUrl() }}" class="btn btn-premium-outline" style="text-decoration: none; padding: 0.4rem 0.85rem; font-size: 0.78rem; border-radius: var(--radius-sm);"><i class="fas fa-chevron-left"></i> Sebelumnya</a>
            @endif

            @if ($newObat->hasMorePages())
                <a href="{{ $newObat->nextPageUrl() }}" class="btn btn-premium-outline" style="text-decoration: none; padding: 0.4rem 0.85rem; font-size: 0.78rem; border-radius: var(--radius-sm);">Selanjutnya <i class="fas fa-chevron-right"></i></a>
            @else
                <button type="button" class="btn btn-premium-outline" disabled style="opacity: 0.5; cursor: not-allowed; padding: 0.4rem 0.85rem; font-size: 0.78rem; border-radius: var(--radius-sm); font-family: inherit;">Selanjutnya <i class="fas fa-chevron-right"></i></button>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- TABEL 2: PENCATATAN RESTOK BARANG --}}
<div class="card-premium">
    <div class="card-header-premium">
        <div class="card-title-premium">
            <i class="fas fa-dolly" style="color:var(--warning); font-size:1.1rem;"></i> 
            <span>Pencatatan Restok Barang (Disetujui)</span>
        </div>
        <span class="card-badge-premium">
            <i class="fas fa-history" style="margin-right:2px; color:var(--warning);"></i> {{ $totalRestockAdditions }} kali transaksi
        </span>
    </div>
    <div class="table-wrapper">
        <table class="table-premium">
            <thead>
                <tr>
                    <th style="width: 120px;">Kode</th>
                    <th>Nama Obat</th>
                    <th>Kategori</th>
                    <th style="text-align:center; width: 180px;">Perubahan Stok</th>
                    <th>Catatan Penyesuaian</th>
                    <th>Tanggal Disetujui</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stockAdditions as $addition)
                @php $isReduction = $addition->jumlah < 0; @endphp
                <tr>
                    <td><span class="kode-badge-premium">{{ $addition->obat->kode ?? '—' }}</span></td>
                    <td style="font-weight:700; color:var(--gray-800);">{{ $addition->obat->nama_obat ?? 'Obat Dihapus' }}</td>
                    <td>
                        <span class="category-badge-premium">{{ $addition->obat->kategori->nama_kategori ?? 'Umum' }}</span>
                    </td>
                    <td style="text-align:center;">
                        <span class="{{ $isReduction ? 'quantity-badge-minus' : 'quantity-badge-plus' }}">
                            <i class="fas {{ $isReduction ? 'fa-minus' : 'fa-plus' }}" style="font-size:0.7rem;"></i>{{ abs($addition->jumlah) }}
                            <span style="font-weight: 600; font-size:0.7rem; text-transform:lowercase; color: {{ $isReduction ? '#b91c1c' : 'var(--success-dark)' }}; opacity: 1;">{{ $addition->obat->satuan ?? 'pcs' }}</span>
                        </span>
                    </td>
                    <td style="font-size:0.82rem; color:var(--gray-600); max-width:280px; white-space:normal; line-height:1.5;">{{ $addition->catatan ?: '—' }}</td>
                    <td>
                        <div style="font-weight:600; color:var(--gray-700);">{{ $addition->created_at->format('d/m/Y') }}</div>
                        <div style="font-size:0.72rem; color:var(--gray-600); margin-top:2px;"><i class="far fa-clock" style="margin-right:2px;"></i>{{ $addition->created_at->format('H:i') }} WIB</div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state-premium">
                            <div class="empty-state-icon">
                                <i class="fas fa-truck-loading"></i>
                            </div>
                            <div class="empty-state-title">Tidak Ada Transaksi Restok</div>
                            <div class="empty-state-desc">Pencatatan penambahan stok obat yang disetujui tidak ditemukan dalam periode filter ini.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($stockAdditions->hasPages())
    <div class="card-footer no-print" style="padding: 1rem 1.5rem; border-top: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); gap: 1rem; flex-wrap: wrap;">
        <div style="font-size: 0.8rem; color: var(--gray-700); font-weight: 600;">
            Menampilkan {{ $stockAdditions->firstItem() }} - {{ $stockAdditions->lastItem() }} dari {{ $stockAdditions->total() }} restok
        </div>
        <div class="btn-premium-group" style="display: flex; gap: 0.5rem; margin: 0;">
            @if ($stockAdditions->onFirstPage())
                <button type="button" class="btn btn-premium-outline" disabled style="opacity: 0.5; cursor: not-allowed; padding: 0.4rem 0.85rem; font-size: 0.78rem; border-radius: var(--radius-sm); font-family: inherit;"><i class="fas fa-chevron-left"></i> Sebelumnya</button>
            @else
                <a href="{{ $stockAdditions->previousPageUrl() }}" class="btn btn-premium-outline" style="text-decoration: none; padding: 0.4rem 0.85rem; font-size: 0.78rem; border-radius: var(--radius-sm);"><i class="fas fa-chevron-left"></i> Sebelumnya</a>
            @endif

            @if ($stockAdditions->hasMorePages())
                <a href="{{ $stockAdditions->nextPageUrl() }}" class="btn btn-premium-outline" style="text-decoration: none; padding: 0.4rem 0.85rem; font-size: 0.78rem; border-radius: var(--radius-sm);">Selanjutnya <i class="fas fa-chevron-right"></i></a>
            @else
                <button type="button" class="btn btn-premium-outline" disabled style="opacity: 0.5; cursor: not-allowed; padding: 0.4rem 0.85rem; font-size: 0.78rem; border-radius: var(--radius-sm); font-family: inherit;">Selanjutnya <i class="fas fa-chevron-right"></i></button>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection
