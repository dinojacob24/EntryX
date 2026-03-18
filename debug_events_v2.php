<?php
require 'c:/xampp/htdocs/Project/EntryX/config/db_connect.php';
$stmt = $pdo->query('SELECT id, name, event_date, status FROM events');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "ID: " . $row['id'] . " | Name: [" . $row['name'] . "] | Date: " . $row['event_date'] . " | Status: " . $row['status'] . "\n";
}
echo "Total events in DB: " . count($rows) . "\n";
