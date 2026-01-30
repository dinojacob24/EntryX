<?php
/**
 * Superadmin Seeder
 * Creates the initial Superadmin and Security accounts.
 */
require_once __DIR__ . '/../config/db_connect.php';

try {
    $superadmin = [
        'name' => 'John Doe (Superadmin)',
        'email' => 'admin@entryx.com',
        'password' => password_hash('Admin@123', PASSWORD_BCRYPT),
        'role' => 'super_admin'
    ];

    $security = [
        'name' => 'Main Gate Security',
        'email' => 'security@entryx.com',
        'password' => password_hash('Security@123', PASSWORD_BCRYPT),
        'role' => 'security'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");

    // Create Superadmin
    $stmt->execute([$superadmin['name'], $superadmin['email'], $superadmin['password'], $superadmin['role']]);

    // Create Security
    $stmt->execute([$security['name'], $security['email'], $security['password'], $security['role']]);

    echo "Initial administration accounts created successfully!\n";
    echo "Superadmin: admin@entryx.com / Admin@123\n";
    echo "Security: security@entryx.com / Security@123\n";

} catch (Exception $e) {
    die("Seeding failed: " . $e->getMessage());
}
