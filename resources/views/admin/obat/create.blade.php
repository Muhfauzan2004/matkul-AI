@extends('layouts.admin')
@section('title', 'Tambah Obat')
@section('content')
<a href="{{ route('admin.obat.index') }}" class="btn btn-ghost btn-sm mb-2"><i class="fas fa-arrow-left"></i> Kembali</a>
<div class="card">
    <div class="card-header">Form Tambah Obat</div>
    <div class="card-body">
        <form action="{{ route('admin.obat.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-2">
                <div class="form-group"><label class="form-label">Kode</label><input type="text" class="form-control" readonly placeholder="[Otomatis]" style="background:var(--gray-100);cursor:not-allowed;"></div>
                <div class="form-group"><label class="form-label">Nama Obat *</label><input type="text" name="nama_obat" class="form-control" value="{{ old('nama_obat') }}" required>@error('nama_obat')<div class="form-error">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label class="form-label">Kategori *</label><select name="kategori_id" class="form-control" required><option value="">Pilih Kategori</option>@foreach($kategoris as $kat)<option value="{{ $kat->id }}" {{ old('kategori_id')==$kat->id?'selected':'' }}>{{ $kat->nama_kategori }}</option>@endforeach</select>@error('kategori_id')<div class="form-error">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label class="form-label">Satuan</label><input type="text" name="satuan" class="form-control" value="{{ old('satuan') }}" placeholder="Tablet, Kapsul, Botol...">@error('satuan')<div class="form-error">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label class="form-label">Stok *</label><input type="number" name="stok" class="form-control" value="{{ old('stok', 0) }}" required min="0">@error('stok')<div class="form-error">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label class="form-label">Harga Umum *</label><input type="number" name="harga_umum" class="form-control" value="{{ old('harga_umum', 0) }}" required min="0">@error('harga_umum')<div class="form-error">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label class="form-label">Harga Barang</label><input type="number" name="harga_barang" class="form-control" value="{{ old('harga_barang', 0) }}" step="0.01">@error('harga_barang')<div class="form-error">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label class="form-label">Harga Jual BPJS</label><input type="number" name="harga_jual_bpjs" class="form-control" value="{{ old('harga_jual_bpjs', 0) }}">@error('harga_jual_bpjs')<div class="form-error">{{ $message }}</div>@enderror</div>
            </div>
            <div class="form-group"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi') }}</textarea></div>
            <div class="form-group"><label class="form-label">Gambar</label><input type="file" name="gambar" class="form-control" accept="image/*">@error('gambar')<div class="form-error">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;"><input type="checkbox" name="is_active" value="1" {{ old('is_active',1)?'checked':'' }}> Aktif</label></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </form>
    </div>
</div>
@endsection
