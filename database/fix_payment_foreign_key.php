<?php
require_once __DIR__ . '/../config/db_connect.php';

try {
    echo "Starting migration to fix payment constraint...\n";

    // 1. Drop the foreign key constraint
    // We use the name found in the error message: program_payments_ibfk_2
    try {
        $pdo->exec("ALTER TABLE program_payments DROP FOREIGN KEY program_payments_ibfk_2");
        echo "Successfully dropped foreign key constraint 'program_payments_ibfk_2'.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "check that column/key exists") !== false || strpos($e->getMessage(), "Can't DROP") !== false) {
            echo "Constraint 'program_payments_ibfk_2' might not exist or was already dropped.\n";
        } else {
            throw $e;
        }
    }

    // 2. Ensure target_type is present (it seems it is, based on our diagnosis)
    $stmt = $pdo->query("SHOW COLUMNS FROM program_payments LIKE 'target_type'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE program_payments ADD COLUMN target_type VARCHAR(20) DEFAULT 'program' AFTER program_id");
        echo "Added missing 'target_type' column.\n";
    } else {
        echo "'target_type' column already exists.\n";
    }

    echo "Migration completed successfully. Payments for both events and programs should now work.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>