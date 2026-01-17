-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 04, 2025 at 07:27 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `preset`
--

-- --------------------------------------------------------

--
-- Table structure for table `bahan_baku`
--

CREATE TABLE `bahan_baku` (
  `id` int NOT NULL,
  `kode_bahan` varchar(20) NOT NULL,
  `nama_bahan` varchar(100) NOT NULL,
  `satuan` varchar(20) NOT NULL COMMENT 'kg, liter, pcs, dll',
  `stok_saat_ini` decimal(10,2) DEFAULT '0.00',
  `level_restok` decimal(10,2) DEFAULT '0.00',
  `harga_beli` decimal(10,2) DEFAULT '0.00',
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bahan_baku`
--

INSERT INTO `bahan_baku` (`id`, `kode_bahan`, `nama_bahan`, `satuan`, `stok_saat_ini`, `level_restok`, `harga_beli`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 'BK001', 'Cokelat', 'kg', 1.00, 2.00, 75000.00, NULL, '2025-11-08 07:05:46', '2025-11-20 00:53:51'),
(2, 'BK002', 'Mentega', 'kg', 3.20, 1.50, 85000.00, NULL, '2025-11-08 07:05:46', '2025-11-08 07:05:46'),
(3, 'BK003', 'Telur', 'pcs', 10.00, 20.00, 2500.00, NULL, '2025-11-08 07:05:46', '2025-12-04 06:45:39'),
(4, 'BK004', 'Minyak Goreng', 'liter', 8.00, 3.00, 15000.00, NULL, '2025-11-08 07:05:46', '2025-11-08 07:05:46'),
(5, 'BK005', 'Krim', 'liter', 2.50, 1.00, 45000.00, NULL, '2025-11-08 07:05:46', '2025-11-08 07:05:46'),
(6, 'BK006', 'Gula', 'kg', 2.00, 5.00, 12000.00, NULL, '2025-11-08 07:05:46', '2025-12-04 06:44:05'),
(7, 'BK007', 'Tepung', 'kg', 15.00, 7.00, 10000.00, NULL, '2025-11-08 07:05:46', '2025-11-08 07:05:46'),
(8, 'BK008', 'Fondant', 'kg', 1.80, 0.50, 120000.00, NULL, '2025-11-08 07:05:46', '2025-11-08 07:05:46'),
(10, 'BK011', 'Tepung Tapioka', 'kg', 6.00, 2.00, 16000.00, 'Bahan Baku', '2025-11-19 02:53:17', '2025-11-19 02:53:17'),
(11, 'BK012', 'Baking Soda', 'gram', 10.00, 3.00, 11000.00, 'Bahan Baku', '2025-11-19 02:54:34', '2025-11-20 00:59:30'),
(12, 'BK014', 'Maizena', 'gram', 5.00, 4.00, 13000.00, 'Bahan Baku', '2025-11-19 02:55:27', '2025-12-04 06:45:10'),
(13, 'BK015', 'Pewarna Makanan', 'ml', 10.00, 2.00, 11000.00, 'Bahan Baku', '2025-11-19 04:00:00', '2025-11-19 04:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `kue`
--

CREATE TABLE `kue` (
  `id` int NOT NULL,
  `kode_kue` varchar(20) NOT NULL,
  `nama_kue` varchar(100) NOT NULL,
  `deskripsi` text,
  `foto` varchar(255) DEFAULT NULL,
  `harga_jual` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_stok`
--

CREATE TABLE `log_stok` (
  `id` int NOT NULL,
  `id_bahan` int NOT NULL,
  `jenis_transaksi` enum('masuk','keluar') NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `stok_sebelum` decimal(10,2) NOT NULL,
  `stok_sesudah` decimal(10,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `log_stok`
--

INSERT INTO `log_stok` (`id`, `id_bahan`, `jenis_transaksi`, `jumlah`, `stok_sebelum`, `stok_sesudah`, `keterangan`, `user_id`, `created_at`) VALUES
(1, 3, 'masuk', 30.00, 50.00, 80.00, 'Pesanan Banyak', 1, '2025-11-19 06:32:25'),
(4, 6, 'keluar', 8.00, 10.00, 2.00, 'Buat Kue', 1, '2025-12-04 06:44:05'),
(5, 12, 'keluar', 4.00, 9.00, 5.00, 'Kue', 1, '2025-12-04 06:45:10'),
(6, 3, 'keluar', 70.00, 80.00, 10.00, 'Kue', 1, '2025-12-04 06:45:39');

-- --------------------------------------------------------

--
-- Table structure for table `produksi_kue`
--

CREATE TABLE `produksi_kue` (
  `id` int NOT NULL,
  `kue_id` int NOT NULL,
  `qty_produksi` int NOT NULL,
  `total_biaya_bahan` decimal(12,2) DEFAULT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resep_kue`
--

CREATE TABLE `resep_kue` (
  `id` int NOT NULL,
  `kue_id` int NOT NULL,
  `bahan_id` int NOT NULL,
  `qty_per_pcs` decimal(10,2) NOT NULL,
  `satuan` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('owner','admin','staff') DEFAULT 'staff',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `created_at`) VALUES
(1, 'shofa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Shofa Owner', 'owner', '2025-11-08 07:05:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bahan_baku`
--
ALTER TABLE `bahan_baku`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_bahan` (`kode_bahan`);

--
-- Indexes for table `kue`
--
ALTER TABLE `kue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_kue` (`kode_kue`);

--
-- Indexes for table `log_stok`
--
ALTER TABLE `log_stok`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_bahan` (`id_bahan`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `produksi_kue`
--
ALTER TABLE `produksi_kue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_produksi_kue_kue` (`kue_id`),
  ADD KEY `fk_produksi_kue_user` (`user_id`);

--
-- Indexes for table `resep_kue`
--
ALTER TABLE `resep_kue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_resep_kue_kue` (`kue_id`),
  ADD KEY `fk_resep_kue_bahan` (`bahan_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bahan_baku`
--
ALTER TABLE `bahan_baku`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `kue`
--
ALTER TABLE `kue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_stok`
--
ALTER TABLE `log_stok`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `produksi_kue`
--
ALTER TABLE `produksi_kue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resep_kue`
--
ALTER TABLE `resep_kue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `log_stok`
--
ALTER TABLE `log_stok`
  ADD CONSTRAINT `log_stok_ibfk_1` FOREIGN KEY (`id_bahan`) REFERENCES `bahan_baku` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `log_stok_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `produksi_kue`
--
ALTER TABLE `produksi_kue`
  ADD CONSTRAINT `fk_produksi_kue_kue` FOREIGN KEY (`kue_id`) REFERENCES `kue` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_produksi_kue_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `resep_kue`
--
ALTER TABLE `resep_kue`
  ADD CONSTRAINT `fk_resep_kue_bahan` FOREIGN KEY (`bahan_id`) REFERENCES `bahan_baku` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_resep_kue_kue` FOREIGN KEY (`kue_id`) REFERENCES `kue` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
