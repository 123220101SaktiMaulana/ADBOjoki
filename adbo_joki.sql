-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2025 at 05:46 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `adbo_joki`
--

-- --------------------------------------------------------

--
-- Table structure for table `akun_game`
--

CREATE TABLE `akun_game` (
  `akunID` int(11) NOT NULL,
  `customerID` int(11) NOT NULL,
  `usernameGame` varchar(50) NOT NULL,
  `passwordGame` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `akun_game`
--

INSERT INTO `akun_game` (`akunID`, `customerID`, `usernameGame`, `passwordGame`) VALUES
(1, 3, 'EvosRRQSakti', '123123');

-- --------------------------------------------------------

--
-- Table structure for table `orderjoki`
--

CREATE TABLE `orderjoki` (
  `orderID` int(11) NOT NULL,
  `customerID` int(11) NOT NULL,
  `akunID` int(11) NOT NULL,
  `jokiID` int(11) DEFAULT NULL,
  `pembayaranID` int(11) DEFAULT NULL,
  `start_rank` varchar(50) DEFAULT NULL,
  `target_rank` varchar(50) DEFAULT NULL,
  `total_biaya` double NOT NULL,
  `status_order` enum('pending','diproses','selesai') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `orderjoki`
--

INSERT INTO `orderjoki` (`orderID`, `customerID`, `akunID`, `jokiID`, `pembayaranID`, `start_rank`, `target_rank`, `total_biaya`, `status_order`) VALUES
(1, 3, 1, 4, 10, 'Elite', 'Epic', 30, 'selesai'),
(2, 3, 1, 4, 11, 'Elite', 'Legend', 40, 'selesai');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `pembayaranID` int(11) NOT NULL,
  `jumlah` double NOT NULL,
  `metode_pembayaran` varchar(50) NOT NULL,
  `status` enum('menunggu','terverifikasi','gagal') DEFAULT 'menunggu',
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pembayaran`
--

INSERT INTO `pembayaran` (`pembayaranID`, `jumlah`, `metode_pembayaran`, `status`, `tanggal`) VALUES
(10, 30, 'pending', 'terverifikasi', '2025-06-12 15:42:23'),
(11, 40, 'pending', 'terverifikasi', '2025-06-12 15:43:36');

-- --------------------------------------------------------

--
-- Table structure for table `ulasan`
--

CREATE TABLE `ulasan` (
  `ulasanID` int(11) NOT NULL,
  `customerID` int(11) NOT NULL,
  `jokiID` int(11) NOT NULL,
  `orderID` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `komentar` text DEFAULT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `ulasan`
--

INSERT INTO `ulasan` (`ulasanID`, `customerID`, `jokiID`, `orderID`, `rating`, `komentar`, `tanggal`) VALUES
(1, 3, 4, 1, 5, 'gacor kang', '2025-06-12 15:43:24'),
(2, 3, 4, 2, 5, 'cakep bang', '2025-06-12 15:44:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','joki','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `username`, `email`, `password`, `role`) VALUES
(1, 'admin', '1@gmail.com', '$2y$10$/uFo3niycgHWSbWDkhGJJ.TYQEpXzEECJcBhPJlwpN5UA0DxSHTH6', 'admin'),
(3, 'customer', 'jaco@gmail.com', '$2y$10$0S.01MNzYiQ/SqMbR4.bCuM9dY2iVg07m0WgwCvf1NuV/0dKi/nTS', 'customer'),
(4, 'joki', 'jaco@gmail.com', '$2y$10$eFdpHnheB5KrTNcDOosL3ODry.DqOdKOMEOst5OHr6h1oHuqes1De', 'joki');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `akun_game`
--
ALTER TABLE `akun_game`
  ADD PRIMARY KEY (`akunID`),
  ADD KEY `customerID` (`customerID`);

--
-- Indexes for table `orderjoki`
--
ALTER TABLE `orderjoki`
  ADD PRIMARY KEY (`orderID`),
  ADD KEY `customerID` (`customerID`),
  ADD KEY `akunID` (`akunID`),
  ADD KEY `jokiID` (`jokiID`),
  ADD KEY `pembayaranID` (`pembayaranID`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`pembayaranID`);

--
-- Indexes for table `ulasan`
--
ALTER TABLE `ulasan`
  ADD PRIMARY KEY (`ulasanID`),
  ADD KEY `customerID` (`customerID`),
  ADD KEY `jokiID` (`jokiID`),
  ADD KEY `orderID` (`orderID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `akun_game`
--
ALTER TABLE `akun_game`
  MODIFY `akunID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orderjoki`
--
ALTER TABLE `orderjoki`
  MODIFY `orderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `pembayaranID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `ulasan`
--
ALTER TABLE `ulasan`
  MODIFY `ulasanID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `akun_game`
--
ALTER TABLE `akun_game`
  ADD CONSTRAINT `akun_game_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `users` (`userID`);

--
-- Constraints for table `orderjoki`
--
ALTER TABLE `orderjoki`
  ADD CONSTRAINT `orderjoki_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `users` (`userID`),
  ADD CONSTRAINT `orderjoki_ibfk_2` FOREIGN KEY (`akunID`) REFERENCES `akun_game` (`akunID`),
  ADD CONSTRAINT `orderjoki_ibfk_3` FOREIGN KEY (`jokiID`) REFERENCES `users` (`userID`),
  ADD CONSTRAINT `orderjoki_ibfk_4` FOREIGN KEY (`pembayaranID`) REFERENCES `pembayaran` (`pembayaranID`);

--
-- Constraints for table `ulasan`
--
ALTER TABLE `ulasan`
  ADD CONSTRAINT `ulasan_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `users` (`userID`),
  ADD CONSTRAINT `ulasan_ibfk_2` FOREIGN KEY (`jokiID`) REFERENCES `users` (`userID`),
  ADD CONSTRAINT `ulasan_ibfk_3` FOREIGN KEY (`orderID`) REFERENCES `orderjoki` (`orderID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
