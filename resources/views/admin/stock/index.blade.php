@extends('layouts.admin')
@section('title', '')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/stock.css') }}">
@endpush
@section('content')

<div class="page-header">
    <div>
        <div class="page-title">Penambahan <span>Stok</span></div>
        <div style="font-size:0.8rem;color:var(--gray-500);margin-top:2px;">Pantau dan perbarui stok inventaris obat apotek</div>
    </div>
</div>

{{-- Stock Stats --}}
<div class="stock-stats" style="grid-template-columns: repeat(4,1fr);">
    <div class="stock-stat">
        <div class="ss-icon" style="background: #fee2e2; color: #ef4444;"><i class="fas fa-exclamation-circle"></i></div>
        <div>
            <div class="ss-label">Stok Kritis <span style="font-weight:400;">(&#60; 10)</span></div>
            <div class="ss-value" style="color:var(--danger);">{{ \App\Models\Obat::where('is_approved',true)->where('stok','<',10)->count() }}</div>
            <div style="font-size:0.68rem;color:var(--gray-400);margin-top:2px;">Perlu restock segera</div>
        </div>
    </div>
    <div class="stock-stat">
        <div class="ss-icon" style="background: #fef3c7; color: #f59e0b;"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <div class="ss-label">Stok Menipis <span style="font-weight:400;">(10–50)</span></div>
                <div class="ss-value" style="color:var(--warning-dark);">{{ \App\Models\Obat::where('is_approved',true)->whereBetween('stok',[10,50])->count() }}</div>
            <div style="font-size:0.68rem;color:var(--gray-400);margin-top:2px;">Mulai direncanakan restock</div>
        </div>
    </div>
    <div class="stock-stat">
        <div class="ss-icon" style="background: #e0f2fe; color: #0284c7;"><i class="fas fa-box"></i></div>
        <div>
            <div class="ss-label">Stok Tersedia <span style="font-weight:400;">(51–100)</span></div>
                <div class="ss-value" style="color:var(--primary);">{{ \App\Models\Obat::where('is_approved',true)->whereBetween('stok',[51,100])->count() }}</div>
            <div style="font-size:0.68rem;color:var(--gray-400);margin-top:2px;">Cukup untuk sementara</div>
        </div>
    </div>
    <div class="stock-stat">
        <div class="ss-icon" style="background: #dcfce7; color: #10b981;"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="ss-label">Stok Aman <span style="font-weight:400;">(&#62; 100)</span></div>
                <div class="ss-value" style="color:var(--success);">{{ \App\Models\Obat::where('is_approved',true)->where('stok','>',100)->count() }}</div>
            <div style="font-size:0.68rem;color:var(--gray-400);margin-top:2px;">Stok berlimpah & aman</div>
        </div>
    </div>
</div>

<div class="main-layout">
    {{-- Form Tambah Stok --}}
    <div class="form-card">
        <div class="form-card-header">
            <i class="fas fa-plus-circle" style="color:var(--primary);"></i>
            <h3>Input Penambahan Stok</h3>
        </div>
        <div class="form-card-body">
            <form action="{{ route('admin.stock.add') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="obat_select">Pilih Obat <span style="color:var(--danger);">*</span></label>
                    <select name="obat_id" id="obat_select" class="form-control" required>
                        <option value="">— Pilih obat —</option>
                        @foreach($obatsSelection as $o)
                            <option value="{{ $o->id }}" data-stok="{{ $o->stok }}" data-satuan="{{ $o->satuan }}">
                                {{ $o->nama_obat }} (Stok: {{ $o->stok }} {{ $o->satuan }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" id="stok_info" style="display:none;">
                    <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:0.75rem 1rem;font-size:0.82rem;">
                        <span style="color:var(--gray-500);">Stok saat ini:</span>
                        <strong id="stok_current" style="color:var(--gray-800);margin-left:0.4rem;"></strong>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="jumlah">Jumlah Stok Tambahan <span style="color:var(--danger);">*</span></label>
                    <input type="number" id="jumlah" name="jumlah" class="form-control" min="1" required placeholder="Contoh: 100">
                </div>
                <div class="form-group">
                    <label class="form-label" for="catatan">Catatan / Keterangan</label>
                    <input type="text" id="catatan" name="catatan" class="form-control" placeholder="Misal: Restock dari Supplier A">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fas fa-save"></i> Simpan Penambahan Stok
                </button>
            </form>
        </div>
    </div>

    {{-- Daftar Status Stok --}}
    <div class="card">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;justify-content:space-between;">
            <div style="font-weight:700;font-size:0.9rem;color:var(--gray-800);">
                <i class="fas fa-list" style="color:var(--primary);margin-right:0.4rem;"></i> Status Stok Seluruh Obat
            </div>
        </div>
        <div class="filter-card" style="border-radius:0;border-left:none;border-right:none;border-top:none;margin-bottom:0;">
            <form action="{{ route('admin.stock.index') }}" method="GET" class="form-inline" style="flex:1;">
                <div style="position:relative;flex:1;">
                    <i class="fas fa-search" style="position:absolute;left:0.65rem;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:0.8rem;pointer-events:none;"></i>
                    <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode obat..." value="{{ request('search') }}" style="padding-left:2rem;width:100%;" aria-label="Cari nama atau kode obat">
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                @if(request('search'))<a href="{{ route('admin.stock.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></a>@endif
            </form>
        </div>
        @forelse($obats as $obat)
            @php
            $pct = min(100, ($obat->stok / 200) * 100);
            if ($obat->stok < 10) {
                $color = 'var(--danger)'; $label = 'Kritis'; $badgeClass = 'badge-danger';
            } elseif ($obat->stok <= 50) {
                $color = 'var(--warning)'; $label = 'Menipis'; $badgeClass = 'badge-warning';
            } elseif ($obat->stok <= 100) {
                $color = 'var(--primary)'; $label = 'Tersedia'; $badgeClass = 'badge-primary';
            } else {
                $color = 'var(--success)'; $label = 'Aman'; $badgeClass = 'badge-success';
            }
        @endphp
        <div class="stok-row2">
            <div style="flex:1;min-width:0;">
                <div class="stok-name">{{ $obat->nama_obat }}</div>
                <div class="stok-meta"><span class="kode-pill">{{ $obat->kode }}</span> · {{ $obat->kategori->nama_kategori ?? '-' }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <div>
                    <div style="text-align:right;font-weight:700;font-size:0.85rem;color:{{ $color }};">{{ $obat->stok }} <span style="font-size:0.72rem;font-weight:400;color:var(--gray-400);">{{ $obat->satuan }}</span></div>
                    <div class="stok-bar-bg" style="margin-top:3px;">
                        <div class="stok-bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                    </div>
                </div>
                <span class="badge {{ $badgeClass }}" style="font-size:0.65rem;min-width:56px;justify-content:center;">{{ $label }}</span>
                <button class="add-btn" title="Tambah stok obat ini" onclick="selectObat({{ $obat->id }}, '{{ $obat->stok }}', '{{ $obat->satuan }}')">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        @empty
        <div class="empty-fancy">
            <i class="fas fa-box-open" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
            <p>Tidak ada data obat ditemukan</p>
        </div>
        @endforelse
        @if($obats->hasPages())
        <div style="padding:1rem 1.25rem;border-top:1px solid var(--gray-200);">
            {{ $obats->withQueryString()->links('pagination::bootstrap-4') }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
const select = document.getElementById('obat_select');
const stokInfo = document.getElementById('stok_info');
const stokCurrent = document.getElementById('stok_current');

select.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.value) {
        stokCurrent.textContent = opt.getAttribute('data-stok') + ' ' + opt.getAttribute('data-satuan');
        stokInfo.style.display = 'block';
    } else {
        stokInfo.style.display = 'none';
    }
});

function selectObat(id, stok, satuan) {
    select.value = id;
    stokCurrent.textContent = stok + ' ' + satuan;
    stokInfo.style.display = 'block';
    select.scrollIntoView({ behavior: 'smooth', block: 'center' });
    select.focus();
}
</script>
@endpush
@endsection
