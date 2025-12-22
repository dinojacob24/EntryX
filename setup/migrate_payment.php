<?php
require_once '../config/db.php';

try {
    $pdo->exec("ALTER TABLE registrations ADD COLUMN payment_status ENUM('pending', 'paid') DEFAULT 'pending' AFTER status");
    echo "Migration Successful: Added payment_status to registrations table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>