<?php
require_once 'config/db_connect.php';

function checkColumns($pdo, $table)
{
    $stmt = $pdo->query("DESCRIBE $table");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$schema = [
    'events' => checkColumns($pdo, 'events'),
    'registrations' => checkColumns($pdo, 'registrations')
];

file_put_contents('group_schema_output.txt', print_r($schema, true));
?>