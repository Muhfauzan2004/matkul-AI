@extends('layouts.admin')
@section('title', '')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/obat.css') }}">
@endpush
@section('content')

<div class="page-header">
    <div>
        <div class="page-title">Data <span>Obat</span></div>
        <div style="font-size:0.8rem;color:var(--gray-500);margin-top:2px;">Kelola seluruh inventaris obat apotek</div>
    </div>
    <div style="display:flex; gap:0.5rem; align-items:center;">
        <a href="{{ route('admin.obat.export', request()->all()) }}" class="btn btn-success" style="display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none; font-weight: 600;">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <button onclick="toggleImportModal(true)" class="btn btn-outline" style="border-color:var(--accent-1); color:var(--accent-1); background:var(--accent-4);">
            <i class="fas fa-file-excel"></i> Import Excel
        </button>
        <a href="{{ route('admin.obat.create') }}" class="btn btn-primary js-obat-modal" data-modal-title="Tambah Obat Baru" data-modal-icon="fas fa-plus-circle">
            <i class="fas fa-plus"></i> Tambah Obat Baru
        </a>
    </div>
</div>

<div class="filter-card">
    <form action="{{ route('admin.obat.index') }}" method="GET" class="form-inline" style="flex:1;" id="filter-form">
        <input type="hidden" name="sort_by" id="sort_by" value="{{ request('sort_by') }}">
        <input type="hidden" name="sort_dir" id="sort_dir" value="{{ request('sort_dir', 'asc') }}">
        <div style="position:relative;">
            <i class="fas fa-search" style="position:absolute;left:0.65rem;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:0.8rem;pointer-events:none;"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode obat..." value="{{ request('search') }}" style="padding-left:2rem;min-width:220px;" aria-label="Cari nama atau kode obat">
        </div>
        <select name="kategori" class="form-control" style="width:auto;min-width:auto;">
            <option value="">Semua Kategori</option>
            @foreach($kategoris as $kat)<option value="{{ $kat->id }}" {{ request('kategori')==$kat->id?'selected':'' }}>{{ $kat->nama_kategori }}</option>@endforeach
        </select>
        <select name="status" class="form-control" style="width:auto;min-width:auto;">
            <option value="">Semua Status</option>
            <option value="aktif" {{ request('status')=='aktif'?'selected':'' }}>Aktif</option>
            <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending Konfirmasi</option>
            <option value="nonaktif" {{ request('status')=='nonaktif'?'selected':'' }}>Nonaktif</option>
        </select>
        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:nowrap;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
            @if(request()->anyFilled(['search','kategori','status','sort_by']))
                <a href="{{ route('admin.obat.index') }}" class="btn btn-outline btn-sm" style="white-space:nowrap;"><i class="fas fa-times"></i> Reset</a>
            @endif
        </div>
    </form>
    <div style="font-size:0.8rem;color:var(--gray-500);white-space:nowrap;">
        {{ $obats->total() }} obat ditemukan
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="obat-table">
            <thead>
                <tr>
                    <th style="width:90px;">Kode</th>
                    <th>Nama Obat</th>
                    <th>Kategori</th>
                    <th>
                        @php
                            $isSortingStok = request('sort_by') === 'stok';
                            $nextDir = ($isSortingStok && request('sort_dir') === 'asc') ? 'desc' : 'asc';
                            $sortIcon = $isSortingStok
                                ? (request('sort_dir') === 'asc' ? 'fa-sort-amount-up-alt' : 'fa-sort-amount-down-alt')
                                : 'fa-sort';
                            $sortColor = $isSortingStok ? 'var(--primary)' : 'var(--gray-400)';
                        @endphp
                        <button type="button"
                            onclick="setSortAndSubmit('stok', '{{ $nextDir }}')"
                            style="background:none;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:0.35rem;font-size:0.78rem;font-weight:600;color:var(--gray-600);text-transform:uppercase;letter-spacing:0.05em;padding:0;font-family:inherit;"
                            title="Klik untuk urut berdasarkan stok">
                            Stok <i class="fas {{ $sortIcon }}" style="color:{{ $sortColor }};font-size:0.7rem;"></i>
                        </button>
                    </th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th style="width:100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($obats as $obat)
                @php
                    $stokPct = min(100, ($obat->stok / 100) * 100);
                    $stokColor = $obat->stok < 10 ? 'var(--danger)' : ($obat->stok < 50 ? 'var(--warning)' : 'var(--success)');
                @endphp
                <tr>
                    <td><span class="kode-pill">{{ $obat->kode }}</span></td>
                    <td>
                        <div class="obat-name">
                            {{ $obat->nama_obat }}
                            <small>{{ $obat->deskripsi ? Str::limit($obat->deskripsi, 40) : '—' }}</small>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-primary">{{ $obat->kategori->nama_kategori ?? '-' }}</span>
                    </td>
                    <td>
                        <div style="font-weight:700;font-size:0.85rem;color:{{ $stokColor }};">{{ $obat->stok }} <span style="font-weight:400;color:var(--gray-400);font-size:0.73rem;">{{ $obat->satuan }}</span></div>
                        <div class="stok-bar-bg"><div class="stok-bar" style="width:{{ $stokPct }}%;background:{{ $stokColor }};"></div></div>
                    </td>
                    <td>
                        <div class="price-col">
                            Rp {{ number_format($obat->harga_umum, 0, ',', '.') }}
                            <small>BPJS: Rp {{ number_format($obat->harga_jual_bpjs, 0, ',', '.') }}</small>
                        </div>
                    </td>
                    <td>
                        @if(!$obat->is_approved)
                            <span class="badge badge-warning"><i class="fas fa-clock" style="font-size:0.65rem;"></i> Pending</span>
                        @elseif($obat->is_active)
                            <span class="badge badge-success"><i class="fas fa-check" style="font-size:0.65rem;"></i> Aktif</span>
                        @else
                            <span class="badge badge-secondary">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-group">
                            @if($obat->is_approved)
                                <a href="{{ route('admin.obat.show', $obat) }}" class="btn-icon btn-icon-blue js-obat-modal" data-modal-title="Detail Obat" data-modal-icon="fas fa-eye" title="Lihat Detail"><i class="fas fa-eye"></i></a>
                                @if($obat->is_active)
                                <a href="{{ route('admin.obat.edit', $obat) }}" class="btn-icon btn-icon-amber js-obat-modal" data-modal-title="Edit: {{ $obat->nama_obat }}" data-modal-icon="fas fa-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.obat.destroy', $obat) }}" method="POST" onsubmit="return confirm('Hapus obat ini secara permanen?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-icon btn-icon-red" title="Hapus"><i class="fas fa-trash"></i></button>
                                </form>
                                @endif
                            @else
                                <span style="font-size:0.75rem;color:var(--gray-400);font-style:italic;">Menunggu Konfirmasi</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-fancy">
                            <div class="empty-icon"><i class="fas fa-pills"></i></div>
                            <h4>Belum ada data obat</h4>
                            <p>Mulai tambahkan obat ke inventaris apotek Anda</p>
                            <a href="{{ route('admin.obat.create') }}" class="btn btn-primary btn-sm js-obat-modal" data-modal-title="Tambah Obat Baru" data-modal-icon="fas fa-plus-circle" style="margin-top:0.75rem;"><i class="fas fa-plus"></i> Tambah Obat</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($obats->hasPages())
    <div style="padding:1rem 1.25rem;border-top:1px solid var(--gray-200);">
        {{ $obats->withQueryString()->links('pagination::bootstrap-4') }}
    </div>
    @endif
</div>

{{-- IMPORT EXCEL MODAL --}}
<div id="importModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; backdrop-filter:blur(4px); transition: var(--transition);">
    <div class="card" style="width:100%; max-width:480px; margin:1rem; border-radius:var(--radius); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15), 0 10px 10px -5px rgba(0,0,0,0.04); overflow:hidden; animation: modalSlideIn 0.3s ease-out;">
    <div class="section-header" style="padding:1.25rem 1.5rem; background:linear-gradient(135deg, var(--accent-1) 0%, var(--primary-medium) 100%); border-bottom:none; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="font-size:1.05rem; font-weight:700; color:white; margin:0; display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-file-excel"></i> Import Data Obat dari Excel
            </h3>
            <button onclick="toggleImportModal(false)" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer; opacity:0.8; transition:0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.8">&times;</button>
        </div>
        
        <form action="{{ route('admin.obat.import') }}" method="POST" enctype="multipart/form-data" style="padding:1.5rem; display:flex; flex-direction:column; gap:1.25rem;">
            @csrf
            <div>
                <label class="form-label" style="font-weight:600; font-size:0.83rem; color:var(--gray-700); margin-bottom:0.5rem; display:block;">Pilih File Excel / CSV</label>
                <div style="border:2px dashed var(--accent-4); border-radius:var(--radius-sm); padding:2rem 1.5rem; text-align:center; background:var(--accent-4); cursor:pointer; position:relative; transition:0.2s;" onmouseover="this.style.borderColor='var(--accent-1)'" onmouseout="this.style.borderColor='var(--accent-4)'" onclick="document.getElementById('file_excel').click()">
                    <i class="fas fa-cloud-upload-alt" style="font-size:2rem; color:var(--accent-1); margin-bottom:0.5rem; display:block;"></i>
                    <span id="file-label" style="font-size:0.8rem; color:var(--accent-1); font-weight:500;">Pilih file Excel (.xlsx, .xls, .csv)</span>
                    <span style="font-size:0.68rem; color:var(--accent-1); opacity:0.8; display:block; margin-top:2px;">Maksimal ukuran file: 5MB</span>
                    <input type="file" id="file_excel" name="file_excel" accept=".xlsx,.xls,.csv" required style="display:none;" onchange="handleFileChange(this)">
                </div>
            </div>
            
            <div style="background:var(--gray-50); border:1px solid var(--gray-200); border-radius:8px; padding:0.75rem 1rem;">
                <div style="font-size:0.75rem; font-weight:700; color:var(--gray-600); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.4rem;">Format Kolom Excel:</div>
                <ul style="font-size:0.72rem; color:var(--gray-500); padding-left:1rem; margin:0; line-height:1.5; display:flex; flex-direction:column; gap:0.2rem;">
                    <li><strong>Nama Obat</strong> <span style="color:var(--primary-dark);">*</span> (nama obat medis)</li>
                    <li><strong>Kategori</strong> <span style="color:var(--primary-dark);">*</span> (misal: Tablet, Sirup, Kapsul)</li>
                    <li><strong>Stok</strong> (misal: 100)</li>
                    <li><strong>Satuan</strong> (misal: Botol, Pcs, Strip)</li>
                    <li><strong>Harga Umum</strong> <span style="color:var(--primary-dark);">*</span> (harga non-BPJS)</li>
                    <li><strong>Harga Barang</strong> <span style="color:var(--primary-dark);">*</span> (harga beli/pokok)</li>
                    <li><strong>Harga Jual BPJS</strong> <span style="color:var(--primary-dark);">*</span> (harga khusus BPJS)</li>
                    <li><strong>Deskripsi</strong> (keterangan obat)</li>
                </ul>
                <div style="font-size:0.65rem; color:var(--gray-400); margin-top:0.4rem; font-style:italic;">* Wajib diisi di header Excel</div>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:0.6rem; margin-top:0.5rem; border-top:1px solid var(--gray-100); padding-top:1rem;">
                <button type="button" onclick="toggleImportModal(false)" class="btn btn-outline btn-sm">Batal</button>
                <button type="submit" class="btn btn-success btn-sm" style="background:var(--accent-1); border-color:var(--accent-1); color:white;">
                    <i class="fas fa-check"></i> Proses Import
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleImportModal(show) {
    const modal = document.getElementById('importModal');
    if (show) {
        modal.style.display = 'flex';
    } else {
        modal.style.display = 'none';
    }
}

function handleFileChange(input) {
    const label = document.getElementById('file-label');
    if (input.files && input.files[0]) {
        label.textContent = input.files[0].name;
        label.style.color = 'var(--accent-1)';
        label.style.fontWeight = '700';
    } else {
        label.textContent = 'Pilih file Excel (.xlsx, .xls, .csv)';
        label.style.fontWeight = '500';
    }
}

function setSortAndSubmit(column, direction) {
    document.getElementById('sort_by').value = column;
    document.getElementById('sort_dir').value = direction;
    document.getElementById('filter-form').submit();
}
</script>

@include('components.obat-crud-modal')
@endsection
