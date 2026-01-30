<?php
require_once '../config/db_connect.php';

try {
    echo "<h1>Migration: Adding Security Role</h1>";

    // Check if column exists (to get current definition)
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($column) {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'event_admin', 'security', 'internal', 'external') NOT NULL");
        echo "<p style='color: green;'>✅ Successfully added 'security' to users.role ENUM.</p>";

        // Create Default Security Account
        $secEmail = 'security@mca.ajce.in';
        $secPass = password_hash('security@15214', PASSWORD_DEFAULT);

        $checkSec = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkSec->execute([$secEmail]);

        if ($checkSec->rowCount() == 0) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'security')");
            $stmt->execute(['Security Officer', $secEmail, $secPass]);
            echo "<p style='color: green;'>✅ Created Default Security Account: <b>$secEmail</b> / security@15214</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Security account <b>$secEmail</b> already exists.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Column 'role' not found in 'users' table.</p>";
    }

    echo "<br><a href='../index.php'>Go to Home</a>";

} catch (PDOException $e) {
    echo "<h2>Error</h2><p>" . $e->getMessage() . "</p>";
}
?>