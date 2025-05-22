-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2025 at 07:08 PM
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
-- Database: `general_service_office`
--

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `borrowing_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `borrow_date` datetime NOT NULL,
  `due_date` datetime NOT NULL,
  `return_date` datetime DEFAULT NULL,
  `status` enum('active','returned','overdue') DEFAULT 'active',
  `condition_on_return` enum('good','fair','damaged') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approval_status` enum('pending','approved','denied') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `return_notes` text DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`borrowing_id`, `equipment_id`, `user_id`, `borrow_date`, `due_date`, `return_date`, `status`, `condition_on_return`, `notes`, `created_at`, `updated_at`, `approval_status`, `approved_by`, `approval_date`, `admin_notes`, `return_notes`, `returned_by`) VALUES
(7, 15, 6, '2025-05-23 10:12:00', '2025-05-24 17:12:00', '2025-05-22 23:08:28', 'returned', 'good', 'acas', '2025-05-22 14:12:52', '2025-05-22 15:08:28', 'approved', 5, '2025-05-22 22:13:07', 'Approved by administrator', 'Excellent', 6),
(8, 19, 6, '2025-05-23 10:58:00', '2025-05-24 16:58:00', '2025-05-22 23:08:21', 'returned', 'good', 'ascasncasjsic', '2025-05-22 14:58:13', '2025-05-22 15:08:21', 'approved', 5, '2025-05-22 23:00:37', 'Approved by administrator', 'Good', 6),
(9, 20, 6, '2025-05-23 11:31:00', '2025-05-24 11:32:00', '2025-05-23 00:38:03', 'returned', 'good', 'scasc', '2025-05-22 15:32:09', '2025-05-22 16:38:03', 'approved', 5, '2025-05-22 23:32:20', 'Approved by administrator', 'Great', 6),
(10, 19, 6, '2025-05-23 11:44:00', '2025-05-24 11:44:00', '2025-05-23 00:37:56', 'returned', 'good', 'ascasc', '2025-05-22 15:44:23', '2025-05-22 16:37:56', 'approved', 5, '2025-05-22 23:44:35', 'Approved by administrator', 'Exellent', 6),
(11, 19, 6, '2025-05-23 12:06:00', '2025-05-24 12:06:00', '2025-05-23 00:24:16', 'returned', 'good', 'acsascs', '2025-05-22 16:06:59', '2025-05-22 16:24:16', 'approved', 5, '2025-05-23 00:07:09', 'Approved by administrator', 'Good', 6),
(12, 15, 6, '2025-05-23 12:36:00', '2025-05-24 12:36:00', '2025-05-23 00:37:48', 'returned', 'good', 'ascas', '2025-05-22 16:36:37', '2025-05-22 16:37:48', 'approved', 5, '2025-05-23 00:36:48', 'Approved by administrator', 'Good', 6),
(13, 20, 6, '2025-05-23 12:39:00', '2025-05-24 12:39:00', '2025-05-23 00:40:58', 'returned', 'good', 'ascas', '2025-05-22 16:39:57', '2025-05-22 16:40:58', 'approved', 5, '2025-05-23 00:40:12', 'Approved by administrator', 'Good', 6),
(14, 20, 6, '2025-05-23 12:43:00', '2025-05-24 12:43:00', '2025-05-23 00:43:58', 'returned', 'good', 'ascacsaac', '2025-05-22 16:43:20', '2025-05-22 16:43:58', 'approved', 5, '2025-05-23 00:43:31', 'Approved by administrator', 'Good', 6),
(15, 20, 6, '2025-05-23 12:56:00', '2025-05-24 12:57:00', '2025-05-23 00:57:56', 'returned', 'good', 'Aax', '2025-05-22 16:57:04', '2025-05-22 16:57:56', 'approved', 5, '2025-05-23 00:57:16', 'Approved by administrator', 'Good', 6),
(16, 16, 6, '2025-05-23 12:58:00', '2025-05-24 12:58:00', '2025-05-23 01:00:22', 'returned', 'good', 'ascsas', '2025-05-22 16:58:10', '2025-05-22 17:00:22', 'approved', 5, '2025-05-23 00:58:20', 'Approved by administrator', 'Good', 6);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `created_at`) VALUES
(1, 'Power Tools', 'Electric and battery-powered tools for construction and maintenance.', '2025-04-19 10:34:38'),
(3, 'Electronic', 'Electronic devices like projectors, cameras, and computers.', '2025-04-19 10:34:38'),
(4, 'Transport Equipment', 'Used for transporting equipment or items.', '2025-05-02 03:33:15'),
(5, 'Sound System', 'Use for event\\&#039;s music.', '2025-05-04 08:09:06');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL,
  `equipment_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `acquisition_date` date DEFAULT NULL,
  `status` enum('available','maintenance','retired','partially_borrowed','borrowed','partially_maintenance','partially_both','available_critical','maintenance_critical','partially_borrowed_critical','borrowed_critical','partially_maintenance_critical','partially_both_critical') DEFAULT NULL,
  `condition_status` enum('new','good','fair','poor') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quantity` int(11) NOT NULL DEFAULT 1,
  `available_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `equipment_code`, `name`, `category_id`, `description`, `acquisition_date`, `status`, `condition_status`, `notes`, `created_at`, `updated_at`, `quantity`, `available_quantity`) VALUES
(15, 'GSO - 001', 'Push Cart Flat Types', 4, 'Cart with a flat platform used for manually transporting goods or materials over short distances. It typically has handles for pushing and no sides or walls, making it ideal for moving large or bulky items in warehouses, stores, or factories.', '0000-00-00', 'available', 'new', '0', '2025-05-16 09:16:56', '2025-05-22 16:39:28', 2, 2),
(16, 'GSO - 002', 'Planer Heavy Duty', 1, 'Woodworking machine used to smooth, flatten, or reduce the thickness of large wooden boards. It is designed for continuous, high-capacity use and can handle hardwood and large materials, making it ideal for industrial or professional carpentry and furniture making.', '2025-05-01', 'partially_maintenance', 'new', '0', '2025-05-16 09:17:31', '2025-05-22 17:00:57', 11, 9),
(18, 'GSO - 004', 'Jigsaw Heavy Duty', 1, 'Cutting tool used to make curved, straight, or intricate cuts in materials like wood, metal, or plastic. It features a reciprocating blade that moves up and down and is designed for tough, continuous use in industrial or professional settings.', '2025-05-01', 'maintenance_critical', 'new', '0', '2025-05-16 09:18:40', '2025-05-22 17:00:35', 1, 0),
(19, 'GSO - 005', 'Portable Welding Machine', 1, 'Used to join metal parts together by melting them with heat. It is designed for easy transport and on-site welding tasks, making it ideal for construction, repairs, and maintenance work in various locations.', '2025-05-01', 'available', 'new', '0', '2025-05-16 09:19:23', '2025-05-22 16:39:22', 6, 6),
(20, 'GSO - 006', 'Speakers', 5, 'To hear audio from sources like microphones, music players, or computers. It is commonly used in sound systems, public address setups, and multimedia devices to amplify and project sound.', '2025-05-01', 'available', 'new', '0', '2025-05-16 09:20:05', '2025-05-22 16:57:56', 4, 4),
(24, 'GSO - 003', 'Electric Drill', 1, 'Used for drilling holes or driving screws into various materials like wood, metal, or plastic. It runs on electric power and typically features adjustable speed and interchangeable drill bits, making it useful for construction, DIY projects, and repair work.', '0000-00-00', 'available', 'new', '', '2025-05-22 17:05:13', '2025-05-22 17:05:13', 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `issue_description` text NOT NULL,
  `maintenance_date` date NOT NULL,
  `resolved_date` date DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `resolved_by` varchar(255) DEFAULT NULL,
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `units` int(11) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`maintenance_id`, `equipment_id`, `issue_description`, `maintenance_date`, `resolved_date`, `cost`, `resolved_by`, `status`, `units`, `notes`, `created_at`, `updated_at`) VALUES
(56, 18, 'aasaf', '2025-05-22', NULL, NULL, NULL, 'pending', 1, '', '2025-05-22 17:00:35', '2025-05-22 17:00:35'),
(57, 16, 'acas', '2025-05-22', NULL, NULL, NULL, 'pending', 1, '', '2025-05-22 17:00:54', '2025-05-22 17:00:54'),
(58, 16, 'asscas', '2025-05-22', NULL, NULL, NULL, 'pending', 1, '', '2025-05-22 17:00:57', '2025-05-22 17:00:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `university_id` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','faculty','staff','admin') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `program_year_section` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive','archived') NOT NULL DEFAULT 'active',
  `status_changed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `university_id`, `first_name`, `last_name`, `email`, `password`, `role`, `department`, `phone`, `program_year_section`, `created_at`, `updated_at`, `status`, `status_changed_at`) VALUES
(3, '23-00001', 'Bry', 'Bermudez', 'bermudez_bryan@plpasig.edu.ph', '$2y$10$59khyvXXOZe5ykrjKDfSDuTUpyCCPl8l1erA7ysLNiekuK1xKhxXi', 'student', 'General Service Office', '1234567890', 'BSIT - 2E', '2025-04-19 10:54:45', '2025-05-22 08:34:39', 'active', '2025-05-22 08:34:39'),
(5, '000', 'General Service', 'Office', 'peligro_johnmichael@plpasig.edu.ph', '$2y$10$lR4u.l8KJXl6ImxmXoiPlu7nXxi2.0MeHS9VXszgiAatHBpJH4jpC', 'admin', 'General Service Office', '12345678', '', '2025-04-19 11:20:26', '2025-05-19 04:31:53', 'active', NULL),
(6, '23-00184', 'John Michael', 'Peligro', 'johnmichaelpeligro4@gmail.com', '$2y$10$BGYG5c4t7CjwjCY7/Kd1FOOgvkrMOy0/nS1RcLx3AVYvgpqN4KPpi', 'student', 'College of Computer Studies', '09162045215', NULL, '2025-04-25 01:47:29', '2025-05-04 09:53:38', 'active', NULL),
(7, '23-00185', 'Johnmel', 'Rojay', 'rojas_johnmel@plpasig.edu.ph', '$2y$10$ECJHeICfGGzsMrDPQfcw7eSsgk21mpi93D3VksbLyPqTbnAAxj.ly', 'student', 'College of Computer Studies', '1234567', 'BSIT - 2E', '2025-05-10 06:23:59', '2025-05-10 06:23:59', 'active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD PRIMARY KEY (`borrowing_id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`),
  ADD UNIQUE KEY `equipment_code` (`equipment_code`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `maintenance_ibfk_1` (`equipment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `borrowing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`),
  ADD CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
