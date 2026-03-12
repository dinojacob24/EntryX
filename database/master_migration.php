<?php
/**
 * Master Database Migration Script
 * Run this once to ensure all required columns exist.
 * Safe to run multiple times - uses ADD COLUMN IF NOT EXISTS
 * 
 * Visit: localhost/Project/EntryX/database/master_migration.php
 */
require_once '../config/db_connect.php';

$migrations = [];

// 1. Events Table - Group Event columns
$migrations[] = "ALTER TABLE events 
    ADD COLUMN IF NOT EXISTS is_group_event TINYINT(1) DEFAULT 0 AFTER status,
    ADD COLUMN IF NOT EXISTS min_team_size INT DEFAULT 1 AFTER is_group_event,
    ADD COLUMN IF NOT EXISTS max_team_size INT DEFAULT 1 AFTER min_team_size;";

// 2. Events Table - Payment columns
$migrations[] = "ALTER TABLE events 
    ADD COLUMN IF NOT EXISTS is_paid TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS base_price DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS is_gst_enabled TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS gst_rate DECIMAL(5,2) DEFAULT 18.00,
    ADD COLUMN IF NOT EXISTS payment_upi VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS gst_target ENUM('both','internals_only','externals_only') DEFAULT 'both';";

// 3. Registrations Table - Group registration columns
$migrations[] = "ALTER TABLE registrations 
    ADD COLUMN IF NOT EXISTS team_name VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS team_members TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS base_amount DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS gst_amount DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) DEFAULT 0.00;";

// 4. Registrations Table - QR Token (rename qr_code to qr_token if needed)
$migrations[] = "ALTER TABLE registrations 
    ADD COLUMN IF NOT EXISTS qr_token VARCHAR(255) DEFAULT NULL;";

// 5. Results Table
$migrations[] = "ALTER TABLE results 
    ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS published_by INT DEFAULT NULL;";

echo "<pre style='font-family: monospace; background: #1a1a1a; color: #0f0; padding: 2rem;'>";
echo "<h2 style='color: #ff4444;'>EntryX Master Migration</h2>\n";

$success = 0;
$failed = 0;

foreach ($migrations as $i => $sql) {
    $n = $i + 1;
    try {
        $pdo->exec($sql);
        echo "✅ Migration $n: OK\n";
        $success++;
    } catch (PDOException $e) {
        echo "⚠️  Migration $n: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n<strong>Done! $success succeeded, $failed had warnings (may already exist).</strong>\n";
echo "</pre>";
?>