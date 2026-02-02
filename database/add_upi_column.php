<?php
require_once '../config/db_connect.php';

try {
    $pdo->exec("ALTER TABLE external_programs ADD COLUMN payment_upi VARCHAR(100) DEFAULT NULL AFTER payment_gateway");
    echo "Column 'payment_upi' added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>