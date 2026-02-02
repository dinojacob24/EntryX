<?php
require_once 'config/db_connect.php';

try {
    // 1. Add target_type to program_payments
    $pdo->exec("ALTER TABLE program_payments ADD COLUMN IF NOT EXISTS target_type VARCHAR(20) DEFAULT 'program' AFTER program_id");
    echo "Added target_type to program_payments\n";

    // 2. Ensure registrations.payment_status enum includes 'completed' (wait, diagnostics said it already has it)
    // Actually, diagnostics said: registrations.payment_status is enum('pending','completed','failed','free')
    // So that's fine.

    // 3. Check users table columns for consistency
    // ALTER TABLE users MODIFY COLUMN payment_status ENUM('not_required', 'pending', 'completed', 'failed') DEFAULT 'not_required';

    echo "Schema fix completed\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>