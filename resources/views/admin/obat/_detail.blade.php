<div class="card" style="box-shadow:none;margin-bottom:0;">
    <div class="card-header">
        <span>{{ $obat->nama_obat }}</span>
        <span class="badge badge-{{ $obat->is_active ? 'success' : 'secondary' }}">{{ $obat->is_active ? 'Aktif' : 'Nonaktif' }}</span>
    </div>
    <div class="card-body">
        <div class="grid grid-2">
            <div><p style="color:var(--gray-400);font-size:0.75rem;margin-bottom:2px;">KODE</p><p style="font-weight:600;">{{ $obat->kode }}</p></div>
            <div><p style="color:var(--gray-400);font-size:0.75rem;margin-bottom:2px;">KATEGORI</p><p><span class="badge badge-primary">{{ $obat->kategori->nama_kategori ?? '-' }}</span></p></div>
            <div><p style="color:var(--gray-400);font-size:0.75rem;margin-bottom:2px;">STOK</p><p style="font-weight:600;">{{ $obat->stok }} {{ $obat->satuan }}</p></div>
            <div><p style="color:var(--gray-400);font-size:0.75rem;margin-bottom:2px;">SATUAN</p><p>{{ $obat->satuan ?? '-' }}</p></div>
            <div><p style="color:var(--gray-400);font-size:0.75rem;margin-bottom:2px;">HARGA UMUM</p><p style="font-weight:700;color:var(--primary);">Rp {{ number_format($obat->harga_umum, 0, ',', '.') }}</p></div>
            <div><p style="color:var(--gray-400);font-size:0.75rem;margin-bottom:2px;">HARGA BARANG</p><p>Rp {{ number_format($obat->harga_barang, 0, ',', '.') }}</p></div>
            <div><p style="color:var(--gray-400);font-size:0.75rem;margin-bottom:2px;">HARGA BPJS</p><p>Rp {{ number_format($obat->harga_jual_bpjs, 0, ',', '.') }}</p></div>
        </div>
        @if($obat->deskripsi)
            <div class="mt-2" style="padding-top:0.75rem;border-top:1px dashed var(--gray-200);">
                <p style="color:var(--gray-400);font-size:0.75rem;margin-bottom:4px;">DESKRIPSI</p>
                <p style="font-size:0.88rem;color:var(--gray-700);">{{ $obat->deskripsi }}</p>
            </div>
        @endif
    </div>
</div>
