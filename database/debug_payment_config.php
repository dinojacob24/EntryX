<?php
require_once '../config/db_connect.php';

$stmt = $pdo->query("SELECT id, program_name, is_active, is_paid, payment_upi, registration_fee FROM external_programs WHERE is_active = 1");
$program = $stmt->fetch(PDO::FETCH_ASSOC);

echo "--- ACTIVE PROGRAM DEBUG ---\n";
if ($program) {
    print_r($program);
} else {
    echo "No active program found.\n";
}

echo "\n--- ALL PROGRAMS ---\n";
$stmt = $pdo->query("SELECT id, program_name, is_active, is_paid, payment_upi FROM external_programs");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>