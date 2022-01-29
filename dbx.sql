-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 28, 2020 at 10:07 AM
-- Server version: 5.7.25-28-log
-- PHP Version: 7.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbzbt28g5mdc5b`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `status`, `created_at`, `updated_at`) VALUES
(3, 'Kitchen Furniture', 'Active', '2020-01-22 04:02:12', '2020-01-22 04:02:12'),
(4, 'Office Furniture', 'Active', '2020-01-27 09:30:08', '2020-01-27 09:30:08'),
(5, 'Home Furniture', 'Active', '2020-01-27 09:59:56', '2020-01-27 09:59:56'),
(6, 'Dining Table', 'Active', '2020-01-28 23:28:30', '2020-01-28 23:28:30');

-- --------------------------------------------------------

--
-- Table structure for table `data`
--

CREATE TABLE `data` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quote_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `filename` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_100000_create_password_resets_table', 1),
(2, '2019_07_11_081111_create_admins_table', 1),
(3, '2019_07_11_111218_create_permission_tables', 1),
(4, '2019_07_11_121001_create_quotes_table', 1),
(5, '2020_01_10_020035_create_users_table', 1),
(6, '2020_01_10_054222_add_roles_to_users_table', 2),
(7, '2020_01_10_055347_create_roles_table', 3),
(8, '2020_01_10_055726_create_role_user_table', 4),
(9, '2020_01_10_062904_create_quotes_table', 5),
(10, '2020_01_13_125111_create_categories_table', 6),
(11, '2020_01_13_140704_create_vendors_table', 7),
(12, '2020_01_22_051657_add_location_to_quotes_table', 8),
(13, '2020_02_05_040017_create_settings_table', 9),
(14, '2020_02_08_120619_create_vendor_quotes_table', 10),
(15, '2020_04_17_011907_create_files_table', 11),
(16, '2020_04_17_120542_create_data_table', 12),
(17, '2020_04_19_084810_create_vendor_data_table', 13),
(18, '2020_04_19_091229_add_v_status_to_vendor_data', 14),
(19, '2020_04_22_112902_add_v_contact_name_to_vendor_data', 15),
(20, '2020_04_22_113421_add_v_is_verified_to_vendor_data', 16),
(21, '2020_04_22_114139_add_v_password_to_vendor_data', 17),
(22, '2020_04_22_115101_add_v_website_to_vendor_data', 18);

-- --------------------------------------------------------

--
-- Table structure for table `quotes`
--

CREATE TABLE `quotes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_sample` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_privacy` tinyint(4) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotes`
--

INSERT INTO `quotes` (`id`, `user_id`, `category`, `item`, `item_description`, `item_sample`, `location`, `status`, `is_privacy`, `created_at`, `updated_at`) VALUES
(77, 69, '3', 'Kitchen Chimney', 'Chimney with all the latest features', '1587967446.png', 'kphb,Hyderabad', 'Quote Raised', 0, '2020-04-27 06:04:06', '2020-04-27 06:04:06'),
(78, 69, '4', '4 burner stove', 'Test Test', '1587968319.png', 'kphb', 'Y', 1, '2020-04-27 06:18:39', '2020-04-27 06:18:39'),
(79, 70, '3', 'stove', 'looking for 4 burner stove', '1588011889.jpg', 'HYDERABAD', 'Quote Raised', 0, '2020-04-27 18:24:49', '2020-04-27 18:24:49'),
(80, 70, '3', 'stove', 'looking for 4 burner stove', '1588011893.jpg', 'HYDERABAD', 'Quote Raised', 0, '2020-04-27 18:24:53', '2020-04-27 18:24:53');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'ROLE_ADMIN', 'A User with Admin Privilege', NULL, NULL),
(2, 'ROLE_VENDOR', 'A User with Vendor Privilege', NULL, NULL),
(3, 'ROLE_CUSTOMER', 'A User with Customer Privilege', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `role_user`
--

CREATE TABLE `role_user` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `quote_responses_customer` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_info_visible` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `quote_responses_customer`, `customer_info_visible`, `created_at`, `updated_at`) VALUES
(1, 'public', 'public', NULL, NULL),
(2, 'public', 'public', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isVerified` int(11) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `roles` text COLLATE utf8mb4_unicode_ci
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `mobile`, `isVerified`, `remember_token`, `roles`) VALUES
(11, 'Admin', 'admin@iq.com', '$2y$10$0tUYhEyZsT30tGFpjfIfveJwckMT4ped2GW0Kp2alp4D4FznomZay', '1234567890', 0, NULL, NULL),
(69, 'praveenkolla4', 'praveenkolla4@gmail.com', '$2y$10$ASQZUWLHuOtMFbn0P8EPyuU1jsrZORDkSYp2mjQFsckGyLc5BXBsq', '9290646568', 1, NULL, NULL),
(70, 'kiransayi93', 'kiransayi93@gmail.com', '$2y$10$6Y0evbtMJSPXxJIYqZBA6.SgOlfeWPxpfyR3AtZC/VAeXXf0p0pH6', '9533377143', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vendor_data`
--

CREATE TABLE `vendor_data` (
  `vId` bigint(20) UNSIGNED NOT NULL,
  `vName` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vContactName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vPassword` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vCategory` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vAddress` text COLLATE utf8mb4_unicode_ci,
  `vEmail` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vPhone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vMobile` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vWebsite` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vLatitude` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vLongitude` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vIsVerified` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vStatus` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vendor_data`
--

INSERT INTO `vendor_data` (`vId`, `vName`, `vContactName`, `vPassword`, `vCategory`, `vAddress`, `vEmail`, `vPhone`, `vMobile`, `vWebsite`, `vLatitude`, `vLongitude`, `vIsVerified`, `vStatus`, `created_at`, `updated_at`) VALUES
(1, '9502110912', 'kasibabu shaik', '$2y$10$FqHnUqrkbriiEU3dTBUGKOV2MrdK3v7Lj4nAA2RA0lbV77qQSAere', '3', 'Hyderabad', 'kbshaik@aveinfosys.com', '9502110912', '9502110912', 'www.aveitsolutions.com', NULL, NULL, '1', '0', '2020-04-27 05:51:32', '2020-04-27 06:15:32'),
(2, 'Sv Kitchen Stores', 'Praveen', '$2y$10$DZqB3OvxjavHG3wTmHzr6uLeEEucbipPa0PEE/1mf0RUWEoVzWM0u', '3', 'kphb,Hyderabad', 'praveen.osius@gmai.com', '9885344485', '9885344485', 'www.google.com', NULL, NULL, '1', '0', '2020-04-27 06:02:33', '2020-04-27 06:14:48');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_quotes`
--

CREATE TABLE `vendor_quotes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `quote_id` int(11) NOT NULL,
  `itemTitle` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `itemSamples` text COLLATE utf8mb4_unicode_ci,
  `additionalDetails` text COLLATE utf8mb4_unicode_ci,
  `quote_response` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isResponded` smallint(6) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vendor_quotes`
--

INSERT INTO `vendor_quotes` (`id`, `user_id`, `vendor_id`, `quote_id`, `itemTitle`, `price`, `discount`, `itemSamples`, `additionalDetails`, `quote_response`, `status`, `isResponded`, `created_at`, `updated_at`) VALUES
(1, 69, 1, 77, NULL, NULL, NULL, NULL, NULL, NULL, 'Quote Raised', 0, '2020-04-27 06:04:10', '2020-04-27 06:04:10'),
(2, 69, 2, 77, NULL, NULL, NULL, NULL, NULL, NULL, 'Quote Raised', 0, '2020-04-27 06:04:10', '2020-04-27 06:04:10'),
(3, 70, 1, 79, NULL, NULL, NULL, NULL, NULL, NULL, 'Quote Raised', 0, '2020-04-27 18:24:53', '2020-04-27 18:24:53'),
(4, 70, 1, 80, NULL, NULL, NULL, NULL, NULL, NULL, 'Quote Raised', 0, '2020-04-27 18:24:55', '2020-04-27 18:24:55'),
(5, 70, 2, 80, NULL, NULL, NULL, NULL, NULL, NULL, 'Quote Raised', 0, '2020-04-27 18:24:55', '2020-04-27 18:24:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `data`
--
ALTER TABLE `data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quotes`
--
ALTER TABLE `quotes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vendor_data`
--
ALTER TABLE `vendor_data`
  ADD PRIMARY KEY (`vId`);

--
-- Indexes for table `vendor_quotes`
--
ALTER TABLE `vendor_quotes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `data`
--
ALTER TABLE `data`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `quotes`
--
ALTER TABLE `quotes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `role_user`
--
ALTER TABLE `role_user`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `vendor_data`
--
ALTER TABLE `vendor_data`
  MODIFY `vId` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `vendor_quotes`
--
ALTER TABLE `vendor_quotes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
