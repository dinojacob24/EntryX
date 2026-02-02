<?php
require_once '../config/db_connect.php';
try {
    $stmt = $pdo->prepare("UPDATE external_programs SET payment_upi = 'college@okaxis', is_paid = 1, registration_fee = 499.00 WHERE is_active = 1");
    $stmt->execute();
    echo "Success: UPI ID updated.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>