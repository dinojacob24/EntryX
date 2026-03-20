<?php
require_once '../config/project_root.php';
require_once '../config/db_connect.php';
require_once '../classes/Registration.php';

header('Content-Type: application/json');

// Only Admins can verify payments
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'event_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$regObj = new Registration($pdo);
$action = $_GET['action'] ?? '';

if ($action === 'list_pending') {
    $pending = $regObj->getPendingRegistrations();
    echo json_encode(['success' => true, 'data' => $pending]);
    exit;
}

if ($action === 'verify' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $regId = $input['registration_id'] ?? null;

    if (!$regId) {
        echo json_encode(['success' => false, 'error' => 'Registration ID required']);
        exit;
    }

    $result = $regObj->verifyPayment($regId);
    echo json_encode($result);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>