<?php
require_once 'config/db_connect.php';

try {
    echo "Checking table 'registrations':\n";
    $stmt = $pdo->query("DESCRIBE registrations");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($columns);

    echo "\nChecking table 'events':\n";
    $stmt = $pdo->query("DESCRIBE events");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($columns);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>