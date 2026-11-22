<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\StockAddition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            return redirect()->back()->with('error', 'Status pengajuan stok ini sudah diproses.');
        }

        if (!$stockAddition->obat) {
            return redirect()->back()->with('error', 'Obat terkait tidak ditemukan.');
        }

        $result = DB::transaction(function () use ($stockAddition) {
            $obat = Obat::lockForUpdate()->find($stockAddition->obat_id);
            if (!$obat) {
                return ['ok' => false, 'message' => 'Obat terkait tidak ditemukan.'];
            }

            $newStock = (int) $obat->stok + (int) $stockAddition->jumlah;
            if ($newStock < 0) {
                return [
                    'ok' => false,
                    'message' => "Stok {$obat->nama_obat} saat ini ({$obat->stok} {$obat->satuan}) tidak mencukupi untuk pengurangan " . abs($stockAddition->jumlah) . " {$obat->satuan}.",
                ];
            }

            $obat->update(['stok' => $newStock]);
            $stockAddition->update(['status' => 'approved']);

            return ['ok' => true, 'is_reduction' => $stockAddition->jumlah < 0];
        });

        if (!$result['ok']) {
            return redirect()->back()->with('error', $result['message']);
        }

        $msg = !empty($result['is_reduction'])
            ? 'Pengurangan stok berhasil dikonfirmasi dan stok obat telah diperbarui.'
            : 'Penambahan stok berhasil dikonfirmasi dan stok obat telah diperbarui.';

        return redirect()->back()->with('success', $msg);
    }

    public function reject(StockAddition $stockAddition)
    {
        if ($stockAddition->status !== 'pending') {
            return redirect()->back()->with('error', 'Status pengajuan stok ini sudah diproses.');
        }

        $stockAddition->update(['status' => 'rejected']);

        return redirect()->back()->with('success', 'Pengajuan penyesuaian stok telah ditolak.');
    }

    public function approveAll()
    {
        $items = StockAddition::where('status', 'pending')->with('obat')->orderBy('id')->get();

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada pengajuan stok yang harus dikonfirmasi.');
        }

        $approvedCount = 0;
        $skippedItems = [];

        DB::transaction(function () use ($items, &$approvedCount, &$skippedItems) {
            foreach ($items as $item) {
                if (!$item->obat) {
                    continue;
                }

                $obat = Obat::lockForUpdate()->find($item->obat_id);
                if (!$obat) {
                    continue;
                }

                $newStock = (int) $obat->stok + (int) $item->jumlah;
                if ($newStock < 0) {
                    $skippedItems[] = $obat->nama_obat;
                    continue;
                }

                $obat->update(['stok' => $newStock]);
                $item->update(['status' => 'approved']);
                $approvedCount++;
            }
        });

        if ($approvedCount === 0 && !empty($skippedItems)) {
            return redirect()->back()->with('error', 'Pengajuan pengurangan stok gagal dikonfirmasi karena stok tidak mencukupi: ' . implode(', ', $skippedItems));
        }

        if (!empty($skippedItems)) {
            return redirect()->back()->with('success', "{$approvedCount} pengajuan berhasil dikonfirmasi. Sebagian dilewati karena stok tidak cukup: " . implode(', ', $skippedItems));
        }

        return redirect()->back()->with('success', 'Semua pengajuan penyesuaian stok berhasil dikonfirmasi dan stok diperbarui.');
    }
}
