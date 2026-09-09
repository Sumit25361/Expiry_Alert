-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2025 at 06:46 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `edr`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@expiryalert.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', 'active', '2025-07-17 15:06:25', '2025-06-13 08:00:09', '2025-07-17 15:06:25');

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_activity_log`
--

INSERT INTO `admin_activity_log` (`id`, `admin_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 08:00:46'),
(2, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 08:00:46'),
(3, 1, 'send_test_notification', 'Test notification sent to: s22_borkar_sumit@mgmcen.ac.in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 08:05:18'),
(4, 1, 'send_test_notification', 'Test notification sent to: s22_borkar_sumit@mgmcen.ac.in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 08:05:26'),
(5, 1, 'send_test_notification', 'Test notification sent to: s22_borkar_sumit@mgmcen.ac.in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 08:18:38'),
(6, 1, 'send_test_notification', 'Test notification sent to: s22_borkar_sumit@mgmcen.ac.in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 08:27:36'),
(7, 1, 'send_test_notification', 'Test notification sent to: s22_borkar_sumit@mgmcen.ac.in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 08:28:01'),
(8, 1, 'send_test_notification', 'Test notification sent to: s22_borkar_sumit@mgmcen.ac.in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 08:38:01'),
(9, 1, 'send_test_notification', 'Test notification sent to: s22_borkar_sumit@mgmcen.ac.in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 08:38:07'),
(10, 1, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 14:26:31'),
(11, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 14:26:31'),
(12, 1, 'send_test_notification', 'Test notification sent to: s22_borkar_sumit@mgmcen.ac.in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 14:27:25'),
(13, 1, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 06:59:52'),
(14, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 06:59:52'),
(15, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 07:44:10'),
(16, 1, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 08:12:14'),
(17, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 08:12:14'),
(18, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 08:13:14'),
(19, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 08:13:33'),
(20, 1, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 18:36:57'),
(21, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 18:36:57'),
(22, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 18:38:55'),
(23, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 18:45:43'),
(24, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 18:48:10'),
(25, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 18:48:16'),
(26, 1, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 15:06:25'),
(27, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 15:06:26'),
(28, 1, 'view_dashboard', 'Admin viewed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 15:07:10');

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_sessions`
--

INSERT INTO `admin_sessions` (`id`, `admin_id`, `session_token`, `ip_address`, `user_agent`, `expires_at`, `created_at`) VALUES
(1, 1, '840af9747055136081025c73b31d15ee83a5ec7ab7dc4088dfe35abcac437d2f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 12:30:46', '2025-06-13 08:00:46'),
(2, 1, 'ef194518137cb007ae8ee7ace8834a4157c26b57864fce5f7a5304c95f707227', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-13 18:56:31', '2025-06-13 14:26:31'),
(3, 1, 'b16349b72561a2ec11d71ff9f7258bb1b0f4e2840b12839e461e4c587ecda647', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 11:29:52', '2025-06-19 06:59:52'),
(4, 1, '9a18fa367b576b1f5a88fdd4f069022bcb3cc2b514e80da243f12edc83218bb6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 12:42:14', '2025-06-19 08:12:14'),
(5, 1, '6db7fdb0a183e910c9a4c2dae85e362fb5d92085de763d38c7bc98a0ef2db5c3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 23:06:57', '2025-06-19 18:36:57'),
(6, 1, 'ca165d6459d637f71a2eeeb69d67996908fdf4e35eb3a1fcd9b0062b34c787ff', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 19:36:25', '2025-07-17 15:06:25');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `book_name` varchar(200) NOT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `email`, `book_name`, `mfg_date`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, 'test@example.com', 'Library Book', NULL, '2025-07-13', '2025-06-13 06:26:30', '2025-06-13 06:26:30'),
(2, 's22_borkar_sumit@mgmcen.ac.in', 'RDsharma', '2025-06-13', '2025-07-12', '2025-06-13 14:17:33', '2025-06-13 14:17:33'),
(3, 's22_borkar_sumit@mgmcen.ac.in', 'hamlet', '2025-07-02', '2025-08-02', '2025-07-17 15:03:46', '2025-07-17 15:03:46'),
(4, 's22_borkar_sumit@mgmcen.ac.in', 'dracula', '2025-06-30', '2025-08-09', '2025-07-17 15:04:12', '2025-07-17 15:04:12');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `status` enum('new','replied','resolved') DEFAULT 'new',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `user_email`, `status`, `priority`, `created_at`, `updated_at`) VALUES
(1, 'Ganesh Sadashiv Kalapad', 's22_borkar_sumit@mgmcen.ac.in', 'notification problem', 'there is problem in notification.Notification is not coming to my mail', 's22_borkar_sumit@mgmcen.ac.in', 'new', 'low', '2025-06-13 14:24:31', '2025-06-13 14:24:31'),
(2, 'xyz', 's22_borkar_sumit@mgmcen.ac.in', 'abc', 'hello', 's22_borkar_sumit@mgmcen.ac.in', 'new', 'low', '2025-06-19 08:21:55', '2025-06-19 08:21:55');

-- --------------------------------------------------------

--
-- Table structure for table `cosmetics`
--

CREATE TABLE `cosmetics` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cosmetic_name` varchar(200) NOT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cosmetics`
--

INSERT INTO `cosmetics` (`id`, `email`, `cosmetic_name`, `mfg_date`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, 'test@example.com', 'Face Cream', '2024-12-13', '2025-06-16', '2025-06-13 06:26:30', '2025-06-13 06:26:30'),
(2, 's22_borkar_sumit@mgmcen.ac.in', 'cetaphile cleanser', '2025-06-11', '2025-07-05', '2025-06-21 06:32:55', '2025-06-21 06:32:55'),
(3, 's22_borkar_sumit@mgmcen.ac.in', 'lipstick', '2025-01-10', '2025-08-08', '2025-07-17 15:05:12', '2025-07-17 15:05:12');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `document_name` varchar(200) NOT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `email`, `document_name`, `mfg_date`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, 'test@example.com', 'Driving License', '2020-06-13', '2025-06-20', '2025-06-13 06:26:30', '2025-06-13 06:26:30'),
(2, 's22_borkar_sumit@mgmcen.ac.in', 'income c', '2025-06-13', '2025-06-21', '2025-06-13 06:44:00', '2025-06-13 06:44:00'),
(4, 's22_borkar_sumit@mgmcen.ac.in', 'income c', '2025-06-12', '2025-06-28', '2025-06-15 15:53:11', '2025-06-15 15:53:11'),
(5, 's22_borkar_sumit@mgmcen.ac.in', 'income c', '2025-06-12', '2025-06-28', '2025-06-15 16:01:36', '2025-06-15 16:01:36'),
(6, 's22_borkar_sumit@mgmcen.ac.in', 'income c', '2025-06-12', '2025-06-28', '2025-06-15 16:15:22', '2025-06-15 16:15:22'),
(7, 's22_borkar_sumit@mgmcen.ac.in', 'passport', '2025-06-11', '2035-06-15', '2025-06-15 17:40:18', '2025-06-15 17:40:18'),
(8, 's22_borkar_sumit@mgmcen.ac.in', 'passport', '2025-06-11', '2035-06-15', '2025-06-15 17:44:13', '2025-06-15 17:44:13'),
(9, 's22_borkar_sumit@mgmcen.ac.in', 'license', '2021-01-15', '2041-07-16', '2025-06-15 17:48:56', '2025-06-15 17:48:56'),
(10, 's22_borkar_sumit@mgmcen.ac.in', 'license', '2025-06-01', '2025-07-01', '2025-06-16 05:24:06', '2025-06-16 05:24:06'),
(11, 's22_borkar_sumit@mgmcen.ac.in', 'Non-Creamy Layer ', '2025-06-10', '2028-06-24', '2025-06-16 06:33:33', '2025-06-16 06:33:33'),
(12, 's22_borkar_sumit@mgmcen.ac.in', 'Non-Creamy Layer ', '2025-06-10', '2028-06-24', '2025-06-16 06:37:35', '2025-06-16 06:37:35'),
(13, 's22_borkar_sumit@mgmcen.ac.in', 'Passport', '2025-06-16', '2025-06-23', '2025-06-18 15:59:18', '2025-06-18 15:59:18'),
(14, 'nageshballurkar2003@gmail.com', 'Passport', '2025-06-01', '2025-06-22', '2025-06-18 16:32:33', '2025-06-18 16:32:33'),
(19, 'nageshballurkar2003@gmail.com', 'License', '2025-06-01', '2025-06-29', '2025-06-18 17:04:58', '2025-06-18 17:04:58'),
(20, 'nageshballurkar2003@gmail.com', 'License', '2025-06-01', '2025-06-29', '2025-06-18 17:07:08', '2025-06-18 17:07:08'),
(21, 'nageshballurkar2003@gmail.com', 'License', '2025-06-01', '2025-06-29', '2025-06-18 17:07:41', '2025-06-18 17:07:41'),
(22, 'nageshballurkar2003@gmail.com', 'License', '2025-06-01', '2025-06-19', '2025-06-18 17:07:51', '2025-06-18 17:07:51'),
(23, 'nageshballurkar2003@gmail.com', 'License', '2025-06-01', '2025-06-19', '2025-06-18 17:30:06', '2025-06-18 17:30:06'),
(24, 'nageshballurkar2003@gmail.com', 'Passport', '2025-06-08', '2025-06-19', '2025-06-18 17:30:20', '2025-06-18 17:30:20'),
(25, 'nageshballurkar2003@gmail.com', 'Passport', '2025-06-08', '2025-06-19', '2025-06-18 17:38:04', '2025-06-18 17:38:04'),
(26, 'nageshballurkar2003@gmail.com', 'Passport', '2025-06-08', '2025-06-19', '2025-06-18 17:40:27', '2025-06-18 17:40:27'),
(27, 'nageshballurkar2003@gmail.com', 'Mobile', '2025-06-01', '2025-06-22', '2025-06-18 17:40:52', '2025-06-18 17:40:52'),
(31, 's22_borkar_sumit@mgmcen.ac.in', 'License', '2025-06-09', '2025-06-20', '2025-06-18 18:00:38', '2025-06-18 18:00:38'),
(32, 's22_borkar_sumit@mgmcen.ac.in', 'License', '2025-06-10', '2025-06-19', '2025-06-18 18:06:31', '2025-06-18 18:06:31'),
(33, 's22_borkar_sumit@mgmcen.ac.in', 'income', '2023-03-14', '2025-06-20', '2025-06-18 18:10:28', '2025-06-18 18:10:28'),
(34, 's22_borkar_sumit@mgmcen.ac.in', 'income', '2023-03-14', '2025-06-20', '2025-06-18 18:12:39', '2025-06-18 18:12:39'),
(35, 's22_borkar_sumit@mgmcen.ac.in', 'Passport', '2015-06-18', '2025-06-20', '2025-06-18 18:13:05', '2025-06-18 18:13:05'),
(36, 's22_borkar_sumit@mgmcen.ac.in', 'License', '2025-06-10', '2025-06-27', '2025-06-18 18:16:45', '2025-06-18 18:16:45'),
(37, 's22_borkar_sumit@mgmcen.ac.in', 'License', '2025-06-10', '2025-06-27', '2025-06-18 18:26:55', '2025-06-18 18:26:55'),
(38, 's22_borkar_sumit@mgmcen.ac.in', 'income', '2025-06-02', '2025-06-20', '2025-06-18 18:27:24', '2025-06-18 18:27:24'),
(39, 's22_borkar_sumit@mgmcen.ac.in', 'Passport', '2025-06-03', '2025-06-28', '2025-06-18 18:33:06', '2025-06-18 18:33:06'),
(40, 's22_borkar_sumit@mgmcen.ac.in', 'License', '2025-06-03', '2025-06-20', '2025-06-18 18:36:18', '2025-06-18 18:36:18'),
(41, 's22_borkar_sumit@mgmcen.ac.in', 'License', '2025-06-04', '2025-06-28', '2025-06-18 18:41:37', '2025-06-18 18:41:37'),
(42, 's22_borkar_sumit@mgmcen.ac.in', 'License', '2025-06-17', '2025-06-27', '2025-06-18 19:20:10', '2025-06-18 19:20:10'),
(43, 's22_borkar_sumit@mgmcen.ac.in', 'License', '2025-06-17', '2025-07-03', '2025-06-18 19:31:54', '2025-06-18 19:31:54'),
(44, 's22_borkar_sumit@mgmcen.ac.in', 'License', '2025-06-17', '2025-07-03', '2025-06-18 19:34:40', '2025-06-18 19:34:40'),
(45, 's22_borkar_sumit@mgmcen.ac.in', 'income c', '2025-07-09', '2026-08-17', '2025-07-17 15:00:57', '2025-07-17 15:00:57');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `category` enum('user-interface','functionality','performance','bug-report','feature-request','general') NOT NULL,
  `feedback` text NOT NULL,
  `suggestions` text DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `status` enum('new','reviewed','resolved') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `name`, `email`, `rating`, `category`, `feedback`, `suggestions`, `user_email`, `status`, `created_at`, `updated_at`) VALUES
(1, 'John Doe', 'john@example.com', 5, 'functionality', 'Great app! Very helpful for tracking expiry dates.', 'Maybe add email notifications?', NULL, 'new', '2025-06-13 06:22:10', '2025-06-13 06:22:10'),
(2, 'Jane Smith', 'jane@example.com', 4, 'user-interface', 'Love the clean design. Easy to use.', 'Dark mode would be nice', NULL, 'new', '2025-06-13 06:22:10', '2025-06-13 06:22:10'),
(3, 'Mike Johnson', 'mike@example.com', 5, 'general', 'This app saved me from using expired medicine!', 'Keep up the good work', NULL, 'new', '2025-06-13 06:22:10', '2025-06-13 06:22:10');

-- --------------------------------------------------------

--
-- Table structure for table `foods`
--

CREATE TABLE `foods` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `food_name` varchar(200) NOT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `foods`
--

INSERT INTO `foods` (`id`, `email`, `food_name`, `mfg_date`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, 'test@example.com', 'Milk', '2025-06-06', '2025-06-14', '2025-06-13 06:26:30', '2025-06-13 06:26:30'),
(2, 's22_borkar_sumit@mgmcen.ac.in', 'eggs', '2025-06-13', '2025-06-19', '2025-06-13 06:47:44', '2025-06-13 06:47:44'),
(4, 's22_borkar_sumit@mgmcen.ac.in', 'bread', '2025-06-12', '2025-06-14', '2025-06-13 13:56:10', '2025-06-13 13:56:10'),
(5, 's22_borkar_sumit@mgmcen.ac.in', 'bread', '2025-06-12', '2025-06-14', '2025-06-13 13:59:44', '2025-06-13 13:59:44'),
(6, 's22_borkar_sumit@mgmcen.ac.in', 'bread', '2025-06-12', '2025-06-14', '2025-06-13 14:02:40', '2025-06-13 14:02:40'),
(7, 's22_borkar_sumit@mgmcen.ac.in', 'bread', '2025-06-12', '2025-06-14', '2025-06-13 14:08:17', '2025-06-13 14:08:17'),
(8, 's22_borkar_sumit@mgmcen.ac.in', 'fanta', '2025-06-02', '2025-06-18', '2025-06-16 06:08:11', '2025-06-16 06:08:11'),
(9, 's22_borkar_sumit@mgmcen.ac.in', 'coco-cola', '2024-12-19', '2025-06-21', '2025-06-19 08:06:52', '2025-06-19 08:06:52'),
(10, 's22_borkar_sumit@mgmcen.ac.in', 'bread', '2025-07-08', '2025-07-25', '2025-07-17 15:03:00', '2025-07-17 15:03:00');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `email`, `medicine_name`, `mfg_date`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, 'test@example.com', 'Paracetamol', '2024-06-13', '2025-06-13', '2025-06-13 06:26:30', '2025-06-13 06:26:30'),
(2, 's22_borkar_sumit@mgmcen.ac.in', 'dolo650', '2025-06-12', '2025-06-14', '2025-06-13 06:46:10', '2025-06-13 06:46:10'),
(3, 's22_borkar_sumit@mgmcen.ac.in', 'paracetamol', '2025-06-12', '2025-06-14', '2025-06-13 14:08:57', '2025-06-13 14:08:57'),
(4, 's22_borkar_sumit@mgmcen.ac.in', 'neftas-pass', '2025-06-12', '2025-06-14', '2025-06-13 14:18:56', '2025-06-13 14:18:56'),
(5, 's22_borkar_sumit@mgmcen.ac.in', 'neftas-pass', '2025-06-11', '2025-06-25', '2025-06-15 16:20:08', '2025-06-15 16:20:08'),
(6, 's22_borkar_sumit@mgmcen.ac.in', 'dolo650', '2025-06-10', '2025-06-17', '2025-06-15 16:32:35', '2025-06-15 16:32:35'),
(7, 's22_borkar_sumit@mgmcen.ac.in', 'neftas-pass', '2025-06-13', '2025-06-18', '2025-06-15 16:51:51', '2025-06-15 16:51:51'),
(8, 's22_borkar_sumit@mgmcen.ac.in', 'neftas-pass', '2025-06-13', '2025-06-18', '2025-06-15 16:54:59', '2025-06-15 16:54:59'),
(14, 's22_borkar_sumit@mgmcen.ac.in', 'Atorvastatin', '2025-06-01', '2025-06-18', '2025-06-16 05:36:12', '2025-06-16 05:36:12'),
(15, 's22_borkar_sumit@mgmcen.ac.in', 'Metformin', '2024-06-16', '2025-06-18', '2025-06-16 06:14:06', '2025-06-16 06:14:06'),
(16, 's22_borkar_sumit@mgmcen.ac.in', 'Ibuprofen', '2025-06-01', '2025-06-20', '2025-06-16 06:20:51', '2025-06-16 06:20:51'),
(17, 's22_borkar_sumit@mgmcen.ac.in', 'Ibuprofen', '2025-06-01', '2025-06-20', '2025-06-16 06:26:00', '2025-06-16 06:26:00'),
(18, 's22_borkar_sumit@mgmcen.ac.in', 'paracetamol', '2025-06-02', '2025-06-26', '2025-06-16 06:26:20', '2025-06-16 06:26:20'),
(19, 's22_borkar_sumit@mgmcen.ac.in', 'Amoxicillin', '2024-02-16', '2025-06-18', '2025-06-16 06:38:30', '2025-06-16 06:38:30'),
(20, 's22_borkar_sumit@mgmcen.ac.in', 'Azithromycin', '2025-06-02', '2025-11-16', '2025-06-16 06:49:01', '2025-06-16 06:49:01'),
(21, 's22_borkar_sumit@mgmcen.ac.in', 'paracetamol', '2025-06-10', '2025-06-21', '2025-06-19 07:54:01', '2025-06-19 07:54:01'),
(22, 's22_borkar_sumit@mgmcen.ac.in', 'Atorvastatin', '2025-06-11', '2025-06-26', '2025-06-19 08:01:19', '2025-06-19 08:01:19'),
(23, 's22_borkar_sumit@mgmcen.ac.in', 'Atorvastatin', '2025-06-11', '2025-06-26', '2025-06-19 08:04:08', '2025-06-19 08:04:08'),
(24, 's22_borkar_sumit@mgmcen.ac.in', 'Atorvastatin', '2025-06-11', '2025-06-26', '2025-06-19 08:06:03', '2025-06-19 08:06:03'),
(25, 's22_borkar_sumit@mgmcen.ac.in', 'neftas-pass', '2025-06-18', '2025-06-25', '2025-06-19 08:06:16', '2025-06-19 08:06:16'),
(26, 's22_borkar_sumit@mgmcen.ac.in', 'Metformin', '2025-06-11', '2025-06-20', '2025-06-19 08:11:36', '2025-06-19 08:11:36'),
(27, 's22_borkar_sumit@mgmcen.ac.in', 'neftas-pass', '2025-07-02', '2025-08-09', '2025-07-17 15:01:19', '2025-07-17 15:01:19');

-- --------------------------------------------------------

--
-- Table structure for table `other_items`
--

CREATE TABLE `other_items` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `other_items`
--

INSERT INTO `other_items` (`id`, `email`, `item_name`, `mfg_date`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, 'test@example.com', 'Expired Item', '2023-06-13', '2025-06-08', '2025-06-13 06:26:30', '2025-06-13 06:26:30'),
(4, 's22_borkar_sumit@mgmcen.ac.in', 'subscription', '2025-06-10', '2025-07-10', '2025-06-15 17:15:41', '2025-06-15 17:15:41'),
(5, 's22_borkar_sumit@mgmcen.ac.in', 'warranty', '2025-06-11', '2025-06-20', '2025-06-15 17:20:42', '2025-06-15 17:20:42'),
(6, 's22_borkar_sumit@mgmcen.ac.in', 'subscription', '2025-06-09', '2025-06-17', '2025-06-15 17:27:03', '2025-06-15 17:27:03'),
(7, 's22_borkar_sumit@mgmcen.ac.in', 'subscription', '2025-06-09', '2025-06-17', '2025-06-15 17:38:52', '2025-06-15 17:38:52'),
(8, 's22_borkar_sumit@mgmcen.ac.in', 'warranty', '2025-06-11', '2025-06-20', '2025-06-15 17:38:59', '2025-06-15 17:38:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` timestamp NULL DEFAULT NULL,
  `account_status` enum('active','inactive','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `created_at`, `updated_at`, `last_login`, `reset_token`, `reset_token_expiry`, `account_status`) VALUES
(1, 'Admin User', 'admin@expiryalert.com', '9999999999', 'admin123', '2025-06-13 06:21:00', '2025-06-13 06:21:00', NULL, NULL, NULL, 'active'),
(2, 'Test User', 'test@example.com', '8888888888', 'password123', '2025-06-13 06:26:30', '2025-06-13 06:26:30', NULL, NULL, NULL, 'active'),
(3, 'Sumitborkar', 's22_borkar_sumit@mgmcen.ac.in', '8421286945', '$2y$10$rmj/GFWWVfeoHluWI9jnee/BZ6LUWugOXvUHbCoyOCyVW.R7vCwGW', '2025-06-13 06:43:15', '2025-07-17 15:00:30', '2025-07-17 15:00:30', NULL, NULL, 'active'),
(4, 'omkar', 's22_fulari_omkar@mgmcen.ac.in', '7507150510', '$2y$10$6FeG9rFf.VsvzhgWQEjZ8O8vUTjC8/d3i362bB1.myONvM/MkPm.6', '2025-06-13 07:02:47', '2025-06-13 07:03:33', '2025-06-13 07:03:33', NULL, NULL, 'active'),
(5, 'omkar fulari', 'omkarfulari699@gmail.com', '7507150510', '$2y$10$allmFUelsam9diYa7opFauFNLvzEZwRrn2FxFG9tGhiCKYvUz9sqC', '2025-06-17 09:45:31', '2025-06-17 09:45:55', '2025-06-17 09:45:55', NULL, NULL, 'active'),
(6, 'Nagesh', 'nageshballurkar2003@gmail.com', '8329803246', '$2y$10$7cGfH8SaJvrYRLMK8LsQr.541CHu5SqrR3YrQzKiX4clVJrVm5wVK', '2025-06-18 16:32:03', '2025-06-18 16:32:19', '2025-06-18 16:32:19', NULL, NULL, 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_email` (`user_email`),
  ADD KEY `idx_contact_email` (`email`),
  ADD KEY `idx_contact_status` (`status`),
  ADD KEY `idx_contact_priority` (`priority`);

--
-- Indexes for table `cosmetics`
--
ALTER TABLE `cosmetics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_email` (`user_email`),
  ADD KEY `idx_feedback_email` (`email`),
  ADD KEY `idx_feedback_rating` (`rating`),
  ADD KEY `idx_feedback_category` (`category`),
  ADD KEY `idx_feedback_status` (`status`);

--
-- Indexes for table `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `other_items`
--
ALTER TABLE `other_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cosmetics`
--
ALTER TABLE `cosmetics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `other_items`
--
ALTER TABLE `other_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE SET NULL;

--
-- Constraints for table `cosmetics`
--
ALTER TABLE `cosmetics`
  ADD CONSTRAINT `cosmetics_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE SET NULL;

--
-- Constraints for table `foods`
--
ALTER TABLE `foods`
  ADD CONSTRAINT `foods_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `other_items`
--
ALTER TABLE `other_items`
  ADD CONSTRAINT `other_items_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
