<?php
require_once '../config/db.php';
session_start();

// Access Control - Super Admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo "Unauthorized access.";
    exit;
}

$eventId = $_GET['event_id'] ?? 0;

if (!$eventId) {
    http_response_code(400);
    echo "Invalid Event ID.";
    exit;
}

try {
    // Get event info
    $eStmt = $pdo->prepare("SELECT name, is_group_event FROM events WHERE id = ?");
    $eStmt->execute([$eventId]);
    $event = $eStmt->fetch();
    $eventName = $event ? $event['name'] : 'Event';
    $isGroup = $event && $event['is_group_event'] == 1;
    $sanitizedEventName = preg_replace('/[^a-zA-Z0-9]/', '_', $eventName);

    // Get registrations with user details
    $stmt = $pdo->prepare("
        SELECT r.id, r.team_name, r.team_members, r.payment_status,
               r.total_amount, r.base_amount, r.gst_amount,
               r.transaction_id, r.registration_date,
               u.name as user_name, u.email as user_email, u.phone as user_phone,
               u.department as user_department, u.role as user_role
        FROM registrations r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.event_id = ?
        ORDER BY r.registration_date ASC
    ");
    $stmt->execute([$eventId]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Registrations_' . $sanitizedEventName . '_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    // Add BOM for proper Excel UTF-8 rendering
    fputs($output, "\xEF\xBB\xBF");

    if ($isGroup) {
        // ---- GROUP EVENT — one row per MEMBER ----
        fputcsv($output, [
            'Reg. ID',
            'Team Name',
            'Member #',
            'Member Name',
            'Department',
            'Year / Sem',
            'Registered By',
            'Registered By Email',
            'Payment Status',
            'Total Amount (₹)',
            'Transaction ID',
            'Registration Date'
        ]);

        foreach ($registrations as $reg) {
            $teamName = $reg['team_name'] ?: 'N/A';
            $rawMembers = json_decode($reg['team_members'] ?? '[]', true);

            if (empty($rawMembers)) {
                // No member data — output one row for the registrant
                fputcsv($output, [
                    $reg['id'],
                    $teamName,
                    1,
                    $reg['user_name'],
                    $reg['user_department'] ?: 'N/A',
                    'N/A',
                    $reg['user_name'],
                    $reg['user_email'],
                    strtoupper($reg['payment_status']),
                    number_format($reg['total_amount'], 2),
                    $reg['transaction_id'] ?: 'N/A',
                    $reg['registration_date']
                ]);
            } else {
                foreach ($rawMembers as $idx => $member) {
                    // Parse "Name | Dept | Year" format
                    $parts = array_map('trim', explode('|', $member));
                    $mName = $parts[0] ?? 'N/A';
                    $mDept = $parts[1] ?? 'N/A';
                    $mYear = $parts[2] ?? 'N/A';

                    fputcsv($output, [
                        $reg['id'],
                        $teamName,
                        $idx + 1,
                        $mName,
                        $mDept,
                        $mYear,
                        $reg['user_name'],
                        $reg['user_email'],
                        $idx === 0 ? strtoupper($reg['payment_status']) : '', // Only show payment on first row
                        $idx === 0 ? number_format($reg['total_amount'], 2) : '',
                        $idx === 0 ? ($reg['transaction_id'] ?: 'N/A') : '',
                        $idx === 0 ? $reg['registration_date'] : ''
                    ]);
                }
            }
        }
    } else {
        // ---- INDIVIDUAL EVENT — one row per registrant ----
        fputcsv($output, [
            'Reg. ID',
            'Participant Name',
            'Email',
            'Phone',
            'Department',
            'Participant Type',
            'Payment Status',
            'Total Amount (₹)',
            'Transaction ID',
            'Registration Date'
        ]);

        foreach ($registrations as $reg) {
            fputcsv($output, [
                $reg['id'],
                $reg['user_name'],
                $reg['user_email'],
                $reg['user_phone'] ?: 'N/A',
                $reg['user_department'] ?: 'N/A',
                strtoupper($reg['user_role']),
                strtoupper($reg['payment_status']),
                number_format($reg['total_amount'], 2),
                $reg['transaction_id'] ?: 'N/A',
                $reg['registration_date']
            ]);
        }
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo "Database Error: " . $e->getMessage();
}
?>