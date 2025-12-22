<?php
require_once '../config/db.php';

try {
    // Add google_id column
    $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE DEFAULT NULL AFTER email");

    // Make password nullable (for google users)
    $pdo->exec("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");

    echo "Migration Successful: Added google_id and made password nullable.";
} catch (PDOException $e) {
    echo "Note: " . $e->getMessage();
}
?>