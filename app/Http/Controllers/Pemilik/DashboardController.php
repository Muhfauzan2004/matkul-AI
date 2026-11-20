<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\StockAddition;
use App\Models\User;
use App\Models\Order;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalObat         = Obat::count();
        $totalObatAktif    = Obat::where('is_active', true)->where('is_approved', true)->count();
        $totalObatNonaktif = Obat::where('is_active', false)->where('is_approved', true)->count();
        $obatPending       = Obat::where('is_approved', false)->count();

        $totalAdmin        = User::where('role', 'admin')->count();

        $stockPending      = StockAddition::where('status', 'pending')->count();
        $stockApproved     = StockAddition::where('status', 'approved')->count();
        $totalStockApproved = StockAddition::count();

        // 1. Monthly revenue comparison
        $thisMonthRevenue = Order::where('status', 'selesai')
                             ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                             ->sum('total_harga');
        $lastMonthRevenue = Order::where('status', 'selesai')
                             ->whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()])
                             ->sum('total_harga');
        
        $diff = $thisMonthRevenue - $lastMonthRevenue;
        if ($lastMonthRevenue > 0) {
            $percentageChange = ($diff / $lastMonthRevenue) * 100;
        } else {
            $percentageChange = $thisMonthRevenue > 0 ? 100 : 0;
        }

        // 2. Sales per day for last 7 days
        $salesWeekly = collect(range(6, 0))->map(function ($i) {
            $date = Carbon::now()->subDays($i);
            return [
                'label' => $date->locale('id')->dayName,
                'value' => (int) Order::where('status', 'selesai')
                             ->whereDate('created_at', $date->toDateString())
                             ->sum('total_harga'),
            ];
        });

        // 3. Low stock alert (stok <= 10)
        $lowStockObat = Obat::where('stok', '<=', 10)
                            ->where('is_approved', true)
                            ->orderBy('stok', 'asc')
                            ->take(5)
                            ->get();

        $criticalStock = Obat::where('stok', '<', 10)->where('is_approved', true)->count();
        $recentOrders = Order::with('user')->latest()->take(5)->get();

        return view('pemilik.dashboard', compact(
            'totalObat',
            'totalObatAktif',
            'totalObatNonaktif',
            'obatPending',
            'totalAdmin',
            'stockPending',
            'stockApproved',
            'totalStockApproved',
            'thisMonthRevenue',
            'lastMonthRevenue',
            'percentageChange',
            'salesWeekly',
            'lowStockObat',
            'criticalStock',
            'recentOrders'
        ));
    }
}

