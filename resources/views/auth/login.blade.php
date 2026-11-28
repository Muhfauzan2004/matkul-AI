<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - APOTEK ITU TOKO OBAT</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="login-container">
        <div class="login-brand">
            <div class="login-brand-icon">
                <x-pharmacy-logo class="auth-brand-logo" />
            </div>
            <h1>APOTEK ITU <span>TOKO OBAT</span></h1>
            <p>Sistem Manajemen Farmasi & Layanan PharmAI Terpadu</p>
        </div>

        <div class="login-card">
            <h2>Masuk ke akun Anda</h2>

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

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Email Staf / Pemilik</label>
                    <div class="input-icon-group">
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="email@contoh.com" required autofocus>
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-icon-group" style="position: relative;">
                        <input type="password" id="login_password" name="password" class="form-control" placeholder="••••••••" required style="padding-right: 2.5rem;">
                        <i class="fas fa-lock"></i>
                        <span onclick="togglePasswordVisibility('login_password', this)" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--gray-500); z-index: 10; display: flex; align-items: center; justify-content: center;">
                            <i class="far fa-eye" style="position: static; pointer-events: auto; color: inherit; font-size: 0.95rem;"></i>
                        </span>
                    </div>
                </div>

                <div class="form-check">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Ingat saya di perangkat ini</label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Masuk Sekarang
                </button>
            </form>
        </div>
    </div>
    <script>
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
    </script>
</body>
</html>
