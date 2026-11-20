<?php

namespace App\Http\Controllers;

use App\Models\AiMessage;
use App\Models\Obat;
use App\Models\Kategori;
use App\Models\AturanRekomendasiObat;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AIController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PENGATURAN AI
    |--------------------------------------------------------------------------
    */

    private string $ollamaUrl = 'http://127.0.0.1:11434/api/chat';

    private string $ollamaModel = 'qwen2.5:1.5b';

    private int $maxHistory = 16;

    /*
    |--------------------------------------------------------------------------
    | 1. ALUR UTAMA CHAT
    |--------------------------------------------------------------------------
    */

    public function chat(Request $request)
    {
        set_time_limit(180);

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        /*
         * Nama pengguna diambil langsung dari akun yang sedang login.
         * Jadi user tidak perlu menyebutkan namanya di chat.
         */
        $namaPengguna = trim((string) ($user->name ?? ''));

        $pertanyaan = trim($request->input('message'));
        $teks = $this->normalizeText($pertanyaan);

        if ($teks === '') {
            return response()->json([
                'error' => 'Pesan tidak boleh kosong.',
            ], 422);
        }

        $riwayat = AiMessage::where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit($this->maxHistory)
            ->get()
            ->reverse()
            ->values();

        AiMessage::create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $pertanyaan,
        ]);

        /*
         * PRIORITAS 0:
         * USER MENANYAKAN NAMANYA
         */
        if ($this->isNameQuestion($teks)) {
            if ($namaPengguna !== '') {
                return $this->sendReply(
                    $user,
                    "Nama kamu {$namaPengguna}. 😊"
                );
            }

            return $this->sendReply(
                $user,
                "Aku belum bisa menemukan nama akun kamu."
            );
        }

        /*
         * ==============================================================
         * PENTING:
         * Data obat dan kategori selalu diambil ulang dari database
         * setiap kali user mengirim pesan.
         *
         * Jadi perubahan dari halaman web seperti:
         * - obat ditambahkan
         * - obat diaktifkan
         * - obat dikonfirmasi
         * - stok berubah
         * - harga berubah
         * - obat dihapus
         *
         * akan mengikuti data database terbaru pada request berikutnya.
         * ==============================================================
         */

        $obat = $this->getDataObat();
        $kategori = $this->getDataKategori();

        /*
         * TAMBAHAN:
         * Ambil seluruh data obat tanpa filter aktif/disetujui.
         *
         * Ini khusus digunakan untuk:
         * - admin
         * - super admin
         * - daftar keseluruhan inventaris
         * - obat belum aktif
         * - obat belum disetujui
         * - obat belum tersedia
         * - obat terbaru
         *
         * Data ini juga selalu diambil ulang setiap request.
         */
        $semuaObat = $this->getDataObatKeseluruhan();

        /*
         * PRIORITAS 1: KEADAAN DARURAT
         */
        if ($this->hasEmergencySymptoms($teks)) {
            return $this->sendReply(
                $user,
                $this->emergencyReply()
            );
        }

        /*
         * ==============================================================
         * TAMBAHAN PRIORITAS INVENTARIS LENGKAP
         * ==============================================================
         *
         * Diletakkan sebelum pencarian obat biasa agar pertanyaan:
         *
         * "daftar semua obat"
         * "semua obat"
         * "inventaris lengkap"
         * "keseluruhan obat"
         *
         * tidak masuk ke Ollama.
         */
        if ($this->isAllMedicineInventoryRequest($teks)) {
            return $this->handleAllMedicineInventory(
                $user,
                $semuaObat
            );
        }

        /*
         * ==============================================================
         * TAMBAHAN:
         * OBAT TERBARU / TAMBAHAN OBAT
         * ==============================================================
         *
         * Contoh:
         * - apakah ada tambahan obat terbaru?
         * - ada obat baru?
         * - obat terbaru apa saja?
         * - ada penambahan obat?
         */
        if ($this->isNewMedicineRequest($teks)) {
            return $this->handleNewMedicineList(
                $user,
                $semuaObat
            );
        }

        /*
         * ==============================================================
         * TAMBAHAN:
         * OBAT BELUM TERSEDIA / BELUM AKTIF / BELUM DIKONFIRMASI
         * ==============================================================
         */
        if ($this->isUnavailableMedicineListRequest($teks)) {
            return $this->handleUnavailableMedicineList(
                $user,
                $semuaObat
            );
        }

        /*
         * PRIORITAS 2: PERTANYAAN TOTAL SELURUH OBAT
         */
        if ($this->isTotalMedicineCountRequest($teks)) {
            return $this->handleTotalMedicineCount($user, $obat);
        }

        /*
         * PRIORITAS 3: PERTANYAAN AMBANG BATAS JUMLAH STOK
         */
        $thresholdData = $this->parseStockThreshold($teks);
        if ($thresholdData !== null) {
            return $this->handleStockThresholdQuery($user, $thresholdData, $obat);
        }

        /*
         * TAMBAHAN:
         * PERTANYAAN STOK MENIPIS SECARA EKSPLISIT
         *
         * Ini tetap memakai data $obat terbaru.
         */
        if ($this->isLowStockListRequest($teks)) {
            return $this->handleLowStockList(
                $user,
                $obat
            );
        }

        /*
         * PRIORITAS 4: DAFTAR OBAT HABIS
         */
        if ($this->isOutOfStockListRequest($teks)) {
            return $this->handleOutOfStockList($user, $obat);
        }

        /*
         * PRIORITAS 5: DAFTAR OBAT TERSEDIA
         */
        if ($this->isAvailableListRequest($teks)) {
            return $this->handleAvailableList($user, $obat);
        }

        /*
         * PRIORITAS 6: DAFTAR KATEGORI / JENIS OBAT
         */
        if ($this->isCategoryListRequest($teks)) {
            return $this->handleCategoryList($user, $kategori, $obat);
        }

        /*
         * PRIORITAS 7: PENCARIAN OBAT TERTENTU
         */
        $obatCocok = $this->findMedicineInMessage($teks, $obat);

        if (
            $obatCocok->isEmpty()
            && ($this->isStockOrPriceQuestion($teks)
                || $this->isMedicineDetailQuestion($teks)
                || $this->isMedicineDosageOrUsageQuestion($teks))
            && $this->hasMedicineReference($teks)
            && !$this->isGeneralMedicineQuery($teks)
        ) {
            $obatCocok = $this->findMedicineInHistory($riwayat, $obat);
        }

        if ($obatCocok->isNotEmpty()) {
            if ($this->isMedicineDosageOrUsageQuestion($teks)) {
                return $this->handleMedicineUsage($user, $obatCocok, $teks);
            }

            if ($this->isStockOrPriceQuestion($teks)) {
                return $this->handleMedicineStock($user, $obatCocok);
            }

            if ($this->isMedicineDetailQuestion($teks)) {
                return $this->handleMedicineDetail($user, $obatCocok);
            }

            return $this->handleMedicineOverview($user, $obatCocok);
        }

        /*
         * PRIORITAS 8: OBAT BERDASARKAN KATEGORI / JENIS TERTENTU
         */
        $kategoriCocok = $this->findCategoryInMessage($teks, $kategori);
        if ($kategoriCocok !== null) {
            return $this->handleCategoryMedicines($user, $kategoriCocok, $obat);
        }

        /*
         * ==============================================================
         * PRIORITAS 9:
         * PERTANYAAN STOK UMUM
         *
         * DILETAKKAN SEBELUM isDirectMedicineStockQuery()
         * agar pertanyaan seperti:
         *
         * "apakah ada stok yang tersedia?"
         * "ada obat yang ready?"
         * "ada obat aktif?"
         * "masih ada stok obat?"
         *
         * tidak dianggap sebagai pencarian nama obat.
         * ==============================================================
         */
        if ($this->isGeneralStockQuestion($teks)) {
            return $this->handleGeneralStockQuestion($user, $obat);
        }

        /*
         * PRIORITAS 10: PERTANYAAN STOK / HARGA PRODUK YANG TIDAK ADA
         */
        if ($this->isDirectMedicineStockQuery($teks)) {
            return $this->handleUnknownMedicineQuery($user);
        }

        /*
         * PRIORITAS 11: PERTANYAAN LANJUTAN TENTANG STOK
         *
         * Data $obat terbaru dikirim ke method agar pengecekan
         * tetap berdasarkan database saat ini.
         */
        $jawabanStok = $this->handleStockFollowUp($teks, $riwayat, $obat);
        if ($jawabanStok !== null) {
            return $this->sendReply($user, $jawabanStok);
        }

        /*
         * PRIORITAS 12: PERCAKAPAN RINGAN
         */
        $smallTalk = $this->handleSmallTalk($teks);
        if ($smallTalk !== null) {
            return $this->sendReply($user, $smallTalk);
        }

        /*
         * PRIORITAS 13: PEMBUKAAN KELUHAN KESEHATAN
         */
        $healthOpening = $this->handleHealthOpening($teks);
        if ($healthOpening !== null) {
            return $this->sendReply($user, $healthOpening);
        }

        /*
         * PRIORITAS 14: REKOMENDASI OBAT
         */
        if ($this->isMedicineRecommendationQuestion($teks)) {
            return $this->sendReply(
                $user,
                $this->handleMedicineRecommendation($teks, $riwayat)
            );
        }

        /*
         * PRIORITAS 15: PERCAKAPAN KESEHATAN
         */
        $healthReply = $this->handleHealthConversation($teks, $riwayat);
        if ($healthReply !== null) {
            return $this->sendReply($user, $healthReply);
        }

        /*
         * PRIORITAS 16: OLLAMA FALLBACK
         */
        $history = AiMessage::where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit($this->maxHistory)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                ];
            })
            ->all();

        try {
            $response = Http::timeout(120)->post(
                $this->ollamaUrl,
                [
                    'model' => $this->ollamaModel,
                    'messages' => array_merge(
                        [
                            [
                                'role' => 'system',
                                'content' => $this->buildSystemPrompt(
                                    $obat,
                                    $kategori,
                                    $user
                                ),
                            ],
                        ],
                        $history
                    ),
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.2,
                        'top_p' => 0.85,
                        'num_ctx' => 4096,
                        'repeat_penalty' => 1.15,
                    ],
                ]
            );

            if ($response->failed()) {
                Log::error('Ollama gagal merespons.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'error' => 'AI sedang mengalami gangguan. Silakan coba lagi.',
                ], 502);
            }

            $reply = $response->json('message.content');

            if (!is_string($reply) || trim($reply) === '') {
                return response()->json([
                    'error' => 'AI belum memberikan jawaban. Silakan coba lagi.',
                ], 502);
            }

            return $this->sendReply(
                $user,
                $this->cleanAIResponse($reply)
            );

        } catch (Throwable $e) {
            Log::error('Kesalahan koneksi Ollama.', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Tidak dapat terhubung ke AI lokal. Pastikan Ollama berjalan.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 2. IDENTITAS PENGGUNA
    |--------------------------------------------------------------------------
    */

    private function isNameQuestion(string $teks): bool
    {
        return (bool) preg_match(
            '/^(?:'
            . 'siapa\s+nama\s+(?:aku|saya)'
            . '|nama\s+(?:aku|saya)\s+siapa'
            . '|namaku\s+siapa'
            . '|siapa\s+namaku'
            . '|siapa\s+nama\s+aku'
            . '|siapa\s+nama\s+saya'
            . '|apakah\s+kamu\s+tahu\s+namaku'
            . '|apakah\s+kamu\s+tahu\s+nama\s+(?:aku|saya)'
            . '|apakah\s+kamu\s+ingat\s+namaku'
            . '|apakah\s+kamu\s+ingat\s+nama\s+(?:aku|saya)'
            . '|kamu\s+tahu\s+namaku'
            . '|kamu\s+tahu\s+nama\s+(?:aku|saya)'
            . '|kamu\s+ingat\s+namaku'
            . '|kamu\s+ingat\s+nama\s+(?:aku|saya)'
            . ')[?!.\s]$/u',
            $teks
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 3. NORMALISASI TEKS
    |--------------------------------------------------------------------------
    */

    private function normalizeText(string $teks): string
    {
        $teks = mb_strtolower(trim($teks));
        $teks = preg_replace('/\s+/u', ' ', $teks);

        return $teks ?? '';
    }

    /*
    |--------------------------------------------------------------------------
    | 4. DATA INVENTARIS
    |--------------------------------------------------------------------------
    */

    private function getDataObat(): Collection
    {
        /*
         * Data diambil langsung dari database pada setiap request.
         *
         * Hanya obat yang:
         * - aktif
         * - disetujui
         *
         * yang dianggap sebagai inventaris resmi yang bisa dilihat AI.
         */
        return Obat::with('kategori')
            ->where('is_active', true)
            ->where('is_approved', true)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'kode' => $item->kode,
                    'nama_obat' => $item->nama_obat,
                    'kategori_id' => $item->kategori_id,
                    'kategori' => $item->kategori?->nama_kategori ?? 'Umum',
                    'stok' => max(0, (int) $item->stok),
                    'satuan' => $item->satuan ?: 'Unit',
                    'harga_umum' => $item->harga_umum,
                    'deskripsi' => $item->deskripsi,
                ];
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | TAMBAHAN 4A. SELURUH DATA OBAT
    |--------------------------------------------------------------------------
    |
    | Method ini sengaja TIDAK menggunakan filter:
    |
    |     is_active = true
    |     is_approved = true
    |
    | Karena admin dan super admin perlu bisa mengetahui:
    |
    | - obat aktif
    | - obat belum aktif
    | - obat belum disetujui
    | - obat belum tersedia
    | - obat yang masih menunggu konfirmasi
    | - obat yang stoknya 0
    | - obat yang stoknya menipis
    | - obat yang stoknya tersedia
    |
    | Data selalu diambil ulang dari database pada setiap chat.
    |
    */

    private function getDataObatKeseluruhan(): Collection
    {
        return Obat::with('kategori')
            ->orderByDesc('id')
            ->get()
            ->map(function ($item) {

                $isActive = (bool) $item->is_active;
                $isApproved = (bool) $item->is_approved;

                if ($isActive && $isApproved) {
                    $status = 'Aktif dan Disetujui';
                } elseif (!$isActive && !$isApproved) {
                    $status = 'Belum Aktif dan Belum Disetujui';
                } elseif (!$isActive && $isApproved) {
                    $status = 'Disetujui tetapi Belum Aktif';
                } else {
                    $status = 'Aktif tetapi Belum Disetujui';
                }

                $stok = max(0, (int) $item->stok);

                if (!$isActive || !$isApproved) {
                    $statusKetersediaan = 'Belum tersedia';
                } elseif ($stok <= 0) {
                    $statusKetersediaan = 'Habis';
                } elseif ($stok <= 10) {
                    $statusKetersediaan = 'Menipis';
                } else {
                    $statusKetersediaan = 'Tersedia';
                }

                return [
                    'id' => $item->id,
                    'kode' => $item->kode,
                    'nama_obat' => $item->nama_obat,
                    'kategori_id' => $item->kategori_id,
                    'kategori' => $item->kategori?->nama_kategori ?? 'Umum',
                    'stok' => $stok,
                    'satuan' => $item->satuan ?: 'Unit',
                    'harga_umum' => $item->harga_umum,
                    'deskripsi' => $item->deskripsi,
                    'is_active' => $isActive,
                    'is_approved' => $isApproved,
                    'status' => $status,
                    'status_ketersediaan' => $statusKetersediaan,
                ];
            })
            ->values();
    }

    private function getDataKategori(): Collection
    {
        return Kategori::with(['obat' => function ($query) {
            $query->where('is_active', true)
                ->where('is_approved', true);
        }])
            ->get()
            ->map(function ($kat) {
                $obatAktif = $kat->obat ?? collect();

                return [
                    'id' => $kat->id,
                    'nama_kategori' => $kat->nama_kategori,
                    'slug' => $kat->slug,
                    'deskripsi' => $kat->deskripsi,
                    'total_obat' => $obatAktif->count(),
                    'obat_tersedia' => $obatAktif->where('stok', '>', 0)->count(),
                ];
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | TAMBAHAN 4B. INVENTARIS LENGKAP
    |--------------------------------------------------------------------------
    */

    private function isAllMedicineInventoryRequest(string $teks): bool
    {
        return (bool) preg_match(
            '/('
            . 'daftar\s+semua\s+obat'
            . '|list\s+semua\s+obat'
            . '|semua\s+obat'
            . '|seluruh\s+obat'
            . '|keseluruhan\s+obat'
            . '|semua\s+data\s+obat'
            . '|seluruh\s+data\s+obat'
            . '|data\s+lengkap\s+obat'
            . '|daftar\s+obat\s+lengkap'
            . '|inventaris\s+obat'
            . '|inventaris\s+lengkap'
            . '|inventaris\s+apotek'
            . '|cek\s+semua\s+obat'
            . '|tampilkan\s+semua\s+obat'
            . '|sebutkan\s+semua\s+obat'
            . '|lihat\s+semua\s+obat'
            . '|obat\s+secara\s+lengkap'
            . ')/u',
            $teks
        );
    }

    private function handleAllMedicineInventory(
        $user,
        Collection $semuaObat
    ) {
        $total = $semuaObat->count();

        if ($semuaObat->isEmpty()) {
            return $this->sendReply(
                $user,
                "Saat ini belum ada data obat yang tercatat di database apotek."
            );
        }

        $aktif = $semuaObat
            ->where('is_active', true)
            ->where('is_approved', true)
            ->count();

        $tersedia = $semuaObat
            ->where('status_ketersediaan', 'Tersedia')
            ->count();

        $menipis = $semuaObat
            ->where('status_ketersediaan', 'Menipis')
            ->count();

        $habis = $semuaObat
            ->where('status_ketersediaan', 'Habis')
            ->count();

        $belumTersedia = $semuaObat
            ->where('status_ketersediaan', 'Belum tersedia')
            ->count();

        $reply = "📦 DATA KESELURUHAN INVENTARIS OBAT APOTEK\n\n"
            . "Total seluruh data obat: {$total} obat\n"
            . "Aktif & disetujui: {$aktif} obat\n"
            . "Tersedia: {$tersedia} obat\n"
            . "Stok menipis: {$menipis} obat\n"
            . "Stok habis: {$habis} obat\n"
            . "Belum tersedia / belum dikonfirmasi: {$belumTersedia} obat\n\n"
            . "DETAIL OBAT:\n\n";

        foreach ($semuaObat as $index => $item) {

            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format(
                    (float) $item['harga_umum'],
                    0,
                    ',',
                    '.'
                )
                : 'Belum tercatat';

            $reply .= ($index + 1) . ". {$item['nama_obat']}\n"
                . "   - Kode: " . ($item['kode'] ?: 'Belum tercatat') . "\n"
                . "   - Kategori: {$item['kategori']}\n"
                . "   - Stok: {$item['stok']} {$item['satuan']}\n"
                . "   - Status: {$item['status']}\n"
                . "   - Ketersediaan: {$item['status_ketersediaan']}\n"
                . "   - Harga umum: {$harga}\n";

            if (!empty($item['deskripsi'])) {
                $reply .= "   - Deskripsi: {$item['deskripsi']}\n";
            }

            $reply .= "\n";
        }

        $reply .= "Data di atas diperiksa langsung dari database "
            . "inventaris pada request ini.";

        return $this->sendReply($user, $reply);
    }

    /*
    |--------------------------------------------------------------------------
    | TAMBAHAN 4C. OBAT TERBARU / TAMBAHAN OBAT
    |--------------------------------------------------------------------------
    */

    private function isNewMedicineRequest(string $teks): bool
    {
        return (bool) preg_match(
            '/('
            . 'obat\s+terbaru'
            . '|obat\s+baru'
            . '|tambahan\s+obat'
            . '|tambahan\s+obat\s+terbaru'
            . '|ada\s+tambahan\s+obat'
            . '|ada\s+obat\s+baru'
            . '|ada\s+obat\s+terbaru'
            . '|penambahan\s+obat'
            . '|obat\s+yang\s+baru\s+ditambahkan'
            . '|obat\s+yang\s+baru\s+ditambah'
            . '|obat\s+terbaru\s+apa\s+saja'
            . '|obat\s+baru\s+apa\s+saja'
            . '|cek\s+obat\s+terbaru'
            . '|cek\s+obat\s+baru'
            . ')/u',
            $teks
        );
    }

    private function handleNewMedicineList(
        $user,
        Collection $semuaObat
    ) {
        if ($semuaObat->isEmpty()) {
            return $this->sendReply(
                $user,
                "Saat ini belum ada obat yang tercatat di database apotek."
            );
        }

        /*
         * Karena struktur tabel yang diberikan menggunakan ID,
         * ID terbesar digunakan sebagai indikator data yang paling
         * baru ditambahkan.
         *
         * Tidak menggunakan hard-code nama obat.
         */
        $terbaru = $semuaObat
            ->sortByDesc('id')
            ->take(10)
            ->values();

        $totalTerbaru = $terbaru->count();

        $reply = "📥 DATA OBAT TERBARU / TAMBAHAN TERAKHIR\n\n";

        if ($totalTerbaru === 0) {
            return $this->sendReply(
                $user,
                "Saat ini belum ada tambahan obat yang dapat ditampilkan."
            );
        }

        $reply .= "Ditemukan {$totalTerbaru} data obat terbaru "
            . "berdasarkan urutan ID database:\n\n";

        foreach ($terbaru as $index => $item) {

            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format(
                    (float) $item['harga_umum'],
                    0,
                    ',',
                    '.'
                )
                : 'Belum tercatat';

            $reply .= ($index + 1) . ". {$item['nama_obat']}\n"
                . "   - Kode: " . ($item['kode'] ?: 'Belum tercatat') . "\n"
                . "   - Kategori: {$item['kategori']}\n"
                . "   - Stok: {$item['stok']} {$item['satuan']}\n"
                . "   - Status: {$item['status']}\n"
                . "   - Ketersediaan: {$item['status_ketersediaan']}\n"
                . "   - Harga: {$harga}\n\n";
        }

        $reply .= "Catatan: daftar ini diambil langsung dari data "
            . "database saat ini. Status obat juga diperiksa ulang sehingga "
            . "obat yang belum aktif atau belum disetujui tidak dianggap "
            . "sebagai obat tersedia.";

        return $this->sendReply($user, $reply);
    }

    /*
    |--------------------------------------------------------------------------
    | TAMBAHAN 4D. OBAT BELUM TERSEDIA / BELUM DIKONFIRMASI
    |--------------------------------------------------------------------------
    */

    private function isUnavailableMedicineListRequest(string $teks): bool
    {
        return (bool) preg_match(
            '/('
            . 'obat\s+belum\s+tersedia'
            . '|obat\s+yang\s+belum\s+tersedia'
            . '|obat\s+belum\s+aktif'
            . '|obat\s+yang\s+belum\s+aktif'
            . '|obat\s+belum\s+disetujui'
            . '|obat\s+yang\s+belum\s+disetujui'
            . '|obat\s+belum\s+dikonfirmasi'
            . '|obat\s+yang\s+belum\s+dikonfirmasi'
            . '|belum\s+tersedia'
            . '|belum\s+aktif'
            . '|belum\s+disetujui'
            . '|belum\s+dikonfirmasi'
            . '|belum\s+terkonfirmasi'
            . '|obat\s+pending'
            . '|obat\s+yang\s+masih\s+pending'
            . '|daftar\s+obat\s+pending'
            . '|obat\s+menunggu\s+konfirmasi'
            . '|obat\s+menunggu\s+persetujuan'
            . ')/u',
            $teks
        );
    }

    private function handleUnavailableMedicineList(
        $user,
        Collection $semuaObat
    ) {
        $belumTersedia = $semuaObat
            ->filter(function ($item) {
                return !$item['is_active']
                    || !$item['is_approved'];
            })
            ->values();

        $total = $belumTersedia->count();

        if ($belumTersedia->isEmpty()) {
            return $this->sendReply(
                $user,
                "Saat ini tidak ada obat yang berstatus belum tersedia, "
                . "belum aktif, atau belum disetujui. Semua data obat yang "
                . "tercatat saat ini sudah berstatus aktif dan disetujui."
            );
        }

        $reply = "📋 OBAT YANG BELUM TERSEDIA / BELUM DIKONFIRMASI\n\n"
            . "Total: {$total} obat\n\n";

        foreach ($belumTersedia as $index => $item) {

            $statusKonfirmasi = '';

            if (!$item['is_active'] && !$item['is_approved']) {
                $statusKonfirmasi = 'Belum aktif dan belum disetujui';
            } elseif (!$item['is_active'] && $item['is_approved']) {
                $statusKonfirmasi = 'Sudah disetujui tetapi belum aktif';
            } elseif ($item['is_active'] && !$item['is_approved']) {
                $statusKonfirmasi = 'Aktif tetapi belum disetujui';
            }

            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format(
                    (float) $item['harga_umum'],
                    0,
                    ',',
                    '.'
                )
                : 'Belum tercatat';

            $reply .= ($index + 1) . ". {$item['nama_obat']}\n"
                . "   - Kode: " . ($item['kode'] ?: 'Belum tercatat') . "\n"
                . "   - Kategori: {$item['kategori']}\n"
                . "   - Stok tercatat: {$item['stok']} {$item['satuan']}\n"
                . "   - Status konfirmasi: {$statusKonfirmasi}\n"
                . "   - Status ketersediaan: Belum tersedia\n"
                . "   - Harga umum: {$harga}\n";

            if (!empty($item['deskripsi'])) {
                $reply .= "   - Deskripsi: {$item['deskripsi']}\n";
            }

            $reply .= "\n";
        }

        $reply .= "Data ini diperiksa langsung dari database pada request "
            . "saat ini. Obat yang belum aktif atau belum disetujui tidak "
            . "dihitung sebagai obat tersedia.";

        return $this->sendReply($user, $reply);
    }

    /*
    |--------------------------------------------------------------------------
    | TAMBAHAN 4E. STOK MENIPIS
    |--------------------------------------------------------------------------
    */

    private function isLowStockListRequest(string $teks): bool
    {
        return (bool) preg_match(
            '/('
            . 'stok\s+menipis'
            . '|stok\s+yang\s+menipis'
            . '|obat\s+stoknya\s+menipis'
            . '|obat\s+yang\s+stoknya\s+menipis'
            . '|stok\s+hampir\s+habis'
            . '|obat\s+hampir\s+habis'
            . '|stok\s+kritis'
            . '|obat\s+kritis'
            . '|stok\s+mau\s+habis'
            . '|obat\s+mau\s+habis'
            . '|daftar\s+stok\s+menipis'
            . '|daftar\s+obat\s+menipis'
            . ')/u',
            $teks
        );
    }

    private function handleLowStockList(
        $user,
        Collection $obat
    ) {
        /*
         * Batas stok menipis:
         * 1 sampai 10.
         *
         * Stok 0 tidak dimasukkan ke sini karena sudah termasuk
         * kategori habis.
         */
        $menipis = $obat
            ->filter(function ($item) {
                $stok = (int) ($item['stok'] ?? 0);

                return $stok > 0 && $stok <= 10;
            })
            ->sortBy('stok')
            ->values();

        $total = $menipis->count();

        if ($menipis->isEmpty()) {
            return $this->sendReply(
                $user,
                "Saat ini tidak ada obat aktif dan disetujui yang "
                . "memiliki stok menipis (1 sampai 10)."
            );
        }

        $reply = "⚠️ DAFTAR OBAT DENGAN STOK MENIPIS\n\n"
            . "Total: {$total} obat\n"
            . "Batas stok menipis: 1 sampai 10\n\n";

        foreach ($menipis as $index => $item) {

            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format(
                    (float) $item['harga_umum'],
                    0,
                    ',',
                    '.'
                )
                : 'Belum tercatat';

            $reply .= ($index + 1) . ". {$item['nama_obat']}\n"
                . "   - Kategori: {$item['kategori']}\n"
                . "   - Stok: {$item['stok']} {$item['satuan']} (Menipis)\n"
                . "   - Harga umum: {$harga}\n\n";
        }

        $reply .= "Data stok diperiksa langsung dari database inventaris "
            . "pada request ini.";

        return $this->sendReply($user, $reply);
    }

    /*
    |--------------------------------------------------------------------------
    | 4F. JUMLAH TOTAL OBAT & FILTER STOK
    |--------------------------------------------------------------------------
    */

    private function isTotalMedicineCountRequest(string $teks): bool
    {
        return (bool) preg_match(
            '/('
            . '(ada\s+)?berapa\s+(total|jumlah|banyak|semua)\sobat'
            . '|total\s+obat.berapa'
            . '|jumlah\s+obat.berapa'
            . '|ada\s+berapa\s+(banyak\s+)?obat\s+(di\s+apotek|yang\s+ada|seluruhnya)'
            . ')/u',
            $teks
        ) && !preg_match(
            '/(kurang|lebih|di\s+bawah|di\s+atas|habis|abis|kosong|tersedia|ready|\d+)/u',
            $teks
        );
    }

    private function handleTotalMedicineCount($user, Collection $obat)
    {
        $total = $obat->count();
        $tersedia = $obat->where('stok', '>', 0)->count();
        $habis = $obat->where('stok', '<=', 0)->count();

        $reply = "Saat ini terdapat total {$total} obat yang terdaftar di apotek kami:\n\n"
            . "- Tersedia (Ready): {$tersedia} obat\n"
            . "- Habis (Kosong): {$habis} obat\n\n"
            . "Kamu bisa menanyakan 'daftar obat tersedia' untuk melihat obat yang ready, "
            . "atau 'daftar obat habis' untuk melihat obat yang stoknya 0.";

        return $this->sendReply($user, $reply);
    }

    private function parseStockThreshold(string $teks): ?array
    {
        if (preg_match(
            '/\b(stok\s(?:yang\s)?)?(menipis|hampir\s+habis|kritis|mau\s+habis)\b/u',
            $teks
        )) {
            return [
                'operator' => 'menipis',
                'threshold' => 10,
                'label' => 'menipis / hampir habis (stok 1 sampai 10)',
            ];
        }

        $adaIndikasiStok = (bool) preg_match(
            '/(stok|jumlah|banyak|obat)/u',
            $teks
        );

        if (!$adaIndikasiStok) {
            return null;
        }

        $teksNormal = str_replace(
            [
                'satu',
                'dua',
                'tiga',
                'empat',
                'lima',
                'enam',
                'tujuh',
                'delapan',
                'sembilan',
                'sepuluh',
                'nol'
            ],
            [
                '1',
                '2',
                '3',
                '4',
                '5',
                '6',
                '7',
                '8',
                '9',
                '10',
                '0'
            ],
            $teks
        );

        if (preg_match(
            '/(?:antara|di\santara|rentang)?\s(\d+)\s(?:sampai|hingga|s\/d|-)\s(\d+)/u',
            $teksNormal,
            $m
        )) {
            $min = min((int) $m[1], (int) $m[2]);
            $max = max((int) $m[1], (int) $m[2]);

            return [
                'operator' => 'between',
                'threshold' => [$min, $max],
                'label' => "antara {$min} sampai {$max}",
            ];
        }

        if (preg_match(
            '/(?:kurang\s+dari\s+sama\s+dengan|<=|maksimal|paling\s+banyak)\s(\d+)/u',
            $teksNormal,
            $m
        )) {
            $val = (int) $m[1];

            return [
                'operator' => '<=',
                'threshold' => $val,
                'label' => "kurang dari atau sama dengan {$val}",
            ];
        }

        if (preg_match(
            '/(?:lebih\s+dari\s+sama\s+dengan|>=|minimal|paling\s+sedikit)\s(\d+)/u',
            $teksNormal,
            $m
        )) {
            $val = (int) $m[1];

            return [
                'operator' => '>=',
                'threshold' => $val,
                'label' => "lebih dari atau sama dengan {$val}",
            ];
        }

        if (preg_match(
            '/(\d+)\s(?:lebih|ke\satas|\+)/u',
            $teksNormal,
            $m
        )) {
            $val = (int) $m[1];

            return [
                'operator' => '>=',
                'threshold' => $val,
                'label' => "{$val} lebih ({$val} ke atas)",
            ];
        }

        if (preg_match(
            '/(\d+)\s(?:ke\sbawah|kurang)/u',
            $teksNormal,
            $m
        )) {
            $val = (int) $m[1];

            return [
                'operator' => '<=',
                'threshold' => $val,
                'label' => "kurang dari atau sama dengan {$val}",
            ];
        }

        if (preg_match(
            '/(?:kurang\s+dari|di\s+bawah|lebih\s+kecil\s+dari|<)\s(\d+)/u',
            $teksNormal,
            $m
        )) {
            $val = (int) $m[1];

            return [
                'operator' => '<',
                'threshold' => $val,
                'label' => "kurang dari {$val}",
            ];
        }

        if (preg_match(
            '/(?:lebih\s+dari|di\s+atas|lebih\s+besar\s+dari|>)\s(\d+)/u',
            $teksNormal,
            $m
        )) {
            $val = (int) $m[1];

            return [
                'operator' => '>',
                'threshold' => $val,
                'label' => "lebih dari {$val}",
            ];
        }

        if (preg_match(
            '/(?:stok|jumlah\sstok)\s(?:nya)?\s(?:sama\s+dengan|=|adalah)?\s(\d+)(?!\s(?:lebih|ke\satas|ke\sbawah|kurang|\+))/u',
            $teksNormal,
            $m
        )) {
            $val = (int) $m[1];

            return [
                'operator' => '=',
                'threshold' => $val,
                'label' => "sama dengan {$val}",
            ];
        }

        return null;
    }

    private function handleStockThresholdQuery(
        $user,
        array $thresholdData,
        Collection $obat
    ) {
        $op = $thresholdData['operator'];
        $val = $thresholdData['threshold'];
        $label = $thresholdData['label'];

        $hasil = $obat->filter(function ($item) use ($op, $val) {
            $stok = (int) ($item['stok'] ?? 0);

            return match ($op) {
                '<' => $stok < $val,
                '<=' => $stok <= $val,
                '>' => $stok > $val,
                '>=' => $stok >= $val,
                '=' => $stok === $val,
                'between' => $stok >= $val[0] && $stok <= $val[1],
                'menipis' => $stok > 0 && $stok <= 10,
                default => false,
            };
        })->values();

        $total = $hasil->count();

        $ketTambahan =
            ($op === '<' && $val === 1)
            || ($op === '<=' && $val === 0)
            || ($op === '=' && $val === 0)
                ? ' (stok 0 / habis)'
                : '';

        if ($hasil->isEmpty()) {
            return $this->sendReply(
                $user,
                "Saat ini tidak ada obat yang jumlah stoknya {$label}{$ketTambahan} di apotek kami. 😊"
            );
        }

        $reply = "Saat ini terdapat total {$total} obat yang jumlah stoknya {$label}{$ketTambahan}:\n\n";

        $limitTampil = 20;
        $tampil = $hasil->take($limitTampil);

        foreach ($tampil as $index => $item) {
            $status = $item['stok'] > 0 ? "Tersedia" : "Habis";

            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format((float) $item['harga_umum'], 0, ',', '.')
                : 'Belum tercatat';

            $reply .= ($index + 1) . ". {$item['nama_obat']} [{$item['kategori']}]\n"
                . "   - Stok: {$item['stok']} {$item['satuan']} ({$status})\n"
                . "   - Harga umum: {$harga}\n\n";
        }

        if ($total > $limitTampil) {
            $sisa = $total - $limitTampil;

            $reply .= "(Menampilkan {$limitTampil} dari total {$total} obat. "
                . "Masih ada {$sisa} obat lainnya). \n\n";
        }

        $reply .= "Data ini diambil langsung dari database inventaris apotek.";

        return $this->sendReply($user, $reply);
    }

    /*
    |--------------------------------------------------------------------------
    | 5. DAFTAR OBAT HABIS
    |--------------------------------------------------------------------------
    */

    private function isOutOfStockListRequest(string $teks): bool
    {
        if (preg_match(
            '/(?:sampai|hingga|di\santara|rentang)\s\d+/u',
            $teks
        )) {
            return false;
        }

        $polaStokNol = (bool) preg_match(
            '/stok(?:nya)?\s(?:adalah\s)?(?:\b0\b|\bnol\b|habis|abis|kosong)/u',
            $teks
        );

        $polaKataHabis = (bool) preg_match(
            '/\b(habis|abis|kosong|kehabisan|nihil)\b/u',
            $teks
        );

        $polaBerapaNol = (bool) preg_match(
            '/(berapa|apa\s+saja|apa\s+aja|ada\s+nggak|ada\s+gak|ada\s+tidak|daftar|sebutkan).?(\b0\b|\bnol\b|habis|abis|kosong)/u',
            $teks
        );

        $menyebutObat = (bool) preg_match(
            '/\b(obat|barang|produk|stok|stoknya)\b/u',
            $teks
        );

        return ($polaStokNol && ($menyebutObat || str_contains($teks, 'berapa')))
            || ($polaKataHabis && $menyebutObat)
            || ($polaBerapaNol && $menyebutObat);
    }

    private function handleOutOfStockList($user, Collection $obat)
    {
        $habis = $obat->where('stok', '<=', 0)->values();
        $totalHabis = $habis->count();

        if ($habis->isEmpty()) {
            return $this->sendReply(
                $user,
                'Saat ini tidak ada obat yang stoknya 0 / habis. '
                . 'Seluruh obat aktif di apotek tercatat masih tersedia. 😊'
            );
        }

        $reply = "Saat ini ada {$totalHabis} obat yang tercatat stoknya habis (0):\n\n";

        foreach ($habis as $index => $item) {
            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format((float) $item['harga_umum'], 0, ',', '.')
                : 'Belum tercatat';

            $reply .= ($index + 1) . ". {$item['nama_obat']}\n"
                . "   - Kategori: {$item['kategori']}\n"
                . "   - Stok: 0 {$item['satuan']} (Habis)\n"
                . "   - Harga umum: {$harga}\n\n";
        }

        $reply .= "Informasi diambil langsung dari data inventaris database apotek.";

        return $this->sendReply($user, $reply);
    }

    /*
    |--------------------------------------------------------------------------
    | 6. DAFTAR OBAT TERSEDIA
    |--------------------------------------------------------------------------
    */

    private function isAvailableListRequest(string $teks): bool
    {
        return (bool) preg_match(
            '/('
            . 'obat.(tersedia|ready|ada)'
            . '|(daftar|tampilkan|sebutkan|list).obat'
            . '|obat\s+apa\s+saja'
            . '|obat\s+apa\s+aja'
            . '|ada\s+obat\s+apa\s+(aja|saja)'
            . '|ada\s+berapa\s+obat.(tersedia|ready|ada)'
            . '|berapa\s+obat.(tersedia|ready|ada)'
            . '|barang\s+apa\s+saja\s+yang\s+ada'
            . '|produk\s+apa\s+saja\s+yang\s+tersedia'
            . ')/u',
            $teks
        ) && !$this->isOutOfStockListRequest($teks)
            && !$this->isAllMedicineInventoryRequest($teks)
            && !$this->isUnavailableMedicineListRequest($teks)
            && !$this->isNewMedicineRequest($teks);
    }

    private function handleAvailableList($user, Collection $obat)
    {
        /*
         * $obat sudah diambil langsung dari database pada request ini.
         * Jadi daftar berikut tidak berasal dari Ollama dan tidak berasal
         * dari daftar hard-code.
         */
        $tersedia = $obat->where('stok', '>', 0)->values();
        $totalTersedia = $tersedia->count();

        if ($tersedia->isEmpty()) {
            return $this->sendReply(
                $user,
                "Maaf ya. 🙏 Saat ini belum ada obat aktif dan disetujui "
                . "yang memiliki stok tersedia di apotek kami."
            );
        }

        $reply = "Saat ini ada {$totalTersedia} obat yang tersedia di apotek:\n\n";

        foreach ($tersedia as $index => $item) {
            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format((float) $item['harga_umum'], 0, ',', '.')
                : 'Belum tercatat';

            $reply .= ($index + 1) . ". {$item['nama_obat']}\n"
                . "   - Kategori: {$item['kategori']}\n"
                . "   - Stok: {$item['stok']} {$item['satuan']}\n"
                . "   - Harga: {$harga}\n\n";
        }

        $reply .= "Informasi ini sesuai dengan data database inventaris apotek saat ini.";

        return $this->sendReply($user, $reply);
    }

    /*
    |--------------------------------------------------------------------------
    | 7. KATEGORI / JENIS OBAT
    |--------------------------------------------------------------------------
    */

    private function isCategoryListRequest(string $teks): bool
    {
        return (bool) preg_match(
            '/('
            . 'kategori\s+obat'
            . '|jenis\s+obat'
            . '|macam\s+obat'
            . '|golongan\s+obat'
            . '|tipe\s+obat'
            . '|ada\s+kategori\s+apa'
            . '|kategori.(apa\s+saja|apa\s+aja|tersedia|ada)'
            . '|jenis.(apa\s+saja|apa\s+aja|tersedia|ada)'
            . '|daftar\s+(kategori|jenis)'
            . '|sebutkan\s+(kategori|jenis)'
            . ')/u',
            $teks
        );
    }

    private function handleCategoryList($user, Collection $kategori, Collection $obat)
    {
        if ($kategori->isEmpty()) {
            return $this->sendReply(
                $user,
                'Saat ini belum ada data kategori obat yang tercatat di sistem apotek.'
            );
        }

        $reply = "Berikut adalah kategori / jenis obat yang ada di apotek kami:\n\n";

        foreach ($kategori as $index => $kat) {
            $desc = $kat['deskripsi']
                ? " ({$kat['deskripsi']})"
                : '';

            $reply .= ($index + 1) . ". {$kat['nama_kategori']}{$desc}\n"
                . "   - Jumlah produk aktif: {$kat['total_obat']} obat "
                . "({$kat['obat_tersedia']} tersedia)\n";
        }

        $reply .= "\n💡 Tips: Kamu bisa menanyakan obat di kategori tertentu, "
            . "misalnya: 'ada obat herbal apa saja?' atau 'tampilkan obat bebas'.";

        return $this->sendReply($user, $reply);
    }

    private function findCategoryInMessage(string $teks, Collection $kategori): ?array
    {
        $adaKonteksPencarian = (bool) preg_match(
            '/(kategori|jenis|obat|produk|daftar|tampilkan|apa\s+saja|apa\s+aja|ada|stok)/u',
            $teks
        );

        if (!$adaKonteksPencarian) {
            return null;
        }

        foreach ($kategori as $kat) {
            $namaKat = $this->normalizeText(
                $kat['nama_kategori'] ?? ''
            );

            if ($namaKat === '') {
                continue;
            }

            if (str_contains($teks, $namaKat)) {
                return $kat;
            }

            if (
                str_contains($namaKat, 'vitamin')
                && (
                    str_contains($teks, 'vitamin')
                    || str_contains($teks, 'suplemen')
                )
            ) {
                return $kat;
            }

            if (
                str_contains($namaKat, 'herbal')
                && (
                    str_contains($teks, 'herbal')
                    || str_contains($teks, 'jamu')
                )
            ) {
                return $kat;
            }

            if (
                str_contains($namaKat, 'alat kesehatan')
                && (
                    str_contains($teks, 'alat kesehatan')
                    || str_contains($teks, 'alkes')
                    || str_contains($teks, 'alat medis')
                )
            ) {
                return $kat;
            }

            if (
                str_contains($namaKat, 'bebas')
                && str_contains($teks, 'obat bebas')
            ) {
                return $kat;
            }

            if (
                str_contains($namaKat, 'keras')
                && str_contains($teks, 'obat keras')
            ) {
                return $kat;
            }
        }

        return null;
    }

    private function handleCategoryMedicines(
        $user,
        array $kategori,
        Collection $obat
    ) {
        $kategoriId = $kategori['id'];
        $namaKategori = $kategori['nama_kategori'];

        $produk = $obat->filter(function ($item) use (
            $kategoriId,
            $namaKategori
        ) {
            return (
                isset($item['kategori_id'])
                && $item['kategori_id'] == $kategoriId
            ) || (
                isset($item['kategori'])
                && strcasecmp(
                    $item['kategori'],
                    $namaKategori
                ) === 0
            );
        })->values();

        if ($produk->isEmpty()) {
            return $this->sendReply(
                $user,
                "Saat ini belum ada produk aktif yang terdaftar "
                . "dalam kategori {$namaKategori} di apotek kami."
            );
        }

        $reply = "Berikut daftar obat dalam kategori {$namaKategori}:\n\n";

        foreach ($produk as $index => $item) {
            $status = $item['stok'] > 0
                ? "Tersedia ({$item['stok']} {$item['satuan']})"
                : "Habis";

            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format(
                    (float) $item['harga_umum'],
                    0,
                    ',',
                    '.'
                )
                : 'Belum tercatat';

            $reply .= ($index + 1) . ". {$item['nama_obat']}\n"
                . "   - Status: {$status}\n"
                . "   - Harga: {$harga}\n";

            if (!empty($item['deskripsi'])) {
                $reply .= "   - Deskripsi: {$item['deskripsi']}\n";
            }

            $reply .= "\n";
        }

        $reply .= "Data di atas bersumber langsung dari database inventaris apotek.";

        return $this->sendReply($user, $reply);
    }

    /*
    |--------------------------------------------------------------------------
    | 8. PENCARIAN OBAT TERTENTU
    |--------------------------------------------------------------------------
    */

    private function findMedicineInMessage(
        string $teks,
        Collection $obat
    ): Collection {
        $teks = $this->normalizeText($teks);

        $cocokLengkap = $obat->filter(function ($item) use ($teks) {
            $nama = $this->normalizeText(
                $item['nama_obat'] ?? ''
            );

            return $nama !== ''
                && str_contains($teks, $nama);
        });

        if ($cocokLengkap->isNotEmpty()) {
            return $cocokLengkap->values();
        }

        $cocokDasar = $obat->filter(function ($item) use ($teks) {
            $nama = $this->normalizeText(
                $item['nama_obat'] ?? ''
            );

            if ($nama === '') {
                return false;
            }

            $namaInti = preg_replace(
                '/\b\d+(?:[.,]\d+)?\s(?:mg|g|ml|l|ply|tablet|kapsul|sachet|botol|box|unit)\b/iu',
                '',
                $nama
            );

            $namaInti = trim(
                preg_replace('/\s+/u', ' ', $namaInti)
            );

            if (
                mb_strlen($namaInti) >= 3
                && str_contains($teks, $namaInti)
            ) {
                return true;
            }

            $kataKata = preg_split(
                '/\s+/u',
                $namaInti
            );

            $kataAbaikan = [
                'obat',
                'medis',
                'digital',
                'extract',
                'ekstrak',
                'dewasa',
                'anak',
                'plus',
                'forte'
            ];

            foreach ($kataKata as $kata) {
                $kata = trim($kata);

                if (
                    mb_strlen($kata) >= 4
                    && !in_array($kata, $kataAbaikan, true)
                ) {
                    if (
                        preg_match(
                            '/\b' . preg_quote($kata, '/') . '\b/iu',
                            $teks
                        )
                    ) {
                        return true;
                    }
                }
            }

            return false;
        });

        return $cocokDasar->values();
    }

    private function findMedicineInHistory(
        Collection $riwayat,
        Collection $obat
    ): Collection {
        foreach (
            $riwayat
                ->where('role', 'user')
                ->sortByDesc('id')
            as $pesan
        ) {
            $hasil = $this->findMedicineInMessage(
                $this->normalizeText(
                    $pesan->content ?? ''
                ),
                $obat
            );

            if ($hasil->isNotEmpty()) {
                return $hasil;
            }
        }

        return collect();
    }

    private function handleMedicineStock(
        $user,
        Collection $obatCocok
    ) {
        $reply = $obatCocok->map(function ($item) {
            $status = $item['stok'] > 0
                ? 'Tersedia'
                : 'Habis (Kosong)';

            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format(
                    (float) $item['harga_umum'],
                    0,
                    ',',
                    '.'
                )
                : 'Belum tercatat';

            return "💊 {$item['nama_obat']}\n"
                . "- Kategori: {$item['kategori']}\n"
                . "- Status: {$status}\n"
                . "- Stok: {$item['stok']} {$item['satuan']}\n"
                . "- Harga umum: {$harga}";
        })->implode("\n\n");

        $reply .= "\n\nInformasi diambil langsung dari data inventaris database apotek.";

        return $this->sendReply($user, $reply);
    }

    private function handleMedicineDetail(
        $user,
        Collection $obatCocok
    ) {
        $reply = $obatCocok->map(function ($item) {
            $status = $item['stok'] > 0
                ? "Tersedia ({$item['stok']} {$item['satuan']})"
                : 'Habis';

            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format(
                    (float) $item['harga_umum'],
                    0,
                    ',',
                    '.'
                )
                : 'Belum tercatat';

            $desc = !empty($item['deskripsi'])
                ? $item['deskripsi']
                : 'Belum ada deskripsi khusus.';

            return "💊 {$item['nama_obat']}\n"
                . "- Kategori: {$item['kategori']}\n"
                . "- Kegunaan / Khasiat: {$desc}\n"
                . "- Status Stok: {$status}\n"
                . "- Harga: {$harga}";
        })->implode("\n\n");

        $reply .= "\n\nCatatan: Pastikan membaca aturan pakai atau "
            . "berkonsultasi dengan apoteker sebelum mengonsumsi obat.";

        return $this->sendReply($user, $reply);
    }

    private function handleMedicineOverview(
        $user,
        Collection $obatCocok
    ) {
        $reply = $obatCocok->map(function ($item) {
            $status = $item['stok'] > 0
                ? "Tersedia ({$item['stok']} {$item['satuan']})"
                : 'Habis';

            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format(
                    (float) $item['harga_umum'],
                    0,
                    ',',
                    '.'
                )
                : 'Belum tercatat';

            $desc = !empty($item['deskripsi'])
                ? "\n- Deskripsi: {$item['deskripsi']}"
                : '';

            return "💊 {$item['nama_obat']}\n"
                . "- Kategori: {$item['kategori']}\n"
                . "- Status Stok: {$status}\n"
                . "- Harga: {$harga}"
                . $desc;
        })->implode("\n\n");

        $reply .= "\n\nInformasi ini sesuai dengan data inventaris apotek.";

        return $this->sendReply($user, $reply);
    }

    private function isMedicineDosageOrUsageQuestion(string $teks): bool
    {
        return (bool) preg_match(
            '/('
            . 'berapa\s+kali'
            . '|kali\s+sehari'
            . '|tiap\s+berapa\s+jam'
            . '|setiap\s+berapa\s+jam'
            . '|aturan\s+(pakai|minum|penggunaan|konsumsi)'
            . '|saran\s+(pemakaian|penggunaan|minum)'
            . '|petunjuk\s+(pemakaian|penggunaan)'
            . '|cara\s+(pakai|minum|mengonsumsi|konsumsi|menggunakan)'
            . '|pemakaian\s+(harian|obat|sehari)'
            . '|pemakaian'
            . '|dosis'
            . '|takaran'
            . '|sebelum\s+atau\s+sesudah\s+makan'
            . '|sesudah\s+makan|sebelum\s+makan'
            . '|kapan\s+diminum'
            . '|waktu\s+minum'
            . ')/u',
            $teks
        );
    }

    private function handleMedicineUsage(
        $user,
        Collection $obatCocok,
        string $teks
    ) {
        $item = $obatCocok->first();

        $namaObat = $item['nama_obat'];
        $kategori = $item['kategori'] ?? 'Obat';
        $deskripsi = $item['deskripsi'] ?? '';

        $aturanKhusus = $this->getKnownMedicineDosageGuidance(
            $namaObat
        );

        if ($aturanKhusus !== null) {
            $reply = "📋 Saran Pemakaian & Aturan Pakai: {$namaObat}\n"
                . "🏷️ Kategori: {$kategori}\n\n"
                . "{$aturanKhusus}\n\n"
                . "⚠️ Catatan Penting Apotek:\n"
                . "- Aturan pakai di atas adalah informasi umum.\n"
                . "- Dosis dan durasi pastinya wajib disesuaikan dengan "
                . "petunjuk apoteker atau resep dokter sesuai kondisi.";

            return $this->sendReply($user, $reply);
        }

        $infoDeskripsi = !empty($deskripsi)
            ? "\nKeterangan di sistem: {$deskripsi}\n"
            : '';

        $reply = "📋 Informasi Pemakaian: {$namaObat}\n"
            . "🏷️ Kategori: {$kategori}\n"
            . $infoDeskripsi . "\n"
            . "Secara umum, aturan pakai dan dosis obat harus "
            . "disesuaikan dengan bentuk sediaan, usia, dan anjuran dokter/apoteker.\n\n"
            . "⚠️ Petunjuk Umum Penggunaan:\n"
            . "- Periksa label kemasan untuk petunjuk pemakaian resmi.\n"
            . "- Tanyakan kepada apoteker mengenai aturan penggunaan.\n"
            . "- Jangan mengubah dosis sendiri tanpa arahan tenaga kesehatan.";

        return $this->sendReply($user, $reply);
    }

    private function getKnownMedicineDosageGuidance(
        string $namaObat
    ): ?string {
        $nama = mb_strtolower($namaObat);

        if (
            str_contains($nama, 'asam mefenamat')
            || str_contains($nama, 'mefenamic')
        ) {
            return "🔹 Frekuensi Pemakaian:\n"
                . "- Ikuti dosis pada etiket/resep atau arahan apoteker.\n\n"
                . "🔹 Waktu & Cara Konsumsi:\n"
                . "- Gunakan sesuai petunjuk pada kemasan atau tenaga kesehatan.\n\n"
                . "🔹 Perhatian:\n"
                . "- Jangan digunakan jika memiliki alergi terhadap obat sejenis "
                . "tanpa berkonsultasi dengan tenaga kesehatan.";
        }

        if (
            str_contains($nama, 'paracetamol')
            || str_contains($nama, 'panadol')
            || str_contains($nama, 'parasetamol')
        ) {
            return "🔹 Penggunaan:\n"
                . "- Gunakan sesuai dosis pada kemasan atau arahan apoteker/dokter.\n\n"
                . "🔹 Waktu & Cara Konsumsi:\n"
                . "- Dapat dikonsumsi sesuai petunjuk produk.\n\n"
                . "🔹 Catatan:\n"
                . "- Periksa kandungan obat lain yang sedang digunakan agar tidak "
                . "mengonsumsi paracetamol dari beberapa produk sekaligus.";
        }

        if (
            str_contains($nama, 'amoxicillin')
            || str_contains($nama, 'amoksisilin')
        ) {
            return "🔹 PENTING (Antibiotik):\n"
                . "- Gunakan hanya sesuai resep atau arahan dokter/apoteker.\n"
                . "- Jangan memulai, menghentikan, atau mengubah dosis antibiotik sendiri.";
        }

        if (str_contains($nama, 'ibuprofen')) {
            return "🔹 Penggunaan:\n"
                . "- Ikuti dosis dan aturan pakai pada kemasan atau arahan apoteker/dokter.\n\n"
                . "🔹 Perhatian:\n"
                . "- Konsultasikan dengan tenaga kesehatan bila memiliki masalah lambung, "
                . "ginjal, atau kondisi khusus lainnya.";
        }

        if (str_contains($nama, 'cetirizine')) {
            return "🔹 Penggunaan:\n"
                . "- Gunakan sesuai dosis pada kemasan atau arahan apoteker/dokter.\n\n"
                . "🔹 Perhatian:\n"
                . "- Obat ini dapat menyebabkan kantuk pada sebagian orang.";
        }

        if (str_contains($nama, 'omeprazole')) {
            return "🔹 Penggunaan:\n"
                . "- Gunakan sesuai petunjuk pada kemasan atau arahan dokter/apoteker.\n\n"
                . "🔹 Perhatian:\n"
                . "- Bentuk dan kekuatan obat dapat berbeda, sehingga ikuti etiket produk.";
        }

        if (str_contains($nama, 'cefixime')) {
            return "🔹 PENTING (Antibiotik):\n"
                . "- Gunakan hanya berdasarkan resep atau arahan tenaga kesehatan.\n"
                . "- Jangan mengubah dosis atau durasi penggunaan sendiri.";
        }

        if (str_contains($nama, 'vitamin c')) {
            return "🔹 Penggunaan:\n"
                . "- Gunakan sesuai dosis yang tercantum pada kemasan.\n\n"
                . "🔹 Perhatian:\n"
                . "- Jangan melebihi dosis yang dianjurkan pada produk.";
        }

        if (
            str_contains($nama, 'antangin')
            || str_contains($nama, 'tolak angin')
        ) {
            return "🔹 Penggunaan:\n"
                . "- Gunakan sesuai petunjuk pada kemasan produk.\n\n"
                . "🔹 Perhatian:\n"
                . "- Perhatikan kandungan dan aturan pakai pada kemasan.";
        }

        if (str_contains($nama, 'metformin')) {
            return "🔹 Penggunaan:\n"
                . "- Gunakan sesuai resep atau arahan dokter/apoteker.\n\n"
                . "🔹 Perhatian:\n"
                . "- Jangan mengubah dosis atau menghentikan obat diabetes secara mandiri.";
        }

        return null;
    }

    private function isStockOrPriceQuestion(string $teks): bool
    {
        if ($this->isMedicineDosageOrUsageQuestion($teks)) {
            return false;
        }

        return (bool) preg_match(
            '/('
            . 'stok|harga|ketersediaan|tersedia|ready|habis|kosong|'
            . 'harganya|jumlah\s+stok|berapa\s+(harga|stok|jumlahnya|biaya|rupiah)|'
            . 'masih\s+ada|ada\s+nggak|ada\s+gak|ada\s+tidak|punya|jual|beli|'
            . 'berapaan'
            . ')/u',
            $teks
        );
    }

    private function isMedicineDetailQuestion(string $teks): bool
    {
        return (bool) preg_match(
            '/('
            . 'buat\s+apa|untuk\s+apa|obat\s+apa|fungsi|kegunaan|'
            . 'manfaat|khasiat|indikasi|deskripsi|keterangan|'
            . 'cara\s+kerja|mengobati\s+apa'
            . ')/u',
            $teks
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PERBAIKAN:
    | DETEKSI PENCARIAN OBAT TERTENTU
    |--------------------------------------------------------------------------
    */

    private function isDirectMedicineStockQuery(string $teks): bool
    {
        /*
         * Jika kalimat mengandung konteks inventaris umum,
         * jangan pernah anggap sebagai nama obat tertentu.
         */
        if (preg_match(
            '/('
            . 'stok\s+obat'
            . '|ketersediaan\s+obat'
            . '|ada\s+obat'
            . '|ada\s+stok'
            . '|obat\s+yang\s+(tersedia|ready|aktif)'
            . '|obat\s+apa\s+saja'
            . '|obat\s+apa\s+aja'
            . '|daftar\s+obat'
            . '|list\s+obat'
            . '|produk\s+apa\s+saja'
            . '|barang\s+apa\s+saja'
            . '|masih\s+ada\s+obat'
            . '|obat\s+terbaru'
            . '|obat\s+baru'
            . '|tambahan\s+obat'
            . '|penambahan\s+obat'
            . ')/u',
            $teks
        )) {
            return false;
        }

        /*
         * Baru dianggap sebagai pencarian obat tertentu jika
         * struktur kalimatnya memang menyerupai:
         *
         * "apakah ada amoxicillin?"
         * "ada paracetamol?"
         * "stok vitamin c?"
         * "harga amoxicillin?"
         */
        return (bool) preg_match(
            '/^(?:'
            . 'apakah\s+ada'
            . '|ada'
            . '|stok'
            . '|harga'
            . '|jual'
            . '|tersedia'
            . ')\s+[a-zA-Z0-9\s]{3,30}'
            . '(?:\s+(?:gak|nggak|tidak|berapa|masih\s+ada|ready))?'
            . '[?!.\s]*$/u',
            $teks
        );
    }

    private function handleUnknownMedicineQuery($user)
    {
        return $this->sendReply(
            $user,
            "Maaf, obat atau produk yang kamu maksud saat ini "
            . "tidak terdaftar atau belum tersedia di inventaris apotek kami.\n\n"
            . "Kamu bisa mengetik 'daftar obat tersedia' untuk melihat obat "
            . "yang ready, atau menanyakan obat lain yang kami sediakan. 😊"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PERBAIKAN:
    | PERTANYAAN STOK UMUM
    |--------------------------------------------------------------------------
    */

    private function isGeneralStockQuestion(string $teks): bool
    {
        return (bool) preg_match(
            '/('
            . 'stok\s+obat'
            . '|stok\s+yang\s+tersedia'
            . '|stok\s+tersedia'
            . '|ada\s+stok'
            . '|ada\s+stok\s+obat'
            . '|ketersediaan\s+obat'
            . '|ketersediaan\s+stok'
            . '|masih\s+ada\s+obat'
            . '|masih\s+ada\s+stok'
            . '|ada\s+obat\s+nggak'
            . '|ada\s+obat\s+gak'
            . '|ada\s+obat\s+tidak'
            . '|ada\s+obat'
            . '|obat\s+yang\s+tersedia'
            . '|obat\s+yang\s+ready'
            . '|obat\s+yang\s+aktif'
            . '|obatnya\s+masih\s+ada'
            . '|apakah\s+ada\s+obat'
            . '|apakah\s+ada\s+stok'
            . ')/u',
            $teks
        );
    }

    private function hasMedicineReference(string $teks): bool
    {
        return (bool) preg_match(
            '/obatnya|obat itu|yang tadi|obat tersebut|'
            . 'harganya|jumlahnya|stoknya|masih ada/u',
            $teks
        );
    }

    private function isGeneralMedicineQuery(string $teks): bool
    {
        return (bool) preg_match(
            '/('
            . 'berapa\s+obat'
            . '|ada\s+berapa'
            . '|semua\s+obat'
            . '|daftar\s+obat'
            . '|list\s+obat'
            . '|obat\s+apa\s+saja'
            . '|obat\s+apa\s+aja'
            . '|kategori\s+obat'
            . '|jenis\s+obat'
            . '|stok\s+obat'
            . '|obat\s+(yang\s+)?(habis|abis|kosong|tersedia|ready|aktif)'
            . '|stok(?:nya)?\s(?:0|nol|habis|abis|kosong)'
            . ')/u',
            $teks
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PERBAIKAN:
    | PERTANYAAN STOK UMUM SEKARANG MENGGUNAKAN DATA TERBARU
    |--------------------------------------------------------------------------
    */

    private function handleGeneralStockQuestion(
        $user,
        Collection $obat
    ) {
        $tersedia = $obat
            ->where('stok', '>', 0)
            ->values();

        $totalTersedia = $tersedia->count();

        if ($tersedia->isEmpty()) {
            return $this->sendReply(
                $user,
                'Maaf ya, saat ini belum ada stok obat yang tersedia '
                . 'di apotek kami.'
            );
        }

        /*
         * Jika user hanya bertanya apakah ada stok,
         * tetap berikan informasi aktual dari database.
         */
        $reply = "Ya, saat ini ada {$totalTersedia} obat yang memiliki stok tersedia di apotek kami:\n\n";

        foreach ($tersedia as $index => $item) {
            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format(
                    (float) $item['harga_umum'],
                    0,
                    ',',
                    '.'
                )
                : 'Belum tercatat';

            $reply .= ($index + 1) . ". {$item['nama_obat']}\n"
                . "   - Kategori: {$item['kategori']}\n"
                . "   - Stok: {$item['stok']} {$item['satuan']}\n"
                . "   - Harga: {$harga}\n\n";
        }

        $reply .= "Data ini dicek langsung dari database inventaris apotek saat ini.";

        return $this->sendReply($user, $reply);
    }

    /*
    |--------------------------------------------------------------------------
    | 9. KONTEKS LANJUTAN STOK
    |--------------------------------------------------------------------------
    */

    private function handleStockFollowUp(
        string $teks,
        Collection $riwayat,
        Collection $obat
    ): ?string {
        $terakhir = $riwayat
            ->where('role', 'assistant')
            ->last();

        if (!$terakhir) {
            return null;
        }

        $jawaban = $this->normalizeText(
            $terakhir->content ?? ''
        );

        $tentangStok = (
            str_contains($jawaban, 'stok')
            && (
                str_contains($jawaban, 'habis')
                || str_contains($jawaban, 'kosong')
                || str_contains($jawaban, 'tersedia')
            )
        );

        if (!$tentangStok) {
            return null;
        }

        /*
         * Jika user menanyakan kenapa obat tidak ada,
         * jangan langsung percaya jawaban assistant sebelumnya.
         *
         * Kita cek dulu kondisi database sekarang.
         */
        if (preg_match(
            '/kok habis|kenapa habis|mengapa habis|'
            . 'kok kosong|kenapa kosong|mengapa kosong|'
            . 'kenapa tidak ada|kok tidak ada/u',
            $teks
        )) {
            $tersediaSekarang = $obat
                ->where('stok', '>', 0)
                ->values();

            if ($tersediaSekarang->isNotEmpty()) {
                return "Aku cek lagi data inventaris terbaru. 😊 "
                    . "Saat ini masih ada {$tersediaSekarang->count()} obat "
                    . "yang memiliki stok tersedia di apotek.";

            }

            return "Maaf banget ya. 😔 Setelah dicek kembali, "
                . "saat ini memang belum ada obat yang memiliki stok tersedia "
                . "di database inventaris apotek.";
        }

        if (preg_match(
            '/^(yah+|waduh|aduh|duh|huhu)[!. ]$/u',
            $teks
        )) {
            /*
             * Cek ulang database sebelum memberikan respons.
             */
            $tersediaSekarang = $obat
                ->where('stok', '>', 0)
                ->values();

            if ($tersediaSekarang->isNotEmpty()) {
                return "Ternyata setelah dicek lagi, saat ini masih ada "
                    . "{$tersediaSekarang->count()} obat yang tersedia di apotek. 😊";
            }

            return "Iya, maaf ya. 😔 Saat ini data inventaris memang "
                . "belum menunjukkan adanya stok obat yang tersedia.";
        }

        if (preg_match(
            '/terus gimana|lalu bagaimana|jadi gimana/u',
            $teks
        )) {
            $tersediaSekarang = $obat
                ->where('stok', '>', 0)
                ->values();

            if ($tersediaSekarang->isNotEmpty()) {
                return "Kalau begitu, aku cek kondisi terbaru dari inventaris. "
                    . "Saat ini masih ada {$tersediaSekarang->count()} obat "
                    . "yang tersedia. Kamu bisa menyebutkan nama obat yang dicari.";
            }

            return "Kalau obat yang kamu cari sedang tidak tersedia, "
                . "kamu bisa mengetik 'daftar obat tersedia' untuk melihat "
                . "produk lain yang saat ini memiliki stok.";
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | 10. PERCAKAPAN RINGAN
    |--------------------------------------------------------------------------
    */

    private function handleSmallTalk(string $teks): ?string
    {
        if (preg_match(
            '/^(halo|hai|hi|hey|pagi|selamat pagi|selamat siang|'
            . 'selamat sore|selamat malam)[!. ]$/u',
            $teks
        )) {
            return 'Hai! 😊 Ada yang bisa aku bantu seputar informasi apotek atau obat hari ini?';
        }

        if (preg_match(
            '/^(makasih|terima kasih|thanks|thank you|thx)[!. ]$/u',
            $teks
        )) {
            return 'Sama-sama! 😊 Semoga lekas sehat dan harimu menyenangkan.';
        }

        if (preg_match(
            '/apa kabar kamu|kamu gimana|kamu baik-baik saja/u',
            $teks
        )) {
            return 'Aku baik, terima kasih sudah bertanya! 😊 Kamu sendiri bagaimana kabarnya?';
        }

        if (preg_match(
            '/^(waduh|yah+|aduh|duh|huhu)[!. ]$/u',
            $teks
        )) {
            return 'Ada apa nih? 😊 Kalau butuh informasi obat atau mau cerita keluhan, aku siap membantu.';
        }

        if (preg_match(
            '/\b(sedih|kecewa|galau|nangis|menangis)\b/u',
            $teks
        )) {
            return 'Aduh, aku ikut prihatin. ❤️ Jika ada keluhan fisik atau kesehatan yang dirasakan, boleh ceritakan ya.';
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | 11. PEMBUKAAN KELUHAN KESEHATAN
    |--------------------------------------------------------------------------
    */

    private function handleHealthOpening(string $teks): ?string
    {
        if (!preg_match(
            '/aku sakit|saya sakit|badanku nggak enak|'
            . 'badan saya tidak enak|aku tidak enak badan|'
            . 'saya tidak enak badan|aku lagi sakit/u',
            $teks
        )) {
            return null;
        }

        if ($this->containsHealthSymptoms($teks)) {
            return null;
        }

        return "Aduh, semoga kamu lekas membaik ya. ❤️ "
            . "Apa keluhan yang paling terasa sekarang? "
            . "Misalnya pusing, demam, batuk, pilek, mual, "
            . "atau keluhan lainnya.";
    }

    /*
    |--------------------------------------------------------------------------
    | 12. DETEKSI GEJALA KESEHATAN
    |--------------------------------------------------------------------------
    */

    private function containsHealthSymptoms(string $teks): bool
    {
        return (bool) preg_match(
            '/demam|panas|pusing|sakit kepala|lemas|lemah|'
            . 'batuk|pilek|mual|muntah|diare|sakit perut|'
            . 'nyeri|gatal|sesak|pegal|menggigil|radang|'
            . 'tenggorokan|bersin|flu|ruam|bengkak|'
            . 'susah tidur|sulit tidur|sembelit|konstipasi|'
            . 'maag|asam lambung|perut kembung|'
            . 'hidung tersumbat|hidung mampet|'
            . 'tenggorokan sakit|tenggorokan gatal|'
            . 'mata gatal|mata merah|'
            . 'sakit gigi|gigi sakit|'
            . 'masuk angin|meriang/u',
            $teks
        );
    }

    private function getRecentUserConversation(
        Collection $riwayat,
        string $teksSekarang
    ): string {
        $pesan = $riwayat
            ->where('role', 'user')
            ->pluck('content')
            ->take(-8)
            ->toArray();

        $pesan[] = $teksSekarang;

        return $this->normalizeText(
            implode(' ', $pesan)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 13. PERCAKAPAN LANJUTAN KESEHATAN
    |--------------------------------------------------------------------------
    */

    private function handleHealthConversation(
        string $teks,
        Collection $riwayat
    ): ?string {
        $konteks = $this->getRecentUserConversation(
            $riwayat,
            $teks
        );

        if (!$this->containsHealthSymptoms($konteks)) {
            return null;
        }

        $adaDemam = preg_match(
            '/demam|badan panas|menggigil/u',
            $konteks
        );

        $adaPusing = preg_match(
            '/pusing|sakit kepala|kepala berat/u',
            $konteks
        );

        $adaLemas = preg_match(
            '/lemas|lemah|tidak bertenaga/u',
            $konteks
        );

        $sudahDitanyaSuhu = $this->assistantAskedAbout(
            $riwayat,
            [
                'ukur suhu',
                'suhu tubuh',
                'termometer',
                'berapa suhu'
            ]
        );

        $sudahMenyebutSuhu = preg_match(
            '/suhu.\d|\d{2}(?:[.,]\d)?\sderajat/u',
            $konteks
        );

        if (
            $adaDemam
            && !$sudahMenyebutSuhu
            && !$sudahDitanyaSuhu
        ) {
            return "Aku mengerti. Kalau ada termometer, "
                . "apakah kamu bisa mengukur suhu tubuhmu? "
                . "Tidak apa-apa kalau belum bisa.";
        }

        $sudahDitanyaJenisPusing = $this->assistantAskedAbout(
            $riwayat,
            [
                'kepala berputar',
                'jenis pusing',
                'terasa melayang'
            ]
        );

        $sudahMenjelaskanPusing = preg_match(
            '/berputar|melayang|mau pingsan|kepala terasa berat/u',
            $konteks
        );

        if (
            $adaPusing
            && !$adaDemam
            && !$sudahDitanyaJenisPusing
            && !$sudahMenjelaskanPusing
        ) {
            return "Pusingnya terasa seperti kepala berputar, melayang, "
                . "atau lebih seperti terasa berat?";
        }

        $sudahDitanyaMakan = $this->assistantAskedAbout(
            $riwayat,
            [
                'bisa makan',
                'bisa minum',
                'makan dan minum'
            ]
        );

        $sudahMenyebutMakan = preg_match(
            '/makan|minum|nafsu makan|sulit makan|sulit minum/u',
            $konteks
        );

        if (
            ($adaDemam || $adaLemas)
            && !$sudahDitanyaMakan
            && !$sudahMenyebutMakan
        ) {
            return "Apakah kamu masih bisa makan dan minum seperti biasanya? "
                . "Kalau sulit, boleh ceritakan juga.";
        }

        return null;
    }

    private function assistantAskedAbout(
        Collection $riwayat,
        array $kataKunci
    ): bool {
        foreach (
            $riwayat
                ->where('role', 'assistant')
                ->sortByDesc('id')
                ->take(5) as $pesan
        ) {
            $isi = $this->normalizeText(
                $pesan->content ?? ''
            );

            foreach ($kataKunci as $kata) {
                if (str_contains($isi, $kata)) {
                    return true;
                }
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | 14. DETEKSI PERMINTAAN REKOMENDASI OBAT
    |--------------------------------------------------------------------------
    */

    private function isMedicineRecommendationQuestion(
        string $teks
    ): bool {
        return (bool) preg_match(
            '/rekomendasi obat|obat apa untuk|obat apa buat|'
            . 'obat apa yang cocok|obat yang cocok|'
            . 'obat buat aku|obat untuk keluhan|obat untuk sakit|'
            . 'obat yang manjur|obat yang ampuh|'
            . 'bisa minum obat apa|sebaiknya minum obat apa|'
            . 'obat apa yang bagus|ada obat untuk|'
            . 'obat apa yang harus diminum|obat apa yang bisa diminum|'
            . 'saran obat|sarankan obat|obat yang pas/u',
            $teks
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TAMBAHAN:
    | TINDAKAN / PENANGANAN UMUM BERDASARKAN KELUHAN
    |--------------------------------------------------------------------------
    */

    private function getHealthActionAdvice(string $konteks): ?string
    {
        $tindakan = [];

        /*
         * DEMAM / MERIANG
         */
        if (preg_match(
            '/demam|badan panas|meriang|menggigil/u',
            $konteks
        )) {
            $tindakan[] = "🌡️ Untuk demam/meriang:\n"
                . "- Istirahat yang cukup.\n"
                . "- Minum air secara berkala agar tubuh tetap terhidrasi.\n"
                . "- Jika memungkinkan, ukur suhu tubuh dan pantau perubahannya.\n"
                . "- Gunakan pakaian yang nyaman dan tidak terlalu tebal.";
        }

        /*
         * BATUK
         */
        if (preg_match(
            '/batuk/u',
            $konteks
        )) {
            $tindakan[] = "😷 Untuk batuk:\n"
                . "- Istirahat yang cukup.\n"
                . "- Minum cukup cairan.\n"
                . "- Hindari asap rokok, debu, atau pemicu yang membuat batuk bertambah.\n"
                . "- Perhatikan apakah batuk semakin berat atau disertai sesak.";
        }

        /*
         * PILEK / HIDUNG TERSUMBAT
         */
        if (preg_match(
            '/pilek|hidung tersumbat|hidung mampet|bersin/u',
            $konteks
        )) {
            $tindakan[] = "🤧 Untuk pilek/hidung tersumbat:\n"
                . "- Istirahat yang cukup.\n"
                . "- Minum cukup cairan.\n"
                . "- Jaga kebersihan tangan dan hindari berbagi alat makan/minum.\n"
                . "- Hindari asap rokok dan udara yang membuat gejala semakin berat.";
        }

        /*
         * SAKIT TENGGOROKAN
         */
        if (preg_match(
            '/tenggorokan sakit|tenggorokan gatal|radang tenggorokan/u',
            $konteks
        )) {
            $tindakan[] = "🫗 Untuk keluhan tenggorokan:\n"
                . "- Minum cukup air.\n"
                . "- Pilih makanan/minuman yang tidak memperparah rasa tidak nyaman.\n"
                . "- Istirahatkan suara jika tenggorokan terasa sakit.";
        }

        /*
         * PUSING / SAKIT KEPALA
         */
        if (preg_match(
            '/pusing|sakit kepala|kepala berat/u',
            $konteks
        )) {
            $tindakan[] = "🧠 Untuk pusing/sakit kepala:\n"
                . "- Istirahat di tempat yang nyaman.\n"
                . "- Minum cukup air.\n"
                . "- Hindari aktivitas yang berisiko jika sedang pusing, misalnya mengendarai kendaraan.\n"
                . "- Jika pusing berat, berulang, atau disertai gejala lain yang mengkhawatirkan, minta bantuan tenaga kesehatan.";
        }

        /*
         * MUAL / MUNTAH
         */
        if (preg_match(
            '/mual|muntah/u',
            $konteks
        )) {
            $tindakan[] = "🤢 Untuk mual/muntah:\n"
                . "- Minum sedikit-sedikit tetapi lebih sering.\n"
                . "- Hindari makan dalam jumlah besar sekaligus.\n"
                . "- Pilih makanan yang ringan jika masih mampu makan.\n"
                . "- Perhatikan tanda kekurangan cairan.";
        }

        /*
         * DIARE
         */
        if (preg_match(
            '/diare/u',
            $konteks
        )) {
            $tindakan[] = "💧 Untuk diare:\n"
                . "- Prioritaskan cairan agar tidak kekurangan cairan.\n"
                . "- Pilih makanan yang ringan dan mudah ditoleransi.\n"
                . "- Jaga kebersihan tangan setelah dari toilet dan sebelum makan.";
        }

        /*
         * SAKIT PERUT / MAAG / ASAM LAMBUNG
         */
        if (preg_match(
            '/sakit perut|maag|asam lambung|perut kembung/u',
            $konteks
        )) {
            $tindakan[] = "🍽️ Untuk keluhan perut:\n"
                . "- Coba makan dalam porsi kecil dan teratur jika masih bisa makan.\n"
                . "- Hindari makanan atau minuman yang biasanya memperburuk keluhan.\n"
                . "- Jangan langsung berbaring setelah makan jika keluhan berkaitan dengan asam lambung.";
        }

        /*
         * GATAL / RUAM
         */
        if (preg_match(
            '/gatal|ruam/u',
            $konteks
        )) {
            $tindakan[] = "🧴 Untuk gatal/ruam:\n"
                . "- Hindari menggaruk area yang gatal.\n"
                . "- Hindari sementara produk atau bahan yang diduga memicu keluhan.\n"
                . "- Perhatikan apakah ruam menyebar atau muncul pembengkakan.";
        }

        /*
         * LEMAS
         */
        if (preg_match(
            '/lemas|lemah|tidak bertenaga/u',
            $konteks
        )) {
            $tindakan[] = "🛌 Untuk rasa lemas:\n"
                . "- Istirahat yang cukup.\n"
                . "- Pastikan tetap makan dan minum jika mampu.\n"
                . "- Hindari aktivitas berat sementara waktu.";
        }

        /*
         * SUSAH TIDUR
         */
        if (preg_match(
            '/susah tidur|sulit tidur|insomnia/u',
            $konteks
        )) {
            $tindakan[] = "😴 Untuk sulit tidur:\n"
                . "- Usahakan jadwal tidur dan bangun yang teratur.\n"
                . "- Kurangi penggunaan perangkat elektronik menjelang tidur.\n"
                . "- Buat lingkungan tidur yang tenang dan nyaman.";
        }

        if (empty($tindakan)) {
            return null;
        }

        return implode("\n\n", $tindakan);
    }

    /*
    |--------------------------------------------------------------------------
    | TAMBAHAN:
    | DETEKSI APAKAH USER MEMINTA TINDAKAN
    |--------------------------------------------------------------------------
    */

    private function isHealthActionQuestion(string $teks): bool
    {
        return (bool) preg_match(
            '/harus\s+ngapain|harus\s+apa|ngapain\s+aja|'
            . 'apa\s+yang\s+harus\s+dilakukan|'
            . 'yang\s+harus\s+dilakukan|'
            . 'cara\s+mengatasi|cara\s+menangani|'
            . 'cara\s+merawat|penanganannya\s+apa|'
            . 'penanganan|tindakan|apa\s+yang\s+bisa\s+dilakukan|'
            . 'gimana\s+cara\s+mengatasinya|'
            . 'gimana\s+ngatasinya/u',
            $teks
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TAMBAHAN:
    | JAWABAN TINDAKAN KESEHATAN
    |--------------------------------------------------------------------------
    */

    private function handleHealthActionQuestion(
        string $teks,
        Collection $riwayat
    ): ?string {
        $konteks = $this->getRecentUserConversation(
            $riwayat,
            $teks
        );

        if (!$this->containsHealthSymptoms($konteks)) {
            return null;
        }

        $tindakan = $this->getHealthActionAdvice(
            $konteks
        );

        if ($tindakan === null) {
            return null;
        }

        return "Beberapa hal umum yang bisa kamu lakukan:\n\n"
            . $tindakan
            . "\n\n"
            . "⚠️ Jika keluhan terasa berat, semakin parah, berlangsung lama, "
            . "atau muncul tanda bahaya, sebaiknya minta bantuan orang dewasa "
            . "dan konsultasikan dengan tenaga kesehatan.";
    }

    /*
    |--------------------------------------------------------------------------
    | 14B. REKOMENDASI OBAT + TINDAKAN
    |--------------------------------------------------------------------------
    */

    private function handleMedicineRecommendation(
        string $teks,
        Collection $riwayat
    ): string {
        $konteks = $this->getRecentUserConversation(
            $riwayat,
            $teks
        );

        if (!$this->containsHealthSymptoms($konteks)) {
            return "Boleh, aku bantu memahami informasi yang perlu diperhatikan. 😊 "
                . "Keluhan apa yang sedang kamu rasakan? Ceritakan gejala yang paling mengganggu terlebih dahulu.";
        }

        /*
         * TAMBAHAN:
         * Ambil tindakan umum berdasarkan gejala.
         */
        $tindakan = $this->getHealthActionAdvice($konteks);

        /*
         * Obat tetap 100% berasal dari database.
         */
        $aturan = AturanRekomendasiObat::query()
            ->where('is_verified', true)
            ->with(['obat.kategori'])
            ->get()
            ->filter(function ($item) {
                $obat = $item->obat;

                return $obat
                    && $obat->is_active
                    && $obat->is_approved;
            })
            ->values();

        /*
         * Jika database aturan rekomendasi kosong,
         * tindakan umum masih boleh diberikan.
         */
        if ($aturan->isEmpty()) {
            $reply = '';

            if ($tindakan !== null) {
                $reply .= "💡 Yang bisa dilakukan:\n\n"
                    . $tindakan
                    . "\n\n";
            }

            $reply .= "Aku belum menemukan aturan rekomendasi obat "
                . "terverifikasi di database apotek yang cocok dengan "
                . "keluhan tersebut.\n\n"
                . "Sebaiknya konsultasikan pilihan obat dengan apoteker "
                . "atau dokter agar sesuai dan aman untuk kondisimu.";

            return $reply;
        }

        /*
         * Cocokkan gejala user dengan gejala yang tersimpan
         * pada AturanRekomendasiObat.
         */
        $hasil = $aturan->map(function ($item) use ($konteks) {
            $frasa = preg_split(
                '/[,;\n|]+/u',
                $this->normalizeText(
                    $item->gejala ?? ''
                )
            );

            $skor = 0;

            foreach ($frasa as $kata) {
                $kata = trim($kata);

                if (mb_strlen($kata) < 3) {
                    continue;
                }

                /*
                 * Cocok langsung.
                 */
                if (str_contains($konteks, $kata)) {
                    $skor++;
                    continue;
                }

                /*
                 * TAMBAHAN:
                 * Beberapa variasi bahasa sehari-hari.
                 */
                $variasi = match ($kata) {
                    'demam' => [
                        'panas',
                        'badan panas',
                        'meriang'
                    ],

                    'sakit kepala' => [
                        'pusing',
                        'kepala sakit',
                        'kepala berat'
                    ],

                    'pilek' => [
                        'hidung mampet',
                        'hidung tersumbat',
                        'meler',
                        'bersin'
                    ],

                    'batuk' => [
                        'batuk-batuk'
                    ],

                    'mual' => [
                        'eneg'
                    ],

                    'diare' => [
                        'mencret',
                        'berak cair'
                    ],

                    'sakit perut' => [
                        'perut sakit',
                        'perut nyeri'
                    ],

                    'gatal' => [
                        'kulit gatal'
                    ],

                    default => [],
                };

                foreach ($variasi as $varian) {
                    if (str_contains($konteks, $varian)) {
                        $skor++;
                        break;
                    }
                }
            }

            return [
                'aturan' => $item,
                'skor' => $skor,
            ];
        })
            ->filter(fn ($item) => $item['skor'] > 0)
            ->sortByDesc('skor')
            ->values();

        /*
         * Tidak ditemukan obat yang cocok.
         * Tapi tindakan umum tetap diberikan.
         */
        if ($hasil->isEmpty()) {
            $reply = '';

            if ($tindakan !== null) {
                $reply .= "💡 Yang bisa dilakukan:\n\n"
                    . $tindakan
                    . "\n\n";
            }

            $reply .= "Aku belum menemukan aturan rekomendasi obat "
                . "terverifikasi yang cocok dengan gejala yang kamu ceritakan.\n\n"
                . "Bukan berarti pasti tidak ada obat yang sesuai, ya. "
                . "Pilihan obat tetap sebaiknya dikonfirmasi dengan apoteker "
                . "atau dokter.";

            return $reply;
        }

        /*
         * Satu obat hanya ditampilkan satu kali.
         */
        $hasil = $hasil
            ->unique(fn ($item) => $item['aturan']->obat_id)
            ->take(5)
            ->values();

        /*
         * Pisahkan obat tersedia dan habis.
         */
        $tersedia = $hasil
            ->filter(
                fn ($item) =>
                    (int) $item['aturan']->obat->stok > 0
            )
            ->values();

        $habis = $hasil
            ->filter(
                fn ($item) =>
                    (int) $item['aturan']->obat->stok <= 0
            )
            ->values();

        /*
         * Jika semua obat yang cocok sedang habis,
         * tetap tampilkan tindakan.
         */
        if ($tersedia->isEmpty()) {
            $reply = '';

            if ($tindakan !== null) {
                $reply .= "💡 Yang bisa dilakukan:\n\n"
                    . $tindakan
                    . "\n\n";
            }

            $reply .= "Aku menemukan informasi obat yang berkaitan "
                . "dengan keluhanmu, tetapi stoknya saat ini sedang habis "
                . "di apotek.\n\n"
                . "Silakan bicarakan alternatifnya dengan apoteker.";

            return $reply;
        }

        /*
         * ==========================================================
         * TAMBAHAN:
         * JAWABAN SEKARANG DIMULAI DENGAN TINDAKAN
         * LALU OBAT DARI DATABASE
         * ==========================================================
         */
        $reply = '';

        if ($tindakan !== null) {
            $reply .= "💡 Yang bisa dilakukan:\n\n"
                . $tindakan
                . "\n\n";
        }

        $reply .= "💊 Obat yang relevan di database apotek:\n\n";

        foreach ($tersedia as $index => $item) {
            $aturanObat = $item['aturan'];
            $obat = $aturanObat->obat;

            $reply .= ($index + 1)
                . ". {$obat->nama_obat}\n";

            if (!empty($aturanObat->indikasi)) {
                $reply .= "   - Indikasi: {$aturanObat->indikasi}\n";
            }

            $reply .= "   - Stok: {$obat->stok} {$obat->satuan}\n";

            if (!empty($aturanObat->peringatan)) {
                $reply .= "   - Peringatan: {$aturanObat->peringatan}\n";
            }

            if (!empty($aturanObat->kontraindikasi)) {
                $reply .= "   - Perhatian: {$aturanObat->kontraindikasi}\n";
            }

            $reply .= "\n";
        }

        if ($habis->isNotEmpty()) {
            $reply .= "📦 Catatan stok: sebagian obat yang relevan "
                . "dengan keluhanmu sedang habis di apotek.\n\n";
        }

        $reply .= "⚠️ Penting: Informasi di atas bukan diagnosis "
            . "atau resep resmi. Kecocokan obat tetap perlu dinilai oleh "
            . "apoteker atau dokter, terutama jika gejala berat, menetap, "
            . "atau kamu memiliki kondisi kesehatan tertentu.";

        return $reply;
    }

    /*
    |--------------------------------------------------------------------------
    | TAMBAHAN:
    | JIKA USER LANGSUNG BERTANYA "HARUS NGAPAIN?"
    |--------------------------------------------------------------------------
    */

    private function handleDirectHealthAction(
        string $teks,
        Collection $riwayat
    ): ?string {
        if (!$this->isHealthActionQuestion($teks)) {
            return null;
        }

        return $this->handleHealthActionQuestion(
            $teks,
            $riwayat
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 15. TANDA BAHAYA KESEHATAN
    |--------------------------------------------------------------------------
    */

    private function hasEmergencySymptoms(string $teks): bool
    {
        $pola = [
            '/sesak napas berat/u',
            '/sulit bernapas/u',
            '/susah bernapas/u',
            '/tidak bisa bernapas/u',
            '/nyeri dada hebat/u',
            '/dada terasa sangat sakit/u',
            '/pingsan/u',
            '/tidak sadarkan diri/u',
            '/kejang/u',
            '/bibir membiru/u',
            '/perdarahan hebat/u',
            '/muntah darah/u',
            '/wajah mencong/u',
            '/lemah mendadak sebelah tubuh/u',
            '/lidah membengkak/u',
            '/tenggorokan membengkak/u',
            '/reaksi alergi berat/u',
        ];

        foreach ($pola as $regex) {
            if (preg_match($regex, $teks)) {
                return true;
            }
        }

        return false;
    }

    private function emergencyReply(): string
    {
        return "Aku khawatir dengan gejala yang kamu ceritakan. ❤️ "
            . "Ini bisa menjadi tanda kondisi serius yang membutuhkan "
            . "pertolongan medis segera.\n\n"
            . "Tolong segera beri tahu orang dewasa yang kamu percaya "
            . "dan minta bantuan untuk mendapatkan pertolongan medis. "
            . "Jangan menunda pertolongan darurat untuk mencari obat melalui chat.";
    }

    /*
    |--------------------------------------------------------------------------
    | 16. SYSTEM PROMPT UNTUK OLLAMA
    |--------------------------------------------------------------------------
    */

    private function buildSystemPrompt(
        Collection $obat,
        Collection $kategori,
        $user = null
    ): string {
        $namaPengguna = 'Pengguna';

        if ($user !== null && !empty($user->name)) {
            $namaPengguna = trim($user->name);
        }

        $daftarObatTeks = '';

        foreach ($obat as $i => $item) {
            $status = $item['stok'] > 0
                ? "Tersedia ({$item['stok']} {$item['satuan']})"
                : "HABIS (0 {$item['satuan']})";

            $harga = is_numeric($item['harga_umum'])
                ? 'Rp ' . number_format(
                    (float) $item['harga_umum'],
                    0,
                    ',',
                    '.'
                )
                : 'Belum tercatat';

            $desc = !empty($item['deskripsi'])
                ? " | Ket: {$item['deskripsi']}"
                : '';

            $daftarObatTeks .= ($i + 1)
                . ". {$item['nama_obat']} | Kategori: {$item['kategori']} "
                . "| Stok: {$status} | Harga: {$harga}{$desc}\n";
        }

        $daftarKategoriTeks = '';

        foreach ($kategori as $k => $kat) {
            $daftarKategoriTeks .= "- {$kat['nama_kategori']}: "
                . "{$kat['total_obat']} jenis obat "
                . "({$kat['obat_tersedia']} tersedia)\n";
        }

        return <<<PROMPT
Kamu adalah Asisten Virtual Apotek yang ramah, sopan, empatik, dan berintegritas tinggi.
Kamu melayani pengguna bernama {$namaPengguna}.

==================================================
DATA RESMI DATABASE APOTEK
==================================================

KATEGORI OBAT RESMI:
{$daftarKategoriTeks}

DAFTAR INVENTARIS PRODUK SAAT INI:
{$daftarObatTeks}

==================================================
ATURAN WAJIB DAN KETAT
==================================================

1. HANYA gunakan data obat, stok, kategori, dan harga dari daftar resmi di atas.

2. DILARANG KERAS mengarang nama obat, stok, atau harga.

3. Jika pengguna menanyakan obat yang TIDAK ADA pada daftar inventaris,
   jawab dengan jujur bahwa obat tersebut tidak terdaftar atau belum tersedia.

4. Jika stok suatu obat tercatat HABIS (0), katakan bahwa stok obat tersebut habis.

5. Jangan mendiagnosis penyakit secara pasti.

6. Jangan membuat resep atau memberikan dosis berbahaya yang tidak berasal
   dari informasi resmi.

7. Untuk keluhan kesehatan, berikan saran umum yang aman seperti istirahat,
   menjaga cairan, memantau gejala, dan menyarankan konsultasi tenaga kesehatan
   bila diperlukan.

8. Jika pengguna meminta rekomendasi obat, gunakan hanya obat yang benar-benar
   terdapat dalam database resmi.

9. Jika tidak ada obat yang cocok di database, jangan mengarang obat alternatif.

10. Jawab secara ringkas, jelas, ramah, dan solutif.

11. DATA INVENTARIS DI ATAS ADALAH DATA TERBARU YANG DIBERIKAN OLEH
    CONTROLLER PADA REQUEST INI. JANGAN MENGGUNAKAN INGATAN DARI CHAT
    SEBELUMNYA UNTUK MENENTUKAN STOK ATAU HARGA, Kecuali ada hal penting yang sedang di bahas.

12. Jika data inventaris saat ini berbeda dengan jawaban sebelumnya,
    SELALU ikuti data inventaris saat ini.

13. Jika pengguna bertanya apakah ada stok/obat tersedia secara umum,
    jangan menganggapnya sebagai nama obat tertentu.

14. Jangan mengatakan suatu obat tersedia jika nama obat tersebut tidak
    ada pada DAFTAR INVENTARIS PRODUK SAAT INI.

15. Data inventaris dapat berubah setiap saat karena admin atau super admin
    dapat menambahkan, mengaktifkan, mengonfirmasi, mengubah stok, mengubah
    harga, atau menghapus obat melalui halaman web.

16. Untuk informasi inventaris, SELALU prioritaskan data database terbaru
    yang diberikan controller pada request saat ini.

17. Jangan menganggap jawaban assistant sebelumnya sebagai sumber kebenaran
    untuk stok, harga, status aktif, atau ketersediaan obat.

PROMPT;
    }

    /*
    |--------------------------------------------------------------------------
    | 17. MEMBERSIHKAN JAWABAN AI
    |--------------------------------------------------------------------------
    */

    private function cleanAIResponse(string $reply): string
    {
        $reply = trim($reply);

        $reply = preg_replace(
            "/\n{3,}/u",
            "\n\n",
            $reply
        );

        return trim($reply);
    }

    /*
    |--------------------------------------------------------------------------
    | 18. SIMPAN DAN KIRIM JAWABAN
    |--------------------------------------------------------------------------
    */

    private function sendReply($user, string $reply)
    {
        AiMessage::create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $reply,
        ]);

        return response()->json([
            'reply' => $reply,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 19. RIWAYAT CHAT
    |--------------------------------------------------------------------------
    */

    public function history(Request $request)
    {
        $messages = AiMessage::where(
            'user_id',
            $request->user()->id
        )
            ->orderBy('id')
            ->get([
                'id',
                'role',
                'content',
                'created_at',
            ]);

        return response()->json([
            'messages' => $messages,
        ]);
    }
}