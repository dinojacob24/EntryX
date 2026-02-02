<?php
require_once '../config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/Project/EntryX');
    session_start();
}
header('Content-Type: application/json');

// Super Admin Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    if ($action === 'get') {
        $stmt = $pdo->prepare("SELECT api_key, api_secret, test_mode, is_active FROM payment_settings WHERE gateway_name = 'razorpay'");
        $stmt->execute();
        $settings = $stmt->fetch();

        if (!$settings) {
            // Initialize if not exists
            $pdo->exec("INSERT INTO payment_settings (gateway_name, is_active, test_mode) VALUES ('razorpay', 0, 1)");
            $settings = ['api_key' => '', 'api_secret' => '', 'test_mode' => 1, 'is_active' => 0];
        }

        echo json_encode(['success' => true, 'data' => $settings]);
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $stmt = $pdo->prepare("
            UPDATE payment_settings 
            SET api_key = ?, api_secret = ?, test_mode = ?, is_active = ?
            WHERE gateway_name = 'razorpay'
        ");

        $stmt->execute([
            $data['api_key'] ?? '',
            $data['api_secret'] ?? '',
            $data['test_mode'] ?? 1,
            $data['is_active'] ?? 0
        ]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
