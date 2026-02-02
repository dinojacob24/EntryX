-- Update active external program with UPI ID for payment
UPDATE external_programs 
SET payment_upi = 'dinojacob24@okaxis'
WHERE is_active = 1;

-- Verify the update
SELECT id, program_name, is_paid, payment_upi, total_amount_with_gst 
FROM external_programs 
WHERE is_active = 1;
