@extends('layouts.pemilik')
@section('title', 'Laporan Pendapatan')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pemilik/report-income.css') }}">
@endpush

@section('content')
<div class="report-header">
    <div>
        <div class="report-title">Laporan <span>Pendapatan</span></div>
        <div style="font-size: 0.85rem; color: var(--gray-600); margin-top: 2px;">
            Periode Laporan: <strong>{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}</strong>
        </div>
    </div>
</div>

{{-- KPI GRID CARDS --}}
<div class="kpi-grid">
    <div class="kpi-card revenue">
        <div class="kpi-label">Total Pendapatan</div>
        <div class="kpi-value currency">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
        <div class="kpi-icon"><i class="fas fa-wallet"></i></div>
    </div>
    <div class="kpi-card transactions">
        <div class="kpi-label">Transaksi Selesai</div>
        <div class="kpi-value">{{ $totalTransactions }}</div>
        <div class="kpi-icon"><i class="fas fa-receipt"></i></div>
    </div>
    <div class="kpi-card average">
        <div class="kpi-label">Rata-rata Transaksi</div>
        <div class="kpi-value currency">Rp {{ number_format($averageTransaction, 0, ',', '.') }}</div>
        <div class="kpi-icon"><i class="fas fa-hand-holding-usd"></i></div>
    </div>
    <div class="kpi-card items">
        <div class="kpi-label">Total Obat Terjual</div>
        <div class="kpi-value">{{ $totalItemsSold }} <span style="font-size:0.85rem;color:var(--gray-600);font-weight:500;">pcs</span></div>
        <div class="kpi-icon"><i class="fas fa-pills"></i></div>
    </div>
</div>

{{-- DATE RANGE FILTER --}}
<div class="filter-card">
    <div class="filter-card-header">
        <i class="fas fa-calendar-alt" style="color: var(--primary);"></i>
        <h3>Filter Rentang Tanggal</h3>
    </div>
    <form action="{{ route('pemilik.reports.income') }}" method="GET" class="filter-form">
        <div>
            <label class="form-label" for="start_date">Tanggal Mulai</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDate }}" required>
        </div>
        <div>
            <label class="form-label" for="end_date">Tanggal Selesai</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate }}" required>
        </div>
        <div class="actions-div">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter Data
            </button>
            <a href="{{ route('pemilik.reports.income') }}" class="btn btn-outline">
                <i class="fas fa-redo"></i> Reset Bulan ini
            </a>
        </div>
    </form>
</div>

{{-- VISUALIZATION CHARTS GRID --}}
<div class="chart-grid">
    {{-- LINE CHART CARD --}}
    <div class="chart-card">
        <div class="chart-card-header">
            <i class="fas fa-chart-line" style="color: var(--primary);"></i>
            <span>Tren Perkembangan Pendapatan Harian</span>
        </div>
        <div class="chart-container">
            <canvas id="revenueLineChart"></canvas>
        </div>
    </div>

    {{-- DOUGHNUT CHART CARD --}}
    <div class="chart-card">
        <div class="chart-card-header">
            <i class="fas fa-credit-card" style="color: var(--accent-1);"></i>
            <span>Metode Pembayaran</span>
        </div>
        <div class="chart-container" style="display: flex; align-items: center; justify-content: center; min-height: 250px;">
            <canvas id="paymentDoughnutChart" style="max-height: 240px; max-width: 240px;"></canvas>
        </div>
    </div>
</div>

{{-- RECENT TRANSACTION DETAILS --}}
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header" style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between;">
        <div style="font-weight: 700; font-size: 0.9rem; color: var(--dark); display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-table" style="color: var(--primary);"></i> Rincian Transaksi Transaksi
        </div>
        <span class="badge badge-primary" style="font-size: 0.72rem;">{{ $totalTransactions }} Transaksi</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No. Faktur</th>
                    <th>Waktu & Tanggal</th>
                    <th>Nama Kasir</th>
                    <th>Item Obat Terjual</th>
                    <th>Metode</th>
                    <th style="text-align: right;">Total Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td style="font-weight: 700; color: var(--primary-dark); font-size: 0.85rem;">
                        {{ $order->no_order }}
                    </td>
                    <td>
                        <div style="font-weight: 500; font-size: 0.83rem;">{{ $order->created_at->format('d/m/Y') }}</div>
                        <div style="font-size: 0.72rem; color: var(--gray-600);">{{ $order->created_at->format('H:i') }} WIB</div>
                    </td>
                    <td style="font-size: 0.83rem; color: var(--gray-700); font-weight: 500;">
                        {{ $order->user->name ?? 'Kasir Dihapus' }}
                    </td>
                    <td>
                        <ul class="item-badge-list">
                            @foreach($order->items as $item)
                            <li>
                                <strong>{{ $item->obat->nama_obat ?? 'Obat Dihapus' }}</strong> 
                                <span style="color: var(--gray-600); margin-left: 2px;">&times; {{ $item->jumlah }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </td>
                    <td>
                        @if($order->metode_pembayaran === 'cash' || $order->metode_pembayaran === 'tunai')
                            <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: var(--success); font-weight: 700;">Cash</span>
                        @elseif($order->metode_pembayaran === 'qris')
                            <span class="badge" style="background: rgba(0, 119, 182, 0.15); color: var(--primary); font-weight: 700;">QRIS</span>
                        @else
                            <span class="badge" style="background: rgba(0, 119, 182, 0.15); color: var(--primary); font-weight: 700;">Transfer</span>
                        @endif
                    </td>
                    <td style="font-weight: 800; text-align: right; color: var(--gray-800); font-size: 0.95rem;">
                        Rp {{ number_format($order->total_harga, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-chart-pie"></i></div>
                            <p style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.25rem;">Tidak Ada Data Transaksi</p>
                            <p style="font-size: 0.8rem; color: var(--gray-600);">Tidak ditemukan transaksi selesai untuk rentang tanggal tersebut.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($totalTransactions > 0)
            <tfoot>
                <tr style="background: var(--gray-50); font-weight: 800;">
                    <td colspan="5" style="text-align: right; font-size: 0.88rem; color: var(--gray-700); padding: 1.25rem;">Total Keseluruhan Pendapatan:</td>
                    <td style="text-align: right; font-size: 1.05rem; color: var(--success); padding: 1.25rem;">
                        Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection

@push('scripts')
{{-- Include Premium Chart.js Engine --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ------------------------------------------------------------------------
    // 1. REVENUE TIMELINE LINE CHART
    // ------------------------------------------------------------------------
    const revenueCtx = document.getElementById('revenueLineChart').getContext('2d');
    
    // Create beautiful glowing linear gradient under the curve
    const rootStyles = getComputedStyle(document.documentElement);
    const cssVar = (name) => rootStyles.getPropertyValue(name).trim();
    function hexToRgba(hex, alpha) {
        if (!hex) return hex;
        hex = hex.replace('#','');
        if (hex.length === 3) hex = hex.split('').map(h => h + h).join('');
        const bigint = parseInt(hex, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    const blueGradient = revenueCtx.createLinearGradient(0, 0, 0, 300);
    blueGradient.addColorStop(0, hexToRgba(cssVar('--primary') || '#0077b6', 0.45));
    blueGradient.addColorStop(1, hexToRgba(cssVar('--primary') || '#0077b6', 0.02));

    // Inject dynamic data passed from controller
    const labelsData = @json($chartLabels);
    const revenueData = @json($chartData);

    const revenueLineChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: labelsData,
            datasets: [{
                label: 'Pendapatan Harian',
                data: revenueData,
                borderColor: cssVar('--primary') || '#0077b6',
                borderWidth: 3,
                backgroundColor: blueGradient,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: cssVar('--white') || '#ffffff',
                pointBorderColor: cssVar('--primary') || '#0077b6',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: cssVar('--dark') || '#03045e',
                pointHoverBorderColor: cssVar('--white') || '#ffffff',
                pointHoverBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // We use custom title card
                },
                tooltip: {
                    backgroundColor: cssVar('--dark') || '#03045e',
                    titleColor: cssVar('--white') || '#ffffff',
                    bodyColor: cssVar('--white') || '#ffffff',
                    titleFont: {
                        family: 'Outfit',
                        weight: '700',
                        size: 13
                    },
                    bodyFont: {
                        family: 'Outfit',
                        size: 13
                    },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed.y;
                            return 'Pendapatan: Rp ' + new Intl.NumberFormat('id-ID').format(value);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: cssVar('--gray-500') || '#64748b',
                        font: {
                            family: 'Outfit',
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: cssVar('--gray-200') || '#e2e8f0',
                        drawBorder: false
                    },
                    ticks: {
                        color: cssVar('--gray-500') || '#64748b',
                        font: {
                            family: 'Outfit',
                            size: 11
                        },
                        callback: function(value) {
                            if (value >= 1000000) {
                                return 'Rp ' + (value / 1000000) + ' Jt';
                            } else if (value >= 1000) {
                                return 'Rp ' + (value / 1000) + ' K';
                            }
                            return 'Rp ' + value;
                        }
                    }
                }
            }
        }
    });

    // ------------------------------------------------------------------------
    // 2. PAYMENT METHODS BREAKDOWN DOUGHNUT CHART
    // ------------------------------------------------------------------------
    const paymentCtx = document.getElementById('paymentDoughnutChart').getContext('2d');
    const paymentData = @json($paymentBreakdown);

    // If both payment counts are 0, display placeholders or empty chart values
    const hasData = (paymentData.cash > 0 || paymentData.transfer > 0);
    const chartLabels = ['Cash', 'Transfer/QRIS'];
    const chartValues = hasData ? [paymentData.cash, paymentData.transfer] : [1, 1];
    const chartColors = hasData ? [cssVar('--success') || '#10b981', cssVar('--primary') || '#0077b6'] : [cssVar('--gray-200') || '#cbd5e1', cssVar('--gray-200') || '#cbd5e1'];

    const paymentDoughnutChart = new Chart(paymentCtx, {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartValues,
                backgroundColor: chartColors,
                borderWidth: 2,
                borderColor: cssVar('--white') || '#ffffff',
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: cssVar('--gray-600') || '#64748b',
                        padding: 15,
                        font: {
                            family: 'Outfit',
                            weight: '500',
                            size: 12
                        }
                    }
                },
                tooltip: {
                    enabled: hasData, // Disable tooltips if no real data exists
                    backgroundColor: cssVar('--dark') || '#03045e',
                    titleColor: cssVar('--white') || '#ffffff',
                    bodyColor: cssVar('--white') || '#ffffff',
                    titleFont: {
                        family: 'Outfit',
                        weight: '700'
                    },
                    bodyFont: {
                        family: 'Outfit'
                    },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value / total) * 100);
                            return ' ' + context.label + ': ' + value + ' Transaksi (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '65%' // Sleek thin rings
        }
    });
});
</script>
@endpush
