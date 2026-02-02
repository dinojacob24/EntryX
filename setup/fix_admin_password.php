<?php
require_once __DIR__ . '/../config/db_connect.php';

$email = 'admin@entryx.com';
$password = 'password';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'super_admin'");
    $stmt->execute([$hashedPassword, $email]);

    if ($stmt->rowCount() > 0) {
        echo "Password updated successfully for $email\n";
    } else {
        // Try to insert if it doesn't exist (though it should)
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role) VALUES (?, ?, ?, 'super_admin')");
        $stmt->execute(['Super Admin', $email, $hashedPassword]);
        if ($stmt->rowCount() > 0) {
            echo "Super Admin account created with email $email and password $password\n";
        } else {
            echo "Failed to update or create user. Maybe already exists with different role?\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
