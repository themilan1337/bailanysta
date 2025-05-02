-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 02, 2025 at 12:16 PM
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
-- Database: `bailanysta`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `post_id`, `content`, `created_at`, `updated_at`) VALUES
(4, 4, 11, 'q', '2025-05-01 18:27:20', '2025-05-01 18:27:20'),
(5, 4, 11, 'q', '2025-05-01 18:29:28', '2025-05-01 18:29:28'),
(6, 4, 11, 'ok', '2025-05-01 18:48:27', '2025-05-01 18:48:27'),
(17, 2, 46, 'q', '2025-05-02 09:23:38', '2025-05-02 09:23:38'),
(18, 2, 44, 'q', '2025-05-02 09:25:59', '2025-05-02 09:25:59'),
(19, 2, 48, 'qqq', '2025-05-02 09:26:21', '2025-05-02 09:26:21'),
(20, 2, 48, 'q', '2025-05-02 09:27:54', '2025-05-02 09:27:54'),
(21, 2, 47, 'q', '2025-05-02 09:30:28', '2025-05-02 09:30:28'),
(22, 2, 47, 'Q', '2025-05-02 09:31:48', '2025-05-02 09:31:48'),
(23, 4, 48, 'woah', '2025-05-02 09:32:38', '2025-05-02 09:32:38'),
(24, 2, 23, 'fddf', '2025-05-02 09:52:10', '2025-05-02 09:52:10');

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

CREATE TABLE `follows` (
  `id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`id`, `follower_id`, `following_id`, `created_at`) VALUES
(5, 4, 2, '2025-05-02 09:47:28');

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `post_id`, `created_at`) VALUES
(16, 4, 11, '2025-05-01 18:27:18'),
(38, 2, 19, '2025-05-01 20:29:17'),
(56, 2, 22, '2025-05-02 09:00:45'),
(57, 2, 18, '2025-05-02 09:00:50'),
(58, 2, 11, '2025-05-02 09:00:50'),
(59, 2, 10, '2025-05-02 09:00:51'),
(60, 2, 48, '2025-05-02 09:22:55'),
(61, 4, 48, '2025-05-02 09:33:43'),
(62, 4, 47, '2025-05-02 09:46:04'),
(63, 4, 49, '2025-05-02 09:50:18'),
(64, 2, 43, '2025-05-02 09:50:37');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('like','comment','follow') NOT NULL,
  `actor_user_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `image_url` varchar(512) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `content`, `image_url`, `created_at`, `updated_at`) VALUES
(10, 2, 'fd', NULL, '2025-05-01 18:05:36', '2025-05-01 18:05:36'),
(11, 2, 'fsfs', NULL, '2025-05-01 18:06:13', '2025-05-01 18:06:13'),
(18, 2, 'cool', NULL, '2025-05-01 19:54:18', '2025-05-01 19:54:18'),
(19, 2, 'fd', NULL, '2025-05-01 20:19:24', '2025-05-01 20:19:24'),
(22, 2, 'fd', NULL, '2025-05-01 20:42:29', '2025-05-01 20:42:29'),
(23, 2, 'hi', '/uploads/posts/post_6813dcbe31ff50.15848808.png', '2025-05-01 20:42:38', '2025-05-01 20:42:38'),
(41, 2, 'India trip highlights: vibrant markets, incredible food, and unforgettable experiences! 🤩🇮🇳  #IndiaTravel #Bailanysta', '/uploads/posts/post_68148993e64ef3.88824053.png', '2025-05-02 09:00:03', '2025-05-02 09:00:03'),
(42, 2, 'nothing changed!', NULL, '2025-05-02 09:07:22', '2025-05-02 09:07:22'),
(43, 2, 'q', NULL, '2025-05-02 09:10:01', '2025-05-02 09:10:01'),
(44, 2, 'q', NULL, '2025-05-02 09:11:11', '2025-05-02 09:11:11'),
(45, 2, 'q', NULL, '2025-05-02 09:11:30', '2025-05-02 09:11:30'),
(46, 2, 'fsadsdfa', NULL, '2025-05-02 09:11:32', '2025-05-02 09:11:32'),
(47, 2, 'q', NULL, '2025-05-02 09:11:47', '2025-05-02 09:11:47'),
(48, 2, 'q', NULL, '2025-05-02 09:11:50', '2025-05-02 09:11:50'),
(49, 4, 'Just observing the latest <strong>developments</strong>...🍿  Interesting times! #Bailanysta #Observing', NULL, '2025-05-02 09:46:54', '2025-05-02 09:46:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `google_id` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `picture_url` varchar(512) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `google_id`, `email`, `name`, `nickname`, `picture_url`, `created_at`, `updated_at`) VALUES
(2, '107561940543127069743', 'gorislavetsmilan123@gmail.com', 'Milan1337', 'fdfdfd', '/uploads/users/avatar_afa440813bc1ff2eda4ec6e4fbf92605fdb18b00.jpg', '2025-05-01 18:03:25', '2025-05-02 09:50:28'),
(4, '109488960103889679810', 'danielrealsigma@gmail.com', 'Daniel', NULL, '/uploads/users/avatar_b1134c4c273504f5d1932a8f97fa9efffe8b1d58.png', '2025-05-01 18:12:44', '2025-05-02 09:32:29'),
(6, '118365483011057385315', 'gorislavetsmilan228@gmail.com', 'Milan Gorislavets', NULL, '/uploads/users/avatar_0ce4a6b2121556c7cb500f322e23b7b811c021ce.jpg', '2025-05-02 08:50:11', '2025-05-02 08:50:11'),
(7, '103697607990099601990', 'mastersofslime123@gmail.com', 'София', NULL, '/uploads/users/avatar_86250e7da5a0ba7894b3415ecb14fcae07978293.jpg', '2025-05-02 09:59:20', '2025-05-02 09:59:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_comments_post_id` (`post_id`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follow` (`follower_id`,`following_id`),
  ADD KEY `idx_follows_follower_id` (`follower_id`),
  ADD KEY `idx_follows_following_id` (`following_id`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`user_id`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `actor_user_id` (`actor_user_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `idx_notifications_user_id_is_read` (`user_id`,`is_read`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_posts_user_id` (`user_id`),
  ADD KEY `idx_posts_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `nickname` (`nickname`),
  ADD KEY `idx_users_google_id` (`google_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `follows`
--
ALTER TABLE `follows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
