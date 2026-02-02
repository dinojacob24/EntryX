<?php
require_once '../config/db_connect.php';

try {
    $sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) DEFAULT NULL AFTER college_organization";
    $pdo->exec($sql);
    echo "Success: department column added.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>