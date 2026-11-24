<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ObatController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// ==================== AUTH ROUTES ====================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==================== ROOT / LOGIN ====================
Route::get('/', [AuthController::class, 'showLogin']);

// ==================== PEMILIK (AUTH) ====================
Route::middleware('auth')->group(function () {

   
    // AI Assistant (khusus Admin & Pemilik)
    Route::middleware('role:admin,pemilik')->group(function () {
        Route::post('/ai/chat', [AIController::class, 'chat'])
            ->name('ai.chat');

        Route::get('/ai/history', [AIController::class, 'history'])
            ->name('ai.history');

        Route::delete('/ai/history', [AIController::class, 'clearHistory'])
            ->name('ai.history.clear');

        Route::delete('/ai/history/{sessionId}', [AIController::class, 'deleteSession'])
            ->name('ai.history.delete-session');
    });


    // Profile
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

// ==================== ADMIN & SUPER ADMIN ====================
Route::middleware(['auth', 'role:super_admin,admin'])->prefix('admin')->name('admin.')->group(function () {
    // Profile Management (Super Admin & Admin)
    Route::get('/profil', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password');

    // User & Role Management (Super Admin ONLY)
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('user', UserController::class)->except(['show']);
    });

    // Fitur Operasional Apotek (Admin ONLY — Super Admin diblokir)
    Route::middleware('role:admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Obat Management
        Route::get('obat/export/excel', [ObatController::class, 'exportExcel'])->name('obat.export');
        Route::post('obat/import', [ObatController::class, 'importExcel'])->name('obat.import');
        Route::resource('obat', ObatController::class);

        // Kategori Management
        Route::resource('kategori', KategoriController::class)->except(['show']);

        // Order Management (Catatan Penjualan)
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');

        // Laporan Penjualan & Stok
        Route::get('/reports/sales', [\App\Http\Controllers\Admin\ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/stock', [\App\Http\Controllers\Admin\ReportController::class, 'stock'])->name('reports.stock');

        // Penambahan Stok
        Route::get('/stock', [\App\Http\Controllers\Admin\StockController::class, 'index'])->name('stock.index');
        Route::post('/stock', [\App\Http\Controllers\Admin\StockController::class, 'add'])->name('stock.add');
    });
});

// ==================== PEMILIK ====================
Route::middleware(['auth', 'role:pemilik'])->prefix('pemilik')->name('pemilik.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Pemilik\DashboardController::class, 'index'])->name('dashboard');

    // Profile Management
    Route::get('/profil', [\App\Http\Controllers\Pemilik\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [\App\Http\Controllers\Pemilik\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [\App\Http\Controllers\Pemilik\ProfileController::class, 'updatePassword'])->name('profile.password');

    // Obat Approval & View
    Route::get('/obat', [\App\Http\Controllers\Pemilik\ObatController::class, 'index'])->name('obat.index');
    Route::put('/obat/approve-all', [\App\Http\Controllers\Pemilik\ObatController::class, 'approveAll'])->name('obat.approveAll');
    Route::put('/obat/{obat}/approve', [\App\Http\Controllers\Pemilik\ObatController::class, 'approve'])->name('obat.approve');
    Route::put('/obat/{obat}/reject', [\App\Http\Controllers\Pemilik\ObatController::class, 'reject'])->name('obat.reject');

    // Stock Addition Approval
    Route::get('/stock-additions', [\App\Http\Controllers\Pemilik\StockAdditionController::class, 'index'])->name('stock.index');
    Route::put('/stock-additions/approve-all', [\App\Http\Controllers\Pemilik\StockAdditionController::class, 'approveAll'])->name('stock.approveAll');
    Route::put('/stock-additions/{stockAddition}/approve', [\App\Http\Controllers\Pemilik\StockAdditionController::class, 'approve'])->name('stock.approve');
    Route::put('/stock-additions/{stockAddition}/reject', [\App\Http\Controllers\Pemilik\StockAdditionController::class, 'reject'])->name('stock.reject');

    // Laporan Pendapatan
    Route::get('/reports/income', [\App\Http\Controllers\Pemilik\ReportController::class, 'income'])->name('reports.income');
    Route::get('/reports/stock', [\App\Http\Controllers\Pemilik\ReportController::class, 'stock'])->name('reports.stock');

    // User Management
    Route::resource('user', \App\Http\Controllers\Pemilik\UserController::class)->except(['show']);
});

