<?php
require_once '../config/db_connect.php';
require_once '../classes/Registration.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/Project/EntryX');
    session_start();
}
ob_clean(); // Clean any previous output
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$regObj = new Registration($pdo);
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = $input['event_id'] ?? null;

    if (!$eventId) {
        echo json_encode(['success' => false, 'error' => 'Event ID required']);
        exit;
    }

    try {
        $transactionId = $input['transaction_id'] ?? null;
        $result = $regObj->registerUser($_SESSION['user_id'], $eventId, $transactionId);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'cancel') {
    $input = json_decode(file_get_contents('php://input'), true);
    $regId = $input['registration_id'] ?? null;

    if (!$regId) {
        echo json_encode(['success' => false, 'error' => 'Registration ID required']);
        exit;
    }

    $result = $regObj->cancelRegistration($_SESSION['user_id'], $regId);
    echo json_encode($result);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>