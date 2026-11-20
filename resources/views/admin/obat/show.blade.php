@extends('layouts.admin')
@section('title', 'Detail Obat')
@section('content')
<a href="{{ route('admin.obat.index') }}" class="btn btn-ghost btn-sm mb-2"><i class="fas fa-arrow-left"></i> Kembali</a>
<div class="card">
    <div class="card-header">{{ $obat->nama_obat }} <span class="badge badge-{{ $obat->is_active ? 'success' : 'secondary' }}">{{ $obat->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
    <div class="card-body">
        <div class="grid grid-2">
            <div><p style="color:var(--gray-400);font-size:0.75rem;">KODE</p><p style="font-weight:600;">{{ $obat->kode }}</p></div>
            <div><p style="color:var(--gray-400);font-size:0.75rem;">KATEGORI</p><p><span class="badge badge-primary">{{ $obat->kategori->nama_kategori ?? '-' }}</span></p></div>
            <div><p style="color:var(--gray-400);font-size:0.75rem;">STOK</p><p style="font-weight:600;">{{ $obat->stok }} {{ $obat->satuan }}</p></div>
            <div><p style="color:var(--gray-400);font-size:0.75rem;">SATUAN</p><p>{{ $obat->satuan ?? '-' }}</p></div>
            <div><p style="color:var(--gray-400);font-size:0.75rem;">HARGA UMUM</p><p style="font-weight:700;color:var(--primary);">Rp {{ number_format($obat->harga_umum,0,',','.') }}</p></div>
            <div><p style="color:var(--gray-400);font-size:0.75rem;">HARGA BARANG</p><p>Rp {{ number_format($obat->harga_barang,0,',','.') }}</p></div>
            <div><p style="color:var(--gray-400);font-size:0.75rem;">HARGA BPJS</p><p>Rp {{ number_format($obat->harga_jual_bpjs,0,',','.') }}</p></div>
        </div>
        @if($obat->deskripsi)<div class="mt-2"><p style="color:var(--gray-400);font-size:0.75rem;">DESKRIPSI</p><p>{{ $obat->deskripsi }}</p></div>@endif
    </div>
</div>
@endsection
