-- ============================================
-- AC SERVICE CRM - COMPLETE DATABASE SCHEMA
-- Run this file in phpMyAdmin or MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS ac_crm;
USE ac_crm;

-- 1. Admin Users
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Customers
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    ac_type ENUM('Split','Window','Cassette','Tower','Portable') DEFAULT 'Split',
    ac_brand VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Technicians
CREATE TABLE technicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    area VARCHAR(100),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Complaints
CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    technician_id INT DEFAULT NULL,
    problem TEXT NOT NULL,
    ai_summary TEXT DEFAULT NULL,
    priority ENUM('low','normal','urgent') DEFAULT 'normal',
    status ENUM('open','in_progress','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL
);

-- 5. Complaint Activity Log
CREATE TABLE complaint_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    done_by VARCHAR(100) DEFAULT 'Admin',
    done_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE
);

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Default admin: username = admin, password = admin123
INSERT INTO admins (username, password, full_name)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

-- Sample Technicians
INSERT INTO technicians (name, phone, area) VALUES
('Rajesh Kumar', '9876543210', 'North Zone'),
('Suresh Babu', '9876543211', 'South Zone'),
('Anil Sharma', '9876543212', 'East Zone'),
('Vikram Singh', '9876543213', 'West Zone');

-- ============================================
-- NOTE: Default password is "password"
-- Change it after first login!
-- ============================================
