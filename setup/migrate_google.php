<?php
require_once '../config/db_connect.php';

try {
    // Add google_id column
    $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL DEFAULT NULL UNIQUE AFTER email");
    echo "Added google_id column.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") === false) {
        echo "Error adding google_id: " . $e->getMessage() . "<br>";
    } else {
        echo "google_id column already exists.<br>";
    }
}

try {
    // Add password reset columns
    $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL DEFAULT NULL AFTER google_id");
    $pdo->exec("ALTER TABLE users ADD COLUMN reset_expiry DATETIME NULL DEFAULT NULL AFTER reset_token");
    echo "Added reset_token and reset_expiry columns.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") === false) {
        echo "Error adding reset columns: " . $e->getMessage() . "<br>";
    } else {
        echo "Reset columns already exist.<br>";
    }
}

// Make password nullable for Google users
try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");
    echo "Modified password column to be nullable.<br>";
} catch (PDOException $e) {
    echo "Error modifying password column: " . $e->getMessage() . "<br>";
}

echo "<h3>Migration Complete</h3>";
?>