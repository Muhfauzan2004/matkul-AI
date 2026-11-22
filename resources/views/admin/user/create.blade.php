@extends('layouts.admin')
@section('title', 'Tambah User Baru')

@section('content')
<div style="max-width:680px; margin:0 auto;">
    <div style="margin-bottom:1rem;">
        <a href="{{ route('admin.user.index') }}" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar User
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <span><i class="fas fa-user-plus" style="color:var(--primary); margin-right:0.4rem;"></i> Tambah User Baru</span>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.user.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Masukkan nama lengkap..." required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="contoh@apotek.com" required>
                    @error('email') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    @error('password') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="grid grid-2" style="gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="pemilik" {{ old('role') === 'pemilik' ? 'selected' : '' }}>Pemilik</option>
                            @if(auth()->user()->isSuperAdmin())
                                <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                            @endif
                        </select>
                        @error('role') <div class="form-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">No. Telepon / WhatsApp</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx">
                        @error('phone') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" rows="3" placeholder="Alamat lengkap pengguna...">{{ old('address') }}</textarea>
                    @error('address') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.25rem;">
                    <a href="{{ route('admin.user.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
