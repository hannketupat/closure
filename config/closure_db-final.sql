-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 21, 2025 at 07:38 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `closure_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_admin` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama_admin`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'Teknisi Utama');

-- --------------------------------------------------------

--
-- Table structure for table `closure`
--

CREATE TABLE `closure` (
  `id_closure` int NOT NULL,
  `kode_closure` varchar(20) NOT NULL,
  `nama_closure` varchar(100) NOT NULL,
  `jenis_kabel` enum('4 core','8 core','12 core','24 core') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `alamat_fisik` text NOT NULL,
  `koordinat` varchar(100) DEFAULT NULL,
  `jarak_tujuan` float DEFAULT NULL,
  `tanggal_input` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `closure`
--

INSERT INTO `closure` (`id_closure`, `kode_closure`, `nama_closure`, `jenis_kabel`, `alamat_fisik`, `koordinat`, `jarak_tujuan`, `tanggal_input`) VALUES
(11, 'CLS-901', 'Clousure 901 [TESTING]', '8 core', 'Jl. Padjajaran', '-6.441184864093675, 106.90470195376115', 1, '2025-10-18 13:56:50');

-- --------------------------------------------------------

--
-- Table structure for table `core_warna`
--

CREATE TABLE `core_warna` (
  `id_core` int NOT NULL,
  `id_closure` int NOT NULL,
  `warna_core` varchar(50) NOT NULL,
  `tujuan_core` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `core_warna`
--

INSERT INTO `core_warna` (`id_core`, `id_closure`, `warna_core`, `tujuan_core`) VALUES
(133, 11, 'Biru', 'ODP-1'),
(134, 11, 'Coklat', 'ODP-1.1'),
(135, 11, 'Hijau', 'ODP-2'),
(136, 11, 'Coklat', 'ODP-4'),
(137, 11, 'Abu-abu', 'ODP-CBN'),
(138, 11, 'Kuning', 'ODP-MY'),
(139, 11, 'Merah', 'ODP-10'),
(140, 11, 'Hitam', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`);

--
-- Indexes for table `closure`
--
ALTER TABLE `closure`
  ADD PRIMARY KEY (`id_closure`);

--
-- Indexes for table `core_warna`
--
ALTER TABLE `core_warna`
  ADD PRIMARY KEY (`id_core`),
  ADD KEY `id_closure` (`id_closure`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `closure`
--
ALTER TABLE `closure`
  MODIFY `id_closure` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `core_warna`
--
ALTER TABLE `core_warna`
  MODIFY `id_core` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `core_warna`
--
ALTER TABLE `core_warna`
  ADD CONSTRAINT `core_warna_ibfk_1` FOREIGN KEY (`id_closure`) REFERENCES `closure` (`id_closure`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
