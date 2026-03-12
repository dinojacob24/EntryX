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

    // === SERVER-SIDE VALIDATION ===
    if (empty($input['name']) || empty($input['event_date'])) {
        echo json_encode(['success' => false, 'error' => 'Event name and date are required.']);
        exit;
    }

    if (strlen(trim($input['name'])) < 2) {
        echo json_encode(['success' => false, 'error' => 'Event name must be at least 2 characters.']);
        exit;
    }

    // Date: reject past dates (allow today)
    $eventDate = date('Y-m-d', strtotime($input['event_date']));
    $today = date('Y-m-d');
    if ($action === 'create' && $eventDate < $today) {
        echo json_encode(['success' => false, 'error' => 'Event date cannot be in the past. Please select today or a future date.']);
        exit;
    }

    // Capacity: must be >= 1
    if (isset($input['capacity']) && (int) $input['capacity'] < 1) {
        echo json_encode(['success' => false, 'error' => 'Event capacity must be at least 1.']);
        exit;
    }

    // Price: if paid event, must be > 0
    if (!empty($input['is_paid']) && $input['is_paid'] == 1) {
        $price = isset($input['base_price']) ? floatval($input['base_price']) : 0;
        if ($price <= 0) {
            echo json_encode(['success' => false, 'error' => 'Registration fee must be greater than ₹0 for a paid event.']);
            exit;
        }
    }

    // GST rate: must be 0-100
    if (isset($input['gst_rate'])) {
        $gst = floatval($input['gst_rate']);
        if ($gst < 0 || $gst > 100) {
            echo json_encode(['success' => false, 'error' => 'GST rate must be between 0% and 100%.']);
            exit;
        }
    }
    // Team Sizes: if group event, min/max must be valid
    if (!empty($input['is_group_event'])) {
        $min = isset($input['min_team_size']) ? (int) $input['min_team_size'] : 1;
        $max = isset($input['max_team_size']) ? (int) $input['max_team_size'] : 1;
        if ($min < 1) {
            echo json_encode(['success' => false, 'error' => 'Minimum team size must be at least 1.']);
            exit;
        }
        if ($max < $min) {
            echo json_encode(['success' => false, 'error' => 'Maximum team size must be greater than or equal to minimum.']);
            exit;
        }
    }
    // === END VALIDATION ===

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