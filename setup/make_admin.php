<?php
require_once '../config/db_connect.php';

$email = 'demo.student@gmail.com'; // The demo user
$action = $_GET['action'] ?? 'promote';

if ($action === 'promote') {
    $stmt = $pdo->prepare("UPDATE users SET role = 'super_admin' WHERE email = ?");
    $stmt->execute([$email]);
    echo "Promoted $email to super_admin.";
} else {
    $stmt = $pdo->prepare("UPDATE users SET role = 'external' WHERE email = ?");
    $stmt->execute([$email]);
    echo "Reverted $email to external.";
}
?>