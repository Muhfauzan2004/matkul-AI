<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\Kategori;
use Illuminate\Http\Request;

class ObatController extends Controller
{
    public function index(Request $request)
    {
        $query = Obat::with('kategori');

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('is_approved', false);
            } elseif ($request->status === 'approved') {
                $query->where('is_approved', true);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_obat', 'like', '%' . $search . '%')
                  ->orWhere('kode', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        $obats = $query->orderBy('is_approved', 'asc')->orderBy('nama_obat')->paginate(10);
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('pemilik.obat.index', compact('obats', 'kategoris'));
    }

    public function approve(Obat $obat)
    {
        if ($obat->is_approved) {
            return redirect()->route('pemilik.obat.index')->with('error', 'Obat sudah disetujui.');
        }

        $obat->update([
            'is_approved' => true,
            'is_active' => true
        ]);

        return redirect()->route('pemilik.obat.index')->with('success', 'Obat berhasil dikonfirmasi dan diaktifkan.');
    }

    public function reject(Obat $obat)
    {
        if ($obat->is_approved) {
            return redirect()->route('pemilik.obat.index')->with('error', 'Obat sudah disetujui, tidak dapat ditolak.');
        }

        if ($obat->gambar) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($obat->gambar);
        }
        $obat->delete();

        return redirect()->route('pemilik.obat.index')->with('success', 'Usulan obat baru berhasil ditolak.');
    }

    public function approveAll()
    {
        $pending = Obat::where('is_approved', false);

        if (!$pending->exists()) {
            return redirect()->back()->with('error', 'Tidak ada pengajuan obat baru yang perlu disetujui.');
        }

        $pending->update([
            'is_approved' => true,
            'is_active' => true
        ]);

        return redirect()->back()->with('success', 'Semua usulan obat baru berhasil disetujui dan diaktifkan.');
    }
}

