<?php
require_once 'config/db_connect.php';

try {
    $stmt = $pdo->query("SELECT * FROM external_programs WHERE is_active = 1");
    $program = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($program) {
        echo "Active Program ID: " . $program['id'] . "\n";
        echo "Program Name: " . $program['program_name'] . "\n";
        echo "Payment UPI: [" . $program['payment_upi'] . "]\n";
        echo "Is Paid: " . $program['is_paid'] . "\n";
    } else {
        echo "No active program found.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>