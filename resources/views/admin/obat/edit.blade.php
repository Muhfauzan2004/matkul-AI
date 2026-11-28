@extends('layouts.admin')
@section('title', 'Edit Obat')
@section('content')
<a href="{{ route('admin.obat.index') }}" class="btn btn-ghost btn-sm mb-2"><i class="fas fa-arrow-left"></i> Kembali</a>
<div class="card">
    <div class="card-header">Edit: {{ $obat->nama_obat }}</div>
    <div class="card-body">
        @include('admin.obat._form', ['obat' => $obat, 'kategoris' => $kategoris, 'isModal' => false])
    </div>
</div>
@endsection
