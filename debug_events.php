<?php
require 'c:/xampp/htdocs/Project/EntryX/config/db_connect.php';
$stmt = $pdo->query('SELECT id, name, event_date, status FROM events');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Name: {$row['name']} | Date: {$row['event_date']} | Status: {$row['status']}\n";
}
