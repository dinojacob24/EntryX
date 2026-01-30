<?php
require_once '../config/db_connect.php';
require_once '../classes/Event.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/Project/EntryX');
    session_start();
}
header('Content-Type: application/json');

// Check Auth
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$eventObj = new Event($pdo);
$action = $_GET['action'] ?? '';

if ($action === 'create' || $action === 'update') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid method']);
        exit;
    }

    if (!in_array($_SESSION['role'], ['super_admin', 'event_admin'])) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($input['name']) || empty($input['event_date'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    if ($action === 'create') {
        $input['created_by'] = $_SESSION['user_id'];
        $result = $eventObj->createEvent($input);
    } else {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Event ID missing']);
            exit;
        }
        $result = $eventObj->updateEvent($id, $input);
    }

    echo json_encode($result);
    exit;
}

if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid method']);
        exit;
    }

    if ($_SESSION['role'] !== 'super_admin') {
        echo json_encode(['success' => false, 'error' => 'Only Super Admin can delete events']);
        exit;
    }

    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Event ID missing']);
        exit;
    }

    $result = $eventObj->deleteEvent($id);
    echo json_encode($result);
    exit;
}

if ($action === 'get') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Event ID missing']);
        exit;
    }
    $event = $eventObj->getEventById($id);
    if ($event) {
        echo json_encode(['success' => true, 'data' => $event]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Event not found']);
    }
    exit;
}

if ($action === 'list') {
    $adminView = isset($_GET['admin']) && $_GET['admin'] === 'true';
    $events = $eventObj->getAllEvents($adminView);
    echo json_encode(['success' => true, 'data' => $events]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>