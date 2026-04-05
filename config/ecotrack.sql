-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 11, 2026 at 09:56 AM
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
-- Database: `ecotrack`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `badge_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_path` varchar(255) DEFAULT NULL,
  `points_required` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`badge_id`, `name`, `description`, `icon_path`, `points_required`) VALUES
(1, 'Newbie', 'Earned your first 10 points.', 'assets/badges/newbie.png', 10),
(2, 'Recycler', 'Earn 100 Eco-Points to unlock this badge.', 'assets/badges/recycler.png', 100),
(3, 'Super Star', 'Reached 1000 points.', 'assets/badges/superStar.png', 1000);

-- --------------------------------------------------------

--
-- Table structure for table `eco_activities`
--

CREATE TABLE `eco_activities` (
  `activity_id` int(11) NOT NULL,
  `activity_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `points_awarded` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eco_activities`
--

INSERT INTO `eco_activities` (`activity_id`, `activity_name`, `description`, `points_awarded`) VALUES
(1, 'Use Reusable Bottle', 'Drank water using a reusable bottle instead of plastic.', 10),
(2, 'Carpooling', 'Shared a ride to campus.', 20),
(3, 'Recycling', 'Separated waste into recycling bins.', 15),
(4, 'Meat-Free Meal', 'Ate a vegetarian lunch at the cafeteria.', 25);

-- --------------------------------------------------------

--
-- Table structure for table `submission_proof`
--

CREATE TABLE `submission_proof` (
  `proof_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `challenge_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `file_path` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submission_proof`
--

INSERT INTO `submission_proof` (`proof_id`, `user_id`, `activity_id`, `challenge_id`, `quantity`, `file_path`, `notes`, `status`, `submitted_at`) VALUES
(1, 1, 1, NULL, 1, 'uploads/dummy1.jpg', NULL, 'approved', '2026-01-08 12:50:27'),
(2, 1, 2, NULL, 1, 'uploads/dummy2.jpg', NULL, 'approved', '2026-01-09 12:50:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','admin','organizer') DEFAULT 'student',
  `intake_code` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password_hash`, `role`, `intake_code`, `created_at`) VALUES
(1, 'De Shen (Student)', 'student@apu.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'UCDF2407ICT', '2026-01-10 12:50:27'),
(2, 'Pravin (Admin)', 'admin@apu.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'STAFF001', '2026-01-10 12:50:27'),
(3, 'Nathan (Organizer)', 'organizer@apu.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organizer', 'STAFF002', '2026-01-10 12:50:27');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `challenge_id` int(11) DEFAULT NULL,
  `proof_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `points_earned` int(11) NOT NULL,
  `logged_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`log_id`, `user_id`, `activity_id`, `challenge_id`, `proof_id`, `quantity`, `points_earned`, `logged_at`) VALUES
(1, 1, 1, NULL, 1, 1, 10, '2026-01-08 12:50:27'),
(2, 1, 2, NULL, 2, 1, 20, '2026-01-09 12:50:27');

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `earned_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weekly_challenges`
--

CREATE TABLE `weekly_challenges` (
  `challenge_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `bonus_points` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `weekly_challenges`
--

INSERT INTO `weekly_challenges` (`challenge_id`, `title`, `description`, `bonus_points`, `start_date`, `end_date`, `image_path`) VALUES
(1, 'Plastic Free Week', 'Avoid all single-use plastics for a week.', 50, '2026-01-01', '2026-01-07', ''),
(2, 'Walk to Class', 'Don\'t use the elevators for a day.', 30, '2026-01-08', '2026-01-14', ''),
(3, 'Plastic Free Week', 'Try and not use plastic for about a week.', 50, '2026-01-11', '2026-01-18', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`badge_id`);

--
-- Indexes for table `eco_activities`
--
ALTER TABLE `eco_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD UNIQUE KEY `activity_name` (`activity_name`);

--
-- Indexes for table `submission_proof`
--
ALTER TABLE `submission_proof`
  ADD PRIMARY KEY (`proof_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `activity_id` (`activity_id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `activity_id` (`activity_id`),
  ADD KEY `proof_id` (`proof_id`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `badge_id` (`badge_id`);

--
-- Indexes for table `weekly_challenges`
--
ALTER TABLE `weekly_challenges`
  ADD PRIMARY KEY (`challenge_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `badge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `eco_activities`
--
ALTER TABLE `eco_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `submission_proof`
--
ALTER TABLE `submission_proof`
  MODIFY `proof_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `weekly_challenges`
--
ALTER TABLE `weekly_challenges`
  MODIFY `challenge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `submission_proof`
--
ALTER TABLE `submission_proof`
  ADD CONSTRAINT `submission_proof_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `submission_proof_ibfk_2` FOREIGN KEY (`activity_id`) REFERENCES `eco_activities` (`activity_id`),
  ADD CONSTRAINT `submission_proof_ibfk_3` FOREIGN KEY (`challenge_id`) REFERENCES `weekly_challenges` (`challenge_id`);

--
-- Constraints for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `user_activity_log_ibfk_2` FOREIGN KEY (`activity_id`) REFERENCES `eco_activities` (`activity_id`),
  ADD CONSTRAINT `user_activity_log_ibfk_3` FOREIGN KEY (`proof_id`) REFERENCES `submission_proof` (`proof_id`);

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`badge_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
