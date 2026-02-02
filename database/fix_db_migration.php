<?php
require_once '../config/db_connect.php';

try {
    $sql = "
    ALTER TABLE external_programs
    ADD COLUMN IF NOT EXISTS payment_upi VARCHAR(255) DEFAULT NULL AFTER currency,
    ADD COLUMN IF NOT EXISTS payment_qr_path VARCHAR(255) DEFAULT NULL AFTER payment_upi;
    ";

    $pdo->exec($sql);
    echo "Migration Successful: Columns added.\n";

    // Now set the test data
    $stmt = $pdo->prepare("UPDATE external_programs SET payment_upi = 'college@okaxis', is_paid = 1, registration_fee = 499.00 WHERE is_active = 1");
    $stmt->execute();
    echo "Success: UPI Id Updated.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>