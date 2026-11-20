<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Kategori;
use App\Models\Obat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@apotek.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'phone' => '081234567890',
            'address' => 'Jl. Admin No. 1',
        ]);

        // Create Admin
        User::create([
            'name' => 'Admin Apotek',
            'email' => 'admin@apotek.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'phone' => '081234567891',
            'address' => 'Jl. Admin No. 2',
        ]);

        // Create Pemilik
        User::create([
            'name' => 'Dr. Rizal',
            'email' => 'pemilik@apotek.com',
            'password' => bcrypt('password'),
            'role' => 'pemilik',
            'phone' => '081234567892',
            'address' => 'Jl. Merdeka No. 10, Jakarta',
        ]);

        // Create Categories
        $categories = [
            ['nama_kategori' => 'Obat Bebas', 'slug' => 'obat-bebas', 'deskripsi' => 'Obat yang dapat dibeli tanpa resep dokter'],
            ['nama_kategori' => 'Obat Keras', 'slug' => 'obat-keras', 'deskripsi' => 'Obat yang memerlukan resep dokter'],
            ['nama_kategori' => 'Vitamin & Suplemen', 'slug' => 'vitamin-suplemen', 'deskripsi' => 'Vitamin dan suplemen kesehatan'],
            ['nama_kategori' => 'Alat Kesehatan', 'slug' => 'alat-kesehatan', 'deskripsi' => 'Alat-alat kesehatan dan medis'],
            ['nama_kategori' => 'Obat Herbal', 'slug' => 'obat-herbal', 'deskripsi' => 'Obat dari bahan alami dan herbal'],
        ];

        foreach ($categories as $cat) {
            Kategori::create($cat);
        }

        // Create Sample Medicines
        $obatList = [
            ['kode' => 'OB-0001', 'nama_obat' => 'Paracetamol 500mg', 'kategori_id' => 1, 'stok' => 250, 'satuan' => 'Tablet', 'harga_umum' => 5000, 'harga_barang' => 2500, 'harga_jual_bpjs' => 3500, 'deskripsi' => 'Obat pereda nyeri dan penurun demam. Aman untuk dewasa dan anak-anak.'],
            ['kode' => 'OB-0002', 'nama_obat' => 'Amoxicillin 500mg', 'kategori_id' => 2, 'stok' => 180, 'satuan' => 'Kapsul', 'harga_umum' => 12000, 'harga_barang' => 7000, 'harga_jual_bpjs' => 8500, 'deskripsi' => 'Antibiotik spektrum luas untuk infeksi bakteri.'],
            ['kode' => 'OB-0003', 'nama_obat' => 'Vitamin C 1000mg', 'kategori_id' => 3, 'stok' => 300, 'satuan' => 'Tablet', 'harga_umum' => 15000, 'harga_barang' => 9000, 'harga_jual_bpjs' => 11000, 'deskripsi' => 'Suplemen vitamin C untuk meningkatkan daya tahan tubuh.'],
            ['kode' => 'OB-0004', 'nama_obat' => 'Omeprazole 20mg', 'kategori_id' => 2, 'stok' => 120, 'satuan' => 'Kapsul', 'harga_umum' => 18000, 'harga_barang' => 10000, 'harga_jual_bpjs' => 13000, 'deskripsi' => 'Obat untuk mengurangi produksi asam lambung.'],
            ['kode' => 'OB-0005', 'nama_obat' => 'Antangin JRG', 'kategori_id' => 5, 'stok' => 200, 'satuan' => 'Sachet', 'harga_umum' => 3000, 'harga_barang' => 1500, 'harga_jual_bpjs' => 2000, 'deskripsi' => 'Jamu herbal untuk mengatasi masuk angin dan perut kembung.'],
            ['kode' => 'OB-0006', 'nama_obat' => 'Masker Medis 3 Ply', 'kategori_id' => 4, 'stok' => 500, 'satuan' => 'Box', 'harga_umum' => 25000, 'harga_barang' => 15000, 'harga_jual_bpjs' => 20000, 'deskripsi' => 'Masker medis 3 lapis untuk perlindungan kesehatan.'],
            ['kode' => 'OB-0007', 'nama_obat' => 'Cetirizine 10mg', 'kategori_id' => 1, 'stok' => 150, 'satuan' => 'Tablet', 'harga_umum' => 8000, 'harga_barang' => 4000, 'harga_jual_bpjs' => 5500, 'deskripsi' => 'Antihistamin untuk meredakan gejala alergi.'],
            ['kode' => 'OB-0008', 'nama_obat' => 'Multivitamin Dewasa', 'kategori_id' => 3, 'stok' => 100, 'satuan' => 'Botol', 'harga_umum' => 45000, 'harga_barang' => 28000, 'harga_jual_bpjs' => 35000, 'deskripsi' => 'Multivitamin lengkap untuk menjaga kesehatan tubuh.'],
            ['kode' => 'OB-0009', 'nama_obat' => 'Ibuprofen 400mg', 'kategori_id' => 1, 'stok' => 220, 'satuan' => 'Tablet', 'harga_umum' => 7000, 'harga_barang' => 3500, 'harga_jual_bpjs' => 5000, 'deskripsi' => 'Obat anti-inflamasi non-steroid untuk nyeri dan radang.'],
            ['kode' => 'OB-0010', 'nama_obat' => 'Tensimeter Digital', 'kategori_id' => 4, 'stok' => 30, 'satuan' => 'Unit', 'harga_umum' => 350000, 'harga_barang' => 220000, 'harga_jual_bpjs' => 280000, 'deskripsi' => 'Alat pengukur tekanan darah digital yang akurat.'],
            ['kode' => 'OB-0011', 'nama_obat' => 'Temulawak Extract', 'kategori_id' => 5, 'stok' => 175, 'satuan' => 'Kapsul', 'harga_umum' => 22000, 'harga_barang' => 13000, 'harga_jual_bpjs' => 17000, 'deskripsi' => 'Suplemen herbal untuk menjaga kesehatan hati.'],
            ['kode' => 'OB-0012', 'nama_obat' => 'Metformin 500mg', 'kategori_id' => 2, 'stok' => 90, 'satuan' => 'Tablet', 'harga_umum' => 10000, 'harga_barang' => 5500, 'harga_jual_bpjs' => 7000, 'deskripsi' => 'Obat untuk mengendalikan kadar gula darah pada diabetes tipe 2.'],
        ];

        foreach ($obatList as $obat) {
            Obat::create($obat);
        }
    }
}
