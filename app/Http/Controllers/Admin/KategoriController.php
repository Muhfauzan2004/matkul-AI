<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KategoriController extends Controller
{
    public function index(Request $request)
    {
        $query = Kategori::withCount('obat')->orderBy('nama_kategori');

        if ($request->filled('search')) {
            $this->applySearchFilter($query, $request->search, ['nama_kategori']);
        }

        $kategoris = $query->paginate(15)->withQueryString();
        return view('admin.kategori.index', compact('kategoris'));
    }

    public function create()
    {
        return view('admin.kategori.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['nama_kategori']);

        Kategori::create($validated);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Kategori $kategori)
    {
        return view('admin.kategori.edit', compact('kategori'));
    }

    public function update(Request $request, Kategori $kategori)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['nama_kategori']);

        $kategori->update($validated);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori)
    {
        if ($kategori->obat()->count() > 0) {
            return back()->with('error', 'Kategori ini memiliki obat, hapus obat terlebih dahulu.');
        }

        $kategori->delete();

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
