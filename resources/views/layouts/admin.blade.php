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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin/shared.css') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            /* Opsi B: Emerald Pharmacy Green & Slate Palette */
            --dark: #022c22;
            --primary-dark: #065f46;
            --primary: #059669;
            --primary-medium: #10b981;
            --primary-light: #34d399;
            --accent-1: #6ee7b7;
            --accent-2: #a7f3d0;
            --accent-3: #d1fae5;
            --accent-4: #ecfdf5;
            --success: #10b981;
            --success-light: #dcfce7;
            --success-dark: #065f46;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --warning-dark: #92400e;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --danger-dark: #991b1b;

            --sidebar-width: 248px;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --white: #ffffff;
            --radius: 16px;
            --radius-sm: 10px;
            --shadow: 0 2px 6px rgba(2, 44, 34, 0.05);
            --shadow-md: 0 10px 20px -5px rgba(5, 150, 105, 0.1), 0 4px 6px -2px rgba(2, 44, 34, 0.04);
            --transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Outfit', 'Inter', -apple-system, sans-serif;
            background: #f4f8f6;
            color: var(--gray-800);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #022c22 0%, #064e3b 58%, #065f46 100%);
            color: rgba(255, 255, 255, 0.7);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            z-index: 50;
            overflow-y: auto;
            box-shadow: 4px 0 25px rgba(2, 44, 34, 0.15);
            border-right: 1px solid rgba(255, 255, 255, 0.06);
        }

        .sidebar-header {
            padding: 1.25rem 1.15rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            color: var(--white);
            font-weight: 800;
            font-size: 0.92rem;
            line-height: 1.22;
            letter-spacing: -0.01em;
        }

        .sidebar-brand i {
            background: linear-gradient(135deg, #34d399 0%, #a7f3d0 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.45rem;
            flex-shrink: 0;
            filter: drop-shadow(0 2px 8px rgba(16, 185, 129, 0.4));
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0.75rem;
        }

        .sidebar-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.11em;
            color: rgba(255, 255, 255, 0.55);
            padding: 0.85rem 0.75rem 0.35rem;
            font-weight: 700;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.62rem 0.8rem;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.74);
            border-radius: var(--radius-sm);
            font-size: 0.86rem;
            font-weight: 500;
            transition: var(--transition);
            margin-bottom: 3px;
            position: relative;
        }

        .sidebar-link:hover {
            background: rgba(255,255,255,0.08);
            color: var(--white);
            transform: translateX(3px);
        }

        .sidebar-link.active {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: var(--white);
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.36);
        }

        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 22%;
            height: 56%;
            width: 4px;
            background: #a7f3d0;
            border-radius: 0 4px 4px 0;
        }

        .sidebar-link i { width: 20px; text-align: center; font-size: 0.95rem; }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        /* ===== MAIN AREA ===== */
        .admin-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-topbar {
            background: rgba(255, 255, 255, 0.86);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.85);
            padding: 0 2rem;
            height: 66px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.02);
        }

        .admin-topbar h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            letter-spacing: -0.02em;
        }

        .admin-topbar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-content { padding: 1.5rem 2rem; }

        /* ===== REUSE SHOP STYLES ===== */
        .card { background: var(--white); border-radius: var(--radius); border: 1px solid var(--gray-200); overflow: hidden; transition: var(--transition); }
        .card:hover { box-shadow: var(--shadow-md); }
        .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--gray-200); font-weight: 600; display: flex; align-items: center; justify-content: space-between; }
        .card-body { padding: 1.25rem; }

        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border: none; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: var(--transition); text-decoration: none; font-family: inherit; line-height: 1.5; }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: var(--gray-200); color: var(--gray-700); }
        .btn-secondary:hover { background: var(--gray-300); }
        .btn-success { background: var(--success); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: var(--danger-dark); }
        .btn-outline { background: transparent; border: 1px solid var(--gray-300); color: var(--gray-600); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }

        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 0.35rem; font-size: 0.85rem; font-weight: 500; color: var(--gray-700); }
        .form-control { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--gray-300); border-radius: var(--radius-sm); font-size: 0.875rem; font-family: inherit; transition: var(--transition); background: var(--white); color: var(--gray-800); }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(14,165,233,0.15); }
        textarea.form-control { min-height: 100px; resize: vertical; }
        select.form-control { cursor: pointer; }
        .form-error { color: var(--danger); font-size: 0.78rem; margin-top: 0.25rem; }

        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--gray-200); }
        th { background: var(--gray-50); font-weight: 600; color: var(--gray-600); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; }
        tr:hover td { background: var(--gray-50); }

        .alert { padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: var(--success-light); color: var(--success-dark); border: 1px solid rgba(16, 185, 129, 0.3); }
        .alert-error { background: var(--danger-light); color: var(--danger-dark); border: 1px solid rgba(239, 68, 68, 0.25); }

        .badge { display: inline-flex; align-items: center; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.7rem; font-weight: 600; text-transform: capitalize; }
        .badge-primary { background: var(--primary); color: var(--white); }
        .badge-success { background: var(--success-light); color: var(--success-dark); }
        .badge-warning { background: var(--warning-light); color: var(--warning-dark); }
        .badge-danger { background: var(--danger-light); color: var(--danger-dark); }
        .badge-secondary { background: var(--gray-200); color: var(--gray-600); }

        .pagination { display: flex; gap: 0.25rem; list-style: none; margin-top: 1.5rem; justify-content: center; }
        .pagination li a, .pagination li span { padding: 0.4rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.85rem; text-decoration: none; color: var(--gray-600); border: 1px solid var(--gray-200); transition: var(--transition); }
        .pagination li.active span { background: var(--primary); color: white; border-color: var(--primary); }
        .pagination li a:hover { border-color: var(--primary); color: var(--primary); }
        .pagination li.disabled span { color: var(--gray-500); background: var(--gray-50); opacity: 0.7; }

        .stat-card { background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius); padding: 1.25rem; display: flex; align-items: center; gap: 1rem; }
        .stat-icon { width: 48px; height: 48px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
        .stat-info h3 { font-size: 1.5rem; font-weight: 700; color: var(--gray-800); }
        .stat-info p { font-size: 0.8rem; color: var(--gray-500); }

        .grid { display: grid; gap: 1.5rem; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: var(--gray-500); }
        .mt-1 { margin-top: 0.5rem; }
        .mt-2 { margin-top: 1rem; }
        .mt-3 { margin-top: 1.5rem; }
        .mb-1 { margin-bottom: 0.5rem; }
        .mb-2 { margin-bottom: 1rem; }
        .mb-3 { margin-bottom: 1.5rem; }
        .flex { display: flex; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .gap-1 { gap: 0.5rem; }
        .gap-2 { gap: 1rem; }
        .w-full { width: 100%; }
        .empty-state { text-align: center; padding: 3rem; color: var(--gray-400); }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; }

        .dropdown { position: relative; }
        .dropdown-menu { position: absolute; right: 0; top: calc(100% + 0.5rem); background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius); padding: 0.5rem; min-width: 180px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08); display: none; z-index: 200; }
        .dropdown-menu::before { content: ''; position: absolute; top: -0.6rem; left: 0; right: 0; height: 0.6rem; background: transparent; }
        .dropdown:hover .dropdown-menu { display: block; }
        .dropdown-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; text-decoration: none; color: var(--gray-600); font-size: 0.85rem; border-radius: var(--radius-sm); transition: var(--transition); }
        .dropdown-item:hover { background: var(--gray-100); color: var(--gray-800); }
        .dropdown-divider { height: 1px; background: var(--gray-200); margin: 0.25rem 0; }

        .form-inline { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .form-inline .form-control { width: auto; min-width: 200px; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .admin-main { margin-left: 0; }
            .admin-content { padding: 1rem; }
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
        }

        .premium-swal-popup {
            font-family: 'Inter', sans-serif !important;
            border-radius: var(--radius) !important;
        }
    </style>
    @stack('styles')
</head>
<body>
    {{-- SIDEBAR --}}
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
                <i class="fas fa-mortar-pestle"></i>
                <span class="sidebar-brand-text">
                    APOTEK ITU TOKO OBAT
                    <small style="display:block;font-size:0.62rem;font-weight:600;color:#6ee7b7;letter-spacing:0.08em;text-transform:uppercase;margin-top:1px;">Admin Panel</small>
                </span>
            </a>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-label">Menu Utama</div>
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" title="Dashboard">
                <i class="fas fa-tachometer-alt"></i>
                <span class="sidebar-link-text">Dashboard</span>
            </a>

            <div class="sidebar-label">Kelola Data</div>
            <a href="{{ route('admin.obat.index') }}" class="sidebar-link {{ request()->routeIs('admin.obat.*') ? 'active' : '' }}" title="Data Obat">
                <i class="fas fa-pills"></i>
                <span class="sidebar-link-text">Data Obat</span>
                @php $pendingObat = \App\Models\Obat::where('is_approved', false)->count(); @endphp
                @if($pendingObat > 0)
                    <span class="sidebar-badge" style="margin-left:auto;background:var(--warning-light);color:var(--warning-dark);border:1px solid rgba(146, 64, 14, 0.35);font-size:0.65rem;font-weight:700;padding:0.1rem 0.45rem;border-radius:999px;min-width:18px;text-align:center;">{{ $pendingObat }}</span>
                @endif
            </a>
            <a href="{{ route('admin.kategori.index') }}" class="sidebar-link {{ request()->routeIs('admin.kategori.*') ? 'active' : '' }}" title="Kategori">
                <i class="fas fa-tags"></i>
                <span class="sidebar-link-text">Kategori</span>
            </a>

            <div class="sidebar-label">Transaksi & Stok</div>
            <a href="{{ route('admin.orders.index') }}" class="sidebar-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" title="Catatan Penjualan">
                <i class="fas fa-shopping-cart"></i>
                <span class="sidebar-link-text">Catatan Penjualan</span>
                @php $pendingOrders = \App\Models\Order::where('status','pending')->count(); @endphp
                @if($pendingOrders > 0)
                    <span class="sidebar-badge" style="margin-left:auto;background:var(--warning-light);color:var(--warning-dark);border:1px solid rgba(146, 64, 14, 0.35);font-size:0.65rem;font-weight:700;padding:0.1rem 0.45rem;border-radius:999px;min-width:18px;text-align:center;">{{ $pendingOrders }}</span>
                @endif
            </a>
            <a href="{{ route('admin.stock.index') }}" class="sidebar-link {{ request()->routeIs('admin.stock.*') ? 'active' : '' }}" title="Penambahan Stok">
                <i class="fas fa-boxes"></i>
                <span class="sidebar-link-text">Penambahan Stok</span>
                @php $kritisStok = \App\Models\Obat::where('is_approved', true)->where('stok','<',10)->count(); @endphp
                @if($kritisStok > 0)
                    <span class="sidebar-badge" style="margin-left:auto;background:var(--danger-light);color:var(--danger-dark);border:1px solid rgba(153, 27, 27, 0.35);font-size:0.65rem;font-weight:700;padding:0.1rem 0.45rem;border-radius:999px;min-width:18px;text-align:center;">{{ $kritisStok }}</span>
                @endif
            </a>

            <div class="sidebar-label">Laporan</div>
            <a href="{{ route('admin.reports.sales') }}" class="sidebar-link {{ request()->routeIs('admin.reports.sales') ? 'active' : '' }}" title="Laporan Penjualan">
                <i class="fas fa-chart-bar"></i>
                <span class="sidebar-link-text">Laporan Penjualan</span>
            </a>
            <a href="{{ route('admin.reports.stock') }}" class="sidebar-link {{ request()->routeIs('admin.reports.stock') ? 'active' : '' }}" title="Laporan Stok Masuk">
                <i class="fas fa-boxes"></i>
                <span class="sidebar-link-text">Laporan Stok Masuk</span>
            </a>

            @if(auth()->user()->isSuperAdmin())
            <div class="sidebar-label">Super Admin</div>
            <a href="{{ route('admin.user.index') }}" class="sidebar-link {{ request()->routeIs('admin.user.*') ? 'active' : '' }}" title="Kelola User">
                <i class="fas fa-users-cog"></i>
                <span class="sidebar-link-text">Kelola User</span>
            </a>
            @endif
        </nav>
    </aside>

    {{-- MAIN --}}
    <main class="admin-main">
        <div class="admin-topbar">
            <h1>@yield('title', 'Dashboard')</h1>
            <div class="admin-topbar-actions">
                <div class="dropdown">
                    <span style="font-size:0.85rem;color:var(--gray-700);cursor:pointer;display:flex;align-items:center;gap:0.4rem;padding:0.35rem 0.75rem;border-radius:var(--radius-sm);transition:var(--transition);background:var(--gray-50);border:1px solid var(--gray-200);">
                        <i class="fas fa-user-circle" style="font-size:1.1rem;color:var(--primary);"></i>
                        <strong>{{ auth()->user()->name }}</strong>
                        <span class="badge badge-primary" style="font-size:0.65rem;">{{ str_replace('_', ' ', auth()->user()->role) }}</span>
                        <i class="fas fa-chevron-down" style="font-size:0.65rem;color:var(--gray-500);"></i>
                    </span>
                    <div class="dropdown-menu">
                        <a href="{{ route('admin.profile.edit') }}" class="dropdown-item">
                            <i class="fas fa-user-edit" style="color:var(--primary);"></i> Edit Profil
                        </a>
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                            @csrf
                            <button type="submit" class="dropdown-item w-full" style="border:none;background:none;cursor:pointer;font-family:inherit;font-size:inherit;text-align:left;width:100%">
                                <i class="fas fa-sign-out-alt" style="color:var(--danger);"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
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
                    <div><i class="fas fa-exclamation-circle"></i>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
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
                // Restore focus and cursor position on load
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

            // Auto-submit on select change (Filter Kategori / Status)
            const formSelects = document.querySelectorAll('form.form-inline select');
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

    @if(auth()->check())
        @include('components.ai-button')
    @endif

</body>
</html>
