<?php
require_once '../config/db_connect.php';

try {
    $sql = "
    ALTER TABLE users
    ADD COLUMN IF NOT EXISTS qr_token VARCHAR(255) DEFAULT NULL AFTER payment_method;
    ";

    $pdo->exec($sql);
    echo "Migration Successful: qr_token column added to users table.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>