@extends('layouts.admin')
@section('title', 'Edit Profil')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/profile.css') }}">
@endpush
@section('content')

<div class="page-header">
    <div>
        <div class="page-title">Edit <span>Profil</span></div>
        <div class="page-subtitle">Kelola informasi akun dan keamanan Anda</div>
    </div>
    <a href="{{ auth()->user()->isSuperAdmin() ? route('admin.user.index') : route('admin.dashboard') }}" class="btn btn-outline btn-sm">
        <i class="fas {{ auth()->user()->isSuperAdmin() ? 'fa-users-cog' : 'fa-home' }}"></i> {{ auth()->user()->isSuperAdmin() ? 'Kelola User' : 'Dashboard' }}
    </a>
</div>

<div class="profile-layout">
    {{-- LEFT: AVATAR CARD --}}
    <div class="avatar-card">
        <div class="avatar-card-top">
            @php
                $initials = collect(explode(' ', $user->name))->map(fn($w) => strtoupper(substr($w,0,1)))->take(2)->implode('');
            @endphp
            <div class="avatar-circle">{{ $initials }}</div>
            <div class="avatar-name">{{ $user->name }}</div>
            <span class="avatar-role-badge">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
        </div>
        <div class="avatar-stats">
            <div class="avatar-stat-row">
                <span class="avatar-stat-label"><i class="fas fa-envelope" style="width:14px;"></i> Email</span>
                <span class="avatar-stat-value" style="font-size:0.75rem;">{{ Str::limit($user->email, 18) }}</span>
            </div>
            <div class="avatar-stat-row">
                <span class="avatar-stat-label"><i class="fas fa-phone" style="width:14px;"></i> Telepon</span>
                <span class="avatar-stat-value">{{ $user->phone ?: '—' }}</span>
            </div>
            <div class="avatar-stat-row">
                <span class="avatar-stat-label"><i class="fas fa-calendar" style="width:14px;"></i> Bergabung</span>
                <span class="avatar-stat-value">{{ $user->created_at->format('M Y') }}</span>
            </div>
            <div class="avatar-stat-row">
                <span class="avatar-stat-label"><i class="fas fa-clock" style="width:14px;"></i> Terakhir login</span>
                <span class="avatar-stat-value">{{ $user->updated_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>

    {{-- RIGHT: FORMS --}}
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        {{-- PROFILE INFO CARD --}}
        <div class="card">
            <div class="card-header" style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200);">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-id-card" style="color: var(--primary); font-size: 1.1rem;"></i>
                    <span>Informasi Profil</span>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-2" style="gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap <span style="color: var(--danger);">*</span></label>
                            <div style="position: relative;">
                                <i class="fas fa-user" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.85rem; pointer-events: none;"></i>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required style="padding-left: 2.25rem;">
                            </div>
                            @error('name')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Alamat Email <span style="color: var(--danger);">*</span></label>
                            <div style="position: relative;">
                                <i class="fas fa-envelope" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.85rem; pointer-events: none;"></i>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required style="padding-left: 2.25rem;">
                            </div>
                            @error('email')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="grid grid-2" style="gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Nomor Telepon</label>
                            <div style="position: relative;">
                                <i class="fas fa-phone" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.85rem; pointer-events: none;"></i>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx" style="padding-left: 2.25rem;">
                            </div>
                            @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Role Pengguna</label>
                            <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $user->role)) }}" readonly style="background: var(--gray-100); color: var(--gray-500); cursor: not-allowed; font-weight: 500;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Lengkap</label>
                        <div style="position: relative;">
                            <i class="fas fa-map-marker-alt" style="position: absolute; left: 0.75rem; top: 1rem; color: var(--gray-400); font-size: 0.85rem; pointer-events: none;"></i>
                            <textarea name="address" class="form-control" rows="3" placeholder="Alamat lengkap..." style="padding-left: 2.25rem;">{{ old('address', $user->address) }}</textarea>
                        </div>
                        @error('address')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="mt-2 text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- PASSWORD UPDATE CARD --}}
        <div class="card">
            <div class="card-header" style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200);">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-shield-alt" style="color: var(--warning); font-size: 1.1rem;"></i>
                    <span>Ubah Password</span>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label">Password Saat Ini <span style="color: var(--danger);">*</span></label>
                        <div style="position: relative;">
                            <i class="fas fa-lock" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.85rem; pointer-events: none;"></i>
                            <input type="password" id="current_password" name="current_password" class="form-control" required style="padding-left: 2.25rem; padding-right: 2.5rem;" placeholder="Masukkan password lama Anda">
                            <span onclick="togglePasswordVisibility('current_password', this)" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--gray-500); z-index: 10;">
                                <i class="far fa-eye" style="position: static; pointer-events: auto; color: inherit; font-size: 0.85rem;"></i>
                            </span>
                        </div>
                        @error('current_password')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password Baru <span style="color: var(--danger);">*</span></label>
                        <div style="position: relative;">
                            <i class="fas fa-key" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.85rem; pointer-events: none;"></i>
                            <input type="password" id="new_password" name="password" class="form-control" required style="padding-left: 2.25rem; padding-right: 2.5rem;" placeholder="Minimal 6 karakter" oninput="checkPasswordStrength(this.value); checkPasswordMatch();">
                            <span onclick="togglePasswordVisibility('new_password', this)" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--gray-500); z-index: 10;">
                                <i class="far fa-eye" style="position: static; pointer-events: auto; color: inherit; font-size: 0.85rem;"></i>
                            </span>
                        </div>
                        {{-- Password Strength Indicator --}}
                        <div class="pwd-strength-bar">
                            <span id="bar1"></span>
                            <span id="bar2"></span>
                            <span id="bar3"></span>
                            <span id="bar4"></span>
                        </div>
                        <div id="pwd-strength-text" class="pwd-strength-text" style="color: var(--gray-400);">Masukkan password baru</div>
                        @error('password')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password Baru <span style="color: var(--danger);">*</span></label>
                        <div style="position: relative;">
                            <i class="fas fa-check-double" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.85rem; pointer-events: none;"></i>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required style="padding-left: 2.25rem;" placeholder="Ulangi password baru Anda" oninput="checkPasswordMatch();">
                        </div>
                        <div id="password-match-text" style="font-size: 0.78rem; font-weight: 600; margin-top: 0.25rem; min-height: 1rem;"></div>
                    </div>

                    <div class="mt-2 text-right">
                        <button type="submit" class="btn btn-warning" style="color: white;">
                            <i class="fas fa-key"></i> Perbarui Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function checkPasswordStrength(value) {
    const bars = [document.getElementById('bar1'), document.getElementById('bar2'),
                  document.getElementById('bar3'), document.getElementById('bar4')];
    const text = document.getElementById('pwd-strength-text');

    let score = 0;
    if (value.length >= 6)  score++;
    if (value.length >= 10) score++;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
    if (/[0-9]/.test(value) && /[^A-Za-z0-9]/.test(value)) score++;

    const colors = ['#ef4444', '#f97316', '#f59e0b', '#10b981'];
    const labels = ['Sangat Lemah', 'Lemah', 'Sedang', 'Kuat'];

    bars.forEach((bar, i) => {
        bar.style.background = i < score ? colors[Math.min(score - 1, 3)] : 'var(--gray-200)';
    });

    if (value.length === 0) {
        text.textContent = 'Masukkan password baru';
        text.style.color = 'var(--gray-400)';
    } else {
        text.textContent = 'Kekuatan: ' + labels[Math.min(score - 1, 3)];
        text.style.color = colors[Math.min(score - 1, 3)];
    }
}

function togglePasswordVisibility(inputId, element) {
    const input = document.getElementById(inputId);
    const icon = element.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function checkPasswordMatch() {
    const password = document.getElementById('new_password');
    const confirmation = document.getElementById('password_confirmation');
    const matchText = document.getElementById('password-match-text');

    if (!confirmation.value) {
        matchText.textContent = '';
        return;
    }

    if (password.value === confirmation.value) {
        matchText.textContent = '✓ Password sama';
        matchText.style.color = '#10b981';
    } else {
        matchText.textContent = '✗ Password tidak sama';
        matchText.style.color = '#ef4444';
    }
}
</script>
@endpush
@endsection
