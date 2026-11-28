<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - APOTEK ITU TOKO OBAT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/pemilik/layout.css') }}">
    @stack('styles')
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('pemilik.dashboard') }}" class="sidebar-brand">
                <x-pharmacy-logo class="sidebar-brand-logo" />
                <span class="sidebar-brand-text">
                    APOTEK ITU TOKO OBAT
                    <small style="display:block;font-size:0.62rem;font-weight:600;color:#6ee7b7;letter-spacing:0.08em;text-transform:uppercase;margin-top:1px;">Panel Pemilik</small>
                </span>
            </a>
            <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Tutup Menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-label">Utama</div>
            <a href="{{ route('pemilik.dashboard') }}" class="sidebar-link {{ request()->routeIs('pemilik.dashboard') ? 'active' : '' }}" title="Dashboard">
                <i class="fas fa-chart-line"></i>
                <span class="sidebar-link-text">Dashboard</span>
            </a>

            <div class="sidebar-label">Inventaris</div>
            <a href="{{ route('pemilik.obat.index') }}" class="sidebar-link {{ request()->routeIs('pemilik.obat.*') ? 'active' : '' }}" title="Persetujuan Obat">
                <i class="fas fa-pills"></i>
                <span class="sidebar-link-text">Persetujuan Obat</span>
                @php $pendingObat = \App\Models\Obat::where('is_approved', false)->count(); @endphp
                @if($pendingObat > 0)
                    <span class="sidebar-badge">{{ $pendingObat }}</span>
                @endif
            </a>
            <a href="{{ route('pemilik.stock.index') }}" class="sidebar-link {{ request()->routeIs('pemilik.stock.*') ? 'active' : '' }}" title="Persetujuan Stok">
                <i class="fas fa-boxes"></i>
                <span class="sidebar-link-text">Persetujuan Stok</span>
                @php $pendingStock = \App\Models\StockAddition::where('status', 'pending')->count(); @endphp
                @if($pendingStock > 0)
                    <span class="sidebar-badge">{{ $pendingStock }}</span>
                @endif
            </a>

            <div class="sidebar-label">Laporan</div>
            <a href="{{ route('pemilik.reports.income') }}" class="sidebar-link {{ request()->routeIs('pemilik.reports.income') ? 'active' : '' }}" title="Laporan Pendapatan">
                <i class="fas fa-file-invoice-dollar"></i>
                <span class="sidebar-link-text">Laporan Pendapatan</span>
            </a>
            <a href="{{ route('pemilik.reports.stock') }}" class="sidebar-link {{ request()->routeIs('pemilik.reports.stock') ? 'active' : '' }}" title="Laporan Stok Masuk">
                <i class="fas fa-boxes"></i>
                <span class="sidebar-link-text">Laporan Stok Masuk</span>
            </a>

            <div class="sidebar-label">Manajemen</div>
            <a href="{{ route('pemilik.user.index') }}" class="sidebar-link {{ request()->routeIs('pemilik.user.*') ? 'active' : '' }}" title="Kelola Admin">
                <i class="fas fa-user-shield"></i>
                <span class="sidebar-link-text">Kelola Admin</span>
            </a>
        </nav>

        @include('components.sidebar-profile', [
            'profileRoute' => 'pemilik.profile.edit',
            'roleLabel' => 'Pemilik',
        ])
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <main class="admin-main">
        <div class="admin-topbar">
            <div class="admin-topbar-left">
                <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Buka Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>@yield('title', 'APOTEK ITU TOKO OBAT')</h1>
            </div>
        </div>

        <div class="admin-content">
            @if(session('success'))
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">
                    <div>
                        <div style="font-weight:700;margin-bottom:0.25rem;"><i class="fas fa-exclamation-circle"></i> Mohon periksa kesalahan berikut:</div>
                        @foreach($errors->all() as $error)
                            <div style="font-size:0.8rem;margin-left:1.25rem;list-style:disc;">• {{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif
            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit search inputs after 1 second of typing with focus preservation
            const searchInputs = document.querySelectorAll('input[name="search"]');
            let debounceTimer;

            searchInputs.forEach(input => {
                // Restore focus and cursor position on reload
                const isFocused = sessionStorage.getItem('search_focused_name') === input.name && 
                                  sessionStorage.getItem('search_focused_path') === window.location.pathname;
                                   
                if (isFocused) {
                    input.focus();
                    const cursorPosition = parseInt(sessionStorage.getItem('search_cursor_position') || '0', 10);
                    input.setSelectionRange(cursorPosition, cursorPosition);
                    
                    sessionStorage.removeItem('search_focused_name');
                    sessionStorage.removeItem('search_focused_path');
                    sessionStorage.removeItem('search_cursor_position');
                }
                
                input.addEventListener('input', function() {
                    // Save current focus and cursor position
                    sessionStorage.setItem('search_focused_name', input.name);
                    sessionStorage.setItem('search_focused_path', window.location.pathname);
                    sessionStorage.setItem('search_cursor_position', input.selectionStart);
                    
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        input.closest('form').submit();
                    }, 1000); // 1-second delay
                });

                // Track cursor updates on keyup/click to stay accurate
                ['keyup', 'click'].forEach(evt => {
                    input.addEventListener(evt, function() {
                        if (document.activeElement === input) {
                            sessionStorage.setItem('search_cursor_position', input.selectionStart);
                        }
                    });
                });
            });

            // Auto-submit on select change inside search/filter forms
            const formSelects = document.querySelectorAll('form[method="GET"] select');
            formSelects.forEach(select => {
                select.addEventListener('change', function() {
                    select.closest('form').submit();
                });
            });

            // Global SweetAlert2 Interceptor for Confirm & Delete
            document.addEventListener('click', function(e) {
                const clickable = e.target.closest('[onclick*="confirm("]');
                if (clickable) {
                    if (clickable.dataset.confirmed) {
                        delete clickable.dataset.confirmed;
                        return;
                    }

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    const onclickAttr = clickable.getAttribute('onclick');
                    let message = 'Apakah Anda yakin?';
                    const match = onclickAttr.match(/confirm\(['"](.+?)['"]\)/);
                    if (match && match[1]) {
                        message = match[1];
                    }

                    let title = 'Konfirmasi';
                    let confirmText = 'Ya';
                    let confirmColor = 'var(--primary)';
                    
                    const lowerMsg = message.toLowerCase();
                    if (lowerMsg.includes('hapus')) {
                        title = 'Konfirmasi Hapus';
                        confirmText = 'Ya, Hapus!';
                        confirmColor = 'var(--danger)';
                    } else if (lowerMsg.includes('setujui')) {
                        title = 'Konfirmasi Setuju';
                        confirmText = 'Ya, Setujui!';
                        confirmColor = 'var(--success)';
                    } else if (lowerMsg.includes('tolak')) {
                        title = 'Konfirmasi Tolak';
                        confirmText = 'Ya, Tolak!';
                        confirmColor = 'var(--danger)';
                    } else if (lowerMsg.includes('konfirmasi')) {
                        title = 'Konfirmasi';
                        confirmText = 'Ya, Konfirmasi!';
                        confirmColor = 'var(--success)';
                    }

                    Swal.fire({
                        title: title,
                        text: message,
                        icon: lowerMsg.includes('hapus') || lowerMsg.includes('tolak') ? 'warning' : 'question',
                        showCancelButton: true,
                        confirmButtonColor: confirmColor,
                        cancelButtonColor: 'var(--gray-500)',
                        confirmButtonText: confirmText,
                        cancelButtonText: 'Batal',
                        background: '#ffffff',
                        customClass: {
                            popup: 'premium-swal-popup'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const originalOnclick = clickable.getAttribute('onclick');
                            clickable.removeAttribute('onclick');
                            clickable.dataset.confirmed = 'true';
                            clickable.click();
                            clickable.setAttribute('onclick', originalOnclick);
                        }
                    });
                }
            }, true);

            document.addEventListener('submit', function(e) {
                const form = e.target;
                const onsubmitAttr = form.getAttribute('onsubmit');
                const methodInput = form.querySelector('input[name="_method"]');
                const isDelete = methodInput && methodInput.value.toUpperCase() === 'DELETE';
                
                if ((onsubmitAttr && onsubmitAttr.includes('confirm(')) || isDelete) {
                    if (form.dataset.confirmed) {
                        return;
                    }

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    let message = 'Apakah Anda yakin ingin menghapus data ini?';
                    if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
                        const match = onsubmitAttr.match(/confirm\(['"](.+?)['"]\)/);
                        if (match && match[1]) {
                            message = match[1];
                        }
                    }

                    let title = 'Konfirmasi';
                    let confirmText = 'Ya';
                    let confirmColor = 'var(--primary)';
                    
                    const lowerMsg = message.toLowerCase();
                    if (lowerMsg.includes('hapus') || isDelete) {
                        title = 'Konfirmasi Hapus';
                        confirmText = 'Ya, Hapus!';
                        confirmColor = 'var(--danger)';
                    }

                    Swal.fire({
                        title: title,
                        text: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: confirmColor,
                        cancelButtonColor: 'var(--gray-500)',
                        confirmButtonText: confirmText,
                        cancelButtonText: 'Batal',
                        background: '#ffffff',
                        customClass: {
                            popup: 'premium-swal-popup'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.dataset.confirmed = 'true';
                            form.submit();
                        }
                    });
                }
            }, true);
        });
    </script>
    
    @stack('scripts')
    @include('components.mobile-nav-script')

    @if(auth()->check())
        @include('components.ai-button')
    @endif

</body>
</html>