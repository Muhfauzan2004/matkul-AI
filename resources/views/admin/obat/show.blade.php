@extends('layouts.admin')
@section('title', 'Detail Obat')
@section('content')
<a href="{{ route('admin.obat.index') }}" class="btn btn-ghost btn-sm mb-2"><i class="fas fa-arrow-left"></i> Kembali</a>
@include('admin.obat._detail', ['obat' => $obat])
@endsection
