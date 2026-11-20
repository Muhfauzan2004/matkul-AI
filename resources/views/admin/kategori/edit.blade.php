@extends('layouts.admin')
@section('title', 'Edit Kategori')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/kategori.css') }}">
@endpush
@section('content')

<div class="page-header">
    <div>
        <div class="page-title">Edit <span>Kategori</span></div>
        <div class="page-subtitle">Perbarui informasi kategori: <strong>{{ $kategori->nama_kategori }}</strong></div>
    </div>
    <a href="{{ route('admin.kategori.index') }}" class="btn btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="form-page-layout">
    {{-- FORM CARD --}}
    <div class="card">
        <div style="padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--gray-200); background: linear-gradient(135deg, var(--warning-light), #fde68a); display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-edit" style="color: var(--warning-dark);"></i>
            <span style="font-weight: 700; font-size: 0.9rem; color: var(--warning-dark);">Edit: {{ $kategori->nama_kategori }}</span>
            <span class="badge badge-primary" style="margin-left: auto;">{{ $kategori->obat_count ?? 0 }} obat</span>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.kategori.update', $kategori) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Nama Kategori <span style="color: var(--danger);">*</span></label>
                    <div style="position: relative;">
                        <i class="fas fa-tag" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:0.85rem;pointer-events:none;"></i>
                        <input type="text" name="nama_kategori" class="form-control"
                               value="{{ old('nama_kategori', $kategori->nama_kategori) }}"
                               placeholder="Nama kategori..." required
                               style="padding-left: 2.25rem;">
                    </div>
                    @error('nama_kategori')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Slug <small style="color: var(--gray-400); font-weight: 400;">(readonly — diperbarui otomatis)</small></label>
                    <div style="position: relative;">
                        <i class="fas fa-link" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--gray-300);font-size:0.85rem;pointer-events:none;"></i>
                        <input type="text" class="form-control" value="{{ $kategori->slug }}" readonly
                               style="padding-left: 2.25rem; background: var(--gray-100); color: var(--gray-500); cursor: not-allowed; font-family: monospace; font-size: 0.83rem;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <div style="position: relative;">
                        <i class="fas fa-align-left" style="position:absolute;left:0.75rem;top:0.75rem;color:var(--gray-400);font-size:0.85rem;pointer-events:none;"></i>
                        <textarea name="deskripsi" class="form-control" rows="4"
                                  placeholder="Keterangan singkat tentang kategori ini..."
                                  style="padding-left: 2.25rem;">{{ old('deskripsi', $kategori->deskripsi) }}</textarea>
                    </div>
                    @error('deskripsi')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--gray-200);">
                    <button type="submit" class="btn btn-warning" style="flex: 1; justify-content: center; color: white;">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.kategori.index') }}" class="btn btn-outline">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- INFO SIDE PANEL --}}
    <div>
        <div class="form-side-card">
            <h4><i class="fas fa-info-circle"></i> Informasi Kategori</h4>
            <div style="font-size: 0.8rem; color: var(--gray-600); line-height: 1.6;">
                <div style="display: flex; justify-content: space-between; padding: 0.3rem 0; border-bottom: 1px dashed var(--gray-300);">
                    <span>ID Kategori</span>
                    <strong>#{{ $kategori->id }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.3rem 0; border-bottom: 1px dashed var(--gray-300);">
                    <span>Jumlah Obat</span>
                    <strong style="color: var(--primary);">{{ $kategori->obat_count ?? 0 }} obat</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.3rem 0; border-bottom: 1px dashed var(--gray-300);">
                    <span>Dibuat</span>
                    <strong>{{ $kategori->created_at->format('d/m/Y') }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.3rem 0;">
                    <span>Diperbarui</span>
                    <strong>{{ $kategori->updated_at->diffForHumans() }}</strong>
                </div>
            </div>
        </div>

        {{-- DANGER ZONE --}}
        <div class="card" style="margin-top: 1rem; border-color: var(--danger-light);">
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--danger-light); background: var(--danger-light);">
                <div style="font-size: 0.78rem; font-weight: 700; color: var(--danger-dark); text-transform: uppercase; letter-spacing: 0.05em;">
                    <i class="fas fa-exclamation-triangle"></i> Zona Berbahaya
                </div>
            </div>
            <div style="padding: 1rem 1.25rem;">
                <p style="font-size: 0.78rem; color: var(--gray-500); margin-bottom: 0.75rem; line-height: 1.5;">
                    Menghapus kategori ini tidak akan menghapus obat yang ada di dalamnya, namun kategori obat tersebut akan menjadi kosong.
                </p>
                <form action="{{ route('admin.kategori.destroy', $kategori) }}" method="POST"
                      onsubmit="return confirm('Hapus kategori {{ $kategori->nama_kategori }}? Obat di dalamnya tidak akan terhapus.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" style="width: 100%; justify-content: center;">
                        <i class="fas fa-trash"></i> Hapus Kategori Ini
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
