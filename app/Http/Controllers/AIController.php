<?php

namespace App\Http\Controllers;

use App\Models\AiMessage;
use App\Models\Obat;
use App\Models\Kategori;
use App\Models\StockAddition;
use App\Models\AturanRekomendasiObat;
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

        // 4. Prioritas Ambang Batas & Rentang Stok (Misal: "kurang dari 1", "300 lebih", "antara 150 sampai 300")
        $thresholdData = $this->parseStockThreshold($teks);
        if ($thresholdData !== null) {
            return $this->handleStockThresholdQuery($user, $thresholdData, $obat);
        }

        // 4b. Prioritas Update Stok Obat (Penambahan & Pengurangan via Persetujuan Pemilik)
        $isStockFollowUp = $this->isPendingStockClarification($riwayat)
            && !$this->isOutOfStockListRequest($teks)
            && !$this->isAvailableListRequest($teks)
            && !$this->isCategoryListRequest($teks);

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
        // Jika user mengeluhkan gejala / luka / pertolongan pertama (misal: "luka sobek", "luka lebam", "pusing dan mual", "apa yang harus dilakukan")
        $hasilGejala = $this->matchMedicinesForSymptoms($teks, $obat);
        $isSpecificStockOrDosage = $this->isMedicineDosageOrUsageQuestion($teks) || $this->isStockOrPriceQuestion($teks);

        if (!empty($hasilGejala['keluhan']) && !$isSpecificStockOrDosage) {
            return $this->handleSymptomRecommendation($user, $hasilGejala);
        }

        // 9. Prioritas Pencarian Obat Spesifik (Nama Obat, Stok, Dosis, Khasiat)
        $obatCocok = $this->findMedicineInMessage($teks, $obat);
        if ($obatCocok->isNotEmpty()) {
            Log::info('PharmAI Medicine Found', [
                'query' => $pertanyaan,
                'matched_count' => $obatCocok->count(),
                'first_match' => $obatCocok->first()['nama_obat'] ?? null,
                'stok' => $obatCocok->first()['stok'] ?? null,
            ]);
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

        // 10. Prioritas Obat Berdasarkan Kategori
        $kategoriCocok = $this->findCategoryInMessage($teks, $kategori);
        if ($kategoriCocok !== null) {
            return $this->handleCategoryMedicines($user, $kategoriCocok, $obat);
        }

        // 10b. Cek Pertanyaan Ketersediaan Nama Obat yang Tidak Ada di Database (misal: "apa ada paramex?")
        if (empty($hasilGejala['keluhan']) && $this->isMedicineAvailabilityInquiry($teks)) {
            return $this->handleMissingMedicineAvailability($user, $teks, $obat);
        }

        // Jika ada keluhan yang tersisa tapi ada nama obat yang spesifik ditanyakan bersama keluhan
        if (!empty($hasilGejala['keluhan'])) {
            return $this->handleSymptomRecommendation($user, $hasilGejala);
        }

        // 11. Prioritas Percakapan Ringan (Small Talk)
        $smallTalk = $this->handleSmallTalk($teks);
        if ($smallTalk !== null) {
            return $this->sendReply($user, $smallTalk);
        }

        // 12. Layanan Konsultasi Medis & Percakapan AI (Gemini 3.5 Flash / DeepSeek / Ollama)
        try {
            $reply = $this->callAIProvider($teks, $riwayat, $obat, $kategori, $namaPengguna);
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
        elseif (preg_match('/luka|sobek|sayat|berdarah|darah|tergores|lecet|p3k|pertolongan\s*pertama/u', $teks)) {
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
            })->values();

            if ($tersedia->isNotEmpty()) {
                $adaObat = true;
                $reply .= "🔹 **Untuk {$labelKeluhan}:**\n";
                foreach ($tersedia->take(2) as $idx => $item) {
                    $harga = is_numeric($item['harga_umum']) ? 'Rp ' . number_format($item['harga_umum'], 0, ',', '.') : 'Rp -';
                    $deskripsi = $this->getMedicineDescription($item['nama_obat'], $item['deskripsi']);
                    $reply .= "   " . ($idx + 1) . ". **{$item['nama_obat']}** [{$item['kategori']}]\n"
                        . "      - Khasiat: {$deskripsi}\n"
                        . "      - Status: Tersedia ({$item['stok']} {$item['satuan']}) | Harga: {$harga}\n";
                }
                $reply .= "\n";
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
                . "⚠️ *Informasi ini merupakan referensi awal inventaris apotek. Bila keluhan berlanjut lebih dari 2-3 hari atau memburuk, segera konsultasikan langsung dengan apoteker atau dokter.*";
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
        if (str_contains($nama, 'cetirizine') || str_contains($nama, 'loratadine') || str_contains($nama, 'alerzin')) {
            return 'Antihistamin untuk meredakan alergi, bersin-bersin, dan gatal kulit.';
        }
        if (str_contains($nama, 'ambroxol')) {
            return 'Pengencer dahak untuk meredakan batuk berdahak.';
        }
        if (str_contains($nama, 'demacolin') || str_contains($nama, 'alpara') || str_contains($nama, 'coparcetin') || str_contains($nama, 'hufagrip')) {
            return 'Kombinasi lengkap meredakan flu, demam, sakit kepala, bersin, dan batuk.';
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

        $teksNormal = str_replace(
            ['satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'nol'],
            ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '0'],
            $teks
        );

        // Rentang: antara 150 sampai 300 / 150 - 300
        if (preg_match('/(?:antara|di\s*antara|rentang)?\s*(\d+)\s*(?:sampai|hingga|s\/d|-)\s*(\d+)/u', $teksNormal, $m)) {
            $min = min((int) $m[1], (int) $m[2]);
            $max = max((int) $m[1], (int) $m[2]);
            return [
                'operator' => 'between',
                'threshold' => [$min, $max],
                'label' => "antara {$min} sampai {$max}",
            ];
        }

        // Postfix: 300 lebih / 300 ke atas / 300+
        if (preg_match('/(\d+)\s*(?:lebih|ke\s*atas|\+)/u', $teksNormal, $m)) {
            $val = (int) $m[1];
            return [
                'operator' => '>=',
                'threshold' => $val,
                'label' => "{$val} lebih ({$val} ke atas)",
            ];
        }

        // Kurang dari sama dengan / <=
        if (preg_match('/(?:kurang\s+dari\s+sama\s+dengan|<=|maksimal|paling\s+banyak)\s*(\d+)/u', $teksNormal, $m)) {
            $val = (int) $m[1];
            return [
                'operator' => '<=',
                'threshold' => $val,
                'label' => "kurang dari atau sama dengan {$val}",
            ];
        }

        // Kurang dari / di bawah / <
        if (preg_match('/(?:kurang\s+dari|di\s+bawah|lebih\s+kecil\s+dari|<)\s*(\d+)/u', $teksNormal, $m)) {
            $val = (int) $m[1];
            return [
                'operator' => '<',
                'threshold' => $val,
                'label' => "kurang dari {$val}",
            ];
        }

        // Lebih dari / di atas / >
        if (preg_match('/(?:lebih\s+dari|di\s+atas|lebih\s+besar\s+dari|>)\s*(\d+)/u', $teksNormal, $m)) {
            $val = (int) $m[1];
            return [
                'operator' => '>',
                'threshold' => $val,
                'label' => "lebih dari {$val}",
            ];
        }

        return null;
    }

    private function handleStockThresholdQuery($user, array $thresholdData, Collection $obat)
    {
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
                'between' => $stok >= $val[0] && $stok <= $val[1],
                'menipis' => $stok > 0 && $stok <= 10,
                default => false,
            };
        })->values();

        $total = $hasil->count();
        $ketTambahan = ($op === '<' && $val === 1) ? ' (stok 0 / habis)' : '';

        if ($hasil->isEmpty()) {
            return $this->sendReply($user, "Saat ini **tidak ada obat** yang jumlah stoknya {$label}{$ketTambahan} di apotek kami. 😊");
        }

        $reply = "Saat ini terdapat total **{$total} obat** yang jumlah stoknya **{$label}**{$ketTambahan}:\n\n";
        $limitTampil = 15;

        foreach ($hasil->take($limitTampil) as $idx => $item) {
            $status = $item['stok'] > 0 ? "Tersedia" : "Habis";
            $harga = is_numeric($item['harga_umum']) ? 'Rp ' . number_format($item['harga_umum'], 0, ',', '.') : 'Rp -';
            $reply .= ($idx + 1) . ". **{$item['nama_obat']}** [{$item['kategori']}] | Stok: {$item['stok']} {$item['satuan']} ({$status}) | Harga: {$harga}\n";
        }

        if ($total > $limitTampil) {
            $sisa = $total - $limitTampil;
            $reply .= "\n*(Menampilkan {$limitTampil} dari total {$total} obat. Masih ada {$sisa} obat lainnya).*";
        }

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
            return $this->sendReply($user, 'Saat ini **tidak ada obat yang stoknya 0 / habis**. Seluruh obat di apotek tercatat tersedia. 😊');
        }

        $reply = "Saat ini ada **{$total} obat** yang tercatat stoknya habis (0):\n\n";
        foreach ($habis->take(15) as $idx => $item) {
            $reply .= ($idx + 1) . ". **{$item['nama_obat']}** [{$item['kategori']}] - Stok: 0 {$item['satuan']} (Habis)\n";
        }

        if ($total > 15) {
            $reply .= "\n*(Menampilkan 15 dari total {$total} obat habis).*";
        }

        return $this->sendReply($user, $reply);
    }

    private function isAvailableListRequest(string $teks): bool
    {
        return (bool) preg_match('/('
            . 'obat.*(tersedia|ready|ada)'
            . '|(daftar|tampilkan|sebutkan|list).*obat'
            . '|obat\s+apa\s+saja|ada\s+obat\s+apa'
            . ')/u', $teks) && !$this->isOutOfStockListRequest($teks);
    }

    private function handleAvailableList($user, Collection $obat)
    {
        $tersedia = $obat->where('stok', '>', 0)->values();
        $total = $tersedia->count();

        $reply = "Saat ini ada **{$total} obat** yang tersedia di apotek:\n\n";
        foreach ($tersedia->take(15) as $idx => $item) {
            $harga = is_numeric($item['harga_umum']) ? 'Rp ' . number_format($item['harga_umum'], 0, ',', '.') : 'Rp -';
            $reply .= ($idx + 1) . ". **{$item['nama_obat']}** [{$item['kategori']}] | Stok: {$item['stok']} {$item['satuan']} | {$harga}\n";
        }

        if ($total > 15) {
            $reply .= "\n*(Menampilkan 15 dari total {$total} obat tersedia).*";
        }

        return $this->sendReply($user, $reply);
    }

    private function isTotalMedicineCountRequest(string $teks): bool
    {
        return (bool) preg_match('/(ada\s+)?berapa\s+(total|jumlah|banyak|semua)\s*obat/u', $teks)
            && !preg_match('/(kurang|lebih|di\s+bawah|habis|abis|kosong|tersedia|\d+)/u', $teks);
    }

    private function handleTotalMedicineCount($user, Collection $obat)
    {
        $total = $obat->count();
        $tersedia = $obat->where('stok', '>', 0)->count();
        $habis = $obat->where('stok', '<=', 0)->count();

        return $this->sendReply(
            $user,
            "Total obat terdaftar di apotek saat ini adalah **{$total} obat**:\n"
            . "- **Tersedia:** {$tersedia} obat\n"
            . "- **Habis:** {$habis} obat"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PENCARIAN OBAT SPESIFIK & ATURAN PAKAI
    |--------------------------------------------------------------------------
    */

    private function findMedicineInMessage(string $teks, Collection $obat): Collection
    {
        // 1. Cek nama lengkap persis
        $cocokLengkap = $obat->filter(function ($item) use ($teks) {
            $nama = mb_strtolower(trim($item['nama_obat']));
            return $nama !== '' && mb_strlen($nama) >= 4 && str_contains($teks, $nama);
        });

        if ($cocokLengkap->isNotEmpty()) {
            return $cocokLengkap->values();
        }

        // Daftar kata umum, atribut warna, bentuk sediaan, atau kata keluhan
        // yang TIDAK boleh mencocokkan obat secara mandiri (mencegah false positive)
        $abaikan = [
            'obat', 'medis', 'digital', 'extract', 'ekstrak', 'dewasa', 'anak', 'bayi', 'balita', 'plus', 'forte',
            'biru', 'merah', 'kuning', 'hijau', 'putih', 'hitam', 'coklat', 'abu', 'ungu', 'oranye', 'orange',
            'bedak', 'salep', 'krim', 'sirup', 'tablet', 'kapsul', 'tetes', 'cair', 'cairan', 'larutan', 'injeksi',
            'injek', 'spray', 'serbuk', 'bubuk', 'botol', 'strip', 'sachet', 'tube', 'roll', 'kain', 'kasa', 'perban',
            'plester', 'kapas', 'swab', 'alkohol', 'minyak',
            'rasa', 'jeruk', 'madu', 'lemon', 'mint', 'strawberry', 'biasa', 'ringan', 'berat', 'tinggi',
            'luka', 'sakit', 'nyeri', 'panas', 'darah', 'kulit', 'badan', 'tangan', 'kaki', 'mata', 'gigi', 'gusi',
            'nellco', 'cap', 'lang', 'kencana', 'kimia', 'farma', 'kalbe', 'sanbe', 'tempo', 'jaya'
        ];

        // 2. Cek nama inti obat tanpa dosis
        return $obat->filter(function ($item) use ($teks, $abaikan) {
            $nama = mb_strtolower($item['nama_obat']);
            $namaInti = preg_replace('/\b\d+(?:[.,]\d+)?\s*(?:mg|g|ml|l|ply|tablet|kapsul|sachet|botol|box|unit)\b/iu', '', $nama);
            $namaInti = trim(preg_replace('/\s+/u', ' ', $namaInti));

            // Jika seluruh nama inti terkandung dalam teks (misal: "bedak salicyl", "bioplacenton", "asam mefenamat")
            if (mb_strlen($namaInti) >= 4 && !in_array($namaInti, $abaikan, true) && str_contains($teks, $namaInti)) {
                return true;
            }

            // Cek frasa 2 kata berurutan dari nama obat (misal: "salicyl biru", "kayu putih", "kasa steril")
            $kataKata = array_values(array_filter(preg_split('/\s+/u', $namaInti)));
            $jumlahKata = count($kataKata);

            for ($i = 0; $i < $jumlahKata - 1; $i++) {
                $frasaDuaKata = $kataKata[$i] . ' ' . $kataKata[$i + 1];
                if (mb_strlen($frasaDuaKata) >= 6 && !in_array($frasaDuaKata, $abaikan, true) && str_contains($teks, $frasaDuaKata)) {
                    return true;
                }
            }

            // Jika hanya 1 kata yang cocok: kata tersebut harus NAMA MEREK/ZAT AKTIF UNIK (panjang >= 5 dan tidak diabaikan)
            foreach ($kataKata as $k) {
                $k = trim($k);
                if (mb_strlen($k) >= 5 && !in_array($k, $abaikan, true) && preg_match('/\b' . preg_quote($k, '/') . '\b/iu', $teks)) {
                    return true;
                }
            }

            return false;
        })->values();
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

        $reply = "📋 **Petunjuk Pemakaian: {$nama}** [{$kategori}]\n\n"
            . "Keterangan: {$item['deskripsi']}\n\n"
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

    private function handleMedicineStock($user, Collection $obatCocok)
    {
        $reply = $obatCocok->map(function ($item) {
            $status = $item['stok'] > 0 ? 'Tersedia' : 'Habis (Kosong)';
            $harga = is_numeric($item['harga_umum']) ? 'Rp ' . number_format($item['harga_umum'], 0, ',', '.') : 'Rp -';

            return "💊 **{$item['nama_obat']}**\n"
                . "- Kategori: {$item['kategori']}\n"
                . "- Status: {$status}\n"
                . "- Stok: {$item['stok']} {$item['satuan']}\n"
                . "- Harga: {$harga}";
        })->implode("\n\n");

        return $this->sendReply($user, $reply);
    }

    private function handleMedicineDetail($user, Collection $obatCocok)
    {
        $item = $obatCocok->first();
        $status = $item['stok'] > 0 ? "Tersedia ({$item['stok']} {$item['satuan']})" : 'Habis';
        $harga = is_numeric($item['harga_umum']) ? 'Rp ' . number_format($item['harga_umum'], 0, ',', '.') : 'Rp -';

        $reply = "💊 **{$item['nama_obat']}** [{$item['kategori']}]\n\n"
            . "- **Khasiat/Deskripsi:** {$item['deskripsi']}\n"
            . "- **Status Stok:** {$status}\n"
            . "- **Harga:** {$harga}";

        return $this->sendReply($user, $reply);
    }

    private function handleMedicineOverview($user, Collection $obatCocok)
    {
        return $this->handleMedicineDetail($user, $obatCocok);
    }

    private function isStockOrPriceQuestion(string $teks): bool
    {
        if ($this->isMedicineDosageOrUsageQuestion($teks)) {
            return false;
        }
        return (bool) preg_match('/(stok|harga|tersedia|ready|habis|kosong|harganya|berapa\s+(harga|stok|jumlahnya|biaya)|masih\s+ada|(?:apa|apakah)\s+ada|ada\s+nggak|ada\s+gak|ada\s+ga|punya|jual|beli)/u', $teks);
    }

    private function isMedicineAvailabilityInquiry(string $teks): bool
    {
        if ($this->isMedicineDosageOrUsageQuestion($teks) || $this->isMedicineDetailQuestion($teks)) {
            return false;
        }

        return (bool) preg_match('/\b(?:apa(?:kah)?\s+ada|masih\s+ada|ada\s+(?:stok\s+)?obat|ada\s+[a-z0-9\-]{3,}(?:\s+(?:ga|gak|nggak|tidak|kah))?\s*[?!.]*$|stok\s+[a-z0-9\-]{3,}|jual\s+[a-z0-9\-]{3,}|tersedia\s+[a-z0-9\-]{3,})\b/iu', $teks);
    }

    private function handleMissingMedicineAvailability($user, string $teks, Collection $obat)
    {
        $clean = preg_replace('/\b(apakah|apa|masih|ada|tersedia|ready|punya|jual|beli|stok|obat|produk|merek|merk|yang|yg|di\s+apotek|disini|di\s+sini|ya|dong|kak|min|bang|pak|bu|ga|gak|nggak|tidak|kah)\b/iu', ' ', $teks);
        $clean = trim(preg_replace('/[^a-z0-9\s\-]/iu', ' ', $clean));
        $clean = preg_replace('/\s+/u', ' ', $clean);
        $namaDicari = $clean !== '' ? ucwords($clean) : 'obat yang ditanyakan';

        // Cek apakah user menanyakan merek umum yang punya padanan zat aktif di database
        $keyword = mb_strtolower($clean);
        $alternatifTokens = match (true) {
            str_contains($keyword, 'paramex'),
            str_contains($keyword, 'panadol'),
            str_contains($keyword, 'bodrex'),
            str_contains($keyword, 'sanmol'),
            str_contains($keyword, 'biogesic') => ['paracetamol', 'ibuprofen', 'asam mefenamat'],
            str_contains($keyword, 'promag'),
            str_contains($keyword, 'mylanta'),
            str_contains($keyword, 'polysilane') => ['antasida', 'omeprazole', 'ranitidine'],
            str_contains($keyword, 'obh'),
            str_contains($keyword, 'woods'),
            str_contains($keyword, 'komix'),
            str_contains($keyword, 'vicks'),
            str_contains($keyword, 'bisolvon') => ['ambroxol', 'bromhexine', 'acetylcisteine'],
            str_contains($keyword, 'betadine') => ['povidone', 'rivanol', 'alkohol'],
            str_contains($keyword, 'incidal'),
            str_contains($keyword, 'ctm') => ['cetirizine', 'loratadine'],
            str_contains($keyword, 'diapet'),
            str_contains($keyword, 'diatab'),
            str_contains($keyword, 'entrostop') => ['attapulgite', 'loperamide', 'oralit'],
            default => [],
        };

        $alternatif = collect();
        if (!empty($alternatifTokens)) {
            $alternatif = $obat->filter(function ($item) use ($alternatifTokens) {
                $nama = mb_strtolower($item['nama_obat']);
                foreach ($alternatifTokens as $tok) {
                    if (str_contains($nama, $tok) && $item['stok'] > 0) {
                        return true;
                    }
                }
                return false;
            })->take(4)->values();
        }

        $reply = "Mohon maaf, produk **{$namaDicari}** saat ini **tidak tersedia / belum terdaftar** di inventaris **APOTEK ITU TOKO OBAT**.";

        if ($alternatif->isNotEmpty()) {
            $reply .= "\n\n💊 **Alternatif Obat Tersedia dengan Kandungan/Fungsi Serupa:**\n";
            foreach ($alternatif as $idx => $item) {
                $harga = is_numeric($item['harga_umum']) ? 'Rp ' . number_format($item['harga_umum'], 0, ',', '.') : 'Rp -';
                $reply .= ($idx + 1) . ". **{$item['nama_obat']}** (`{$item['kode']}`) — Stok: **{$item['stok']} {$item['satuan']}** | {$harga}\n";
            }
            $reply .= "\nSilakan beri tahu jika ingin menanyakan aturan pakai atau detail salah satu alternatif di atas.";
        } else {
            $reply .= "\n\n💡 Anda bisa menanyakan nama zat aktifnya (misal: *Paracetamol*, *Ambroxol*, *Antasida*) atau ketik **daftar obat tersedia** untuk melihat stok yang ada saat ini.";
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
        $reply = "Berikut kategori / jenis obat di apotek kami:\n\n";
        foreach ($kategori as $idx => $kat) {
            $reply .= ($idx + 1) . ". **{$kat['nama']}** ({$kat['total_obat']} produk): {$kat['deskripsi']}\n";
        }
        $reply .= "\n💡 *Ketik nama kategori (misal: 'obat bebas' atau 'obat herbal') untuk melihat produk di dalamnya.*";
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
            return $this->sendReply($user, "Saat ini belum ada produk dalam kategori **{$nama}** di apotek kami.");
        }

        $reply = "Daftar obat dalam kategori **{$nama}**:\n\n";
        foreach ($produk->take(15) as $idx => $item) {
            $status = $item['stok'] > 0 ? "Tersedia ({$item['stok']} {$item['satuan']})" : "Habis";
            $harga = is_numeric($item['harga_umum']) ? 'Rp ' . number_format($item['harga_umum'], 0, ',', '.') : 'Rp -';
            $reply .= ($idx + 1) . ". **{$item['nama_obat']}** | {$status} | {$harga}\n";
        }

        return $this->sendReply($user, $reply);
    }

    /*
    |--------------------------------------------------------------------------
    | INTEGRASI AI: DEEPSEEK (UTAMA/PAY-AS-YOU-GO), GEMINI & OLLAMA (LOKAL)
    |--------------------------------------------------------------------------
    */

    private function callAIProvider(string $teks, Collection $riwayat, Collection $obat, Collection $kategori, string $namaUser): string
    {
        $provider = config('services.ai.provider', env('AI_PROVIDER', 'deepseek'));

        // 1. PRIORITAS UTAMA: DEEPSEEK (Pay As You Go - Cerdas, Luwes & Respon Variatif)
        $deepseekKey = config('services.ai.deepseek.key', env('DEEPSEEK_API_KEY'));
        if (($provider === 'deepseek' || !empty($deepseekKey)) && !empty($deepseekKey)) {
            try {
                $model = config('services.ai.deepseek.model', env('DEEPSEEK_MODEL', 'deepseek-chat'));
                $url = config('services.ai.deepseek.url', env('DEEPSEEK_URL', 'https://api.deepseek.com/chat/completions'));

                // Suntikkan sampel stok obat nyata dari database agar DeepSeek tidak halusinasi
                $sampleObat = $obat->where('stok', '>', 0)->take(20)->map(function ($item) {
                    return "{$item['nama_obat']} [{$item['kategori']}, Stok: {$item['stok']} {$item['satuan']}]";
                })->implode(', ');

                $systemPrompt = "Kamu adalah Asisten Virtual Apotek yang ramah, sopan, dan empatik. "
                    . "Pengguna bernama {$namaUser}. "
                    . "Jawab pertanyaan seputar kesehatan, keluhan sakit, atau obat secara alami, luwes, dan solutif dalam Bahasa Indonesia. "
                    . "Jika merekomendasikan obat yang tersedia di apotek kami, prioritaskan produk dari daftar inventaris riil berikut: [{$sampleObat}]. "
                    . "Jika ada kondisi darurat (misal sesak napas berat, nyeri dada, luka bakar luas), selalu instruksikan untuk segera ke IGD atau dokter.";

                $messages = [
                    ['role' => 'system', 'content' => $systemPrompt],
                ];

                foreach ($riwayat->take(4) as $msg) {
                    $messages[] = [
                        'role' => $msg->role === 'assistant' ? 'assistant' : 'user',
                        'content' => $msg->content,
                    ];
                }
                $messages[] = ['role' => 'user', 'content' => $teks];

                $response = Http::withToken($deepseekKey)
                    ->timeout(12)
                    ->post($url, [
                        'model' => $model,
                        'messages' => $messages,
                        'temperature' => 0.7,
                        'max_tokens' => 800,
                    ]);

                if ($response->successful()) {
                    $reply = $response->json('choices.0.message.content');
                    if (is_string($reply) && trim($reply) !== '') {
                        return trim($reply);
                    }
                }
            } catch (Throwable $e) {
                Log::warning('DeepSeek API Error: ' . $e->getMessage());
            }
        }

        // 2. GOOGLE GEMINI (Multi-Model Flash Fast Fallback)
        if ($provider === 'gemini' && !empty(env('GEMINI_API_KEY'))) {
            $rawApiKeys = env('GEMINI_API_KEY');
            $apiKeys = array_values(array_filter(array_map('trim', explode(',', $rawApiKeys))));
            $modelList = array_values(array_filter(array_map('trim', explode(',', config('services.ai.gemini.model', env('GEMINI_MODEL', 'gemini-3.5-flash-lite,gemini-3-flash-preview,gemini-3.6-flash,gemini-3.5-flash'))))));
            if (empty($modelList)) {
                $modelList = ['gemini-3.5-flash-lite', 'gemini-3-flash-preview', 'gemini-3.6-flash', 'gemini-3.5-flash'];
            }

            // Sertakan konteks obat agar Gemini merekomendasikan obat yang nyata ada di apotek
            $sampleObat = $obat->where('stok', '>', 0)->take(15)->map(function ($item) {
                return "{$item['nama_obat']} [Stok: {$item['stok']} {$item['satuan']}]";
            })->implode(', ');

            $systemInstruction = "Kamu adalah PharmAI, Asisten Virtual Pintar dari APOTEK ITU TOKO OBAT dan Konsultan Medis Farmasi Klinis yang profesional, ramah, dan sangat empatik. Pengguna bernama {$namaUser}.\n\n"
                . "DAFTAR SEBAGIAN OBAT RIIL DI APOTEK ITU TOKO OBAT: [{$sampleObat}].\n\n"
                . "PANDUAN MEDIS & KLINIS:\n"
                . "1. Tanggapi setiap keluhan fisik, rasa sakit, luka (luka bakar, sobek, lebam, tusuk, lecet, mimisan), dan pertanyaan lanjutan (seperti waktu recovery/pemulihan, cara kompres, aturan pakai) dengan empati mendalam dan pemahaman medis yang akurat.\n"
                . "2. PAHAMI KONTEKS PERCAKAPAN SEBELUMNYA! Jika pengguna menanyakan 'kira-kira berapa lama waktu recoverynya?', kaitkan secara cerdas dengan topik luka/kondisi yang baru saja dibahas sebelumnya.\n"
                . "3. JANGAN PERNAH memberikan sapaan kasir apotek yang kaku ('Hai! Ada info obat yang bisa dibantu?') jika pengguna sedang berkonsultasi atau bertanya seputar keluhan kesehatan.\n"
                . "4. Berikan edukasi medis terstruktur: langkah perawatan mandiri yang aman, durasi penyembuhan yang wajar, hal yang tidak boleh dilakukan, serta tanda bahaya kapan harus ke dokter.\n"
                . "5. Format jawaban rapi dengan poin/bullet dalam Bahasa Indonesia.";

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
                                'temperature' => 0.7,
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
                    ['role' => 'system', 'content' => "Kamu adalah PharmAI, Konsultan Medis & Farmasi dari APOTEK ITU TOKO OBAT yang ramah dan solutif. Pengguna bernama {$namaUser}. Berikan saran medis P3K yang tepat dan aman."],
                    ['role' => 'user', 'content' => $teks]
                ],
                'stream' => false,
                'options' => ['temperature' => 0.5],
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

        return "Hai {$namaUser}! 👋 Saya **PharmAI** dari **APOTEK ITU TOKO OBAT**. Ada informasi obat, aturan pakai, atau ketersediaan stok yang bisa aku bantu hari ini? 😊";
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

    private function isStockUpdateIntent(string $teks): bool
    {
        if (preg_match('/\b(kurang\s+dari|kurang\s+dr|lebih\s+dari|di\s*bawah|dibawah|di\s*atas|diatas)\b/iu', $teks)) {
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

    private function handleSmallTalk(string $teks): ?string
    {
        if (preg_match('/^(halo|hai|hi|hey|pagi|selamat pagi|selamat siang|selamat sore|selamat malam)[!. ]*$/u', $teks)) {
            return 'Hai! 😊 Saya **PharmAI** dari **APOTEK ITU TOKO OBAT**. Ada yang bisa aku bantu seputar informasi obat, keluhan kesehatan, atau stok hari ini?';
        }
        if (preg_match('/(?:siapa\s+nama\s*(?:mu|kamu)|nama\s*(?:mu|kamu)\s+siapa|kamu\s+siapa|siapa\s+kamu)/u', $teks)) {
            return 'Halo! 👋 Aku **PharmAI**, asisten virtual pintar dari **APOTEK ITU TOKO OBAT** yang siap membantu konsultasi keluhan kesehatan, informasi obat, dan pengecekan stok apotek.';
        }
        if (preg_match('/(?:nama\s+apotek|apotek\s+apa\s+ini)/u', $teks)) {
            return 'Ini adalah **APOTEK ITU TOKO OBAT**, dan aku **PharmAI** yang siap membantu kamu! 😊';
        }
        if (preg_match('/^(makasih|terima kasih|thanks|thank you)[!. ]*$/u', $teks)) {
            return 'Sama-sama! 😊 Semoga lekas sehat dan harimu menyenangkan.';
        }
        if (preg_match('/apa kabar/u', $teks)) {
            return 'Aku baik, terima kasih! Kamu sendiri bagaimana kabarnya hari ini? 😊';
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
