<?php
require_once '../config/db.php';
session_start();

$action = $_GET['action'] ?? 'list';
$data = json_decode(file_get_contents('php://input'), true);

if ($action === 'publish' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    try {
        $stmt = $pdo->prepare("INSERT INTO results (event_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$data['event_id'], $data['title'], $data['content']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($action === 'delete') {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'event_admin'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $id = $_GET['id'] ?? 0;
    try {
        $stmt = $pdo->prepare("DELETE FROM results WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($action === 'list') {
    $stmt = $pdo->query("
        SELECT r.*, e.name as event_title 
        FROM results r 
        JOIN events e ON r.event_id = e.id 
        ORDER BY r.published_at DESC
    ");
    $fetchAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['results' => $fetchAll]);
} elseif ($action === 'get_candidates') {
    $eventId = $_GET['event_id'] ?? 0;
    $requestAll = isset($_GET['all']) && $_GET['all'] == '1';
    try {
        // Check if event is group event
        $eStmt = $pdo->prepare("SELECT is_group_event FROM events WHERE id = ?");
        $eStmt->execute([$eventId]);
        $event = $eStmt->fetch();
        $isGroup = $event && $event['is_group_event'] == 1;

        if ($requestAll) {
            // Admin view - get everything
            $stmt = $pdo->prepare("
                SELECT r.*, u.name, u.email 
                FROM registrations r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.event_id = ?
                ORDER BY r.registration_date DESC
            ");
            $stmt->execute([$eventId]);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'candidates' => $registrations]);
        } else {
            // Public candidate selection view — result publishing dropdowns
            if ($isGroup) {
                $stmt = $pdo->prepare("
                    SELECT 
                        COALESCE(r.team_name, u.name) as name, 
                        u.name as registered_by,
                        r.team_members,
                        r.payment_status,
                        r.id as registration_id
                    FROM registrations r 
                    JOIN users u ON r.user_id = u.id 
                    WHERE r.event_id = ?
                    ORDER BY name ASC
                ");
                $stmt->execute([$eventId]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // enrich with member count and payment status for dropdown display
                $candidates = array_map(function ($r) {
                    $memberCount = count(json_decode($r['team_members'] ?? '[]', true));
                    $payLabel = strtoupper($r['payment_status']);
                    $extra = $memberCount > 0 ? "Team · {$memberCount} members" : "Individual";
                    return [
                        'name' => $r['name'],
                        'email' => "{$extra} · {$payLabel} · by {$r['registered_by']}"
                    ];
                }, $rows);
            } else {
                $stmt = $pdo->prepare("
                    SELECT u.name, u.email, u.department, r.payment_status
                    FROM registrations r 
                    JOIN users u ON r.user_id = u.id 
                    WHERE r.event_id = ?
                    ORDER BY u.name ASC
                ");
                $stmt->execute([$eventId]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $candidates = array_map(function ($r) {
                    $payLabel = strtoupper($r['payment_status']);
                    $detail = $r['email'];
                    if (!empty($r['department'])) {
                        $detail = $r['department'] . ' · ' . $r['email'];
                    }
                    return ['name' => $r['name'] . " [{$payLabel}]", 'email' => $detail];
                }, $rows);
            }
            echo json_encode(['success' => true, 'candidates' => $candidates]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>