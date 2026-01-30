-- Database Schema for MCA Mini Project: College Event Management System

DROP DATABASE IF EXISTS entryx;
CREATE DATABASE entryx;
USE entryx;

-- 1. Users Table
-- Supports all roles: Super Admin, Event Admin, Internal (Student/Staff), External (Guest)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'event_admin', 'security', 'internal', 'external') NOT NULL,
    college_id VARCHAR(50) DEFAULT NULL, -- For Internal Users
    id_proof VARCHAR(255) DEFAULT NULL,  -- For External Users (Path to uploaded file or ID number)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Events Table
-- Manages event details, capacity, and payment settings
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    poster_image VARCHAR(255) DEFAULT NULL,
    event_date DATETIME NOT NULL,
    venue VARCHAR(100) NOT NULL,
    capacity INT DEFAULT 100,
    type ENUM('internal', 'external', 'both') DEFAULT 'both',
    
    -- Payment Logic
    is_paid BOOLEAN DEFAULT FALSE,
    base_price DECIMAL(10, 2) DEFAULT 0.00,
    is_gst_enabled BOOLEAN DEFAULT FALSE, -- Only applicable if is_paid is TRUE
    gst_rate DECIMAL(5, 2) DEFAULT 18.00,
    
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    created_by INT, -- Event Admin ID
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- 3. Registrations Table
-- Links users to events, handles payment status and QR generation
CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    
    -- Payment Details
    payment_status ENUM('pending', 'completed', 'failed', 'free') DEFAULT 'pending',
    base_amount DECIMAL(10, 2) DEFAULT 0.00,
    gst_amount DECIMAL(10, 2) DEFAULT 0.00,
    total_amount DECIMAL(10, 2) DEFAULT 0.00,
    transaction_id VARCHAR(100) DEFAULT NULL,
    
    -- QR Code Logic
    qr_code VARCHAR(255) NOT NULL UNIQUE, -- The unique string stored in the QR
    
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (user_id, event_id) -- One registration per event per user
);

-- 4. Attendance Logs
-- Tracks entry and exit times for security and analytics
CREATE TABLE attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    exit_time TIMESTAMP NULL, 
    status ENUM('inside', 'exited') DEFAULT 'inside',
    
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
);

-- 5. Results Table
-- structured storage for event winners
CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    winner_name VARCHAR(100) NOT NULL,
    runner_up_name VARCHAR(100) NOT NULL,
    consolation_prize VARCHAR(100) DEFAULT NULL,
    description TEXT,
    
    published_by INT, -- Event Admin ID
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (published_by) REFERENCES users(id)
);

-- 6. Seed Default Super Admin
INSERT INTO users (name, email, phone, password, role) VALUES 
('Super Admin', 'administrator@mca.ajce.in', '1234567890', '$2y$10$fVunHVs37vY.E6Y7.rU4uOq0yB3J7o4K6yH.7J6XbQn7I6kU7V7yO', 'super_admin');
-- Password is 'admin@15214' (bcrypt hash)
