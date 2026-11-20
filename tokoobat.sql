-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 08 Jul 2026 pada 05.50
-- Versi server: 8.0.30
-- Versi PHP: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tokoobat`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `carts`
--

CREATE TABLE `carts` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `obat_id` bigint UNSIGNED NOT NULL,
  `jumlah` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` bigint UNSIGNED NOT NULL,
  `nama_kategori` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `slug`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'Obat Bebas', 'obat-bebas', 'Obat yang dapat dibeli tanpa resep dokter', '2026-05-18 19:51:15', '2026-05-18 19:51:15'),
(2, 'Obat Keras', 'obat-keras', 'Obat yang memerlukan resep dokter', '2026-05-18 19:51:15', '2026-05-18 19:51:15'),
(3, 'Vitamin & Suplemen', 'vitamin-suplemen', 'Vitamin dan suplemen kesehatan', '2026-05-18 19:51:15', '2026-05-18 19:51:15'),
(4, 'Alat Kesehatan', 'alat-kesehatan', 'Alat-alat kesehatan dan medis', '2026-05-18 19:51:15', '2026-05-18 19:51:15'),
(5, 'Obat Herbal', 'obat-herbal', 'Obat dari bahan alami dan herbal', '2026-05-18 19:51:15', '2026-05-18 19:51:15'),
(6, 'Lainnya', 'lainnya', 'Dibuat otomatis via import Excel', '2026-05-21 13:28:36', '2026-05-21 13:28:36'),
(7, 'Obat Bebas Terbatas', 'obat-bebas-terbatas', 'Dibuat otomatis via import Excel', '2026-05-21 13:28:36', '2026-05-21 13:28:36'),
(8, 'Paten Antibiotik', 'paten-antibiotik', 'Dibuat otomatis via import Excel', '2026-05-21 13:28:36', '2026-05-21 13:28:36'),
(9, 'Jamu', 'jamu', 'Dibuat otomatis via import Excel', '2026-05-21 13:28:36', '2026-05-21 13:28:36'),
(10, 'Labor', 'labor', 'Dibuat otomatis via import Excel', '2026-05-21 13:28:36', '2026-05-21 13:28:36'),
(11, 'Obat Hebal Terstandar', 'obat-hebal-terstandar', 'Dibuat otomatis via import Excel', '2026-05-21 13:28:36', '2026-05-21 13:28:36'),
(12, 'Kosmetika', 'kosmetika', 'Dibuat otomatis via import Excel', '2026-05-21 13:28:36', '2026-05-21 13:28:36'),
(13, 'Generik', 'generik', 'Dibuat otomatis via import Excel', '2026-05-21 13:28:37', '2026-05-21 13:28:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2024_01_01_000001_create_users_table', 1),
(2, '2024_01_01_000002_create_kategori_table', 1),
(3, '2024_01_01_000003_create_obat_table', 1),
(4, '2024_01_01_000004_create_orders_table', 1),
(5, '2024_01_01_000005_create_order_items_table', 1),
(6, '2024_01_01_000006_create_carts_table', 1),
(7, '2024_05_19_000000_add_is_approved_to_obat_table', 2),
(8, '2026_05_20_000000_create_stock_additions_table', 3);

-- --------------------------------------------------------

--
-- Struktur dari tabel `obat`
--

CREATE TABLE `obat` (
  `id` bigint UNSIGNED NOT NULL,
  `kode` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_obat` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_id` bigint UNSIGNED NOT NULL,
  `stok` smallint NOT NULL DEFAULT '0',
  `satuan` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `harga_umum` int NOT NULL DEFAULT '0',
  `harga_barang` decimal(15,2) NOT NULL DEFAULT '0.00',
  `harga_jual_bpjs` int NOT NULL DEFAULT '0',
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `gambar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `obat`
--

INSERT INTO `obat` (`id`, `kode`, `nama_obat`, `kategori_id`, `stok`, `satuan`, `harga_umum`, `harga_barang`, `harga_jual_bpjs`, `deskripsi`, `gambar`, `is_active`, `is_approved`, `created_at`, `updated_at`) VALUES
(1, 'OB-0001', 'Paracetamol 500mg', 1, 250, 'Tablet', 5000, 2500.00, 3500, 'Obat pereda nyeri dan penurun demam. Aman untuk dewasa dan anak-anak.', NULL, 1, 1, '2026-05-18 19:51:15', '2026-05-23 08:01:34'),
(2, 'OB-0002', 'Amoxicillin 500mg', 2, 296, 'Kapsul', 12000, 7000.00, 8500, 'Antibiotik spektrum luas untuk infeksi bakteri.', NULL, 1, 1, '2026-05-18 19:51:15', '2026-06-29 14:55:48'),
(3, 'OB-0003', 'Vitamin C 1000mg', 3, 300, 'Tablet', 15000, 9000.00, 11000, 'Suplemen vitamin C untuk meningkatkan daya tahan tubuh.', NULL, 1, 1, '2026-05-18 19:51:15', '2026-05-23 08:01:34'),
(4, 'OB-0004', 'Omeprazole 20mg', 2, 120, 'Kapsul', 18000, 10000.00, 13000, 'Obat untuk mengurangi produksi asam lambung.', NULL, 1, 1, '2026-05-18 19:51:15', '2026-05-23 08:01:34'),
(5, 'OB-0005', 'Antangin JRG', 5, 199, 'Sachet', 3000, 1500.00, 2000, 'Jamu herbal untuk mengatasi masuk angin dan perut kembung.', NULL, 1, 1, '2026-05-18 19:51:15', '2026-05-19 05:47:00'),
(6, 'OB-0006', 'Masker Medis 3 Ply', 4, 500, 'Box', 25000, 15000.00, 20000, 'Masker medis 3 lapis untuk perlindungan kesehatan.', NULL, 1, 1, '2026-05-18 19:51:15', '2026-05-23 08:01:34'),
(7, 'OB-0007', 'Cetirizine 10mg', 1, 150, 'Tablet', 8000, 4000.00, 5500, 'Antihistamin untuk meredakan gejala alergi.', NULL, 1, 1, '2026-05-18 19:51:15', '2026-05-18 21:44:26'),
(8, 'OB-0008', 'Multivitamin Dewasa', 3, 100, 'Botol', 45000, 28000.00, 35000, 'Multivitamin lengkap untuk menjaga kesehatan tubuh.', NULL, 1, 1, '2026-05-18 19:51:15', '2026-05-23 08:01:34'),
(9, 'OB-0009', 'Ibuprofen 400mg', 1, 220, 'Tablet', 7000, 3500.00, 5000, 'Obat anti-inflamasi non-steroid untuk nyeri dan radang.', NULL, 1, 1, '2026-05-18 19:51:15', '2026-05-23 08:01:34'),
(10, 'OB-0010', 'Tensimeter Digital', 4, 30, 'Unit', 350000, 220000.00, 280000, 'Alat pengukur tekanan darah digital yang akurat.', NULL, 1, 1, '2026-05-18 19:51:15', '2026-05-23 08:01:34'),
(11, 'OB-0011', 'Temulawak Extract', 5, 175, 'Kapsul', 22000, 13000.00, 17000, 'Suplemen herbal untuk menjaga kesehatan hati.', NULL, 1, 1, '2026-05-18 19:51:15', '2026-05-23 08:01:34'),
(12, 'OB-0012', 'Metformin 500mg', 2, 90, 'Tablet', 10000, 5500.00, 7000, 'Obat untuk mengendalikan kadar gula darah pada diabetes tipe 2.', NULL, 1, 1, '2026-05-18 19:51:15', '2026-05-23 08:01:34'),
(13, 'OBT0013', 'TRANSOFIX / Double spike 7cm onemed', 6, 4, 'pcs', 5000, 1.00, 5000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(14, 'OBT0014', 'ACETYLCYSTEINE 200MG Yarindo Farmatama', 2, 270, 'Tablet', 1500, 105000.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 11:48:55'),
(15, 'OBT0015', 'ACYCLOVIR 400  400 MG PHAROS', 2, 132, 'TABLET', 1000, 1.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-06-01 03:57:24'),
(16, 'OBT0016', 'ACYCLOVIR 5g 5 g First Medipharma', 2, 0, 'Tube', 8000, 5500.00, 8000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-31 17:23:16'),
(17, 'OBT0017', 'ALERZIN 10MG Sampharindo Perdana', 1, 270, 'Tablet', 600, 11000.00, 600, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(18, 'OBT0018', 'ALKOHOL 70% antiseptik 100ml 100ml MOLEX AYUS', 1, 10, 'Botol', 9000, 5033.00, 9000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-21 15:19:16'),
(19, 'OBT0019', 'ALLERON  4MG MEF', 2, 120, 'Kaplet', 300, 23.54, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(20, 'OBT0020', 'ALLOPURINOL  100 100 MG HEXPHARM JAYA', 7, 0, 'Tablet', 400, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-21 15:19:12'),
(21, 'OBT0021', 'ALOCLAIR PLUS MOUTHWASH syr 60ml 60ml Enseval putera megatrading, Tbk', 2, 0, 'Botol', 44000, 20000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-06-01 03:57:24'),
(22, 'OBT0022', 'ALOFAR 300 300 MG IFARS', 7, 0, 'KAPLET', 900, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(23, 'OBT0023', 'ALOFAR 300 ifars 300 mg IFARS', 2, 60, 'Kaplet', 900, 841.67, 900, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(24, 'OBT0024', 'ALPARA  500MG MOLEX AYUS', 7, 200, 'KAPLET', 1500, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(25, 'OBT0025', 'ALTADIS / MICONAZOLE SALF 10G 10G PRIMA MEDIKA', 2, 1, 'Tube', 24000, 18000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(26, 'OBT0026', 'ALTAMED ALCOHOL SWAB - SAPTA SARI', 6, 303, 'PCS', 11000, 7834.00, 11000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(27, 'OBT0027', 'ALTAMED alcohol swab (03/09/25) 100 SAPTA SARI', 1, 400, 'Pcs', 11000, 78.34, 11000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(28, 'OBT0028', 'AMBROXOL HYDROCHLORIDE 30 Errita Pharma', 2, 270, 'Tablet', 400, 20846.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(29, 'OBT0029', 'AMBROXOL SYR Bernofarm 60ml 60ML BERNOFARM', 2, 6, 'Botol', 9000, 6750.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(30, 'OBT0030', 'AMLODIPINE 5mg ifars KAPLET 5mg IFARS', 2, 90, 'Kaplet', 500, 12750.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(31, 'OBT0031', 'AMLODIPINE BESILAT 10 HJ 10 Hexpharm Jaya', 2, 250, 'Tablet', 1400, 100.00, 1400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(32, 'OBT0032', 'AMLODIPINE BESILATE 10MG SANCO OGB 10MG SANCO OGB', 2, 70, 'Tablet', 700, 97.50, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(33, 'OBT0033', 'AMOXICILLIN 500MG 500M ERRITA PHARMA', 2, 220, 'KAPLET', 1300, 1000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(34, 'OBT0034', 'AMOXICILLIN TRIHYDRATE 500MG IFARS', 2, 0, 'Kaplet', 1300, 66833.00, 1300, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(35, 'OBT0035', 'AMOXSAN FORTE syr kering 60ml 60ml Capri', 2, 1, 'Botol', 45000, 32800.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(36, 'OBT0036', 'ANABION SYR 60ml  60ml Berlico Mulia Farma', 1, 6, 'Botol', 11000, 7675.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(37, 'OBT0037', 'ANACETIN 60ML BMF', 7, 11, 'Syrup', 16000, 11719.00, 16000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(38, 'OBT0038', 'ANAKONIDIN 60ml Konimex', 7, 0, 'Syrup', 15000, 11300.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(39, 'OBT0039', 'ANAKONIDIN OBH 60ML Konimex', 7, 2, 'Syrup', 16000, 12300.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(40, 'OBT0040', 'ANASTAN FORTE 500 Gratia Husada Farma', 2, 280, 'Tablet', 600, 350.00, 600, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(41, 'OBT0041', 'ANDALAN 3BULAN 150MG/1ML Harsen', 6, 17, 'INJEK', 10000, 1784.48, 10000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(42, 'OBT0042', 'ANDALAN pil KB  0,03 mg Harsen', 2, 476, 'Tablet', 900, 13406.00, 900, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(43, 'OBT0043', 'ANTANGIN JRG + MADU  12 DELTO', 1, 0, 'PCS', 4000, 2792.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(44, 'OBT0044', 'ANTASIDA DEON 60ML Sampharindo Perdana', 7, 0, 'Syrup', 7000, 5135.00, 7000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(45, 'OBT0045', 'ANTASIDA DOEN 60ML Mersi', 7, 150, 'SUSPENSI', 7000, 3000.00, 7000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-06-29 15:00:13'),
(46, 'OBT0046', 'ANTASIDA DOEN syr 60ml ERELA 60ml ERELA', 2, 7, 'Botol', 7000, 553.11, 7000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(47, 'OBT0047', 'ARKAVIT C 500 BALAFIT', 1, 140, 'Tablet', 1500, 1075.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(48, 'OBT0048', 'ARKAVIT TAB 20mg Balatif', 1, 0, 'Kaplet', 1000, 500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(49, 'OBT0049', 'ARMACORT salf  5g IFARS', 2, 6, 'Tube', 15000, 10517.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(50, 'OBT0050', 'ASAM MEFENAMAT 500mg Dexa Medica', 2, 310, 'Tablet', 500, 27225.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(51, 'OBT0051', 'ASAM MEFENAMAT  500 Errita Pharma', 2, 0, 'Kapsul', 500, 400.00, 5000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(52, 'OBT0052', 'ASPILETS 80 Darya Varia', 2, 110, 'Tablet', 1000, 61000.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(53, 'OBT0053', 'Acetylcisteine 200mg Mulia Farma Suci', 2, 0, 'Kapsul', 1500, 105000.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-21 15:17:54'),
(54, 'OBT0054', 'Acifar 5g IFARS', 2, 0, 'Salep', 10000, 9000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-31 17:23:16'),
(55, 'OBT0055', 'Actifed 60ml Haleon', 7, 0, 'Syrup', 70000, 52400.00, 70000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 11:48:55'),
(56, 'OBT0056', 'Acyclovir KRIM 0,05 0,05 First Medipharma', 2, 0, 'Tube', 8000, 5500.00, 8000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-21 15:19:01'),
(57, 'OBT0057', 'Allopurinol 100mg HJ 100mg HEXPHARM JAYA', 2, 200, 'Tablet', 400, 260.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(58, 'OBT0058', 'Allopurinol HJ 300 300mg Hexpharm Jaya', 2, 230, 'Tablet', 700, 550.00, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(59, 'OBT0059', 'Alofar 100 100mg IFARS', 2, 0, 'Kaplet', 600, 300.00, 600, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(60, 'OBT0060', 'Alofar IFARS 100 100 mg Ifars', 2, 120, 'Kaplet', 600, 100.00, 600, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(61, 'OBT0061', 'Alpara 500mg Molex Ayus', 7, 0, 'Kaplet', 1500, 120000.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(62, 'OBT0062', 'Ambeven 90mg Medikon', 6, 84, 'Kapsul', 2200, 1.00, 2200, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(63, 'OBT0063', 'Ambroxol HCI 15mg/5ml (60ml) 15mg Bernofarm', 2, 5, 'Botol', 7000, 6000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(64, 'OBT0064', 'Amlodipine BESILATE 10MG IFARS 10mg IFARS', 2, 80, 'Kaplet', 700, 112.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(65, 'OBT0065', 'Amlodipine Besilate  MULIA 10 10 mg Mulia Farma Suci', 2, 0, 'Tablet', 700, 146.00, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(66, 'OBT0066', 'Amlodipine Besilate 5mg HJ 5mg HEXPHARM JAYA', 2, 215, 'Tablet', 700, 361.80, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(67, 'OBT0067', 'Amlodipine Besilate IFARS 10 10 mg  Ifars', 2, 0, 'Kaplet', 700, 15450.00, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(68, 'OBT0068', 'Amlodipine Besilate OGB 5 5 MG SAMCO', 2, 0, 'Tablet', 500, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(69, 'OBT0069', 'Amoxicilin syr kering 125mg/5ml 60ml Mersi', 2, 9, 'Botol', 5000, 3480.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(70, 'OBT0070', 'Amoxicilin syr kering 250mg/5ml 60ml Mersi', 2, 7, 'Botol', 7000, 5200.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(71, 'OBT0071', 'Anakonidin 30ml Konimex', 1, 0, 'Syrup', 12000, 11000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(72, 'OBT0072', 'Anakonidin 60ml Konimex', 1, 0, 'Syrup', 16000, 15000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(73, 'OBT0073', 'Antasida Doen Tablet 200mg Imfarmind Farmasi Industri', 1, 344, 'Tablet', 300, 119.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(74, 'OBT0074', 'Antimo 50mg Phapros', 1, 0, 'Tablet', 600, 500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(75, 'OBT0075', 'Arkavit  20mg Balatif', 1, 105, 'Kaplet', 1000, 500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(76, 'OBT0076', 'B Complex 2mg Kimia Farma', 1, 3, 'Tablet', 300, 200.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(77, 'OBT0077', 'BALSEM GELIGA  20g Eagle indo farma', 1, 2, 'PCS', 10000, 7500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(78, 'OBT0078', 'BALSEM TELON 20 GR 20 GR Ultra Sakti', 1, 4, 'TUBE', 10000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(79, 'OBT0079', 'BATUGIN ELIXIR  120 ml Kimia Farma', 7, 1, 'Botol', 35000, 26050.00, 35000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(80, 'OBT0080', 'BENOSON 5GRAM Bernofarm', 2, 1, 'Salep', 19000, 15000.00, 19000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(81, 'OBT0081', 'BETADINE MOUTHWASH AND GARGLE 100ml 100 ml mundipharma', 7, 0, 'Botol', 30000, 20212.00, 30000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(82, 'OBT0082', 'BETAHISTINE MESILATE 6 Dexa Medica', 2, 210, 'Tablet', 700, 83916.00, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(83, 'OBT0083', 'BIOCORRBIN 5000MG Mepro', 2, 11, 'Ampul', 10000, 55585.00, 10000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(84, 'OBT0084', 'BIOPLACENTON 15 g 15 g Kalbe', 2, 4, 'Tube', 33000, 28000.00, 33000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(85, 'OBT0085', 'BISOPROLOL FUMARATE 5 MG 5 MG HEXPHARM JAYA', 2, 60, 'TABLET', 1000, 1.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(86, 'OBT0086', 'BROMIFAR PLUS sirup ifars 60ml  60ml IFARS', 2, 6, 'Botol', 9000, 5500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(87, 'OBT0087', 'BUFACOB 5GRAM Bufa', 2, 2, 'KRIM', 37000, 34000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(88, 'OBT0088', 'BUFACOMB SALF 5 5 GR BUFA ANEKA', 2, 2, 'TUBE', 33000, 28500.00, 33000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(89, 'OBT0089', 'BYE BYE FEVER ANAK 10 Hisamitsu', 1, 10, 'Sachet', 12000, 9629.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(90, 'OBT0090', 'BYE BYE FEVER BABY 10 PYRI', 1, 10, 'PLESTER', 8000, 5687.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(91, 'OBT0091', 'BYE BYE FEVER DEWASA 10', 1, 28, 'PLESTER', 13000, 1500.00, 13000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(92, 'OBT0092', 'Balsem Lang 10gram Caplang', 6, 2, 'Pcs', 10000, 1.00, 10000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(93, 'OBT0093', 'Bedak salicyl biru 60gr Nellco', 6, 0, 'Botol', 10000, 1000.00, 10000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(94, 'OBT0094', 'Bedak salicyl menthol coklat  60 g Nellco', 6, 5, 'Botol', 10000, 7700.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(95, 'OBT0095', 'Bedak salicyl orange 60gr Nellco', 6, 0, 'Botol', 6000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(96, 'OBT0096', 'Bedak salicyl pink 60gr Nellco', 6, 1, 'Botol', 8000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(97, 'OBT0097', 'Benoson-N 15gr Bernofarm', 2, 0, 'Krim', 45000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(98, 'OBT0098', 'Benoson-N 5G 5gram Bernofarm', 2, 0, 'Krim', 23000, 1.00, 23000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(99, 'OBT0099', 'Betadine Antiseptic Solution 5ml 5 ml mundipharma', 1, 2, 'Botol', 8000, 5530.00, 8000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-28 08:05:12'),
(100, 'OBT0100', 'Betadine Mouthwash 190ml 190ml mundipharma', 6, 2, 'botol', 44000, 32445.00, 44000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(101, 'OBT0101', 'Betadine Sol 15ML 15 ml mundipharma', 1, 1, 'Botol', 18000, 12861.00, 18000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(102, 'OBT0102', 'Betamethasone VALERATE 0,1%/5g 5g KIMIA FARMA', 2, 64, 'Tube', 6000, 55425.00, 6000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(103, 'OBT0103', 'Bionce 91.27.00 Triman', 6, 15, 'Kapsul', 500, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(104, 'OBT0104', 'Bioplacenton 15gr Kalbe', 2, 0, 'GEL', 33000, 32000.00, 33000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(105, 'OBT0105', 'Bisolvon Extra syr 60ml Sanofi', 7, 1, 'Botol', 55000, 44702.00, 55000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(106, 'OBT0106', 'Broadamox 125 125mg Sampharindo Perdana', 2, 2, 'sirup', 20000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(107, 'OBT0107', 'Broadamox 250mg 250mg/5ml Sampharindo Perdana', 8, 0, 'Syrup', 10208, 7852.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(108, 'OBT0108', 'Bronchitin Expectorant 120mg Exeltis', 7, 8, 'sirup', 12000, 1.00, 12000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(109, 'OBT0109', 'Bufacort - N 5g Bufa', 2, 3, 'Salep', 25000, 7401.00, 25000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(110, 'OBT0110', 'CANDESARTAN 8 8 MG INTERBAT', 2, 0, 'TAB', 2100, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(111, 'OBT0111', 'CANDESARTAN CILEXETIL 16 16 MG OGB DEXA', 2, 10, 'Tablet', 3300, 195000.00, 3300, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(112, 'OBT0112', 'CANDESARTAN CILEXETIL 8 8 MG OGB DEXA', 2, 30, 'Tablet', 2000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(113, 'OBT0113', 'CAPTOPRIL 12,5 DEXA 12,5 Dexa Medica', 2, 140, 'Tablet', 2000, 1000.00, 2000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(114, 'OBT0114', 'CAPTOPRIL 25 ERRITA 25 Errita Pharma', 2, 50, 'Tablet', 300, 24.17, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(115, 'OBT0115', 'CARBIDU 0,5 Sampharindo Perdana', 2, 0, 'Tablet', 500, 10.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(116, 'OBT0116', 'CARMED  40 gr 10% Surya Dermato', 2, 1, 'Tube', 44000, 33500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(117, 'OBT0117', 'CAVIPLEX 60ML Erela', 1, 6, 'Syrup', 17000, 9818.00, 17000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(118, 'OBT0118', 'CAVIPLEX BONE kap 330iu ERELA', 1, 60, 'Kaplet', 1500, 25000.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(119, 'OBT0119', 'CAVIPLEX KAP 4000 iu new 4000 iu ERELA', 1, 335, 'Kaplet', 1600, 612.50, 1600, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(120, 'OBT0120', 'CAVIPLEX Kaplet 4000 iu Erela', 1, 0, 'Kaplet', 1500, 61250.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(121, 'OBT0121', 'CDR 1000mg Bayer', 6, 0, 'Botol', 60000, 1.00, 60000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(122, 'OBT0122', 'CEFADROXIL 250mg/60 mL 250 mg  IFARS', 2, 2, 'Botol', 20000, 15500.00, 20000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(123, 'OBT0123', 'CEFADROXIL MONOHYDRATE 500MG HEXPHARM JAYA', 2, 60, 'Kapsul', 1600, 110.00, 1600, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(124, 'OBT0124', 'CEFIXIME 100mg 100 MG HEXPHARM JAYA', 2, 300, 'KAPLET', 1900, 225000.00, 1900, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(125, 'OBT0125', 'CEFIXIME 200 MG 200 mg  Dexa Medica', 2, 60, 'Kapsul', 3100, 366700.00, 3100, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(126, 'OBT0126', 'CEFIXIME TRIHYDRATE SYR 100MG/5ML IFARS', 2, 2, 'Syrup', 35000, 14000.00, 35000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(127, 'OBT0127', 'CELANA SUNAT UK XXXL 123 SAMSON', 1, 4, 'PCS', 25000, 17000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(128, 'OBT0128', 'CENDO EYEFRESH TM minidose 0,6ml 0,6ml Cendo', 2, 5, 'Tube', 6000, 2000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(129, 'OBT0129', 'CENDO XITROL Minidose TM 0,6ml 0,6ml Cendo', 2, 10, 'Tube', 6000, 2690.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(130, 'OBT0130', 'CENDO XITROL TM BOTOL 5ML Cendo', 2, 3, 'Botol', 40000, 29900.00, 40000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(131, 'OBT0131', 'CENDO XLYTEERS MINI DOSE / per pcs 0,6ML CENDO', 2, 10, 'TUBE', 5000, 3700.00, 5000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(132, 'OBT0132', 'CETIRIZINE HCI hj 10mg  10mg HEXPHARM JAYA', 2, 290, 'Tablet', 400, 300.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(133, 'OBT0133', 'CHIO ESSENTIAL OIL 10ML DELTA', 9, 5, 'ESSENTIAL', 35000, 34000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(134, 'OBT0134', 'CHIO HIJAU 10ML DELTA', 9, 1, 'ESSENTIAL', 35000, 34000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(135, 'OBT0135', 'CHIO KUNING 10ML DELTA', 9, 1, 'ESSENTIAL', 35000, 34000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(136, 'OBT0136', 'CHIO UNGU 10ML DELTA', 9, 1, 'ESSENTIAL', 35000, 34000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(137, 'OBT0137', 'CHLORPHENAMINE MALEATE (CTM) TRIFA 4 MG TRIFA', 7, 208, 'Tablet', 200, 120.00, 200, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(138, 'OBT0138', 'CIPROFLOXACIN HCI 500mg  500 MG Hexpharm Jaya', 2, 370, 'Tablet', 1000, 459.18, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(139, 'OBT0139', 'CLINDAMYCIN 300 mg   300 mg mersi', 2, 50, 'Kapsul', 1300, 1000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(140, 'OBT0140', 'COMBANTRIN 250mg TAB 250mg JOHNSON', 1, 8, 'Tablet', 12000, 8000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(141, 'OBT0141', 'COMBANTRINJeruk SUSPENSI  10 ml Pfizer', 2, 4, 'Botol', 24000, 17440.00, 24000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(142, 'OBT0142', 'COOLING 5 PLUS Spray gigi 15 ml Novell', 7, 1, 'Botol', 46000, 34000.00, 46000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(143, 'OBT0143', 'COPARCETIN 500MG Sampharindo Perdana', 7, 303, 'Tablet', 1000, 50558.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(144, 'OBT0144', 'CORPACETIN 500 Sampharindo Perdana', 2, 35, 'Kaplet', 1000, 800.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(145, 'OBT0145', 'COTRIMOXAZOLE SYR 60ML Mersi', 2, 4, 'Syrup', 5000, 3900.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(146, 'OBT0146', 'COTRIMOXAZOLE TABLET 400MG mersi', 2, 20, 'Tablet', 500, 150.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(147, 'OBT0147', 'COTTON BUD SELECTION 1234567 SELECTION', 6, 1, 'PCS', 7000, 2000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(148, 'OBT0148', 'COUNTERPAIN Salf 15 GR 15 gr Taisho', 2, 2, 'Tube', 32000, 23300.00, 32000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(149, 'OBT0149', 'COUNTERPAIN Salf 5 GR 5 gr  Taisho', 1, 2, 'Tube', 10010, 7700.00, 10010, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(150, 'OBT0150', 'CURBION SYR 60ML Mutifa', 1, 0, 'Botol', 10000, 7000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(151, 'OBT0151', 'CURVIPLEX SYR 60ml new (03/09/25) 60ml Graha Farma', 1, 4, 'Botol', 13000, 7533.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(152, 'OBT0152', 'CYCLOFEM KB 0,5ML Tunggal Idaman Abdi', 2, 45, 'VIAL', 12000, 175000.00, 12000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(153, 'OBT0153', 'Caladine LOTION 60 ML 60 ml Galenium', 7, 2, 'Botol', 21000, 15090.00, 21000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(154, 'OBT0154', 'Caladine LOTION 95 ML 95 ml Galenium', 7, 0, 'Botol', 28000, 20620.00, 28000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(155, 'OBT0155', 'Caladine Talk 60g Galnium', 6, 1, 'Botol', 20000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(156, 'OBT0156', 'Captopril 12,5 MG 12.5mg Dexa Medica', 6, 0, 'Tablet', 200, 1.00, 200, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(157, 'OBT0157', 'Caviplex kaplet 4000iu ERELA', 1, 0, 'Kaplet', 1600, 612.50, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(158, 'OBT0158', 'Celana Sunat Uk L - -', 6, 0, 'Pcs', 25000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(159, 'OBT0159', 'Celana Sunat Uk S - -', 6, 1, 'Pcs', 25000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(160, 'OBT0160', 'Celana Sunat Uk XL - -', 6, 1, 'Pcs', 25000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(161, 'OBT0161', 'Celana Sunat Uk XXL - -', 6, 2, 'Pcs', 25000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(162, 'OBT0162', 'Cerebrofort Marine Gummy Orange 8 SAKA FARMA', 1, 0, 'PCS', 17000, 13004.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(163, 'OBT0163', 'Cetirizine HCI  10 mg Hexpharm Jaya', 2, 4, 'Tablet', 400, 30000.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(164, 'OBT0164', 'Cetirizine hydrochloride SYR 60ml Sampharindo Perdana', 2, 7, 'Syrup', 10000, 8000.00, 10000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(165, 'OBT0165', 'Chloramfecort - H 10g Kimia Farma', 2, 0, 'Salep', 18000, 17000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(166, 'OBT0166', 'Cinolon N  5mg Sanbe', 2, 3, 'Salep', 18000, 17000.00, 18000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(167, 'OBT0167', 'Ciprofloxacin 500mg HJ (03/09/25) 500MG HEXPHARM JAYA', 2, 0, 'Tablet', 1000, 372.45, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(168, 'OBT0168', 'Ciprofloxacin HCl 500 mg Hexpharm Jaya', 2, 0, 'Tablet', 1000, 50000.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(169, 'OBT0169', 'Clindamycin Hydrochloride 150 MG 150mg Novell', 6, 40, 'Kapsul', 1000, 1.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(170, 'OBT0170', 'Codeine Phoshate 20MG Kimia farma', 6, 10, 'Tab', 2000, 1.00, 2000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(171, 'OBT0171', 'Colchicine 0.5mg Novell', 6, 10, 'Tablet', 1500, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(172, 'OBT0172', 'Contrimoxazole Syrup 240mg/5ml Mersi', 8, 4, 'Syrup', 5703, 4387.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(173, 'OBT0173', 'Cooling 5 5mg Novell', 6, 0, 'Pcs', 45000, 1.00, 45000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(174, 'OBT0174', 'Coparcetin SYR 60ml Sampharindo Perdana', 1, 3, 'Syrup', 13000, 10000.00, 13000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(175, 'OBT0175', 'Coparcetin kid cough SYR 60ml Sampharindo Perdana', 1, 0, 'Syrup', 15000, 14000.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(176, 'OBT0176', 'Curcuma Force 20 mg Soho', 6, 90, 'Tablet', 1600, 1.00, 1600, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(177, 'OBT0177', 'Curcuma plus SYR 60ml 60Ml Soho\'', 1, 5, 'Syrup', 18000, 13100.00, 18000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(178, 'OBT0178', 'Cyclo Harmonis 25 gram Catur Dakwah Crane Farmasi', 2, 9, 'Vial', 10000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(179, 'OBT0179', 'DAKTARIN 10g JANSSEN', 7, 1, 'Tube', 52000, 37773.00, 52000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(180, 'OBT0180', 'DAKTARIN 5g JANSSEN', 7, 0, 'Tube', 32000, 23951.00, 32000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(181, 'OBT0181', 'DANASONE 0,5 Hexpharm Jaya', 2, 740, 'Tablet', 500, 20000.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(182, 'OBT0182', 'DEHISTA 4 Berlico Mulia Farma', 2, 0, 'Tablet', 300, 100.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(183, 'OBT0183', 'DEHISTA / CHLORPHENAMINE MALEATE 4 Novapharin', 7, 58, 'Tablet', 300, 100.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(184, 'OBT0184', 'DEMACOLIN 500mg', 7, 320, 'Tablet', 1000, 446.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(185, 'OBT0185', 'DEXAHARSEN 0.75 MG 7,5 MG HEXPHARM JAYA', 7, 180, 'Tablet', 400, 1.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(186, 'OBT0186', 'DEXAMETHASONE 0,5MG BERNOFARM', 2, 0, 'Tablet', 300, 14000.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(187, 'OBT0187', 'DEXTEEM PLUS 0,5mg Erlimpex  0,5mg ERLIMPEX', 2, 280, 'Tablet', 500, 28307.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(188, 'OBT0188', 'DEXTEEM PLUS New (03/09/25) 2mg Erlimpex', 2, 0, 'Tablet', 500, 296.46, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(189, 'OBT0189', 'DEXTRAL 500MG MOLEX AYUS', 1, 120, 'Tablet', 1000, 830.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(190, 'OBT0190', 'DICLOFENAC POTASSIUM 25MG NULAB PHARMACEUTICAL INDONESIA', 2, 90, 'Tablet', 900, 35.53, 900, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(191, 'OBT0191', 'DICLOFENAC SODIUM 50MG NOVELL', 2, 306, 'Tablet', 900, 14235.00, 900, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(192, 'OBT0192', 'DICLOFENAC SODIUM  50 SAMCO', 2, 0, 'Tablet', 900, 24000.00, 900, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(193, 'OBT0193', 'DIGOXIN Tablet  0,25 mg First Medipharma', 2, 100, 'Tablet', 300, 230.00, 3000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(194, 'OBT0194', 'DIVOLTAR 50 (DICLOFENAC SODIUM/NA) 50MG Kalbe', 2, 0, 'Tablet', 1000, 500.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(195, 'OBT0195', 'DOMPERIDONE TAB 10 MG Sampharindo Perdana', 7, 94, 'Tablet', 600, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(196, 'OBT0196', 'DOXYCYCLINE  100MG 100MG KIMIA FARMA', 2, 10, 'KAPSUL', 1000, 700.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(197, 'OBT0197', 'DULCOLAX TAB 5mg 5mg Erel', 1, 40, 'Tablet', 2300, 1664.10, 2300, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(198, 'OBT0198', 'DUMIN 500 500MG ACTAVIS', 1, 70, 'Kaplet', 900, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(199, 'OBT0199', 'DUMIN Rectal 250mg/4ml  250mg/4ml  ACTAVIS', 2, 7, 'Tube', 42000, 30000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(200, 'OBT0200', 'Decadryl 60 60ml Harsen', 6, 3, 'sirup', 21000, 15500.00, 21000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(201, 'OBT0201', 'Decolgen 400mg Darya Varia', 7, 8, 'Tablet', 500, 400.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(202, 'OBT0202', 'Degirol Lozenges  0,25 mg Darya Varia', 1, 90, 'Tablet', 1600, 1111.84, 1600, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(203, 'OBT0203', 'Deponeo 150 mg Triyasa Nagamas Farma', 2, 4, 'Vial', 10000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(204, 'OBT0204', 'Diatabs  600 mg Darya Varia', 1, 96, 'Tablet', 1000, 300.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(205, 'OBT0205', 'Diclofenac Potassium 50mg Hexpharm Jaya', 6, 0, 'Tablet', 900, 65000.00, 900, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(206, 'OBT0206', 'Dohixat 100mg IFARS', 6, 150, 'Kapsul', 1500, 1.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(207, 'OBT0207', 'Domperidone syr 60ml  60ml ERLIMPEX', 2, 4, 'Botol', 6000, 4363.00, 6000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(208, 'OBT0208', 'ELSIRON 650 IFARS', 2, 3, 'Tablet', 1400, 61000.00, 1400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(209, 'OBT0209', 'ELSIRON 650mg IFARS', 7, 90, 'Kaplet', 1400, 61000.00, 1400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(210, 'OBT0210', 'ENERVON C Tab 4 Medifarma', 1, 84, 'Tablet', 3750, 1000.00, 3750, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(211, 'OBT0211', 'ENTROSTOP 650 mg Kalbe', 1, 58, 'Tablet', 1000, 750.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(212, 'OBT0212', 'ERLAMYCETIN Plus TM 5 ml Erela', 2, 5, 'Botol', 30000, 13357.00, 30000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(213, 'OBT0213', 'ERLAMYCETIN TM 10ML ERELA', 2, 2, 'Tube', 15000, 14000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(214, 'OBT0214', 'ERLAMYCETIN TM DROPS 10ML ERELA', 2, 0, 'DROP', 17000, 9928.00, 17000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(215, 'OBT0215', 'ERLAMYCETIN salf 3,5 g ERELA', 2, 6, 'Tube', 13000, 9666.00, 13000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(216, 'OBT0216', 'ERLAPEC 30MG ERELA', 2, 190, 'Tablet', 300, 20800.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(217, 'OBT0217', 'ERLAPECT 30mg 30mg ERELA', 7, 0, 'Tablet', 400, 280.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(218, 'OBT0218', 'ERPHASAL sirup 60ml 60ml ERLIMPEX', 2, 2, 'Botol', 30000, 20000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(219, 'OBT0219', 'ERSOLON 4mg  4 mg ERLIMPEX', 2, 10, 'Tablet', 1000, 500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(220, 'OBT0220', 'ESTALEX 50 IFARS', 2, 0, 'Tablet', 2000, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(221, 'OBT0221', 'ETABION 50 ETA', 1, 135, 'Tablet', 800, 600.00, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(222, 'OBT0222', 'Ecodin 30ml jayamas medika', 6, 4, 'botol', 15000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(223, 'OBT0223', 'Elastic bandage 1234 onemed', 10, 1, 'Pcs', 50000, 30000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(224, 'OBT0224', 'Elastic bandage 2 1234 Winner', 10, 1, 'Pcs', 55000, 40000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(225, 'OBT0225', 'Erladerm 5gram Erela', 2, 4, 'Krim', 10000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(226, 'OBT0226', 'Erlamycetin TT  10ml ERELA', 2, 4, 'Botol', 15000, 98.33, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(227, 'OBT0227', 'Etadryl Expectorant 60ml Errita Pharma', 6, 0, 'sirup', 8000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(228, 'OBT0228', 'Ever E250 250mg Konimex', 6, 30, 'Capsul', 74000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(229, 'OBT0229', 'FARIZOL 500 MG (METRONIDAZOL) 500 MG IFARS', 2, 100, 'KAPLET', 1200, 45666.00, 1200, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(230, 'OBT0230', 'FARMOTEN Tablet 25mg Pratapa Nirmala', 2, 120, 'Tablet', 400, 26875.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(231, 'OBT0231', 'FARSIFEN  200MG IFARS', 2, 100, 'Tablet', 400, 120.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(232, 'OBT0232', 'FARSIFEN 200mg Ifars Kap 200mg IFARS', 1, 100, 'Tablet', 4000, 2000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(233, 'OBT0233', 'FARSIFEN FORTE sirup 60ml 60ml IFARS', 2, 5, 'Botol', 18000, 9083.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(234, 'OBT0234', 'FARSIFEN PLUS 600 IFARS', 2, 130, 'Tablet', 1000, 900.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(235, 'OBT0235', 'FARSIRETIC 40 IFARS', 2, 20, 'Tablet', 600, 5.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(236, 'OBT0236', 'FASIDOL DROPS 15 ml IFARS', 1, 3, 'Botol', 20000, 10000.00, 20000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(237, 'OBT0237', 'FASIDOL FORTE 650MG IFARS', 7, 60, 'Tablet', 1000, 800.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(238, 'OBT0238', 'FASIDOL PLUS 650mg IFARS', 1, 40, 'Kaplet', 1000, 342.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(239, 'OBT0239', 'FERRIZ strawberry 100ml 100ml Nicholas', 1, 1, 'Botol', 49000, 37500.00, 49000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(240, 'OBT0240', 'FG TROCHES MEIJI 2,5MG MEIJI', 9, 100, 'SALUT', 2000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(241, 'OBT0241', 'FITKOM MULTIVIT  RASA ANGGUR / tablet  100MG Soho', 1, 21, 'Tablet', 905, 1.00, 905, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(242, 'OBT0242', 'FITKOM RASA JERUK / TABLET  21 Soho', 1, 21, 'Tablet', 905, 10.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(243, 'OBT0243', 'FLOXIFAR 500MG IFARS', 2, 140, 'KAPLET', 1600, 61666.00, 1600, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(244, 'OBT0244', 'FLUCADEX 500 Graha Farma', 2, 0, 'Tablet', 1000, 646.69, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(245, 'OBT0245', 'FLUCADEX 60ml Graha Farma', 1, 4, 'Syrup', 21000, 19000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(246, 'OBT0246', 'FLUCADEX TAB 500 MG Graha Farma', 7, 170, 'KAPLET', 1000, 64669.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(247, 'OBT0247', 'FLUTAMOL 500mg Pyridam', 7, 0, 'Tablet', 1000, 700.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(248, 'OBT0248', 'FLUTAMOL TAB 500 MG Pyridam', 7, 100, 'Kaplet', 1000, 43573.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(249, 'OBT0249', 'FLUTOP-C syr 60ml 60ml IFARS', 7, 7, 'Botol', 13000, 9954.00, 13000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(250, 'OBT0250', 'FLUTROP 30MG TROPICA', 7, 30, 'TAB', 1500, 1400.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(251, 'OBT0251', 'FLUTROP 30 MG TROPICA', 2, 0, 'Kaplet', 1500, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(252, 'OBT0252', 'FLUTROP-C 60ML IFARS', 1, 6, 'Syrup', 13000, 11000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(253, 'OBT0253', 'FOLACITE 400 ERLIMPEX', 2, 120, 'Tablet', 400, 24200.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(254, 'OBT0254', 'FRESH CARE CITRUS 10ML Ultra Sakti', 11, 2, 'BOTOL KACA', 15000, 11560.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(255, 'OBT0255', 'FRESH CARE MINYAK ANGIN STRONG 10ML Ultra Sakti', 11, 4, 'BOTOL KACA', 15000, 11560.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(256, 'OBT0256', 'FRESHCARE SMASH ROLL NEW 0,9 ml Ultra Sakti', 1, 1, 'Botol', 16000, 11560.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(257, 'OBT0257', 'FUROSEMIDE 40 First Medipharma', 2, 10, 'Tablet', 300, 5.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(258, 'OBT0258', 'Faridexon tab 0,5mg Hexpharm Jaya', 2, 30, 'Tablet', 500, 300.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(259, 'OBT0259', 'Farsifen 400 400mg IFARS', 2, 80, 'Tablet', 1000, 48423.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(260, 'OBT0260', 'Farsifen syrup 100mg/5ml IFARS', 7, 0, 'Syrup', 15000, 6757.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(261, 'OBT0261', 'Fasidol Forte SYR  60ml IFARS', 1, 0, 'Syrup', 21000, 8056.00, 21000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(262, 'OBT0262', 'Fasidol SYR 60ml IFARS', 1, 7, 'Syrup', 15000, 14000.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(263, 'OBT0263', 'Fasidol ifars 500 MG 500 mg Ifars', 1, 400, 'Kaplet', 500, 32072.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(264, 'OBT0264', 'Flucadex SYR 60ml Graha Farma', 7, 0, 'Syrup', 21000, 12266.00, 21000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(265, 'OBT0265', 'Folavit 400mg Sanbe', 6, 50, 'Tablet', 14000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(266, 'OBT0266', 'Folley Catether no18 - One omed', 6, 3, 'Pcs', 25000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(267, 'OBT0267', 'FreshCare CITRUS 10 ml Ultra Sakti', 1, 0, 'Botol', 16000, 10000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(268, 'OBT0268', 'FreshCare Matcha NEW 0,9ml Ultra Sakti', 1, 1, 'Botol', 16000, 9826.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(269, 'OBT0269', 'FreshCare STRONG 10 ml Ultra Sakti', 1, 0, 'Botol', 16000, 10000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(270, 'OBT0270', 'Freshcare Lavender 10ml 10 Ultra Sakti', 1, 1, 'Botol', 16000, 9826.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(271, 'OBT0271', 'Frezza 13ml Konimex', 6, 2, 'Pcs', 26000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(272, 'OBT0272', 'GABAPENTIN 300mg 300 MG OGB DEXA', 2, 40, 'KAPSUL', 6000, 1500.00, 6000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(273, 'OBT0273', 'GABAPETIN 100mg 100MG Dexa Medica', 2, 100, 'Tablet', 3400, 250000.00, 3400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(274, 'OBT0274', 'GAELA 150 Erela', 2, 80, 'Tablet', 1500, 1300.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(275, 'OBT0275', 'GASELA erela 150 mg  150 mg ERELA', 2, 150, 'Tablet', 500, 265.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(276, 'OBT0276', 'GASTRUCID SYR 60ml NUFARINDO', 1, 6, 'BOTOL', 12000, 81.25, 12000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(277, 'OBT0277', 'GENOINT 15 GR Erela', 2, 0, 'Salep', 7000, 4000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(278, 'OBT0278', 'GLIMEPIRIDE 4mg 4 OGB DEXA', 2, 96, 'Tablet', 1500, 1400.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(279, 'OBT0279', 'GLUCOSAMINE mpl 500 mg  500 mg Medikon', 7, 130, 'Kaplet', 2300, 1000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(280, 'OBT0280', 'GLYCEROL 10% 10 LUCAS', 2, 0, 'Botol', 7000, 100.00, 7000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(281, 'OBT0281', 'GOM 10% PT AFI FARMA', 1, 0, 'BOTOL', 11000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(282, 'OBT0282', 'GOTIK  5 ml Prosweal Indomax', 7, 7, 'Botol', 6000, 1600.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(283, 'OBT0283', 'GPU 60ML 60ml Eagle indo farma', 6, 5, 'botol', 20000, 1.00, 20000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(284, 'OBT0284', 'GRAFALIN 4 (SALBUTAMOL 4) 4MG Graha Farma', 2, 200, 'Tablet', 400, 154.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(285, 'OBT0285', 'GRANTUSIF 120 Graha Farma', 2, 170, 'Tablet', 1000, 78011.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(286, 'OBT0286', 'Gastrucid 325 mg Exeltis', 1, 200, 'Tablet', 600, 1.00, 600, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(287, 'OBT0287', 'Genoint 3.5 gram Erela', 2, 0, 'Salep Mata Steril', 12000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(288, 'OBT0288', 'Gentamicin Sulfate 5g Kimia Farma', 2, 0, 'Salep', 10000, 9000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(289, 'OBT0289', 'Gentamicin Sulfate 5g First Medipharma', 2, 8, 'Salep', 10000, 9000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(290, 'OBT0290', 'Giovan 90ml Giovan', 6, 2, 'Botol', 11000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(291, 'OBT0291', 'Glibenclamid 5mg 5mg Kimia farma', 2, 150, 'Tablet', 500, 1.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(292, 'OBT0292', 'Glibenclamid 5mg 5mg First Medipharma', 2, 90, 'Tablet', 500, 1.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(293, 'OBT0293', 'Glibenclamid 5mg 5mg Indo Farma', 6, 0, 'Tablet', 500, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(294, 'OBT0294', 'Glimepired 4 mg 4mg Kimia farma', 6, 0, 'Tablet', 1500, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(295, 'OBT0295', 'Glimepiride 2mg 2mg OGB DEXA', 2, 185, 'Tablet', 900, 729.68, 900, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(296, 'OBT0296', 'Glimepiride 3mg 4mg Dexa Medica', 6, 0, 'Tablet', 1500, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(297, 'OBT0297', 'Gom 8ml  8ml CITO', 7, 5, 'Botol', 12000, 8695.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(298, 'OBT0298', 'Guanistrep 986mg Itrasal', 6, 3, 'sirup', 16000, 5841.00, 16000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(299, 'OBT0299', 'HALOWELL C 500 500 mg PERTIWI AGUNG', 1, 0, 'BOTOL (30 KAPLET)', 40000, 31000.00, 40000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(300, 'OBT0300', 'HALOWELL D3 1000 1000 IU PERTIWI AGUNG', 1, 75, 'Tablet', 2300, 300.00, 2300, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(301, 'OBT0301', 'HAND SANITIZER antiseptic Asepso 55ml 55 ml Pan pacific', 1, 0, 'Botol', 13000, 9415.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(302, 'OBT0302', 'HANSAPLAST AQUA PROTECT 65mmX25mm Beiersdorf', 6, 0, 'LEMBAR', 3000, 125.31, 3000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(303, 'OBT0303', 'HEROCYN 85 GR 85 GR Pharmaceutical', 1, 4, 'BOTOL', 16000, 11400.00, 16000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(304, 'OBT0304', 'HEROCYN BABY 100G CORONET CROWN', 12, 2, 'TALK', 11000, 8000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(305, 'OBT0305', 'HEROCYN BABY 200GR 200 GR Pharmaceutical', 1, 2, 'BOTOL', 17000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(306, 'OBT0306', 'HEVIT PLUS 500 Hexpharm Jaya', 1, 5, 'Tablet', 1500, 1300.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(307, 'OBT0307', 'HEXALGIN 500 Hexpharm Jaya', 2, 100, 'Kaplet', 150, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(308, 'OBT0308', 'HISTIGO  6 MG IFARS', 1, 190, 'Tablet', 1000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(309, 'OBT0309', 'HISTOGO 10MG IFARS', 7, 90, 'Tablet', 1000, 29034.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(310, 'OBT0310', 'HOLISTI CARE ESTER C (ISI 4) 320MG ESTER C', 1, 72, 'Tablet', 2500, 69600.00, 2500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(311, 'OBT0311', 'HOLISTICARE ESTER C 30 TAB 800MG ESTER C', 1, 0, 'Botol', 59000, 50000.00, 59000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(312, 'OBT0312', 'HOLIZINC 500mg HOLI PHARMA', 1, 0, 'Tablet', 700, 24.35, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(313, 'OBT0313', 'HOT IN CR 60G ( BOTOL) 60 Ultra Sakti', 1, 1, 'Botol', 24000, 18000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(314, 'OBT0314', 'HOT IN CR STRONG 120G (BOTOL) 120 Ultra Sakti', 1, 2, 'Botol', 36000, 26900.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(315, 'OBT0315', 'HOT IN CREAM 120GR 120gr Ultra Sakti', 1, 2, 'Tube', 37000, 27600.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(316, 'OBT0316', 'HOT IN CREAM 120ML 120ML Ultra Sakti', 6, 0, 'Botol', 31000, 23332.00, 31000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(317, 'OBT0317', 'HOT IN CREAM STRONG 30 GR 30 GR Ultra Sakti', 1, 0, 'TUBE', 15000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(318, 'OBT0318', 'HOT IN DCL 30G Ultra Sakti', 6, 5, 'GEL', 16000, 11390.00, 16000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(319, 'OBT0319', 'HOT IN DCL 60 GR 60 GR Ultra Sakti', 1, 4, 'TUBE', 28000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(320, 'OBT0320', 'HUFABETHAMIN SYR 60ml  60ml HUFA', 2, 14, 'Botol', 15000, 5576.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(321, 'OBT0321', 'HUFADON 10 Hufa', 2, 0, 'Tablet', 500, 300.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(322, 'OBT0322', 'HUFADON 10MG HUFA', 7, 120, 'Tablet', 500, 25740.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(323, 'OBT0323', 'HUFAGRIP FLU DAN BATUK (KUNING) 60ML Hufa', 7, 1, 'Syrup', 27000, 19618.00, 27000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(324, 'OBT0324', 'Handscoon 123 mg Global Pharma Indonesia', 1, 300, 'Pcs', 1000, 500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(325, 'OBT0325', 'Hansaplast Roll 1,25cmx1m 1,25cmx1m Beiersdorf', 6, 15, 'Pcs', 8000, 1.00, 8000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(326, 'OBT0326', 'Hansaplast Roll 1,25cmx5cm 1 Beiersdorf', 1, 20, 'Pcs', 14000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34');
INSERT INTO `obat` (`id`, `kode`, `nama_obat`, `kategori_id`, `stok`, `satuan`, `harga_umum`, `harga_barang`, `harga_jual_bpjs`, `deskripsi`, `gambar`, `is_active`, `is_approved`, `created_at`, `updated_at`) VALUES
(327, 'OBT0327', 'Hansaplast elastis  FUN PCS 1 Beiersdorf', 1, 89, 'PCS', 1000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(328, 'OBT0328', 'Hansaplast elastis PCS 1 Beiersdorf', 6, 270, 'Pcs', 1000, 1.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(329, 'OBT0329', 'Hico 15 gram IFARS', 7, 2, 'Gel', 26000, 1.00, 26000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(330, 'OBT0330', 'Histigo 6mg IFARS', 2, 0, 'Tablet', 1000, 67166.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(331, 'OBT0331', 'Hot In Cream 30 GR 30Gr Top', 7, 1, 'Salep', 14000, 1.00, 14000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(332, 'OBT0332', 'Hot In Cream STRONG 60 GR 60Gr Top', 1, 2, 'Salep', 22000, 14200.00, 22000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(333, 'OBT0333', 'Hufabethamin 60ml HUFA 60ml HUFA', 2, 0, 'Botol', 15000, 5576.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(334, 'OBT0334', 'Hufadine 150mg Hufa', 2, 250, 'Tablet', 500, 33680.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(335, 'OBT0335', 'Hufadon syr 60ML 60ml Gratia Husada Farma', 2, 5, 'Botol', 9000, 9000.00, 9000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(336, 'OBT0336', 'Hufagrip BP hijau 60ml (03/09/25) 60ml Gratia Husada Farma', 2, 0, 'Botol', 23000, 17251.00, 23000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(337, 'OBT0337', 'Hufagrip Bp Batuk Kering & flu  (HIJAU) 60 ml Gratia Husada Farma', 7, 2, 'Botol', 23000, 17250.75, 23000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(338, 'OBT0338', 'Hufagripp Pilek (BIRU) 60ml Hufa', 7, 2, 'Syrup', 23000, 15000.00, 23000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(339, 'OBT0339', 'Hufavit kalk syr 60ml  60ml HUFA', 2, 8, 'Botol', 10000, 5995.00, 10000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(340, 'OBT0340', 'Hydrocortisone Acetate 5g Kalbe', 2, 23, 'Salep', 10000, 9000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(341, 'OBT0341', 'IBUPROFEN 400MG PROMED', 2, 0, 'Tablet', 400, 36939.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(342, 'OBT0342', 'IBUPROFEN 400MG TRIFA', 2, 0, 'Tablet', 400, 200.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(343, 'OBT0343', 'IBUPROFEN 400MG IFARS', 2, 0, 'Tablet', 400, 46333.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(344, 'OBT0344', 'ICHTIYOL-zalf 15gr 15gr Exeltis', 1, 2, 'Botol', 15000, 3000.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(345, 'OBT0345', 'IFARSYL  100MG IFARS', 7, 120, 'Tablet', 1000, 381.08, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(346, 'OBT0346', 'IMBOOST EXTRA VIT C & D3 500mg Soho', 1, 108, 'Tablet', 5250, 3880.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:36', '2026-05-23 08:01:34'),
(347, 'OBT0347', 'IMBOOST EXTRA Vit. C & D3 KIDS botol  200 IU Soho', 1, 2, 'Tablet', 37000, 27600.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(348, 'OBT0348', 'IMBOOST Original 10mg Soho', 1, 8, 'Tablet', 4750, 1000.00, 4750, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(349, 'OBT0349', 'INCIDAL ISI 4 10 Bayer', 7, 0, 'Tablet', 2100, 100.00, 2100, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(350, 'OBT0350', 'INFUS PARACETAMOL HJ  100ML Hexpharm Jaya', 2, 1, 'BAG', 53000, 40000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(351, 'OBT0351', 'INFUS RL  500 ml 500ml SATORIA', 2, 5, 'Botol', 19000, 11000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(352, 'OBT0352', 'INFUSION SET CHILD  123 Gea', 1, 4, 'SELANG', 15000, 11000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(353, 'OBT0353', 'INJ BIOCOMBIN 5000 HCI  3ml Mepro', 2, 3, 'Ampul', 20000, 6875.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(354, 'OBT0354', 'INJ DIPHENYDRAMINE HCI 123 PHAROS', 2, 5, 'Ampul', 10000, 4000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(355, 'OBT0355', 'INJ Dexamethasone HCI  1ml Pharos', 2, 1, 'Ampul', 10000, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(356, 'OBT0356', 'INJ Ondansetron HCI PT. IPHA  2mg/mL IPHA', 2, 2, 'Botol', 10000, 4000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(357, 'OBT0357', 'INJ RANITIDINE HCI 25mg/ml mersi', 2, 0, 'Botol', 10000, 8000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(358, 'OBT0358', 'INSTO DRY EYES 7,5ML Combiphar', 1, 5, 'Tube', 18000, 13063.00, 18000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(359, 'OBT0359', 'INSTO REGULAR  7,5 ml Pharma health care', 7, 4, 'Botol', 17000, 12300.00, 17000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(360, 'OBT0360', 'INTUNAL  300 Mepro', 7, 0, 'Kaplet', 750, 200.00, 750, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(361, 'OBT0361', 'IPI D3 1000 IU 1000IU FEBRI', 1, 135, 'Tube', 700, 495.00, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(362, 'OBT0362', 'IV Cath no 20 - Onemed', 6, 2, 'Pcs', 25000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(363, 'OBT0363', 'IV Cath no 22 - Hexa Care', 6, 2, 'Pcs', 25000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(364, 'OBT0364', 'IV Cath no. 24 - Onemed', 6, 1, 'Pcs', 25000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(365, 'OBT0365', 'IV Cath no.20 - Terumo', 6, 2, 'Pcs', 25000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(366, 'OBT0366', 'Ibuprofen 400mg Novapharin', 2, 0, 'Tablet', 32626, 25097.00, 32626, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(367, 'OBT0367', 'Ifarsyl Plus 60ml IFARS', 6, 1, 'sirup', 20000, 8417.00, 20000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(368, 'OBT0368', 'Ikadryl SYR 60ml ikapharmindo', 7, 0, 'sirup', 20000, 15000.00, 20000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(369, 'OBT0369', 'Imboost Force FCT ungu 250 mg SOHO Industri Pharmasi', 1, 22, 'Kaplet', 8200, 5843.57, 8200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(370, 'OBT0370', 'Imbost Force 250mg Soho', 6, 56, 'Tablet', 80000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(371, 'OBT0371', 'Incidal-OD 10mg  10 mg Bayer', 7, 30, 'Kapsul', 5250, 3498.72, 5250, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(372, 'OBT0372', 'Infus set Dewasa 123 Gea', 6, 7, 'Pcs', 15000, 1.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(373, 'OBT0373', 'Inj KETOROLAC TROMETAMOL  30mg/ml Mepro', 2, 13, 'Ampul', 10000, 1000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(374, 'OBT0374', 'Inj PHYTOMENADIONE Hexpharm 1ml 1ml HEXPHARM JAYA', 2, 5, 'Ampul', 10000, 7000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(375, 'OBT0375', 'Inj Ranitidin 15mg/ml Phapros', 13, 25, '1', 10000, 5000.00, 10000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(376, 'OBT0376', 'Injeksi 10ml - Onehealth', 6, 9, 'Pcs', 7000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(377, 'OBT0377', 'Injeksi 10ml - Onemed', 6, 2, 'Pcs', 7000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(378, 'OBT0378', 'Injeksi 5ml - Onehealth', 6, 8, 'Pcs', 5000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(379, 'OBT0379', 'Injeksi Ceftriaxone Sodium 1gr Mahakam Beta Farma', 2, 8, 'Vial', 50000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(380, 'OBT0380', 'Intunal F 500mg Mepro', 7, 120, 'Tablet', 1250, 10.00, 1250, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(381, 'OBT0381', 'Isosorbide Dinitrate 5mg Yarindo Farmatama', 2, 100, 'Tablet', 300, 20.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(382, 'OBT0382', 'KADITIC 50 IFARS', 2, 190, 'Tablet', 800, 35166.00, 800, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(383, 'OBT0383', 'KASSA ROLL 15 CM 123 onemed', 1, 3, 'PCS', 18000, 200.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(384, 'OBT0384', 'KETOCONAZOL TAB 200MG OGB DEXA', 2, 200, 'TABLET', 700, 20500.00, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(385, 'OBT0385', 'Kassa Roll - Onemed', 6, 18, 'Pcs', 8000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(386, 'OBT0386', 'Kassa steril 12 lembar Narita Farma', 6, 12, 'Box', 6000, 1.00, 6000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(387, 'OBT0387', 'Ketoconazole 5gram First Medipharma', 7, 12, 'Krim', 10000, 1.00, 10000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(388, 'OBT0388', 'Kitavit 100ml Hexpharm Jaya', 1, 2, 'Syrup', 17000, 16000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(389, 'OBT0389', 'Kotak P3K - -', 6, 1, 'Box', 30000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(390, 'OBT0390', 'L-VIT D3 5000MG LAPI', 2, 30, 'Tablet', 53000, 5200.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(391, 'OBT0391', 'LACTO B  1 GR NOVELL', 1, 8, 'SACHET', 10000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(392, 'OBT0392', 'LAMBUCID (KUNYAH) 500MG HEXPHARM JAYA', 7, 90, 'Tablet', 300, 48000.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(393, 'OBT0393', 'LANSOPRAZOLE 30mg NULAB PHARMACEUTICAL INDONESIA', 2, 200, 'Kapsul', 1800, 1500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(394, 'OBT0394', 'LASERIN MADU ANAK SYR 60ml MECOSIN', 2, 5, 'Botol', 16000, 12000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(395, 'OBT0395', 'LASERIN Mecosin SIRUP 60ml  60ML MECOSIN', 1, 4, 'Botol', 17000, 12500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(396, 'OBT0396', 'LASERIN SYR 30 ML 30 ML MECOSIN', 1, 2, 'BOTOL', 8000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(397, 'OBT0397', 'LAXADINE SYR 60 ml Galenium', 7, 3, 'Botol', 63000, 48500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(398, 'OBT0398', 'LECOZINC SYR 10MG/ 60ML IFARS', 7, 4, 'Botol', 22000, 9000.00, 22000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(399, 'OBT0399', 'LERZIN 10mg IFARS', 7, 90, 'Kapsul', 1500, 1400.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(400, 'OBT0400', 'LERZIN DROPS 10MG IFARS', 2, 1, 'Botol', 25000, 19000.00, 25000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(401, 'OBT0401', 'LEVOFLOXACIN PROMED 500MG PROMED', 2, 19, 'TABLET', 1300, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(402, 'OBT0402', 'LEVOFLOXACIN TAB 500MG  BERNOFARM', 2, 60, 'TABLET', 1200, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(403, 'OBT0403', 'LIBROCEF 500MG HEXPHARM JAYA', 2, 39, 'KAPSUL', 23000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(404, 'OBT0404', 'LICOKALK TAB 500MG Berlico Mulia Farma', 1, 220, 'Kaplet', 500, 32107.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(405, 'OBT0405', 'LODIA 2MG Sanbe', 2, 20, 'Tablet', 1500, 1086.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(406, 'OBT0406', 'LOKEV 20 IFARS', 7, 30, 'Kapsul', 1600, 1500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(407, 'OBT0407', 'LORATADINE 10 10 MG Novell', 7, 100, 'Tablet', 400, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(408, 'OBT0408', 'LORATADINE 10  10 MG NULAB PHARMACEUTICAL INDONESIA', 7, 0, 'TABLET', 400, 3.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(409, 'OBT0409', 'LOSTACEF 250mg syr 250 mg  IFARS', 2, 3, 'Botol', 25000, 15500.00, 25000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(410, 'OBT0410', 'LOSTACEF 500MG  500 MG IFARS', 2, 50, 'KAPSUL', 2800, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(411, 'OBT0411', 'Lambucid syr 100 ML 100 ml Hexpharm Jaya', 2, 9, 'Botol', 16000, 11500.00, 16000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(412, 'OBT0412', 'Lambucid syr 60 ML 60ml Hexpharm Jaya', 2, 4, 'Botol', 13000, 8500.00, 13000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(413, 'OBT0413', 'Lecozinc IFARS TABLET 20mg Ifars', 2, 255, 'Tablet', 800, 348.33, 800, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(414, 'OBT0414', 'Lerzin SIRUP 60ML IFARS', 2, 3, 'sirup', 14000, 6644.00, 14000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(415, 'OBT0415', 'Libebi COUGH Syrup  60ml HOLI PHARMA', 1, 8, 'Syrup', 14000, 5236.00, 14000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(416, 'OBT0416', 'Licocalk Plus 500mg Berlico Mulia Farma', 6, 25, 'Kaplet', 700, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(417, 'OBT0417', 'Lopamid 2mg Harsen', 2, 83, 'Tablet', 500, 24474.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(418, 'OBT0418', 'Lostacef 125mg syr 125mg IFARS', 2, 2, 'sirup', 18000, 1.00, 18000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(419, 'OBT0419', 'MEDI-KLIN 15 GR SDM', 2, 1, 'SALEP', 37000, 27200.00, 37000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(420, 'OBT0420', 'MEDI-KLIN TR 15 GR SDM', 2, 1, 'SALEP', 63000, 47800.00, 63000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(421, 'OBT0421', 'MEFENTAN 500 Kalbe', 2, 60, 'Kaplet', 500, 350.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(422, 'OBT0422', 'MEFINAL 500MG Sanbe', 2, 0, 'Tablet', 1900, 1800.00, 1900, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(423, 'OBT0423', 'MEFINAL  500 mg SANBE', 2, 140, 'Kaplet', 2200, 160.00, 2200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(424, 'OBT0424', 'MELOXICAM 15 15 MG Triman', 2, 0, 'Tablet', 1500, 1400.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(425, 'OBT0425', 'MELOXICAM 7.5 MG 7.5 MG OGB DEXA', 2, 100, 'Tablet', 1000, 700.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(426, 'OBT0426', 'MELOXICAM tablet 7,5mg hj 7,5mg HEXPHARM JAYA', 2, 50, 'Tablet', 1000, 48000.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(427, 'OBT0427', 'METFORMIN HCL 850mg 850MG BERNOFARM', 2, 100, 'Tablet', 600, 475.00, 600, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(428, 'OBT0428', 'METHYLPREDNISOLONE 4MG HEXPHARM JAYA', 2, 80, 'Tablet', 700, 45000.00, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(429, 'OBT0429', 'METRONIDAZOL 500MG Triman', 2, 101, 'Tablet', 500, 25675.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(430, 'OBT0430', 'MICONAZOLE  2 % 10gr Kimia Farma', 2, 28, 'Tube', 7000, 5000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(431, 'OBT0431', 'MICROLAX  tab   5mg PHAROS', 7, 13, 'Tablet', 1700, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(432, 'OBT0432', 'MICROLAX GEL  5ML PHAROS', 1, 3, 'GEL', 27000, 19700.00, 27000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(433, 'OBT0433', 'MINYAK KAYU PUTIH 30ml  30ml Eagle indo farma', 1, 0, 'Botol', 13000, 9459.50, 13000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(434, 'OBT0434', 'MINYAK KAYU PUTIH CAP LANG 120ml  120ml Eagle indo farma', 1, 1, 'Botol', 46000, 34414.00, 46000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(435, 'OBT0435', 'MINYAK KAYU PUTIH CAP LANG 60ml  60ml Eagle indo farma', 1, 4, 'Botol', 24000, 17837.00, 24000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(436, 'OBT0436', 'MIXALGIN  500 mg Erela', 2, 140, 'Kaplet', 1200, 54312.00, 1200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(437, 'OBT0437', 'MOLEXFLU 500 MG Molex Ayus', 7, 30, 'Kaplet', 1000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(438, 'OBT0438', 'MY BABY MINYAK TELON 60 ML 60ml PT TEMPO NATURAL', 1, 2, 'BOTOL', 21000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(439, 'OBT0439', 'MY BABY MINYAK TELON 90 ML 90ML PT TEMPO NATURAL', 1, 1, 'BOTOL', 26000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(440, 'OBT0440', 'MY BABY minyak telon plus 150ml  150ml Tempo scan', 1, 1, 'Botol', 40000, 29325.00, 40000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(441, 'OBT0441', 'MY WELL VIT C + ZN 500MG Ultra Sakti', 1, 0, 'Tablet', 1000, 90.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(442, 'OBT0442', 'MYCORAL 2 %  Kalbe', 2, 2, 'Tube', 22000, 17000.00, 22000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(443, 'OBT0443', 'MYWELL 1000MG Ultra Sakti', 1, 200, 'Tablet', 23000, 2000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(444, 'OBT0444', 'Mecobalamin 500Mg Mulia Farma Suci', 2, 205, 'Kapsul', 1200, 110.00, 1200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(445, 'OBT0445', 'Meloxicam 15MG HJ  15MG HEXPHARM JAYA', 2, 250, 'Tablet', 1500, 85000.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(446, 'OBT0446', 'Meloxicam 15mg  Erlimpex 15 mg ERLIMPEX', 2, 0, 'Tablet', 1500, 32307.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(447, 'OBT0447', 'Mersibion 500 INJ  500 Mersi', 2, 26, 'Botol', 15000, 27900.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(448, 'OBT0448', 'Mersibon inj 2mg Mersi', 2, 0, '1', 15000, 15000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(449, 'OBT0449', 'Metformin HCI 500 mg  500 mg Hexpharm Jaya', 2, 260, 'Tablet', 300, 245.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(450, 'OBT0450', 'Metformin Hcl 500mg Hexpharm Jaya', 2, 0, 'Tablet', 400, 55000.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(451, 'OBT0451', 'Methylpredmisolone  4 mg OGB DEXA', 2, 0, 'Tablet', 700, 30270.00, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(452, 'OBT0452', 'Micropore 1/2 inc 1/2 inc 3M', 6, 2, 'pcs', 17000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(453, 'OBT0453', 'Micropore 1inc  2,5 onemed', 1, 3, 'Pcs', 25000, 2000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(454, 'OBT0454', 'Minosep 150ml Minorock Mandiri', 6, 1, 'botol', 55000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(455, 'OBT0455', 'Minyak Kayu Putih 15 ML 15 ML PT EAGLE INDO PHARMA', 1, 8, 'BOTOL', 7000, 5180.00, 7000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(456, 'OBT0456', 'Minyak Tawon DD 30ml Tawn Jaya', 1, 2, 'Botol', 37000, 28233.00, 37000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(457, 'OBT0457', 'Minyak Tawon EE 60ml Tawon Jaya', 1, 2, 'botol', 60000, 1.00, 60000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(458, 'OBT0458', 'Minyak Tawon FF 90ml Tawon Jaya', 1, 2, 'Botol', 84000, 64650.00, 84000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(459, 'OBT0459', 'Molexflu 500mg Molex Ayus', 2, 0, 'Kaplet', 1000, 800.00, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(460, 'OBT0460', 'Mylanta Liquid 50 ML 50ml PT Integrated Healthcare', 1, 3, 'Botol', 19000, 13746.00, 19000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(461, 'OBT0461', 'NACI /  INFUS 500ml 500 ml Otsuka', 2, 21, 'Botol', 19000, 5913.00, 19000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(462, 'OBT0462', 'NACI / INFUS 100ML 100ml Otsu-NS', 6, 3, 'Kolf', 15000, 1.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(463, 'OBT0463', 'NEO RHEUMACYL tablet 500MG Tempo scan', 7, 290, 'Tablet', 550, 150.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(464, 'OBT0464', 'NEURALGIN RX 500 Kalbe', 2, 200, 'Kaplet', 1300, 100.00, 1300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(465, 'OBT0465', 'NEUROBION  200mcg PT. MERCK', 1, 60, 'Tablet', 3200, 2200.00, 3200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(466, 'OBT0466', 'NEUROBION FORTE 5000mcg PT. MERCK', 1, 50, 'Tablet', 5200, 389.59, 5200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(467, 'OBT0467', 'NEURODEX  100 mg Dexa Medica', 1, 340, 'Tablet', 800, 550.00, 800, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(468, 'OBT0468', 'NEXITRA (ASAM TRANEKSAMAT) 500MG IFARS', 2, 100, 'Kaplet', 2700, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(469, 'OBT0469', 'NGT NO 16 - Trumo', 6, 2, 'Pcs', 23000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(470, 'OBT0470', 'NORIT TABLET  325mg Eagle indo farma', 9, 80, 'Tablet', 500, 145.05, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(471, 'OBT0471', 'NOVADIUM 2 Novapharin', 2, 70, 'Tablet', 500, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(472, 'OBT0472', 'NOVAMOX 500MG 500MG  Novapharin', 2, 0, 'KAPLET', 1300, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(473, 'OBT0473', 'NOVAMOX 500MG 500MG Novapharin', 2, 35, 'Kaplet', 1300, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(474, 'OBT0474', 'Nellco Special  PE 55ml Nellco', 6, 1, 'sirup', 28000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(475, 'OBT0475', 'Nellco Special  PE 100ml Nellco', 6, 2, 'sirup', 47000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(476, 'OBT0476', 'Neo Diaform 570mg Corsa', 1, 25, 'Tablet', 400, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(477, 'OBT0477', 'Neozep forte 500mg Darya Varia', 7, 0, 'Tablet', 750, 200.00, 750, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(478, 'OBT0478', 'Nephrolit  100 mg Kalbe', 7, 80, 'Kapsul', 1000, 500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(479, 'OBT0479', 'Nifedipin 10mg Dexa Medica', 6, 106, 'Tablet', 300, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(480, 'OBT0480', 'Nisagon 5g IFARS', 2, 6, 'Salep', 10000, 6869.00, 10000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(481, 'OBT0481', 'Novapyron 500mg Nova', 2, 60, 'Kaplet', 700, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(482, 'OBT0482', 'Novatrim 240mg novatrim', 2, 3, 'sirup', 7000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(483, 'OBT0483', 'OB HERBAL 60 ML SYR  60ml DELTO', 9, 2, 'Syrup', 20000, 14213.00, 20000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(484, 'OBT0484', 'OB HERBAL HABBATUSSAUDA SYR 60ml DELTO', 9, 2, 'Syrup', 20000, 14208.00, 20000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(485, 'OBT0485', 'OB HERBAL SACHET 15 ML DELTO', 11, 20, 'SACHET', 3000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(486, 'OBT0486', 'OBH COMBI ANAK 60 ML JERUK 60ML combiphar', 7, 2, 'Syrup', 20000, 14836.00, 20000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(487, 'OBT0487', 'OBH COMBI ANAK STRAWBERRY 60 ML SYR 60ml combiphar', 7, 1, 'Syrup', 20000, 14836.00, 20000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(488, 'OBT0488', 'OBH COMBI BATUK & FLU SYR 100ML 100 ml combiphar', 7, 3, 'Botol', 25000, 18670.00, 25000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(489, 'OBT0489', 'OBH Combi Batuk + Flu  SYR 60 ML 60ml combiphar', 6, 5, 'sirup', 19000, 13700.00, 19000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(490, 'OBT0490', 'OBH Combi menthol Batuk berdahak SYR 100 ML 100Ml combiphar', 1, 4, 'Syrup', 19000, 18670.00, 19000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(491, 'OBT0491', 'OBH Itrasal 60ml Itrasal', 1, 7, 'Syrup', 12000, 5995.00, 12000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(492, 'OBT0492', 'OBH NELLCO BERDAHAK 100 ml 100 ml  Nellco', 1, 1, 'Botol', 20000, 10000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(493, 'OBT0493', 'OBH NELLCO BERDAHAK 55 ml 55 ml Nellco', 1, 3, 'Botol', 14000, 10000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(494, 'OBT0494', 'OBH Nellco Special Menthol 100 ml 100 ml Nellco', 7, 1, 'Botol', 32000, 24500.00, 32000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(495, 'OBT0495', 'OBH Nellco Special menthol 55 ml 55 ml Nellco', 7, 6, 'Botol', 28000, 21500.00, 28000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(496, 'OBT0496', 'OBH Surya Itrasal 100ml Itrasal', 6, 1, 'sirup', 10000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(497, 'OBT0497', 'OMEDOM SIRUP 5MG/ 5ML MUTIARA MUKTI FARMA', 2, 1, 'Botol', 10000, 7500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(498, 'OBT0498', 'OMEGDIAR  700mg Mutifa', 1, 1, 'Tablet', 400, 23811.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(499, 'OBT0499', 'OMEPRAZOL NOVAPHARIN 20mg Novapharin', 2, 570, 'Kapsul', 800, 26447.00, 800, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(500, 'OBT0500', 'OMEPRAZOLE Novell 20 mg 20 mg NOVELL', 2, 0, 'Kapsul', 1000, 90.92, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(501, 'OBT0501', 'ONDANSENTRON 4 4 MG MEGA LIFESCIENCES', 2, 70, 'TAB', 2000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(502, 'OBT0502', 'ORALIT scht 1  RAMA EMERALD  MULTI SUKSES', 1, 75, 'Sachet', 1000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(503, 'OBT0503', 'OXYCAN GREEN 500CC SAMATOR', 6, 4, 'KALENG', 50000, 36364.00, 50000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(504, 'OBT0504', 'Obat batuk Cap Ibu dan Anak 150ml Sentosa Karya Gemilang', 6, 1, 'sirup', 60000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(505, 'OBT0505', 'Omegdiar  700 mg MUTIARA MUKTI FARMA', 1, 75, 'Tablet', 400, 291.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(506, 'OBT0506', 'Omegesic 500 Mutifa', 2, 40, 'Tablet', 500, 400.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(507, 'OBT0507', 'Ondansentron HCl 4mg Mahakan Beta Farma', 6, 0, 'Tablet', 2000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(508, 'OBT0508', 'Opistan 500mg Otto', 6, 100, 'Kaplet', 700, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(509, 'OBT0509', 'Oviskin 5g Kalbe', 2, 1, 'Salep', 10000, 9000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(510, 'OBT0510', 'PANADOL Biru  500mg Glaxo wellcome indonesia', 1, 120, 'Kaplet', 1400, 600.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(511, 'OBT0511', 'PAPAVERINE HCI  40mg Novapharin', 2, 70, 'Tablet', 800, 700.00, 800, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(512, 'OBT0512', 'PARACETAMOL 500 MEF 500MG MEGA ESA FARMA', 1, 0, 'TABLET', 300, 1.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(513, 'OBT0513', 'PARACETAMOL DROPS 15ML IFARS', 2, 1, 'Botol', 18000, 12000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(514, 'OBT0514', 'PARACETAMOL MERSI 500 500mg mersi', 7, 140, 'Tablet', 300, 15180.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(515, 'OBT0515', 'PARATUSIN 500 MG Darya Varia', 1, 0, 'Tablet', 2000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(516, 'OBT0516', 'PARATUSIN  500 MG Darya Varia', 1, 120, 'Tablet', 2000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(517, 'OBT0517', 'PASABA COUGH AND FLU syr 60ml 60ml HUFA', 7, 6, 'Botol', 11000, 8174.00, 11000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(518, 'OBT0518', 'PI KANG SHUANG 5gr 5gr Sano gratia farma', 2, 4, 'Tube', 16000, 9974.50, 16000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(519, 'OBT0519', 'PIM-TRA-KOL kids STRAWBERRY new (03/09/25) 60ml Pharmaceutical', 2, 4, 'Botol', 20000, 11170.00, 20000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(520, 'OBT0520', 'PIMTRAKOL CHERRY 60 ML PIM PHARMACEUTICALS', 7, 6, 'Botol', 20000, 17800.00, 20000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(521, 'OBT0521', 'PIOGLITAZONE 30 OGB DEXA', 2, 30, 'Tablet', 3000, 2900.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(522, 'OBT0522', 'PIROXICAM 10 10 Novapharin', 2, 150, 'Tablet', 200, 100.00, 200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(523, 'OBT0523', 'PIROXICAM 20 20 MG Novapharin', 2, 100, 'Tablet', 300, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(524, 'OBT0524', 'PLABOTTLE 500ml Otsuka', 2, 40, 'Botol', 19000, 6912.50, 19000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(525, 'OBT0525', 'PLESTERIN ANTI AIR  1234 jayamas medika', 1, 99, 'PCS', 1000, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(526, 'OBT0526', 'PLESTERIN ANTI AIR PER 10 LEMBAR  123456 jayamas medika', 1, 40, 'PCS', 10000, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(527, 'OBT0527', 'PLESTERIN KIDS 1 PT JAYAMAS', 1, 74, 'PCS', 1000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(528, 'OBT0528', 'POLIDENT DENTURE GR 20GR Haleon', 6, 2, 'Tube', 40000, 29800.00, 40000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(529, 'OBT0529', 'PRAXION JERUK 60ML PHAROS', 7, 10, 'Syrup', 30000, 23000.00, 30000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(530, 'OBT0530', 'PREDNISONE TAB 5MG Triman', 2, 50, 'Tablet', 300, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(531, 'OBT0531', 'PRIMADEX FORTE  960mg Dexa Medica', 2, 200, 'Kaplet', 1200, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(532, 'OBT0532', 'PRIMOLUT N  5 mg Bayer', 2, 120, 'Tablet', 12000, 166469.00, 12000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(533, 'OBT0533', 'PROVE D3 500MG KALBE', 7, 10, 'Tablet', 5600, 1275.00, 5600, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(534, 'OBT0534', 'Panadol 500mg Gsk', 1, 20, 'Tablet', 1400, 1300.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(535, 'OBT0535', 'Panadol Extra 565mg Haleon', 1, 40, 'Tablet', 1400, 100.00, 1950, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(536, 'OBT0536', 'Panadol biru (JAN / 2027) 500mg Glaxo wellcome indonesia', 1, 0, 'Kaplet', 1400, 600.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(537, 'OBT0537', 'Pansdol Extra 500mg Combiphar', 6, 50, 'Tablet', 15000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(538, 'OBT0538', 'Paracetamol SYR 60ml Imfarmind Farmasi Industri', 1, 5, 'Syrup', 5000, 2760.00, 5000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(539, 'OBT0539', 'Pasaba Syrup 50mg/5ml Gratia Husada Farma', 7, 3, 'Syrup', 8000, 5423.00, 8000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(540, 'OBT0540', 'Peditox - Combiphar', 6, 3, 'Botol', 12000, 8938.00, 12000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(541, 'OBT0541', 'Peka 2g Seino', 6, 11, 'Tube', 5000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(542, 'OBT0542', 'Phenacold 60ml Sanbe', 6, 1, 'sirup', 13000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(543, 'OBT0543', 'Pimtrakol lemon sirup 60 ml  PIM PHARMACEUTICALS', 7, 6, 'Botol', 20000, 15400.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(544, 'OBT0544', 'Piroxicam 20 20 Imfarmind Farmasi Industri', 2, 0, 'Tablet', 300, 11900.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(545, 'OBT0545', 'Plesterin Kids - Jayamas', 6, 25, 'Pcs', 1000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(546, 'OBT0546', 'Polisilene 100ml Pharos', 6, 1, 'sirup', 28000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(547, 'OBT0547', 'Polysilane SYR 100ml Pharos', 1, 3, 'Suspensi', 30000, 29000.00, 30000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(548, 'OBT0548', 'Polysilane TAB ISI 8 TABLET 200mg Pharos', 1, 72, 'Tablet', 1500, 380.00, 1500, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(549, 'OBT0549', 'Povidon Iodin 15ml Onemed', 6, 1, 'botol', 10000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(550, 'OBT0550', 'Primadex Forte 960mg Dexa Medica', 6, 0, 'Kaplet', 1200, 50000.00, 1200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(551, 'OBT0551', 'Primolut N 5mg Bayer', 2, 0, 'Tablet', 13300, 5322.47, 13300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(552, 'OBT0552', 'Proris 60ml Faratu', 6, 1, 'sirup', 31000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(553, 'OBT0553', 'Proris Forte 50ml Faratu', 6, 1, 'sirup', 37000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(554, 'OBT0554', 'Proris Supp 125mg  125mg Pharos', 2, 3, 'PCS', 15000, 7000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(555, 'OBT0555', 'RAMIPRIL 5 MG OGB DEXA', 2, 60, 'Tablet', 600, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(556, 'OBT0556', 'RANITIDINE hcl 150 mg ERELA  150 mg ERELA', 2, 520, 'Tablet', 400, 21602.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(557, 'OBT0557', 'RENOVIT GOLD TAB ISI 4 1575mg Konimex', 1, 60, 'Tablet', 3750, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(558, 'OBT0558', 'REPASS (ibuprofen) Tab 400mg ERELA', 1, 130, 'Tablet', 500, 346.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(559, 'OBT0559', 'RHEMAFAR (METHYLPREDNISOLON 4) 4 IFARS', 2, 380, 'Tablet', 1000, 47387.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(560, 'OBT0560', 'RIVANOL 100 ml Molex Ayus 100 ml MOLEX AYUS', 7, 10, 'Botol', 8000, 3000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(561, 'OBT0561', 'RL kolf 500ml Otsuka', 6, 8, 'Kolf', 17000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(562, 'OBT0562', 'ROHTO 7ml 7 ml Rohto laboratories indonesia', 7, 3, 'Botol', 15000, 10935.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(563, 'OBT0563', 'Ranitidine Hcl 150mg Hexpharm Jaya', 2, 0, 'Tablet', 400, 300.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(564, 'OBT0564', 'Ranitidine inj 2ml Generik', 2, 0, 'Ampul', 10000, 35000.00, 10000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(565, 'OBT0565', 'Ranitidinie HCI TABLET 150MG 150mg Hexpharm Jaya', 2, 0, 'Tablet', 400, 216.02, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(566, 'OBT0566', 'Redoxon  10 Bayer', 7, 1, 'Botol', 55000, 42000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(567, 'OBT0567', 'Relaxon (EPERISONE HCL) 50mg Erela', 2, 130, 'Tablet', 2000, 36300.00, 2000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(568, 'OBT0568', 'Repass 400mg Erela', 6, 0, 'Kaplet', 500, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(569, 'OBT0569', 'Rexavin 500 500mg IFARS', 6, 65, 'Kaplet', 2500, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(570, 'OBT0570', 'Roverton 15mg IFARS', 2, 5, 'sirup', 9000, 5810.00, 9000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(571, 'OBT0571', 'SAKATONIK ABC RASA TUTTI FRUTTI  100IU SAKA FARMA', 1, 1, 'Botol', 24000, 18000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(572, 'OBT0572', 'SAKATONIK ABC RASA jeruk 100 IU SAKA FARMA', 1, 1, 'Botol', 24000, 17307.00, 24000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(573, 'OBT0573', 'SALBUTAMOL 2mg 2MG Yarindo Farmatama', 2, 0, 'Tablet', 200, 18000.00, 200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(574, 'OBT0574', 'SALBUTAMOL 2mg 2MG Mulia Farma Suci', 2, 160, 'Tablet', 200, 130.00, 200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(575, 'OBT0575', 'SALBUTAMOL SULFATE 4mg 4MG Mulia Farma Suci', 2, 0, 'Tablet', 400, 200.00, 400, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(576, 'OBT0576', 'SALBUTAMOL SULFATE SYR 2MG/5ML Imfarmind Farmasi Industri', 2, 2, 'Botol', 9000, 6500.00, 9000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(577, 'OBT0577', 'SANADRYL DMP sirup 60ml 60ml Sanbe', 7, 6, 'Botol', 28000, 10000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(578, 'OBT0578', 'SANADRYL EXP sirup 60ml 60ml Sanbe', 7, 3, 'Botol', 17000, 12750.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(579, 'OBT0579', 'SANADRYL expectorant SYR 120ml  120ML SANBE', 7, 1, 'Botol', 24000, 12750.00, 24000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(580, 'OBT0580', 'SANGOBION KAP isi 4 tab  259mg PT. MERCK', 1, 80, 'Kapsul', 2500, 750.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(581, 'OBT0581', 'SANMOL SYR 60ML 60ml SANBE', 1, 8, 'Syrup', 23000, 15480.00, 23000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(582, 'OBT0582', 'SCABIMITE Krim 5% 30gr ED 08/28 30gr Galenium', 2, 0, 'Tube', 100000, 74000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(583, 'OBT0583', 'SCOPMA PLUS 510MG IFARS', 2, 66, 'Tablet', 2000, 132612.00, 2000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(584, 'OBT0584', 'SELANG OXYGEN DEWASA / ANAK 135546 Onemed', 1, 6, 'PCS', 15000, 11500.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(585, 'OBT0585', 'SILADEX DMP 60ml Konimex', 7, 0, 'Syrup', 17000, 12200.00, 17000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(586, 'OBT0586', 'SILADEX MUCOLYTIC 60ML Konimex', 2, 5, 'Syrup', 17000, 12450.00, 17000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(587, 'OBT0587', 'SILADEX cough and Cold 60ml Konimex', 7, 6, 'Botol', 16000, 12200.00, 16000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(588, 'OBT0588', 'SIMVASTATIN 20 MG 20 Bernofarm', 2, 0, 'Tablet', 1200, 34272.00, 1200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(589, 'OBT0589', 'SIMVASTATIN 20 MG 20 MG BERNOFARM', 2, 70, 'Tablet', 1200, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(590, 'OBT0590', 'SPIRONOLACTONE 25mg OGB DEXA', 2, 0, 'Tablet', 500, 200.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(591, 'OBT0591', 'STARFOLAT 400 Dexa Medica', 2, 0, 'Tablet', 400, 300.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(592, 'OBT0592', 'SUCRALFATE SUSPENSI 500MG/5ML Dexa Medica', 2, 15, 'Botol', 25000, 20000.00, 25000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(593, 'OBT0593', 'Salbutamol 2 MG 2 Graha Farma', 2, 0, 'Tablet', 200, 190.00, 200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(594, 'OBT0594', 'Salonpas GEL 15G 15gr Hisamitsu', 1, 2, 'Tube', 15000, 10000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(595, 'OBT0595', 'Salonpas HOT pcs 10 Hisamitsu', 1, 11, 'PCS', 8000, 4000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(596, 'OBT0596', 'Salonpas Hijau PCS - Hisamitsu', 6, 10, 'Pcs', 8000, 7506.40, 8000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(597, 'OBT0597', 'Salonpat Hot PCS - Hisamitsu', 6, 12, 'Pcs', 8000, 1.00, 8000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(598, 'OBT0598', 'Sangobion 500MG PT. MERCK', 7, 0, 'Kapsul', 2200, 210.00, 2200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(599, 'OBT0599', 'Sangobion KIDS Syr 100ml PT Merck Tbk', 7, 0, 'Botol', 55000, 44500.00, 55000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(600, 'OBT0600', 'Sanmol Drop 15ml Sanbe', 6, 2, 'Drop', 25000, 1.00, 25000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(601, 'OBT0601', 'Sanmol Forte  60ml Sanbe', 1, 1, 'Syrup', 41000, 15857.00, 41000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(602, 'OBT0602', 'Sanmol TABLET 500mg Sanbe', 1, 184, 'Tablet', 750, 44.33, 750, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(603, 'OBT0603', 'Scabimite 10gr  10gr Galenium', 2, 3, 'Tube', 60000, 39478.00, 60000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(604, 'OBT0604', 'Scpma plus 500 IFARS', 2, 6, 'Tablet', 2000, 1900.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(605, 'OBT0605', 'Siladex Cough & cold 100 ml Konimex', 7, 1, 'Botol', 23000, 16800.00, 23000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(606, 'OBT0606', 'Silex 41,67 mg Darya Varia', 6, 1, 'sirup', 84000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(607, 'OBT0607', 'Simvastatin 10MG 10 Hexpharm Jaya', 2, 170, 'Tablet', 600, 38000.00, 600, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(608, 'OBT0608', 'Spasminal 500MG  500mg Hexpharm Jaya', 2, 174, 'Kaplet', 1200, 100.00, 1200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(609, 'OBT0609', 'Spironolactone 25 OGB DEXA', 2, 60, 'Tablet', 500, 27346.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(610, 'OBT0610', 'Steril Lattex size 7 - Maxter', 6, 3, 'Pcs', 7000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(611, 'OBT0611', 'Sterilised Water 25ml Otsu - WI', 6, 2, 'Kolf', 10000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(612, 'OBT0612', 'TAXIME 100 mg  100 MG Hexpharm Jaya', 2, 20, 'Kapsul', 3000, 3000.00, 3000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(613, 'OBT0613', 'TEMPRA Drops 15 ml Taisho', 1, 1, 'Botol', 58000, 40189.00, 58000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(614, 'OBT0614', 'TEMPRA SYR 60ML Taisho', 1, 0, 'Syrup', 54000, 36208.00, 54000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(615, 'OBT0615', 'TEMPRA syr 60 ml 60 ml Taisho', 2, 2, 'Botol', 54000, 38600.00, 54000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(616, 'OBT0616', 'TEOSAL 130 Mahakan Beta Farma', 2, 0, 'Tablet', 300, 200.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(617, 'OBT0617', 'TERA-F 500mg RAMA', 2, 275, 'Tablet', 700, 110000.00, 700, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(618, 'OBT0618', 'THIAMPHENICOL 500 MG 500MG BERNOFARM', 2, 100, 'Kapsul', 2100, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(619, 'OBT0619', 'THROMBOGEL 10 g 10 g Tunggal Idaman Abdi', 7, 1, 'Tube', 78000, 40000.00, 78000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(620, 'OBT0620', 'TOLAK LINU CAIR 15ml SIDOMUNCUL', 1, 10, 'SCT', 4000, 2500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(621, 'OBT0621', 'TOLAKANGIN MADU  15ml SIDOMUNCUL', 1, 10, 'PCS', 5000, 3990.00, 5000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(622, 'OBT0622', 'TONIKUM BAYER 100ML Bayer', 1, 2, 'Syrup', 20000, 19000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(623, 'OBT0623', 'TRANEXAMIC ACID 500 First Medipharma', 2, 100, 'Tablet', 17000, 12000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(624, 'OBT0624', 'TRANSPULMIN 10 GR 10 GR MENARINI', 1, 1, 'TUBE', 62000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(625, 'OBT0625', 'TRICLOFEM INJK 3BULAN 150mg Tunggal Idaman Abdi', 2, 21, 'Ampul', 13000, 195000.00, 13000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(626, 'OBT0626', 'Tegaderem 2 - 3M', 6, 1, 'Pcs', 18000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(627, 'OBT0627', 'Tegaderm Filem 4 - 3M', 6, 1, 'Pcs', 31000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(628, 'OBT0628', 'Tempra Forte 60ml Taisho', 7, 2, 'Syrup', 60000, 42600.00, 60000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(629, 'OBT0629', 'Teosal Tablet 1mg Dexa Medica', 2, 90, 'Tablet', 300, 19000.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(630, 'OBT0630', 'Termometer  Alpha 1 1234 ThermoONE', 10, 2, 'Pcs', 35000, 25000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(631, 'OBT0631', 'Termometer Alpha - -', 6, 1, 'Pcs', 45000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(632, 'OBT0632', 'Termometer Alpha 3 1234 onemed', 10, 1, 'Pcs', 40000, 30000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(633, 'OBT0633', 'Termorex 60ml Konimex', 1, 1, 'Syrup', 17000, 16000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(634, 'OBT0634', 'Test Pack Onemed - onemed', 6, 50, 'pcs', 5000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(635, 'OBT0635', 'Test Pack Sensitif - Danpac Pharma', 6, 0, 'pcs', 30000, 1.00, 25000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(636, 'OBT0636', 'Test Pack akurat - Danpac Pharma', 6, 4, 'pcs', 13000, 9500.00, 13000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(637, 'OBT0637', 'Thermo One Thermometer Alpha 3 123 ThermoONE', 1, 0, 'Pcs', 40000, 25000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(638, 'OBT0638', 'ThermoONE Alpha 1  234566 ThermoONE', 1, 1, 'Pcs', 35000, 19000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(639, 'OBT0639', 'URESIX (FUROSEMIDE 40 MG) 40 MG SANBE', 2, 50, 'Tablet', 1400, 100000.00, 1400, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(640, 'OBT0640', 'Urine Bag 2000ML One omed', 1, 2, 'Pcs', 15000, 13000.00, 1, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(641, 'OBT0641', 'Urine bag - Mepro', 6, 3, 'Pcs', 8000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(642, 'OBT0642', 'VENTOLIN NEBULES  2,5mg Gsk', 2, 18, 'Ampul', 25000, 10000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(643, 'OBT0643', 'VERMINT 12 KAPS 30 Vermindo Internasional', 2, 0, 'Botol', 32000, 23000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(644, 'OBT0644', 'VESPERUM DROPS SYR 5MG IFARS', 2, 1, 'Botol', 30000, 30000.00, 25000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(645, 'OBT0645', 'VESPERUM SIRUP  5mg IFARS', 2, 4, 'Botol', 15000, 11000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(646, 'OBT0646', 'VICKS 44 ANAK STRAWBERRY 27ML Darya Varia', 7, 2, 'Syrup', 15000, 10895.00, 15000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(647, 'OBT0647', 'VICKS 44 ANAK STRAWBERRY 54ml 54ML Darya Varia', 7, 3, 'Syrup', 26000, 20315.00, 26000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(648, 'OBT0648', 'VICKS FORMULA 44 27ml 27ml Darya Varia', 7, 6, 'Botol', 13000, 9816.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(649, 'OBT0649', 'VICKS FORMULA 44 54ml 54ML Darya Varia', 7, 6, 'Syrup', 24000, 23000.00, 24000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(650, 'OBT0650', 'VICKS INHALER KEY CHAIN  0,5 ml Darya Varia', 1, 3, 'Tabung', 22000, 16858.00, 22000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(651, 'OBT0651', 'VICKS VapoRub  10g Vicks', 6, 5, 'Pcs', 10000, 7632.00, 10000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(652, 'OBT0652', 'VIT B COMP IPI 500MG Supra ferbindo farma', 7, 0, 'Tablet', 7000, 4955.00, 7000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(653, 'OBT0653', 'VIT C 250mg Novapharm', 1, 65, 'Tablet', 600, 500.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(654, 'OBT0654', 'VITAL 10ML Medikon', 1, 1, 'Botol', 21000, 16000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(655, 'OBT0655', 'VITAMIN A IPI Botol 45tab 2000 iu Supra ferbindo farma', 1, 3, 'Botol', 7000, 3000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(656, 'OBT0656', 'VITAMIN B COMPLEX  20MG Novapharin', 1, 0, 'Tablet', 300, 117.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34');
INSERT INTO `obat` (`id`, `kode`, `nama_obat`, `kategori_id`, `stok`, `satuan`, `harga_umum`, `harga_barang`, `harga_jual_bpjs`, `deskripsi`, `gambar`, `is_active`, `is_approved`, `created_at`, `updated_at`) VALUES
(657, 'OBT0657', 'VITAMIN B COMPLEX + B12 Kaplet 10 SAMCO', 1, 75, 'Kaplet', 900, 180.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(658, 'OBT0658', 'VITAMIN B KOMPLEKS  10mg Sejahtera lestari farma', 7, 70, 'Kaplet', 300, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(659, 'OBT0659', 'VITAMIN B KOMPLEX IPI/ TABLET 40 Supra ferbindo farma', 1, 110, 'Tablet', 225, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(660, 'OBT0660', 'VITAMIN B.COMP 2MG AFI FARMA', 7, 85, 'Tablet', 400, 10296.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(661, 'OBT0661', 'VITAMIN C 50MG Balatif', 1, 355, 'Tablet', 300, 153.00, 300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(662, 'OBT0662', 'VITAMIN C  250 MG Novapharin', 6, 25, 'Kapsul', 600, 400.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(663, 'OBT0663', 'VITAMIN C 50MG  50MG Balatif', 1, 170, 'Tablet', 300, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(664, 'OBT0664', 'VITAMIN C IPI Jeruk botol 80tab 50mg Supra ferbindo farma', 1, 95, 'Tablet', 100, 10.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(665, 'OBT0665', 'VITAZYM plus  4500 HEXPHARM JAYA', 1, 250, 'Tablet', 1400, 94286.00, 1400, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(666, 'OBT0666', 'VOLTADEX 50 Dexa Medica', 2, 0, 'Tablet', 800, 700.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(667, 'OBT0667', 'VOLTADEX 50 50 MG Dexa Medica', 2, 300, 'TABLET', 800, 1.00, 800, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(668, 'OBT0668', 'Vicks FORMULA 44 Dws 100 ml Darya Varia', 7, 2, 'Botol', 36000, 26401.00, 36000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(669, 'OBT0669', 'Vitacimin Fresh LEMON  500 mg Takeda Indonesia', 7, 94, 'Tablet', 1500, 710.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(670, 'OBT0670', 'Vitamin B COMPLEX IPI botol 45tab 45 Supra ferbindo farma', 1, 11, 'Botol', 7000, 3000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(671, 'OBT0671', 'Vitamin C 25mg PIM Pharmaceuticals 25mg PIM PHARMACEUTICALS', 1, 1, 'Botol', 5000, 2000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(672, 'OBT0672', 'Vitamin C IPI botol 45 tab Supra ferbindo farma', 1, 10, 'Botol', 7000, 2000.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(673, 'OBT0673', 'Vitamin C Rasa Jeruk Btl Pt. PIM  25mg PIM PHARMACEUTICALS', 1, 95, 'Tablet', 70, 10.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(674, 'OBT0674', 'Vitamin D3 1000 IU IPI BOTOL 1000 IU Supra ferbindo farma', 1, 11, 'Botol', 8000, 5000.00, 8000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(675, 'OBT0675', 'Vitamin D3 1000 IU IPI BOTOL 1000 Supra ferbindo farma', 7, 20, 'Tablet', 300, 100.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(676, 'OBT0676', 'Vitamin E 100 IU 100 IU PyfaHealth', 6, 30, 'Kapsul', 1000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(677, 'OBT0677', 'Vitamin IPI VITAMIN D3+K2 400 IU FEBRINDO', 1, 7, 'Botol', 10000, 7927.00, 10000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(678, 'OBT0678', 'Vitazym Plus 4500 U Hexpharm Jaya', 1, 0, 'Tabet', 1400, 1.00, 1300, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(679, 'OBT0679', 'WINATIN (LORATADINE 10) 10 IFARS', 2, 0, 'Tablet', 1000, 900.00, 1000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(680, 'OBT0680', 'WOODS sirup Kuning 60ml 60ml Kalbe', 7, 2, 'Botol', 29000, 19000.00, 29000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(681, 'OBT0681', 'Wing Needle - Onemed', 6, 5, 'Pcs', 5000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(682, 'OBT0682', 'YUSIMOX FORTE 250mg  60ml IFARS', 2, 6, 'Botol', 20000, 10333.00, 20000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(683, 'OBT0683', 'YUSIMOX SYR 125mg Ifars', 2, 8, 'Botol', 16000, 5900.00, 16000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(684, 'OBT0684', 'Yusimox Forte IFARS 250mg IFARS', 2, 0, 'Botol', 20000, 10333.33, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(685, 'OBT0685', 'ZINC SULFATE 20 HOLI PHARMA', 1, 70, 'Tablet', 500, 400.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(686, 'OBT0686', 'Zinc 20mg Nova', 6, 50, 'tablet', 500, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(687, 'OBT0687', 'atorvastatin 20mg 20mg Dexa Medica', 2, 110, 'tablet', 5000, 400000.00, 5000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(688, 'OBT0688', 'calcium lactate 500mg mersi', 6, 150, 'Tablet', 200, 121.62, 200, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(689, 'OBT0689', 'cataflam 50mg Novartis', 6, 109, 'Tablet', 8800, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(690, 'OBT0690', 'cefadroxil monohydrate 125MG/ 60ML 125mg IFARS', 2, 3, 'sirup', 18000, 1.00, 18000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(691, 'OBT0691', 'cefixime trihidrate syr 100mg Bernofarm', 2, 3, 'sirup', 1900, 225000.00, 1900, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(692, 'OBT0692', 'chlorphenamine maleate 4mg Novapharm', 6, 0, 'Tablet', 100, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(693, 'OBT0693', 'farizol syr 125mg IFARS', 2, 1, 'sirup', 13000, 1.00, 13000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(694, 'OBT0694', 'farsifen Plus 600mg IFARS', 6, 0, 'Kaplet', 1000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(695, 'OBT0695', 'infalgin 500mg Graha Farma', 6, 0, 'Kaplet', 500, 1.00, 500, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(696, 'OBT0696', 'lostacef 250 250mg IFARS', 2, 0, 'sirup', 28000, 1.00, 0, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34'),
(697, 'OBT0697', 'vermint 250mg Vermindo Internasional', 6, 2, 'Kapsul', 60000, 1.00, 60000, NULL, NULL, 1, 1, '2026-05-21 13:28:37', '2026-05-23 08:01:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `no_order` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `status` enum('pending','dikonfirmasi','diproses','dikirim','selesai','dibatalkan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total_harga` int NOT NULL DEFAULT '0',
  `alamat_pengiriman` text COLLATE utf8mb4_unicode_ci,
  `metode_pembayaran` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `no_order`, `user_id`, `status`, `total_harga`, `alamat_pengiriman`, `metode_pembayaran`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 'ORD-202605190001', 2, 'selesai', 12000, NULL, 'qris', 'Penjualan Manual', '2026-05-18 21:02:12', '2026-05-18 21:02:12'),
(2, 'ORD-202605190002', 2, 'selesai', 15000, NULL, 'qris', 'Penjualan Manual', '2026-05-19 05:47:00', '2026-05-19 05:47:00'),
(3, 'ORD-202605210001', 2, 'selesai', 14500, NULL, 'tunai', 'Penjualan Manual', '2026-05-21 16:07:39', '2026-05-21 16:07:39'),
(4, 'ORD-202605230001', 2, 'selesai', 81500, NULL, 'tunai', 'Penjualan Manual', '2026-05-23 11:48:55', '2026-05-23 11:48:55'),
(5, 'ORD-202605280001', 2, 'selesai', 28000, NULL, 'transfer_bank', 'Penjualan Manual', '2026-05-28 08:05:12', '2026-05-28 08:05:12'),
(6, 'ORD-202606010001', 2, 'selesai', 69000, NULL, 'qris', 'Penjualan Manual', '2026-05-31 17:23:16', '2026-05-31 17:23:16'),
(7, 'ORD-202606010002', 2, 'selesai', 91000, NULL, 'tunai', 'Penjualan Manual', '2026-06-01 03:57:24', '2026-06-01 03:57:24'),
(8, 'ORD-202606290001', 2, 'selesai', 36000, NULL, 'qris', 'Penjualan Manual', '2026-06-29 14:55:48', '2026-06-29 14:55:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `obat_id` bigint UNSIGNED NOT NULL,
  `jumlah` int NOT NULL DEFAULT '1',
  `harga_satuan` int NOT NULL DEFAULT '0',
  `subtotal` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `obat_id`, `jumlah`, `harga_satuan`, `subtotal`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, 12000, 12000, '2026-05-18 21:02:12', '2026-05-18 21:02:12'),
(2, 2, 2, 1, 12000, 12000, '2026-05-19 05:47:00', '2026-05-19 05:47:00'),
(3, 2, 5, 1, 3000, 3000, '2026-05-19 05:47:00', '2026-05-19 05:47:00'),
(4, 3, 14, 3, 1500, 4500, '2026-05-21 16:07:39', '2026-05-21 16:07:39'),
(5, 3, 54, 1, 10000, 10000, '2026-05-21 16:07:39', '2026-05-21 16:07:39'),
(6, 4, 14, 1, 1500, 1500, '2026-05-23 11:48:55', '2026-05-23 11:48:55'),
(7, 4, 54, 1, 10000, 10000, '2026-05-23 11:48:55', '2026-05-23 11:48:55'),
(8, 4, 55, 1, 70000, 70000, '2026-05-23 11:48:55', '2026-05-23 11:48:55'),
(9, 5, 2, 1, 12000, 12000, '2026-05-28 08:05:12', '2026-05-28 08:05:12'),
(10, 5, 99, 2, 8000, 16000, '2026-05-28 08:05:12', '2026-05-28 08:05:12'),
(11, 6, 15, 5, 1000, 5000, '2026-05-31 17:23:16', '2026-05-31 17:23:16'),
(12, 6, 16, 3, 8000, 24000, '2026-05-31 17:23:16', '2026-05-31 17:23:16'),
(13, 6, 54, 4, 10000, 40000, '2026-05-31 17:23:16', '2026-05-31 17:23:16'),
(14, 7, 15, 3, 1000, 3000, '2026-06-01 03:57:24', '2026-06-01 03:57:24'),
(15, 7, 21, 2, 44000, 88000, '2026-06-01 03:57:24', '2026-06-01 03:57:24'),
(16, 8, 2, 3, 12000, 36000, '2026-06-29 14:55:48', '2026-06-29 14:55:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `stock_additions`
--

CREATE TABLE `stock_additions` (
  `id` bigint UNSIGNED NOT NULL,
  `obat_id` bigint UNSIGNED NOT NULL,
  `jumlah` int NOT NULL,
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `stock_additions`
--

INSERT INTO `stock_additions` (`id`, `obat_id`, `jumlah`, `catatan`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 21, 'pembelian secara offline', 'approved', '2026-05-21 14:11:51', '2026-05-21 15:17:50'),
(2, 34, 430, 'dari supplier', 'pending', '2026-05-25 13:13:41', '2026-05-25 13:13:41'),
(3, 34, 430, 'dari supplier', 'pending', '2026-05-25 13:13:43', '2026-05-25 13:13:43'),
(4, 34, 432, 'dari supplier', 'pending', '2026-05-25 13:13:58', '2026-05-25 13:13:58'),
(5, 45, 150, 'pembelian secara offline', 'approved', '2026-05-28 07:58:25', '2026-06-29 15:00:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','admin','pemilik') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pemilik',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `phone`, `address`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'superadmin@apotek.com', NULL, '$2y$12$C0JwpvJ9a/cgV0stDHcXi.FFtbT4blopG7tQq0kyDphBfrO3LEFAC', 'super_admin', '081234567890', 'Jl. Admin No. 1', NULL, '2026-05-18 19:51:14', '2026-05-18 19:51:14'),
(2, 'Admin Sulthan', 'admin@apotek.com', NULL, '$2y$12$OPfw78oQKcvTmzIMJNuAJeNQgQ5vP4m4cYJW7CZq3.7/IHtyy5M1e', 'admin', '081234567891', 'Jl. Admin No. 2', NULL, '2026-05-18 19:51:15', '2026-05-21 12:34:55'),
(3, 'Dr. Rizal', 'pemilik@apotek.com', NULL, '$2y$12$QX9ZEGUaPmKSlVGysRNpdOZJh1u3T61dRT483bKpqKBaCX5302.Fi', 'pemilik', '081234567892', 'Jl. Merdeka No. 10, Jakarta', NULL, '2026-05-18 19:51:15', '2026-05-23 08:18:33');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carts_user_id_foreign` (`user_id`),
  ADD KEY `carts_obat_id_foreign` (`obat_id`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kategori_slug_unique` (`slug`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `obat`
--
ALTER TABLE `obat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `obat_kode_unique` (`kode`),
  ADD KEY `obat_kategori_id_foreign` (`kategori_id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_no_order_unique` (`no_order`),
  ADD KEY `orders_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_foreign` (`order_id`),
  ADD KEY `order_items_obat_id_foreign` (`obat_id`);

--
-- Indeks untuk tabel `stock_additions`
--
ALTER TABLE `stock_additions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_additions_obat_id_foreign` (`obat_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `obat`
--
ALTER TABLE `obat`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=698;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `stock_additions`
--
ALTER TABLE `stock_additions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_obat_id_foreign` FOREIGN KEY (`obat_id`) REFERENCES `obat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `obat`
--
ALTER TABLE `obat`
  ADD CONSTRAINT `obat_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_obat_id_foreign` FOREIGN KEY (`obat_id`) REFERENCES `obat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `stock_additions`
--
ALTER TABLE `stock_additions`
  ADD CONSTRAINT `stock_additions_obat_id_foreign` FOREIGN KEY (`obat_id`) REFERENCES `obat` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
