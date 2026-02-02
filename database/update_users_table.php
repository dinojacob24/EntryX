<?php
require_once __DIR__ . '/../config/db_connect.php';

try {
    // 1. Add college_organization to users table
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS college_organization VARCHAR(255) AFTER college_id");

    // 2. Add payment_method to users table for tracking
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) AFTER payment_status");

    echo "Migration Successful: Added college_organization and payment_method to users table.\n";
} catch (PDOException $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
}
?>