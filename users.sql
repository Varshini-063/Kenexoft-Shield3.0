-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 08, 2026 at 07:36 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shield3`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) NOT NULL,
  `persona` enum('MSP','COMPANY','IT_CONSULTANT') NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE','PENDING') NOT NULL DEFAULT 'ACTIVE',
  `role` enum('SUBSCRIBER','SUPER_ADMIN') NOT NULL DEFAULT 'SUBSCRIBER',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `persona`, `first_name`, `last_name`, `email`, `mobile`, `password_hash`, `gstin`, `status`, `role`, `created_at`, `updated_at`) VALUES
(4, 'IT_CONSULTANT', 'varshini', 'k', 'radhika12@mailinator.com', '2546318972', '$2y$10$KdgD16jmLI/x62rUiF5fjOb.pW2YknWG5QY7f5Fph8WkbCTvIisfW', NULL, 'ACTIVE', 'SUBSCRIBER', '2026-06-26 19:58:37', '2026-06-26 19:58:37'),
(5, 'MSP', 'Krishna', NULL, 'krishna31@mailinator.com', '5789642135', '$2y$10$wd0rEx/zLqFfhhz.VpYnturNCwWO6LC5/7Rke.cr3OcQywNM78rQq', NULL, 'ACTIVE', 'SUBSCRIBER', '2026-06-26 20:28:00', '2026-06-26 20:28:00'),
(12, 'MSP', 'John Doe', NULL, 'johndoe23@mailinator.com', '5824697435', '$2y$10$6qg298YA5E58XJPldo860.BrO1Ljbri4esEaCytDEcLgstnPEddW6', NULL, 'ACTIVE', 'SUBSCRIBER', '2026-07-05 17:12:21', '2026-07-05 17:12:21'),
(13, 'COMPANY', 'Super', 'Admin', 'admin@shield.local', '+910000000000', '$2y$10$5YC.0b7FmyJRDl4.G/.7Auw.1qjW6RHJb2.8M0NGlpwH4gLC8Lw/O', NULL, 'ACTIVE', 'SUPER_ADMIN', '2026-07-08 22:37:54', '2026-07-08 22:45:14'),
(16, 'IT_CONSULTANT', 'varshini', 'k', 'janesmith12@mailinator.com', '5469872315', '$2y$10$xjqwgiwp1KXmf/VL.fcUkeVpa4LZORVYGxGvmGVZGF4w/7S5p39TW', NULL, 'ACTIVE', 'SUBSCRIBER', '2026-07-08 22:50:54', '2026-07-08 22:50:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_persona` (`persona`),
  ADD KEY `idx_users_status` (`status`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
