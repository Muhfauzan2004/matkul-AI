
@extends('layouts.pemilik')
@section('title', 'Edit Admin')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pemilik/user-edit.css') }}">
@endpush

@section('content')
<div class="form-card-wrapper">
    <a href="{{ route('pemilik.user.index') }}" class="back-btn">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Admin
    </a>
    
    <div class="card">
        <div class="card-header">
            <div>
                <i class="fas fa-user-edit" style="color:var(--primary);margin-right:0.4rem;"></i>
                Perbarui Informasi Akun Admin
            </div>
            <span class="badge badge-primary" style="font-size:0.7rem;">ID: #{{ $user->id }}</span>
        </div>
        <div class="card-body">
            <form action="{{ route('pemilik.user.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="form-group">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" placeholder="Masukkan nama lengkap staf..." required>
                    @error('name')
                        <div class="form-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label class="form-label">Alamat Email *</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" placeholder="contoh: staf@apotek.com" required>
                    @error('email')
                        <div class="form-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        Kata Sandi (Password) 
                        <small style="color:var(--gray-500);font-weight:500;">(Kosongkan jika tidak ingin mengubah sandi)</small>
                    </label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Masukkan kata sandi baru jika ingin diubah...">
                    @error('password')
                        <div class="form-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">Hak Akses (Role) *</label>
                        <select name="role" class="form-control" style="cursor:pointer;" required>
                            <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin (Pengelola)</option>
                            <option value="pemilik" {{ old('role', $user->role) == 'pemilik' ? 'selected' : '' }}>Pemilik (Owner)</option>
                            @if($user->isSuperAdmin())
                                <option value="super_admin" selected>Super Admin (Staf Khusus)</option>
                            @endif
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">No. Telepon / WhatsApp</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="Contoh: 08123456789">
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom:1.75rem;">
                    <label class="form-label">Alamat Tempat Tinggal</label>
                    <textarea name="address" class="form-control" rows="3" placeholder="Masukkan alamat lengkap rumah staf di sini...">{{ old('address', $user->address) }}</textarea>
                </div>
                
                <div style="display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary" style="padding-left:2rem; padding-right:2rem;">
                        <i class="fas fa-save"></i> Perbarui Data Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
