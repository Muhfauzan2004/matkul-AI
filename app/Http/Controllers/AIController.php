<?php

namespace App\Http\Controllers;

use App\Models\AiMessage;
use App\Models\Obat;
use App\Models\OrderItem;
use App\Models\Kategori;
use App\Models\StockAddition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AIController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PENGATURAN ASISTEN AI
    |--------------------------------------------------------------------------
    */

    private int $maxHistory = 12;
    private ?string $activeSessionId = null;
    private const STOCK_CLARIFICATION_HEADER = '📦 **Konfirmasi Spesifikasi Update Stok**';

    public function chat(Request $request)
    {
        set_time_limit(90);

        $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:64',
        ]);

        $user = $request->user();
        $sessionId = trim((string) $request->input('session_id'));
        if ($sessionId === '') {
            $sessionId = 'sess_' . time() . '_' . bin2hex(random_bytes(4));
        }
        $this->activeSessionId = $sessionId;

        $namaPengguna = trim((string) ($user->name ?? 'Pengguna'));
        $pertanyaan = trim($request->input('message'));
        $teks = mb_strtolower($pertanyaan);
        $teks = preg_replace('/\s+/u', ' ', $teks);

        if ($teks === '') {
            return response()->json([
                'error' => 'Pesan tidak boleh kosong.',
            ], 422);
        }

        // Ambil riwayat percakapan user dalam sesi ini
        $riwayatQuery = AiMessage::where('user_id', $user->id);
        if ($request->filled('session_id')) {
            $riwayatQuery->where('session_id', $sessionId);
        }
        $riwayat = $riwayatQuery->orderByDesc('id')
            ->limit($this->maxHistory)
            ->get()
            ->reverse()
            ->values();

        // Simpan pertanyaan user
        AiMessage::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => $pertanyaan,
        ]);

        Log::info('PharmAI Chat Request', [
            'user_id' => $user->id,
            'role' => $user->role,
            'session_id' => $sessionId,
            'message' => mb_substr($pertanyaan, 0, 200),
        ]);

        // 1. Prioritas Nama User
        if ($this->isNameQuestion($teks)) {
            return $this->sendReply($user, "Nama kamu {$namaPengguna}. Ada yang bisa aku bantu hari ini? 😊");
        }

        // 2. Prioritas Keadaan Darurat Medis
        if ($this->hasEmergencySymptoms($teks)) {
            return $this->sendReply(
                $user,
                "🚨 **PERHATIAN MEDIS DARURAT**\n\n"
                . "Gejala yang kamu sebutkan berpotensi merupakan kondisi gawat darurat medis yang memerlukan pertolongan segera.\n\n"
                . "Segera kunjungi **IGD Rumah Sakit terdekat** atau hubungi layanan darurat medis (119). "
                . "Jangan menunda pertolongan medis darurat untuk mencari obat melalui chat."
            );
        }

        // 2b. Deteksi Khusus Luka Bakar Gawat Darurat (20%, Luas, Derajat Berat, Wajah, dll.)
        if ($this->isEmergencyBurn($teks)) {
            return $this->handleEmergencyBurn($user, $teks);
        }

        // Ambil data inventaris database real-time
        $obat = $this->getDataObat();
        $kategori = $this->getDataKategori();

        // 3. Prioritas Pertanyaan Total Obat
        if ($this->isTotalMedicineCountRequest($teks)) {
            return $this->handleTotalMedicineCount($user, $obat);
        }

        // 4. Prioritas Ambang Batas & Rentang Stok / Harga
        $thresholdData = $this->parseStockThreshold($teks);
        if ($thresholdData !== null) {
            return $this->handleThresholdQuery($user, $thresholdData, $obat, 'stock');
        }

        $priceThresholdData = $this->parsePriceThreshold($teks);
        if ($priceThresholdData !== null) {
            $obatSpesifikHarga = $this->findMedicineInMessage($teks, $obat);
            $poolHarga = $obatSpesifikHarga->isNotEmpty() ? $obatSpesifikHarga : $obat;
            return $this->handleThresholdQuery($user, $priceThresholdData, $poolHarga, 'price');
        }

        // 4b. Prioritas Update Stok Obat (Penambahan & Pengurangan via Persetujuan Pemilik)
        $isStockFollowUp = $this->isPendingStockClarification($riwayat)
            && !$this->isOutOfStockListRequest($teks)
            && !$this->isAvailableListRequest($teks)
            && !$this->isCategoryListRequest($teks)
            && !$this->isStockProceduralOrNegationQuery($teks);

        if ($isStockFollowUp || $this->isStockUpdateIntent($teks)) {
            return $this->handleStockUpdateConversation($user, $teks, $pertanyaan, $riwayat, $obat, $namaPengguna);
        }

        // 5. Prioritas Daftar Obat Habis (Stok 0)
        if ($this->isOutOfStockListRequest($teks)) {
            return $this->handleOutOfStockList($user, $obat);
        }

        // 6. Prioritas Daftar Obat Tersedia
        if ($this->isAvailableListRequest($teks)) {
            return $this->handleAvailableList($user, $obat);
        }

        // 7. Prioritas Daftar Kategori / Jenis Obat
        if ($this->isCategoryListRequest($teks)) {
            return $this->handleCategoryList($user, $kategori, $obat);
        }

        // 8. PRIORITAS UTAMA: REKOMENDASI OBAT & PERTOLONGAN PERTAMA KELUHAN GEJALA (INSTAN & AKURAT)
        // Kecualikan pertanyaan konsultasi interaksi/keamanan/resep/penyebab agar dijawab lebih kaya oleh AI apoteker senior
        $isAskingMedicineRecommendation = (bool) preg_match('/\b(obatnya\s+apa|obat\s+apa|rekomendasi\s+obat|butuh\s+obat|kasih\s+obat|sarankan\s+obat|beli\s+obat|pakai\s+obat\s+apa)\b/iu', $teks);
        $isClinicalMechanismOrCause = (bool) preg_match('/\b(kenapa|mengapa|penyebab|sebabnya|bikin|membuat|menyebabkan|kandungan|komposisi|cara\s+kerja|mekanisme|aman(?:kah)?\s+(?:untuk|buat|bagi|saat)|ibu\s+hamil|hamil|menyusui|bahaya|risiko|kontraindikasi|overdosis|bedanya|perbedaan)\b/iu', $teks)
            && !$isAskingMedicineRecommendation;
        $isConsultativeClinicalQuestion = $isClinicalMechanismOrCause
            || (bool) preg_match('/\b(bolehkah|apakah\s+boleh|perlu\s+resep|pakai\s+resep|tanpa\s+resep|efek\s+samping|interaksi|pantangan|setelah\s+minum|bareng|kopi|susu|teh|obat\s+keras)\b/iu', $teks);
        $hasilGejala = $this->matchMedicinesForSymptoms($teks, $obat);
        $isSpecificStockOrDosage = $this->isMedicineDosageOrUsageQuestion($teks) || $this->isStockOrPriceQuestion($teks);

        if (!empty($hasilGejala['keluhan']) && !$isSpecificStockOrDosage && !$isConsultativeClinicalQuestion) {
            return $this->handleSymptomRecommendation($user, $hasilGejala);
        }

        // 9. Prioritas Pencarian Obat Spesifik (Nama Obat, Stok, Dosis, Khasiat)
        $obatCocok = $this->findMedicineInMessage($teks, $obat);
        if ($obatCocok->isNotEmpty() && !$isConsultativeClinicalQuestion) {
            Log::info('PharmAI Medicine Found', [
                'query' => $pertanyaan,
                'matched_count' => $obatCocok->count(),
                'first_match' => $obatCocok->first()['nama_obat'] ?? null,
                'stok' => $obatCocok->first()['stok'] ?? null,
            ]);
            if ($this->isMedicineDosageOrUsageQuestion($teks)) {
                return $this->handleMedicineUsage($user, $obatCocok, $teks);
            }
            if ($this->isMedicineDetailQuestion($teks) || preg_match('/\b(alternatif|alternatifnya|pengganti|penggantinya|opsi\s+lain|selain\s+itu|kalau\s+habis|kalo\s+habis|kalau\s+(?:ga|gak|nggak|tidak)\s+ada|kalo\s+(?:ga|gak|nggak|tidak)\s+ada)\b/iu', $teks)) {
                return $this->handleMedicineDetail($user, $obatCocok, $obat, $teks, $riwayat);
            }
            if ($this->isStockOrPriceQuestion($teks)) {
                return $this->handleMedicineStock($user, $obatCocok, $teks, $riwayat);
            }
            return $this->handleMedicineOverview($user, $obatCocok, $obat, $teks, $riwayat);
        }

        // 10. Prioritas Obat Berdasarkan Kategori (kecuali pertanyaan konsultasi klinis/keamanan)
        $kategoriCocok = !$isConsultativeClinicalQuestion ? $this->findCategoryInMessage($teks, $kategori) : null;
        if ($kategoriCocok !== null) {
            return $this->handleCategoryMedicines($user, $kategoriCocok, $obat);
        }

        // 10b. Cek Pertanyaan Ketersediaan Nama Obat yang Tidak Ada di Database (misal: "apa ada paramex?")
        if (empty($hasilGejala['keluhan']) && $this->isMedicineAvailabilityInquiry($teks)) {
            return $this->handleMissingMedicineAvailability($user, $teks, $obat, $riwayat);
        }

        // Jika ada keluhan yang tersisa tapi ada nama obat yang spesifik ditanyakan bersama keluhan
        if (!empty($hasilGejala['keluhan']) && !$isConsultativeClinicalQuestion) {
            return $this->handleSymptomRecommendation($user, $hasilGejala);
        }

        // 11. Prioritas Percakapan Ringan (Small Talk)
        $smallTalk = $this->handleSmallTalk($teks, $namaPengguna, $riwayat);
        if ($smallTalk !== null) {
            return $this->sendReply($user, $smallTalk);
        }

        // 12. Layanan Konsultasi Medis & Percakapan AI (Gemini / Ollama)
        try {
            $reply = $this->callAIProvider($teks, $riwayat, $obat, $kategori, $namaPengguna, $user);
            return $this->sendReply($user, $reply);
        } catch (Throwable $e) {
            Log::warning('AI Fallback Error: ' . $e->getMessage());

            if ($this->isHealthOrMedicalQuery($teks)) {
                return $this->sendReply($user, $this->getGeneralHealthGuidanceText($teks, $namaPengguna, $obat));
            }

            return $this->sendReply(
                $user,
                "Hai {$namaPengguna}! 👋 Saya **PharmAI** dari **APOTEK ITU TOKO OBAT**. Ada informasi obat, aturan pakai, atau ketersediaan stok yang bisa aku bantu hari ini? 😊"
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | REKOMENDASI OBAT BERDASARKAN KELUHAN GEJALA (CEPAT & TANPA HALUSINASI)
    |--------------------------------------------------------------------------
    */

    private function matchMedicinesForSymptoms(string $teks, Collection $katalogObat): array
    {
        $keluhanDitemukan = [];
        $rekomendasi = [];

        // 1a. Luka Bakar Ringan (Minor / Knalpot / Minyak Panas)
        if (preg_match('/luka\s*bakar|terbakar|kena\s*knalpot|kena\s*(?:air|minyak)\s*panas|kena\s*setrika/u', $teks)) {
            $keluhanDitemukan[] = 'luka bakar ringan';
            $rekomendasi['Salep Khusus Luka Bakar'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'bioplacenton');
            });
            $rekomendasi['Pembersih & Kompres Luka'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'rivanol');
            });
        }
        // 1b. Luka Tusuk (Paku, Jarum, Pecahan Kaca)
        elseif (preg_match('/tertusuk|tusuk|paku|jarum|kaca/u', $teks)) {
            $keluhanDitemukan[] = 'luka tusuk & waspada tetanus';
            $rekomendasi['Antiseptik Pembersih Luka Tusuk'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'rivanol') || str_contains($teksObat, 'betadine');
            });
            $rekomendasi['Penutup Luka Steril'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return (str_contains($teksObat, 'plester') || str_contains($teksObat, 'hansaplast'))
                    && !str_contains($teksObat, 'swab');
            });
        }
        // 1c. Luka Memar / Lebam / Terbentur
        elseif (preg_match('/memar|lebam|terbentur|bengkak\s*akibat\s*benturan/u', $teks)) {
            $keluhanDitemukan[] = 'luka memar / lebam';
            $rekomendasi['Pereda Nyeri & Bengkak'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'thrombophob')
                    || str_contains($teksObat, 'heparin')
                    || str_contains($teksObat, 'paracetamol')
                    || str_contains($teksObat, 'ibuprofen');
            });
        }
        // 1d. Mimisan / Pendarahan Hidung
        elseif (preg_match('/mimisan|hidung\s*berdarah/u', $teks)) {
            $keluhanDitemukan[] = 'mimisan (pendarahan hidung)';
            $rekomendasi['Kapas & Pembersih Steril'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'swab') || str_contains($teksObat, 'rivanol');
            });
        }
        // 1e. Luka Sobek / Terbuka / Sayat / Berdarah / Tergores / Lecet / P3K
        elseif (preg_match('/luka|sobek|sayat|berdarah|pendarahan|keluar\s*darah|tergores|lecet|p3k|pertolongan\s*pertama/u', $teks)) {
            $keluhanDitemukan[] = 'perawatan luka sobek & terbuka';
            $rekomendasi['Antiseptik & Pembersih Luka'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'betadine')
                    || str_contains($teksObat, 'rivanol');
            });
            $rekomendasi['Salep Luka & Anti-Infeksi'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'bioplacenton')
                    || str_contains($teksObat, 'gentamicin');
            });
            $rekomendasi['Plester & Penutup Luka'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return (str_contains($teksObat, 'plester') || str_contains($teksObat, 'hansaplast'))
                    && !str_contains($teksObat, 'swab');
            });
        }

        // 2. Pusing Berputar / Vertigo / Melayang
        if (preg_match('/berputar|melayang|vertigo/u', $teks)) {
            $keluhanDitemukan[] = 'pusing berputar / vertigo';
            $rekomendasi['Pusing Berputar / Vertigo'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'betahistine')
                    || str_contains($teksObat, 'vertigo')
                    || str_contains($teksObat, 'paracetamol');
            });
        }
        // Pusing biasa / Sakit Kepala / Migrain
        elseif (preg_match('/pusing|sakit\s*kepala|kepala\s*(?:nyeri|berat)|migrain|migren/u', $teks)) {
            $keluhanDitemukan[] = 'pusing / sakit kepala';
            $rekomendasi['Pusing / Sakit Kepala'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'paracetamol')
                    || str_contains($teksObat, 'panadol')
                    || str_contains($teksObat, 'sanmol')
                    || str_contains($teksObat, 'ibuprofen')
                    || str_contains($teksObat, 'pusing')
                    || str_contains($teksObat, 'kepala');
            });
        }

        // 3. Mual / Muntah / Asam Lambung / Maag / Masuk Angin / Kembung
        if (preg_match('/mual|muntah|eneg|enek|lambung|maag|kembung|begah|perut\s*tidak\s*enak|masuk\s*angin/u', $teks)) {
            $keluhanDitemukan[] = 'mual / gangguan lambung';
            $rekomendasi['Mual & Gangguan Lambung'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'domperidone')
                    || str_contains($teksObat, 'antasida')
                    || str_contains($teksObat, 'omeprazole')
                    || str_contains($teksObat, 'antangin')
                    || str_contains($teksObat, 'sucralfate')
                    || str_contains($teksObat, 'mual')
                    || str_contains($teksObat, 'lambung')
                    || str_contains($teksObat, 'kembung');
            });
        }

        // 4. Diare / Mencret / Sakit Perut BAB Cair / Keracunan Makanan
        if (preg_match('/diare|mencret|bab\s*(?:cair|terus)|buang\s*air\s*besar\s*cair|muntaber|keracunan/u', $teks)) {
            $keluhanDitemukan[] = 'diare / buang air cair';
            $rekomendasi['Diare & Dehidrasi'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'oralit')
                    || str_contains($teksObat, 'entrostop')
                    || str_contains($teksObat, 'diapet');
            });
        }

        // 5. Sakit Gigi / Gusi Bengkak / Gigi Berlubang
        if (preg_match('/gigi|gusi\s*bengkak|ngilu/u', $teks)) {
            $keluhanDitemukan[] = 'sakit gigi / nyeri gusi';
            $rekomendasi['Sakit Gigi & Nyeri'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'asam mefenamat')
                    || str_contains($teksObat, 'mefenamat')
                    || str_contains($teksObat, 'ibuprofen')
                    || str_contains($teksObat, 'paracetamol');
            });
        }

        // 6. Sakit Tenggorokan / Sariawan / Panas Dalam / Radang
        if (preg_match('/tenggorokan|sariawan|panas\s*dalam|susah\s*menelan|serak/u', $teks)) {
            $keluhanDitemukan[] = 'sakit tenggorokan / sariawan';
            $rekomendasi['Tenggorokan & Sariawan'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'degirol')
                    || str_contains($teksObat, 'fg troches')
                    || str_contains($teksObat, 'aloclair')
                    || str_contains($teksObat, 'betadine mouthwash');
            });
        }

        // 7. Mata Merah / Iritasi / Belekan / Kering
        if (preg_match('/mata\s*(?:merah|perih|gatal|kering|iritasi|belekan|kelilipan)/u', $teks)) {
            $keluhanDitemukan[] = 'mata merah / iritasi mata';
            $rekomendasi['Mata Merah & Iritasi'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'insto')
                    || str_contains($teksObat, 'cendo')
                    || str_contains($teksObat, 'rohto');
            });
        }

        // 8. Sesak Napas / Asma
        if (preg_match('/sesak\s*napas|asma|mengi|bengek/u', $teks)) {
            $keluhanDitemukan[] = 'sesak napas / asma';
            $rekomendasi['Sesak Napas / Asma'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'salbutamol')
                    || str_contains($teksObat, 'grafalin')
                    || str_contains($teksObat, 'ventolin');
            });
        }

        // 9. Demam / Panas / Meriang
        if (preg_match('/demam|badan\s*panas|panas\s*dingin|meriang|suhu\s*tinggi/u', $teks)) {
            $keluhanDitemukan[] = 'demam / badan panas';
            $rekomendasi['Demam / Panas'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'paracetamol')
                    || str_contains($teksObat, 'sanmol')
                    || str_contains($teksObat, 'fasidol')
                    || str_contains($teksObat, 'panas')
                    || str_contains($teksObat, 'demam');
            });
        }

        // 10. Batuk / Pilek / Flu / Bersin
        if (preg_match('/batuk|pilek|flu|bersin|hidung\s*mampet|hidung\s*tersumbat/u', $teks)) {
            $keluhanDitemukan[] = 'batuk / pilek / flu';
            $rekomendasi['Flu, Batuk & Pilek'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'demacolin')
                    || str_contains($teksObat, 'alpara')
                    || str_contains($teksObat, 'coparcetin')
                    || str_contains($teksObat, 'hufagrip')
                    || str_contains($teksObat, 'ambroxol')
                    || str_contains($teksObat, 'batuk')
                    || str_contains($teksObat, 'flu');
            });
        }

        // 11. Nyeri Otot / Radang / Pegal / Sendi
        if (preg_match('/nyeri\s*otot|pegal|linu|radang|sendi/u', $teks)) {
            $keluhanDitemukan[] = 'nyeri / radang';
            $rekomendasi['Nyeri & Radang'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'asam mefenamat')
                    || str_contains($teksObat, 'mefenamat')
                    || str_contains($teksObat, 'ibuprofen')
                    || str_contains($teksObat, 'diclofenac')
                    || str_contains($teksObat, 'voltadex');
            });
        }

        // 12. Alergi / Gatal
        if (preg_match('/gatal|alergi|biduran|bintik\s*merah/u', $teks)) {
            $keluhanDitemukan[] = 'alergi / gatal';
            $rekomendasi['Alergi & Gatal'] = $katalogObat->filter(function ($item) {
                $teksObat = mb_strtolower($item['nama_obat'] . ' ' . $item['deskripsi']);
                return str_contains($teksObat, 'cetirizine')
                    || str_contains($teksObat, 'loratadine')
                    || str_contains($teksObat, 'alerzin')
                    || str_contains($teksObat, 'chlorphenamine')
                    || str_contains($teksObat, 'gatal');
            });
        }

        return [
            'keluhan' => $keluhanDitemukan,
            'kategori_rekomendasi' => $rekomendasi,
        ];
    }

    private function handleSymptomRecommendation($user, array $data)
    {
        $daftarKeluhan = implode(' dan ', $data['keluhan']);
        $rekomendasiGrup = $data['kategori_rekomendasi'];
        $isLukaBakar = isset($rekomendasiGrup['Salep Khusus Luka Bakar']);
        $isLukaSobek = isset($rekomendasiGrup['Antiseptik & Pembersih Luka'])
            || isset($rekomendasiGrup['Salep Luka & Anti-Infeksi'])
            || isset($rekomendasiGrup['Plester & Penutup Luka']);
        $isLukaTusuk = isset($rekomendasiGrup['Antiseptik Pembersih Luka Tusuk']);
        $isLukaMemar = isset($rekomendasiGrup['Pereda Nyeri & Bengkak']);
        $isMimisan = isset($rekomendasiGrup['Kapas & Pembersih Steril']);
        $isLuka = $isLukaBakar || $isLukaSobek || $isLukaTusuk || $isLukaMemar || $isMimisan;

        if ($isLukaBakar) {
            $reply = "🔥 **Panduan Pertolongan Pertama untuk Luka Bakar Ringan (Minor):**\n\n"
                . "1️⃣ **Dinginkan Segera dengan Air Mengalir (Langkah Utama):**\n"
                . "   - Alirkan air bersih suhu biasa/ruang (bukan air es) ke area luka bakar selama 15–20 menit terus-menerus. Ini langkah terpenting untuk menghentikan panas merusak lapisan kulit lebih dalam.\n"
                . "2️⃣ **Lepaskan Benda di Sekitar Luka:**\n"
                . "   - Lepaskan cincin, jam tangan, atau pakaian ketat sebelum area membengkak.\n"
                . "3️⃣ **🚫 PENTING - Hal yang JANGAN Dilakukan:**\n"
                . "   - ❌ **JANGAN** oleskan odol (pasta gigi), mentega, kecap, minyak, atau tepung! Zat tersebut memerangkap panas dan memicu infeksi berat.\n"
                . "   - ❌ **JANGAN** gunakan es batu / air es (dapat merusak jaringan kulit yang sedang rapuh).\n"
                . "   - ❌ **JANGAN** pecahkan lepuhan (gelembung air/blister) karena lepuhan tersebut adalah perisai pelindung steril dari infeksi.\n"
                . "4️⃣ **Oleskan Salep Khusus Luka Bakar:**\n"
                . "   - Setelah dingin dan dikeringkan secara lembut, oleskan salep luka bakar seperti **BIOPLACENTON** tipis-tipis untuk mempercepat regenerasi kulit.\n"
                . "5️⃣ **Tutup Longgar:**\n"
                . "   - Tutup longgar dengan kasa steril bila perlu (hindari plester yang menempel erat pada kulit yang melepuh).\n\n"
                . "🚨 *Catatan: Bila luka bakar meluas (diameter > 7 cm), mengenai wajah, persendian, atau melepuh parah, segera periksakan ke dokter/IGD.*\n\n"
                . "📦 **Produk Apotek Tersedia untuk Luka Bakar:**\n\n";
        } elseif ($isLukaSobek) {
            $reply = "🩹 **Panduan Pertolongan Pertama untuk Luka Sobek / Terbuka:**\n\n"
                . "1️⃣ **Hentikan Pendarahan:**\n"
                . "   - Tekan area luka secara perlahan dengan kain bersih atau kasa steril selama 5–10 menit tanpa sering diangkat.\n"
                . "2️⃣ **Bersihkan Luka:**\n"
                . "   - Cuci luka dengan air bersih mengalir atau cairan antiseptik seperti **Rivanol**.\n"
                . "   - ⚠️ *Penting:* Jangan tuangkan alkohol langsung ke dalam luka terbuka karena dapat merusak jaringan dan sangat perih; alkohol hanya untuk membersihkan kulit di sekitar luka.\n"
                . "3️⃣ **Oleskan Antiseptik / Salep:**\n"
                . "   - Oleskan **Betadine Antiseptic (Povidone Iodine)** atau salep luka/antibakteri seperti **Gentamicin Sulfate** / **Bioplacenton** untuk mencegah infeksi kuman.\n"
                . "4️⃣ **Tutup & Lindungi Luka:**\n"
                . "   - Tutup luka dengan plester steril (**Plesterin Anti Air** / **Hansaplast**) atau perban kasa.\n\n"
                . "🚨 **Tanda Bahaya (Kapan Harus ke Dokter/IGD Segera):**\n"
                . "- Bila luka sobek **dalam atau menganga lebar** (mungkin membutuhkan **jahitan medis** agar luka rapat dan tidak meninggalkan bekas parah).\n"
                . "- Darah terus memancar deras dan tidak berhenti setelah 10 menit ditekan.\n"
                . "- Luka disebabkan oleh benda berkarat, kotor, atau gigitan hewan (memerlukan suntikan anti-tetanus).\n\n"
                . "📦 **Produk P3K & Perawatan Luka Tersedia di Apotek Kami:**\n\n";
        } elseif ($isLukaTusuk) {
            $reply = "📌 **Panduan Pertolongan Pertama untuk Luka Tusuk (Paku / Kaca / Benda Tajam):**\n\n"
                . "1️⃣ **Bersihkan & Cuci Luka Segera:**\n"
                . "   - Cuci area yang tertusuk dengan air bersih mengalir dan sabun lembut selama minimal 5 menit untuk membantu membilas kotoran keluar.\n"
                . "2️⃣ **Biarkan Darah Mengalir Sedikit (Jika Aman):**\n"
                . "   - Biarkan darah mengalir sedikit untuk membantu membersihkan saluran luka secara alami dari dalam.\n"
                . "3️⃣ **Oleskan Antiseptik & Tutup Steril:**\n"
                . "   - Teteskan antiseptik pembersih luka seperti **Rivanol** atau **Betadine**, lalu tutup dengan perban/plester steril.\n"
                . "4️⃣ **🚫 Hal yang JANGAN Dilakukan:**\n"
                . "   - ❌ **JANGAN** mencongkel atau mengorek luka terlalu dalam sendiri jika ada benda tertanam.\n\n"
                . "🚨 **PERINGATAN BAHAYA TETANUS (PENTING):**\n"
                . "- Bakteri *Clostridium tetani* dapat berkembang biak di saluran luka tusuk yang dalam dan minim udara (terutama dari **paku berkarat, tanah, kebun, atau pecahan kaca**).\n"
                . "- **Sangat disarankan segera ke Puskesmas, Klinik, atau IGD dalam 24 jam** untuk mendapatkan suntikan **Anti-Tetanus (Serum ATS / Vaksin TT)** jika belum suntik dalam 5–10 tahun terakhir.\n\n"
                . "📦 **Produk P3K Tersedia di Apotek Kami:**\n\n";
        } elseif ($isLukaMemar) {
            $reply = "🧊 **Panduan Penanganan untuk Luka Memar / Lebam / Bengkak Terbentur:**\n\n"
                . "1️⃣ **Kompres Dingin / Es Segera (24–48 Jam Pertama):**\n"
                . "   - Bungkus es dengan handuk/kain bersih (jangan letakkan es langsung ke kulit), lalu kompres selama 15–20 menit. Ulangi beberapa kali sehari untuk meredakan pembengkakan dan menghentikan rembesan darah di bawah kulit.\n"
                . "2️⃣ **Istirahatkan & Posisikan Lebih Tinggi (Elevasi):**\n"
                . "   - Istirahatkan area yang terbentur. Jika memar di kaki atau tangan, sangga dengan bantal agar posisinya lebih tinggi dari dada.\n"
                . "3️⃣ **🚫 PANTANGAN KERAS - JANGAN DIPIJAT / DIURUT:**\n"
                . "   - ❌ **JANGAN** memijat atau mengurut keras area yang baru memar! Pijatan keras dapat merobek lebih banyak pembuluh darah kapiler dan memperparah bengkak.\n"
                . "4️⃣ **Obat & Gel Pereda Memar / Nyeri:**\n"
                . "   - Oleskan gel pereda lebam (seperti **Thrombophob Gel**) pada kulit yang utuh tanpa luka terbuka, atau minum **Paracetamol** bila terasa nyeri berdenyut.\n\n"
                . "🚨 *Tanda Bahaya: Bila bengkak membesar sangat cepat, timbul mati rasa, sendi tidak dapat digerakkan, atau dicurigai retak/patah tulang, segera periksakan ke dokter/RS.*\n\n"
                . "📦 **Produk Pereda Nyeri & Bengkak Tersedia di Apotek Kami:**\n\n";
        } elseif ($isMimisan) {
            $reply = "🩸 **Panduan Pertolongan Pertama untuk Mimisan (Pendarahan Hidung):**\n\n"
                . "1️⃣ **Duduk Tegak & Condongkan Tubuh ke Depan:**\n"
                . "   - Duduk dengan tenang dan condongkan tubuh sedikit **ke depan**.\n"
                . "   - ❌ **JANGAN menengadahkan kepala ke belakang!** Menengadahkan kepala membuat darah tertelan ke lambung (memicu mual/muntah) atau tersedak ke saluran pernapasan.\n"
                . "2️⃣ **Jepit Cuping Hidung Rapat-Rapat:**\n"
                . "   - Tekan bagian lunak hidung (tepat di bawah tulang hidung) dengan ibu jari dan jari telunjuk secara rapat selama **10–15 menit** tanpa sering dilepas. Bernapaslah melalui mulut.\n"
                . "3️⃣ **Kompres Dingin di Pangkal Hidung:**\n"
                . "   - Letakkan kompres es atau kain dingin di pangkal hidung untuk membantu menyempitkan pembuluh darah.\n"
                . "4️⃣ **Setelah Darah Berhenti:**\n"
                . "   - Jangan mengorek hidung atau membuang ingus keras-keras selama beberapa jam.\n\n"
                . "🚨 **Tanda Bahaya (Kapan Harus ke IGD):**\n"
                . "- Mimisan berlangsung lebih dari 20–30 menit dan tidak berhenti meski sudah ditekan.\n"
                . "- Pendarahan sangat deras atau terjadi setelah benturan keras pada kepala/wajah.\n\n"
                . "📦 **Perlengkapan Steril Tersedia di Apotek Kami:**\n\n";
        } else {
            $reply = "Aduh, semoga lekas membaik ya. ❤️ Aku memahami kamu sedang mengalami keluhan **{$daftarKeluhan}**.\n\n"
                . "Berdasarkan inventaris apotek kami, berikut rekomendasi obat yang relevan:\n\n";
        }

        $adaObat = false;
        foreach ($rekomendasiGrup as $labelKeluhan => $koleksi) {
            $tersedia = $koleksi->where('stok', '>', 0)->sortBy(function ($item) {
                $nama = mb_strtolower($item['nama_obat']);
                $isInfus = (str_contains($nama, 'infus') || str_contains($nama, 'inj ') || str_contains($nama, 'injeksi')) ? 1 : 0;
                return $isInfus * 10000 - min(9999, (int) $item['stok']);
            })->take(3)->values();

            if ($tersedia->isNotEmpty()) {
                $adaObat = true;
                $reply .= "🔹 **Untuk {$labelKeluhan}:**\n\n"
                    . $this->formatMedicineMarkdownTable($tersedia, true)
                    . "\n\n";
            }
        }

        if (!$adaObat && !$isLuka) {
            $reply = "Saat ini kami belum menemukan obat di inventaris apotek yang tersedia khusus untuk keluhan **{$daftarKeluhan}**.\n\n";
        }

        if (!$isLuka) {
            $reply .= "💡 **Tips Perawatan Mandiri:**\n"
                . "- Minum air putih hangat sedikit demi sedikit tetapi sering.\n"
                . "- Istirahat di tempat yang tenang dan cukupi kebutuhan tidur.\n"
                . "- Hindari makanan pantangan sesuai keluhan sementara waktu.\n\n"
                . "⚠️ *Informasi ini merupakan referensi awal inventaris apotek. Bila keluhan berlanjut lebih dari 2-3 hari atau memburuk, segera konsultasikan langsung dengan apoteker atau dokter. Jika butuh penjelasan aturan pakai atau alternatif obat lain, kabari saja ya!*";
        } else {
            $reply .= "⚠️ *Pastikan produk perawatan luka di atas digunakan sesuai petunjuk kemasan. Kalau butuh alternatif produk atau informasi aturan pakainya, silakan tanya ya!*";
        }

        return $this->sendReply($user, $reply);
    }

    private function getMedicineDescription(string $namaObat, string $deskripsiDb): string
    {
        if (trim($deskripsiDb) !== '') {
            return trim($deskripsiDb);
        }

        $nama = mb_strtolower($namaObat);
        if (str_contains($nama, 'betadine')) {
            return 'Cairan antiseptik povidone iodine untuk mencegah infeksi pada luka.';
        }
        if (str_contains($nama, 'rivanol')) {
            return 'Cairan antiseptik untuk membersihkan dan mengompres luka.';
        }
        if (str_contains($nama, 'plester') || str_contains($nama, 'hansaplast')) {
            return 'Penutup luka steril untuk melindungi luka dari kotoran dan air.';
        }
        if (str_contains($nama, 'bioplacenton')) {
            return 'Salep untuk mempercepat penyembuhan luka bakar, luka terbuka, dan lecet.';
        }
        if (str_contains($nama, 'gentamicin')) {
            return 'Salep antibiotik untuk mengobati infeksi bakteri pada kulit dan luka.';
        }
        if (str_contains($nama, 'swab')) {
            return 'Kapas beralkohol praktis untuk membersihkan kulit sebelum/setelah tindakan.';
        }
        if (str_contains($nama, 'oralit')) {
            return 'Larutan elektrolit untuk mencegah dehidrasi akibat diare atau muntah.';
        }
        if (str_contains($nama, 'entrostop') || str_contains($nama, 'diapet')) {
            return 'Membantu memadatkan feses dan menyerap racun saat diare.';
        }
        if (str_contains($nama, 'insto') || str_contains($nama, 'cendo') || str_contains($nama, 'rohto')) {
            return 'Tetes mata untuk meredakan iritasi, mata merah, dan mata kering.';
        }
        if (str_contains($nama, 'degirol') || str_contains($nama, 'fg troches')) {
            return 'Tablet hisap antiseptik untuk radang tenggorokan dan sariawan.';
        }
        if (str_contains($nama, 'salbutamol') || str_contains($nama, 'grafalin') || str_contains($nama, 'ventolin')) {
            return 'Bronkodilator untuk melegakan saluran napas pada asma dan sesak napas.';
        }
        if (str_contains($nama, 'betahistine')) {
            return 'Membantu meredakan pusing berputar (vertigo) dan rasa melayang.';
        }
        if (str_contains($nama, 'domperidone')) {
            return 'Meredakan rasa mual, muntah, serta kembung di perut.';
        }
        if (str_contains($nama, 'antasida')) {
            return 'Menetralkan asam lambung, meredakan nyeri ulu hati dan mual.';
        }
        if (str_contains($nama, 'omeprazole')) {
            return 'Mengurangi produksi asam lambung berlebih (tukak lambung / GERD).';
        }
        if (str_contains($nama, 'sucralfate')) {
            return 'Melapisi dinding lambung untuk melindungi luka tukak lambung.';
        }
        if (str_contains($nama, 'antangin') || str_contains($nama, 'tolak angin')) {
            return 'Herbal untuk meredakan masuk angin, mual, meriang, dan kembung.';
        }
        if (str_contains($nama, 'paracetamol') || str_contains($nama, 'panadol') || str_contains($nama, 'sanmol') || str_contains($nama, 'fasidol')) {
            return 'Pereda sakit kepala, pusing, dan penurun demam yang aman di lambung.';
        }
        if (str_contains($nama, 'ibuprofen')) {
            return 'Obat anti-inflamasi pereda nyeri kepala, nyeri otot, dan penurun demam.';
        }
        if (str_contains($nama, 'mefenamat')) {
            return 'Pereda nyeri sedang hingga berat (sakit gigi, sakit kepala, nyeri haid).';
        }
        if (str_contains($nama, 'cetirizine') || str_contains($nama, 'loratadine') || str_contains($nama, 'alerzin') || str_contains($nama, 'lerzin')) {
            return 'Antihistamin untuk meredakan alergi, bersin-bersin, dan gatal kulit.';
        }
        if (str_contains($nama, 'chlorpheniramine') || str_contains($nama, 'ctm') || str_contains($nama, 'alleron')) {
            return 'Antihistamin (CTM) untuk meredakan alergi, gatal, biduran, dan bersin (dapat menyebabkan kantuk).';
        }
        if (str_contains($nama, 'ambroxol') || str_contains($nama, 'bromhexine') || str_contains($nama, 'acetylcisteine') || str_contains($nama, 'bisolvon')) {
            return 'Pengencer dahak (mukolitik) untuk meredakan batuk berdahak agar dahak lebih mudah dikeluarkan.';
        }
        if (str_contains($nama, 'obh') || str_contains($nama, 'anakonidin')) {
            return 'Sirup obat batuk dan flu untuk meredakan batuk, melegakan tenggorokan, serta hidung tersumbat.';
        }
        if (str_contains($nama, 'demacolin') || str_contains($nama, 'alpara') || str_contains($nama, 'coparcetin') || str_contains($nama, 'hufagrip') || str_contains($nama, 'flutamol')) {
            return 'Kombinasi lengkap meredakan flu, demam, sakit kepala, bersin, dan batuk.';
        }
        if (str_contains($nama, 'ranitidine') || str_contains($nama, 'lansoprazole')) {
            return 'Menurunkan produksi asam lambung berlebih untuk mengatasi nyeri ulu hati, maag, dan GERD.';
        }
        if (str_contains($nama, 'attapulgite') || str_contains($nama, 'loperamide') || str_contains($nama, 'diatab')) {
            return 'Membantu memadatkan tinja dan mengurangi frekuensi buang air besar saat diare.';
        }
        if (str_contains($nama, 'zinc') || str_contains($nama, 'lecozinc') || str_contains($nama, 'holizinc')) {
            return 'Suplemen zinc untuk membantu mempercepat pemulihan diare dan mendukung daya tahan tubuh.';
        }
        if (str_contains($nama, 'meloxicam') || str_contains($nama, 'piroxicam') || str_contains($nama, 'diclofenac') || str_contains($nama, 'diklofenak')) {
            return 'Anti-inflamasi nonsteroid (OAINS) untuk meredakan nyeri sendi, radang, dan pembengkakan.';
        }
        if (str_contains($nama, 'amoxicillin') || str_contains($nama, 'cefadroxil') || str_contains($nama, 'cefixime') || str_contains($nama, 'ciprofloxacin')) {
            return 'Antibiotik (Obat Keras) untuk mengatasi infeksi bakteri; wajib dengan resep dokter dan harus dihabiskan.';
        }
        if (str_contains($nama, 'amlodipine') || str_contains($nama, 'captopril') || str_contains($nama, 'candesartan')) {
            return 'Obat antihipertensi (Obat Keras) untuk membantu menurunkan dan mengontrol tekanan darah tinggi.';
        }
        if (str_contains($nama, 'metformin') || str_contains($nama, 'glimepiride') || str_contains($nama, 'glibenclamid')) {
            return 'Obat antidiabetes (Obat Keras) untuk membantu mengontrol kadar gula darah pasien diabetes tipe 2.';
        }
        if (str_contains($nama, 'simvastatin') || str_contains($nama, 'atorvastatin')) {
            return 'Obat penurun kolesterol (Obat Keras) untuk menurunkan kadar LDL/lemak darah.';
        }
        if (str_contains($nama, 'acifar') || str_contains($nama, 'acyclovir')) {
            return 'Antivirus topikal/oral (Obat Keras) untuk mengatasi infeksi virus herpes simplex/herpes zoster dan cacar air.';
        }

        return 'Membantu meredakan gejala sesuai indikasi medis obat.';
    }

    /*
    |--------------------------------------------------------------------------
    | PARSER AMBANG BATAS & RENTANG STOK (INSTAN & AKURAT)
    |--------------------------------------------------------------------------
    */

    private function parseStockThreshold(string $teks): ?array
    {
        $hasPriceContext = (bool) preg_match('/\b(harga|harganya|rp|rupiah|murah|mahal|termurah|termahal)\b/iu', $teks);
        $hasExplicitStockContext = (bool) preg_match('/\b(stok|stoknya|sisa|persediaan|jumlah\s+stok)\b/iu', $teks);

        if ($hasPriceContext && !$hasExplicitStockContext) {
            return null;
        }

        if (preg_match('/\b(stok\s*(?:yang\s*)?)?(menipis|hampir\s+habis|kritis|mau\s+habis)\b/u', $teks)) {
            return [
                'operator' => 'menipis',
                'threshold' => 10,
                'label' => 'menipis / hampir habis (stok 1 sampai 10)',
            ];
        }

        if (!preg_match('/(stok|jumlah|banyak|obat)/u', $teks)) {
            return null;
        }

        $teksNormal = $this->normalizeThresholdNumbers($teks, false);
        $exactPattern = '/\b(?:stok(?:nya)?|sisa(?:nya)?|jumlah\s+stok(?:nya)?)\s*(?:obat\s*)?(?:yang\s*)?(?:adalah|ada|tinggal|sisa|tepat|berjumlah|jumlahnya|sama\s+dengan|=)?\s*(\d+)\b(?!\s*(?:mg|ml|mcg|g|gr|iu|%|gram|lebih|ke\s*atas)\b)/u';

        foreach ($this->extractThresholdSegments($teksNormal) as $segment) {
            $matched = $this->matchNumericThresholdSegment($segment, $exactPattern, 'stock');
            if ($matched !== null) {
                return $matched;
            }
        }

        return null;
    }

    private function parsePriceThreshold(string $teks): ?array
    {
        if (!preg_match('/\b(harga|harganya|rp|rupiah)\b/iu', $teks)) {
            return null;
        }

        if ($this->isStockUpdateIntent($teks)) {
            return null;
        }

        $teksNormal = $this->normalizeThresholdNumbers($teks, true);
        $exactPattern = '/\b(?:harga(?:nya)?)\s*(?:obat\s*)?(?:yang\s*)?(?:adalah|sebesar|tepat|sama\s+dengan|=)\s*(\d+)\b(?!\s*(?:mg|ml|mcg|g|gr|iu|%|gram|lebih|ke\s*atas)\b)/u';

        foreach ($this->extractThresholdSegments($teksNormal) as $segment) {
            $matched = $this->matchNumericThresholdSegment($segment, $exactPattern, 'price');
            if ($matched !== null) {
                return $matched;
            }
        }

        return null;
    }

    private function normalizeThresholdNumbers(string $teks, bool $withRupiahUnits): string
    {
        $teksNormal = str_replace(
            ['satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'nol'],
            ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '0'],
            $teks
        );

        if ($withRupiahUnits) {
            $teksNormal = preg_replace('/\brp\.?\s*/iu', '', $teksNormal);
            $teksNormal = preg_replace('/\b(\d+)\.(?=\d{3}\b)/u', '$1', $teksNormal);
            $teksNormal = preg_replace_callback('/\b(\d+)\s*(?:rb|ribu)\b/iu', fn ($m) => (string) ((int) $m[1] * 1000), $teksNormal);
            $teksNormal = preg_replace_callback('/\b(\d+)\s*(?:jt|juta)\b/iu', fn ($m) => (string) ((int) $m[1] * 1000000), $teksNormal);
        }

        return $teksNormal;
    }

    private function extractThresholdSegments(string $teksNormal): array
    {
        // Utamakan klausa pertanyaan inti agar angka di klausa pengantar tidak salah tangkap.
        $candidateSegments = [];
        if (preg_match('/[,;]\s*(.+)$/u', $teksNormal, $segComma)) {
            $candidateSegments[] = $segComma[1];
        }
        if (preg_match('/\b(berapa\b.+)$/u', $teksNormal, $segBerapa)) {
            $candidateSegments[] = $segBerapa[1];
        }
        $candidateSegments[] = $teksNormal;

        return array_values(array_unique($candidateSegments));
    }

    private function matchNumericThresholdSegment(string $teksNormal, string $exactFieldPattern, string $mode = 'stock'): ?array
    {
        $fmt = fn (int $n): string => $mode === 'price' ? 'Rp ' . number_format($n, 0, ',', '.') : (string) $n;
        $unitGuard = '(?!\s*(?:mg|ml|mcg|g|gr|iu|%|gram)\b)';

        // Rentang: antara 150 sampai 300 / 150 - 300
        if (preg_match('/(?:antara|di\s*antara|rentang)?\s*(\d+)' . $unitGuard . '\s*(?:sampai|hingga|s\/d|-)\s*(\d+)' . $unitGuard . '/u', $teksNormal, $m)) {
            $min = min((int) $m[1], (int) $m[2]);
            $max = max((int) $m[1], (int) $m[2]);
            return [
                'operator' => 'between',
                'threshold' => [$min, $max],
                'label' => "antara {$fmt($min)} sampai {$fmt($max)}",
            ];
        }

        // Postfix: 300 lebih / 300 ke atas / 300+
        if (preg_match('/(\d+)' . $unitGuard . '\s*(?:lebih|ke\s*atas|\+)/u', $teksNormal, $m)) {
            $val = (int) $m[1];
            return [
                'operator' => '>=',
                'threshold' => $val,
                'label' => "{$fmt($val)} lebih ({$fmt($val)} ke atas)",
            ];
        }

        // Kurang dari sama dengan / <=
        if (preg_match('/(?:kurang\s+dari\s+sama\s+dengan|<=|maksimal|paling\s+(?:banyak|mahal)|maks\.?)\s*(\d+)' . $unitGuard . '/u', $teksNormal, $m)) {
            $val = (int) $m[1];
            return [
                'operator' => '<=',
                'threshold' => $val,
                'label' => "kurang dari atau sama dengan {$fmt($val)}",
            ];
        }

        // Kurang dari / di bawah / <
        if (preg_match('/(?:kurang\s+dari|di\s+bawah|lebih\s+(?:kecil|murah)\s+dari|<)\s*(\d+)' . $unitGuard . '/u', $teksNormal, $m)) {
            $val = (int) $m[1];
            return [
                'operator' => '<',
                'threshold' => $val,
                'label' => "kurang dari {$fmt($val)}",
            ];
        }

        // Lebih dari / di atas / >
        if (preg_match('/(?:lebih\s+dari|di\s+atas|lebih\s+(?:besar|mahal)\s+dari|>)\s*(\d+)' . $unitGuard . '/u', $teksNormal, $m)) {
            $val = (int) $m[1];
            return [
                'operator' => '>',
                'threshold' => $val,
                'label' => "lebih dari {$fmt($val)}",
            ];
        }

        // Sama dengan / tepat N (ambil kecocokan terakhir agar tidak tertukar dengan angka pembanding sebelumnya)
        if (
            !preg_match('/\b(tambah|tambahkan|tambahin|kurang|kurangi|kurangkan|kurangin|restock|restok|update|ubah|edit|sesuaikan)\b/u', $teksNormal)
            && preg_match_all($exactFieldPattern, $teksNormal, $allMatches)
            && !empty($allMatches[1])
        ) {
            $val = (int) end($allMatches[1]);
            return [
                'operator' => '=',
                'threshold' => $val,
                'label' => "tepat {$fmt($val)}",
            ];
        }

        return null;
    }

    private function handleThresholdQuery($user, array $thresholdData, Collection $obat, string $mode = 'stock')
    {
        $op = $thresholdData['operator'];
        $val = $thresholdData['threshold'];
        $label = $thresholdData['label'];
        $isPrice = $mode === 'price';

        $hasil = $obat->filter(function ($item) use ($op, $val, $isPrice) {
            if ($isPrice && !is_numeric($item['harga_umum'] ?? null)) {
                return false;
            }
            $metric = $isPrice ? (int) $item['harga_umum'] : (int) ($item['stok'] ?? 0);
            return match ($op) {
                '=' => $metric === $val,
                '<' => $metric < $val,
                '<=' => $metric <= $val,
                '>' => $metric > $val,
                '>=' => $metric >= $val,
                'between' => $metric >= $val[0] && $metric <= $val[1],
                'menipis' => $metric > 0 && $metric <= 10,
                default => false,
            };
        })->values();

        $total = $hasil->count();
        $subject = $isPrice ? 'harganya' : 'jumlah stoknya';
        $ketTambahan = (!$isPrice && $op === '<' && $val === 1) ? ' (stok 0 / habis)' : '';

        if ($hasil->isEmpty()) {
            return $this->sendReply($user, "Saat ini **tidak ada obat** yang {$subject} {$label}{$ketTambahan} di apotek kami. 😊 Kalau mau cek rentang stok/harga lain atau cari nama obat tertentu, kabari ya!");
        }

        $limitTampil = 15;
        $tampil = $hasil->take($limitTampil);
        $readyCount = $hasil->where('stok', '>', 0)->count();
        $habisCount = $total - $readyCount;
        $contohNama = $this->summarizeMedicineNames($tampil, 3);

        $reply = "Berdasarkan pengecekan inventaris real-time **Apotek Itu Toko Obat**, terdapat total **{$total} obat** yang {$subject} **{$label}**{$ketTambahan} (**{$readyCount} tersedia** dan **{$habisCount} habis**), di antaranya {$contohNama}:\n\n"
            . $this->formatMedicineMarkdownTable($tampil);

        if ($total > $limitTampil) {
            $sisa = $total - $limitTampil;
            $reply .= "\n\n*(Menampilkan {$limitTampil} dari total {$total} obat. Masih ada {$sisa} obat lainnya).*";
        }

        $reply .= "\n\n" . $this->buildPharmacistClosingNote(
            $tampil,
            false,
            'Jika ingin mengetahui khasiat, aturan pakai, alternatif obat lain, atau memfilter lebih spesifik berdasarkan nama/kategori, silakan sebutkan saja ya!'
        );

        return $this->sendReply($user, $reply);
    }

    /*
    |--------------------------------------------------------------------------
    | DAFTAR STOK HABIS & TERSEDIA
    |--------------------------------------------------------------------------
    */

    private function isOutOfStockListRequest(string $teks): bool
    {
        if (preg_match('/(?:sampai|hingga|di\s*antara|rentang)\s*\d+/u', $teks)) {
            return false;
        }

        $polaStokNol = (bool) preg_match('/stok(?:nya)?\s*(?:adalah\s*)?(?:\b0\b|\bnol\b|habis|abis|kosong)/u', $teks);
        $polaKataHabis = (bool) preg_match('/\b(habis|abis|kosong|kehabisan|nihil)\b/u', $teks);
        $polaBerapaNol = (bool) preg_match('/(berapa|apa\s+saja|apa\s+aja|ada\s+nggak|ada\s+gak|ada\s+tidak|daftar|sebutkan).*?(\b0\b|\bnol\b|habis|abis|kosong)/u', $teks);
        $menyebutObat = (bool) preg_match('/\b(obat|barang|produk|stok|stoknya)\b/u', $teks);

        return ($polaStokNol && ($menyebutObat || str_contains($teks, 'berapa')))
            || ($polaKataHabis && $menyebutObat)
            || ($polaBerapaNol && $menyebutObat);
    }

    private function handleOutOfStockList($user, Collection $obat)
    {
        $habis = $obat->where('stok', '<=', 0)->values();
        $total = $habis->count();

        if ($habis->isEmpty()) {
            return $this->sendReply($user, 'Saat ini **tidak ada obat yang stoknya 0 / habis**. Seluruh obat di apotek tercatat tersedia. 😊 Kalau butuh cek daftar obat atau detail item tertentu, beri tahu saya ya!');
        }

        $tampil = $habis->take(15);
        $contohNama = $this->summarizeMedicineNames($tampil, 3);

        $reply = "Berikut daftar obat dengan stok habis (`0`) di **Apotek Itu Toko Obat**. Saat ini tercatat ada **{$total} produk** yang kosong, antara lain {$contohNama}:\n\n"
            . $this->formatMedicineMarkdownTable($tampil);

        if ($total > 15) {
            $reply .= "\n\n*(Menampilkan 15 dari total {$total} obat habis).*";
        }

        $reply .= "\n\n" . $this->buildPharmacistClosingNote(
            $tampil,
            false,
            'Anda bisa menanyakan **alternatif yang tersedia (`Stok > 0`)** untuk salah satu obat di atas, atau langsung ajukan penambahan/restok jika diperlukan.'
        );

        return $this->sendReply($user, $reply);
    }

    private function isAvailableListRequest(string $teks): bool
    {
        if (
            $this->isMedicineDetailQuestion($teks)
            || $this->isMedicineDosageOrUsageQuestion($teks)
            || preg_match('/\b(alternatif|alternatifnya|pengganti|penggantinya|opsi\s+lain|selain|kalau\s+(?:ga|gak|nggak|tidak)\s+ada|kalo\s+(?:ga|gak|nggak|tidak)\s+ada)\b/iu', $teks)
        ) {
            return false;
        }

        return (bool) preg_match('/('
            . '\bobat\s+(?:yang\s+)?(?:masih\s+)?(?:tersedia|ready)\b'
            . '|\b(?:daftar|tampilkan|sebutkan|list)\s+(?:semua\s+|daftar\s+)?(?:stok\s+)?obat\b'
            . '|\bobat\s+apa\s+(?:saja|aja)\b'
            . '|\bada\s+obat\s+apa(?:\s+(?:saja|aja))?\b'
            . ')/u', $teks)
            && !$this->isOutOfStockListRequest($teks)
            && !$this->isCategoryListRequest($teks);
    }

    private function handleAvailableList($user, Collection $obat)
    {
        $tersedia = $obat->where('stok', '>', 0)->values();
        $total = $tersedia->count();
        $tampil = $tersedia->take(15);
        $contohNama = $this->summarizeMedicineNames($tampil, 3);

        $reply = "Saat ini terdapat **{$total} obat** yang tersedia (`Stok > 0`) di **Apotek Itu Toko Obat**, di antaranya {$contohNama}:\n\n"
            . $this->formatMedicineMarkdownTable($tampil);

        if ($total > 15) {
            $reply .= "\n\n*(Menampilkan 15 dari total {$total} obat tersedia).*";
        }

        $reply .= "\n\n" . $this->buildPharmacistClosingNote(
            $tampil,
            false,
            'Sebutkan nama obat, keluhan kesehatan, kategori, atau batas harga tertentu jika ingin mencari obat yang lebih spesifik.'
        );

        return $this->sendReply($user, $reply);
    }

    private function isTotalMedicineCountRequest(string $teks): bool
    {
        return (bool) preg_match('/\b(?:ada\s+)?berapa\s+(?:total|jumlah|banyak|semua|seluruh)(?:\s+(?:total|seluruh|semua|unit|stok|item|jenis|macam|produk|data)){0,4}\s+(?:obat|produk|item|stok)\b(?:\s+(?:yang\s+ada|tersimpan|terdaftar|keseluruhan|saat\s+ini|sekarang|di\s+(?:apotek|gudang|sistem|database|toko|sini)))*\s*[?!.]*$/u', $teks)
            && !preg_match('/(kurang|lebih|di\s+bawah|habis|abis|kosong|tersedia|\d+)/u', $teks);
    }

    private function handleTotalMedicineCount($user, Collection $obat)
    {
        $total = $obat->count();
        $tersedia = $obat->where('stok', '>', 0)->count();
        $habis = $obat->where('stok', '<=', 0)->count();
        $totalUnit = number_format((int) $obat->sum('stok'), 0, ',', '.');

        $reply = "Berikut ringkasan total inventaris obat di **Apotek Itu Toko Obat** saat ini: terdapat **{$total} produk terdaftar** (**{$tersedia} tersedia** dan **{$habis} habis**) dengan akumulasi **{$totalUnit} unit stok** di gudang:\n\n"
            . "| Status Inventaris | Jumlah |\n"
            . "| :--- | :--- |\n"
            . "| Total Obat Terdaftar | **{$total} produk** |\n"
            . "| Stok Tersedia (> 0) | **{$tersedia} produk** |\n"
            . "| Stok Habis (0) | **{$habis} produk** |\n"
            . "| Total Seluruh Unit Stok | **{$totalUnit} unit** |\n\n"
            . "💡 *Jika ingin melihat daftar obat yang habis, stok menipis, atau mencari alternatif obat tertentu, silakan tanyakan langsung ya!*";

        return $this->sendReply($user, $reply);
    }

    private function formatMedicineMarkdownTable(Collection $items, bool $withDescription = false): string
    {
        $cleanCell = function (?string $val): string {
            $v = trim(preg_replace('/\s+/u', ' ', str_replace('|', '/', (string) $val)));
            return $v !== '' ? $v : '—';
        };

        $header = $withDescription
            ? "| Kode | Nama Obat | Golongan | Stok | Harga | Khasiat |\n| :--- | :--- | :--- | :--- | :--- | :--- |"
            : "| Kode | Nama Obat | Golongan | Stok | Harga |\n| :--- | :--- | :--- | :--- | :--- |";

        $rows = $items->map(function ($item) use ($withDescription, $cleanCell) {
            $kode = $cleanCell($item['kode'] ?? '—');
            $nama = $cleanCell($item['nama_obat'] ?? '');
            $gol = $cleanCell($item['kategori'] ?? 'Umum');
            $stokInt = (int) ($item['stok'] ?? 0);
            $satuan = $cleanCell($item['satuan'] ?? 'Unit');
            $stokLabel = $stokInt > 0 ? "Tersedia ({$stokInt} {$satuan})" : "Habis (0 {$satuan})";
            $harga = is_numeric($item['harga_umum'] ?? null) ? 'Rp ' . number_format($item['harga_umum'], 0, ',', '.') : 'Rp -';

            if ($withDescription) {
                $desc = $cleanCell($this->getMedicineDescription($item['nama_obat'] ?? '', $item['deskripsi'] ?? ''));
                return "| `{$kode}` | {$nama} | {$gol} | {$stokLabel} | {$harga} | {$desc} |";
            }

            return "| `{$kode}` | {$nama} | {$gol} | {$stokLabel} | {$harga} |";
        })->implode("\n");

        return $header . "\n" . $rows;
    }

    /*
    |--------------------------------------------------------------------------
    | PENCARIAN OBAT SPESIFIK & ATURAN PAKAI
    |--------------------------------------------------------------------------
    */

    private function findMedicineInMessage(string $teks, Collection $obat): Collection
    {
        // Daftar kata umum, varian generik, bentuk sediaan, pabrikan, atau kata keluhan
        // yang TIDAK boleh mencocokkan obat secara mandiri (mencegah false positive seperti "extra" -> "Bisolvon Extra")
        $abaikan = [
            'obat', 'medis', 'digital', 'extract', 'ekstrak', 'dewasa', 'anak', 'bayi', 'balita', 'plus', 'forte',
            'extra', 'kids', 'adult', 'junior', 'senior', 'mini', 'super', 'max', 'gold', 'advance', 'total', 'pro',
            'mild', 'soft', 'regular', 'reguler',
            'biru', 'merah', 'kuning', 'hijau', 'putih', 'hitam', 'coklat', 'abu', 'ungu', 'oranye', 'orange',
            'bedak', 'salep', 'krim', 'cream', 'gel', 'sirup', 'syr', 'syrup', 'tablet', 'tab', 'kaplet', 'kap',
            'kapsul', 'cap', 'tetes', 'drop', 'drops', 'cair', 'cairan', 'larutan', 'injeksi', 'injek', 'infus',
            'spray', 'patch', 'serbuk', 'bubuk', 'botol', 'strip', 'sachet', 'tube', 'roll', 'kain', 'kasa', 'perban',
            'plester', 'kapas', 'swab', 'alkohol', 'minyak',
            'rasa', 'jeruk', 'madu', 'lemon', 'mint', 'strawberry', 'biasa', 'ringan', 'berat', 'tinggi',
            'luka', 'sakit', 'nyeri', 'panas', 'darah', 'kulit', 'badan', 'tangan', 'kaki', 'mata', 'gigi', 'gusi',
            'nellco', 'lang', 'kencana', 'kimia', 'farma', 'kalbe', 'sanbe', 'tempo', 'jaya', 'sanofi', 'haleon',
            'novapharin', 'hexpharm', 'ifars', 'mersi', 'erela', 'bernofarm', 'mulia', 'suci', 'pharos', 'dexa',
            'soho', 'bayer', 'combiphar', 'phapros', 'indofarma', 'apotek', 'sehat', 'prima', 'indo', 'medika', 'hj', 'mef'
        ];

        $shortMedicineTokens = ['ctm', 'obh', 'zinc', 'nacl', 'h2o2'];

        // Ekstrak angka dosis dari teks user (misal "500mg", "60ml") untuk bonus akurasi varian
        preg_match_all('/\b\d+(?:[.,]\d+)?\s*(?:mg|ml|mcg|g|gr|iu|%)\b/iu', $teks, $teksDoseMatches);
        $teksDoseTokens = array_map(fn ($d) => preg_replace('/\s+/u', '', mb_strtolower($d)), $teksDoseMatches[0] ?? []);

        $scored = $obat->map(function ($item) use ($teks, $abaikan, $shortMedicineTokens, $teksDoseTokens) {
            $nama = mb_strtolower(trim($item['nama_obat'] ?? ''));
            if ($nama === '') {
                return null;
            }

            $score = 0;

            // 1. Nama lengkap persis terkandung dalam pesan
            if (mb_strlen($nama) >= 4 && str_contains($teks, $nama)) {
                $score += 200 + mb_strlen($nama);
            }

            $namaInti = preg_replace('/\b\d+(?:[.,]\d+)?\s*(?:mg|g|ml|l|ply|tablet|kapsul|sachet|botol|box|unit)\b/iu', '', $nama);
            $namaInti = trim(preg_replace('/\s+/u', ' ', $namaInti));

            // 2. Seluruh nama inti tanpa dosis terkandung dalam pesan
            if (mb_strlen($namaInti) >= 4 && !in_array($namaInti, $abaikan, true) && str_contains($teks, $namaInti)) {
                $score += 120 + mb_strlen($namaInti);
            }

            $kataKata = array_values(array_filter(preg_split('/[\s()\[\]{},;\/]+/u', $namaInti)));
            $jumlahKata = count($kataKata);

            // 3. Frasa 2 kata berurutan dari nama obat (misal: "panadol extra", "salicyl biru", "kayu putih")
            for ($i = 0; $i < $jumlahKata - 1; $i++) {
                $k1 = trim($kataKata[$i]);
                $k2 = trim($kataKata[$i + 1]);
                if (in_array($k1, $abaikan, true) && in_array($k2, $abaikan, true)) {
                    continue;
                }
                $frasaDuaKata = $k1 . ' ' . $k2;
                if (mb_strlen($frasaDuaKata) >= 6 && !in_array($frasaDuaKata, $abaikan, true) && str_contains($teks, $frasaDuaKata)) {
                    $score += 60;
                }
            }

            // 4. Kecocokan kata tunggal: wajib kata merek/zat aktif unik (panjang >= 5 atau token obat pendek terdaftar)
            $significantMatched = 0;
            $secondaryMatched = 0;
            foreach ($kataKata as $k) {
                $k = trim($k);
                if ($k === '' || !preg_match('/\b' . preg_quote($k, '/') . '\b/iu', $teks)) {
                    continue;
                }
                if (in_array($k, $abaikan, true)) {
                    $secondaryMatched++;
                } elseif (mb_strlen($k) >= 5 || in_array($k, $shortMedicineTokens, true)) {
                    $significantMatched++;
                    $score += 25 + mb_strlen($k);
                }
            }

            if ($score === 0) {
                return null;
            }

            // Bonus bila varian generik/takaran yang diminta user juga ada di nama obat
            $score += $secondaryMatched * 12;
            foreach ($teksDoseTokens as $doseTok) {
                if (str_contains(str_replace(' ', '', $nama), $doseTok)) {
                    $score += 20;
                }
            }
            if ((int) ($item['stok'] ?? 0) > 0) {
                $score += 3;
            }

            return ['item' => $item, 'score' => $score];
        })->filter()
            ->sortByDesc('score')
            ->pluck('item')
            ->values();

        return $scored;
    }

    private function isMedicineDosageOrUsageQuestion(string $teks): bool
    {
        return (bool) preg_match('/(berapa\s+kali|aturan\s+pakai|aturan\s+minum|cara\s+pakai|cara\s+minum|pemakaian|dosis|sebelum\s+atau\s+sesudah\s+makan)/u', $teks);
    }

    private function handleMedicineUsage($user, Collection $obatCocok, string $teks)
    {
        $item = $obatCocok->first();
        $nama = $item['nama_obat'];
        $kategori = $item['kategori'];

        $aturan = $this->getKnownMedicineDosageGuidance($nama);

        if ($aturan !== null) {
            $reply = "📋 **Aturan Pakai & Dosis Umum: {$nama}**\n"
                . "🏷️ *Kategori: {$kategori}*\n\n"
                . "{$aturan}\n\n"
                . "⚠️ *Aturan di atas merupakan dosis umum dewasa. Konsultasikan dengan apoteker atau dokter untuk kondisi khusus.*";
            return $this->sendReply($user, $reply);
        }

        $keterangan = $this->getMedicineDescription($nama, (string) ($item['deskripsi'] ?? ''));

        $reply = "📋 **Petunjuk Pemakaian: {$nama}** [{$kategori}]\n\n"
            . "Keterangan: {$keterangan}\n\n"
            . "⚠️ *Secara umum, konsumsi obat harus mengikuti anjuran resep dokter atau petunjuk pada kemasan obat.*";

        return $this->sendReply($user, $reply);
    }

    private function getKnownMedicineDosageGuidance(string $namaObat): ?string
    {
        $nama = mb_strtolower($namaObat);

        if (str_contains($nama, 'asam mefenamat') || str_contains($nama, 'mefenamic')) {
            return "- **Dosis Dewasa:** 3 kali sehari 1 tablet/kapsul (500 mg), tiap 8 jam saat merasakan nyeri.\n"
                . "- **Waktu Minum:** **WAJIB sesudah makan** dengan segelas air putih untuk mencegah iritasi lambung.\n"
                . "- **Durasi:** Digunakan hanya saat nyeri terasa, maksimal 5–7 hari berturut-turut.";
        }
        if (str_contains($nama, 'paracetamol') || str_contains($nama, 'panadol')) {
            return "- **Dosis Dewasa:** 3–4 kali sehari 1–2 tablet (500–1000 mg) bila demam/nyeri terasa (jeda 4–6 jam).\n"
                . "- **Waktu Minum:** Dapat diminum sebelum atau sesudah makan.\n"
                . "- **Maksimal:** 4000 mg per hari.";
        }
        if (str_contains($nama, 'amoxicillin') || str_contains($nama, 'amoksisilin')) {
            return "- **Dosis Dewasa:** 3 kali sehari 1 kaplet (500 mg) tiap 8 jam sesudah makan.\n"
                . "- **PENTING (Antibiotik):** Wajib dihabiskan sesuai durasi resep dokter agar tidak kebal bakteri.";
        }
        if (str_contains($nama, 'omeprazole')) {
            return "- **Dosis Dewasa:** 1 kali sehari 1 kapsul (20 mg) di pagi hari.\n"
                . "- **Waktu Minum:** **30–60 menit SEBELUM makan** (kapsul ditelan utuh dengan air).";
        }
        if (str_contains($nama, 'cetirizine')) {
            return "- **Dosis Dewasa:** 1 kali sehari 1 tablet (10 mg), disarankan malam hari sebelum tidur karena dapat memicu kantuk.";
        }
        if (str_contains($nama, 'ibuprofen')) {
            return "- **Dosis Dewasa:** 3 kali sehari 1 tablet sesudah makan.";
        }

        return null;
    }

    private function handleMedicineStock($user, Collection $obatCocok, string $teks = '', ?Collection $riwayat = null)
    {
        $isSlang = $this->isSlangOrCasualMessage($teks, $riwayat);
        $totalCocok = $obatCocok->count();
        $readyCount = $obatCocok->where('stok', '>', 0)->count();
        $habisCount = $totalCocok - $readyCount;
        $tampil = $obatCocok->take(10);
        $contohNama = $this->summarizeMedicineNames($tampil, 3);

        $rows = $tampil->map(function ($item) {
            $status = $item['stok'] > 0 ? "Tersedia ({$item['stok']} {$item['satuan']})" : 'Habis (0)';
            $harga = is_numeric($item['harga_umum']) ? 'Rp ' . number_format($item['harga_umum'], 0, ',', '.') : 'Rp -';
            $kode = $item['kode'] ?: '—';
            return "| `{$kode}` | {$item['nama_obat']} | {$item['kategori']} | {$status} | {$harga} |";
        })->implode("\n");

        $table = "| Kode | Nama Obat | Golongan | Stok | Harga |\n"
            . "| :--- | :--- | :--- | :--- | :--- |\n"
            . $rows;

        if ($isSlang) {
            $reply = "Siap bestie! Di database **Apotek Itu Toko Obat** ketemu **{$totalCocok} varian** yang cocok (**{$readyCount} tersedia** dan **{$habisCount} habis**), antara lain {$contohNama}. Langsung gue *spill* data stok real-time-nya nih! 🔥\n\n"
                . $table . "\n\n"
                . $this->buildPharmacistClosingNote($tampil, true, 'Kalau mau cek khasiat, aturan pakai, alternatif lain yang ready, atau sekalian ajukan tambah/kurangi stok, tinggal sebutin aja ya!');
        } else {
            $reply = "Berdasarkan data stok real-time **Apotek Itu Toko Obat**, ditemukan **{$totalCocok} produk** yang sesuai (**{$readyCount} tersedia** dan **{$habisCount} habis**), yaitu {$contohNama}:\n\n"
                . $table . "\n\n"
                . $this->buildPharmacistClosingNote($tampil, false, 'Jika ingin mengetahui khasiat, aturan pakai, alternatif pengganti yang tersedia, atau mengajukan update stok, silakan beri tahu saya ya.');
        }

        return $this->sendReply($user, $reply);
    }

    private function summarizeMedicineNames(Collection $items, int $limit = 3): string
    {
        $names = $items->pluck('nama_obat')->filter()->values();
        $total = $names->count();
        if ($total === 0) {
            return '';
        }

        $shown = $names->take($limit)->map(fn ($n) => "**{$n}**")->all();
        if ($total <= $limit) {
            return count($shown) > 1
                ? implode(', ', array_slice($shown, 0, -1)) . ', dan ' . end($shown)
                : $shown[0];
        }

        $sisa = $total - $limit;
        return implode(', ', $shown) . ", dan {$sisa} varian lainnya";
    }

    private function buildPharmacistClosingNote(Collection $items, bool $isSlang = false, string $customClosing = ''): string
    {
        $hasObatKeras = $items->contains(fn ($item) => str_contains(mb_strtolower((string) ($item['kategori'] ?? '')), 'keras'));
        $notes = [];

        if ($hasObatKeras) {
            $notes[] = $isSlang
                ? '📌 *Catatan apoteker:* Ada item berlabel **Obat Keras** (logo K merah) di daftar atas ya — wajib pakai resep dokter resmi biar aman!'
                : '📌 *Catatan Apoteker:* Produk berlabel **Obat Keras** (logo K merah) wajib disertai resep dokter resmi.';
        }

        if ($customClosing !== '') {
            $notes[] = "💬 {$customClosing}";
        } else {
            $notes[] = $isSlang
                ? '💬 *Kalau butuh aturan pakai, efek samping, atau opsi alternatif lain yang ready, gas langsung tanya lagi ya!*'
                : '💬 *Jika ingin menanyakan aturan pakai, efek samping, atau mencari alternatif lainnya, silakan beri tahu saya ya!*';
        }

        return implode("\n\n", $notes);
    }

    private function handleMedicineDetail($user, Collection $obatCocok, ?Collection $semuaObat = null, string $teks = '', ?Collection $riwayat = null)
    {
        $isSlang = $this->isSlangOrCasualMessage($teks, $riwayat);
        $totalCocok = $obatCocok->count();
        $readyItems = $obatCocok->where('stok', '>', 0)->values();
        $habisItems = $obatCocok->where('stok', '<=', 0)->values();
        $tampilUtama = $obatCocok->take(10);

        $semuaHabis = $readyItems->isEmpty();
        $mintaAlternatif = $teks !== '' && preg_match('/\b(alternatif|alternatifnya|pengganti|penggantinya|opsi\s+lain|selain\s+itu|kalau\s+habis|kalo\s+habis|kalau\s+(?:ga|gak|nggak|tidak)\s+ada|kalo\s+(?:ga|gak|nggak|tidak)\s+ada)\b/iu', $teks);

        $alternatif = collect();
        if ($semuaObat !== null && ($semuaHabis || $mintaAlternatif)) {
            $alternatif = $this->findAlternativeMedicines($teks, $semuaObat, $obatCocok)->take(6)->values();
        }

        // Bangun keterangan pembuka yang merangkum produk tersedia & alternatifnya
        $ringkasanReady = $readyItems->isNotEmpty()
            ? "**{$readyItems->count()} varian tersedia**, di antaranya " . $this->summarizeMedicineNames($readyItems, 2)
            : "**semua varian sedang habis (`0`)**";
        $infoHabis = ($readyItems->isNotEmpty() && $habisItems->isNotEmpty())
            ? "; serta **{$habisItems->count()} varian habis**"
            : '';

        if ($isSlang) {
            $opening = "Siap! Gue udah cek database **Apotek Itu Toko Obat**, ketemu **{$totalCocok} varian** yang cocok — {$ringkasanReady}{$infoHabis}.";
            if ($alternatif->isNotEmpty()) {
                $opening .= " Selain itu, ada **{$alternatif->count()} alternatif serupa yang ready (`Stok > 0`)** yaitu " . $this->summarizeMedicineNames($alternatif, 3) . '.';
            }
        } else {
            $opening = "Berdasarkan data inventaris **Apotek Itu Toko Obat**, ditemukan **{$totalCocok} varian** obat yang sesuai — {$ringkasanReady}{$infoHabis}.";
            if ($alternatif->isNotEmpty()) {
                $opening .= " Sebagai alternatif dengan kegunaan serupa yang masih tersedia (`Stok > 0`), terdapat **{$alternatif->count()} pilihan** seperti " . $this->summarizeMedicineNames($alternatif, 3) . '.';
            } elseif ($semuaHabis || $mintaAlternatif) {
                $opening .= ' Saat ini belum ditemukan alternatif lain berstok `> 0` pada kelompok zat aktif yang sama di database.';
            }
        }

        $reply = "{$opening}\n\n"
            . "💊 **Detail Data Obat di Database Apotek:**\n\n"
            . $this->formatMedicineMarkdownTable($tampilUtama, true);

        if ($alternatif->isNotEmpty()) {
            $heading = $semuaHabis
                ? "\n\n⚠️ *Stok obat yang dicari sedang habis. Berikut **alternatif serupa yang tersedia (`Stok > 0`)** di apotek:*"
                : "\n\n🔄 **Alternatif Obat Serupa yang Tersedia (`Stok > 0`) di Database:**";
            $reply .= "{$heading}\n\n" . $this->formatMedicineMarkdownTable($alternatif, true);
        }

        $combinedShown = $tampilUtama->concat($alternatif);
        $customClose = $isSlang
            ? 'Mau gue *spill* aturan pakai/dosis salah satu obat di atas, atau sekalian ajukan update stok? Tinggal sebutin aja ya!'
            : 'Jika ingin mengetahui aturan pakai/dosis lebih rinci atau mengajukan update stok untuk salah satu obat di atas, silakan beri tahu saya ya!';

        $reply .= "\n\n" . $this->buildPharmacistClosingNote($combinedShown, $isSlang, $customClose);

        return $this->sendReply($user, $reply);
    }

    private function handleMedicineOverview($user, Collection $obatCocok, ?Collection $semuaObat = null, string $teks = '', ?Collection $riwayat = null)
    {
        return $this->handleMedicineDetail($user, $obatCocok, $semuaObat, $teks, $riwayat);
    }

    private function getEquivalentIngredientTokens(string $text): array
    {
        $kw = mb_strtolower($text);
        $groups = [
            ['keywords' => ['paramex', 'panadol', 'bodrex', 'sanmol', 'biogesic', 'sumagesic', 'dumin', 'tempra', 'paracetamol'], 'targets' => ['paracetamol', 'panadol', 'sanmol', 'ibuprofen', 'asam mefenamat']],
            ['keywords' => ['ponstan', 'mefinal', 'asam mefenamat', 'mefenamic', 'cataflam', 'voltaren', 'diclofenac', 'diklofenak', 'proris', 'ibuprofen', 'meloxicam', 'piroxicam'], 'targets' => ['ibuprofen', 'asam mefenamat', 'diclofenac', 'diklofenak', 'meloxicam', 'piroxicam', 'paracetamol']],
            ['keywords' => ['promag', 'mylanta', 'polysilane', 'polycrol', 'plantacid', 'antasida', 'magasida', 'omeprazole', 'lansoprazole', 'pantoprazole', 'ranitidine', 'sucralfate', 'inpepsa', 'episan', 'domperidone'], 'targets' => ['antasida', 'omeprazole', 'lansoprazole', 'ranitidine', 'sucralfate', 'domperidone']],
            ['keywords' => ['obh', 'woods', 'komix', 'vicks', 'bisolvon', 'mucohexin', 'epexol', 'fluimucil', 'acetylcisteine', 'ambroxol', 'bromhexine', 'siladex', 'demacolin', 'alpara', 'flutamol', 'neozep', 'decolgen', 'mixagrip', 'ultraflu', 'procold', 'sanaflu', 'rhinos', 'tremenza', 'actifed'], 'targets' => ['ambroxol', 'bromhexine', 'acetylcisteine', 'obh', 'bisolvon', 'demacolin', 'alpara', 'flutamol', 'coparcetin']],
            ['keywords' => ['incidal', 'ctm', 'chlorpheniramine', 'alleron', 'lerzin', 'cetirizine', 'claritin', 'loratadine', 'ryvel', 'ozen'], 'targets' => ['cetirizine', 'loratadine', 'chlorpheniramine', 'alleron', 'lerzin']],
            ['keywords' => ['diapet', 'diatab', 'entrostop', 'lodia', 'imodium', 'loperamide', 'attapulgite', 'oralit', 'phoralit', 'zinc', 'lecozinc', 'holizinc'], 'targets' => ['attapulgite', 'loperamide', 'oralit', 'zinc', 'lecozinc', 'holizinc']],
            ['keywords' => ['betadine', 'povidone', 'rivanol', 'bioplacenton', 'gentamicin', 'nebacetin', 'hansaplast', 'alkohol'], 'targets' => ['povidone', 'rivanol', 'bioplacenton', 'gentamicin', 'alkohol']],
            ['keywords' => ['insto', 'rohto', 'braito', 'visine', 'cendo'], 'targets' => ['insto', 'rohto', 'cendo']],
            ['keywords' => ['degirol', 'strepsils', 'troches', 'aloclair'], 'targets' => ['degirol', 'troches']],
            ['keywords' => ['ventolin', 'lasal', 'salbutamol', 'grafalin', 'aminophylline', 'teosal'], 'targets' => ['salbutamol', 'grafalin', 'teosal']],
            ['keywords' => ['mertigo', 'vastigo', 'betahistine'], 'targets' => ['betahistine', 'vastigo']],
            ['keywords' => ['norvask', 'amlodipine', 'captopril', 'valsartan', 'candesartan'], 'targets' => ['amlodipine', 'captopril', 'candesartan']],
            ['keywords' => ['glucophage', 'metformin', 'glimepiride', 'glibenclamid'], 'targets' => ['metformin', 'glimepiride', 'glibenclamid']],
            ['keywords' => ['lipitor', 'atorvastatin', 'simvastatin'], 'targets' => ['simvastatin', 'atorvastatin']],
            ['keywords' => ['acifar', 'acyclovir', 'asiklovir', 'zovirax'], 'targets' => ['acyclovir', 'acifar']],
            ['keywords' => ['amoxsan', 'amoxicillin', 'cefixime', 'cefadroxil', 'ciprofloxacin', 'azithromycin'], 'targets' => ['amoxicillin', 'cefadroxil', 'cefixime', 'ciprofloxacin']],
        ];

        $tokens = [];
        foreach ($groups as $g) {
            foreach ($g['keywords'] as $trigger) {
                if (preg_match('/\b' . preg_quote($trigger, '/') . '\b/iu', $kw)) {
                    $tokens = array_merge($tokens, $g['targets']);
                    break;
                }
            }
        }

        return array_values(array_unique($tokens));
    }

    private function findAlternativeMedicines(string $teks, Collection $obat, ?Collection $excludeItems = null): Collection
    {
        $excludedIds = $excludeItems
            ? $excludeItems->pluck('id')->filter()->all()
            : [];

        $seedText = $teks;
        if ($excludeItems && $excludeItems->isNotEmpty()) {
            $seedText .= ' ' . $excludeItems->pluck('nama_obat')->implode(' ');
        }

        $alternatifTokens = $this->getEquivalentIngredientTokens($seedText);
        $inStock = $obat->filter(function ($item) use ($excludedIds) {
            return (int) ($item['stok'] ?? 0) > 0 && !in_array($item['id'] ?? null, $excludedIds, true);
        });

        if (!empty($alternatifTokens)) {
            $byEquiv = $inStock->filter(function ($item) use ($alternatifTokens) {
                $nama = mb_strtolower($item['nama_obat'] ?? '');
                foreach ($alternatifTokens as $tok) {
                    if (str_contains($nama, $tok)) {
                        return true;
                    }
                }
                return false;
            })->values();

            if ($byEquiv->isNotEmpty()) {
                return $byEquiv->take(8)->values();
            }
        }

        // Fallback: jika item asli diketahui kategorinya & tidak umum, sarankan kategori yang sama
        if ($excludeItems && $excludeItems->isNotEmpty()) {
            $kat = mb_strtolower(trim((string) ($excludeItems->first()['kategori'] ?? '')));
            if ($kat !== '' && !in_array($kat, ['umum', 'lainnya'], true)) {
                return $inStock->filter(fn ($item) => mb_strtolower(trim((string) ($item['kategori'] ?? ''))) === $kat)
                    ->take(5)
                    ->values();
            }
        }

        return collect();
    }

    private function isStockOrPriceQuestion(string $teks): bool
    {
        if ($this->isMedicineDosageOrUsageQuestion($teks)) {
            return false;
        }
        return (bool) preg_match('/(stok|harga|tersedia|ready|habis|abis|kosong|harganya|berapa\s+(harga|stok|jumlahnya|biaya)|masih\s+ada|(?:apa|apakah)\s+ada|ada\s+nggak|ada\s+gak|ada\s+ga|gaada|gada|punya|jual|beli|spill)/u', $teks);
    }

    private function isMedicineAvailabilityInquiry(string $teks): bool
    {
        if (
            $this->isMedicineDosageOrUsageQuestion($teks)
            || $this->isMedicineDetailQuestion($teks)
            || preg_match('/\b(ada\s+berapa|berapa|harga|harganya|total|seluruh|semua|daftar|list|gimana|bagaimana|kenapa|mengapa|caranya|cara)\b/iu', $teks)
        ) {
            return false;
        }

        return (bool) preg_match('/\b(?:(?:apa(?:kah)?\s+ada|masih\s+ada)\s+(?:stok\s+|obat\s+)?(?!yang\b|apa\b|berapa\b|di\b)[a-z0-9\-]{3,}|ada\s+(?:stok\s+|obat\s+)?(?!yang\b|obat\b|apa\b|berapa\b|di\b)[a-z0-9\-]{3,}(?:\s+(?:ga|gak|nggak|tidak|kah|nih|min|bro|bestie))?\s*[?!.]*$|stok\s+(?!obat\b|yang\b|di\b|ini\b|itu\b|nya\b)[a-z0-9\-]{3,}|spill\s+(?:stok\s+|obat\s+)?(?!obat\b|yang\b)[a-z0-9\-]{3,}|jual\s+(?!obat\b|apa\b)[a-z0-9\-]{3,}|tersedia\s+(?!obat\b)[a-z0-9\-]{3,}|[a-z0-9\-]{3,}\s+(?:ready|ada)\s+(?:ga|gak|nggak|gaada|gada))\b/iu', $teks);
    }

    private function handleMissingMedicineAvailability($user, string $teks, Collection $obat, ?Collection $riwayat = null)
    {
        $isSlang = $this->isSlangOrCasualMessage($teks, $riwayat);
        $clean = preg_replace('/\b(apakah|apa|masih|ada|tersedia|ready|punya|jual|beli|stok|spill|kepo|obat|produk|merek|merk|yang|yg|di\s+apotek|disini|di\s+sini|ya|dong|kak|min|bang|pak|bu|bro|sis|bestie|besti|cuy|gan|nih|sih|ga|gak|nggak|tidak|kah|gaada|gada|apaan)\b/iu', ' ', $teks);
        $clean = trim(preg_replace('/[^a-z0-9\s\-]/iu', ' ', $clean));
        $clean = preg_replace('/\s+/u', ' ', $clean);
        $namaDicari = $clean !== '' ? ucwords($clean) : 'obat yang ditanyakan';

        $alternatif = $this->findAlternativeMedicines(mb_strtolower($clean), $obat)->take(4);

        $reply = $isSlang
            ? "Waduh, jujurly produk **{$namaDicari}** lagi **kosong / belum terdaftar** di inventaris **Apotek Itu Toko Obat**, nih."
            : "Mohon maaf, produk **{$namaDicari}** saat ini **tidak tersedia / belum terdaftar** di inventaris **APOTEK ITU TOKO OBAT**.";

        if ($alternatif->isNotEmpty()) {
            $reply .= ($isSlang
                ? "\n\n💊 **Tenang, ini ada opsi alternatif yang ready & fungsinya sefrekuensi:**\n\n"
                : "\n\n💊 **Alternatif Obat Tersedia dengan Kandungan/Fungsi Serupa:**\n\n")
                . $this->formatMedicineMarkdownTable($alternatif);
            $reply .= $isSlang
                ? "\n\nGas bilang aja kalau mau gue *spill* aturan pakai atau detail salah satu obat di atas ya!"
                : "\n\nSilakan beri tahu jika ingin menanyakan aturan pakai atau detail salah satu alternatif di atas.";
        } else {
            $reply .= $isSlang
                ? "\n\n💡 Coba sebutin nama zat aktifnya (misal *Paracetamol*, *Ambroxol*, *Antasida*) atau ketik **daftar obat tersedia** biar langsung gue cekin yang *ready*."
                : "\n\n💡 Anda bisa menanyakan nama zat aktifnya (misal: *Paracetamol*, *Ambroxol*, *Antasida*) atau ketik **daftar obat tersedia** untuk melihat stok yang ada saat ini.";
        }

        Log::info('PharmAI Medicine Not Found', [
            'query' => $teks,
            'searched_name' => $namaDicari,
            'alternatives_count' => $alternatif->count(),
        ]);

        return $this->sendReply($user, $reply);
    }

    private function isMedicineDetailQuestion(string $teks): bool
    {
        return (bool) preg_match('/(buat\s+apa|untuk\s+apa|obat\s+apa|fungsi|kegunaan|manfaat|khasiat|deskripsi)/u', $teks);
    }

    /*
    |--------------------------------------------------------------------------
    | KATEGORI OBAT
    |--------------------------------------------------------------------------
    */

    private function isCategoryListRequest(string $teks): bool
    {
        return (bool) preg_match('/(kategori\s+obat|jenis\s+obat|ada\s+kategori\s+apa|daftar\s+kategori)/u', $teks);
    }

    private function handleCategoryList($user, Collection $kategori, Collection $obat)
    {
        $cleanCell = fn(?string $v) => trim(preg_replace('/\s+/u', ' ', str_replace('|', '/', (string) $v))) ?: '—';
        $rows = $kategori->values()->map(function ($kat, $idx) use ($cleanCell) {
            return "| " . ($idx + 1) . " | {$cleanCell($kat['nama'])} | {$kat['total_obat']} produk | {$cleanCell($kat['deskripsi'])} |";
        })->implode("\n");

        $reply = "Berikut **{$kategori->count()} kategori / jenis obat** yang terdaftar di **Apotek Itu Toko Obat**:\n\n"
            . "| No | Kategori | Jumlah Produk | Deskripsi |\n"
            . "| :--- | :--- | :--- | :--- |\n"
            . $rows
            . "\n\n💡 *Ketik nama kategori (misal: 'obat bebas' atau 'obat herbal'), nama obat, atau keluhan yang ingin dicari untuk melihat daftar produk beserta status stoknya ya!*";

        return $this->sendReply($user, $reply);
    }

    private function findCategoryInMessage(string $teks, Collection $kategori): ?array
    {
        foreach ($kategori as $kat) {
            $nama = mb_strtolower($kat['nama']);
            if (str_contains($teks, $nama)) {
                return $kat;
            }
            if (str_contains($nama, 'vitamin') && (str_contains($teks, 'vitamin') || str_contains($teks, 'suplemen'))) {
                return $kat;
            }
            if (str_contains($nama, 'herbal') && (str_contains($teks, 'herbal') || str_contains($teks, 'jamu'))) {
                return $kat;
            }
        }
        return null;
    }

    private function handleCategoryMedicines($user, array $kategori, Collection $obat)
    {
        $nama = $kategori['nama'];
        $produk = $obat->filter(function ($item) use ($nama) {
            return strcasecmp($item['kategori'], $nama) === 0;
        })->values();

        if ($produk->isEmpty()) {
            return $this->sendReply($user, "Saat ini belum ada produk dalam kategori **{$nama}** di apotek kami. Kalau ingin mencari kategori atau nama obat lain, silakan beri tahu ya!");
        }

        $total = $produk->count();
        $readyCount = $produk->where('stok', '>', 0)->count();
        $habisCount = $total - $readyCount;
        $tampil = $produk->take(15);
        $contohNama = $this->summarizeMedicineNames($tampil, 3);

        $reply = "Terdapat total **{$total} produk** dalam kategori **{$nama}** (**{$readyCount} tersedia** dan **{$habisCount} habis**), di antaranya {$contohNama}:\n\n"
            . $this->formatMedicineMarkdownTable($tampil);

        if ($total > 15) {
            $reply .= "\n\n*(Menampilkan 15 dari total {$total} produk dalam kategori {$nama}).*";
        }

        $reply .= "\n\n" . $this->buildPharmacistClosingNote(
            $tampil,
            false,
            'Sebutkan nama salah satu obat di atas jika ingin melihat khasiat, aturan pakai, atau mencari alternatif serupa yang tersedia.'
        );

        return $this->sendReply($user, $reply);
    }

    /*
    |--------------------------------------------------------------------------
    | INTEGRASI AI: GOOGLE GEMINI (UTAMA) & OLLAMA (BACKUP LOKAL)
    |--------------------------------------------------------------------------
    */

    private function callAIProvider(string $teks, Collection $riwayat, Collection $obat, Collection $kategori, string $namaUser, ?User $user = null): string
    {
        $provider = config('services.ai.provider', env('AI_PROVIDER', 'gemini'));
        $temperature = (float) env('AI_TEMPERATURE', 1.35);
        $systemInstruction = $this->buildPharmAIPersonaPrompt($user, $namaUser, $obat, $teks, $riwayat);

        // 1. GOOGLE GEMINI (Multi-Model Flash Fast Fallback)
        if ($provider === 'gemini' && !empty(env('GEMINI_API_KEY'))) {
            $rawApiKeys = env('GEMINI_API_KEY');
            $apiKeys = array_values(array_filter(array_map('trim', explode(',', $rawApiKeys))));
            $modelList = array_values(array_filter(array_map('trim', explode(',', config('services.ai.gemini.model', env('GEMINI_MODEL', 'gemini-3.5-flash-lite,gemini-3-flash-preview,gemini-3.6-flash,gemini-3.5-flash'))))));
            if (empty($modelList)) {
                $modelList = ['gemini-3.5-flash-lite', 'gemini-3-flash-preview', 'gemini-3.6-flash', 'gemini-3.5-flash'];
            }

            // Susun history percakapan multi-turn untuk Gemini API (alternating user/model yang valid)
            $contents = [];
            $historyItems = $riwayat ? $riwayat->take(-6)->values() : collect();

            foreach ($historyItems as $msg) {
                $role = ($msg->role === 'assistant') ? 'model' : 'user';
                $content = trim((string) $msg->content);
                if ($content === '') {
                    continue;
                }
                // Gemini API mensyaratkan giliran pertama adalah 'user'
                if (empty($contents) && $role !== 'user') {
                    continue;
                }
                if (!empty($contents) && $contents[array_key_last($contents)]['role'] === $role) {
                    $contents[array_key_last($contents)]['parts'][0]['text'] .= "\n" . $content;
                    continue;
                }
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $content]],
                ];
            }

            if (!empty($contents) && $contents[array_key_last($contents)]['role'] === 'user') {
                $contents[array_key_last($contents)]['parts'][0]['text'] .= "\n" . $teks;
            } else {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => $teks]],
                ];
            }

            foreach ($apiKeys as $apiKey) {
                foreach ($modelList as $rawModel) {
                    $model = trim($rawModel);
                    if ($model === '') continue;

                    try {
                        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                        $response = Http::connectTimeout(5)->timeout(20)->post($url, [
                            'systemInstruction' => [
                                'parts' => [['text' => $systemInstruction]],
                            ],
                            'contents' => $contents,
                            'generationConfig' => [
                                'temperature' => $temperature,
                                'topP' => 0.95,
                                'maxOutputTokens' => 4096,
                            ],
                        ]);

                        if ($response->successful()) {
                            $parts = $response->json('candidates.0.content.parts') ?? [];
                            $finishReason = $response->json('candidates.0.finishReason');
                            $replyText = collect($parts)
                                ->reject(fn($p) => !empty($p['thought']))
                                ->pluck('text')
                                ->filter(fn($t) => is_string($t) && trim($t) !== '')
                                ->implode('');

                            if (trim($replyText) !== '') {
                                Log::info("Gemini API success [{$model}]", [
                                    'finish_reason' => $finishReason,
                                    'reply_len' => mb_strlen(trim($replyText)),
                                    'temperature' => $temperature,
                                ]);
                                if ($finishReason === 'MAX_TOKENS' && mb_strlen(trim($replyText)) < 450) {
                                    Log::warning("Gemini response truncated early [{$model}], trying next fallback.");
                                    continue;
                                }
                                return trim($replyText);
                            }
                        } else {
                            Log::warning("Gemini API non-200 [{$model}]: " . substr($response->body(), 0, 200));
                        }
                    } catch (Throwable $e) {
                        Log::warning("Gemini model [{$model}] error: " . $e->getMessage());
                    }
                }
            }
        }

        // 3. BACKUP LOKAL: OLLAMA
        $url = config('services.ai.ollama.url', env('OLLAMA_URL', 'http://127.0.0.1:11434/api/chat'));
        $model = config('services.ai.ollama.model', env('OLLAMA_MODEL', 'qwen2.5:1.5b'));

        try {
            $response = Http::timeout(4)->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemInstruction],
                    ['role' => 'user', 'content' => $teks]
                ],
                'stream' => false,
                'options' => ['temperature' => $temperature],
            ]);

            if ($response->successful()) {
                $reply = $response->json('message.content');
                if (is_string($reply) && trim($reply) !== '') {
                    return trim($reply);
                }
            }
        } catch (Throwable $e) {
            // Ollama offline
        }

        if ($this->isHealthOrMedicalQuery($teks, $riwayat)) {
            return $this->getGeneralHealthGuidanceText($teks, $namaUser, $obat, $riwayat);
        }

        return "Hai {$namaUser}! 👋 Saya **PharmAI** dari **Apotek Itu Toko Obat**. Ada informasi obat, aturan pakai, atau ketersediaan stok yang bisa aku bantu hari ini? 😊";
    }

    private function buildPharmAIPersonaPrompt(?User $user, string $namaUser, Collection $obat, string $teks, ?Collection $riwayat = null): string
    {
        $fullName = trim((string) ($user?->name ?: $namaUser)) ?: 'Rekan Apotek';
        $username = $user?->email ? explode('@', $user->email)[0] : mb_strtolower(preg_replace('/\s+/', '', $fullName));
        $role = $user?->role ?? 'user';
        $isSlang = $this->isSlangOrCasualMessage($teks, $riwayat);

        $adaptiveStyleInstruction = $isSlang
            ? "MODE GAUL ADAPTIF AKTIF: Lawan bicara sedang chat dengan gaya bahasa santai/gaul/slang! Ikut balas dengan bahasa gaul Indonesia yang luwes, seru, dan natural (misal pakai 'gue/lo' atau 'aku/kamu santai', sapaan akrab seperti 'bestie/bro/kak/bosku' menyesuaikan peran, serta istilah populer seperti 'sat-set', 'gercep', 'spill', 'relate', 'vibes', 'healing', 'auto aman', 'no debat'). TAPI WAJIB jaga agar penjelasan medis, mekanisme obat, dosis, peringatan Obat Keras (Logo K Merah), dan data stok/tabel tetap LENGKAP, AKURAT, dan SANGAT INFORMATIF (jangan menyederhanakan fakta medis!)."
            : "MODE GAUL ADAPTIF (STANDAR): Lawan bicara berbicara dengan gaya standar/rapi. Balas dengan bahasa Indonesia yang hangat, akrab, cerdas, dan punya humor apoteker ringan. Namun jika di kalimatnya atau percakapan berikutnya lawan bicara memakai bahasa gaul/slang, langsung sesuaikan gaya balasmu menjadi gaul & seru tanpa mengorbankan kelengkapan dan akurasi informasi medis.";

        [$roleDisplay, $roleGreetingGuide] = match ($role) {
            'admin' => [
                'Admin Apotek',
                "Sapa sebagai partner kerja satu tim di apotek dengan akrab, lincah, dan bersemangat (misal 'Siap Kak {$fullName}', 'Gaspol Kak!', atau sapaan hangat rekan sejawat apoteker).",
            ],
            'pemilik' => [
                'Pemilik Apotek',
                "Sapa dengan sopan namun tetap hangat, cair, dan bersahabat kepada atasan/pemilik apotek (misal 'Siap Pak/Bu {$fullName}', santun tanpa terdengar kaku seperti robot).",
            ],
            'super_admin' => [
                'Super Admin',
                "Sapa sebagai pengendali sistem dengan ramah, tangkas, dan komunikatif.",
            ],
            default => [
                'Pengguna',
                "Sapa dengan ramah, empatik, hangat, dan bersahabat layaknya apoteker senior.",
            ],
        };

        // Obat spesifik yang disinggung user dalam pesan/riwayat terbaru + kandidat alternatif tersedia di DB
        $mentionedObat = $this->findMedicineInMessage($teks, $obat);
        $recentContextText = $riwayat ? mb_strtolower($riwayat->take(-3)->pluck('content')->implode(' ')) : '';
        if ($mentionedObat->isEmpty() && $recentContextText !== '') {
            $mentionedObat = $this->findMedicineInMessage($recentContextText, $obat);
        }

        $mentionedContext = '';
        if ($mentionedObat->isNotEmpty()) {
            $mentionedLines = $mentionedObat->take(8)->map(function ($item) {
                $harga = is_numeric($item['harga_umum']) ? 'Rp ' . number_format($item['harga_umum'], 0, ',', '.') : '-';
                return "{$item['kode']} | {$item['nama_obat']} | {$item['kategori']} | Stok: {$item['stok']} {$item['satuan']} | {$harga}";
            })->implode("\n");
            $mentionedContext = "\n[Obat yang Disinggung Pengguna di DB]\n{$mentionedLines}\n";
        }

        $alternativeContext = '';
        $alternativeCandidates = $this->findAlternativeMedicines($teks . ' ' . $recentContextText, $obat, $mentionedObat);
        $isAskingAlternativeOrDetail = $this->isMedicineDetailQuestion($teks)
            || (bool) preg_match('/\b(alternatif|alternatifnya|pengganti|penggantinya|opsi\s+lain|selain|mirip|setara|sejenis|kalau\s+(?:ga|gak|nggak|tidak)\s+ada|kalo\s+(?:ga|gak|nggak|tidak)\s+ada)\b/iu', $teks);

        if ($alternativeCandidates->isNotEmpty()) {
            $altLines = $alternativeCandidates->map(function ($item) {
                $harga = is_numeric($item['harga_umum']) ? 'Rp ' . number_format($item['harga_umum'], 0, ',', '.') : '-';
                return "{$item['kode']} | {$item['nama_obat']} | {$item['kategori']} | Stok: {$item['stok']} {$item['satuan']} | {$harga}";
            })->implode("\n");
            $alternativeContext = "\n[Kandidat Alternatif / Pengganti Tersedia (Stok > 0) di DB]\n{$altLines}\n";
        } elseif ($isAskingAlternativeOrDetail) {
            // Untuk obat yang belum terpetakan ke daftar sinonim, kirim katalog ringkas seluruh stok tersedia agar AI bisa memilih pengganti yang benar-benar ada di DB
            $compactAvailableCatalog = $obat->where('stok', '>', 0)->map(function ($item) {
                $harga = is_numeric($item['harga_umum']) ? number_format($item['harga_umum'], 0, ',', '.') : '-';
                return "{$item['kode']}|{$item['nama_obat']}|{$item['kategori']}|{$item['stok']} {$item['satuan']}|Rp {$harga}";
            })->implode("\n");
            $alternativeContext = "\n[Daftar Lengkap Obat Tersedia (Stok > 0) di DB untuk Pencarian Alternatif]\n{$compactAvailableCatalog}\n";
        }

        // Sampel stok tersedia + obat menipis untuk konteks nyata
        $availableSample = $obat->where('stok', '>', 0)->take(15)->map(function ($item) {
            $harga = is_numeric($item['harga_umum']) ? 'Rp ' . number_format($item['harga_umum'], 0, ',', '.') : '-';
            return "{$item['kode']} | {$item['nama_obat']} | {$item['kategori']} | Stok: {$item['stok']} {$item['satuan']} | {$harga}";
        })->implode("\n");

        $lowStockSample = $obat->where('stok', '<=', 5)->take(10)->map(function ($item) {
            return "{$item['kode']} | {$item['nama_obat']} | {$item['kategori']} | Stok: {$item['stok']} {$item['satuan']}";
        })->implode("\n");

        // Sertakan data tren penjualan 30 hari terakhir jika user bertanya soal penjualan/terlaris/rekomendasi restock
        $salesSnapshot = '';
        if (preg_match('/(terlaris|laku|penjualan|omzet|tren|rekomendasi\s+restock|analisis\s+stok|perlu\s+pesan|supplier|pbf)/iu', $teks)) {
            $salesSnapshot = $this->buildSalesTrendSnapshot();
        }

        return <<<PROMPT
Kamu adalah **PharmAI**, asisten apoteker senior digital di "Apotek Itu Toko Obat".

### 1. IDENTITAS, PERSONA & TONE
- **Karakter:** Apoteker senior yang cerdas klinis, lincah dalam manajemen inventaris apotek, komunikatif, hangat, dan punya selera humor ringan khas apoteker (tidak kaku seperti robot/mesin). Sesekali selipkan analogi jenaka atau candaan halus seputar obat/kesehatan atau semangat kerja apotek tanpa mengurangi ketepatan data dan informasi medis.
- **Tone & Gaya Bahasa Adaptif:** {$adaptiveStyleInstruction}
- **Konteks Rekan Bicara Saat Ini:**
  * Nama Lengkap: {$fullName}
  * Username: {$username}
  * Peran (Role): {$roleDisplay}
  * Panduan Sapaan Kontekstual: {$roleGreetingGuide}
- Gunakan sapaan yang terasa alami dan mengalir sesuai peran di atas (tidak perlu mengulang-ulang data teknis akun secara kaku). Jangan menyingkat atau memplesetkan nama obat, kode obat, maupun angka dosis!

### 2. KEAHLIAN KLINIS & ETIKA FARMASI
- **Konsultasi Pasien & Obat:** Mampu menjelaskan indikasi obat, mekanisme kerja ringan, aturan minum (sebelum atau sesudah makan), interval waktu, serta efek samping umum (contoh: rasa kantuk pada antihistamin atau obat batuk seperti CTM/OBH).
- **Pertanyaan Fungsi Obat & Alternatif Jika Tidak Ada / Habis:** Jika pengguna menanyakan fungsi suatu obat sekaligus menanyakan alternatifnya bila tidak ada/habis di apotek: (1) Jelaskan fungsi, kandungan zat aktif utama, dan peringatan Obat Keras jika berlaku; (2) Nyatakan dengan jujur apakah obat tersebut terdaftar atau tidak berdasarkan data DB di bawah; (3) Sajikan alternatif obat dengan kegunaan/zat aktif setara yang **Stok > 0** dari data DB di bawah dalam bentuk **TABEL MARKDOWN** (jangan mengarang stok/kode yang tidak ada di data).
- **Peringatan Wajib Obat Keras (Logo K Merah):** Untuk obat golongan Keras (lingkaran merah berhuruf K, seperti antibiotik Cefadroxil/Amoxicillin, obat hipertensi Amlodipine, antidiabetes Metformin, dsb.), WAJIB beri penegasan tegas bahwa obat tersebut **harus menggunakan resep dokter resmi** dan tidak boleh ditebus sembarangan demi keselamatan pasien.
- **Disclaimer Medis:** Selalu sertakan 1 kalimat penutup ringkas yang ramah untuk mengingatkan agar pasien berkonsultasi ke dokter jika gejala berlanjut lebih dari 3 hari atau terasa semakin memberat.

### 3. MANAJEMEN INVENTARIS & ALUR RESTOK APOTEK
- Sistem memiliki alur pengecekan stok, pengajuan tambah/kurang stok (dengan klarifikasi spesifik ukuran ml/mg/pcs dan wajib berstatus **PENDING** menunggu persetujuan **Pemilik** demi kerapian audit stok), serta pemantauan stok menipis.
- Jika pengguna ingin mengajukan perubahan stok lewat chat, arahkan mereka untuk menyebutkan aksi (tambah/kurang), nama & takaran/kemasan spesifik (berapa ml/mg), serta jumlah dan satuannya (pcs/botol/tablet) agar dapat diproses ke antrean persetujuan **Pemilik**.
- Gunakan HANYA data inventaris dan penjualan nyata di bawah ini saat menyebut angka stok/harga/penjualan — jangan mengarang angka stok yang tidak tercantum.

### 4. FORMAT TAMPILAN JAWABAN (PEMBUKA, TABEL MARKDOWN & PENUTUP)
- **Keterangan Pembuka Sebelum Data/Tabel:** Awali setiap jawaban dengan ringkasan yang jelas dan ramah mengenai apa saja obat yang tersedia/ditemukan beserta alternatifnya (jika relevan dengan pertanyaan pengguna) sebelum menyajikan tabel atau rincian teknis.
- **Tabel Markdown:** Setiap kali menampilkan data stok obat, daftar inventaris, perbandingan harga, obat terlaris, atau rekomendasi restock, **WAJIB sajikan dalam bentuk TABEL MARKDOWN** yang ringkas, bersih, dan mudah dibaca (kolom: Kode, Nama Obat, Golongan, Stok, Harga/Status, dll).
- **Keterangan Penutup Cerdas & Jenaka:** Di akhir jawaban (setelah tabel/penjelasan), selalu sertakan 1–2 kalimat simpulan praktis, peringatan Obat Keras bila relevan, saran operasional/klinis, serta tawaran bantuan lanjutan yang hangat dan sedikit jenaka khas apoteker ramah.

### DATA INVENTARIS & OPERASIONAL SAAT INI (REAL-TIME DB):{$mentionedContext}{$alternativeContext}
[Contoh Obat Tersedia]
{$availableSample}

[Contoh Obat Stok Menipis / Habis (<= 5)]
{$lowStockSample}
{$salesSnapshot}
PROMPT;
    }

    private function buildSalesTrendSnapshot(): string
    {
        try {
            $since = now()->subDays(30);
            $topSold = OrderItem::query()
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('obat', 'order_items.obat_id', '=', 'obat.id')
                ->leftJoin('kategori', 'obat.kategori_id', '=', 'kategori.id')
                ->where('orders.created_at', '>=', $since)
                ->whereNotIn('orders.status', ['cancelled', 'dibatalkan'])
                ->groupBy('obat.id', 'obat.kode', 'obat.nama_obat', 'obat.stok', 'obat.satuan', 'obat.harga_umum', 'kategori.nama_kategori')
                ->selectRaw('obat.kode, obat.nama_obat, COALESCE(kategori.nama_kategori, \'Umum\') as kategori, obat.stok, obat.satuan, obat.harga_umum, SUM(order_items.jumlah) as total_terjual')
                ->orderByDesc('total_terjual')
                ->limit(10)
                ->get();

            if ($topSold->isEmpty()) {
                return "\n[Tren Penjualan 30 Hari Terakhir]\nBelum ada transaksi penjualan tercatat dalam 30 hari terakhir.";
            }

            $lines = $topSold->map(function ($row) {
                $harga = is_numeric($row->harga_umum) ? 'Rp ' . number_format($row->harga_umum, 0, ',', '.') : '-';
                return "{$row->kode} | {$row->nama_obat} | {$row->kategori} | Terjual 30h: {$row->total_terjual} {$row->satuan} | Sisa Stok: {$row->stok} | {$harga}";
            })->implode("\n");

            return "\n[Tren Penjualan 30 Hari Terakhir (Top 10)]\n{$lines}";
        } catch (Throwable $e) {
            return '';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DATA INVENTARIS DATABASE REAL-TIME
    |--------------------------------------------------------------------------
    */

    private function getDataObat(): Collection
    {
        return Obat::with('kategori')
            ->where('is_active', true)
            ->where('is_approved', true)
            ->orderBy('nama_obat')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'kode' => $item->kode,
                    'nama_obat' => $item->nama_obat,
                    'kategori' => $item->kategori?->nama_kategori ?? 'Umum',
                    'stok' => max(0, (int) $item->stok),
                    'satuan' => $item->satuan ?: 'Unit',
                    'harga_umum' => $item->harga_umum,
                    'deskripsi' => $item->deskripsi ?: '',
                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | PENGATURAN & PENGAJUAN UPDATE STOK OBAT VIA PHARMAI (BUTUH APPROVE PEMILIK)
    |--------------------------------------------------------------------------
    */

    private function isPendingStockClarification(Collection $riwayat): bool
    {
        $lastAssistant = $riwayat->where('role', 'assistant')->last();
        if (!$lastAssistant) {
            return false;
        }

        return str_contains((string) $lastAssistant->content, self::STOCK_CLARIFICATION_HEADER);
    }

    private function isStockProceduralOrNegationQuery(string $teks): bool
    {
        // Kalimat negasi / larangan agar tidak memicu pengajuan update stok
        if (preg_match('/\b(jangan|jgn|tak\s+usah|tidak\s+usah|gak\s+usah|ga\s+usah|nggak\s+usah|belum\s+perlu|bukan\s+(?:untuk\s+)?(?:tambah|kurang))\b/iu', $teks)) {
            return true;
        }

        // Pertanyaan prosedur ("gimana cara..."), alasan ("kenapa bisa ditolak..."), atau aturan sistem
        return (bool) preg_match('/\b(gimana\s+cara|bagaimana\s+cara|cara\s+(?:tambah|tambahkan|kurang|kurangi|kurangkan|update|ubah|edit|restock|restok|pengajuan|ajukan|mengajukan)|kenapa\b.*\b(?:ditolak|disetujui|pending|gagal|lama)|mengapa\b.*\b(?:ditolak|disetujui|pending|gagal|lama)|apa\s+(?:syarat|alur|prosedur|ketentuan|fungsi|arti)|jelaskan\s+(?:alur|cara|prosedur)|tutorial|panduan)\b/iu', $teks);
    }

    private function isStockUpdateIntent(string $teks): bool
    {
        if (preg_match('/\b(kurang\s+dari|kurang\s+dr|lebih\s+dari|di\s*bawah|dibawah|di\s*atas|diatas)\b/iu', $teks)) {
            return false;
        }

        if ($this->isStockProceduralOrNegationQuery($teks)) {
            return false;
        }

        if (preg_match('/\b(rekomendasi|analisis|analisa|tren|terlaris|laporan|saran\s+restock|perlu\s+restock)\b/iu', $teks)) {
            return false;
        }

        if (preg_match('/\b(penambahan|pengurangan|update|ubah|edit|sesuaikan|penyesuaian|input|catat)\s+(?:data\s+)?stok\b/iu', $teks)) {
            return true;
        }

        if (preg_match('/\bstok\s+(?:masuk|keluar|bertambah|berkurang|ditambah(?:kan)?|dikurang(?:i|kan)?)\b/iu', $teks)) {
            return true;
        }

        if (preg_match('/\b(tambah|tambahkan|tambahin|restock|re-stock|restok|kurang|kurangi|kurangkan|kurangin|potong)\s+(?:data\s+|jumlah\s+)?stok\b/iu', $teks)) {
            return true;
        }

        if (preg_match('/\b(?:stok\b.*?\b(?:tambahkan|tambahin|tambah|kurangi|kurangkan|kurangin|restock|restok))\b/iu', $teks)) {
            return true;
        }

        if (preg_match('/\b(tambahkan|tambahin|kurangi|kurangkan|kurangin|restock|restok)\s+(?:stok\s+|obat\s+)?(?:\d+\s+)?[a-z]/iu', $teks)) {
            return true;
        }

        return false;
    }

    private function handleStockUpdateConversation(
        $user,
        string $teks,
        string $pertanyaan,
        Collection $riwayat,
        Collection $obat,
        string $namaPengguna
    ) {
        if (preg_match('/\b(batal|batalkan|cancel|tidak\s+jadi|gak\s+jadi|ga\s+jadi|gajadi|nggak\s+jadi|stop)\b/iu', $teks)) {
            return $this->sendReply($user, 'Baik, pengajuan update stok dibatalkan. Ada informasi obat atau stok lain yang bisa aku bantu?');
        }

        if (!$user->isAdmin()) {
            if ($user->isPemilik()) {
                return $this->sendReply(
                    $user,
                    "🔒 Pengajuan update stok melalui **PharmAI** hanya dapat dibuat oleh **Admin Apotek** agar proses persetujuan tetap terpisah.\n\n"
                    . "Sebagai **Pemilik**, Anda dapat meninjau, menyetujui, atau menolak seluruh pengajuan perubahan stok di menu **Persetujuan Stok**."
                );
            }

            return $this->sendReply($user, '🔒 Maaf, fitur pengajuan update stok hanya tersedia untuk role **Admin Apotek** dan tetap memerlukan persetujuan **Pemilik**.');
        }

        [$userTurns, $prevPrompt] = $this->collectStockClarificationContext($riwayat, $pertanyaan);
        $parsed = $this->parseStockUpdateDetails($userTurns, $prevPrompt, $obat);

        // 1. Pastikan jenis aksi (penambahan / pengurangan) jelas
        if ($parsed['action'] === null) {
            return $this->sendReply(
                $user,
                self::STOCK_CLARIFICATION_HEADER . "\n\n"
                . "Apakah Anda ingin mengajukan **penambahan (+)** atau **pengurangan (-)** stok?\n"
                . "Mohon sebutkan jenis perubahannya beserta **nama obat spesifik (berapa ml/mg)**, **jumlah**, dan **satuannya (pcs/botol/tablet)**.\n\n"
                . "*Contoh:* `tambah stok Ambroxol 60ml 5 botol` atau `kurangi stok Paracetamol 500mg 10 tablet`."
            );
        }

        $actionLabel = $parsed['action'] === 'reduce' ? 'pengurangan' : 'penambahan';
        $candidates = $parsed['candidates'];

        // 2. Tidak ada obat yang cocok
        if ($candidates->isEmpty()) {
            return $this->sendReply(
                $user,
                self::STOCK_CLARIFICATION_HEADER . "\n\n"
                . "Aku belum menemukan obat aktif yang cocok untuk pengajuan **{$actionLabel} stok** tersebut.\n"
                . "Mohon sebutkan **nama obat**, **varian (berapa ml/mg)**, **merek** atau **kode obat (OBT...)** yang lebih spesifik, atau ketik `batal` untuk membatalkan."
            );
        }

        // 3. Lebih dari 1 kandidat cocok -> minta klarifikasi spesifik (ml/mg/merek/pcs/kode)
        if ($candidates->count() > 1) {
            return $this->sendReply(
                $user,
                $this->buildAmbiguousStockReply($candidates, $actionLabel, $parsed['qty'], $parsed['unit'])
            );
        }

        // 4. Tepat 1 kandidat ditemukan
        $target = $candidates->first();
        $satuanDb = $target['satuan'] ?: 'Unit';
        $satuanDbLower = mb_strtolower($satuanDb);
        $missingParts = [];

        // Cek apakah nama obat punya takaran/kemasan spesifik (ml/mg/%/dll) dan user belum menyebutkan/mengonfirmasinya
        if (
            $this->medicineHasVariantMeasurement($target['nama_obat'])
            && !$this->userSpecifiedVariantOrConfirmed($target, $parsed)
        ) {
            $missingParts[] = "kepastian varian kemasan (**" . $this->extractVariantSpecLabel($target['nama_obat']) . "**)";
        }

        if ($parsed['ml_as_amount'] && $parsed['qty'] === null && !preg_match('/\bml\b/iu', $satuanDbLower)) {
            $missingParts[] = "jumlah dalam **{$satuanDbLower} / pcs** (stok di database dicatat dalam satuan **{$satuanDb}**, bukan per ml)";
        } elseif ($parsed['qty'] === null) {
            $missingParts[] = "jumlah {$actionLabel} dan satuannya (berapa **{$satuanDbLower} / pcs**)";
        } elseif (!$parsed['unit_explicit'] && !$parsed['confirmed']) {
            $missingParts[] = "konfirmasi satuan jumlah (**{$parsed['qty']} {$satuanDbLower}** atau **pcs**?)";
        }

        if (!empty($missingParts)) {
            $detailList = implode(' serta ', $missingParts);
            return $this->sendReply(
                $user,
                self::STOCK_CLARIFICATION_HEADER . "\n\n"
                . "Ditemukan obat berikut untuk **{$actionLabel} stok**:\n"
                . "1. **[{$target['kode']}] {$target['nama_obat']}**\n"
                . "   • Satuan Stok: **{$satuanDb}** | Stok Saat Ini: **{$target['stok']} {$satuanDb}**\n\n"
                . "Agar tidak salah input di database, mohon spesifikasikan lagi {$detailList}.\n"
                . "*Contoh konfirmasi:* `ya, " . ($parsed['qty'] ?? 5) . " {$satuanDbLower}`"
                . ($this->medicineHasVariantMeasurement($target['nama_obat']) ? " atau sebutkan `" . $this->extractVariantSpecLabel($target['nama_obat']) . "`" : '')
                . "."
            );
        }

        $qty = (int) $parsed['qty'];
        if ($qty <= 0) {
            return $this->sendReply(
                $user,
                self::STOCK_CLARIFICATION_HEADER . "\n\n"
                . "Jumlah {$actionLabel} stok harus lebih dari `0`. Mohon sebutkan jumlahnya dalam **{$satuanDbLower} / pcs**."
            );
        }

        // 5. Validasi kecukupan stok untuk pengurangan
        if ($parsed['action'] === 'reduce' && $qty > (int) $target['stok']) {
            return $this->sendReply(
                $user,
                self::STOCK_CLARIFICATION_HEADER . "\n\n"
                . "⚠️ **Stok Tidak Mencukupi**\n"
                . "Pengurangan **{$qty} {$satuanDb}** untuk **[{$target['kode']}] {$target['nama_obat']}** melebihi stok saat ini (**{$target['stok']} {$satuanDb}**).\n\n"
                . "Silakan masukkan jumlah pengurangan maksimal **{$target['stok']} {$satuanDbLower}**, atau ketik `batal`."
            );
        }

        // 6. Buat pengajuan PENDING di stock_additions (TIDAK mengubah obat.stok langsung; wajib approve Pemilik)
        $signedQty = $parsed['action'] === 'reduce' ? -$qty : $qty;
        $unitUser = $parsed['unit'] ?: $satuanDbLower;
        $jenisTeks = $parsed['action'] === 'reduce' ? 'Pengurangan' : 'Penambahan';
        $tanda = $parsed['action'] === 'reduce' ? '-' : '+';

        $catatan = mb_substr(
            "[Via PharmAI - {$namaPengguna}] {$jenisTeks} {$qty} {$unitUser} ({$satuanDb}). Pesan: " . implode(' | ', $userTurns),
            0,
            245
        );

        StockAddition::create([
            'obat_id' => $target['id'],
            'jumlah' => $signedQty,
            'catatan' => $catatan,
            'status' => 'pending',
        ]);

        $estimasiStok = (int) $target['stok'] + $signedQty;
        $unitInfo = mb_strtolower($unitUser) !== $satuanDbLower
            ? "{$qty} {$unitUser} (dicatat sebagai **{$tanda}{$qty} {$satuanDb}**)"
            : "**{$tanda}{$qty} {$satuanDb}**";

        return $this->sendReply(
            $user,
            "✅ **Pengajuan Update Stok Berhasil Dikirim ke Pemilik**\n\n"
            . "- **Obat:** {$target['nama_obat']} (`{$target['kode']}`)\n"
            . "- **Jenis:** {$jenisTeks} Stok\n"
            . "- **Jumlah Diajukan:** {$unitInfo}\n"
            . "- **Stok Saat Ini (Belum Berubah):** {$target['stok']} {$satuanDb}\n"
            . "- **Estimasi Setelah Disetujui:** {$estimasiStok} {$satuanDb}\n"
            . "- **Status:** ⏳ **Menunggu Persetujuan Pemilik (Pending)**\n\n"
            . "📌 *Stok di database belum berubah dan baru akan diperbarui setelah **Pemilik** menyetujuinya di menu **Persetujuan Stok**.*"
        );
    }

    private function collectStockClarificationContext(Collection $riwayat, string $currentMessage): array
    {
        $items = $riwayat->values();
        $collectedTurns = [];
        $prevPrompt = null;

        for ($i = $items->count() - 1; $i >= 0; $i--) {
            $msg = $items[$i];
            $content = (string) $msg->content;

            if ($msg->role === 'assistant') {
                if (str_contains($content, self::STOCK_CLARIFICATION_HEADER)) {
                    if ($prevPrompt === null) {
                        $prevPrompt = $content;
                    }
                    continue;
                }
                break;
            }

            if ($msg->role === 'user') {
                array_unshift($collectedTurns, $content);
            }
        }

        $collectedTurns[] = $currentMessage;

        return [$collectedTurns, $prevPrompt];
    }

    private function parseStockUpdateDetails(array $userTurns, ?string $prevPrompt, Collection $obat): array
    {
        $combined = mb_strtolower(preg_replace('/\s+/u', ' ', implode(' ', $userTurns)));
        $latest = mb_strtolower(preg_replace('/\s+/u', ' ', end($userTurns) ?: ''));

        // Aksi: cek giliran terbaru dulu lalu mundur
        $action = null;
        foreach (array_reverse($userTurns) as $turn) {
            $t = mb_strtolower($turn);
            if (preg_match('/\b(kurang|kurangi|kurangkan|kurangin|pengurangan|keluar|pengeluaran|pakai|terpakai|terjual|rusak|kadaluarsa|expired|potong|turunkan|turunin|minus)\b/iu', $t)) {
                $action = 'reduce';
                break;
            }
            if (preg_match('/\b(tambah|tambahkan|tambahin|penambahan|nambah|restock|re-stock|restok|masuk|pemasukan|naikkan|naikin)\b/iu', $t)) {
                $action = 'add';
                break;
            }
        }

        // Kandidat bernomor dari prompt klarifikasi sebelumnya
        $prevCandidates = $this->extractPromptCandidateMap($prevPrompt);

        // Deteksi pilihan nomor / kode dari giliran terbaru
        $selectedCode = null;
        if (preg_match('/\b([A-Z]{2,6}-?\d{3,})\b/iu', $latest, $m)) {
            $candidateCode = mb_strtoupper($m[1]);
            if ($obat->contains(fn($item) => mb_strtoupper((string) ($item['kode'] ?? '')) === $candidateCode)) {
                $selectedCode = $candidateCode;
            }
        }
        if ($selectedCode === null && !empty($prevCandidates)) {
            if (preg_match('/\b(?:no|nomor|nomer|pilih|pilihan|opsi|ke)\s*\.?\s*-?\s*(\d+)\b/iu', $latest, $m)) {
                $idx = (int) $m[1];
                if (isset($prevCandidates[$idx])) {
                    $selectedCode = $prevCandidates[$idx];
                }
            } elseif (preg_match('/^\s*(\d+)\s*$/u', $latest, $m)) {
                $idx = (int) $m[1];
                if (isset($prevCandidates[$idx])) {
                    $selectedCode = $prevCandidates[$idx];
                }
            }
        }

        // Ekstrak ukuran/dosis (60ml, 100ml, 500mg, 70%) di seluruh percakapan
        preg_match_all('/\b\d+(?:[.,]\d+)?\s*(?:ml|mg|mcg|gram|gr|iu|%)\b/iu', $combined, $sizeMatches);
        $sizeTokens = array_values(array_unique(array_map(
            fn($s) => preg_replace('/\s+/u', '', mb_strtolower($s)),
            $sizeMatches[0] ?? []
        )));

        // Ekstrak jumlah + satuan transaksi (prioritas dari turn terbaru ke terlama)
        $qty = null;
        $unit = null;
        $unitExplicit = false;
        $mlAsAmount = false;

        $countUnitsPattern = 'pcs|pc|buah|biji|botol|btl|tablet|tab|kaplet|kapsul|kap|strip|str|tube|sachet|box|boks|dus|fls|vial|ampul|pot|roll|lembar|unit|botol\/pcs';

        foreach (array_reverse($userTurns) as $idx => $turn) {
            $t = mb_strtolower(preg_replace('/\s+/u', ' ', $turn));

            // Jangan anggap nomor pilihan ("nomor 1", "no 2") sebagai quantity
            $tWithoutSelection = preg_replace('/\b(?:no|nomor|nomer|pilih|pilihan|opsi|ke)\s*\.?\s*-?\s*\d+\b/iu', ' ', $t);

            if (preg_match('/\b(\d+)\s*(' . $countUnitsPattern . ')\b/iu', $tWithoutSelection, $m)) {
                $qty = (int) $m[1];
                $unit = $this->normalizeStockUnit($m[2]);
                $unitExplicit = true;
                break;
            }

            // Jika user hanya menyebutkan satuan di follow-up (misal "botol" / "pcs") setelah sebelumnya menyebut angka
            if ($unit === null && preg_match('/\b(' . $countUnitsPattern . ')\b/iu', $tWithoutSelection, $m)) {
                $unit = $this->normalizeStockUnit($m[1]);
                $unitExplicit = true;
            }

            // Deteksi kasus seperti "kurangi alkohol 50 ml" (user menyebut ml sebagai jumlah, bukan kemasan 100ml)
            if (preg_match('/(?:sebanyak|sejumlah|qty|tambah(?:kan|in)?|kurang(?:i|kan|in)?)\s+(?:stok\s+)?(\d+)\s*ml\b/iu', $tWithoutSelection)) {
                $mlAsAmount = true;
            }

            // Angka tanpa unit setelah kata kerja atau pada follow-up singkat
            if ($qty === null && preg_match('/(?:sebanyak|jumlah|qty|tambah(?:kan|in)?|kurang(?:i|kan|in)?|restock|restok)\s*(?:stok\s*)?(\d+)\b(?!\s*(?:mg|ml|mcg|%|gram|gr|iu)\b)/iu', $tWithoutSelection, $m)) {
                $qty = (int) $m[1];
                continue;
            }

            if ($qty === null && $idx === 0 && preg_match('/(?:^|[,\s])(\d+)\s*$/u', trim($tWithoutSelection), $m)) {
                $candidateNum = (int) $m[1];
                if ($selectedCode === null || !preg_match('/^\s*\d+\s*$/u', trim($t))) {
                    $qty = $candidateNum;
                }
            }
        }

        // Cek konfirmasi eksplisit ("ya", "iya", "benar", "betul", "oke", "ok") setelah klarifikasi
        $confirmed = $prevPrompt !== null && (bool) preg_match('/\b(ya|iya|betul|benar|oke|ok|setuju|sesuai|yg\s+itu|yang\s+itu)\b/iu', $latest);

        // Cocokkan kandidat obat
        if ($selectedCode !== null) {
            $candidates = $obat->filter(fn($item) => mb_strtoupper((string) ($item['kode'] ?? '')) === $selectedCode)->values();
        } else {
            $candidates = $this->findStockUpdateCandidates($userTurns, $sizeTokens, $prevCandidates, $obat);
        }

        return [
            'action' => $action,
            'qty' => $qty,
            'unit' => $unit,
            'unit_explicit' => $unitExplicit,
            'ml_as_amount' => $mlAsAmount,
            'size_tokens' => $sizeTokens,
            'selected_code' => $selectedCode,
            'confirmed' => $confirmed,
            'candidates' => $candidates,
            'combined' => $combined,
        ];
    }

    private function extractPromptCandidateMap(?string $prevPrompt): array
    {
        if ($prevPrompt === null) {
            return [];
        }

        $map = [];
        if (preg_match_all('/^\s*(\d+)\.\s+\*\*\[([^\]]+)\]/imu', $prevPrompt, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $map[(int) $m[1]] = mb_strtoupper(trim($m[2]));
            }
        }

        return $map;
    }

    private function findStockUpdateCandidates(array $userTurns, array $sizeTokens, array $prevCandidates, Collection $obat): Collection
    {
        $combined = mb_strtolower(implode(' ', $userTurns));

        // Jika ada daftar kandidat dari prompt sebelumnya, prioritaskan mencari di dalam daftar tersebut terlebih dahulu
        $pool = $obat;
        if (!empty($prevCandidates)) {
            $codes = array_values($prevCandidates);
            $narrowedPool = $obat->filter(fn($item) => in_array(mb_strtoupper((string) ($item['kode'] ?? '')), $codes, true))->values();
            if ($narrowedPool->isNotEmpty()) {
                $latestClean = $this->extractMedicineSearchTokens(end($userTurns) ?: '');
                if (!empty($latestClean) || !empty($sizeTokens)) {
                    $matchedInNarrow = $this->scoreAndFilterMedicineCandidates($narrowedPool, $latestClean, $sizeTokens);
                    if ($matchedInNarrow->isNotEmpty()) {
                        return $matchedInNarrow;
                    }
                }
            }
        }

        $tokens = $this->extractMedicineSearchTokens($combined);
        if (empty($tokens) && empty($sizeTokens)) {
            return collect();
        }

        return $this->scoreAndFilterMedicineCandidates($pool, $tokens, $sizeTokens);
    }

    private function extractMedicineSearchTokens(string $text): array
    {
        $clean = mb_strtolower($text);
        $clean = preg_replace('/\b(?:no|nomor|nomer|pilih|pilihan|opsi|ke)\s*\.?\s*-?\s*\d+\b/iu', ' ', $clean);
        $clean = preg_replace('/\b\d+\s*(?:pcs|pc|buah|biji|botol|btl|tablet|tab|kaplet|kapsul|kap|strip|str|tube|sachet|box|boks|dus|fls|vial|ampul|pot|roll|lembar|unit)\b/iu', ' ', $clean);
        $clean = preg_replace('/\b\d+(?:[.,]\d+)?\s*(?:ml|mg|mcg|gram|gr|iu|%)\b/iu', ' ', $clean);
        $clean = preg_replace('/[^a-z0-9\s]/iu', ' ', $clean);

        $stopwords = [
            'tolong', 'mohon', 'minta', 'bantu', 'bisa', 'mau', 'ingin', 'akan', 'ajukan', 'pengajuan',
            'tambah', 'tambahkan', 'tambahin', 'penambahan', 'nambah', 'restock', 'restok',
            'kurang', 'kurangi', 'kurangkan', 'kurangin', 'pengurangan', 'potong', 'turunkan', 'turunin',
            'update', 'ubah', 'edit', 'sesuaikan', 'penyesuaian', 'input', 'catat', 'set',
            'stok', 'obat', 'data', 'database', 'apotek', 'di', 'ke', 'dari', 'untuk', 'dengan', 'yang', 'yg',
            'ya', 'iya', 'betul', 'benar', 'oke', 'ok', 'setuju', 'sesuai', 'itu', 'ini', 'ada', 'lagi',
            'sebanyak', 'sejumlah', 'jumlah', 'qty', 'berapa', 'dong', 'nih', 'nya', 'dan', 'atau', 'aja', 'saja',
        ];

        $words = preg_split('/\s+/u', trim($clean), -1, PREG_SPLIT_NO_EMPTY);
        $filtered = [];

        foreach ($words as $w) {
            if (in_array($w, $stopwords, true)) {
                continue;
            }
            if (is_numeric($w) || mb_strlen($w) < 2) {
                continue;
            }
            $filtered[] = $w;
        }

        return array_values(array_unique($filtered));
    }

    private function scoreAndFilterMedicineCandidates(Collection $pool, array $tokens, array $sizeTokens): Collection
    {
        $scored = $pool->map(function ($item) use ($tokens, $sizeTokens) {
            $namaRaw = mb_strtolower((string) $item['nama_obat']);
            $namaCompact = preg_replace('/\s+/u', '', $namaRaw);
            $kodeLower = mb_strtolower((string) ($item['kode'] ?? ''));

            $tokenHits = 0;
            $distinctTokenMatches = 0;
            foreach ($tokens as $tok) {
                if ($tok === $kodeLower) {
                    $tokenHits += 5;
                    $distinctTokenMatches++;
                } elseif (preg_match('/\b' . preg_quote($tok, '/') . '\b/iu', $namaRaw)) {
                    $tokenHits += 2;
                    $distinctTokenMatches++;
                } elseif (str_contains($namaRaw, $tok)) {
                    $tokenHits += 1;
                    $distinctTokenMatches++;
                }
            }

            $sizeHits = 0;
            foreach ($sizeTokens as $sz) {
                if (str_contains($namaCompact, $sz)) {
                    $sizeHits += 2;
                }
            }

            if ($tokenHits === 0 && !empty($tokens)) {
                return null;
            }

            if (!empty($sizeTokens) && $sizeHits === 0) {
                return null;
            }

            // Jika user memberi beberapa kata (misal "ambroxol bernofarm"), utamakan yang cocok semua kata
            return [
                'item' => $item,
                'score' => ($tokenHits * 10) + ($sizeHits * 8),
                'matched_all_tokens' => count($tokens) > 0 && $distinctTokenMatches >= count($tokens),
            ];
        })->filter()->sortByDesc('score')->values();

        if ($scored->isEmpty()) {
            return collect();
        }

        $topScore = $scored->first()['score'];
        $strictMatches = $scored->filter(fn($entry) => $entry['matched_all_tokens'])->values();

        $selected = $strictMatches->isNotEmpty()
            ? $strictMatches
            : $scored->filter(fn($entry) => $entry['score'] >= ($topScore * 0.6))->values();

        return $selected->pluck('item')->values();
    }

    private function normalizeStockUnit(string $raw): string
    {
        $u = mb_strtolower(trim($raw));
        return match ($u) {
            'pc' => 'pcs',
            'btl' => 'botol',
            'tab' => 'tablet',
            'kap' => 'kaplet',
            'str' => 'strip',
            default => $u,
        };
    }

    private function buildAmbiguousStockReply(Collection $candidates, string $actionLabel, ?int $qty, ?string $unit): string
    {
        $maxShow = 8;
        $list = $candidates->take($maxShow)->values();
        $lines = [];

        foreach ($list as $idx => $item) {
            $num = $idx + 1;
            $satuan = $item['satuan'] ?: 'Unit';
            $lines[] = "{$num}. **[{$item['kode']}] {$item['nama_obat']}**\n"
                . "   • Satuan: **{$satuan}** | Stok Saat Ini: **{$item['stok']} {$satuan}**";
        }

        $extraNote = $candidates->count() > $maxShow
            ? "\n*(Menampilkan {$maxShow} dari {$candidates->count()} obat yang cocok — ketik nama/ml/merek lebih spesifik untuk mempersempit.)*\n"
            : '';

        $qtyHint = $qty !== null
            ? ($unit !== null
                ? " (jumlah: **{$qty} {$unit}**)"
                : " dan pastikan satuannya untuk **{$qty}** item (misal: **{$qty} botol** / **{$qty} pcs**)")
            : ' beserta **jumlah & satuannya** (misal: `5 botol` / `10 tablet` / `3 pcs`)';

        $sampleVerb = $actionLabel === 'pengurangan' ? 'kurangi' : 'tambah';

        return self::STOCK_CLARIFICATION_HEADER . "\n\n"
            . "Ditemukan beberapa varian obat yang cocok untuk **{$actionLabel} stok**:\n\n"
            . implode("\n", $lines) . "\n"
            . $extraNote . "\n"
            . "Mohon spesifikasikan nomor atau varian obat yang mana (**berapa ml / mg / merek**){$qtyHint}.\n"
            . "*Contoh:* `nomor 1, {$sampleVerb} 5 " . mb_strtolower($list->first()['satuan'] ?: 'pcs') . "` atau sebutkan `yang 60ml 5 botol`.";
    }

    private function medicineHasVariantMeasurement(string $namaObat): bool
    {
        return (bool) preg_match('/\b\d+(?:[.,]\d+)?\s*(?:ml|mg|mcg|iu|%)\b/iu', $namaObat);
    }

    private function extractVariantSpecLabel(string $namaObat): string
    {
        if (preg_match_all('/\b\d+(?:[.,]\d+)?\s*(?:ml|mg|mcg|iu|%)\b/iu', $namaObat, $m)) {
            return implode(' / ', array_unique($m[0]));
        }

        return 'varian spesifik';
    }

    private function userSpecifiedVariantOrConfirmed(array $target, array $parsed): bool
    {
        if ($parsed['confirmed'] || $parsed['selected_code'] !== null) {
            return true;
        }

        $namaCompact = preg_replace('/\s+/u', '', mb_strtolower((string) $target['nama_obat']));
        foreach ($parsed['size_tokens'] as $sz) {
            if (str_contains($namaCompact, $sz)) {
                return true;
            }
        }

        return false;
    }

    private function getDataKategori(): Collection
    {
        return Kategori::withCount(['obat' => function ($q) {
            $q->where('is_active', true)->where('is_approved', true);
        }])->get()->map(function ($kat) {
            return [
                'nama' => $kat->nama_kategori,
                'total_obat' => $kat->obat_count,
                'deskripsi' => $kat->deskripsi ?: '-',
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | PERCAKAPAN RINGAN & IDENTITAS
    |--------------------------------------------------------------------------
    */

    private function isNameQuestion(string $teks): bool
    {
        return (bool) preg_match('/^(?:siapa\s+nama\s+(?:aku|saya)|nama\s+(?:aku|saya)\s+siapa|namaku\s+siapa|siapa\s+namaku)[?!.\s]*$/u', $teks);
    }

    private function isSlangOrCasualMessage(string $teks, ?Collection $riwayat = null): bool
    {
        $slangRegex = '/\b(gue|gw|gua|lo|lu|elu|bro|sis|bestie|besti|cuy|coy|gan|min|slur|wkwk+|haha+|hehe+|hihi+|kwkw+|anjir|anjay|gokil|mantul|mantap|gercep|sat\s*set|satset|spill|kuy|skuy|gaskeun|gaspol|mager|baper|halu|julid|gabut|santuy|santui|sabi|valid|relate|vibes|healing|overthinking|gws|otw|fomo|red\s*flag|green\s*flag|gg|ez|fix|parah\s*sih|bet|bgt|banget|nih|sih|dong|kek|kayak|gimana|gmn|yg|ga|gak|nggak|gk|gaada|gada|apaan|kepo|jujurly|literally|btw|ygy|cmiiw|best)\b/iu';

        if (preg_match($slangRegex, $teks)) {
            return true;
        }

        if ($riwayat && $riwayat->isNotEmpty()) {
            $recentUserText = $riwayat->where('role', 'user')->take(-2)->pluck('content')->implode(' ');
            if ($recentUserText !== '' && preg_match($slangRegex, $recentUserText)) {
                return true;
            }
        }

        return false;
    }

    private function handleSmallTalk(string $teks, string $namaUser = 'Kak', ?Collection $riwayat = null): ?string
    {
        $isSlang = $this->isSlangOrCasualMessage($teks, $riwayat);

        if (preg_match('/^(halo+|hai+|hi+|hey+|p|yo|uy|pagi|siang|sore|malam|selamat\s+(?:pagi|siang|sore|malam))(?:\s+(?:bro|sis|bestie|besti|cuy|coy|gan|min|kak|dok|pharm|pharmai|nih|dong|ya))*[!. ?]*$/iu', $teks)) {
            if ($isSlang) {
                return "Yoo halo {$namaUser}! 👋 Gue **PharmAI**, apoteker senior digital di **Apotek Itu Toko Obat** yang siap sat-set bantu lo!\n\n"
                    . "Mau *spill* stok obat, tanya aturan pakai biar gak salah dosis, konsultasi keluhan biar cepet *healing*, atau ajukan update stok (tambah/kurang)? Gas langsung ketik aja ya! 💊✨";
            }

            return "Halo {$namaUser}! 😊 Saya **PharmAI**, asisten apoteker senior di **Apotek Itu Toko Obat**.\n\n"
                . "Ada yang bisa saya bantu hari ini—mulai dari cek stok & harga obat, konsultasi aturan pakai/efek samping, rekomendasi keluhan, hingga pengajuan update stok? Silakan sampaikan saja ya! 💊";
        }
        if (preg_match('/(?:siapa\s+nama\s*(?:mu|kamu|lo|lu)|nama\s*(?:mu|kamu|lo|lu)\s+siapa|kamu\s+siapa|lo\s+siapa|siapa\s+(?:kamu|lo|nih))/iu', $teks)) {
            return $isSlang
                ? "Kenalin nih, gue **PharmAI**, apoteker senior digital kepercayaannya **Apotek Itu Toko Obat**! 😎 Siap bantu konsultasi obat yang valid & informatif, cek stok real-time, sampai rekap tren penjualan."
                : 'Halo! 👋 Aku **PharmAI**, asisten apoteker senior digital dari **Apotek Itu Toko Obat** yang siap membantu konsultasi keluhan kesehatan, informasi obat yang akurat, dan pengecekan stok apotek.';
        }
        if (preg_match('/(?:nama\s+apotek|apotek\s+apa\s+ini)/u', $teks)) {
            return 'Ini adalah **APOTEK ITU TOKO OBAT**, dan aku **PharmAI** yang siap membantu kamu! 😊';
        }
        if (preg_match('/^(makasih+|terima\s*kasih|thanks|thank\s*you|thx|tengkyu)(?:\s+(?:ya|banyak|min|bro|bestie|kak|pharmai|dong))*[!. ]*$/iu', $teks)) {
            return $isSlang
                ? "Sama-sama {$namaUser}! 🙌 Semoga keluhannya cepet *healing* dan aktivitasnya makin lancar jaya. Kalau butuh info obat lagi, tinggal colek gue lagi aja!"
                : 'Sama-sama! 😊 Semoga lekas sehat dan selalu dalam kondisi prima. Jangan ragu bertanya lagi bila butuh informasi obat atau stok apotek ya!';
        }
        if (preg_match('/apa\s+kabar|gmn\s+kabar|gimana\s+kabar/iu', $teks)) {
            return $isSlang
                ? "Kabar gue aman jaya, seger terus kayak vitamin C 1000mg! 😎 Lo sendiri gimana nih, {$namaUser}? Sehat-sehat aja kan, atau ada keluhan/stok obat yang mau dicek?"
                : 'Alhamdulillah saya baik dan selalu siap membantu! 😊 Bagaimana kabar Anda hari ini? Ada informasi obat atau stok apotek yang sedang dibutuhkan?';
        }
        return null;
    }

    private function hasEmergencySymptoms(string $teks): bool
    {
        $pola = [
            '/sesak napas berat/u',
            '/tidak bisa bernapas/u',
            '/nyeri dada hebat/u',
            '/tidak sadarkan diri/u',
            '/kejang/u',
            '/muntah darah/u',
            '/perdarahan hebat/u',
        ];
        foreach ($pola as $regex) {
            if (preg_match($regex, $teks)) {
                return true;
            }
        }
        return false;
    }

    private function isEmergencyBurn(string $teks): bool
    {
        if (!preg_match('/(?:luka\s*bakar|terbakar|kena\s*(?:air|minyak)\s*panas)/u', $teks)) {
            return false;
        }

        // Cek persentase luas luka bakar (misal 20%, 15%, 10%)
        if (preg_match('/(\d+)\s*%/u', $teks, $m)) {
            if ((int) $m[1] >= 10) {
                return true;
            }
        }

        // Cek kata kunci keparahan / derajat darurat
        if (preg_match('/(?:derajat\s*(?:3|tiga|2|dua\s*dalam)|wajah|muka|leher|kelamin|selangkangan|seluruh\s*tubuh|setengah\s*badan|luas|parah|melepuh\s*hebat|gosong|hangus|hitam|putih\s*pucat|mati\s*rasa|tersengat\s*listrik\s*tegangan|bahan\s*kimia)/u', $teks)) {
            return true;
        }

        return false;
    }

    private function handleEmergencyBurn($user, string $teks)
    {
        preg_match('/(\d+)\s*%/u', $teks, $m);
        $persen = isset($m[1]) ? "seluas **{$m[1]}%**" : "derajat berat / luas";

        $reply = "🚨 **PERINGATAN GAWAT DARURAT MEDIS: SEGERA KE IGD RUMAH SAKIT!**\n\n"
            . "Luka bakar {$persen} merupakan **kondisi gawat darurat medis kritis** yang memerlukan penanganan rumah sakit segera. Luka bakar seluas ini berisiko tinggi menyebabkan hilangnya cairan tubuh secara masif (**syok hipovolemik**), hipotermia, serta infeksi sistemik berat.\n\n"
            . "⚠️ **Kondisi ini TIDAK BISA diobati mandiri di rumah atau hanya menggunakan salep apotek.**\n\n"
            . "🚑 **Tindakan Darurat yang Harus Dilakukan SEKARANG:**\n"
            . "1️⃣ **Segera bawa korban ke IGD Rumah Sakit terdekat** atau hubungi ambulans / layanan darurat (**119**).\n"
            . "2️⃣ **Dinginkan luka darurat:** Alirkan air bersih suhu ruang (bukan air es) secara perlahan selama 10–15 menit sambil mempersiapkan keberangkatan ke RS.\n"
            . "3️⃣ **Lepaskan benda ketat:** Segera lepaskan jam tangan, cincin, ikat pinggang, atau pakaian di sekitar area luka sebelum membengkak (*jangan dipaksa jika pakaian sudah menempel erat pada luka bakar*).\n"
            . "4️⃣ **Lindungi luka:** Tutup area luka bakar secara longgar dengan kain bersih yang dibasahi air matang/bersih atau plastik wrap bersih agar tidak terkena debu saat perjalanan.\n"
            . "5️⃣ **Jaga kehangatan tubuh:** Selimuti bagian tubuh yang tidak terbakar untuk mencegah hipotermia (kedinginan akibat hilangnya fungsi pelindung kulit).\n\n"
            . "🚫 **PANTANGAN KERAS (JANGAN DILAKUKAN):**\n"
            . "- ❌ **JANGAN** oleskan pasta gigi (odol), mentega, kecap, minyak, atau tepung.\n"
            . "- ❌ **JANGAN** gunakan es batu atau air es (dapat menyebabkan radang dingin / kerusakan jaringan lebih dalam).\n"
            . "- ❌ **JANGAN** memecahkan gelembung lepuhan kulit yang muncul.\n"
            . "- ❌ **JANGAN** menunda perjalanan ke rumah sakit demi mencari salep.\n\n"
            . "*Mohon prioritaskan keselamatan dan segera menuju IGD rumah sakit terdekat.*";

        return $this->sendReply($user, $reply);
    }

    private function isHealthOrMedicalQuery(string $teks, ?Collection $riwayat = null): bool
    {
        if ($this->isStockOrPriceQuestion($teks) || $this->isMedicineAvailabilityInquiry($teks)) {
            return false;
        }

        // 1. Cek langsung teks sekarang
        $isMatchTeks = (bool) preg_match(
            '/(?:luka|sobek|bakar|tusuk|memar|lebam|lecet|berdarah|darah|sayat|tergores|knalpot|paku|jarum|kaca|mimisan|p3k|'
            . 'terbentur|jatuh|bengkak|perban|jahitan|pusing|sakit|nyeri|pegal|linu|ngilu|kram|kemeng|kaku|berputar|vertigo|'
            . 'migrain|denyut|kesemutan|mati\s*rasa|panas|meriang|dingin|menggigil|mual|muntah|lambung|maag|gerd|ulu\s*hati|'
            . 'perut|kembung|begah|diare|mencret|bab|sembelit|konstipasi|wasir|ambien|batuk|pilek|flu|bersin|tenggorokan|'
            . 'sesak|asma|hidung|serak|radang|amandel|sariawan|kepala|mata|gigi|gusi|telinga|pinggang|punggung|dada|kaki|'
            . 'tangan|lutut|kulit|gatal|alergi|biduran|ruam|sembuh|dosis|minum|sembuhkan|mengobati|penyakit|gejala|'
            . 'infeksi|demam|antibiotik|analgesik|antivirus|antijamur|antihistamin|suplemen|vitamin|efek\s*samping|interaksi|kontraindikasi|pertolongan\s*pertama|recovery|sembuhnya|berapa\s*lama|berapa\s*hari|kapan\s*sembuh)/u',
            $teks
        );

        if ($isMatchTeks) {
            return true;
        }

        // 2. Jika user bertanya lanjutan di tengah konsultasi luka/keluhan sebelumnya
        $isFollowUpPhrasing = (bool) preg_match('/\b(?:berapa\s*lama|kapan|caranya|bagaimana|gimana|kompres|boleh|pantangan|recovery|sembuhnya|bahaya)\b/iu', $teks);
        if ($isFollowUpPhrasing && $riwayat && $riwayat->isNotEmpty()) {
            $pesanTerakhir = mb_strtolower($riwayat->take(-4)->pluck('content')->implode(' '));
            if (preg_match('/(?:luka|memar|lebam|sobek|bakar|nyeri|sakit|demam|pusing|mual|batuk|flu|bengkak)/ui', $pesanTerakhir)) {
                return true;
            }
        }

        return false;
    }

    private function getGeneralHealthGuidanceText(string $teks, string $namaUser, Collection $obat, ?Collection $riwayat = null): string
    {
        $riwayatTeks = $riwayat ? mb_strtolower($riwayat->take(-4)->pluck('content')->implode(' ')) : '';

        // Kasus: Pertanyaan lanjutan tentang waktu penyembuhan / recovery
        if (preg_match('/(?:recovery|sembuh|berapa\s*lama|berapa\s*hari|kapan\s*sembuh|durasi)/u', $teks)) {
            if (str_contains($riwayatTeks, 'memar') || str_contains($riwayatTeks, 'lebam') || str_contains($riwayatTeks, 'terbentur') || str_contains($teks, 'lebam') || str_contains($teks, 'memar')) {
                return "⏱️ **Estimasi Waktu Pemulihan (Recovery) Luka Memar / Lebam:**\n\n"
                    . "Secara umum, luka memar atau lebam membutuhkan waktu **1 hingga 2 minggu (7–14 hari)** untuk pulih sepenuhnya, tergantung seberapa keras benturan yang dialami.\n\n"
                    . "🌈 **Tahapan Perubahan Warna Lebam Saat Sembuh:**\n"
                    . "1️⃣ **Hari ke-1–2 (Merah ke Biru/Ungu Gelap):** Terjadi akibat penumpukan sel darah dari pembuluh kapiler yang pecah di bawah kulit.\n"
                    . "2️⃣ **Hari ke-5–10 (Hijau ke Kuning/Kecokelatan):** Tubuh mulai memecah hemoglobin dan menyerap kembali sisa-sisa endapan darah beku.\n"
                    . "3️⃣ **Hari ke-10–14 (Kuning Pudar ke Warna Kulit Normal):** Bekas lebam memudar dan jaringan kulit pulih seperti semula.\n\n"
                    . "💡 **Tips Mempercepat Pemulihan:**\n"
                    . "- **24–48 jam pertama:** Kompres dingin/es (15–20 menit) untuk menekan pembengkakan.\n"
                    . "- **Setelah 48 jam:** Beralih ke **kompres hangat** (2–3 kali sehari) guna melancarkan sirkulasi darah agar lebam cepat diserap tubuh.\n"
                    . "- Oleskan gel heparin seperti **Thrombophob Gel** secara tipis dan teratur pada area lebam (pastikan tidak ada luka terbuka).\n\n"
                    . "🚨 *Perhatian Medis: Segera periksa ke dokter jika memar justru membesar drastis, terasa sangat nyeri berdenyut hebat, timbul mati rasa/kebas, atau tidak membaik sama sekali setelah 2 minggu.*";
            }

            if (str_contains($riwayatTeks, 'bakar') || str_contains($teks, 'bakar')) {
                return "⏱️ **Estimasi Waktu Pemulihan Luka Bakar Ringan (Derajat 1 / Minor):**\n\n"
                    . "Umumnya luka bakar ringan membutuhkan waktu **7 hingga 14 hari** untuk regenerasi lapisan kulit baru.\n\n"
                    . "💡 **Tips Perawatan:**\n"
                    . "- Oleskan salep luka bakar seperti **Bioplacenton** secara rutin 2–3 kali sehari.\n"
                    . "- Jaga area luka tetap bersih dan kering.\n"
                    . "- ❌ **JANGAN** pernah memecahkan gelembung lepuhan kulit karena lapisan kulit tersebut melindungi dari infeksi bakteri.";
            }

            if (str_contains($riwayatTeks, 'sobek') || str_contains($riwayatTeks, 'sayat') || str_contains($teks, 'sobek')) {
                return "⏱️ **Estimasi Waktu Pemulihan Luka Sobek / Sayat:**\n\n"
                    . "Untuk luka sobek atau sayat ringan yang bersih, kulit biasanya merapat dan menutup dalam waktu **5 hingga 10 hari**.\n\n"
                    . "💡 **Tips Perawatan:**\n"
                    . "- Bersihkan dengan cairan antiseptik (**Rivanol**).\n"
                    . "- Oleskan salep antibakteri (**Gentamicin** atau **Bioplacenton**) dan tutup dengan plester steril anti-air.\n"
                    . "- Ganti plester setiap hari atau jika basah/kotor.";
            }
        }

        return "Aduh, semoga lekas membaik ya, {$namaUser}. ❤️\n\n"
            . "Sebagai **PharmAI** dari **APOTEK ITU TOKO OBAT**, aku memahami keluhan kesehatan atau pertanyaan yang sedang kamu rasakan.\n\n"
            . "📋 **Langkah Perawatan Mandiri & Pertolongan Pertama (P3K):**\n"
            . "1️⃣ **Istirahat yang Cukup:** Beri tubuh waktu beristirahat di ruangan yang sejuk, tenang, dan bersirkulasi udara baik.\n"
            . "2️⃣ **Cukupi Kebutuhan Cairan:** Minum air putih hangat sedikit demi sedikit secara teratur untuk menjaga hidrasi tubuh.\n"
            . "3️⃣ **Perawatan Berdasarkan Gejala:**\n"
            . "   - **Bila Demam / Pusing / Nyeri:** Dapat dibantu pereda nyeri umum seperti **Paracetamol** sesuai dosis kemasan.\n"
            . "   - **Bila Mual / Perut Perih / Kembung:** Hindari makanan pedas, asam, atau berlemak; bila perlu konsumsi obat lambung seperti **Antasida**.\n"
            . "   - **Bila Terjadi Luka / Cedera:** Segera bersihkan dengan air mengalir atau antiseptik (**Rivanol / Betadine**), dan lindungi dengan perban/kasa steril.\n\n"
            . "🚨 **Tanda Bahaya (Kapan Harus Segera ke Faskes / IGD):**\n"
            . "- Nyeri hebat yang tidak tertahankan atau mendadak memburuk drastis.\n"
            . "- Sesak napas berat, demam tinggi di atas 39°C, atau muntah terus-menerus hingga lemas.\n"
            . "- Pendarahan aktif yang tidak kunjung berhenti setelah 10 menit penekanan.\n\n"
            . "⚠️ *Informasi ini merupakan panduan pertolongan awal. Bila keluhan berlanjut lebih dari 2–3 hari atau bertambah parah, sangat disarankan segera berkonsultasi langsung dengan dokter atau apoteker.*";
    }

    protected function sendReply($user, string $reply)
    {
        Log::info('PharmAI Reply Sent', [
            'user_id' => $user->id,
            'session_id' => $this->activeSessionId,
            'reply_preview' => mb_substr(preg_replace('/\s+/u', ' ', $reply), 0, 160),
        ]);

        try {
            AiMessage::create([
                'user_id' => $user->id,
                'session_id' => $this->activeSessionId,
                'role' => 'assistant',
                'content' => $reply,
            ]);
        } catch (Throwable $e) {
            // Abaikan jika DB offline
        }

        return response()->json([
            'reply' => $reply,
            'session_id' => $this->activeSessionId,
        ]);
    }

    public function history(Request $request)
    {
        $messages = AiMessage::where('user_id', $request->user()->id)
            ->orderBy('id')
            ->get(['id', 'session_id', 'role', 'content', 'created_at']);

        return response()->json(['messages' => $messages]);
    }

    public function clearHistory(Request $request)
    {
        try {
            AiMessage::where('user_id', $request->user()->id)->delete();
            return response()->json(['success' => true, 'message' => 'Riwayat chat berhasil dibersihkan.']);
        } catch (Throwable $e) {
            return response()->json(['error' => 'Gagal menghapus riwayat: ' . $e->getMessage()], 500);
        }
    }

    public function deleteSession(Request $request, string $sessionId)
    {
        try {
            AiMessage::where('user_id', $request->user()->id)
                ->where('session_id', $sessionId)
                ->delete();

            return response()->json(['success' => true, 'message' => 'Riwayat percakapan berhasil dihapus.']);
        } catch (Throwable $e) {
            return response()->json(['error' => 'Gagal menghapus sesi: ' . $e->getMessage()], 500);
        }
    }
}
