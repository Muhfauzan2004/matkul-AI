@extends('layouts.admin')
@section('title', 'Edit Obat')
@section('content')
<a href="{{ route('admin.obat.index') }}" class="btn btn-ghost btn-sm mb-2"><i class="fas fa-arrow-left"></i> Kembali</a>
<div class="card">
    <div class="card-header">Edit: {{ $obat->nama_obat }}</div>
    <div class="card-body">
        <form action="{{ route('admin.obat.update', $obat) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="grid grid-2">
                <div class="form-group"><label class="form-label">Kode</label><input type="text" class="form-control" readonly value="{{ $obat->kode }}" style="background:var(--gray-100);cursor:not-allowed;"></div>
                <div class="form-group"><label class="form-label">Nama Obat *</label><input type="text" name="nama_obat" class="form-control" value="{{ old('nama_obat', $obat->nama_obat) }}" required>@error('nama_obat')<div class="form-error">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label class="form-label">Kategori *</label><select name="kategori_id" class="form-control" required><option value="">Pilih</option>@foreach($kategoris as $kat)<option value="{{ $kat->id }}" {{ old('kategori_id',$obat->kategori_id)==$kat->id?'selected':'' }}>{{ $kat->nama_kategori }}</option>@endforeach</select></div>
                <div class="form-group"><label class="form-label">Satuan</label><input type="text" name="satuan" class="form-control" value="{{ old('satuan', $obat->satuan) }}"></div>
                <div class="form-group"><label class="form-label">Stok *</label><input type="number" name="stok" class="form-control" value="{{ old('stok', $obat->stok) }}" required min="0"></div>
                <div class="form-group"><label class="form-label">Harga Umum *</label><input type="number" name="harga_umum" class="form-control" value="{{ old('harga_umum', $obat->harga_umum) }}" required></div>
                <div class="form-group"><label class="form-label">Harga Barang</label><input type="number" name="harga_barang" class="form-control" value="{{ old('harga_barang', $obat->harga_barang) }}" step="0.01"></div>
                <div class="form-group"><label class="form-label">Harga BPJS</label><input type="number" name="harga_jual_bpjs" class="form-control" value="{{ old('harga_jual_bpjs', $obat->harga_jual_bpjs) }}"></div>
            </div>
            <div class="form-group"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi', $obat->deskripsi) }}</textarea></div>
            <div class="form-group"><label class="form-label">Gambar</label>@if($obat->gambar)<div class="mb-1"><img src="{{ asset('storage/'.$obat->gambar) }}" style="max-height:100px;border-radius:8px;"></div>@endif<input type="file" name="gambar" class="form-control" accept="image/*"></div>
            <div class="form-group"><label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;"><input type="checkbox" name="is_active" value="1" {{ old('is_active',$obat->is_active)?'checked':'' }}> Aktif</label></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
        </form>
    </div>
</div>
@endsection
