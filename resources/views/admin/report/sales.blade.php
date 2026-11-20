@extends('layouts.admin')
@section('title', 'Laporan Penjualan')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/report.css') }}">
@endpush
@section('content')

<div class="page-header">
    <div>
        <div class="page-title">Laporan <span>Penjualan</span></div>
        <div style="font-size:0.8rem;color:var(--gray-600);margin-top:2px;">
            Periode: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</strong>
            — <strong>{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</strong>
        </div>
    </div>
    <div class="export-btn-group no-print">
        <button onclick="window.print()" class="btn-export btn-export-print">
            <i class="fas fa-print"></i> Cetak Laporan
        </button>
    </div>
</div>

{{-- KPI Cards --}}
<div class="kpi-report">
    <div class="kpi-r">
        <div class="accent" style="background:var(--success);"></div>
        <div class="kpi-r-label">Total Pendapatan</div>
        <div class="kpi-r-value sm">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</div>
        <div class="kpi-r-icon" style="background:var(--success-light);color:var(--success);"><i class="fas fa-money-bill-wave"></i></div>
    </div>
    <div class="kpi-r">
        <div class="accent" style="background:var(--primary);"></div>
        <div class="kpi-r-label">Total Transaksi Selesai</div>
        <div class="kpi-r-value">{{ $totalTransaksi }}</div>
        <div class="kpi-r-icon" style="background:var(--accent-3);color:var(--primary-dark);"><i class="fas fa-receipt"></i></div>
    </div>
    <div class="kpi-r">
        <div class="accent" style="background:var(--accent-2);"></div>
        <div class="kpi-r-label">Total Obat Terjual</div>
        <div class="kpi-r-value">{{ $totalObatTerjual }} <span style="font-size:1rem;color:var(--gray-600);">pcs</span></div>
        <div class="kpi-r-icon" style="background:var(--accent-4);color:var(--primary-dark);"><i class="fas fa-pills"></i></div>
    </div>
</div>

{{-- Filter --}}
<div class="filter-panel no-print">
    <h3><i class="fas fa-calendar-alt"></i> Filter Periode</h3>
    <form action="{{ route('admin.reports.sales') }}" method="GET" class="form-inline">
        <div>
            <label class="form-label" for="start_date">Dari Tanggal</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDate }}" required>
        </div>
        <div>
            <label class="form-label" for="end_date">Sampai Tanggal</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate }}" required>
        </div>
        <div style="align-self:flex-end;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Terapkan</button>
            <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline"><i class="fas fa-sync"></i> Bulan Ini</a>
        </div>
    </form>
</div>

{{-- CHARTS --}}
@if($totalTransaksi > 0)
<div class="chart-grid no-print">
    {{-- Line Chart: Tren Harian --}}
    <div class="chart-card">
        <div class="chart-card-header">
            <i class="fas fa-chart-line" style="color: var(--primary);"></i>
            Tren Pendapatan Harian
        </div>
        <div class="chart-container">
            <canvas id="salesLineChart"></canvas>
        </div>
    </div>

    {{-- Doughnut: Metode Pembayaran --}}
    <div class="chart-card">
        <div class="chart-card-header">
            <i class="fas fa-chart-pie" style="color: var(--primary);"></i>
            Metode Pembayaran
        </div>
        <div class="chart-container" style="height: 220px; display: flex; align-items: center; justify-content: center;">
            <canvas id="paymentDoughnut"></canvas>
        </div>
    </div>
</div>
@endif

{{-- Detail Table --}}
<div class="card">
    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;justify-content:space-between;">
        <div style="font-weight:700;font-size:0.9rem;color:var(--gray-800);display:flex;align-items:center;gap:0.5rem;">
            <i class="fas fa-list-alt" style="color:var(--primary);"></i> Rincian Transaksi
        </div>
        <span style="font-size:0.78rem;color:var(--gray-500);">{{ $totalTransaksi }} transaksi selesai</span>
    </div>
    <div class="table-wrapper">
        <table class="detail-table">
            <thead>
                <tr>
                    <th>No. Faktur</th>
                    <th>Waktu</th>
                    <th>Kasir</th>
                    <th>Item Terjual</th>
                    <th>Metode</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td style="font-weight:700;font-size:0.84rem;color:var(--primary);">{{ $order->no_order }}</td>
                    <td>
                        <div style="font-size:0.82rem;">{{ $order->created_at->format('d/m/Y') }}</div>
                        <div style="font-size:0.72rem;color:var(--gray-600);">{{ $order->created_at->format('H:i') }} WIB</div>
                    </td>
                    <td style="font-size:0.83rem;color:var(--gray-700);">{{ $order->user->name ?? 'Penjualan Manual' }}</td>
                    <td>
                        <ul class="item-list">
                            @foreach($order->items as $item)
                                <li>{{ $item->obat->nama_obat ?? 'Obat Dihapus' }} &times;{{ $item->jumlah }}</li>
                            @endforeach
                        </ul>
                    </td>
                    <td>
                        @if($order->metode_pembayaran === 'cash' || $order->metode_pembayaran === 'tunai')
                            <span class="badge" style="background: rgba(16,185,129,0.15); color: var(--success); border-radius:999px; padding:0.15rem 0.55rem; font-weight:700;">Cash</span>
                        @elseif($order->metode_pembayaran === 'qris')
                            <span class="badge" style="background: rgba(0,119,182,0.15); color: var(--primary); border-radius:999px; padding:0.15rem 0.55rem; font-weight:700;">QRIS</span>
                        @else
                            <span class="badge" style="background: rgba(0,119,182,0.15); color: var(--primary); border-radius:999px; padding:0.15rem 0.55rem; font-weight:700;">Transfer</span>
                        @endif
                    </td>
                    <td style="font-weight:800;text-align:right;color:var(--gray-800);">Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <div class="empty-fancy">
                        <div class="empty-icon"><i class="fas fa-chart-bar"></i></div>
                        <p>Tidak ada transaksi pada periode ini.</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
            @if($totalTransaksi > 0)
            <tfoot>
                <tr style="background:var(--gray-50);">
                    <td colspan="5" style="font-weight:700;font-size:0.85rem;color:var(--gray-700);text-align:right;">Total Pendapatan Periode Ini</td>
                    <td style="font-weight:800;font-size:1rem;color:var(--success);text-align:right;">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@if($totalTransaksi > 0)
// ===== Data dari PHP =====
@php
    $dailyData = $orders->groupBy(function($o) {
        return $o->created_at->format('d/m');
    })->map(function($grp) {
        return $grp->sum('total_harga');
    });

    $cashCount     = $orders->where('metode_pembayaran', 'cash')->count() + $orders->where('metode_pembayaran', 'tunai')->count();
    $qrisCount     = $orders->where('metode_pembayaran', 'qris')->count();
    $transferCount = $orders->whereNotIn('metode_pembayaran', ['cash','tunai','qris'])->count();
@endphp

const dailyLabels  = @json($dailyData->keys());
const dailyValues  = @json($dailyData->values());

// ===== Line Chart =====
const lineCtx = document.getElementById('salesLineChart').getContext('2d');
new Chart(lineCtx, {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: dailyValues,
            borderColor: 'var(--primary)',
            backgroundColor: 'rgba(0, 119, 182, 0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: 'var(--primary)',
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => 'Rp ' + ctx.raw.toLocaleString('id-ID')
                }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
            y: {
                grid: { color: 'rgba(0,0,0,0.04)' },
                ticks: {
                    font: { size: 10 },
                    callback: v => 'Rp ' + (v >= 1000000 ? (v/1000000).toFixed(1)+'jt' : (v/1000).toFixed(0)+'k')
                }
            }
        }
    }
});

// ===== Doughnut Chart =====
const doughCtx = document.getElementById('paymentDoughnut').getContext('2d');
new Chart(doughCtx, {
    type: 'doughnut',
    data: {
        labels: ['Cash/Tunai', 'QRIS', 'Transfer'],
        datasets: [{
            data: [{{ $cashCount }}, {{ $qrisCount }}, {{ $transferCount }}],
            backgroundColor: ['rgba(16,185,129,0.8)', 'rgba(0,119,182,0.8)', 'rgba(72,202,228,0.8)'],
            borderColor: ['#fff','#fff','#fff'],
            borderWidth: 2,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 11 }, padding: 12, usePointStyle: true }
            },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.label + ': ' + ctx.raw + ' transaksi'
                }
            }
        }
    }
});
@endif
</script>
@endpush

@endsection
