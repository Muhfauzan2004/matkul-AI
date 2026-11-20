<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\StockAddition;
use Illuminate\Http\Request;

class StockAdditionController extends Controller
{
    public function index()
    {
        $additions = StockAddition::with('obat.kategori')
            ->latest()
            ->paginate(10);
            
        return view('pemilik.stock_addition.index', compact('additions'));
    }

    public function approve(StockAddition $stockAddition)
    {
        if ($stockAddition->status !== 'pending') {
            return redirect()->back()->with('error', 'Status penambahan stok ini sudah diproses.');
        }

        if (!$stockAddition->obat) {
            return redirect()->back()->with('error', 'Obat terkait tidak ditemukan.');
        }

        // Increment stock
        $stockAddition->obat->increment('stok', $stockAddition->jumlah);
        
        // Update status
        $stockAddition->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Penambahan stok berhasil dikonfirmasi dan stok obat telah diperbarui.');
    }

    public function reject(StockAddition $stockAddition)
    {
        if ($stockAddition->status !== 'pending') {
            return redirect()->back()->with('error', 'Status penambahan stok ini sudah diproses.');
        }

        if (!$stockAddition->obat) {
            return redirect()->back()->with('error', 'Obat terkait tidak ditemukan.');
        }

        $stockAddition->update(['status' => 'rejected']);

        return redirect()->back()->with('success', 'Penambahan stok ditolak.');
    }

    public function approveAll()
    {
        $pending = StockAddition::where('status', 'pending')->with('obat');

        if (!$pending->exists()) {
            return redirect()->back()->with('error', 'Tidak ada pengajuan penambahan stok yang harus dikonfirmasi.');
        }

        $items = $pending->get();
        foreach ($items as $item) {
            if ($item->obat) {
                $item->obat->increment('stok', $item->jumlah);
            }
            $item->update(['status' => 'approved']);
        }

        return redirect()->back()->with('success', 'Semua pengajuan penambahan stok berhasil dikonfirmasi dan stok diperbarui.');
    }
}
