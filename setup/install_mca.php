<?php
require_once '../config/db_connect.php';

echo "<h1>Installing MCA Project Database...</h1>";

try {
    // Read the MCA Schema
    $sql = file_get_contents('../database/mca_schema.sql');

    // Execute Schema
    $pdo->exec($sql);
    echo "<h2 style='color: green;'>✅ Database 'entryx' and Tables Created Successfully!</h2>";
    echo "<p>Connected to: <b>$db_name</b></p>";
    echo "<p>Super Admin created: <b>admin@entryx.com / password</b></p>";

    // Check if tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tables in Database:</h3><ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Error: " . $e->getMessage() . "</h2>";
}
?>