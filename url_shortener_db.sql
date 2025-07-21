-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 18, 2025 at 10:39 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `url_shortener_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `daily_limit` int(11) DEFAULT 100,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_usage`
--

CREATE TABLE `api_usage` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `endpoint` varchar(100) NOT NULL,
  `request_date` date NOT NULL,
  `request_count` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_imports`
--

CREATE TABLE `bulk_imports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `total_urls` int(11) DEFAULT 0,
  `processed_urls` int(11) DEFAULT 0,
  `failed_urls` int(11) DEFAULT 0,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `click_logs`
--

CREATE TABLE `click_logs` (
  `id` int(11) NOT NULL,
  `url_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `clicked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `click_logs`
--

INSERT INTO `click_logs` (`id`, `url_id`, `ip_address`, `user_agent`, `referer`, `clicked_at`) VALUES
(1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost:3000/', '2025-07-17 06:17:29'),
(2, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost:3000/', '2025-07-17 06:17:37'),
(3, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '', '2025-07-17 06:23:24'),
(4, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/url-shortener/admin/urls.php', '2025-07-17 06:48:53'),
(5, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/url-shortener/admin/urls.php', '2025-07-17 06:48:55'),
(6, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/url-shortener/admin/urls.php', '2025-07-17 06:49:02'),
(7, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/url-shortener/admin/urls.php', '2025-07-17 06:49:15'),
(8, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/url-shortener/admin/urls.php', '2025-07-17 09:37:19');

-- --------------------------------------------------------

--
-- Table structure for table `click_stats`
--

CREATE TABLE `click_stats` (
  `id` int(11) NOT NULL,
  `short_code` varchar(20) NOT NULL,
  `clicked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `click_stats`
--

INSERT INTO `click_stats` (`id`, `short_code`, `clicked_at`, `user_ip`, `user_agent`, `referer`, `country`, `city`) VALUES
(1, '7Z4oB2', '2025-07-17 09:48:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '', NULL, NULL),
(2, 'zPbhOM', '2025-07-17 09:52:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/url-shortener/zPbhOM', NULL, NULL),
(3, 'eZP86k', '2025-07-17 09:53:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/url-shortener/eZP86k', NULL, NULL),
(4, 'jcTAuY', '2025-07-17 10:43:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `custom_domains`
--

CREATE TABLE `custom_domains` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `download_logs`
--

CREATE TABLE `download_logs` (
  `id` int(11) NOT NULL,
  `url_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `downloaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `ip_address`, `user_agent`, `login_time`) VALUES
(1, 2, '10.40.11.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-17 10:25:05'),
(2, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-17 10:28:55'),
(3, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-17 10:30:53'),
(4, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-17 10:31:52'),
(5, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-17 11:26:39'),
(6, 1, '10.40.11.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-17 11:45:33'),
(7, 1, '10.40.11.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-17 12:00:08'),
(8, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-18 02:13:25'),
(9, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-18 05:24:38'),
(10, 1, '10.40.11.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-18 08:38:16');

-- --------------------------------------------------------

--
-- Table structure for table `qr_styles`
--

CREATE TABLE `qr_styles` (
  `id` int(11) NOT NULL,
  `url_id` int(11) DEFAULT NULL,
  `dot_style` varchar(50) DEFAULT 'square',
  `corner_square_style` varchar(50) DEFAULT 'square',
  `corner_dot_style` varchar(50) DEFAULT 'square',
  `bg_color` varchar(7) DEFAULT '#FFFFFF',
  `fg_color` varchar(7) DEFAULT '#000000',
  `logo_path` varchar(255) DEFAULT NULL,
  `frame_style` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `qr_styles`
--

INSERT INTO `qr_styles` (`id`, `url_id`, `dot_style`, `corner_square_style`, `corner_dot_style`, `bg_color`, `fg_color`, `logo_path`, `frame_style`, `created_at`) VALUES
(1, 1, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:14:41'),
(2, 2, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:14:43'),
(3, 3, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:14:48'),
(4, 4, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:14:50'),
(5, 5, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:14:51'),
(6, 6, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:14:55'),
(7, 7, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:14:56'),
(8, 8, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:14:56'),
(9, 9, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:14:56'),
(10, 10, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:14:56'),
(11, 11, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:14:57'),
(12, 12, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:14:57'),
(14, 14, 'square', 'square', 'square', '#ffffff', '#000000', 'logo_14.jpg', NULL, '2025-07-17 06:19:48'),
(15, 15, 'square', 'square', 'square', '#ffffff', '#000000', NULL, NULL, '2025-07-17 06:22:34');

-- --------------------------------------------------------

--
-- Table structure for table `short_urls`
--

CREATE TABLE `short_urls` (
  `id` int(11) NOT NULL,
  `short_code` varchar(20) NOT NULL,
  `original_url` text NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `type` enum('link','text','wifi','file') NOT NULL DEFAULT 'link',
  `content` text DEFAULT NULL,
  `clicks` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `user_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `bg_color` varchar(7) DEFAULT '#FFFFFF',
  `fg_color` varchar(7) DEFAULT '#000000',
  `dot_style` varchar(20) DEFAULT 'square',
  `logo_path` varchar(255) DEFAULT NULL,
  `wifi_ssid` varchar(255) DEFAULT NULL,
  `wifi_password` varchar(255) DEFAULT NULL,
  `wifi_security` varchar(20) DEFAULT NULL,
  `wifi_hidden` tinyint(1) DEFAULT 0,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `download_limit` int(11) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `short_urls`
--

INSERT INTO `short_urls` (`id`, `short_code`, `original_url`, `password`, `type`, `content`, `clicks`, `created_at`, `expires_at`, `is_active`, `user_ip`, `user_agent`, `qr_code_path`, `bg_color`, `fg_color`, `dot_style`, `logo_path`, `wifi_ssid`, `wifi_password`, `wifi_security`, `wifi_hidden`, `file_path`, `file_name`, `file_size`, `file_type`, `download_limit`, `download_count`) VALUES
(1, '9pHKlm', 'https://www.facebook.com/Suradechtuch/', '$2y$10$Y8qGF7QP2eODMNiZVFiQk.uQ4NN6vCeoY/qKN6fscvUJmVrSc8gqu', 'link', NULL, 0, '2025-07-17 09:36:33', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(2, '5bBRLY', 'https://www.facebook.com/Suradechtuch/', '$2y$10$6h52eC7fURn81GgsxQ8B0OkFLgQftlHM4av5wb4LhWY0/W76aVpJ2', 'link', NULL, 0, '2025-07-17 09:37:59', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(3, 'EeFyhi', 'https://www.facebook.com/Suradechtuch/', '$2y$10$QwGtlKMrNoWd/6xanCCtfeibxriiYEzurZkz/LtVm38P/cuA2RCJy', 'link', NULL, 0, '2025-07-17 09:40:11', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(4, 'vtMTQl', 'https://www.facebook.com/Suradechtuch/', '$2y$10$9HvoRBaOSMXWjrWqwReW0OfpKUEprUnY1PViFDDLMWQbi1T42bqWS', 'link', NULL, 0, '2025-07-17 09:40:26', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(5, 'CHb0nY', 'https://edu.yru.ac.th/url/', '$2y$10$b.MZgvnY/wVqNODtHQMgPupVeeRAfPFVMpU0dt.FTsh5HGWgRoPfm', 'link', NULL, 0, '2025-07-17 09:42:32', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(6, 'BOh1F6', 'https://edu.yru.ac.th/url/', NULL, 'link', NULL, 0, '2025-07-17 09:42:49', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(7, 'fTMbUS', 'https://www.facebook.com/Suradechtuch/', '$2y$10$8RyLdmHZXKfPHBGQxnJRy.SQ6dpK5I7Sox80e68RphK4sLgZML9pu', 'link', NULL, 0, '2025-07-17 09:45:00', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(8, 'lpaycU', 'https://www.facebook.com/Suradechtuch/', '$2y$10$SOKYmVxxAmq8AerWezumL.WwTeToYAKUKImiHwGV93pb0vxkd3ibW', 'link', NULL, 0, '2025-07-17 09:47:04', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(9, 'Rm6maD', 'https://www.facebook.com/Suradechtuch/', '$2y$10$Q6uKRT2gEuwrpoasiR4PKOiQG45g7seKiE6HYSBfPGXBGewEipg3K', 'link', NULL, 0, '2025-07-17 09:47:23', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(10, '7Z4oB2', '', NULL, 'text', 'แมว', 1, '2025-07-17 09:48:12', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(11, 'jZVVuS', 'https://edu.yru.ac.th/url/', NULL, 'link', NULL, 0, '2025-07-17 09:48:25', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(12, 'TyXZXm', 'https://edu.yru.ac.th/url/', NULL, 'link', NULL, 0, '2025-07-17 09:48:36', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(13, 'zPbhOM', 'https://www.facebook.com/Suradechtuch/', '$2y$10$jDGjnwvwSjoqVjVM/DYqM.1e2oZq/3Pc97QzsWgYdOdS62lTjNtqW', 'link', NULL, 1, '2025-07-17 09:51:46', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(14, 'Iym9Ja', 'https://edu.yru.ac.th/url/', '$2y$10$9zR59dQ5lkHjBrsgjatWk.HVVWXGjzEguz.OFEZ2cOqtSgJQW9ac.', 'link', NULL, 0, '2025-07-17 09:52:36', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(15, 'eZP86k', 'https://edu.yru.ac.th/url/', '$2y$10$PWOocvcdjEqSL54H9mSRmeUL.rYdt3PyN29RwnOmfcsCINoZjzsBS', 'link', NULL, 1, '2025-07-17 09:53:36', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(16, 'jcTAuY', '', NULL, 'text', 'สุรเดช ทองทวี', 1, '2025-07-17 10:43:09', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(17, 'tcajDY', 'https://www.facebook.com/Suradechtuch/', NULL, 'link', NULL, 0, '2025-07-17 10:47:38', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(18, 'C5QPW0', 'https://www.facebook.com/Suradechtuch/', '$2y$10$iS12xajyFfIrzCKV0a8r2O.HDmUr9rd3QqZ0/BSaboY3P1WpluxMq', 'link', NULL, 0, '2025-07-17 10:53:20', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(19, 's7UVAm', 'https://www.facebook.com/Suradechtuch/', '$2y$10$yEVtQT4MQQRE5OAPKcasY.BwuZOCBXc828jDmWKLuQKhmhS27pXQm', 'link', NULL, 0, '2025-07-17 11:02:23', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(20, 'test123', 'https://www.google.com', NULL, 'link', NULL, 0, '2025-07-17 11:22:21', NULL, 1, NULL, NULL, NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(21, 'hello', 'สวัสดีครับ นี่คือข้อความทดสอบ', NULL, 'text', NULL, 0, '2025-07-17 11:22:21', NULL, 1, NULL, NULL, NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(22, 'DXJyah', 'https://www.facebook.com/Suradechtuch/', '$2y$10$d6SnN2iIfwpAsqyBIb6iluw7oOZFcQD58pIthA2oJZFee7Uljcpa6', 'link', NULL, 0, '2025-07-17 11:24:20', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(23, 'd3aqMu', '', NULL, 'wifi', NULL, 0, '2025-07-17 11:33:07', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, 'EDU_WIFI', 'edu_wifi', 'WPA2', 0, NULL, NULL, NULL, NULL, NULL, 0),
(24, 'mnsjQn', '', NULL, 'wifi', NULL, 0, '2025-07-17 11:34:18', NULL, 1, '10.40.11.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, 'EDU_WIFI', 'edu_wifi', 'WPA2', 0, NULL, NULL, NULL, NULL, NULL, 0),
(25, '1U9vpV', 'https://www.youtube.com/watch?v=JZKJ5orJ-ik', '$2y$10$eYZEJ71WyBjDj/GMIO5Om.AZJzefpBDZ3OHBimoYJGZsMfaiVopm2', 'link', NULL, 0, '2025-07-17 11:35:00', '0000-00-00 00:00:00', 1, '10.40.11.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(26, 'nwXJom', '', NULL, 'text', 'อิอิ', 0, '2025-07-17 11:57:20', NULL, 1, '10.40.11.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(27, 'r3iSHS', 'https://www.facebook.com/Suradechtuch/', '$2y$10$i1ve3bqFeJX16iCupPY43uwbm5CpWD7gwrwFnbTBu.D4Uj3EWUW8S', 'link', NULL, 0, '2025-07-17 12:05:21', '0000-00-00 00:00:00', 1, '10.40.11.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(28, 'yyyshG', 'https://www.facebook.com/Suradechtuch/', '$2y$10$DmntFIHYtElNw5IYa0hJ.uPIjBR9kWbnGLz3VWF49EwcWrZ2Ct.M6', 'link', NULL, 0, '2025-07-17 12:06:00', '0000-00-00 00:00:00', 1, '10.40.11.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(29, 'dC5aHA', 'https://www.facebook.com/Suradechtuch/', '$2y$10$ClOz.pR1njc5agBHzVyif.T72Vb5ohp1XpJyIQN3/TeRaDGVqKx36', 'link', NULL, 0, '2025-07-17 12:14:14', '0000-00-00 00:00:00', 1, '10.40.11.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(30, '46xo4K', 'https://www.facebook.com/Suradechtuch/', '$2y$10$9etHbuXR7akhXD5sSZCXDecOQEiStRxUhYlGDG59QZJwAGViI2czq', 'link', NULL, 0, '2025-07-18 02:01:49', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(31, 'DDBbkw', 'https://edu.yru.ac.th/url/', '$2y$10$tELHAHbA5vnPQApcOYhvieCSImI0B1QBXlqHUay6J1vy2f.phg5IO', 'link', NULL, 0, '2025-07-18 02:02:18', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(32, 'IPQF00', '', '$2y$10$FuxrTl9qDIybqndrSJN7nO9AjvI8ukPLjm9lDyPFX9dee9Y.bbQse', 'file', 'เทสระบบ', 0, '2025-07-18 02:16:39', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879ae86f23b5_BD-chananya.pdf', 'BD-chananya.pdf', 27354, 'application/pdf', 0, 0),
(33, '2cQc1V', 'https://www.facebook.com/Suradechtuch/', '$2y$10$StZ7ToZZF10uO7hQuOZd1u.KrgBcqDmWJjVqPYaQL4Os4xzbWCQSi', 'link', NULL, 0, '2025-07-18 02:19:16', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(34, 'VoFFIF', '', NULL, '', 'BD-chananya.pdf', 0, '2025-07-18 02:19:34', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879af364ea17_BD-chananya.pdf', 'BD-chananya.pdf', 27354, 'application/pdf', 0, 0),
(35, 'eOrbJt', '', NULL, '', '9.jpg', 0, '2025-07-18 02:23:22', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879b01aef564_9.jpg', '9.jpg', 834425, 'image/jpeg', 0, 0),
(36, '6A2Fys', '', NULL, '', 'qrcode_eec809984673b52bc6a8ac4cefc8f3c5_200.png', 0, '2025-07-18 02:27:20', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879b10893cb3_qrcode_eec809984673b52bc6a8ac4cefc8f3c5_200.png', 'qrcode_eec809984673b52bc6a8ac4cefc8f3c5_200.png', 315, 'image/png', 0, 0),
(37, 'b9NNGn', '', NULL, '', 'logo (1).png', 0, '2025-07-18 02:29:34', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879b18eb8e4b_logo1.png', 'logo (1).png', 72587, 'image/png', 0, 0),
(38, 'mjHUwJ', '', NULL, '', 'Test Upload', 0, '2025-07-18 02:32:52', NULL, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879b2546d16a_qrcode_eec809984673b52bc6a8ac4cefc8f3c5_200.png', 'qrcode_eec809984673b52bc6a8ac4cefc8f3c5_200.png', 315, 'image/png', NULL, 0),
(39, 'bwHagm', '', NULL, '', '', 0, '2025-07-18 02:35:37', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879b2f96ca18___________________________________________________.pdf', 'รายชื่อจองคิวส่ง รอบที่ 1 - รอบที่ 4 (6 ก.ค. 68).pdf', 589448, 'application/pdf', 0, 0),
(40, 'a6FbRP', '', NULL, 'wifi', 'ลงทะเบียนเข้าร่วมงาน.docx', 0, '2025-07-18 02:38:12', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879b39423dab___________________________________________________.docx', 'ลงทะเบียนเข้าร่วมงาน.docx', 14341, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 0, 0),
(41, 'QlRLr7', '', NULL, 'file', '9C9B98BD92D8CBF40BFE6F04D9058A9C.pdf', 0, '2025-07-18 02:41:05', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879b44108824_9C9B98BD92D8CBF40BFE6F04D9058A9C.pdf', '9C9B98BD92D8CBF40BFE6F04D9058A9C.pdf', 121762, 'application/pdf', 0, 0),
(42, 'H68zWi', 'https://edu.yru.ac.th/url/', '$2y$10$OawxbK3FC2GHnE4raioQ4.HHuCtl7VJKmTYNwrrT3StIVmQuPfOqW', 'link', NULL, 0, '2025-07-18 02:41:41', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(43, 'E9p6M2', '', NULL, 'file', 'BD-chananya.pdf', 1, '2025-07-18 02:43:14', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879b4c29b93c_BD-chananya.pdf', 'BD-chananya.pdf', 27354, 'application/pdf', 0, 1),
(44, 'RO5DGd', '', '$2y$10$4RXXAumF9x7UG/PEWYNYBe3q18lZp61OWS/ktxIM7IER.ueEiSG4u', 'file', 'เทสระบบ', 1, '2025-07-18 02:43:55', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879b4eb84a2a_9C9B98BD92D8CBF40BFE6F04D9058A9C.pdf', '9C9B98BD92D8CBF40BFE6F04D9058A9C.pdf', 121762, 'application/pdf', 1, 1),
(45, 'd0rjy8', '', NULL, 'file', 'cdlvna.jpg', 0, '2025-07-18 03:13:28', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879bbd8c0825_cdlvna.jpg', 'cdlvna.jpg', 67924, 'image/jpeg', 0, 0),
(46, 'Iy4Wbx', 'https://www.facebook.com/Suradechtuch/', '$2y$10$Rn/i9PkqDj4nhe8O8/xREemFC9/TkgP0ltSimMM6EGl3OlFmc7n4m', 'link', NULL, 0, '2025-07-18 04:03:14', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(47, 'x6ACQb', 'https://edu.yru.ac.th/url/', '$2y$10$KWmQIBSDRxNk5QYat3mQXuarVTLtkXkL7dVyZXwLIUAmqLsqQ9uGq', 'link', NULL, 0, '2025-07-18 04:22:46', '0000-00-00 00:00:00', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(48, 'svPHvE', 'https://edoc.yru.ac.th/public/login', '$2y$10$csY/b7tSKeGkiZsDfNvBcOnBYeCL7xzt57r6zJUVI4nOcQV65U1oC', 'link', NULL, 0, '2025-07-18 07:40:42', '0000-00-00 00:00:00', 1, '10.40.11.239', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0),
(49, 'rO91tW', '', NULL, 'file', 'point นำเสนองบพัฒนาครู.pdf', 1, '2025-07-18 07:41:22', '0000-00-00 00:00:00', 1, '10.40.11.239', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, 'uploads/files/6879faa27f43f_point_____________________________________________.pdf', 'point นำเสนองบพัฒนาครู.pdf', 1910785, 'application/pdf', 0, 1),
(50, 'GpYMPW', '', NULL, 'text', '่้ริ่้ีเ้ั่รีเิ', 0, '2025-07-18 07:41:54', NULL, 1, '10.40.11.239', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL, '#FFFFFF', '#000000', 'square', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `text_contents`
--

CREATE TABLE `text_contents` (
  `id` int(11) NOT NULL,
  `url_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `urls`
--

CREATE TABLE `urls` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `short_code` varchar(10) NOT NULL,
  `original_url` text NOT NULL,
  `url_type` enum('link','text','wifi') DEFAULT 'link',
  `password` varchar(255) DEFAULT NULL,
  `click_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `urls`
--

INSERT INTO `urls` (`id`, `user_id`, `short_code`, `original_url`, `url_type`, `password`, `click_count`, `created_at`, `expires_at`, `is_active`) VALUES
(1, NULL, 'PtWwqh', 'https://edu.yru.ac.th/url/', 'link', '$2y$10$SbZETnaEzrWpALNYY0zYuulYH7Fvk1WS4b.1sxUCSPvvWJ4pPXdfK', 0, '2025-07-17 06:14:41', NULL, 1),
(2, NULL, 'la5Z0c', 'https://edu.yru.ac.th/url/', 'link', '$2y$10$3Z/JizGll/EGeFhGTbjgBe0PbTQ6ZWfaoGUm.lrAiYIbemTV8RpKa', 0, '2025-07-17 06:14:43', NULL, 1),
(3, NULL, 'fMETZZ', 'https://edu.yru.ac.th/url/', 'link', '$2y$10$DiBeWX5yK3r35a0QAT8j/eq8jCzk9HQ0IOCNpgvHIn4GzHntQQdsK', 0, '2025-07-17 06:14:48', NULL, 1),
(4, NULL, 'lptTRO', 'https://edu.yru.ac.th/url/', 'link', '$2y$10$zXpgNR0T/Jxn04DV6pdrtu3SUomy8QBtEXpmeM9PCjrIjoMq1Np6K', 0, '2025-07-17 06:14:50', NULL, 1),
(5, NULL, '3qVbKh', 'https://edu.yru.ac.th/url/', 'link', '$2y$10$u4Yjervk6TVYfhwgc2lvauBml0li3byiCEpTbSbyPW9iOl9d1jir2', 0, '2025-07-17 06:14:51', NULL, 1),
(6, NULL, 'TxwVZH', 'https://edu.yru.ac.th/url/', 'link', '$2y$10$dxKg4oKinGDN6W54g/SjvO1XQX9hZaKXyMh8hxY7BBtCORPydFQDu', 0, '2025-07-17 06:14:55', NULL, 1),
(7, NULL, 'u77yoK', 'https://edu.yru.ac.th/url/', 'link', '$2y$10$QV8Gz6bN7orSYV.IENaRJuogRNYtYHwUUj8HK8apWQnY5ihcWwnXO', 0, '2025-07-17 06:14:56', NULL, 1),
(8, NULL, 'iZAfac', 'https://edu.yru.ac.th/url/', 'link', '$2y$10$6LN3JyhoOk9ii7pHICE4JeU6YSQccACdkWCkRE6Zy2WbanHGvzkR6', 0, '2025-07-17 06:14:56', NULL, 1),
(9, NULL, 'ZoDHwO', 'https://edu.yru.ac.th/url/', 'link', '$2y$10$SWgpgJ7pYGWBzUNHhQpYHO9vsAmbAeIjsVH2rcaKanYlH916/JDwW', 0, '2025-07-17 06:14:56', NULL, 1),
(10, NULL, 'E3Zm4V', 'https://edu.yru.ac.th/url/', 'link', '$2y$10$it9231m1R10yVwA0bB29XOxuZ7u39LJ2nTmKW1UgaQZnnpHcHAygi', 0, '2025-07-17 06:14:56', NULL, 1),
(11, NULL, 'TOIuCr', 'https://edu.yru.ac.th/url/', 'link', '$2y$10$XcoxQGDTzFoXoOE3apy4k.1WCGf7dJH8TA7UOhJ9KC7RkgJ7iOPBq', 0, '2025-07-17 06:14:57', NULL, 1),
(12, NULL, 'RUu4JH', 'https://edu.yru.ac.th/url/', 'link', '$2y$10$wI/F9qn.VHmIQAfd1sHOGOYOM1z9dGfjWLMw8jA82mjpdxJJZFQle', 0, '2025-07-17 06:14:57', NULL, 1),
(14, NULL, '6MtpGj', 'https://www.youtube.com/watch?v=S_Kagw5PolY', 'link', '$2y$10$U6YGu2ttqMhZPuOnRygcc.zcKfuY9h2e/64I3ujjrSefS9F/dvR4m', 0, '2025-07-17 06:19:48', NULL, 1),
(15, NULL, 'ncSuY0', 'https://edu.yru.ac.th/th/', 'link', '$2y$10$1PfIWiYAmxPYQrZUQNbH6utq0xDf7y.1dTv81ReWtEaliZMdo4X66', 0, '2025-07-17 06:22:34', NULL, 1),
(18, NULL, 'RBzCjP', 'https://www.facebook.com/Suradechtuch/', 'link', '$2y$10$ESp.EtT.UW2cr3bAuljEz.nUWTFFX/rqZouNpJouPbCGj.6r5HrIO', 0, '2025-07-17 11:15:22', '0000-00-00 00:00:00', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `url_statistics`
-- (See below for the actual view)
--
CREATE TABLE `url_statistics` (
`id` int(11)
,`short_code` varchar(20)
,`original_url` text
,`type` enum('link','text','wifi','file')
,`clicks` int(11)
,`created_at` timestamp
,`unique_visitors` bigint(21)
,`total_clicks` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `api_key` varchar(64) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `api_key`, `is_active`, `created_at`, `last_login`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$KnAElY6qVZINEwyOOzSXWO52pmSI/9OL91jJf.j7FY3AIS918LUQm', 'admin', '9a448c5ac1bf22d98900998a3c8d1ac15f468419f0285ad38f293ebe6023391f', 1, '2025-07-17 06:27:13', '2025-07-18 08:38:16'),
(2, 'user', 'user@user.com', '$2y$10$OmgABfzOfc5AE8q7Obd3bOsPXiWDEAKA2Rfb1Kmt7RNBCWPyV0.2O', 'user', '2d83e12e3b8f282a32c7bd389511da675e2880f6c335c62b23b819bed4ba725a', 1, '2025-07-17 10:24:52', '2025-07-17 10:30:53');

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_statistics`
-- (See below for the actual view)
--
CREATE TABLE `user_statistics` (
`id` int(11)
,`username` varchar(50)
,`email` varchar(100)
,`role` enum('admin','user')
,`total_urls` bigint(21)
,`total_clicks` decimal(32,0)
,`last_url_created` timestamp
,`last_login` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `wifi_configs`
--

CREATE TABLE `wifi_configs` (
  `id` int(11) NOT NULL,
  `url_id` int(11) DEFAULT NULL,
  `ssid` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `security_type` enum('WEP','WPA','WPA2','nopass') DEFAULT 'WPA2',
  `hidden` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `url_statistics`
--
DROP TABLE IF EXISTS `url_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `url_statistics`  AS SELECT `u`.`id` AS `id`, `u`.`short_code` AS `short_code`, `u`.`original_url` AS `original_url`, `u`.`type` AS `type`, `u`.`clicks` AS `clicks`, `u`.`created_at` AS `created_at`, count(distinct `cl`.`ip_address`) AS `unique_visitors`, count(`cl`.`id`) AS `total_clicks` FROM (`short_urls` `u` left join `click_logs` `cl` on(`u`.`id` = `cl`.`url_id`)) WHERE `u`.`is_active` = 1 GROUP BY `u`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `user_statistics`
--
DROP TABLE IF EXISTS `user_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_statistics`  AS SELECT `u`.`id` AS `id`, `u`.`username` AS `username`, `u`.`email` AS `email`, `u`.`role` AS `role`, count(distinct `urls`.`id`) AS `total_urls`, coalesce(sum(`urls`.`click_count`),0) AS `total_clicks`, max(`urls`.`created_at`) AS `last_url_created`, `u`.`last_login` AS `last_login` FROM (`users` `u` left join `urls` on(`u`.`id` = `urls`.`user_id`)) GROUP BY `u`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `idx_api_key` (`api_key`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `api_usage`
--
ALTER TABLE `api_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usage` (`user_id`,`endpoint`,`request_date`),
  ADD KEY `idx_user_date` (`user_id`,`request_date`);

--
-- Indexes for table `bulk_imports`
--
ALTER TABLE `bulk_imports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `click_logs`
--
ALTER TABLE `click_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_url_id` (`url_id`),
  ADD KEY `idx_clicked_at` (`clicked_at`),
  ADD KEY `idx_ip` (`ip_address`);

--
-- Indexes for table `click_stats`
--
ALTER TABLE `click_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_short_code` (`short_code`),
  ADD KEY `idx_clicked_at` (`clicked_at`);

--
-- Indexes for table `custom_domains`
--
ALTER TABLE `custom_domains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `domain` (`domain`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_domain` (`domain`);

--
-- Indexes for table `download_logs`
--
ALTER TABLE `download_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_url_id` (`url_id`),
  ADD KEY `idx_downloaded_at` (`downloaded_at`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_login_time` (`login_time`);

--
-- Indexes for table `qr_styles`
--
ALTER TABLE `qr_styles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `url_id` (`url_id`);

--
-- Indexes for table `short_urls`
--
ALTER TABLE `short_urls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `short_code` (`short_code`),
  ADD KEY `idx_short_code` (`short_code`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_download_count` (`download_count`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_downloads` (`download_count`,`download_limit`);

--
-- Indexes for table `text_contents`
--
ALTER TABLE `text_contents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `url_id` (`url_id`);

--
-- Indexes for table `urls`
--
ALTER TABLE `urls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `short_code` (`short_code`),
  ADD KEY `idx_short_code` (`short_code`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_api_key` (`api_key`);

--
-- Indexes for table `wifi_configs`
--
ALTER TABLE `wifi_configs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `url_id` (`url_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_usage`
--
ALTER TABLE `api_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bulk_imports`
--
ALTER TABLE `bulk_imports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `click_logs`
--
ALTER TABLE `click_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `click_stats`
--
ALTER TABLE `click_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `custom_domains`
--
ALTER TABLE `custom_domains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `download_logs`
--
ALTER TABLE `download_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `qr_styles`
--
ALTER TABLE `qr_styles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `short_urls`
--
ALTER TABLE `short_urls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `text_contents`
--
ALTER TABLE `text_contents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `urls`
--
ALTER TABLE `urls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wifi_configs`
--
ALTER TABLE `wifi_configs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `api_usage`
--
ALTER TABLE `api_usage`
  ADD CONSTRAINT `api_usage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bulk_imports`
--
ALTER TABLE `bulk_imports`
  ADD CONSTRAINT `bulk_imports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `click_logs`
--
ALTER TABLE `click_logs`
  ADD CONSTRAINT `click_logs_ibfk_1` FOREIGN KEY (`url_id`) REFERENCES `urls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `click_stats`
--
ALTER TABLE `click_stats`
  ADD CONSTRAINT `click_stats_ibfk_1` FOREIGN KEY (`short_code`) REFERENCES `short_urls` (`short_code`) ON DELETE CASCADE;

--
-- Constraints for table `custom_domains`
--
ALTER TABLE `custom_domains`
  ADD CONSTRAINT `custom_domains_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `download_logs`
--
ALTER TABLE `download_logs`
  ADD CONSTRAINT `download_logs_ibfk_1` FOREIGN KEY (`url_id`) REFERENCES `short_urls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `qr_styles`
--
ALTER TABLE `qr_styles`
  ADD CONSTRAINT `qr_styles_ibfk_1` FOREIGN KEY (`url_id`) REFERENCES `urls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `text_contents`
--
ALTER TABLE `text_contents`
  ADD CONSTRAINT `text_contents_ibfk_1` FOREIGN KEY (`url_id`) REFERENCES `urls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `urls`
--
ALTER TABLE `urls`
  ADD CONSTRAINT `urls_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wifi_configs`
--
ALTER TABLE `wifi_configs`
  ADD CONSTRAINT `wifi_configs_ibfk_1` FOREIGN KEY (`url_id`) REFERENCES `urls` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
