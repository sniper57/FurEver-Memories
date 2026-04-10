-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 09, 2026 at 02:13 PM
-- Server version: 10.6.24-MariaDB-cll-lve
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `prod_furever_memories_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actor_user_id` int(10) UNSIGNED DEFAULT NULL,
  `actor_role` varchar(50) DEFAULT NULL,
  `action_name` varchar(100) NOT NULL,
  `message` varchar(255) NOT NULL,
  `target_type` varchar(100) DEFAULT NULL,
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `metadata_json` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `actor_user_id`, `actor_role`, `action_name`, `message`, `target_type`, `target_id`, `ip_address`, `user_agent`, `metadata_json`, `created_at`) VALUES
(1, 1, 'administrator', 'login.success', 'User logged in successfully.', 'user', 1, '112.200.164.68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', NULL, '2026-04-07 03:02:59'),
(2, 1, 'administrator', 'client.create', 'Administrator created client account.', 'user', 2, '112.200.164.68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"email\":\"irlandezshirley@gmail.com\",\"verification_sent\":true}', '2026-04-07 03:04:10'),
(3, 1, 'administrator', 'login.success', 'User logged in successfully.', 'user', 1, '112.200.164.68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', NULL, '2026-04-07 03:38:05'),
(4, 1, 'administrator', 'login.success', 'User logged in successfully.', 'user', 1, '112.200.164.68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', NULL, '2026-04-07 06:00:19'),
(5, 1, 'administrator', 'client.verification.resend', 'Verification link resent by administrator.', 'user', 2, '112.200.164.68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"sent\":true}', '2026-04-07 06:38:54'),
(6, 1, 'administrator', 'client.verification.resend', 'Verification link resent by administrator.', 'user', 2, '112.200.164.68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"sent\":true}', '2026-04-07 06:43:34'),
(7, NULL, NULL, 'email.verify', 'Email verification completed.', 'user', 2, '112.200.164.68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', NULL, '2026-04-07 06:44:01'),
(8, 1, 'administrator', 'login.success', 'User logged in successfully.', 'user', 1, '111.90.238.150', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', NULL, '2026-04-07 17:26:35');

-- --------------------------------------------------------

--
-- Table structure for table `auth_login_attempts`
--

CREATE TABLE `auth_login_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attempt_key` varchar(190) NOT NULL,
  `ip_address` varchar(64) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auth_login_attempts`
--

INSERT INTO `auth_login_attempts` (`id`, `attempt_key`, `ip_address`, `success`, `user_id`, `created_at`) VALUES
(1, 'admin@furevermemories.com', '112.200.164.68', 1, 1, '2026-04-07 03:02:59'),
(2, 'admin@furevermemories.com', '112.200.164.68', 1, 1, '2026-04-07 03:38:05'),
(3, 'admin@furevermemories.com', '112.200.164.68', 1, 1, '2026-04-07 06:00:19'),
(4, 'admin@furevermemories.com', '111.90.238.150', 1, 1, '2026-04-07 17:26:35');

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_tokens`
--

CREATE TABLE `email_verification_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification_tokens`
--

INSERT INTO `email_verification_tokens` (`id`, `user_id`, `email`, `token_hash`, `expires_at`, `is_used`, `used_at`, `created_at`) VALUES
(1, 2, 'irlandezshirley@gmail.com', 'a210683b03a93da13c93e3c7e71b8cab1f883863f1dd10fed1a5aa34ec86543a', '2026-04-09 03:04:06', 1, '2026-04-07 06:38:53', '2026-04-07 03:04:06'),
(2, 2, 'irlandezshirley@gmail.com', '40fce2d7af3afa91930d664beb5fde826dd198df337c3b734343ae3a14143e5f', '2026-04-09 06:38:53', 1, '2026-04-07 06:43:34', '2026-04-07 06:38:53'),
(3, 2, 'irlandezshirley@gmail.com', '9469365a5a70a3befb499aebd9ab1d948216ca2612d0c7d58958a502c2b54e74', '2026-04-09 06:43:34', 1, '2026-04-07 06:44:01', '2026-04-07 06:43:34');

-- --------------------------------------------------------

--
-- Table structure for table `memorial_gallery`
--

CREATE TABLE `memorial_gallery` (
  `id` int(10) UNSIGNED NOT NULL,
  `memorial_page_id` int(10) UNSIGNED NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `memorial_gallery`
--

INSERT INTO `memorial_gallery` (`id`, `memorial_page_id`, `photo_path`, `caption`, `sort_order`, `created_at`) VALUES
(31, 1, '049e4cbe-a469-4010-bc5c-845f95435cfb/gallery_20260407_064836_d53bc2f5.jpg', '', 1, '2026-04-07 07:09:12'),
(32, 1, '049e4cbe-a469-4010-bc5c-845f95435cfb/gallery_20260407_064839_5d03c563.jpg', '', 2, '2026-04-07 07:09:12'),
(33, 1, '049e4cbe-a469-4010-bc5c-845f95435cfb/gallery_20260407_064840_810b07d4.jpg', '', 3, '2026-04-07 07:09:12'),
(34, 1, '049e4cbe-a469-4010-bc5c-845f95435cfb/gallery_20260407_064840_12e36ef9.jpg', '', 4, '2026-04-07 07:09:12'),
(35, 1, '049e4cbe-a469-4010-bc5c-845f95435cfb/gallery_20260407_064840_9a1b36da.jpg', '', 5, '2026-04-07 07:09:12'),
(36, 1, '049e4cbe-a469-4010-bc5c-845f95435cfb/gallery_20260407_064841_6728231d.jpg', '', 6, '2026-04-07 07:09:12'),
(37, 1, '049e4cbe-a469-4010-bc5c-845f95435cfb/gallery_20260407_064841_af42c446.jpg', '', 7, '2026-04-07 07:09:12'),
(38, 1, '049e4cbe-a469-4010-bc5c-845f95435cfb/gallery_20260407_064841_32e20a5d.jpg', '', 8, '2026-04-07 07:09:12'),
(39, 1, '049e4cbe-a469-4010-bc5c-845f95435cfb/gallery_20260407_064842_a3874191.jpg', '', 9, '2026-04-07 07:09:12'),
(40, 1, '049e4cbe-a469-4010-bc5c-845f95435cfb/gallery_20260407_064842_6f1767a8.jpg', '', 10, '2026-04-07 07:09:12');

-- --------------------------------------------------------

--
-- Table structure for table `memorial_messages`
--

CREATE TABLE `memorial_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `memorial_page_id` int(10) UNSIGNED NOT NULL,
  `visitor_name` varchar(150) NOT NULL,
  `visitor_photo` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `visitor_ip_hash` varchar(64) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `memorial_page_views`
--

CREATE TABLE `memorial_page_views` (
  `id` int(10) UNSIGNED NOT NULL,
  `memorial_page_id` int(10) UNSIGNED NOT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `visitor_ip_hash` varchar(64) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `viewed_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `memorial_music`
--

CREATE TABLE `memorial_music` (
  `id` int(10) UNSIGNED NOT NULL,
  `memorial_page_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `music_url` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `memorial_music`
--

INSERT INTO `memorial_music` (`id`, `memorial_page_id`, `title`, `music_url`, `sort_order`, `created_at`) VALUES
(7, 1, 'November Rain', 'https://music.youtube.com/watch?v=y6lfK3bH4z8&si=-ZxEpeTx5v2S-qb_', 1, '2026-04-07 07:09:12'),
(8, 1, 'Everlong', 'https://music.youtube.com/watch?v=AxuTd9rwEHQ&si=5dC9CXvv3kEgmFPp', 2, '2026-04-07 07:09:12');

-- --------------------------------------------------------

--
-- Table structure for table `memorial_pages`
--

CREATE TABLE `memorial_pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_user_id` int(10) UNSIGNED NOT NULL,
  `pet_name` varchar(150) DEFAULT NULL,
  `pet_birth_date` date DEFAULT NULL,
  `pet_memorial_date` date DEFAULT NULL,
  `short_tribute` text DEFAULT NULL,
  `final_letter` longtext DEFAULT NULL,
  `video_type` enum('none','file','youtube') NOT NULL DEFAULT 'none',
  `video_url` text DEFAULT NULL,
  `youtube_embed_url` text DEFAULT NULL,
  `video_file` varchar(255) DEFAULT NULL,
  `bg_image_portrait` varchar(255) DEFAULT NULL,
  `bg_image_landscape` varchar(255) DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `share_footer_text` varchar(255) DEFAULT NULL,
  `video_max_mb` int(11) NOT NULL DEFAULT 50,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `memorial_pages`
--

INSERT INTO `memorial_pages` (`id`, `client_user_id`, `pet_name`, `pet_birth_date`, `pet_memorial_date`, `short_tribute`, `final_letter`, `video_type`, `video_url`, `youtube_embed_url`, `video_file`, `bg_image_portrait`, `bg_image_landscape`, `cover_photo`, `share_footer_text`, `video_max_mb`, `created_at`, `updated_at`) VALUES
(1, 2, 'asdasdad', '2026-04-08', '2026-04-08', '<p>asddasddasd</p>', '<p>qweqwqwe</p>', 'youtube', 'https://www.youtube.com/watch?v=-Wn9bvoUqyg', 'https://www.youtube.com/embed/-Wn9bvoUqyg', '', '049e4cbe-a469-4010-bc5c-845f95435cfb/bg_portrait_20260407_070852_ccf137db.png', '049e4cbe-a469-4010-bc5c-845f95435cfb/bg_landscape_20260407_070858_a42888f0.png', '049e4cbe-a469-4010-bc5c-845f95435cfb/cover_20260407_070907_23bf18d6.png', 'Created with love through FurEver Memories', 50, '2026-04-07 03:04:06', '2026-04-07 07:09:12');

-- --------------------------------------------------------

--
-- Table structure for table `memorial_playlist`
--

CREATE TABLE `memorial_playlist` (
  `id` int(11) NOT NULL,
  `memorial_page_id` int(10) unsigned NOT NULL,
  `type` enum('youtube','mp3') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `file_path` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `memorial_reactions`
--

CREATE TABLE `memorial_reactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `memorial_page_id` int(10) UNSIGNED NOT NULL,
  `reaction_type` enum('candle','heart') NOT NULL,
  `visitor_name` varchar(150) NOT NULL,
  `visitor_ip_hash` varchar(64) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `memorial_timelines`
--

CREATE TABLE `memorial_timelines` (
  `id` int(10) UNSIGNED NOT NULL,
  `memorial_page_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `memorial_timelines`
--

INSERT INTO `memorial_timelines` (`id`, `memorial_page_id`, `title`, `event_date`, `photo_path`, `description`, `sort_order`, `created_at`) VALUES
(10, 1, 'wqeqwqwe', '2026-04-14', '049e4cbe-a469-4010-bc5c-845f95435cfb/timeline_20260407_064835_581a49cd.jpg', '<p>qweqwqweqwe</p>', 1, '2026-04-07 07:09:12'),
(11, 1, 'weqwqwe', '2026-04-23', '049e4cbe-a469-4010-bc5c-845f95435cfb/timeline_20260407_064835_412a69ee.jpg', '<p>qweqwqweqwe</p>', 2, '2026-04-07 07:09:12'),
(12, 1, '2123123', '2026-04-24', '049e4cbe-a469-4010-bc5c-845f95435cfb/timeline_20260407_064835_82a2e649.jpg', '<p>qweqweqwqweqw</p>', 3, '2026-04-07 07:09:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role` enum('administrator','client') NOT NULL,
  `client_guid` varchar(64) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `client_guid`, `full_name`, `contact_number`, `address`, `email`, `password_hash`, `is_active`, `is_email_verified`, `email_verified_at`, `last_login_at`, `last_login_ip`, `created_at`, `updated_at`) VALUES
(1, 'administrator', NULL, 'Main Administrator', '', '', 'admin@furevermemories.com', '$2y$12$lQpv4S1V55hH.3ElWe5ZWel95DpFoDWDqBRAz8VZa7eC54lxqievS', 1, 1, NULL, '2026-04-07 17:26:35', '111.90.238.150', '2026-04-06 11:39:23', '2026-04-07 17:26:35'),
(2, 'client', '049e4cbe-a469-4010-bc5c-845f95435cfb', 'Shirley Galacgac', '09171231234', 'Dona Carmen Heights Subd., Commonwealth, Quezon City', 'irlandezshirley@gmail.com', '$2y$10$kXVmYjyjYUVXKZj/lZI//OWkoD6vjln5VySbpQJOFyBtzlwJ/ZdQC', 1, 1, '2026-04-07 06:44:01', NULL, NULL, '2026-04-07 03:04:06', '2026-04-07 06:44:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_actor` (`actor_user_id`,`created_at`),
  ADD KEY `idx_audit_action` (`action_name`,`created_at`);

--
-- Indexes for table `auth_login_attempts`
--
ALTER TABLE `auth_login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attempt_lookup` (`attempt_key`,`ip_address`,`created_at`),
  ADD KEY `fk_attempt_user` (`user_id`);

--
-- Indexes for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_verification_user` (`user_id`),
  ADD KEY `idx_verification_token` (`token_hash`);

--
-- Indexes for table `memorial_gallery`
--
ALTER TABLE `memorial_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gallery_memorial` (`memorial_page_id`);

--
-- Indexes for table `memorial_messages`
--
ALTER TABLE `memorial_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_memorial` (`memorial_page_id`);

--
-- Indexes for table `memorial_page_views`
--
ALTER TABLE `memorial_page_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_page_views_memorial` (`memorial_page_id`);

--
-- Indexes for table `memorial_music`
--
ALTER TABLE `memorial_music`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_music_memorial` (`memorial_page_id`);

--
-- Indexes for table `memorial_pages`
--
ALTER TABLE `memorial_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_memorial_client` (`client_user_id`);

--
-- Indexes for table `memorial_playlist`
--
ALTER TABLE `memorial_playlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_playlist_memorial` (`memorial_page_id`);

--
-- Indexes for table `memorial_reactions`
--
ALTER TABLE `memorial_reactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reactions_memorial` (`memorial_page_id`);

--
-- Indexes for table `memorial_timelines`
--
ALTER TABLE `memorial_timelines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_timeline_memorial` (`memorial_page_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_client_guid` (`client_guid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `auth_login_attempts`
--
ALTER TABLE `auth_login_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `memorial_gallery`
--
ALTER TABLE `memorial_gallery`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `memorial_messages`
--
ALTER TABLE `memorial_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `memorial_page_views`
--
ALTER TABLE `memorial_page_views`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `memorial_music`
--
ALTER TABLE `memorial_music`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `memorial_pages`
--
ALTER TABLE `memorial_pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `memorial_playlist`
--
ALTER TABLE `memorial_playlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `memorial_reactions`
--
ALTER TABLE `memorial_reactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `memorial_timelines`
--
ALTER TABLE `memorial_timelines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `auth_login_attempts`
--
ALTER TABLE `auth_login_attempts`
  ADD CONSTRAINT `fk_attempt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD CONSTRAINT `fk_verification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memorial_gallery`
--
ALTER TABLE `memorial_gallery`
  ADD CONSTRAINT `fk_gallery_memorial` FOREIGN KEY (`memorial_page_id`) REFERENCES `memorial_pages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memorial_messages`
--
ALTER TABLE `memorial_messages`
  ADD CONSTRAINT `fk_messages_memorial` FOREIGN KEY (`memorial_page_id`) REFERENCES `memorial_pages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memorial_page_views`
--
ALTER TABLE `memorial_page_views`
  ADD CONSTRAINT `fk_page_views_memorial` FOREIGN KEY (`memorial_page_id`) REFERENCES `memorial_pages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memorial_music`
--
ALTER TABLE `memorial_music`
  ADD CONSTRAINT `fk_music_memorial` FOREIGN KEY (`memorial_page_id`) REFERENCES `memorial_pages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memorial_playlist`
--
ALTER TABLE `memorial_playlist`
  ADD CONSTRAINT `fk_playlist_memorial` FOREIGN KEY (`memorial_page_id`) REFERENCES `memorial_pages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memorial_pages`
--
ALTER TABLE `memorial_pages`
  ADD CONSTRAINT `fk_memorial_pages_client` FOREIGN KEY (`client_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memorial_reactions`
--
ALTER TABLE `memorial_reactions`
  ADD CONSTRAINT `fk_reactions_memorial` FOREIGN KEY (`memorial_page_id`) REFERENCES `memorial_pages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memorial_timelines`
--
ALTER TABLE `memorial_timelines`
  ADD CONSTRAINT `fk_timeline_memorial` FOREIGN KEY (`memorial_page_id`) REFERENCES `memorial_pages` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
