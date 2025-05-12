-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2025 at 08:51 AM
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
  `admin_issued_id` int(11) DEFAULT NULL,
  `admin_received_id` int(11) DEFAULT NULL,
  `status` enum('active','returned','overdue','lost') DEFAULT 'active',
  `condition_on_return` enum('excellent','good','fair','poor','damaged') DEFAULT NULL,
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
(3, 'Electronics', 'Electronic devices like projectors, cameras, and computers.', '2025-04-19 10:34:38'),
(4, 'Transport Equipment', 'Used for transporting heavy duty equipment or items.', '2025-05-02 03:33:15'),
(5, 'Sound System', 'Use for event\\\'s music', '2025-05-04 08:09:06');

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
  `status` enum('available','maintenance','retired') NOT NULL,
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
(2, '001', 'Microphone', 3, '', '0000-00-00', 'available', 'new', '', '2025-04-20 06:41:36', '2025-05-11 18:15:37', 5, 5),
(4, 'CART - 001', 'Push Car', 4, 'Used to transport equipments', '0000-00-00', 'available', 'good', '', '2025-05-02 06:01:33', '2025-05-11 18:26:25', 8, 8),
(6, '005', 'JonesMelf', 3, 'sdasd', '0000-00-00', 'available', 'new', '', '2025-05-09 16:25:42', '2025-05-12 04:57:03', 5, 5),
(11, 'E - 001', 'Portable Wielding Machine', 1, 'For wielding.', '2025-05-01', 'available', 'good', '0', '2025-05-12 05:11:34', '2025-05-12 05:18:31', 4, 4);

--
-- Triggers `equipment`
--
DELIMITER $$
CREATE TRIGGER `check_equipment_quantity_after_insert` AFTER INSERT ON `equipment` FOR EACH ROW BEGIN
    -- Check if quantity or available_quantity has fallen to 3 or below
    IF (NEW.quantity <= 3 OR NEW.available_quantity <= 3) AND NEW.status != 'retired' THEN
        -- Insert into notifications table
        INSERT INTO notifications (
            type, 
            title, 
            message,
            equipment_id,
            is_read,
            created_at
        ) VALUES (
            'low_quantity',
            'Low Equipment Quantity',
            CONCAT('Equipment "', NEW.name, '" (Code: ', NEW.equipment_code, ') has low quantity. ',
                   'Total: ', NEW.quantity, ', Available: ', NEW.available_quantity),
            NEW.equipment_id,
            0,
            NOW()
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `check_equipment_quantity_after_update` AFTER UPDATE ON `equipment` FOR EACH ROW BEGIN
    -- Check if quantity or available_quantity has fallen to 3 or below
    IF (NEW.quantity <= 3 OR NEW.available_quantity <= 3) AND NEW.status != 'retired' THEN
        -- Insert into notifications table
        INSERT INTO notifications (
            type, 
            title, 
            message,
            equipment_id,
            is_read,
            created_at
        ) VALUES (
            'low_quantity',
            'Low Equipment Quantity',
            CONCAT('Equipment "', NEW.name, '" (Code: ', NEW.equipment_code, ') has low quantity. ',
                   'Total: ', NEW.quantity, ', Available: ', NEW.available_quantity),
            NEW.equipment_id,
            0,
            NOW()
        );
    END IF;
END
$$
DELIMITER ;

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
  `resolved_by` varchar(100) DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(3, '23-00001', 'Bry', 'Bermudez', 'bermudez_bryan@plpasig.edu.ph', '$2y$10$HUveH9O4JkwOUZFsqCqX3OeB4itZZSsHDdS1CoJe4SR7swahMdPKG', 'student', 'General Service Office', '1234567890', 'BSIT - 2E', '2025-04-19 10:54:45', '2025-05-10 06:21:14', 'active', '2025-05-10 06:21:14'),
(5, '000', 'Jm', 'Peligro', 'peligro_johnmichael@plpasig.edu.ph', '$2y$10$znGJ8Iw7yKxySbCb4pIWm.yxYocjMWewEEtxuRWAstoL57sNSlIHa', 'admin', 'College of Computer Studies', '12345678', '', '2025-04-19 11:20:26', '2025-05-11 09:26:53', 'active', NULL),
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
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_issued_id` (`admin_issued_id`),
  ADD KEY `admin_received_id` (`admin_received_id`);

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
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `related_id` (`related_id`);

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
  MODIFY `borrowing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`),
  ADD CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `borrowings_ibfk_3` FOREIGN KEY (`admin_issued_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `borrowings_ibfk_4` FOREIGN KEY (`admin_received_id`) REFERENCES `users` (`user_id`);

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

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
