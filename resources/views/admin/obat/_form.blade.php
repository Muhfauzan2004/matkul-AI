@php
    $isEdit = isset($obat) && $obat->exists;
    $isModalView = $isModal ?? false;
@endphp

<form action="{{ $isEdit ? route('admin.obat.update', $obat) : route('admin.obat.store') }}" method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="grid grid-2">
        <div class="form-group">
            <label class="form-label">Kode</label>
            <input type="text" class="form-control" readonly
                @if($isEdit) value="{{ $obat->kode }}" @else placeholder="[Otomatis]" @endif
                style="background:var(--gray-100);cursor:not-allowed;">
        </div>
        <div class="form-group">
            <label class="form-label">Nama Obat *</label>
            <input type="text" name="nama_obat" class="form-control" value="{{ old('nama_obat', $isEdit ? $obat->nama_obat : '') }}" required>
            @error('nama_obat')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="form-label">Kategori *</label>
            <select name="kategori_id" class="form-control" required>
                <option value="">Pilih Kategori</option>
                @foreach($kategoris as $kat)
                    <option value="{{ $kat->id }}" {{ old('kategori_id', $isEdit ? $obat->kategori_id : '') == $kat->id ? 'selected' : '' }}>{{ $kat->nama_kategori }}</option>
                @endforeach
            </select>
            @error('kategori_id')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="form-label">Satuan</label>
            <input type="text" name="satuan" class="form-control" value="{{ old('satuan', $isEdit ? $obat->satuan : '') }}" placeholder="Tablet, Kapsul, Botol...">
            @error('satuan')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="form-label">Stok *</label>
            <input type="number" name="stok" class="form-control" value="{{ old('stok', $isEdit ? $obat->stok : 0) }}" required min="0">
            @error('stok')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="form-label">Harga Umum *</label>
            <input type="number" name="harga_umum" class="form-control" value="{{ old('harga_umum', $isEdit ? $obat->harga_umum : 0) }}" required min="0">
            @error('harga_umum')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="form-label">Harga Barang</label>
            <input type="number" name="harga_barang" class="form-control" value="{{ old('harga_barang', $isEdit ? $obat->harga_barang : 0) }}" step="0.01" min="0" required>
            @error('harga_barang')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="form-label">Harga Jual BPJS</label>
            <input type="number" name="harga_jual_bpjs" class="form-control" value="{{ old('harga_jual_bpjs', $isEdit ? $obat->harga_jual_bpjs : 0) }}" min="0" required>
            @error('harga_jual_bpjs')<div class="form-error">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Deskripsi</label>
        <textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi', $isEdit ? $obat->deskripsi : '') }}</textarea>
    </div>

    <div class="form-group">
        <label style="display:inline-flex;align-items:center;gap:0.5rem;cursor:pointer;">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $isEdit ? $obat->is_active : 1) ? 'checked' : '' }}> Aktif
        </label>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:0.6rem;padding-top:0.5rem;border-top:1px solid var(--gray-200);">
        @if($isModalView)
            <button type="button" class="btn btn-outline" onclick="closeObatCrudModal()">Batal</button>
        @endif
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> {{ $isEdit ? 'Update' : 'Simpan' }}
        </button>
    </div>
</form>
