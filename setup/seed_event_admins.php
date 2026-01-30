<?php
require_once '../config/db_connect.php';

echo "<h1>Seeding Event Admins...</h1>";

$admins = [
    [
        'name' => 'Event Admin 1',
        'email' => 'event1@entryx.com',
        'phone' => '9876543211',
        // Password is 'password'
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'role' => 'event_admin'
    ],
    [
        'name' => 'Event Admin 2',
        'email' => 'event2@entryx.com',
        'phone' => '9876543212',
        // Password is 'password'
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'role' => 'event_admin'
    ]
];

try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (:name, :email, :phone, :password, :role)");

    foreach ($admins as $admin) {
        // Check if exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$admin['email']]);

        if ($check->rowCount() == 0) {
            $stmt->execute($admin);
            echo "<p style='color: green;'>✅ Created: <b>{$admin['email']}</b> / password</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Exists: <b>{$admin['email']}</b></p>";
        }
    }

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Error: " . $e->getMessage() . "</h2>";
}
?>