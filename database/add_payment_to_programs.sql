-- Add Payment and GST Support to External Programs
-- This migration adds payment functionality to external programs

USE entryx;

-- Add payment-related columns to external_programs table
ALTER TABLE external_programs 
ADD COLUMN IF NOT EXISTS is_paid BOOLEAN DEFAULT 0 AFTER max_participants,
ADD COLUMN IF NOT EXISTS registration_fee DECIMAL(10,2) DEFAULT 0.00 AFTER is_paid,
ADD COLUMN IF NOT EXISTS is_gst_enabled BOOLEAN DEFAULT 0 AFTER registration_fee,
ADD COLUMN IF NOT EXISTS gst_rate DECIMAL(5,2) DEFAULT 18.00 AFTER is_gst_enabled,
ADD COLUMN IF NOT EXISTS total_amount_with_gst DECIMAL(10,2) GENERATED ALWAYS AS (
    CASE 
        WHEN is_gst_enabled = 1 THEN registration_fee + (registration_fee * gst_rate / 100)
        ELSE registration_fee
    END
) STORED AFTER gst_rate,
ADD COLUMN IF NOT EXISTS payment_gateway VARCHAR(50) DEFAULT 'razorpay' AFTER total_amount_with_gst,
ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'INR' AFTER payment_gateway;

-- Create payments table to track all transactions
CREATE TABLE IF NOT EXISTS program_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    program_id INT NOT NULL,
    order_id VARCHAR(100) UNIQUE NOT NULL,
    payment_id VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    gst_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'INR',
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_gateway VARCHAR(50) DEFAULT 'razorpay',
    transaction_id VARCHAR(100),
    payment_response JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES external_programs(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_program_id (program_id),
    INDEX idx_order_id (order_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add payment_id to users table to track if they've paid for external program
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS program_payment_id INT AFTER external_program_id,
ADD COLUMN IF NOT EXISTS payment_status ENUM('not_required', 'pending', 'completed', 'failed') DEFAULT 'not_required' AFTER program_payment_id,
ADD FOREIGN KEY (program_payment_id) REFERENCES program_payments(id) ON DELETE SET NULL;

-- Create payment settings table for gateway configuration
CREATE TABLE IF NOT EXISTS payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_name VARCHAR(50) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT 0,
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    webhook_secret VARCHAR(255),
    test_mode BOOLEAN DEFAULT 1,
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gateway_name (gateway_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default Razorpay settings (placeholder - admin needs to configure)
INSERT INTO payment_settings (gateway_name, is_active, test_mode, settings) VALUES
('razorpay', 0, 1, '{"display_name": "Razorpay", "supported_currencies": ["INR"], "description": "Accept payments via Razorpay"}')
ON DUPLICATE KEY UPDATE gateway_name=gateway_name;

-- Add GST breakdown table for detailed tax records
CREATE TABLE IF NOT EXISTS payment_gst_breakdown (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    base_amount DECIMAL(10,2) NOT NULL,
    cgst_rate DECIMAL(5,2) DEFAULT 9.00,
    sgst_rate DECIMAL(5,2) DEFAULT 9.00,
    igst_rate DECIMAL(5,2) DEFAULT 18.00,
    cgst_amount DECIMAL(10,2) DEFAULT 0.00,
    sgst_amount DECIMAL(10,2) DEFAULT 0.00,
    igst_amount DECIMAL(10,2) DEFAULT 0.00,
    total_gst DECIMAL(10,2) NOT NULL,
    is_interstate BOOLEAN DEFAULT 0,
    gstin VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES program_payments(id) ON DELETE CASCADE,
    INDEX idx_payment_id (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Success message
SELECT 'Payment and GST functionality added successfully!' AS MESSAGE;
SELECT 'External programs can now have registration fees with GST calculation' AS INFO;
