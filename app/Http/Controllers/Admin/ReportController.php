<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $orders = Order::with('items.obat')
            ->where('status', 'selesai')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->latest()
            ->get();

        $totalPendapatan = $orders->sum('total_harga');
        $totalTransaksi = $orders->count();
        $totalObatTerjual = $orders->sum(function ($order) {
            return $order->items->sum('jumlah');
        });

        return view('admin.report.sales', compact('orders', 'startDate', 'endDate', 'totalPendapatan', 'totalTransaksi', 'totalObatTerjual'));
    }

    public function stock(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $newObatQuery = \App\Models\Obat::with('kategori')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $stockAdditionsQuery = \App\Models\StockAddition::with('obat.kategori')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $totalNewObat = $newObatQuery->count();
        $totalRestockAdditions = $stockAdditionsQuery->count();
        $totalQtyRestocked = $stockAdditionsQuery->sum('jumlah');

        $newObat = $newObatQuery->latest()->paginate(10, ['*'], 'new_page')->withQueryString();
        $stockAdditions = $stockAdditionsQuery->latest()->paginate(10, ['*'], 'stock_page')->withQueryString();

        return view('admin.report.stock', compact(
            'newObat',
            'stockAdditions',
            'startDate',
            'endDate',
            'totalNewObat',
            'totalRestockAdditions',
            'totalQtyRestocked'
        ));
    }
}
