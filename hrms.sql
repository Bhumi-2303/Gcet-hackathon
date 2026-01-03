-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 03, 2026 at 12:12 PM
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
-- Database: `hrms`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `type` enum('IN','OUT') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `employee_id`, `type`, `created_at`, `ip_address`) VALUES
(1, 2, 'IN', '2026-01-03 10:18:35', '::1'),
(2, 2, 'OUT', '2026-01-03 10:18:39', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `company_id` int(11) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `company_code` varchar(10) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`company_id`, `company_name`, `company_code`, `logo_url`, `created_at`) VALUES
(1, 'Odoo India', 'OI', NULL, '2026-01-03 07:27:21'),
(2, 'abc', 'CMP', 'uploads/logo_6958e8dd5e008.jpg', '2026-01-03 10:01:01');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `date_of_joining` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `company_id`, `first_name`, `last_name`, `email`, `phone`, `gender`, `date_of_joining`, `created_at`) VALUES
(1, 1, 'System', 'Admin', 'admin@odoo.com', '9999999999', NULL, '2026-01-03', '2026-01-03 07:27:21'),
(2, 2, 'studed', '', 'abc@gmail.com', '1234567890', NULL, '2026-01-03', '2026-01-03 10:01:01'),
(3, 2, 'dhruv', 'ribadiya', 'hello@gmail.com', '2345678901', NULL, '2026-01-01', '2026-01-03 10:21:13');

-- --------------------------------------------------------

--
-- Table structure for table `employee_certifications`
--

CREATE TABLE `employee_certifications` (
  `certification_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `certification_name` varchar(200) NOT NULL,
  `issuing_organization` varchar(150) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `certificate_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_profiles`
--

CREATE TABLE `employee_profiles` (
  `profile_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `job_love` text DEFAULT NULL,
  `interests_hobbies` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_salary`
--

CREATE TABLE `employee_salary` (
  `salary_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `monthly_wage` decimal(10,2) NOT NULL DEFAULT 0.00,
  `yearly_wage` decimal(10,2) NOT NULL DEFAULT 0.00,
  `working_days_per_week` decimal(3,1) DEFAULT NULL,
  `break_time_hours` decimal(4,2) DEFAULT NULL,
  `basic_salary` decimal(10,2) DEFAULT 0.00,
  `basic_salary_percent` decimal(5,2) DEFAULT 50.00,
  `hra` decimal(10,2) DEFAULT 0.00,
  `hra_percent` decimal(5,2) DEFAULT 50.00,
  `standard_allowance` decimal(10,2) DEFAULT 0.00,
  `standard_allowance_percent` decimal(5,2) DEFAULT 16.67,
  `performance_bonus` decimal(10,2) DEFAULT 0.00,
  `performance_bonus_percent` decimal(5,2) DEFAULT 8.33,
  `lta` decimal(10,2) DEFAULT 0.00,
  `lta_percent` decimal(5,2) DEFAULT 8.33,
  `fixed_allowance` decimal(10,2) DEFAULT 0.00,
  `pf_employee` decimal(10,2) DEFAULT 0.00,
  `pf_employee_percent` decimal(5,2) DEFAULT 12.00,
  `pf_employer` decimal(10,2) DEFAULT 0.00,
  `pf_employer_percent` decimal(5,2) DEFAULT 12.00,
  `professional_tax` decimal(10,2) DEFAULT 200.00,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_skills`
--

CREATE TABLE `employee_skills` (
  `skill_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_audit`
--

CREATE TABLE `login_audit` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('SUCCESS','FAILED') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_audit`
--

INSERT INTO `login_audit` (`audit_id`, `user_id`, `login_time`, `ip_address`, `status`) VALUES
(1, NULL, '2026-01-03 10:01:03', '::1', 'FAILED'),
(2, 2, '2026-01-03 10:01:15', '::1', 'SUCCESS'),
(3, 2, '2026-01-03 10:19:28', '::1', 'SUCCESS'),
(4, 3, '2026-01-03 10:22:03', '::1', 'SUCCESS'),
(5, 3, '2026-01-03 10:27:28', '::1', 'SUCCESS'),
(6, 2, '2026-01-03 10:27:39', '::1', 'SUCCESS'),
(7, 2, '2026-01-03 10:59:11', '::1', 'SUCCESS');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'ADMIN'),
(3, 'EMPLOYEE'),
(2, 'HR');

-- --------------------------------------------------------

--
-- Table structure for table `time_off`
--

CREATE TABLE `time_off` (
  `time_off_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `type` enum('Paid Time Off','Sick Leave','Unpaid Leave') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `attachment_url` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `login_id` varchar(30) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_first_login` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `employee_id`, `company_id`, `role_id`, `login_id`, `password_hash`, `is_first_login`, `is_active`, `last_login`, `created_at`) VALUES
(1, 1, 1, 1, 'OIADMIN0001', '$2b$12$examplehashedpasswordstring', 0, 1, NULL, '2026-01-03 07:27:21'),
(2, 2, 2, 1, 'CMPADMIN0002', '$2y$10$IW0kEKAvxz9N5ruj5DKvjugSxU.frJ704zpRg7u1/nHXFQdcFh94y', 1, 1, '2026-01-03 10:59:11', '2026-01-03 10:01:01'),
(3, 3, 2, 3, 'CMPEM0003', '$2y$10$7b0uP5qX90EKOkUtbdHCFeOmHIM1P9TWh5LU6XMf/Fw002Jb4PzE.', 1, 1, '2026-01-03 10:27:28', '2026-01-03 10:21:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`company_id`),
  ADD UNIQUE KEY `company_code` (`company_code`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `employee_certifications`
--
ALTER TABLE `employee_certifications`
  ADD PRIMARY KEY (`certification_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_profiles`
--
ALTER TABLE `employee_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `employee_profiles_ibfk_2` (`manager_id`);

--
-- Indexes for table `employee_salary`
--
ALTER TABLE `employee_salary`
  ADD PRIMARY KEY (`salary_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD PRIMARY KEY (`skill_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `login_audit`
--
ALTER TABLE `login_audit`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `time_off`
--
ALTER TABLE `time_off`
  ADD PRIMARY KEY (`time_off_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `status` (`status`),
  ADD KEY `start_date` (`start_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `login_id` (`login_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employee_certifications`
--
ALTER TABLE `employee_certifications`
  MODIFY `certification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_profiles`
--
ALTER TABLE `employee_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_salary`
--
ALTER TABLE `employee_salary`
  MODIFY `salary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_skills`
--
ALTER TABLE `employee_skills`
  MODIFY `skill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_audit`
--
ALTER TABLE `login_audit`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `time_off`
--
ALTER TABLE `time_off`
  MODIFY `time_off_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_certifications`
--
ALTER TABLE `employee_certifications`
  ADD CONSTRAINT `employee_certifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_profiles`
--
ALTER TABLE `employee_profiles`
  ADD CONSTRAINT `employee_profiles_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_profiles_ibfk_2` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_salary`
--
ALTER TABLE `employee_salary`
  ADD CONSTRAINT `employee_salary_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD CONSTRAINT `employee_skills_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `login_audit`
--
ALTER TABLE `login_audit`
  ADD CONSTRAINT `login_audit_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `time_off`
--
ALTER TABLE `time_off`
  ADD CONSTRAINT `time_off_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;