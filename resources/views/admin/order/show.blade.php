@extends('layouts.admin')
@section('title', 'Detail Pesanan #' . $order->no_order)
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/order.css') }}">
@endpush
@section('content')

<div class="page-header no-print">
    <div>
        <div class="page-title">Detail <span>Pesanan</span></div>
        <div class="page-subtitle">{{ $order->no_order }} · {{ $order->created_at->format('d M Y, H:i') }} WIB</div>
    </div>
    <div style="display: flex; gap: 0.6rem; align-items: center;">
        <button onclick="window.print()" class="btn btn-outline btn-sm" style="border-color: var(--primary); color: var(--primary);">
            <i class="fas fa-print"></i> Cetak Struk
        </button>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="order-detail-layout">
    {{-- LEFT: INVOICE --}}
    <div class="invoice-card">
        {{-- Header --}}
        <div class="invoice-header">
            <div class="invoice-brand">
                <div class="invoice-brand-icon"><i class="fas fa-capsules"></i></div>
                <div>
                    <div class="invoice-brand-name">APOTEK SULTHAN ELIDAS</div>
                    <div class="invoice-brand-sub">Sistem Manajemen Apotek Premium</div>
                </div>
            </div>
            <div class="invoice-no">
                <div class="label">No. Faktur</div>
                <div class="value">{{ $order->no_order }}</div>
                <div style="margin-top: 4px;">
                    @php $sc = ['pending'=>'warning','dikonfirmasi'=>'primary','diproses'=>'primary','dikirim'=>'primary','selesai'=>'success','dibatalkan'=>'danger']; @endphp
                    <span class="badge badge-{{ $sc[$order->status] ?? 'secondary' }}" style="font-size: 0.7rem;">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Meta --}}
        <div class="invoice-meta">
            <div class="meta-field">
                <div class="label">Kasir</div>
                <div class="value">{{ $order->user->name ?? 'Penjualan Manual' }}</div>
            </div>
            <div class="meta-field">
                <div class="label">Tanggal & Waktu</div>
                <div class="value">{{ $order->created_at->format('d M Y') }}</div>
                <div style="font-size: 0.75rem; color: var(--gray-400);">{{ $order->created_at->format('H:i') }} WIB</div>
            </div>
            <div class="meta-field">
                <div class="label">Metode Pembayaran</div>
                @if($order->metode_pembayaran === 'cash' || $order->metode_pembayaran === 'tunai')
                    <div class="value">💵 Cash / Tunai</div>
                @elseif($order->metode_pembayaran === 'qris')
                    <div class="value">📱 QRIS</div>
                @else
                    <div class="value">🏦 Transfer Bank</div>
                @endif
            </div>
        </div>

        {{-- Items Table --}}
        <div class="invoice-items">
            <table>
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:right;">Harga Satuan</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--gray-800);">{{ $item->obat->nama_obat ?? 'Obat Dihapus' }}</div>
                            @if($item->obat)
                            <div style="font-size: 0.72rem; color: var(--gray-400);">{{ $item->obat->kategori->nama_kategori ?? '' }} · {{ $item->obat->satuan }}</div>
                            @endif
                        </td>
                        <td style="text-align:center; font-weight: 700; color: var(--gray-700);">{{ $item->jumlah }}</td>
                        <td style="text-align:right; color: var(--gray-600);">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight: 700; color: var(--gray-800);">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Total --}}
        <div class="invoice-total">
            <div class="total-label">Total Pembayaran:</div>
            <div class="total-amount">Rp {{ number_format($order->total_harga, 0, ',', '.') }}</div>
        </div>


    </div>

    {{-- RIGHT: STATUS & ACTIONS --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        {{-- Quick Actions --}}
        <div class="card no-print">
            <div class="section-header">
                <div class="section-title"><i class="fas fa-bolt" style="color: var(--warning);"></i> Aksi Cepat</div>
            </div>
            <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.6rem;">
                <button onclick="window.print()" class="btn btn-primary" style="justify-content: center;">
                    <i class="fas fa-print"></i> Cetak / Print Struk
                </button>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline" style="justify-content: center;">
                    <i class="fas fa-list"></i> Semua Transaksi
                </a>
                <a href="{{ route('admin.orders.create') }}" class="btn btn-outline" style="justify-content: center;">
                    <i class="fas fa-plus"></i> Input Penjualan Baru
                </a>
            </div>
        </div>

        {{-- Order Summary --}}
        <div class="card no-print">
            <div class="section-header">
                <div class="section-title"><i class="fas fa-info-circle" style="color: var(--primary);"></i> Ringkasan</div>
            </div>
            <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.65rem;">
                @php
                    $totalItems = $order->items->sum('jumlah');
                    $uniqueObat = $order->items->count();
                @endphp
                <div style="display: flex; justify-content: space-between; font-size: 0.83rem; padding: 0.4rem 0; border-bottom: 1px dashed var(--gray-200);">
                    <span style="color: var(--gray-500);">Total Item</span>
                    <strong>{{ $totalItems }} pcs</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.83rem; padding: 0.4rem 0; border-bottom: 1px dashed var(--gray-200);">
                    <span style="color: var(--gray-500);">Jenis Obat</span>
                    <strong>{{ $uniqueObat }} jenis</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.83rem; padding: 0.4rem 0; border-bottom: 1px dashed var(--gray-200);">
                    <span style="color: var(--gray-500);">Status</span>
                    <span class="badge badge-{{ $sc[$order->status] ?? 'secondary' }}">{{ ucfirst($order->status) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.83rem; padding: 0.4rem 0;">
                    <span style="color: var(--gray-500);">Dibuat</span>
                    <strong>{{ $order->created_at->diffForHumans() }}</strong>
                </div>
            </div>
            <div style="padding: 0.75rem 1.25rem; border-top: 2px solid var(--gray-200); background: var(--gray-50); display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.83rem; font-weight: 600; color: var(--gray-600);">Total Bayar</span>
                <span style="font-size: 1.2rem; font-weight: 800; color: var(--primary);">Rp {{ number_format($order->total_harga, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>

@endsection
