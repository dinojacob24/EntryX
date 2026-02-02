<?php
require_once '../config/db_connect.php';
echo "--- EXTERNAL_PROGRAMS ---\n";
$q = $pdo->query("DESCRIBE external_programs");
foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo $r['Field'] . "\n";
}

echo "\n--- USERS ---\n";
$q = $pdo->query("DESCRIBE users");
foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo $r['Field'] . "\n";
}
?>