<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\Kategori;
use Illuminate\Http\Request;

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

    public function create(Request $request)
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        if ($request->boolean('modal') || $request->ajax()) {
            return view('admin.obat._form', ['kategoris' => $kategoris, 'isModal' => true]);
        }

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
            'is_active' => 'boolean',
        ]);

        $validated['kode'] = Obat::generateKodeObat();

        $validated['is_approved'] = auth()->user()->isSuperAdmin() ? true : false;
        
        if (!$validated['is_approved']) {
            $validated['is_active'] = false;
        } else {
            $validated['is_active'] = $request->has('is_active');
        }

        Obat::create($validated);

        return redirect()->route('admin.obat.index')->with('success', 'Obat berhasil ditambahkan.');
    }

    public function show(Request $request, Obat $obat)
    {
        if (!$obat->is_approved) {
            if ($request->boolean('modal') || $request->ajax()) {
                return response('<div class="alert alert-error">Obat yang masih pending tidak dapat dilihat.</div>', 422);
            }
            return redirect()->route('admin.obat.index')->with('error', 'Obat yang masih pending tidak dapat dilihat.');
        }
        $obat->load('kategori');

        if ($request->boolean('modal') || $request->ajax()) {
            return view('admin.obat._detail', compact('obat'));
        }

        return view('admin.obat.show', compact('obat'));
    }

    public function edit(Request $request, Obat $obat)
    {
        if (!$obat->is_approved) {
            if ($request->boolean('modal') || $request->ajax()) {
                return response('<div class="alert alert-error">Obat yang masih pending tidak dapat diubah.</div>', 422);
            }
            return redirect()->route('admin.obat.index')->with('error', 'Obat yang masih pending tidak dapat diubah.');
        }
        if (!$obat->is_active) {
            if ($request->boolean('modal') || $request->ajax()) {
                return response('<div class="alert alert-error">Obat yang tidak aktif tidak dapat diubah.</div>', 422);
            }
            return redirect()->route('admin.obat.index')->with('error', 'Obat yang tidak aktif tidak dapat diubah.');
        }
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        if ($request->boolean('modal') || $request->ajax()) {
            return view('admin.obat._form', ['obat' => $obat, 'kategoris' => $kategoris, 'isModal' => true]);
        }

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
            'is_active' => 'boolean',
        ]);

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

        // Fallback otomatis bila PhpSpreadsheet belum terpasang (misal PHP 8.2 / XAMPP tanpa vendor baru)
        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            if (class_exists(\ZipArchive::class)) {
                return $this->exportNativeXlsx($obats);
            }

            return $this->exportSpreadsheetXml($obats);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title block
        $sheet->setCellValue('A1', 'DATA DAFTAR OBAT - APOTEK ITU TOKO OBAT');
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
            if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();
            } else {
                $rows = $this->parseSpreadsheetFallback($file);
            }

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

    /**
     * Native PHP .xlsx generator (OpenXML) tanpa library eksternal agar berjalan di PHP 8.2+.
     */
    private function exportNativeXlsx($obats)
    {
        $xmlEsc = fn($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $strCell = fn(string $ref, string $val, int $style = 0) =>
            "<c r=\"{$ref}\" t=\"inlineStr\" s=\"{$style}\"><is><t>" . $xmlEsc($val) . '</t></is></c>';
        $numCell = fn(string $ref, $val, int $style = 0) =>
            "<c r=\"{$ref}\" s=\"{$style}\"><v>" . (is_numeric($val) ? $val : 0) . '</v></c>';

        $sheetRows = [];
        $sheetRows[] = '<row r="1">' . $strCell('A1', 'DATA DAFTAR OBAT - APOTEK ITU TOKO OBAT', 1) . '</row>';
        $sheetRows[] = '<row r="2">' . $strCell('A2', 'Tanggal Unduh: ' . now()->format('d-m-Y H:i'), 0) . '</row>';

        $headers = ['No', 'Kode Obat', 'Nama Obat', 'Kategori', 'Stok', 'Satuan', 'Harga Beli', 'Harga Umum', 'Harga Jual BPJS'];
        $headerCells = '';
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $headerCells .= $strCell($col . '4', $h, 1);
        }
        $sheetRows[] = '<row r="4">' . $headerCells . '</row>';

        $rowNum = 5;
        foreach ($obats as $index => $obat) {
            $cells = $numCell('A' . $rowNum, $index + 1, 0)
                . $strCell('B' . $rowNum, (string) $obat->kode, 0)
                . $strCell('C' . $rowNum, (string) $obat->nama_obat, 0)
                . $strCell('D' . $rowNum, (string) ($obat->kategori ? $obat->kategori->nama_kategori : '-'), 0)
                . $numCell('E' . $rowNum, (int) $obat->stok, 0)
                . $strCell('F' . $rowNum, (string) ($obat->satuan ?? ''), 0)
                . $numCell('G' . $rowNum, (float) $obat->harga_barang, 2)
                . $numCell('H' . $rowNum, (int) $obat->harga_umum, 2)
                . $numCell('I' . $rowNum, (int) $obat->harga_jual_bpjs, 2);

            $sheetRows[] = '<row r="' . $rowNum . '">' . $cells . '</row>';
            $rowNum++;
        }

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<cols>'
            . '<col min="1" max="1" width="6" customWidth="1"/>'
            . '<col min="2" max="2" width="14" customWidth="1"/>'
            . '<col min="3" max="3" width="38" customWidth="1"/>'
            . '<col min="4" max="4" width="22" customWidth="1"/>'
            . '<col min="5" max="6" width="12" customWidth="1"/>'
            . '<col min="7" max="9" width="18" customWidth="1"/>'
            . '</cols>'
            . '<sheetData>' . implode('', $sheetRows) . '</sheetData>'
            . '<mergeCells count="2"><mergeCell ref="A1:I1"/><mergeCell ref="A2:I2"/></mergeCells>'
            . '</worksheet>';

        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rp &quot;#,##0"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';

        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Daftar Obat" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $tempDir = storage_path('app');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }
        $tmpFile = tempnam(is_writable($tempDir) ? $tempDir : sys_get_temp_dir(), 'xlsx_');
        $zip = new \ZipArchive();
        if ($zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpFile);
            return $this->exportSpreadsheetXml($obats);
        }

        $zip->addFromString('[Content_Types].xml', $contentTypesXml);
        $zip->addFromString('_rels/.rels', $relsXml);
        $zip->addFromString('xl/workbook.xml', $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $xlsxContent = file_get_contents($tmpFile);
        @unlink($tmpFile);

        $fileName = 'Daftar_Obat_' . now()->format('Ymd_His') . '.xlsx';

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Fallback SpreadsheetML XML (.xls) tanpa ekstensi PHP apapun bila ZipArchive juga nonaktif.
     */
    private function exportSpreadsheetXml($obats)
    {
        $esc = fn($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $fileName = 'Daftar_Obat_' . now()->format('Ymd_His') . '.xls';

        $rowsXml = '<Row><Cell ss:MergeAcross="8" ss:StyleID="Title"><Data ss:Type="String">DATA DAFTAR OBAT - APOTEK ITU TOKO OBAT</Data></Cell></Row>';
        $rowsXml .= '<Row><Cell ss:MergeAcross="8"><Data ss:Type="String">Tanggal Unduh: ' . $esc(now()->format('d-m-Y H:i')) . '</Data></Cell></Row>';
        $rowsXml .= '<Row/>';

        $headers = ['No', 'Kode Obat', 'Nama Obat', 'Kategori', 'Stok', 'Satuan', 'Harga Beli', 'Harga Umum', 'Harga Jual BPJS'];
        $rowsXml .= '<Row>';
        foreach ($headers as $h) {
            $rowsXml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">' . $esc($h) . '</Data></Cell>';
        }
        $rowsXml .= '</Row>';

        foreach ($obats as $index => $obat) {
            $rowsXml .= '<Row>'
                . '<Cell><Data ss:Type="Number">' . ($index + 1) . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">' . $esc($obat->kode) . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">' . $esc($obat->nama_obat) . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">' . $esc($obat->kategori ? $obat->kategori->nama_kategori : '-') . '</Data></Cell>'
                . '<Cell><Data ss:Type="Number">' . (int) $obat->stok . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">' . $esc($obat->satuan ?? '') . '</Data></Cell>'
                . '<Cell><Data ss:Type="Number">' . (float) $obat->harga_barang . '</Data></Cell>'
                . '<Cell><Data ss:Type="Number">' . (int) $obat->harga_umum . '</Data></Cell>'
                . '<Cell><Data ss:Type="Number">' . (int) $obat->harga_jual_bpjs . '</Data></Cell>'
                . '</Row>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<?mso-application progid="Excel.Sheet"?>'
            . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            . '<Styles>'
            . '<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="13"/></Style>'
            . '<Style ss:ID="Header"><Font ss:Bold="1"/></Style>'
            . '</Styles>'
            . '<Worksheet ss:Name="Daftar Obat"><Table>' . $rowsXml . '</Table></Worksheet>'
            . '</Workbook>';

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Fallback pembaca file CSV / XLSX sederhana tanpa PhpSpreadsheet.
     */
    private function parseSpreadsheetFallback($file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if ($ext === 'csv') {
            $rows = [];
            if (($handle = fopen($path, 'r')) !== false) {
                while (($data = fgetcsv($handle, 0, ',')) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            }
            return $rows;
        }

        if ($ext === 'xlsx' && class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                $sharedStrings = [];
                if (($ssXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
                    $ss = @simplexml_load_string($ssXml);
                    if ($ss && isset($ss->si)) {
                        foreach ($ss->si as $si) {
                            $text = '';
                            if (isset($si->t)) {
                                $text = (string) $si->t;
                            } elseif (isset($si->r)) {
                                foreach ($si->r as $r) {
                                    $text .= (string) ($r->t ?? '');
                                }
                            }
                            $sharedStrings[] = $text;
                        }
                    }
                }

                $rows = [];
                if (($sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml')) !== false) {
                    $sheet = @simplexml_load_string($sheetXml);
                    if ($sheet && isset($sheet->sheetData->row)) {
                        foreach ($sheet->sheetData->row as $row) {
                            $targetRowIdx = isset($row['r']) ? max(0, (int) $row['r'] - 1) : count($rows);
                            while (count($rows) < $targetRowIdx) {
                                $rows[] = [];
                            }
                            $rowArr = [];
                            foreach ($row->c as $c) {
                                $ref = (string) ($c['r'] ?? '');
                                preg_match('/^([A-Z]+)/', $ref, $m);
                                $colIdx = 0;
                                if (!empty($m[1])) {
                                    for ($i = 0; $i < strlen($m[1]); $i++) {
                                        $colIdx = $colIdx * 26 + (ord($m[1][$i]) - 64);
                                    }
                                    $colIdx--;
                                }
                                $type = (string) ($c['t'] ?? '');
                                if ($type === 's') {
                                    $val = $sharedStrings[(int) $c->v] ?? '';
                                } elseif ($type === 'inlineStr') {
                                    $val = (string) ($c->is->t ?? '');
                                } else {
                                    $val = (string) ($c->v ?? '');
                                }
                                while (count($rowArr) < $colIdx) {
                                    $rowArr[] = '';
                                }
                                $rowArr[$colIdx] = $val;
                            }
                            $rows[$targetRowIdx] = $rowArr;
                        }
                    }
                }
                $zip->close();
                return $rows;
            }
        }

        throw new \RuntimeException('Untuk format .' . $ext . ', aktifkan ekstensi zip atau gunakan file .xlsx / .csv.');
    }
}
