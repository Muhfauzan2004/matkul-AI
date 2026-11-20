@extends('layouts.admin')
@section('title', 'Tambah Kategori')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/kategori.css') }}">
@endpush
@section('content')

<div class="page-header">
    <div>
        <div class="page-title">Tambah <span>Kategori</span></div>
        <div class="page-subtitle">Buat kategori baru untuk mengelompokkan obat</div>
    </div>
    <a href="{{ route('admin.kategori.index') }}" class="btn btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="form-page-layout">
    {{-- FORM CARD --}}
    <div class="card">
        <div style="padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--gray-200); background: linear-gradient(135deg, var(--accent-4), var(--accent-3)); display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-tag" style="color: var(--primary);"></i>
            <span style="font-weight: 700; font-size: 0.9rem; color: var(--gray-800);">Form Kategori Baru</span>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.kategori.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label class="form-label">Nama Kategori <span style="color: var(--danger);">*</span></label>
                    <div style="position: relative;">
                        <i class="fas fa-tag" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:0.85rem;pointer-events:none;"></i>
                        <input type="text" name="nama_kategori" class="form-control" value="{{ old('nama_kategori') }}"
                               placeholder="Contoh: Antibiotik, Vitamin, Analgesik..." required
                               style="padding-left: 2.25rem;">
                    </div>
                    @error('nama_kategori')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                    <div style="font-size: 0.72rem; color: var(--gray-400); margin-top: 0.3rem;">
                        Slug akan dibuat otomatis dari nama kategori
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <div style="position: relative;">
                        <i class="fas fa-align-left" style="position:absolute;left:0.75rem;top:0.75rem;color:var(--gray-400);font-size:0.85rem;pointer-events:none;"></i>
                        <textarea name="deskripsi" class="form-control" rows="4"
                                  placeholder="Keterangan singkat tentang kategori ini..."
                                  style="padding-left: 2.25rem;">{{ old('deskripsi') }}</textarea>
                    </div>
                    @error('deskripsi')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--gray-200);">
                    <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
                        <i class="fas fa-save"></i> Simpan Kategori
                    </button>
                    <a href="{{ route('admin.kategori.index') }}" class="btn btn-outline">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- TIPS SIDE PANEL --}}
    <div>
        <div class="form-side-card">
            <h4><i class="fas fa-lightbulb"></i> Tips Kategori yang Baik</h4>
            <div class="tip-item">
                <i class="fas fa-check-circle"></i>
                <span>Gunakan nama yang jelas dan familiar, misal: <strong>Antibiotik</strong>, <strong>Vitamin</strong>, <strong>Antijamur</strong></span>
            </div>
            <div class="tip-item">
                <i class="fas fa-check-circle"></i>
                <span>Hindari nama yang terlalu umum seperti "Obat" atau "Lain-lain"</span>
            </div>
            <div class="tip-item">
                <i class="fas fa-check-circle"></i>
                <span>Tambahkan deskripsi agar staf mudah memahami jenis obat dalam kategori ini</span>
            </div>
            <div class="tip-item">
                <i class="fas fa-check-circle"></i>
                <span>Slug dibuat otomatis — tidak perlu diisi manual</span>
            </div>
        </div>

        <div class="card" style="margin-top: 1rem;">
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--gray-200);">
                <div style="font-size: 0.78rem; font-weight: 700; color: var(--gray-600); text-transform: uppercase; letter-spacing: 0.05em;">
                    <i class="fas fa-list" style="color: var(--primary);"></i> Kategori yang Ada
                </div>
            </div>
            @php $existingKats = \App\Models\Kategori::withCount('obat')->orderBy('nama_kategori')->take(8)->get(); @endphp
            <div style="padding: 0.5rem;">
                @forelse($existingKats as $ek)
                <div style="padding: 0.4rem 0.75rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; border-radius: var(--radius-sm); transition: var(--transition);">
                    <span style="color: var(--gray-700);">{{ $ek->nama_kategori }}</span>
                    <span class="badge badge-primary" style="font-size: 0.65rem;">{{ $ek->obat_count }}</span>
                </div>
                @empty
                <div style="padding: 1rem; text-align: center; color: var(--gray-400); font-size: 0.8rem;">Belum ada kategori</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
