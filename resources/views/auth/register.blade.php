<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - APOTEK ITU TOKO OBAT</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="register-container">
        <div class="register-brand">
            <div class="register-brand-icon">
                <i class="fas fa-mortar-pestle"></i>
            </div>
            <h1>APOTEK ITU <span>TOKO OBAT</span></h1>
            <p>Registrasi Staf & Operasional Farmasi</p>
        </div>

        <div class="register-card">
            <h2>Buat Akun Staf Baru</h2>

            @if($errors->any())
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <div class="input-icon-group">
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Nama lengkap Anda" required>
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Operasional</label>
                    <div class="input-icon-group">
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="email@contoh.com" required>
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">No. Telepon / WhatsApp</label>
                    <div class="input-icon-group">
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx">
                        <i class="fas fa-phone"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Kode Undangan Staf</label>
                    <div class="input-icon-group">
                        <input type="text" name="invite_code" class="form-control" value="{{ old('invite_code') }}" placeholder="Masukkan kode registrasi admin">
                        <i class="fas fa-key"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-icon-group">
                        <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                        <i class="fas fa-lock"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Konfirmasi Password</label>
                    <div class="input-icon-group">
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password" required>
                        <i class="fas fa-shield-halved"></i>
                    </div>
                </div>

                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Registrasi Akun Baru
                </button>
            </form>

            <div class="register-footer">
                Sudah terdaftar? <a href="{{ route('login') }}">Masuk di sini</a>
            </div>
        </div>
    </div>
</body>
</html>
