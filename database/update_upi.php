<?php
require_once '../config/db_connect.php';

try {
    $stmt = $pdo->prepare("UPDATE external_programs SET payment_upi = 'dinojacob24@okaxis' WHERE is_active = 1");
    $stmt->execute();
    echo "Success: UPI Id updated to 'dinojacob24@okaxis'.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>