<?php
class Registration
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function registerUser($userId, $eventId, $transactionId = null)
    {
        // 1. Get Event Details
        $stmt = $this->pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();

        if (!$event)
            return ['success' => false, 'error' => 'Event not found'];
        if ($event['capacity'] <= 0)
            return ['success' => false, 'error' => 'Event is full'];

        // 2. Check Duplicate
        $stmt = $this->pdo->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?");
        $stmt->execute([$userId, $eventId]);
        if ($stmt->fetch())
            return ['success' => false, 'error' => 'Already registered'];

        // 3. User Role for GST Targeting
        $stmtUser = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $userRole = $stmtUser->fetchColumn();
        $isInternal = in_array($userRole, ['student', 'staff']);
        $isExternal = ($userRole === 'external');

        // 4. Generate QR Token
        $qrToken = bin2hex(random_bytes(16)) . '-' . $userId . '-' . $eventId;

        // 5. Calculate Payment with GST Targeting
        $base = (float) ($event['base_price'] ?? 0);
        $gstRate = (float) ($event['gst_rate'] ?? 0);

        $applyGst = false;
        if ($event['is_gst_enabled'] && $event['is_paid']) {
            $target = $event['gst_target'] ?? 'both';
            if ($target === 'both') {
                $applyGst = true;
            } elseif ($target === 'internals_only' && $isInternal) {
                $applyGst = true;
            } elseif ($target === 'externals_only' && $isExternal) {
                $applyGst = true;
            }
        }

        $gst = $applyGst ? ($base * $gstRate / 100) : 0;
        $total = $event['is_paid'] ? ($base + $gst) : 0;
        $paymentStatus = $event['is_paid'] ? 'pending' : 'free';

        // 6. Strict Payment Validation
        if ($event['is_paid']) {
            if (!$transactionId) {
                return ['success' => false, 'error' => 'Transaction ID is required for paid events'];
            }

            // Enforce 12-digit UTR format (Standard for manual UPI/NetBanking in India)
            // Allow Razorpay IDs (usually pay_...) to bypass this specific 12-digit format check
            $isAutomated = (strpos($transactionId, 'pay_') === 0);

            if (!$isAutomated && !preg_match('/^\d{12}$/', $transactionId)) {
                return ['success' => false, 'error' => 'Invalid Transaction ID. Please provide the 12-digit UTR/Reference number from your payment app.'];
            }

            // check if this transaction ID has already been used
            $stmtCheck = $this->pdo->prepare("SELECT id FROM registrations WHERE transaction_id = ?");
            $stmtCheck->execute([$transactionId]);
            if ($stmtCheck->fetch()) {
                return ['success' => false, 'error' => 'This Transaction ID has already been used. Please provide a valid, unique reference number.'];
            }
        }

        // 7. Insert Registration
        $sql = "INSERT INTO registrations (user_id, event_id, amount_paid, qr_token, transaction_id, payment_status, base_amount, gst_amount, total_amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $eventId, $total, $qrToken, $transactionId, $paymentStatus, $base, $gst, $total]);

            // Decrease Capacity
            $this->pdo->prepare("UPDATE events SET capacity = capacity - 1 WHERE id = ?")->execute([$eventId]);

            return ['success' => true, 'qr_token' => $qrToken, 'payment_needed' => $event['is_paid']];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getUserRegistrations($userId)
    {
        $sql = "SELECT r.*, e.name as event_name, e.event_date, e.venue 
                FROM registrations r 
                JOIN events e ON r.event_id = e.id 
                WHERE r.user_id = ? 
                ORDER BY r.registration_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function cancelRegistration($userId, $regId)
    {
        try {
            // 1. Get Event ID first
            $stmt = $this->pdo->prepare("SELECT event_id FROM registrations WHERE id = ? AND user_id = ?");
            $stmt->execute([$regId, $userId]);
            $reg = $stmt->fetch();

            if (!$reg) {
                return ['success' => false, 'error' => 'Registration not found or unauthorized'];
            }

            $eventId = $reg['event_id'];

            // 2. Delete Registration
            $stmt = $this->pdo->prepare("DELETE FROM registrations WHERE id = ? AND user_id = ?");
            $stmt->execute([$regId, $userId]);

            // 3. Increment Capacity
            $this->pdo->prepare("UPDATE events SET capacity = capacity + 1 WHERE id = ?")->execute([$eventId]);

            return ['success' => true, 'message' => 'Registration cancelled successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getPendingRegistrations()
    {
        $sql = "SELECT r.*, e.name as event_name, u.name as user_name, u.email as user_email
                FROM registrations r 
                JOIN events e ON r.event_id = e.id 
                JOIN users u ON r.user_id = u.id
                WHERE r.payment_status = 'pending'
                ORDER BY r.registration_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function verifyPayment($regId)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE registrations SET payment_status = 'completed' WHERE id = ?");
            $success = $stmt->execute([$regId]);
            return ['success' => $success];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>