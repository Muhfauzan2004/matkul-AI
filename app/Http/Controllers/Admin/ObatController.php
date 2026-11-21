<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ObatController extends Controller
{
    public function index(Request $request)
    {
        $query = Obat::with('kategori');

        if ($request->filled('search')) {
            $this->applySearchFilter($query, $request->search, ['nama_obat', 'kode']);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori_id', $request->kategori);
        }

        if ($request->filled('status')) {
            if ($request->status == 'aktif') {
                $query->where('is_active', true)->where('is_approved', true);
            } elseif ($request->status == 'pending') {
                $query->where('is_approved', false);
            } elseif ($request->status == 'nonaktif') {
                $query->where('is_active', false)->where('is_approved', true);
            }
        }

        // Sorting
        $sortBy  = $request->input('sort_by');
        $sortDir = in_array($request->input('sort_dir'), ['asc', 'desc']) ? $request->input('sort_dir') : 'asc';

        if ($sortBy === 'stok') {
            $query->orderBy('stok', $sortDir);
        } else {
            $query->orderBy('nama_obat', 'asc');
        }

        $obats = $query->paginate(10)->withQueryString();
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('admin.obat.index', compact('obats', 'kategoris'));

    }

    public function create()
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        return view('admin.obat.create', compact('kategoris'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_obat' => 'required|string|max:100',
            'kategori_id' => 'required|exists:kategori,id',
            'stok' => 'required|integer|min:0',
            'satuan' => 'nullable|string|max:20',
            'harga_umum' => 'required|integer|min:0',
            'harga_barang' => 'required|numeric|min:0',
            'harga_jual_bpjs' => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
        ]);

        $validated['kode'] = Obat::generateKodeObat();

        if ($request->hasFile('gambar')) {
            $validated['gambar'] = $request->file('gambar')->store('obat', 'public');
        }

        $validated['is_approved'] = auth()->user()->isSuperAdmin() ? true : false;
        
        if (!$validated['is_approved']) {
            $validated['is_active'] = false;
        } else {
            $validated['is_active'] = $request->has('is_active');
        }

        Obat::create($validated);

        return redirect()->route('admin.obat.index')->with('success', 'Obat berhasil ditambahkan.');
    }

    public function show(Obat $obat)
    {
        if (!$obat->is_approved) {
            return redirect()->route('admin.obat.index')->with('error', 'Obat yang masih pending tidak dapat dilihat.');
        }
        $obat->load('kategori');
        return view('admin.obat.show', compact('obat'));
    }

    public function edit(Obat $obat)
    {
        if (!$obat->is_approved) {
            return redirect()->route('admin.obat.index')->with('error', 'Obat yang masih pending tidak dapat diubah.');
        }
        if (!$obat->is_active) {
            return redirect()->route('admin.obat.index')->with('error', 'Obat yang tidak aktif tidak dapat diubah.');
        }
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        return view('admin.obat.edit', compact('obat', 'kategoris'));
    }

    public function update(Request $request, Obat $obat)
    {
        if (!$obat->is_approved) {
            return redirect()->route('admin.obat.index')->with('error', 'Obat yang masih pending tidak dapat diubah.');
        }
        if (!$obat->is_active) {
            return redirect()->route('admin.obat.index')->with('error', 'Obat yang tidak aktif tidak dapat diubah.');
        }

        $validated = $request->validate([
            'nama_obat' => 'required|string|max:100',
            'kategori_id' => 'required|exists:kategori,id',
            'stok' => 'required|integer|min:0',
            'satuan' => 'nullable|string|max:20',
            'harga_umum' => 'required|integer|min:0',
            'harga_barang' => 'required|numeric|min:0',
            'harga_jual_bpjs' => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('gambar')) {
            if ($obat->gambar) {
                Storage::disk('public')->delete($obat->gambar);
            }
            $validated['gambar'] = $request->file('gambar')->store('obat', 'public');
        }

        if (!$obat->is_approved) {
            $validated['is_active'] = false;
        } else {
            $validated['is_active'] = $request->has('is_active');
        }
        // If an admin updates an obat, it might need re-approval, but let's just keep its current status
        // unless we want to reset it. For now, we don't change is_approved.

        $obat->update($validated);

        return redirect()->route('admin.obat.index')->with('success', 'Obat berhasil diperbarui.');
    }

    public function destroy(Obat $obat)
    {
        if (!$obat->is_approved) {
            return redirect()->route('admin.obat.index')->with('error', 'Obat yang masih pending tidak dapat dihapus.');
        }
        if (!$obat->is_active) {
            return redirect()->route('admin.obat.index')->with('error', 'Obat yang tidak aktif tidak dapat dihapus.');
        }

        if ($obat->gambar) {
            Storage::disk('public')->delete($obat->gambar);
        }
        $obat->delete();

        return redirect()->route('admin.obat.index')->with('success', 'Obat berhasil dihapus.');
    }

    public function exportExcel(Request $request)
    {
        $query = Obat::with('kategori');

        if ($request->filled('search')) {
            $this->applySearchFilter($query, $request->search, ['nama_obat', 'kode']);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori_id', $request->kategori);
        }

        if ($request->filled('status')) {
            if ($request->status == 'aktif') {
                $query->where('is_active', true)->where('is_approved', true);
            } elseif ($request->status == 'pending') {
                $query->where('is_approved', false);
            } elseif ($request->status == 'nonaktif') {
                $query->where('is_active', false)->where('is_approved', true);
            }
        }

        // Sorting
        $sortBy  = $request->input('sort_by');
        $sortDir = in_array($request->input('sort_dir'), ['asc', 'desc']) ? $request->input('sort_dir') : 'asc';

        if ($sortBy === 'stok') {
            $query->orderBy('stok', $sortDir);
        } else {
            $query->orderBy('nama_obat', 'asc');
        }

        $obats = $query->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title block
        $sheet->setCellValue('A1', 'DATA DAFTAR OBAT - APOTEK SULTHAN ELIDAS');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $sheet->setCellValue('A2', 'Tanggal Unduh: ' . now()->format('d-m-Y H:i'));
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        // Table headers
        $sheet->setCellValue('A4', 'No');
        $sheet->setCellValue('B4', 'Kode Obat');
        $sheet->setCellValue('C4', 'Nama Obat');
        $sheet->setCellValue('D4', 'Kategori');
        $sheet->setCellValue('E4', 'Stok');
        $sheet->setCellValue('F4', 'Satuan');
        $sheet->setCellValue('G4', 'Harga Beli');
        $sheet->setCellValue('H4', 'Harga Umum');
        $sheet->setCellValue('I4', 'Harga Jual BPJS');
        
        $sheet->getStyle('A4:I4')->getFont()->setBold(true);
        $sheet->getStyle('A4:I4')->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $rowNum = 5;
        foreach ($obats as $index => $obat) {
            $sheet->setCellValue('A' . $rowNum, $index + 1);
            $sheet->setCellValue('B' . $rowNum, $obat->kode);
            $sheet->setCellValue('C' . $rowNum, $obat->nama_obat);
            $sheet->setCellValue('D' . $rowNum, $obat->kategori ? $obat->kategori->nama_kategori : '-');
            $sheet->setCellValue('E' . $rowNum, $obat->stok);
            $sheet->setCellValue('F' . $rowNum, $obat->satuan);
            $sheet->setCellValue('G' . $rowNum, $obat->harga_barang);
            $sheet->setCellValue('H' . $rowNum, $obat->harga_umum);
            $sheet->setCellValue('I' . $rowNum, $obat->harga_jual_bpjs);
            
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode('Rp #,##0');
            $sheet->getStyle('H' . $rowNum)->getNumberFormat()->setFormatCode('Rp #,##0');
            $sheet->getStyle('I' . $rowNum)->getNumberFormat()->setFormatCode('Rp #,##0');
            $rowNum++;
        }

        // Auto size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Daftar_Obat_' . now()->format('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file_excel.required' => 'File Excel wajib diunggah.',
            'file_excel.mimes' => 'Format file harus berupa .xlsx, .xls, atau .csv',
            'file_excel.max' => 'Ukuran file maksimal adalah 5MB.',
        ]);

        try {
            $file = $request->file('file_excel');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (count($rows) <= 1) {
                return back()->with('error', 'File Excel kosong atau tidak memiliki baris data.');
            }

            // Extract header and map indices
            $header = array_map(function($val) {
                return trim(strtolower($val));
            }, $rows[0]);

            $indices = [
                'nama_obat' => array_search('nama obat', $header) !== false ? array_search('nama obat', $header) : (array_search('nama', $header) !== false ? array_search('nama', $header) : -1),
                'kategori' => array_search('kategori', $header) !== false ? array_search('kategori', $header) : (array_search('category', $header) !== false ? array_search('category', $header) : -1),
                'stok' => array_search('stok', $header) !== false ? array_search('stok', $header) : (array_search('stok awal', $header) !== false ? array_search('stok awal', $header) : (array_search('stock', $header) !== false ? array_search('stock', $header) : -1)),
                'satuan' => array_search('satuan', $header) !== false ? array_search('satuan', $header) : (array_search('unit', $header) !== false ? array_search('unit', $header) : -1),
                'harga_umum' => array_search('harga umum', $header) !== false ? array_search('harga umum', $header) : (array_search('harga jual', $header) !== false ? array_search('harga jual', $header) : (array_search('harga_umum', $header) !== false ? array_search('harga_umum', $header) : -1)),
                'harga_barang' => array_search('harga barang', $header) !== false ? array_search('harga barang', $header) : (array_search('harga beli', $header) !== false ? array_search('harga beli', $header) : (array_search('harga pokok', $header) !== false ? array_search('harga pokok', $header) : (array_search('harga_barang', $header) !== false ? array_search('harga_barang', $header) : -1))),
                'harga_jual_bpjs' => array_search('harga jual bpjs', $header) !== false ? array_search('harga jual bpjs', $header) : (array_search('harga bpjs', $header) !== false ? array_search('harga bpjs', $header) : (array_search('harga_jual_bpjs', $header) !== false ? array_search('harga_jual_bpjs', $header) : -1)),
                'deskripsi' => array_search('deskripsi', $header) !== false ? array_search('deskripsi', $header) : (array_search('description', $header) !== false ? array_search('description', $header) : -1),
            ];

            // Validate that we have the essential columns
            if ($indices['nama_obat'] === -1) {
                return back()->with('error', 'Kolom "Nama Obat" atau "Nama" tidak ditemukan di file Excel.');
            }
            if ($indices['kategori'] === -1) {
                return back()->with('error', 'Kolom "Kategori" atau "Category" tidak ditemukan di file Excel.');
            }
            if ($indices['harga_umum'] === -1 || $indices['harga_barang'] === -1 || $indices['harga_jual_bpjs'] === -1) {
                return back()->with('error', 'Kolom harga (Harga Umum, Harga Barang/Beli, Harga Jual BPJS) harus ada di file Excel.');
            }

            $successCount = 0;
            $skippedCount = 0;

            // Start database transaction to ensure safety
            \DB::beginTransaction();

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                // Skip completely empty rows
                if (empty($row) || !isset($row[$indices['nama_obat']]) || trim($row[$indices['nama_obat']]) === '') {
                    $skippedCount++;
                    continue;
                }

                $namaObat = trim($row[$indices['nama_obat']]);
                $categoryName = isset($row[$indices['kategori']]) ? trim($row[$indices['kategori']]) : 'Umum';
                
                // Default to 'Umum' if category name is empty
                if (empty($categoryName)) {
                    $categoryName = 'Umum';
                }

                // Look up or dynamically create Kategori
                $kategori = \App\Models\Kategori::where('nama_kategori', $this->likeOperator(), $categoryName)->first();
                if (!$kategori) {
                    $kategori = \App\Models\Kategori::create([
                        'nama_kategori' => $categoryName,
                        'slug' => \Illuminate\Support\Str::slug($categoryName),
                        'deskripsi' => 'Dibuat otomatis via import Excel'
                    ]);
                }

                $stok = isset($row[$indices['stok']]) ? (int)$row[$indices['stok']] : 0;
                $satuan = isset($row[$indices['satuan']]) ? trim($row[$indices['satuan']]) : 'Pcs';
                if (empty($satuan)) {
                    $satuan = 'Pcs';
                }

                $hargaUmum = isset($row[$indices['harga_umum']]) ? (int)preg_replace('/[^0-9]/', '', $row[$indices['harga_umum']]) : 0;
                $hargaBarang = isset($row[$indices['harga_barang']]) ? (float)preg_replace('/[^0-9.]/', '', $row[$indices['harga_barang']]) : 0;
                $hargaBpjs = isset($row[$indices['harga_jual_bpjs']]) ? (int)preg_replace('/[^0-9]/', '', $row[$indices['harga_jual_bpjs']]) : 0;
                $deskripsi = $indices['deskripsi'] !== -1 && isset($row[$indices['deskripsi']]) ? trim($row[$indices['deskripsi']]) : null;

                // Generate new kode obat
                $kode = \App\Models\Obat::generateKodeObat();

                // Check if this medicine name already exists to avoid duplication
                $existingObat = \App\Models\Obat::where('nama_obat', $namaObat)
                    ->where('kategori_id', $kategori->id)
                    ->first();

                if ($existingObat) {
                    // Update stock and prices
                    $existingObat->update([
                        'stok' => $existingObat->stok + $stok,
                        'harga_umum' => $hargaUmum,
                        'harga_barang' => $hargaBarang,
                        'harga_jual_bpjs' => $hargaBpjs,
                        'deskripsi' => $deskripsi ?? $existingObat->deskripsi,
                        'satuan' => $satuan,
                    ]);
                } else {
                    // Create new obat
                    $isApproved = auth()->user()->isSuperAdmin() ? true : false;
                    \App\Models\Obat::create([
                        'kode' => $kode,
                        'nama_obat' => $namaObat,
                        'kategori_id' => $kategori->id,
                        'stok' => $stok,
                        'satuan' => $satuan,
                        'harga_umum' => $hargaUmum,
                        'harga_barang' => $hargaBarang,
                        'harga_jual_bpjs' => $hargaBpjs,
                        'deskripsi' => $deskripsi,
                        'is_active' => $isApproved,
                        'is_approved' => $isApproved,
                    ]);
                }

                $successCount++;
            }

            \DB::commit();

            return redirect()->route('admin.obat.index')->with('success', "Import Excel sukses! Berhasil memproses {$successCount} obat (diabaikan/kosong: {$skippedCount}).");

        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        }
    }
}
