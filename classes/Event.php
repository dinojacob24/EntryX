<?php
class Event
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function createEvent($data)
    {
        $sql = "INSERT INTO events (name, description, event_date, venue, capacity, type, is_paid, base_price, is_gst_enabled, gst_rate, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['event_date'],
                $data['venue'],
                $data['capacity'],
                $data['type'],
                $data['is_paid'],
                $data['base_price'],
                $data['is_gst_enabled'],
                $data['gst_rate'],
                $data['created_by']
            ]);
            return ['success' => true, 'message' => 'Event created successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllEvents($adminView = false)
    {
        if ($adminView) {
            $sql = "SELECT * FROM events ORDER BY event_date DESC";
        } else {
            $sql = "SELECT * FROM events WHERE status != 'cancelled' ORDER BY event_date ASC";
        }
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getEventById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateEvent($id, $data)
    {
        $sql = "UPDATE events SET 
                name = ?, description = ?, event_date = ?, venue = ?, 
                capacity = ?, type = ?, is_paid = ?, base_price = ?, 
                is_gst_enabled = ?, gst_rate = ?, status = ?
                WHERE id = ?";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['event_date'],
                $data['venue'],
                $data['capacity'],
                $data['type'],
                $data['is_paid'],
                $data['base_price'],
                $data['is_gst_enabled'],
                $data['gst_rate'],
                $data['status'],
                $id
            ]);
            return ['success' => true, 'message' => 'Event updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteEvent($id)
    {
        try {
            // First check if there are any registrations to prevent accidental data loss
            $check = $this->pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                // Instead of hard delete, maybe just mark as cancelled?
                // For this project, let's allow deletion but warning is handled in UI
                // We'll delete registrations and attendance logs first if it's a hard delete requirement
                $this->pdo->prepare("DELETE FROM attendance_logs WHERE registration_id IN (SELECT id FROM registrations WHERE event_id = ?)")->execute([$id]);
                $this->pdo->prepare("DELETE FROM registrations WHERE event_id = ?")->execute([$id]);
            }

            $stmt = $this->pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Event and its data deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getEventStats($eventId)
    {
        // Total Registrations
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $totalReg = $stmt->fetchColumn();

        // Currently Inside (Status = 'inside') in attendance_logs
        // Need to join registrations to filter by event_id
        $sqlInside = "SELECT COUNT(*) FROM attendance_logs a 
                      JOIN registrations r ON a.registration_id = r.id 
                      WHERE r.event_id = ? AND a.status = 'inside'";
        $stmtIn = $this->pdo->prepare($sqlInside);
        $stmtIn->execute([$eventId]);
        $inside = $stmtIn->fetchColumn();

        return ['registrations' => $totalReg, 'inside' => $inside];
    }

    // Additional methods for updating status, etc.
}
?>