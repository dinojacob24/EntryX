<?php
class Registration
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function registerUser($userId, $eventId)
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

        // 3. Generate QR Token (Unique String)
        $qrToken = bin2hex(random_bytes(16)) . '-' . $userId . '-' . $eventId;

        // 4. Calculate Payment
        $base = (float) ($event['base_price'] ?? 0);
        $gstRate = (float) ($event['gst_rate'] ?? 0);
        $gst = ($event['is_gst_enabled'] && $event['is_paid']) ? ($base * $gstRate / 100) : 0;
        $total = $event['is_paid'] ? ($base + $gst) : 0;
        $paymentStatus = $event['is_paid'] ? 'pending' : 'free';

        // 5. Insert Registration
        $sql = "INSERT INTO registrations (user_id, event_id, amount_paid, qr_token, payment_status, base_amount, gst_amount, total_amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $eventId, $total, $qrToken, $paymentStatus, $base, $gst, $total]);

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
}
?>