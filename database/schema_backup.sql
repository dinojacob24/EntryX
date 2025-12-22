CREATE DATABASE IF NOT EXISTS college_event_db;
USE college_event_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'internal', 'external', 'gatekeeper', 'faculty') DEFAULT 'external',
    student_id VARCHAR(50), -- Only for internals
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Events Table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    venue VARCHAR(100),
    base_fee DECIMAL(10, 2) DEFAULT 0.00,
    has_gst BOOLEAN DEFAULT TRUE,
    gst_rate DECIMAL(5, 2) DEFAULT 18.00,
    allow_internal BOOLEAN DEFAULT TRUE,
    allow_external BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Registrations Table
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    qr_token VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('registered', 'cancelled') DEFAULT 'registered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (event_id) REFERENCES events(id)
);

-- Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    entry_time TIMESTAMP NULL,
    exit_time TIMESTAMP NULL,
    status ENUM('outside', 'inside', 'completed') DEFAULT 'outside',
    FOREIGN KEY (registration_id) REFERENCES registrations(id)
);

-- Results Table
CREATE TABLE IF NOT EXISTS results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT, -- Could be HTML table or JSON
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id)
);
