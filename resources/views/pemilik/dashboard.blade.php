@extends('layouts.pemilik')
@section('title', 'Dashboard Pemilik')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pemilik/dashboard.css') }}">
@endpush

@section('content')

{{-- GREETING BANNER --}}
<div class="dashboard-greeting">
    <div class="greeting-text">
        <h2>Selamat Datang Kembali, {{ auth()->user()->name }}! 👑</h2>
        <p>Kelola persetujuan stok apotek dan pantau data admin Anda secara real-time.</p>
        <div class="greeting-badges">
            <div class="greeting-badge"><i class="fas fa-circle" style="font-size:0.5rem;color:var(--success);"></i> Sistem Aktif</div>
            <div class="greeting-badge"><i class="fas fa-pills"></i> {{ $totalObatAktif }} obat aktif</div>
            @if($obatPending > 0)
            <div class="greeting-badge" style="background:rgba(0,150,199,0.15);border-color:rgba(0,150,199,0.35);">
                <i class="fas fa-hourglass-half"></i> {{ $obatPending }} obat pending
            </div>
            @endif
            @if($stockPending > 0)
            <div class="greeting-badge" style="background:rgba(239,68,68,0.18);border-color:rgba(239,68,68,0.32);">
                <i class="fas fa-boxes"></i> {{ $stockPending }} stok pending
            </div>
            @endif
        </div>
    </div>
</div>

{{-- PENDING ACTION BANNER --}}
@if($obatPending > 0 || $stockPending > 0)
<div class="pending-banner">
    <div class="pending-pulse"></div>
    <div style="flex:1;">
        <div style="font-size:0.95rem;font-weight:800;color:#d97706;">Persetujuan Tertunda Diperlukan</div>
        <div style="font-size:0.8rem;color:#b45309;margin-top:3px;font-weight:600;">
            @if($obatPending > 0)Terdapat <strong>{{ $obatPending }} obat baru</strong> menunggu konfirmasi.@endif
            @if($obatPending > 0 && $stockPending > 0) dan @endif
            @if($stockPending > 0)<strong>{{ $stockPending }} penambahan stok</strong> membutuhkan persetujuan.@endif
        </div>
    </div>
    <div style="display:flex;gap:0.75rem;flex-shrink:0;align-items:center;flex-wrap:wrap;">
        @if($obatPending > 0)
        <div style="display:flex;gap:0.35rem;align-items:center;">
            <a href="{{ route('pemilik.obat.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline" style="color:#92400e;border-color:#b45309;background:transparent;box-shadow:none;padding:0.4rem 0.8rem;font-size:0.78rem;">
                <i class="fas fa-pills"></i> Tinjau Obat
            </a>
            <form action="{{ route('pemilik.obat.approveAll') }}" method="POST" style="margin:0;display:inline-block;">
                @csrf @method('PUT')
                <button type="submit" class="btn btn-sm btn-primary" style="background:#92400e;border-color:#92400e;color:white;box-shadow:none;padding:0.4rem 0.8rem;font-size:0.78rem;font-weight:700;" onclick="return confirm('Setujui semua {{ $obatPending }} usulan obat baru secara instan?')">
                    <i class="fas fa-check-circle"></i> Setujui Semua
                </button>
            </form>
        </div>
        @endif
        @if($stockPending > 0)
        <div style="display:flex;gap:0.35rem;align-items:center;">
            <a href="{{ route('pemilik.stock.index') }}" class="btn btn-sm btn-outline" style="color:#92400e;border-color:#b45309;background:transparent;box-shadow:none;padding:0.4rem 0.8rem;font-size:0.78rem;">
                <i class="fas fa-boxes"></i> Tinjau Stok
            </a>
            <form action="{{ route('pemilik.stock.approveAll') }}" method="POST" style="margin:0;display:inline-block;">
                @csrf @method('PUT')
                <button type="submit" class="btn btn-sm btn-primary" style="background:#92400e;border-color:#92400e;color:white;box-shadow:none;padding:0.4rem 0.8rem;font-size:0.78rem;font-weight:700;" onclick="return confirm('Setujui semua {{ $stockPending }} pengajuan stok masuk secara instan?')">
                    <i class="fas fa-check-circle"></i> Setujui Semua
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endif

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
    @endphpS
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
            <div class="kpi-trend"><i class="fas fa-check-circle" style="color:var(--success);"></i> {{ $totalObatAktif }} aktif</div>
        </div>
        <div class="kpi-icon" style="background:var(--accent-3);color:var(--primary-dark);"><i class="fas fa-pills"></i></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-accent" style="background:var(--warning);"></div>
        <div style="padding-left:0.5rem;">
            <div class="kpi-label">Obat Pending</div>
            <div class="kpi-value">{{ $obatPending }}</div>
            <div class="kpi-trend">
                @if($obatPending > 0)
                <i class="fas fa-exclamation-circle" style="color:var(--warning-dark);"></i> Butuh tinjauan
                @else
                <i class="fas fa-check" style="color:var(--success);"></i> Selesai
                @endif
            </div>
        </div>
        <div class="kpi-icon" style="background:var(--warning-light);color:var(--warning-dark);"><i class="fas fa-hourglass-start"></i></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-accent" style="background:var(--accent-2);"></div>
        <div style="padding-left:0.5rem;">
            <div class="kpi-label">Stok Pending</div>
            <div class="kpi-value">{{ $stockPending }}</div>
            <div class="kpi-trend">
                @if($stockPending > 0)
                <i class="fas fa-exclamation-circle" style="color:var(--primary-dark);"></i> Butuh tinjauan
                @else
                <i class="fas fa-check" style="color:var(--success);"></i> Selesai
                @endif
            </div>
        </div>
        <div class="kpi-icon" style="background:var(--accent-4);color:var(--primary-dark);"><i class="fas fa-boxes"></i></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-accent" style="background:var(--success);"></div>
        <div style="padding-left:0.5rem;">
            <div class="kpi-label">Kelola Admin</div>
            <div class="kpi-value">{{ $totalAdmin }}</div>
            <div class="kpi-trend"><i class="fas fa-shield-alt" style="color:var(--success);"></i> Terdaftar</div>
        </div>
        <div class="kpi-icon" style="background:var(--success-light);color:var(--success);"><i class="fas fa-user-shield"></i></div>
    </div>
</div>

{{-- ALERT BANNERS --}}
@if(($obatPending > 0 || $stockPending > 0) || $criticalStock > 0)
<div class="alert-row" style="grid-template-columns: {{ (($obatPending > 0 || $stockPending > 0) && $criticalStock > 0) ? '1fr 1fr' : '1fr' }}; max-width: 100%;">
    @if($obatPending > 0 || $stockPending > 0)
    <a href="{{ route('pemilik.obat.index', ['status' => 'pending']) }}" class="alert-mini alert-mini-warning">
        <div class="alert-mini-icon"><i class="fas fa-hourglass-half"></i></div>
        <div>
            <strong>Persetujuan Tertunda:</strong>
            @if($obatPending > 0) {{ $obatPending }} obat baru @endif
            @if($obatPending > 0 && $stockPending > 0) &amp; @endif
            @if($stockPending > 0) {{ $stockPending }} stok @endif
            menunggu keputusan.<br>
            <span style="font-size:0.75rem;opacity:0.8;">Klik untuk meninjau usulan</span>
        </div>
    </a>
    @endif
    @if($criticalStock > 0)
    <a href="{{ route('pemilik.stock.index') }}" class="alert-mini alert-mini-danger">
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
                <a href="{{ route('pemilik.reports.income') }}" class="section-action">Lihat Laporan →</a>
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
                <a href="{{ route('pemilik.reports.income') }}" class="section-action">Lihat Semua →</a>
            </div>
            @forelse($recentOrders as $order)
            @php $sc=['pending'=>'warning','dikonfirmasi'=>'primary','diproses'=>'primary','dikirim'=>'primary','selesai'=>'success','dibatalkan'=>'danger']; @endphp
            <div class="order-row">
                <div>
                    <span class="order-no" style="cursor:default;">{{ $order->no_order }}</span>
                    <div class="order-user"><i class="fas fa-user" style="font-size:0.65rem;"></i> {{ $order->user->name ?? 'Kasir Dihapus' }}</div>
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
            <a href="{{ route('pemilik.stock.index') }}" class="section-action">Restock →</a>
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
                <a href="{{ route('pemilik.obat.index', ['status' => 'pending']) }}" class="btn btn-primary btn-sm" style="justify-content:center;">
                    <i class="fas fa-pills"></i> Tinjau Obat Baru
                </a>
                <a href="{{ route('pemilik.stock.index') }}" class="btn btn-outline btn-sm" style="justify-content:center;">
                    <i class="fas fa-boxes"></i> Tinjau Stok Masuk
                </a>
                <a href="{{ route('pemilik.reports.income') }}" class="btn btn-outline btn-sm" style="justify-content:center;">
                    <i class="fas fa-chart-line"></i> Laporan Pendapatan
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
