<?php
require_once 'config/db_connect.php';
try {
    $stmt = $pdo->query("DESCRIBE events");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>