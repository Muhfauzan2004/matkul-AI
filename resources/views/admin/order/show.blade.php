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

@include('admin.order._detail_content', ['order' => $order, 'isModal' => false])

@endsection
