-- ENTRY X Database Setup Script
-- Run this in phpMyAdmin to initialize the database

-- Create Database
CREATE DATABASE IF NOT EXISTS entryx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE entryx;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255),
    role ENUM('super_admin', 'event_admin', 'internal', 'external') DEFAULT 'internal',
    college_id VARCHAR(50),
    phone VARCHAR(20),
    id_proof VARCHAR(255),
    google_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events Table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    venue VARCHAR(255),
    capacity INT DEFAULT 100,
    is_paid BOOLEAN DEFAULT 0,
    price DECIMAL(10,2) DEFAULT 0.00,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event_date (event_date),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registrations Table
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    qr_token VARCHAR(255) UNIQUE NOT NULL,
    attended BOOLEAN DEFAULT 0,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attended_at TIMESTAMP NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, user_id),
    INDEX idx_qr_token (qr_token),
    INDEX idx_attended (attended)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Results Table
CREATE TABLE IF NOT EXISTS results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    winner_name VARCHAR(255) NOT NULL,
    runner_up_name VARCHAR(255),
    consolation_prize VARCHAR(255),
    description TEXT,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password Resets Table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Sample Super Admin
-- Password: Admin@123 (hashed with bcrypt)
INSERT INTO users (name, email, password, role) VALUES
('System Administrator', 'admin@entryx.system', '$2y$12$LQv3c1yycEPICJhhPp8hC.AHXz7KpM3LWZy6o.1B7z.5YqJ8yHg7W', 'super_admin')
ON DUPLICATE KEY UPDATE id=id;

-- Insert Sample Event Admin
-- Password: EventAdmin@123
INSERT INTO users (name, email, password, role) VALUES
('Event Coordinator', 'coordinator@entryx.system', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'event_admin')
ON DUPLICATE KEY UPDATE id=id;

-- Insert Sample Events
INSERT INTO events (name, description, event_date, venue, capacity, is_paid, price, created_by) VALUES
('Tech Summit 2026', 'Annual technology conference featuring industry leaders and cutting-edge innovations.', '2026-03-15', 'Main Auditorium', 500, 0, 0.00, 1),
('Cultural Fest', 'Celebrate diversity with music, dance, and art performances from around the world.', '2026-04-20', 'Open Ground', 1000, 1, 50.00, 1),
('Hackathon Pro', '48-hour coding marathon with exciting prizes and mentorship opportunities.', '2026-05-10', 'Computer Lab A', 200, 1, 100.00, 2)
ON DUPLICATE KEY UPDATE id=id;

-- Success Message
SELECT 'Database setup complete! You can now login with:' AS MESSAGE;
SELECT 'Admin: admin@entryx.system / Admin@123' AS CREDENTIALS;
SELECT 'Event Admin: coordinator@entryx.system / EventAdmin@123' AS CREDENTIALS;
