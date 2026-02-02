<?php
require_once '../config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/Project/EntryX');
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? 'event'; // 'event' or 'program'
$targetId = $data['id'] ?? 0;

if (!$targetId) {
    echo json_encode(['success' => false, 'error' => 'Target ID required']);
    exit;
}

try {
    // 1. Get Payment Settings
    $stmt = $pdo->prepare("SELECT api_key, api_secret, is_active FROM payment_settings WHERE gateway_name = 'razorpay'");
    $stmt->execute();
    $gateway = $stmt->fetch();

    if (!$gateway || !$gateway['is_active']) {
        echo json_encode(['success' => false, 'error' => 'Payment gateway is not enabled by administrator']);
        exit;
    }

    // 2. Fetch Item Details & Calculate Amount
    $amount = 0;
    $gstAmount = 0;
    $totalAmount = 0;
    $itemName = "";

    if ($type === 'program') {
        $stmtItem = $pdo->prepare("SELECT program_name, registration_fee, is_gst_enabled, gst_rate FROM external_programs WHERE id = ?");
        $stmtItem->execute([$targetId]);
        $item = $stmtItem->fetch();
        if (!$item)
            throw new Exception("Program not found");

        $itemName = $item['program_name'];
        $amount = (float) $item['registration_fee'];
        if ($item['is_gst_enabled']) {
            $gstAmount = $amount * ($item['gst_rate'] / 100);
        }
    } else {
        $stmtItem = $pdo->prepare("SELECT name, base_price, is_gst_enabled, gst_rate FROM events WHERE id = ?");
        $stmtItem->execute([$targetId]);
        $item = $stmtItem->fetch();
        if (!$item)
            throw new Exception("Event not found");

        $itemName = $item['name'];
        $amount = (float) $item['base_price'];
        if ($item['is_gst_enabled']) {
            $gstAmount = $amount * ((float) $item['gst_rate'] / 100);
        }
    }

    $totalAmount = $amount + $gstAmount;

    // Razorpay amount is in paise
    $rpAmount = round($totalAmount * 100);

    // 3. Create Razorpay Order
    $url = "https://api.razorpay.com/v1/orders";
    $receipt = "rcpt_" . time() . "_" . $_SESSION['user_id'];

    $postData = [
        "amount" => $rpAmount,
        "currency" => "INR",
        "receipt" => $receipt,
        "notes" => [
            "user_id" => $_SESSION['user_id'],
            "target_id" => $targetId,
            "type" => $type,
            "item_name" => $itemName
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $gateway['api_key'] . ":" . $gateway['api_secret']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        throw new Exception("Razorpay order error: " . ($error['error']['description'] ?? 'Unknown error'));
    }

    $order = json_decode($response, true);

    // 4. Fetch User Phone for Prefill (since it might not be in session)
    $stmtUser = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $userPhone = $stmtUser->fetchColumn() ?: '';

    // 5. Store Order in program_payments table
    $stmtStore = $pdo->prepare("
        INSERT INTO program_payments 
        (user_id, program_id, target_type, order_id, amount, gst_amount, total_amount, payment_status, payment_gateway)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'razorpay')
    ");

    $stmtStore->execute([
        $_SESSION['user_id'],
        $targetId,
        $type,
        $order['id'],
        $amount,
        $gstAmount,
        $totalAmount
    ]);

    echo json_encode([
        'success' => true,
        'order_id' => $order['id'],
        'amount' => $rpAmount,
        'key' => $gateway['api_key'],
        'item_name' => $itemName,
        'user_name' => $_SESSION['name'] ?? 'Guest User',
        'user_email' => $_SESSION['email'] ?? '',
        'user_contact' => $userPhone
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
