/* =========================================
   DATABASE CREATION
   ========================================= */

CREATE DATABASE IF NOT EXISTS hrms;
USE hrms;

/* =========================================
   COMPANIES TABLE
   Stores company profile & logo
   ========================================= */

CREATE TABLE companies (
    company_id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(150) NOT NULL,
    company_code VARCHAR(10) UNIQUE NOT NULL,
    logo_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/* =========================================
   ROLES TABLE
   Role-based access control
   ========================================= */

CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL
);

/* Default roles */
INSERT INTO roles (role_name)
VALUES ('ADMIN'), ('HR'), ('EMPLOYEE');

/* =========================================
   EMPLOYEES TABLE
   Stores employee personal details
   ========================================= */

CREATE TABLE employees (
    employee_id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(15) UNIQUE,
    date_of_joining DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE
);

/* =========================================
   USERS TABLE
   Authentication & authorization
   ========================================= */

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT UNIQUE,
    company_id INT NOT NULL,
    role_id INT NOT NULL,

    login_id VARCHAR(30) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,

    is_first_login BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE SET NULL,

    FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,

    FOREIGN KEY (role_id)
        REFERENCES roles(role_id)
);

/* =========================================
   LOGIN AUDIT TABLE (OPTIONAL BUT IMPRESSIVE)
   ========================================= */

CREATE TABLE login_audit (
    audit_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    status ENUM('SUCCESS', 'FAILED'),

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

/* =========================================
   INITIAL SYSTEM SETUP (SEED DATA)
   ========================================= */

/* Insert company (Sign Up page usage) */
INSERT INTO companies (company_name, company_code, logo_url)
VALUES ('Odoo India', 'OI', NULL);

/* Insert Admin employee */
INSERT INTO employees (
    company_id, first_name, last_name, email, phone, date_of_joining
)
VALUES (
    1, 'System', 'Admin', 'admin@odoo.com', '9999999999', CURDATE()
);

/* Insert Admin user (password must be hashed in backend) */
INSERT INTO users (
    employee_id, company_id, role_id,
    login_id, password_hash, is_first_login
)
VALUES (
    1, 1, 1,
    'OIADMIN0001',
    '$2b$12$examplehashedpasswordstring',
    FALSE
);
