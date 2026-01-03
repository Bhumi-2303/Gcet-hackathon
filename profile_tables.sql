-- Profile tables for HRMS
-- Run this SQL to add profile-related tables

USE hrms;

-- Employee Profile Details Table
CREATE TABLE IF NOT EXISTS `employee_profiles` (
  `profile_id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `job_love` text DEFAULT NULL,
  `interests_hobbies` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `manager_id` int DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_profiles_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `employee_profiles_ibfk_2` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Employee Skills Table
CREATE TABLE IF NOT EXISTS `employee_skills` (
  `skill_id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`skill_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_skills_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Employee Certifications Table
CREATE TABLE IF NOT EXISTS `employee_certifications` (
  `certification_id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `certification_name` varchar(200) NOT NULL,
  `issuing_organization` varchar(150) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `certificate_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`certification_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_certifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Employee Salary Information Table (Admin only)
CREATE TABLE IF NOT EXISTS `employee_salary` (
  `salary_id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`salary_id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_salary_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

