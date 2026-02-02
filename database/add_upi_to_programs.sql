-- Add UPI and QR Payment Support to External Programs
USE entryx;

ALTER TABLE external_programs
ADD COLUMN IF NOT EXISTS payment_upi VARCHAR(255) DEFAULT NULL AFTER currency,
ADD COLUMN IF NOT EXISTS payment_qr_path VARCHAR(255) DEFAULT NULL AFTER payment_upi;

SELECT 'UPI and QR Payment support added successfully!' AS MESSAGE;
