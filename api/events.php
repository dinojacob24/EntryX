<?php
require_once '../config/db.php';
session_start();

$action = $_GET['action'] ?? 'list';
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'guest';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
if (!$data)
    $data = $_POST;

if ($action === 'create' && $role === 'admin') {
    try {
        $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, venue, base_fee, has_gst, gst_rate, allow_internal, allow_external) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['event_date'],
            $data['venue'],
            $data['base_fee'] ?? 0,
            $data['has_gst'] ?? 1,
            $data['gst_rate'] ?? 18,
            $data['allow_internal'] ?? 1,
            $data['allow_external'] ?? 1
        ]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($action === 'list') {
    $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date DESC");
    echo json_encode(['events' => $stmt->fetchAll()]);
} elseif ($action === 'register') {
    if (!$user_id) {
        http_response_code(401);
        die(json_encode(['error' => 'Login required']));
    }

    $event_id = $data['event_id'];

    // Check if already registered
    $stmt = $pdo->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);
    if ($stmt->fetch()) {
        die(json_encode(['error' => 'Already registered']));
    }

    // Get Event Details
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event)
        die(json_encode(['error' => 'Event not found']));

    // Calculate Amount
    $amount = 0;
    if ($role === 'internal') {
        // Internals usually free or base fee only (no GST often, implying distinct logic)
        // User request: "internals will not be having gst... but for externals the gst amount will also be included... or u can make it both [options]"
        // Logic: if Internal, pay Base Fee. if External, Pay Base fee + GST.
        $amount = $event['base_fee'];
        // NOTE: Usually internal events are free, but if paid, no GST for them as per prompt.
    } elseif ($role === 'external') {
        $amount = $event['base_fee'];
        if ($event['has_gst']) {
            $gst_amount = ($event['base_fee'] * $event['gst_rate']) / 100;
            $amount += $gst_amount;
        }
    }

    $qr_token = bin2hex(random_bytes(16)); // Unique token for QR

    try {
        $stmt = $pdo->prepare("INSERT INTO registrations (user_id, event_id, amount_paid, qr_token) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $event_id, $amount, $qr_token]);
        echo json_encode(['success' => true, 'qr_token' => $qr_token]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($action === 'my_tickets') {
    if (!$user_id)
        die(json_encode(['error' => 'Login required']));

    $stmt = $pdo->prepare("
        SELECT r.*, e.title, e.event_date, e.venue 
        FROM registrations r 
        JOIN events e ON r.event_id = e.id 
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    echo json_encode(['tickets' => $stmt->fetchAll()]);
}
?>