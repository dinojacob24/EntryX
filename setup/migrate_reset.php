<?php
require_once __DIR__ . '/../config/db_connect.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL, ADD COLUMN reset_expiry DATETIME DEFAULT NULL;");
    echo "Columns added successfully!";
} catch (PDOException $e) {
    echo "Error or columns already exist: " . $e->getMessage();
}
?>