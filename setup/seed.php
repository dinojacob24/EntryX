<?php
require_once '../config/db.php';

try {
    $password = password_hash('admin@15214', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=id");
    $stmt->execute(['Admin User', 'administrator@mca.ajce.in', $password, 'super_admin']);

    // Create Gatekeeper
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=id");
    $stmt->execute(['Gate Keeper', 'gate@college.edu', $password, 'gatekeeper']);

    echo "<h1>Setup Complete</h1>";
    echo "<p>Admin Created: administrator@mca.ajce.in / admin@15214</p>";
    echo "<p>Gatekeeper Created: gate@college.edu / password123</p>";
    echo "<a href='/Project/index.php'>Go to Home</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>