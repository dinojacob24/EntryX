<?php
require_once '../config/db_connect.php';

try {
    $sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(50) DEFAULT NULL AFTER payment_method";
    $pdo->exec($sql);
    echo "Success: transaction_id column added.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>