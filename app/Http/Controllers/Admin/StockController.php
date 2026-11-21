<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Obat::with('kategori')->where('is_approved', true);

        if ($request->filled('search')) {
            $this->applySearchFilter($query, $request->search, ['nama_obat', 'kode']);
        }

        // Fetch active & approved medicines for stock additions
        $obatsSelection = Obat::where('is_active', true)->where('is_approved', true)->orderBy('nama_obat')->get();

        // Paginate only approved medicines to view their stock statuses
        $obats = $query->orderBy('stok', 'asc')->paginate(10)->withQueryString();

        return view('admin.stock.index', compact('obats', 'obatsSelection'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|exists:obat,id',
            'jumlah' => 'required|integer|min:1',
            'catatan' => 'nullable|string|max:255',
        ]);

        $obat = Obat::findOrFail($request->obat_id);
        
        \App\Models\StockAddition::create([
            'obat_id' => $obat->id,
            'jumlah' => $request->jumlah,
            'catatan' => $request->catatan,
            'status' => 'pending'
        ]);

        return redirect()->route('admin.stock.index')->with('success', "Permintaan penambahan stok obat {$obat->nama_obat} sebanyak {$request->jumlah} {$obat->satuan} berhasil diajukan dan sedang menunggu konfirmasi Pemilik.");
    }
}
