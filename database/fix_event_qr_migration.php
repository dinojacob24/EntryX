<?php
/**
 * Fix Event QR Migration
 * 
 * Purpose: Sync qr_code → qr_token for any old registration records
 * where qr_token is NULL but qr_code exists.
 * Also adds qr_code column to registrations if missing (for legacy support).
 * 
 * Visit: localhost/Project/EntryX/database/fix_event_qr_migration.php
 */
require_once '../config/db_connect.php';

echo "<pre style='font-family: monospace; background: #1a1a1a; color: #0f0; padding: 2rem;'>";
echo "<h2 style='color: #ff4444;'>EntryX — Event QR Fix Migration</h2>\n";

// Step 1: Ensure qr_code column exists in registrations (legacy support)
try {
    $pdo->exec("ALTER TABLE registrations ADD COLUMN IF NOT EXISTS qr_code VARCHAR(255) DEFAULT NULL;");
    echo "✅ Step 1: qr_code column ensured in registrations\n";
} catch (PDOException $e) {
    echo "⚠️  Step 1: " . $e->getMessage() . "\n";
}

// Step 2: Ensure qr_token column exists in registrations
try {
    $pdo->exec("ALTER TABLE registrations ADD COLUMN IF NOT EXISTS qr_token VARCHAR(255) DEFAULT NULL;");
    echo "✅ Step 2: qr_token column ensured in registrations\n";
} catch (PDOException $e) {
    echo "⚠️  Step 2: " . $e->getMessage() . "\n";
}

// Step 3: Sync qr_code → qr_token for records where qr_token is NULL but qr_code exists
try {
    $stmt = $pdo->query("UPDATE registrations SET qr_token = qr_code WHERE qr_token IS NULL AND qr_code IS NOT NULL AND qr_code != ''");
    $count = $stmt->rowCount();
    echo "✅ Step 3: Synced qr_code → qr_token for $count registration(s)\n";
} catch (PDOException $e) {
    echo "⚠️  Step 3: " . $e->getMessage() . "\n";
}

// Step 4: Sync qr_token → qr_code for records where qr_code is NULL but qr_token exists
try {
    $stmt = $pdo->query("UPDATE registrations SET qr_code = qr_token WHERE qr_code IS NULL AND qr_token IS NOT NULL AND qr_token != ''");
    $count = $stmt->rowCount();
    echo "✅ Step 4: Synced qr_token → qr_code for $count registration(s)\n";
} catch (PDOException $e) {
    echo "⚠️  Step 4: " . $e->getMessage() . "\n";
}

// Step 5: Verify attendance_logs table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        registration_id INT NOT NULL,
        entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        exit_time TIMESTAMP NULL DEFAULT NULL,
        status ENUM('inside','exited') DEFAULT 'inside',
        FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
    );");
    echo "✅ Step 5: attendance_logs table ensured\n";
} catch (PDOException $e) {
    echo "⚠️  Step 5: " . $e->getMessage() . "\n";
}

// Step 6: Report current state
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN qr_token IS NOT NULL THEN 1 ELSE 0 END) as with_token, SUM(CASE WHEN qr_token IS NULL THEN 1 ELSE 0 END) as without_token FROM registrations");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\n📊 Registrations Summary:\n";
    echo "   Total: {$row['total']}\n";
    echo "   With qr_token: {$row['with_token']}\n";
    echo "   Without qr_token: {$row['without_token']}\n";
} catch (PDOException $e) {
    echo "⚠️  Summary: " . $e->getMessage() . "\n";
}

echo "\n<strong style='color: #0ff;'>✅ Migration complete! External event QR codes should now work correctly.</strong>\n";
echo "</pre>";
?>
