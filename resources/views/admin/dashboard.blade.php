@extends('layouts.admin')
@section('title', 'Dashboard')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pemilik/dashboard.css') }}">
@endpush

@section('content')

{{-- GREETING BANNER --}}
<div class="dashboard-greeting">
    <div class="greeting-text">
        <h2>Selamat datang, {{ auth()->user()->name }}! 👋</h2>
        <p>Berikut ringkasan aktivitas Apotek hari ini — {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
        <div class="greeting-badges">
            <div class="greeting-badge"><i class="fas fa-circle" style="font-size:0.5rem;color:var(--success);"></i> Sistem Aktif</div>
            <div class="greeting-badge"><i class="fas fa-pills"></i> {{ $obatAktif }} obat aktif</div>
            @if($pendingApproval > 0)
            <div class="greeting-badge" style="background:rgba(0,150,199,0.15);border-color:rgba(0,150,199,0.35);">
                <i class="fas fa-clock"></i> {{ $pendingApproval }} obat menunggu konfirmasi
            </div>
            @endif
            @if($criticalStock > 0)
            <div class="greeting-badge" style="background:rgba(239,68,68,0.18);border-color:rgba(239,68,68,0.32);">
                <i class="fas fa-exclamation-triangle"></i> {{ $criticalStock }} obat stok kritis
            </div>
            @endif
        </div>
    </div>
    <div class="greeting-emblem" aria-hidden="true" title="Apotek Itu Toko Obat">
        <x-pharmacy-logo />
    </div>
</div>

{{-- PERBANDINGAN PENJUALAN BULANAN (MONTHLY SALES COMPARISON) --}}
<div class="comparison-card">
    <div class="comparison-header">
        <div class="comparison-title">
            <i class="fas fa-chart-line" style="color:var(--primary); font-size: 1.1rem;"></i>
            <span>Analisis Perbandingan Penjualan Bulanan</span>
        </div>
        <span style="font-size:0.75rem; font-weight:600; color:var(--gray-600); background:var(--gray-50); padding:0.25rem 0.6rem; border-radius:999px; border:1px solid var(--gray-100);">
            Data Real-time
        </span>
    </div>

    <div class="comparison-grid">
        {{-- Bulan Ini --}}
        <div class="comp-box">
            <div class="comp-box-title">Bulan Ini</div>
            <div class="comp-box-value">Rp {{ number_format($thisMonthRevenue, 0, ',', '.') }}</div>
            <div class="comp-box-period"><i class="far fa-calendar-alt"></i> {{ now()->locale('id')->isoFormat('MMMM Y') }}</div>
        </div>

        {{-- Bulan Lalu --}}
        <div class="comp-box">
            <div class="comp-box-title">Bulan Lalu</div>
            <div class="comp-box-value">Rp {{ number_format($lastMonthRevenue, 0, ',', '.') }}</div>
            <div class="comp-box-period"><i class="far fa-calendar-alt"></i> {{ now()->subMonth()->locale('id')->isoFormat('MMMM Y') }}</div>
        </div>

        {{-- Status Untung / Rugi --}}
        @if($percentageChange >= 0)
        <div class="comp-status-box profit">
            <div class="comp-status-icon">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="comp-status-info">
                <h3>
                    Untung/Tumbuh 
                    <span class="comp-badge">
                        +{{ number_format($percentageChange, 2, ',', '.') }}%
                    </span>
                </h3>
                <p>Pendapatan bulan ini meningkat dibanding bulan lalu. Pertahankan kinerja penjualan obat Anda!</p>
            </div>
        </div>
        @else
        <div class="comp-status-box loss">
            <div class="comp-status-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="comp-status-info">
                <h3>
                    Penurunan/Rugi 
                    <span class="comp-badge">
                        {{ number_format($percentageChange, 2, ',', '.') }}%
                    </span>
                </h3>
                <p>Pendapatan menurun dari bulan lalu. Evaluasi stok obat terlaris atau berikan promosi tambahan.</p>
            </div>
        </div>
        @endif
    </div>

    {{-- Progress Bar Pencapaian --}}
    @php
        $pctAchievement = $lastMonthRevenue > 0 ? min(100, ($thisMonthRevenue / $lastMonthRevenue) * 100) : ($thisMonthRevenue > 0 ? 100 : 0);
    @endphp
    <div class="comp-progress-container">
        <div class="comp-progress-labels">
            <span>Pencapaian penjualan bulan ini terhadap bulan lalu</span>
            <span style="font-weight:700; color: {{ $percentageChange >= 0 ? 'var(--success)' : 'var(--warning)' }};">
                {{ number_format($pctAchievement, 1, ',', '.') }}%
            </span>
        </div>
        <div class="comp-progress-bar-bg">
            <div class="comp-progress-bar-fill" style="width: {{ $pctAchievement }}%; background: {{ $percentageChange >= 0 ? 'linear-gradient(90deg, var(--primary-light) 0%, var(--success) 100%)' : 'linear-gradient(90deg, var(--primary-light) 0%, var(--warning) 100%)' }};"></div>
        </div>
    </div>
</div>

{{-- KPI CARDS --}}
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-accent" style="background:var(--primary);"></div>
        <div style="padding-left:0.5rem;">
            <div class="kpi-label">Total Obat</div>
            <div class="kpi-value">{{ $totalObat }}</div>
            <div class="kpi-trend"><i class="fas fa-check-circle" style="color:var(--success);"></i> {{ $obatAktif }} aktif</div>
        </div>
        <div class="kpi-icon" style="background:var(--accent-3);color:var(--primary-dark);"><i class="fas fa-pills"></i></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-accent" style="background:var(--success);"></div>
        <div style="padding-left:0.5rem;">
            <div class="kpi-label">Pendapatan Hari Ini</div>
            <div class="kpi-value smaller">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</div>
            <div class="kpi-trend"><i class="fas fa-calendar-week" style="color:var(--primary);"></i> Minggu ini: Rp {{ number_format($weeklyRevenue, 0, ',', '.') }}</div>
        </div>
        <div class="kpi-icon" style="background:var(--success-light);color:var(--success);"><i class="fas fa-money-bill-wave"></i></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-accent" style="background:var(--warning);"></div>
        <div style="padding-left:0.5rem;">
            <div class="kpi-label">Total Transaksi</div>
            <div class="kpi-value">{{ $totalOrder }}</div>
            <div class="kpi-trend"><i class="fas fa-clock" style="color:var(--warning-dark);"></i> {{ $pendingOrders }} masih pending</div>
        </div>
        <div class="kpi-icon" style="background:var(--warning-light);color:var(--warning-dark);"><i class="fas fa-shopping-cart"></i></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-accent" style="background:var(--accent-2);"></div>
        <div style="padding-left:0.5rem;">
            <div class="kpi-label">Total Pendapatan</div>
            <div class="kpi-value smaller">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            <div class="kpi-trend"><i class="fas fa-tags" style="color:var(--primary-medium);"></i> {{ $totalKategori }} kategori obat</div>
        </div>
        <div class="kpi-icon" style="background:var(--accent-4);color:var(--primary-dark);"><i class="fas fa-chart-line"></i></div>
    </div>
</div>

{{-- ALERT BANNERS --}}
@if($pendingApproval > 0 || $criticalStock > 0)
<div class="alert-row" style="grid-template-columns: {{ ($pendingApproval > 0 && $criticalStock > 0) ? '1fr 1fr' : '1fr' }}; max-width: {{ ($pendingApproval > 0 && $criticalStock > 0) ? '100%' : '50%' }};">
    @if($pendingApproval > 0)
    <a href="{{ route('admin.obat.index') }}" class="alert-mini alert-mini-warning">
        <div class="alert-mini-icon"><i class="fas fa-hourglass-half"></i></div>
        <div><strong>{{ $pendingApproval }} obat</strong> menunggu konfirmasi Pemilik.<br><span style="font-size:0.75rem;opacity:0.8;">Klik untuk melihat daftar</span></div>
    </a>
    @endif
    @if($criticalStock > 0)
    <a href="{{ route('admin.stock.index') }}" class="alert-mini alert-mini-danger">
        <div class="alert-mini-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div><strong>{{ $criticalStock }} obat</strong> dalam kondisi stok kritis!<br><span style="font-size:0.75rem;opacity:0.8;">Segera lakukan restock</span></div>
    </a>
    @endif
</div>
@endif

{{-- CHART + TABLES --}}
<div class="bottom-grid">
    {{-- LEFT: Recent Orders + Chart --}}
    <div style="display:flex;flex-direction:column;gap:1.25rem;">
        {{-- Mini Bar Chart --}}
        <div class="card">
            <div class="section-header">
                <div class="section-title"><i class="fas fa-chart-bar" style="color:var(--primary);"></i> Penjualan 7 Hari Terakhir</div>
                <a href="{{ route('admin.reports.sales') }}" class="section-action">Lihat Laporan →</a>
            </div>
            <div style="padding: 0.5rem 1.25rem 1.25rem;">
                @php $maxVal = $salesWeekly->max('value') ?: 1; @endphp
                <div class="chart-bar-wrapper">
                    @foreach($salesWeekly as $day)
                    <div class="chart-bar-col">
                        <div class="chart-bar" style="height: {{ max(4, ($day['value'] / $maxVal) * 100) }}%;"
                             title="Rp {{ number_format($day['value'], 0, ',', '.') }}"></div>
                        <div class="chart-bar-label">{{ $day['label'] }}</div>
                    </div>
                    @endforeach
                </div>
                @if($salesWeekly->sum('value') == 0)
                <p style="text-align:center;color:var(--gray-400);font-size:0.8rem;margin-top:0.5rem;">Belum ada data penjualan minggu ini</p>
                @endif
            </div>
        </div>

        {{-- Recent Orders --}}
        <div class="card">
            <div class="section-header">
                <div class="section-title"><i class="fas fa-receipt" style="color:var(--primary);"></i> Transaksi Terbaru</div>
                <a href="{{ route('admin.orders.index') }}" class="section-action">Lihat Semua →</a>
            </div>
            @forelse($recentOrders as $order)
            @php $sc=['pending'=>'warning','dikonfirmasi'=>'primary','diproses'=>'primary','dikirim'=>'primary','selesai'=>'success','dibatalkan'=>'danger']; @endphp
            <div class="order-row">
                <div>
                    <a href="{{ route('admin.orders.show', $order) }}" class="order-no js-order-modal" data-order-no="{{ $order->no_order }}">{{ $order->no_order }}</a>
                    <div class="order-user"><i class="fas fa-user" style="font-size:0.65rem;"></i> {{ $order->user->name ?? 'Penjualan Manual' }}</div>
                </div>
                <div style="text-align:center;">
                    <span class="badge badge-{{ $sc[$order->status] ?? 'secondary' }}">{{ ucfirst($order->status) }}</span>
                </div>
                <div>
                    <div class="order-amount">Rp {{ number_format($order->total_harga, 0, ',', '.') }}</div>
                    <div class="order-date">{{ $order->created_at->diffForHumans() }}</div>
                </div>
            </div>
            @empty
            <div style="padding:2rem;text-align:center;color:var(--gray-400);">
                <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
                Belum ada transaksi
            </div>
            @endforelse
        </div>
    </div>

    {{-- RIGHT: Low Stock --}}
    <div class="card" style="align-self: start;">
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-boxes" style="color:var(--warning);"></i> Stok Perlu Perhatian
            </div>
            <a href="{{ route('admin.stock.index') }}" class="section-action">Restock →</a>
        </div>
        @forelse($lowStockObat as $obat)
        @php
            $pct = min(100, ($obat->stok / 20) * 100);
            $color = $obat->stok < 5 ? 'var(--danger)' : ($obat->stok < 10 ? 'var(--warning)' : 'var(--primary)');
        @endphp
        <div class="stock-row">
            <div>
                <div class="stock-name">{{ $obat->nama_obat }}</div>
                <div style="font-size:0.72rem;color:var(--gray-600);margin-top:2px;">{{ $obat->satuan }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:0.6rem;">
                <div class="stock-bar-bg">
                    <div class="stock-bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                </div>
                <span style="font-size:0.8rem;font-weight:700;color:{{ $color }};min-width:28px;text-align:right;">{{ $obat->stok }}</span>
            </div>
        </div>
        @empty
        <div style="padding:2rem;text-align:center;color:var(--gray-400);">
            <i class="fas fa-check-circle" style="font-size:2rem;display:block;margin-bottom:0.5rem;color:var(--success);"></i>
            Semua stok dalam kondisi aman
        </div>
        @endforelse

        {{-- Quick Actions --}}
        <div style="padding:1rem 1.25rem;border-top:1px solid var(--gray-200);">
            <div style="font-size:0.75rem;font-weight:700;color:var(--gray-600);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.75rem;">Aksi Cepat</div>
            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                <a href="{{ route('admin.obat.create') }}" class="btn btn-primary btn-sm" style="justify-content:center;">
                    <i class="fas fa-plus"></i> Tambah Obat Baru
                </a>
                <a href="{{ route('admin.orders.create') }}" class="btn btn-outline btn-sm" style="justify-content:center;">
                    <i class="fas fa-shopping-cart"></i> Input Penjualan
                </a>
                <a href="{{ route('admin.stock.index') }}" class="btn btn-outline btn-sm" style="justify-content:center;">
                    <i class="fas fa-boxes"></i> Tambah Stok
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@include('components.order-detail-modal')
