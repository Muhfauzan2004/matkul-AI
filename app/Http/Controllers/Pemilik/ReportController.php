<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function income(Request $request)
    {
        // Default to current month range
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        // Fetch completed orders within range
        $orders = Order::with(['items.obat', 'user'])
            ->where('status', 'selesai')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->latest()
            ->get();

        // Calculate general KPI statistics
        $totalRevenue = $orders->sum('total_harga');
        $totalTransactions = $orders->count();
        $averageTransaction = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;
        
        // Sum total quantities of medicines sold
        $totalItemsSold = $orders->sum(function ($order) {
            return $order->items->sum('jumlah');
        });

        // Payment Method breakdown
        $paymentBreakdown = [
            'cash' => $orders->whereIn('metode_pembayaran', ['cash', 'tunai'])->count(),
            'transfer' => $orders->whereIn('metode_pembayaran', ['transfer', 'transfer_bank', 'qris'])->count(),
        ];

        // Prepare continuous dates for the timeline chart
        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            (new \DateTime($endDate))->modify('+1 day')
        );

        $chartLabels = [];
        $chartData = [];
        $dailyRevenue = [];

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $dailyRevenue[$formattedDate] = 0;
            // Short label for chart, e.g. "21 Mei"
            $chartLabels[] = Carbon::parse($formattedDate)->translatedFormat('d M');
        }

        // Fetch daily total revenues from Database
        $dbDaily = Order::where('status', 'selesai')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('DATE(created_at) as date, SUM(total_harga) as total')
            ->groupBy('date')
            ->get();

        foreach ($dbDaily as $row) {
            if (isset($dailyRevenue[$row->date])) {
                $dailyRevenue[$row->date] = (float)$row->total;
            }
        }

        // Output data sequence matching the continuous labels
        foreach ($dailyRevenue as $date => $amount) {
            $chartData[] = $amount;
        }

        return view('pemilik.report.income', compact(
            'orders',
            'startDate',
            'endDate',
            'totalRevenue',
            'totalTransactions',
            'averageTransaction',
            'totalItemsSold',
            'paymentBreakdown',
            'chartLabels',
            'chartData'
        ));
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

        return view('pemilik.report.stock', compact(
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
