<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Collection;

// Simulasi katalog obat riil di apotek
$mockObat = collect([
    [
        'id' => 1,
        'nama_obat' => 'Paracetamol 500mg',
        'kategori' => 'Analgesik & Antipiretik',
        'stok' => 50,
        'satuan' => 'Tablet',
        'harga_umum' => 5000,
        'deskripsi' => 'Pereda sakit kepala, pusing, dan penurun demam.'
    ],
    [
        'id' => 2,
        'nama_obat' => 'Antasida Doen',
        'kategori' => 'Antasida',
        'stok' => 30,
        'satuan' => 'Tablet',
        'harga_umum' => 4000,
        'deskripsi' => 'Menetralkan asam lambung dan meredakan rasa mual / perih lambung.'
    ],
    [
        'id' => 3,
        'nama_obat' => 'Domperidone 10mg',
        'kategori' => 'Antiemetik',
        'stok' => 25,
        'satuan' => 'Tablet',
        'harga_umum' => 8000,
        'deskripsi' => 'Meredakan mual dan muntah.'
    ],
    [
        'id' => 4,
        'nama_obat' => 'Betahistine Mesylate 6mg',
        'kategori' => 'Antivertigo',
        'stok' => 20,
        'satuan' => 'Tablet',
        'harga_umum' => 15000,
        'deskripsi' => 'Meredakan pusing berputar dan vertigo.'
    ],
    [
        'id' => 5,
        'nama_obat' => 'Bioplacenton Gel 15g',
        'kategori' => 'Salep Luka',
        'stok' => 15,
        'satuan' => 'Tube',
        'harga_umum' => 33000,
        'deskripsi' => 'Salep untuk mempercepat penyembuhan luka bakar dan luka terbuka.'
    ],
    [
        'id' => 6,
        'nama_obat' => 'Rivanol 100ml',
        'kategori' => 'Antiseptik',
        'stok' => 10,
        'satuan' => 'Botol',
        'harga_umum' => 8000,
        'deskripsi' => 'Cairan antiseptik untuk membersihkan dan mengompres luka.'
    ],
    [
        'id' => 7,
        'nama_obat' => 'Betadine Solution 15ml',
        'kategori' => 'Antiseptik',
        'stok' => 12,
        'satuan' => 'Botol',
        'harga_umum' => 12000,
        'deskripsi' => 'Antiseptik povidone iodine untuk mencegah infeksi luka.'
    ],
    [
        'id' => 8,
        'nama_obat' => 'Hansaplast Plester Kain',
        'kategori' => 'Plester',
        'stok' => 100,
        'satuan' => 'Lembar',
        'harga_umum' => 1000,
        'deskripsi' => 'Penutup luka steril untuk melindungi dari kotoran.'
    ],
]);

class AIControllerTestable extends App\Http\Controllers\AIController
{
    public function testEmergencyBurn(string $teks)
    {
        $reflection = new ReflectionClass(parent::class);
        $method = $reflection->getMethod('isEmergencyBurn');
        $method->setAccessible(true);
        return $method->invoke($this, $teks);
    }

    public function testMatchSymptoms(string $teks, Collection $katalogObat)
    {
        $reflection = new ReflectionClass(parent::class);
        $method = $reflection->getMethod('matchMedicinesForSymptoms');
        $method->setAccessible(true);
        return $method->invoke($this, $teks, $katalogObat);
    }

    public function testHandleSymptomRecommendation(array $data)
    {
        $reflection = new ReflectionClass(parent::class);
        $method = $reflection->getMethod('handleSymptomRecommendation');
        $method->setAccessible(true);
        $dummyUser = (object)['id' => 1, 'name' => 'Sulthan'];
        return $method->invoke($this, $dummyUser, $data);
    }

    public function testHealthOrMedicalQuery(string $teks)
    {
        $reflection = new ReflectionClass(parent::class);
        $method = $reflection->getMethod('isHealthOrMedicalQuery');
        $method->setAccessible(true);
        return $method->invoke($this, $teks);
    }

    public function testGeneralHealthGuidance(string $teks, Collection $obat)
    {
        $reflection = new ReflectionClass(parent::class);
        $method = $reflection->getMethod('getGeneralHealthGuidanceText');
        $method->setAccessible(true);
        return $method->invoke($this, $teks, 'Sulthan', $obat);
    }
}

$tester = new AIControllerTestable();

$queries = [
    'Image 4: Luka bakar 20%' => 'jika saya mengalami luka bakar 20%, apa yang harus dilakukan untuk mengobatinya',
    'Image 1: Pusing dan mual' => 'saat ini aku lagi sakit pusing dan mual-mual,kira-kira obat apa yang cocok ya',
    'Image 1: Berputar' => 'berputar',
    'Image 3: Luka sobek (mengalama)' => 'jika saya mengalama luka sobek,apa yang harus dilakukan',
    'Image 3: Luka sobek lengkap' => 'jika saya mengalami luka sobek,apa yang harus dilakukan untuk mengobatinya',
    'Kasus Baru: Luka tusuk paku' => 'telapak kaki tertusuk paku berkarat di jalanan, obatnya apa ya?',
    'Kasus Baru: Luka memar terbentur' => 'lutut terbentur lantai sampai memar lebam bengkak dan sakit',
    'Kasus Baru: Mimisan hidung' => 'hidung saya tiba-tiba keluar darah mimisan, tolong caranya',
    'Pertanyaan Medis Umum' => 'kenapa leher saya kaku dan nyeri saat menoleh?',
];

echo "=== HASIL VERIFIKASI LOGIKA MEDIS AI ===\n\n";

foreach ($queries as $label => $q) {
    echo "========================================================\n";
    echo "KASUS: {$label}\n";
    echo "INPUT: \"{$q}\"\n";
    echo "--------------------------------------------------------\n";

    // 1. Cek Emergency Burn
    if ($tester->testEmergencyBurn($q)) {
        echo "🚨 TERDETEKSI: KEADAAN GAWAT DARURAT MEDIS (LUKA BAKAR LUAS >=10%)\n";
        echo "TINDAKAN: Dirujuk langsung ke IGD Rumah Sakit / Layanan 119.\n\n";
        continue;
    }

    // 2. Cek Symptom Match
    $match = $tester->testMatchSymptoms($q, $mockObat);
    if (!empty($match['keluhan'])) {
        echo "✅ TERDETEKSI GEJALA KLINIS: " . implode(', ', $match['keluhan']) . "\n";
        $response = $tester->testHandleSymptomRecommendation($match);
        $data = json_decode($response->getContent(), true);
        echo "RESPON SISTEM:\n" . $data['reply'] . "\n\n";
        continue;
    }

    // 3. Cek General Health Query Fallback
    if ($tester->testHealthOrMedicalQuery($q)) {
        echo "🩺 TERDETEKSI PERTANYAAN MEDIS / KESEHATAN UMUM\n";
        $reply = $tester->testGeneralHealthGuidance($q, $mockObat);
        echo "RESPON SISTEM (Panduan Medis Terstruktur):\n" . $reply . "\n\n";
        continue;
    }

    echo "ℹ️ Bukan pertanyaan medis (percakapan kasir apotek).\n\n";
}
