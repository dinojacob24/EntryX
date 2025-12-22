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
} elseif ($action === 'list') {
    $stmt = $pdo->query("
        SELECT r.*, e.title as event_title 
        FROM results r 
        JOIN events e ON r.event_id = e.id 
        ORDER BY r.published_at DESC
    ");
    echo json_encode(['results' => $stmt->fetchAll()]);
}
?>