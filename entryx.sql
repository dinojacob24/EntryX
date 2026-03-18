-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 18, 2026 at 07:50 PM
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
-- Database: `entryx`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `action_description` text DEFAULT NULL,
  `affected_table` varchar(100) DEFAULT NULL,
  `affected_record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_activity_log`
--

INSERT INTO `admin_activity_log` (`id`, `admin_id`, `action_type`, `action_description`, `affected_table`, `affected_record_id`, `ip_address`, `created_at`) VALUES
(1, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-01-30 10:47:10'),
(2, 1, 'update_external_program', 'Updated external program ID: 1 (Paid: ₹50)', 'external_programs', 1, '::1', '2026-01-30 10:47:45'),
(3, 1, 'enable_external_registration', 'Enabled external registration for program: Azure', 'external_programs', 1, '::1', '2026-01-30 10:47:48'),
(4, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-01-30 11:09:04'),
(5, 1, 'delete_external_program', 'Deleted external program ID: 1', 'external_programs', 1, '::1', '2026-01-30 11:09:07'),
(6, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 2, '::1', '2026-01-30 11:31:41'),
(7, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 2, '::1', '2026-01-30 11:31:49'),
(8, 1, 'delete_external_program', 'Deleted external program ID: 2', 'external_programs', 2, '::1', '2026-02-01 06:43:35'),
(9, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹100)', 'external_programs', 3, '::1', '2026-02-01 06:51:28'),
(10, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 3, '::1', '2026-02-01 06:51:45'),
(11, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 09:11:19'),
(12, 1, 'create_external_program', 'Created external program: Tech fest (Paid: ₹50)', 'external_programs', 4, '::1', '2026-02-01 09:12:33'),
(13, 1, 'enable_external_registration', 'Enabled external registration for program: Tech fest', 'external_programs', 4, '::1', '2026-02-01 09:12:42'),
(14, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 3, '::1', '2026-02-01 09:12:51'),
(15, 1, 'enable_external_registration', 'Enabled external registration for program: Tech fest', 'external_programs', 4, '::1', '2026-02-01 09:13:03'),
(16, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 09:13:54'),
(17, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 3, '::1', '2026-02-01 09:14:01'),
(18, 1, 'enable_external_registration', 'Enabled external registration for program: Tech fest', 'external_programs', 4, '::1', '2026-02-01 09:22:32'),
(19, 1, 'delete_external_program', 'Deleted external program ID: 4', 'external_programs', 4, '::1', '2026-02-01 09:23:56'),
(20, 1, 'create_external_program', 'Created external program: Tech fest (Paid: ₹50)', 'external_programs', 5, '::1', '2026-02-01 09:24:59'),
(21, 1, 'enable_external_registration', 'Enabled external registration for program: Tech fest', 'external_programs', 5, '::1', '2026-02-01 09:25:04'),
(22, 1, 'update_external_program', 'Updated external program ID: 3 (Paid: ₹100.00)', 'external_programs', 3, '::1', '2026-02-01 09:26:54'),
(23, 1, 'delete_external_program', 'Deleted external program ID: 5', 'external_programs', 5, '::1', '2026-02-01 09:27:03'),
(24, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 3, '::1', '2026-02-01 09:27:12'),
(25, 1, 'delete_external_program', 'Deleted external program ID: 3 (Forced including 1 participants)', 'external_programs', 3, '::1', '2026-02-01 09:41:13'),
(26, 1, 'create_external_program', 'Created external program: Tech fest (Paid: ₹50)', 'external_programs', 6, '::1', '2026-02-01 13:31:17'),
(27, 1, 'enable_external_registration', 'Enabled external registration for program: Tech fest', 'external_programs', 6, '::1', '2026-02-01 13:31:30'),
(28, 1, 'delete_external_program', 'Deleted external program ID: 6 (Forced including 1 participants)', 'external_programs', 6, '::1', '2026-02-01 13:41:08'),
(29, 1, 'create_external_program', 'Created external program: Tech fest (Paid: ₹50)', 'external_programs', 7, '::1', '2026-02-01 16:55:55'),
(30, 1, 'enable_external_registration', 'Enabled external registration for program: Tech fest', 'external_programs', 7, '::1', '2026-02-01 16:55:59'),
(31, 1, 'delete_external_program', 'Deleted external program ID: 7', 'external_programs', 7, '::1', '2026-02-01 16:56:06'),
(32, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 8, '::1', '2026-02-01 16:57:42'),
(33, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 8, '::1', '2026-02-01 17:10:06'),
(34, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 18:20:27'),
(35, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 8, '::1', '2026-02-01 18:20:31'),
(36, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 18:34:15'),
(37, 1, 'delete_external_program', 'Deleted external program ID: 8 (Forced including 1 participants)', 'external_programs', 8, '::1', '2026-02-01 18:34:15'),
(38, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 18:34:21'),
(39, 1, 'delete_external_program', 'Deleted external program ID: 8', 'external_programs', 8, '::1', '2026-02-01 18:34:21'),
(40, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 18:34:28'),
(41, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 18:34:31'),
(42, 1, 'delete_external_program', 'Deleted external program ID: 8', 'external_programs', 8, '::1', '2026-02-01 18:34:31'),
(43, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 9, '::1', '2026-02-01 18:41:52'),
(44, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 9, '::1', '2026-02-01 18:41:57'),
(45, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 18:42:02'),
(46, 1, 'delete_external_program', 'Deleted external program ID: 9', 'external_programs', 9, '::1', '2026-02-01 18:42:02'),
(47, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 18:42:13'),
(48, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 18:42:21'),
(49, 1, 'delete_external_program', 'Deleted external program ID: 9', 'external_programs', 9, '::1', '2026-02-01 18:42:21'),
(50, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹69)', 'external_programs', 10, '::1', '2026-02-01 18:46:55'),
(51, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 10, '::1', '2026-02-01 18:46:59'),
(52, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 18:47:02'),
(53, 1, 'delete_external_program', 'Deleted external program ID: 10', 'external_programs', 10, '::1', '2026-02-01 18:47:02'),
(54, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹49)', 'external_programs', 11, '::1', '2026-02-01 18:50:44'),
(55, 1, 'delete_external_program', 'Deleted external program ID: 11', 'external_programs', 11, '::1', '2026-02-01 18:50:49'),
(56, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 12, '::1', '2026-02-01 18:53:19'),
(57, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 12, '::1', '2026-02-01 18:53:35'),
(58, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 19:01:53'),
(59, 1, 'delete_external_program', 'Deleted external program ID: 12 (Forced including 1 participants)', 'external_programs', 12, '::1', '2026-02-01 19:01:53'),
(60, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 19:01:59'),
(61, 1, 'delete_external_program', 'Deleted external program ID: 12', 'external_programs', 12, '::1', '2026-02-01 19:01:59'),
(62, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-01 19:02:05'),
(63, 1, 'delete_external_program', 'Deleted external program ID: 12', 'external_programs', 12, '::1', '2026-02-01 19:02:05'),
(64, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 13, '::1', '2026-02-01 19:04:13'),
(65, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 13, '::1', '2026-02-01 19:04:16'),
(66, 1, 'delete_external_program', 'Deleted external program ID: 13', 'external_programs', 13, '::1', '2026-02-01 19:04:20'),
(67, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 14, '::1', '2026-02-02 04:26:52'),
(68, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 14, '::1', '2026-02-02 04:30:31'),
(69, 1, 'delete_external_program', 'Deleted external program ID: 14 (Forced including 1 participants)', 'external_programs', 14, '::1', '2026-02-02 04:37:48'),
(70, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 15, '::1', '2026-02-02 04:38:43'),
(71, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-02 04:38:55'),
(72, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 15, '::1', '2026-02-02 04:38:59'),
(73, 1, 'delete_external_program', 'Deleted external program ID: 15', 'external_programs', 15, '::1', '2026-02-02 04:44:31'),
(74, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 16, '::1', '2026-02-02 05:44:06'),
(75, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 16, '::1', '2026-02-02 05:44:12'),
(76, 1, 'delete_external_program', 'Deleted external program ID: 16 (Forced including 1 participants)', 'external_programs', 16, '::1', '2026-02-02 06:43:11'),
(77, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 17, '::1', '2026-02-02 07:54:17'),
(78, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 17, '::1', '2026-02-02 07:54:25'),
(79, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-02-02 08:07:29'),
(80, 1, 'delete_external_program', 'Deleted external program ID: 17 (Forced including 1 participants)', 'external_programs', 17, '::1', '2026-02-02 08:07:48'),
(81, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 18, '::1', '2026-02-02 08:14:03'),
(82, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 18, '::1', '2026-02-02 08:14:16'),
(83, 1, 'delete_external_program', 'Deleted external program ID: 18', 'external_programs', 18, '::1', '2026-02-02 08:14:25'),
(84, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 19, '::1', '2026-02-02 08:34:32'),
(85, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 19, '::1', '2026-02-02 08:34:42'),
(86, 1, 'delete_external_program', 'Deleted external program ID: 19 (Forced including 1 participants)', 'external_programs', 19, '::1', '2026-02-02 08:43:45'),
(87, 1, 'create_external_program', 'Created external program: Tech fest (Paid: ₹0)', 'external_programs', 20, '::1', '2026-02-02 09:25:44'),
(88, 1, 'enable_external_registration', 'Enabled external registration for program: Tech fest', 'external_programs', 20, '::1', '2026-02-02 09:26:00'),
(89, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-03-04 16:29:57'),
(90, 1, 'delete_external_program', 'Deleted external program ID: 20 (Forced including 1 participants)', 'external_programs', 20, '::1', '2026-03-04 16:30:05'),
(91, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 21, '::1', '2026-03-04 16:31:23'),
(92, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 21, '::1', '2026-03-04 16:31:28'),
(93, 1, 'delete_external_program', 'Deleted external program ID: 21', 'external_programs', 21, '::1', '2026-03-05 08:49:47'),
(94, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 22, '::1', '2026-03-05 08:50:58'),
(95, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 22, '::1', '2026-03-05 08:51:05'),
(96, 1, 'update_external_program', 'Updated external program ID: 22 (Paid: ₹50)', 'external_programs', 22, '::1', '2026-03-05 09:24:12'),
(97, 1, 'delete_external_program', 'Deleted external program ID: 22 (Forced including 1 participants)', 'external_programs', 22, '::1', '2026-03-05 09:25:01'),
(98, 1, 'create_external_program', 'Created external program: Tech fest (Paid: ₹50)', 'external_programs', 23, '::1', '2026-03-05 09:25:46'),
(99, 1, 'enable_external_registration', 'Enabled external registration for program: Tech fest', 'external_programs', 23, '::1', '2026-03-05 09:26:23'),
(100, 1, 'delete_external_program', 'Deleted external program ID: 23 (Forced including 1 participants)', 'external_programs', 23, '::1', '2026-03-06 04:19:58'),
(101, 1, 'create_external_program', 'Created external program: AZURE', 'external_programs', 24, '::1', '2026-03-11 08:27:57'),
(102, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 24, '::1', '2026-03-11 08:28:07'),
(103, 1, 'delete_external_program', 'Deleted external program ID: 24 (Forced including 2 participants)', 'external_programs', 24, '::1', '2026-03-18 06:42:42'),
(104, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 25, '::1', '2026-03-18 06:50:18'),
(105, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 25, '::1', '2026-03-18 06:50:23'),
(106, 1, 'delete_external_program', 'Deleted external program ID: 25 (Forced including 1 participants)', 'external_programs', 25, '::1', '2026-03-18 08:41:14'),
(107, 1, 'create_external_program', 'Created external program: AZURE', 'external_programs', 26, '::1', '2026-03-18 08:48:29'),
(108, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 26, '::1', '2026-03-18 08:49:36'),
(109, 1, 'delete_external_program', 'Deleted external program ID: 26 (Forced including 1 participants)', 'external_programs', 26, '::1', '2026-03-18 09:47:23'),
(110, 1, 'create_external_program', 'Created external program: AZURE', 'external_programs', 27, '::1', '2026-03-18 09:56:35'),
(111, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 27, '::1', '2026-03-18 09:56:40'),
(112, 1, 'delete_external_program', 'Deleted external program ID: 27 (Forced including 2 participants)', 'external_programs', 27, '::1', '2026-03-18 11:11:24'),
(113, 1, 'create_external_program', 'Created external program: Tech fest (Paid: ₹50)', 'external_programs', 28, '::1', '2026-03-18 15:29:38'),
(114, 1, 'enable_external_registration', 'Enabled external registration for program: Tech fest', 'external_programs', 28, '::1', '2026-03-18 15:29:45'),
(115, 1, 'delete_external_program', 'Deleted external program ID: 28', 'external_programs', 28, '::1', '2026-03-18 15:41:53'),
(116, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 29, '::1', '2026-03-18 15:43:19'),
(117, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 29, '::1', '2026-03-18 15:43:24'),
(118, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-03-18 15:43:35'),
(119, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 29, '::1', '2026-03-18 15:43:53'),
(120, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-03-18 15:49:23'),
(121, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 29, '::1', '2026-03-18 15:52:05'),
(122, 1, 'disable_external_registration', 'Disabled external registration and deactivated all programs', 'system_settings', NULL, '::1', '2026-03-18 15:52:10'),
(123, 1, 'delete_external_program', 'Deleted external program ID: 29', 'external_programs', 29, '::1', '2026-03-18 15:52:19'),
(124, 1, 'create_external_program', 'Created external program: AZURE (Paid: ₹50)', 'external_programs', 30, '::1', '2026-03-18 15:52:46'),
(125, 1, 'enable_external_registration', 'Enabled external registration for program: AZURE', 'external_programs', 30, '::1', '2026-03-18 15:52:52'),
(126, 1, 'delete_external_program', 'Deleted external program ID: 30 (Forced including 1 participants)', 'external_programs', 30, '::1', '2026-03-18 16:05:55'),
(127, 1, 'create_external_program', 'Created external program: Tech fest (Paid: ₹50)', 'external_programs', 31, '::1', '2026-03-18 16:31:21'),
(128, 1, 'enable_external_registration', 'Enabled external registration for program: Tech fest', 'external_programs', 31, '::1', '2026-03-18 16:31:30');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `entry_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `exit_time` timestamp NULL DEFAULT NULL,
  `status` enum('inside','exited') DEFAULT 'inside'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `registration_id`, `entry_time`, `exit_time`, `status`) VALUES
(14, 26, '2026-03-18 16:36:51', '2026-03-18 16:37:33', 'exited');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `poster_image` varchar(255) DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `venue` varchar(100) NOT NULL,
  `capacity` int(11) DEFAULT 100,
  `type` enum('internal','external','both') DEFAULT 'both',
  `is_group_event` tinyint(1) DEFAULT 0,
  `min_team_size` int(11) DEFAULT 1,
  `max_team_size` int(11) DEFAULT 1,
  `program_type` enum('regular','external_program','both') DEFAULT 'regular',
  `is_external_registration_open` tinyint(1) DEFAULT 0,
  `external_program_details` text DEFAULT NULL,
  `is_paid` tinyint(1) DEFAULT 0,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `is_gst_enabled` tinyint(1) DEFAULT 0,
  `gst_rate` decimal(5,2) DEFAULT 18.00,
  `payment_upi` varchar(255) DEFAULT NULL,
  `gst_target` enum('externals_only','both','internals_only') DEFAULT 'both',
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `name`, `description`, `poster_image`, `event_date`, `venue`, `capacity`, `type`, `is_group_event`, `min_team_size`, `max_team_size`, `program_type`, `is_external_registration_open`, `external_program_details`, `is_paid`, `base_price`, `is_gst_enabled`, `gst_rate`, `payment_upi`, `gst_target`, `status`, `created_by`, `created_at`) VALUES
(16, 'General Campus Admission', 'Main gate entry for all registered guests and participants.', NULL, '2026-03-18 14:34:11', 'Main Entry Gate', 100, 'both', 0, 1, 1, 'regular', 0, NULL, 0, 0.00, 0, 18.00, NULL, 'both', 'ongoing', NULL, '2026-03-18 09:04:11'),
(17, 'General Campus Admission', 'Main gate entry for all registered guests and participants.', NULL, '2026-03-18 14:34:22', 'Main Entry Gate', 100, 'both', 0, 1, 1, 'regular', 0, NULL, 0, 0.00, 0, 18.00, NULL, 'both', 'ongoing', NULL, '2026-03-18 09:04:22'),
(18, 'General Campus Admission', 'Main gate entry for all registered guests and participants.', NULL, '2026-03-18 14:34:36', 'Main Entry Gate', 100, 'both', 0, 1, 1, 'regular', 0, NULL, 0, 0.00, 0, 18.00, NULL, 'both', 'ongoing', NULL, '2026-03-18 09:04:36'),
(19, 'General Campus Admission', 'Main gate entry for all registered guests and participants.', NULL, '2026-03-18 14:34:54', 'Main Entry Gate', 100, 'both', 0, 1, 1, 'regular', 0, NULL, 0, 0.00, 0, 18.00, NULL, 'both', 'ongoing', NULL, '2026-03-18 09:04:54'),
(20, 'group dance', '', NULL, '2026-03-19 11:15:00', 'RB lawn', 500, 'both', 1, 3, 6, 'regular', 0, NULL, 1, 50.00, 1, 18.00, '', 'externals_only', 'upcoming', 1, '2026-03-18 16:41:05');

-- --------------------------------------------------------

--
-- Table structure for table `external_programs`
--

CREATE TABLE `external_programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_description` text DEFAULT NULL,
  `registration_form_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`registration_form_fields`)),
  `is_active` tinyint(1) DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `max_participants` int(11) DEFAULT 500 COMMENT 'Maximum number of external entries allowed for this program',
  `is_paid` tinyint(1) DEFAULT 0,
  `registration_fee` decimal(10,2) DEFAULT 0.00,
  `is_gst_enabled` tinyint(1) DEFAULT 0,
  `gst_rate` decimal(5,2) DEFAULT 18.00,
  `total_amount_with_gst` decimal(10,2) GENERATED ALWAYS AS (case when `is_gst_enabled` = 1 then `registration_fee` + `registration_fee` * `gst_rate` / 100 else `registration_fee` end) STORED,
  `payment_gateway` varchar(50) DEFAULT 'razorpay',
  `currency` varchar(3) DEFAULT 'INR',
  `payment_upi` varchar(255) DEFAULT NULL,
  `payment_qr_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `external_programs`
--

INSERT INTO `external_programs` (`id`, `program_name`, `program_description`, `registration_form_fields`, `is_active`, `start_date`, `end_date`, `max_participants`, `is_paid`, `registration_fee`, `is_gst_enabled`, `gst_rate`, `payment_gateway`, `currency`, `payment_upi`, `payment_qr_path`, `created_by`, `created_at`, `updated_at`) VALUES
(31, 'Tech fest', 'For External Entries ', '[]', 1, '2026-03-19', '2026-03-21', 500, 1, 50.00, 1, 18.00, 'razorpay', 'INR', '', NULL, 1, '2026-03-18 16:31:21', '2026-03-18 16:31:30');

-- --------------------------------------------------------

--
-- Table structure for table `payment_gst_breakdown`
--

CREATE TABLE `payment_gst_breakdown` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `base_amount` decimal(10,2) NOT NULL,
  `cgst_rate` decimal(5,2) DEFAULT 9.00,
  `sgst_rate` decimal(5,2) DEFAULT 9.00,
  `igst_rate` decimal(5,2) DEFAULT 18.00,
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `igst_amount` decimal(10,2) DEFAULT 0.00,
  `total_gst` decimal(10,2) NOT NULL,
  `is_interstate` tinyint(1) DEFAULT 0,
  `gstin` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `gateway_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `api_key` varchar(255) DEFAULT NULL,
  `api_secret` varchar(255) DEFAULT NULL,
  `webhook_secret` varchar(255) DEFAULT NULL,
  `test_mode` tinyint(1) DEFAULT 1,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `gateway_name`, `is_active`, `api_key`, `api_secret`, `webhook_secret`, `test_mode`, `settings`, `created_at`, `updated_at`) VALUES
(1, 'razorpay', 1, 'rzp_test_Rr4vDE5NfeXsq7', 'VAsZLjUPMaRyLuB6xqPzzhjs', NULL, 1, '{\"display_name\": \"Razorpay\", \"supported_currencies\": [\"INR\"], \"description\": \"Accept payments via Razorpay\"}', '2026-02-01 17:52:44', '2026-03-05 08:44:15');

-- --------------------------------------------------------

--
-- Table structure for table `program_payments`
--

CREATE TABLE `program_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `target_type` varchar(20) DEFAULT 'program',
  `order_id` varchar(100) NOT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gst_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_gateway` varchar(50) DEFAULT 'razorpay',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_response`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `program_payments`
--

INSERT INTO `program_payments` (`id`, `user_id`, `program_id`, `target_type`, `order_id`, `payment_id`, `amount`, `gst_amount`, `total_amount`, `currency`, `payment_status`, `payment_method`, `payment_gateway`, `transaction_id`, `payment_response`, `created_at`, `updated_at`) VALUES
(4, 2, 11, 'event', 'order_SP9uOky3RrjauJ', 'pay_SP9v27kHL1qyI9', 100.00, 18.00, 118.00, 'INR', 'completed', NULL, 'razorpay', NULL, NULL, '2026-03-09 14:23:43', '2026-03-09 14:24:37'),
(5, 2, 11, 'event', 'order_SPA7Gb4jTMxTER', 'pay_SPA7Nqv2PKDimV', 100.00, 18.00, 118.00, 'INR', 'completed', NULL, 'razorpay', NULL, NULL, '2026-03-09 14:35:54', '2026-03-09 14:36:16'),
(6, 2, 11, 'event', 'order_SPA8tzE6IfYC4p', NULL, 100.00, 18.00, 118.00, 'INR', 'pending', NULL, 'razorpay', NULL, NULL, '2026-03-09 14:37:27', '2026-03-09 14:37:27'),
(7, 2, 11, 'event', 'order_SPAGVPoGeGm910', 'pay_SPAGiosHNVzkY4', 100.00, 18.00, 118.00, 'INR', 'completed', NULL, 'razorpay', NULL, NULL, '2026-03-09 14:44:39', '2026-03-09 14:45:07'),
(8, 2, 11, 'event', 'order_SPAHE0QxQWIQTi', NULL, 100.00, 18.00, 118.00, 'INR', 'pending', NULL, 'razorpay', NULL, NULL, '2026-03-09 14:45:20', '2026-03-09 14:45:20'),
(9, 2, 11, 'event', 'order_SPAJGuIRMLubjn', NULL, 100.00, 18.00, 118.00, 'INR', 'pending', NULL, 'razorpay', NULL, NULL, '2026-03-09 14:47:16', '2026-03-09 14:47:16'),
(10, 2, 12, 'event', 'order_SPAOEVol78CDhR', 'pay_SPAOKA7uJHMyI1', 50.00, 9.00, 59.00, 'INR', 'completed', NULL, 'razorpay', NULL, NULL, '2026-03-09 14:51:58', '2026-03-09 14:52:30'),
(11, 2, 12, 'event', 'order_SPCSt5I6oDUhq4', 'pay_SPCT47M0qlSxy8', 50.00, 9.00, 59.00, 'INR', 'completed', NULL, 'razorpay', NULL, NULL, '2026-03-09 16:53:46', '2026-03-09 16:54:12'),
(12, 2, 12, 'event', 'order_SPQ2QRA3uTq3ys', 'pay_SPQ2d9P2LlYvmo', 50.00, 9.00, 59.00, 'INR', 'completed', NULL, 'razorpay', NULL, NULL, '2026-03-10 06:10:26', '2026-03-10 06:10:53'),
(13, 2, 12, 'event', 'order_SPQ8XEIWhqBYid', 'pay_SPQ8cIpPN0ZuqZ', 50.00, 9.00, 59.00, 'INR', 'completed', NULL, 'razorpay', NULL, NULL, '2026-03-10 06:16:13', '2026-03-10 06:16:33'),
(14, 2, 12, 'event', 'order_SPQQieYTkKdWsN', 'pay_SPQQosyYRkhY70', 50.00, 9.00, 59.00, 'INR', 'completed', NULL, 'razorpay', NULL, NULL, '2026-03-10 06:33:26', '2026-03-10 06:33:49'),
(17, 22, 31, 'program', 'order_SSkx2xr7siTUil', 'pay_SSkxAKsTadyXB7', 50.00, 9.00, 59.00, 'INR', 'completed', NULL, 'razorpay', NULL, NULL, '2026-03-18 16:34:54', '2026-03-18 16:35:18');

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `team_name` varchar(255) DEFAULT NULL,
  `team_members` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`team_members`)),
  `qr_token` varchar(255) NOT NULL,
  `payment_status` enum('pending','completed','failed','free') DEFAULT 'pending',
  `base_amount` decimal(10,2) DEFAULT 0.00,
  `gst_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `transaction_id` varchar(100) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `amount_paid` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `user_id`, `event_id`, `team_name`, `team_members`, `qr_token`, `payment_status`, `base_amount`, `gst_amount`, `total_amount`, `transaction_id`, `qr_code`, `registration_date`, `amount_paid`) VALUES
(26, 22, 16, NULL, NULL, '400b5885870cd3455a2896c9849a519f-auto-Techfest-22', 'free', 0.00, 0.00, 0.00, NULL, '400b5885870cd3455a2896c9849a519f-auto-Techfest-22', '2026-03-18 16:36:51', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `winner_name` varchar(100) NOT NULL,
  `runner_up_name` varchar(100) DEFAULT NULL,
  `consolation_prize` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `published_by` int(11) DEFAULT NULL,
  `published_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`, `updated_by`) VALUES
(1, 'external_registration_enabled', '1', NULL, '2026-03-18 16:31:30', 1),
(3, 'current_external_program_id', '31', NULL, '2026-03-18 16:31:30', 1),
(4, 'current_external_program_name', 'Tech fest', NULL, '2026-03-18 16:31:30', 1),
(5, 'current_external_program_description', 'For External Entries ', NULL, '2026-03-18 16:31:30', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `external_program_id` int(11) DEFAULT NULL,
  `program_payment_id` int(11) DEFAULT NULL,
  `payment_status` enum('not_required','pending','completed','failed') DEFAULT 'not_required',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(50) DEFAULT NULL,
  `qr_token` varchar(255) DEFAULT NULL,
  `registration_source` enum('direct','external_program','google_oauth') DEFAULT 'direct',
  `role` enum('super_admin','event_admin','security','internal','external') NOT NULL,
  `college_id` varchar(50) DEFAULT NULL,
  `college_organization` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `id_proof` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `google_id`, `external_program_id`, `program_payment_id`, `payment_status`, `payment_method`, `transaction_id`, `qr_token`, `registration_source`, `role`, `college_id`, `college_organization`, `department`, `id_proof`, `created_at`, `reset_token`, `reset_expiry`) VALUES
(1, 'Super Admin', 'admin@entryx.com', NULL, '$2y$10$WG6z.HoWXt5KUQ4ojaGgGOwltOUjuoGFoU82tNuVzEg8kk0.E7kfy', NULL, NULL, NULL, 'not_required', NULL, NULL, NULL, 'direct', 'super_admin', NULL, NULL, NULL, NULL, '2026-01-30 10:38:20', NULL, NULL),
(2, 'DINO JACOB INT MCA 2023-2028', 'dinojacob2028@mca.ajce.in', NULL, '', '112160029784041297721', NULL, NULL, 'not_required', NULL, NULL, NULL, 'google_oauth', 'internal', NULL, NULL, NULL, NULL, '2026-01-30 10:43:33', NULL, NULL),
(3, 'Security Admin', 'security@entryx.com', NULL, '$2y$10$l8BB7ogNqSoXFA.OXm3C6.NAWxYUkskb6hK.KkhmVS6PPwvxi9urO', NULL, NULL, NULL, 'not_required', NULL, NULL, NULL, 'direct', 'security', NULL, NULL, NULL, NULL, '2026-01-30 11:11:08', NULL, NULL),
(22, 'Dino Jacob', 'dinojacob24@gmail.com', '9778720724', '$2y$10$0Q9FftWZZlSVFH2yLf3l.uBtHbb9w152cwDbQiQSdZzo4JtzO89Tm', '106009343393150750849', 31, 17, 'completed', NULL, '', '092f1b46130d220cf9b0bee8fea27a60', 'external_program', 'external', '', 'External Participant', '', 'assets/uploads/id_proofs/id_9be68cd7c9191858_1773851693.pdf', '2026-03-18 16:34:54', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration_log` (`registration_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `external_programs`
--
ALTER TABLE `external_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `payment_gst_breakdown`
--
ALTER TABLE `payment_gst_breakdown`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_id` (`payment_id`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gateway_name` (`gateway_name`),
  ADD KEY `idx_gateway_name` (`gateway_name`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `program_payments`
--
ALTER TABLE `program_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_program_id` (`program_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`user_id`,`event_id`),
  ADD UNIQUE KEY `qr_token` (`qr_token`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD UNIQUE KEY `unique_transaction` (`transaction_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `published_by` (`published_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `external_program_id` (`external_program_id`),
  ADD KEY `program_payment_id` (`program_payment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `external_programs`
--
ALTER TABLE `external_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `payment_gst_breakdown`
--
ALTER TABLE `payment_gst_breakdown`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `program_payments`
--
ALTER TABLE `program_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `external_programs`
--
ALTER TABLE `external_programs`
  ADD CONSTRAINT `external_programs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_gst_breakdown`
--
ALTER TABLE `payment_gst_breakdown`
  ADD CONSTRAINT `payment_gst_breakdown_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `program_payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `program_payments`
--
ALTER TABLE `program_payments`
  ADD CONSTRAINT `program_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `results_ibfk_3` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`external_program_id`) REFERENCES `external_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`program_payment_id`) REFERENCES `program_payments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`external_program_id`) REFERENCES `external_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_4` FOREIGN KEY (`program_payment_id`) REFERENCES `program_payments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_5` FOREIGN KEY (`program_payment_id`) REFERENCES `program_payments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
