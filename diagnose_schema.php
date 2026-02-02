<?php
require_once 'config/db_connect.php';

function checkTable($pdo, $tableName)
{
    echo "\n--- Columns in '$tableName' ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "{$col['Field']} - {$col['Type']}\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

checkTable($pdo, 'events');
checkTable($pdo, 'external_programs');
checkTable($pdo, 'payment_settings');
checkTable($pdo, 'program_payments');
checkTable($pdo, 'registrations');
checkTable($pdo, 'users');

echo "\n--- Payment Settings Content ---\n";
try {
    $stmt = $pdo->query("SELECT * FROM payment_settings");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>