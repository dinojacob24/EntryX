<?php
/**
 * Migration: Add UNIQUE constraint on attendance_logs.registration_id
 *
 * This ensures that at the database level, each registration can only ever
 * have ONE attendance_log row (one entry, one exit — no duplicates possible).
 *
 * Run this ONCE via browser: http://localhost/Project/EntryX/database/add_unique_attendance_constraint.php
 */

require_once '../config/db_connect.php';

try {
    // Check if the constraint already exists
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = 'attendance_logs'
          AND CONSTRAINT_NAME = 'unique_registration_log'
          AND CONSTRAINT_TYPE = 'UNIQUE'
    ");
    $exists = (int)$stmt->fetchColumn();

    if ($exists > 0) {
        echo "<p style='color:green;font-family:monospace'>✅ Unique constraint already exists on attendance_logs.registration_id — no action needed.</p>";
    } else {
        // Check for any existing duplicate rows first
        $dupeCheck = $pdo->query("
            SELECT registration_id, COUNT(*) as cnt
            FROM attendance_logs
            GROUP BY registration_id
            HAVING cnt > 1
        ")->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($dupeCheck)) {
            echo "<p style='color:orange;font-family:monospace'>⚠️ Found duplicate attendance_logs rows for the following registration_ids. Please clean them up manually before re-running:</p>";
            foreach ($dupeCheck as $d) {
                echo "<p style='font-family:monospace'>registration_id=" . $d['registration_id'] . " appears " . $d['cnt'] . " times</p>";
            }
        } else {
            // Safe to add the unique constraint
            $pdo->exec("ALTER TABLE attendance_logs ADD UNIQUE KEY unique_registration_log (registration_id)");
            echo "<p style='color:green;font-family:monospace'>✅ UNIQUE constraint added to attendance_logs.registration_id successfully!</p>";
            echo "<p style='font-family:monospace'>Each QR code can now only ever be used for ONE entry and ONE exit — enforced at the database level.</p>";
        }
    }

    // Also show the current attendance_logs table structure for verification
    echo "<hr><h3 style='font-family:monospace'>Current attendance_logs Structure:</h3>";
    $cols = $pdo->query("DESCRIBE attendance_logs")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='6' style='font-family:monospace;border-collapse:collapse'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($cols as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "<p style='color:red;font-family:monospace'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
