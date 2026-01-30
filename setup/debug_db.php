<?php
require_once __DIR__ . '/../config/db_connect.php';

try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    echo "Structure of 'users' table:\n";
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error describing users: " . $e->getMessage();
}
?>