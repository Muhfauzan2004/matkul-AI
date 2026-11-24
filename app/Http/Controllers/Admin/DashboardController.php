<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\Order;
use App\Models\Kategori;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalObat       = Obat::where('is_approved', true)->count();
        $obatAktif       = Obat::where('is_active', true)->where('is_approved', true)->count();
        $totalKategori   = Kategori::count();
        $totalOrder      = Order::count();
        $pendingOrders   = Order::where('status', 'pending')->count();
        $pendingApproval = Obat::where('is_approved', false)->count();
        $totalRevenue    = Order::where('status', 'selesai')->sum('total_harga');
        $todayRevenue    = Order::where('status', 'selesai')
                             ->whereDate('created_at', Carbon::today())
                             ->sum('total_harga');
        $weeklyRevenue   = Order::where('status', 'selesai')
                             ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                             ->sum('total_harga');
        $criticalStock   = Obat::where('stok', '<', 10)->where('is_approved', true)->count();

        // Monthly comparison data
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

        // Sales per day for last 7 days
        $salesWeekly = collect(range(6, 0))->map(function ($i) {
            $date = Carbon::now()->subDays($i);
            return [
                'label' => $date->locale('id')->dayName,
                'value' => (int) Order::where('status', 'selesai')
                             ->whereDate('created_at', $date->toDateString())
                             ->sum('total_harga'),
            ];
        });

        $recentOrders = Order::with('user')->latest()->take(5)->get();
        $lowStockObat = Obat::where('stok', '<', 20)
                            ->where('is_approved', true)
                            ->orderBy('stok', 'asc')
                            ->take(5)
                            ->get();

        return view('admin.dashboard', compact(
            'totalObat', 'obatAktif', 'totalKategori', 'totalOrder',
            'pendingOrders', 'pendingApproval', 'criticalStock',
            'totalRevenue', 'todayRevenue', 'weeklyRevenue',
            'salesWeekly', 'recentOrders', 'lowStockObat',
            'thisMonthRevenue', 'lastMonthRevenue', 'percentageChange'
        ));
    }
}
