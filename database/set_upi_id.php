<?php
require_once '../config/db_connect.php';

try {
    $stmt = $pdo->prepare("UPDATE external_programs SET payment_upi = 'college@okaxis', is_paid = 1 WHERE is_active = 1");
    $stmt->execute();
    echo "Success: UPI Id set to 'college@okaxis' for active program.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>