<?php
require_once 'config/db_connect.php';
$stmt = $pdo->query("DESCRIBE program_payments");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('pp_schema_output.txt', print_r($cols, true));
?>