-- External Programs Feature Migration
-- This adds support for super admin controlled external registration programs

USE entryx;

-- Add external_registration_enabled flag to system settings
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default setting for external registration
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('external_registration_enabled', '0', 'Controls whether external user registration is visible on landing page'),
('current_external_program_name', '', 'Name of the current external program/event'),
('current_external_program_description', '', 'Description shown on external registration form')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- Add program_type to events table to distinguish between regular and external programs
ALTER TABLE events 
ADD COLUMN IF NOT EXISTS program_type ENUM('regular', 'external_program', 'both') DEFAULT 'regular' AFTER type,
ADD COLUMN IF NOT EXISTS is_external_registration_open BOOLEAN DEFAULT 0 AFTER program_type,
ADD COLUMN IF NOT EXISTS external_program_details TEXT AFTER is_external_registration_open;

-- Create external_programs table for better management
CREATE TABLE IF NOT EXISTS external_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_name VARCHAR(255) NOT NULL,
    program_description TEXT,
    registration_form_fields JSON,
    is_active BOOLEAN DEFAULT 1,
    start_date DATE,
    end_date DATE,
    max_participants INT DEFAULT 500,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add external_program_id to users table to track which program they registered through
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS external_program_id INT AFTER google_id,
ADD COLUMN IF NOT EXISTS registration_source ENUM('direct', 'external_program', 'google_oauth') DEFAULT 'direct' AFTER external_program_id,
ADD FOREIGN KEY (external_program_id) REFERENCES external_programs(id) ON DELETE SET NULL;

-- Create activity log for admin actions
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    action_description TEXT,
    affected_table VARCHAR(100),
    affected_record_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Success message
SELECT 'External Programs feature tables created successfully!' AS MESSAGE;
