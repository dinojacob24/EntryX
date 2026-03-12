<?php
require_once __DIR__ . '/../config/db_connect.php';

try {
    echo "Starting migration for Group Registration support...\n";

    // 1. Update 'events' table
    $eventsColumns = [
        'is_group_event' => "TINYINT(1) DEFAULT 0 AFTER type",
        'min_team_size' => "INT DEFAULT 1 AFTER is_group_event",
        'max_team_size' => "INT DEFAULT 1 AFTER min_team_size"
    ];

    foreach ($eventsColumns as $col => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE '$col'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE events ADD $col $definition");
            echo "Added column '$col' to 'events'.\n";
        }
    }

    // 2. Update 'registrations' table
    $registrationsColumns = [
        'team_name' => "VARCHAR(255) AFTER event_id",
        'team_members' => "JSON AFTER team_name"
    ];

    foreach ($registrationsColumns as $col => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM registrations LIKE '$col'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE registrations ADD $col $definition");
            echo "Added column '$col' to 'registrations'.\n";
        }
    }

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>