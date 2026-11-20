@extends('layouts.admin')
@section('title', 'Catatan Penjualan')
{{-- Shared CSS loaded via public/css/admin/shared.css --}}
@section('content')

<div class="page-header">
    <div>
        <div class="page-title">Catatan <span>Penjualan</span></div>
        <div style="font-size:0.8rem;color:var(--gray-500);margin-top:2px;">Rekam dan pantau seluruh transaksi penjualan obat</div>
    </div>
    <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Input Penjualan Baru
    </a>
</div>

{{-- Summary Bar --}}
<div class="summary-bar">
    <div class="summary-item">
        <div class="summary-icon" style="background: #e0f2fe; color: #0284c7;"><i class="fas fa-receipt"></i></div>
        <div>
            <div class="summary-label">Total Transaksi</div>
            <div class="summary-value">{{ $orders->total() }}</div>
        </div>
    </div>
    <div class="summary-item">
        <div class="summary-icon" style="background: #fef3c7; color: #f59e0b;"><i class="fas fa-clock"></i></div>
        <div>
            <div class="summary-label">Pending</div>
            <div class="summary-value">{{ $orders->where('status','pending')->count() }}</div>
        </div>
    </div>
    <div class="summary-item">
        <div class="summary-icon" style="background: #dcfce7; color: #10b981;"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="summary-label">Selesai</div>
            <div class="summary-value">{{ $orders->where('status','selesai')->count() }}</div>
        </div>
    </div>
</div>



{{-- Search Bar --}}
<div class="filter-card">
    <form action="{{ route('admin.orders.index') }}" method="GET" class="form-inline" style="flex:1;">
        <div style="position:relative;">
            <i class="fas fa-search" style="position:absolute;left:0.65rem;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:0.8rem;pointer-events:none;"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari no faktur atau nama pembeli..." value="{{ request('search') }}" style="padding-left:2rem;min-width:280px;" aria-label="Cari no faktur atau nama pembeli">
        </div>
        <input type="date" name="date" class="form-control" value="{{ request('date') }}" style="width:auto;">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
        @if(request()->anyFilled(['search','date']))
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Reset</a>
        @endif
    </form>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No. Faktur</th>
                    <th>Pembeli / Kasir</th>
                    <th>Pembayaran</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Tanggal</th>
                    <th style="width:60px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                @php $sc=['pending'=>'warning','dikonfirmasi'=>'primary','diproses'=>'primary','dikirim'=>'primary','selesai'=>'success','dibatalkan'=>'danger']; @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.orders.show', $order) }}" class="no-order-link">{{ $order->no_order }}</a>
                        <div style="font-size:0.72rem;color:var(--gray-400);margin-top:1px;">{{ $order->items->count() ?? 0 }} item</div>
                    </td>
                    <td>
                        <div style="font-weight:500;font-size:0.85rem;">{{ $order->user->name ?? 'Penjualan Manual' }}</div>
                        <div style="font-size:0.72rem;color:var(--gray-400);">{{ $order->catatan ?? '' }}</div>
                    </td>
                    <td>
                        @if($order->metode_pembayaran === 'cash' || $order->metode_pembayaran === 'tunai')
                            <span class="badge" style="font-size:0.73rem; background: rgba(72,202,228,0.12); color: var(--accent-1); border-radius:999px; padding:0.15rem 0.55rem; font-weight: 700;">Cash</span>
                        @elseif($order->metode_pembayaran === 'qris')
                            <span class="badge" style="font-size:0.73rem; background: rgba(0, 119, 182, 0.12); color: var(--primary); border-radius:999px; padding:0.15rem 0.55rem; font-weight: 700;">QRIS</span>
                        @else
                            <span class="badge" style="font-size:0.73rem; background: rgba(0, 119, 182, 0.12); color: var(--primary); border-radius:999px; padding:0.15rem 0.55rem; font-weight: 700;">Transfer</span>
                        @endif
                    </td>
                    <td><span class="badge badge-{{ $sc[$order->status]??'secondary' }}">{{ ucfirst($order->status) }}</span></td>
                    <td style="font-weight:700;color:var(--gray-800);">Rp {{ number_format($order->total_harga,0,',','.') }}</td>
                    <td>
                        <div style="font-size:0.82rem;color:var(--gray-700);">{{ $order->created_at->format('d/m/Y') }}</div>
                        <div style="font-size:0.72rem;color:var(--gray-400);">{{ $order->created_at->format('H:i') }}</div>
                    </td>
                    <td>
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn-icon btn-icon-blue" title="Lihat Detail"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty-fancy">
                        <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                        <h4>Belum ada catatan penjualan</h4>
                        <p>Rekam penjualan pertama Anda sekarang</p>
                        <a href="{{ route('admin.orders.create') }}" class="btn btn-primary btn-sm" style="margin-top:0.75rem;"><i class="fas fa-plus"></i> Input Penjualan</a>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
    <div style="padding:1rem 1.25rem;border-top:1px solid var(--gray-200);">
        {{ $orders->withQueryString()->links('pagination::bootstrap-4') }}
    </div>
    @endif
</div>
@endsection
