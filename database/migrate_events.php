<?php
require_once 'config/db_connect.php';

try {
    echo "Starting database migration for 'events' table...\n";

    // 1. Check for 'title' vs 'name'
    $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE 'title'");
    if ($stmt->fetch()) {
        $pdo->exec("ALTER TABLE events CHANGE title name VARCHAR(200) NOT NULL");
        echo "Renamed 'title' to 'name'.\n";
    }

    // 2. Check for 'base_fee' vs 'base_price'
    $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE 'base_fee'");
    if ($stmt->fetch()) {
        $pdo->exec("ALTER TABLE events CHANGE base_fee base_price DECIMAL(10, 2) DEFAULT 0.00");
        echo "Renamed 'base_fee' to 'base_price'.\n";
    }

    // 3. Check for 'has_gst' vs 'is_gst_enabled'
    $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE 'has_gst'");
    if ($stmt->fetch()) {
        $pdo->exec("ALTER TABLE events CHANGE has_gst is_gst_enabled BOOLEAN DEFAULT TRUE");
        echo "Renamed 'has_gst' to 'is_gst_enabled'.\n";
    }

    // 4. Add missing columns
    $columnsToAdd = [
        'poster_image' => "VARCHAR(255) AFTER name",
        'capacity' => "INT DEFAULT 100 AFTER venue",
        'type' => "ENUM('internal', 'external', 'both') DEFAULT 'both' AFTER capacity",
        'program_type' => "VARCHAR(50) AFTER type", // For internal/external classification
        'is_paid' => "BOOLEAN DEFAULT FALSE AFTER program_type",
        'payment_upi' => "VARCHAR(255) AFTER gst_rate",
        'gst_target' => "ENUM('externals_only', 'both') DEFAULT 'both' AFTER payment_upi",
        'status' => "ENUM('active', 'cancelled', 'completed') DEFAULT 'active' AFTER gst_target",
        'created_by' => "INT AFTER status"
    ];

    foreach ($columnsToAdd as $col => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE '$col'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE events ADD $col $definition");
            echo "Added column '$col'.\n";
        }
    }

    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
}
?>