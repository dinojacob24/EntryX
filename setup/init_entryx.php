<?php
require_once '../config/db.php';

echo "<h1>Initializing 'entryx' Database...</h1>";

try {
    // Read Schema
    $sql = file_get_contents('../database/schema.sql');

    // Execute Schema
    $pdo->exec($sql);
    echo "<h2 style='color: green;'>✅ Tables Created Successfully!</h2>";
    echo "<p>Connected to: <b>$db_name</b></p>";

    // Check if tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tables in Database:</h3><ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

    // Verify Google Column (Migration check)
    $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'google_id'")->fetchAll();
    if (count($cols) == 0) {
        echo "<p style='color: orange;'>⚠️ Adding 'google_id' column...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE DEFAULT NULL AFTER email;");
        $pdo->exec("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL;");
        echo "<p style='color: green;'>✅ Google Auth Columns Added.</p>";
    }

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Error: " . $e->getMessage() . "</h2>";
}
?>