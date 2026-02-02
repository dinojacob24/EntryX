<?php
require_once '../config/db_connect.php';

// Fetch all active programs to see what's going on
$stmt = $pdo->query("SELECT id, program_name, is_active, payment_upi FROM external_programs WHERE is_active = 1");
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "--- ACTIVE PROGRAMS ---\n";
print_r($programs);

// Check if maybe the user is hitting a specific ID in URL?
// Logic in register.php usually fetches 'is_active = 1' LIMIT 1
?>