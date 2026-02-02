<?php
require_once 'config/db_connect.php';

try {
    $sql = file_get_contents('database/add_payment_to_programs.sql');

    // Remove USE entryx; as we are already connected to the DB
    $sql = preg_replace('/USE\s+\w+;/i', '', $sql);

    // Split segments and execute
    $pdo->exec($sql);

    echo json_encode(['success' => true, 'message' => 'Migration executed successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
