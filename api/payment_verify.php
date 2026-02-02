<?php
require_once '../config/db_connect.php';
require_once '../classes/Registration.php';

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
$razorpay_order_id = $data['razorpay_order_id'] ?? '';
$razorpay_payment_id = $data['razorpay_payment_id'] ?? '';
$razorpay_signature = $data['razorpay_signature'] ?? '';

if (!$razorpay_order_id || !$razorpay_payment_id || !$razorpay_signature) {
    echo json_encode(['success' => false, 'error' => 'Invalid payment data received']);
    exit;
}

try {
    // 1. Get Gateway Credentials
    $stmt = $pdo->prepare("SELECT api_secret FROM payment_settings WHERE gateway_name = 'razorpay'");
    $stmt->execute();
    $gateway = $stmt->fetch();

    if (!$gateway) {
        throw new Exception("Gateway configuration not found");
    }

    // 2. Verify Signature
    $generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, $gateway['api_secret']);

    if ($generated_signature !== $razorpay_signature) {
        throw new Exception("Payment signature verification failed. Transaction may be tampered.");
    }

    // 3. Update Payment Table
    $stmtUpdate = $pdo->prepare("
        UPDATE program_payments 
        SET payment_id = ?, payment_status = 'completed', updated_at = CURRENT_TIMESTAMP 
        WHERE order_id = ?
    ");
    $stmtUpdate->execute([$razorpay_payment_id, $razorpay_order_id]);

    // 4. Register the User for the Event/Program
    // Get details from the payment record
    $stmtPay = $pdo->prepare("SELECT program_id, target_type, user_id FROM program_payments WHERE order_id = ?");
    $stmtPay->execute([$razorpay_order_id]);
    $payRecord = $stmtPay->fetch();

    if (!$payRecord) {
        throw new Exception("Order record not found for verification");
    }

    $registration = new Registration($pdo);
    $targetId = $payRecord['program_id'];
    $type = $payRecord['target_type'];

    if ($type === 'program') {
        // Handle External Program Registration
        $stmtUser = $pdo->prepare("
            UPDATE users 
            SET external_program_id = ?, payment_status = 'completed', program_payment_id = (SELECT id FROM program_payments WHERE order_id = ?)
            WHERE id = ?
        ");
        $stmtUser->execute([$targetId, $razorpay_order_id, $payRecord['user_id']]);
        $message = "Program registration completed successfully.";
    } else {
        // Handle Event Registration
        $eventId = $targetId; // In gateway.php we stored event_id in program_id

        // registerUser will handle the duplicate check and insertion
        // We pass the payment_id as transaction_id
        $result = $registration->registerUser($payRecord['user_id'], $eventId, $razorpay_payment_id);

        if (!$result['success']) {
            throw new Exception("Registration failed: " . $result['error']);
        }

        // Automatically verify it as 'completed'
        $stmtReg = $pdo->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ? AND transaction_id = ?");
        $stmtReg->execute([$payRecord['user_id'], $eventId, $razorpay_payment_id]);
        $row = $stmtReg->fetch();
        if ($row) {
            $registration->verifyPayment($row['id']);
        }
        $message = "Event registration confirmed.";
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'payment_id' => $razorpay_payment_id
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
