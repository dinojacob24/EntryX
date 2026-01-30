<?php
require_once '../config/db_connect.php';

$newEmail = 'administrator@mca.ajce.in';
$newPassword = password_hash('admin@15214', PASSWORD_DEFAULT);
$role = 'super_admin';

try {
    // Check if the administrator already exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$newEmail]);

    if ($check->rowCount() > 0) {
        // Update existing admin
        $stmt = $pdo->prepare("UPDATE users SET password = ?, role = 'super_admin' WHERE email = ?");
        $stmt->execute([$newPassword, $newEmail]);
        echo "<h1>Credentials Updated!</h1>";
        echo "<p>Updated password for existing account: <b>$newEmail</b></p>";
    } else {
        // Create new admin
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Super Admin', $newEmail, $newPassword, $role]);
        echo "<h1>New Admin Created!</h1>";
        echo "<p>Email: <b>$newEmail</b><br>Password: <b>admin@15214</b></p>";
    }

    echo "<br><a href='../pages/login.php'>Go to Login</a>";

} catch (PDOException $e) {
    echo "<h1>Error</h1><p>" . $e->getMessage() . "</p>";
}
?>