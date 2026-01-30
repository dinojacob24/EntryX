<?php
/**
 * External Programs API
 * Handles CRUD operations for external registration programs
 * Super Admin Only
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/Project/EntryX');
    session_start();
}
header('Content-Type: application/json');

require_once '../config/db_connect.php';

// Super Admin Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'get_all':
            getAllPrograms($pdo);
            break;

        case 'get':
            getProgram($pdo, $_GET['id'] ?? 0);
            break;

        case 'create':
            createProgram($pdo, $userId);
            break;

        case 'update':
            updateProgram($pdo, $_GET['id'] ?? 0, $userId);
            break;

        case 'delete':
            deleteProgram($pdo, $_GET['id'] ?? 0, $userId);
            break;

        case 'toggle_status':
            toggleProgramStatus($pdo, $_GET['id'] ?? 0, $userId);
            break;

        case 'get_settings':
            getSystemSettings($pdo);
            break;

        case 'update_settings':
            updateSystemSettings($pdo, $userId);
            break;

        case 'enable_external_registration':
            enableExternalRegistration($pdo, $_GET['program_id'] ?? 0, $userId);
            break;

        case 'disable_external_registration':
            disableExternalRegistration($pdo, $userId);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getAllPrograms($pdo)
{
    $stmt = $pdo->query("
        SELECT ep.*, u.name as creator_name,
               (SELECT COUNT(*) FROM users WHERE external_program_id = ep.id) as participant_count
        FROM external_programs ep
        LEFT JOIN users u ON ep.created_by = u.id
        ORDER BY ep.created_at DESC
    ");
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $programs]);
}

function getProgram($pdo, $id)
{
    $stmt = $pdo->prepare("SELECT * FROM external_programs WHERE id = ?");
    $stmt->execute([$id]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($program) {
        echo json_encode(['success' => true, 'data' => $program]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Program not found']);
    }
}

function createProgram($pdo, $userId)
{
    $data = json_decode(file_get_contents('php://input'), true);

    $stmt = $pdo->prepare("
        INSERT INTO external_programs 
        (program_name, program_description, registration_form_fields, is_active, 
         start_date, end_date, max_participants, is_paid, registration_fee, 
         is_gst_enabled, gst_rate, payment_gateway, currency, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $formFields = json_encode($data['form_fields'] ?? []);

    $stmt->execute([
        $data['program_name'],
        $data['program_description'] ?? '',
        $formFields,
        $data['is_active'] ?? 1,
        $data['start_date'] ?? null,
        $data['end_date'] ?? null,
        $data['max_participants'] ?? 500,
        $data['is_paid'] ?? 0,
        $data['registration_fee'] ?? 0.00,
        $data['is_gst_enabled'] ?? 0,
        $data['gst_rate'] ?? 18.00,
        $data['payment_gateway'] ?? 'razorpay',
        $data['currency'] ?? 'INR',
        $userId
    ]);

    $programId = $pdo->lastInsertId();

    // Log admin action
    logAdminAction(
        $pdo,
        $userId,
        'create_external_program',
        "Created external program: {$data['program_name']}" . ($data['is_paid'] ? " (Paid: ₹{$data['registration_fee']})" : ""),
        'external_programs',
        $programId
    );

    echo json_encode(['success' => true, 'program_id' => $programId]);
}

function updateProgram($pdo, $id, $userId)
{
    $data = json_decode(file_get_contents('php://input'), true);

    $stmt = $pdo->prepare("
        UPDATE external_programs 
        SET program_name = ?, program_description = ?, registration_form_fields = ?,
            is_active = ?, start_date = ?, end_date = ?, max_participants = ?,
            is_paid = ?, registration_fee = ?, is_gst_enabled = ?, gst_rate = ?,
            payment_gateway = ?, currency = ?
        WHERE id = ?
    ");

    $formFields = json_encode($data['form_fields'] ?? []);

    $stmt->execute([
        $data['program_name'],
        $data['program_description'] ?? '',
        $formFields,
        $data['is_active'] ?? 1,
        $data['start_date'] ?? null,
        $data['end_date'] ?? null,
        $data['max_participants'] ?? 500,
        $data['is_paid'] ?? 0,
        $data['registration_fee'] ?? 0.00,
        $data['is_gst_enabled'] ?? 0,
        $data['gst_rate'] ?? 18.00,
        $data['payment_gateway'] ?? 'razorpay',
        $data['currency'] ?? 'INR',
        $id
    ]);

    logAdminAction(
        $pdo,
        $userId,
        'update_external_program',
        "Updated external program ID: {$id}" . ($data['is_paid'] ? " (Paid: ₹{$data['registration_fee']})" : ""),
        'external_programs',
        $id
    );

    echo json_encode(['success' => true]);
}

function deleteProgram($pdo, $id, $userId)
{
    // Check if program has participants
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE external_program_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo json_encode([
            'success' => false,
            'error' => "Cannot delete program with {$count} registered participants"
        ]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM external_programs WHERE id = ?");
    $stmt->execute([$id]);

    logAdminAction(
        $pdo,
        $userId,
        'delete_external_program',
        "Deleted external program ID: {$id}",
        'external_programs',
        $id
    );

    echo json_encode(['success' => true]);
}

function toggleProgramStatus($pdo, $id, $userId)
{
    $stmt = $pdo->prepare("UPDATE external_programs SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);

    logAdminAction(
        $pdo,
        $userId,
        'toggle_program_status',
        "Toggled status for program ID: {$id}",
        'external_programs',
        $id
    );

    echo json_encode(['success' => true]);
}

function getSystemSettings($pdo)
{
    $stmt = $pdo->query("SELECT * FROM system_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $settingsArray = [];
    foreach ($settings as $setting) {
        $settingsArray[$setting['setting_key']] = $setting['setting_value'];
    }

    echo json_encode(['success' => true, 'data' => $settingsArray]);
}

function updateSystemSettings($pdo, $userId)
{
    $data = json_decode(file_get_contents('php://input'), true);

    foreach ($data as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_by)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?
        ");
        $stmt->execute([$key, $value, $userId, $value, $userId]);
    }

    logAdminAction(
        $pdo,
        $userId,
        'update_system_settings',
        "Updated system settings",
        'system_settings',
        null
    );

    echo json_encode(['success' => true]);
}

function enableExternalRegistration($pdo, $programId, $userId)
{
    // First, deactivate all programs
    $pdo->exec("UPDATE external_programs SET is_active = 0");

    // Enable the specific selected program
    $stmt = $pdo->prepare("UPDATE external_programs SET is_active = 1 WHERE id = ?");
    $stmt->execute([$programId]);

    // Get program details for system settings backup
    $stmt = $pdo->prepare("SELECT program_name, program_description FROM external_programs WHERE id = ?");
    $stmt->execute([$programId]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$program) {
        throw new Exception("Program not found");
    }

    // Update system settings for global tracking
    $settings = [
        'external_registration_enabled' => '1',
        'current_external_program_id' => $programId,
        'current_external_program_name' => $program['program_name'],
        'current_external_program_description' => $program['program_description']
    ];

    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_by)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?
        ");
        $stmt->execute([$key, $value ?: '', $userId, $value ?: '', $userId]);
    }

    logAdminAction(
        $pdo,
        $userId,
        'enable_external_registration',
        "Enabled external registration for program: {$program['program_name']}",
        'external_programs',
        $programId
    );

    echo json_encode(['success' => true, 'message' => 'External registration enabled']);
}

function disableExternalRegistration($pdo, $userId)
{
    // 1. Update the global system setting
    $stmt = $pdo->prepare("
        UPDATE system_settings 
        SET setting_value = '0', updated_by = ?
        WHERE setting_key = 'external_registration_enabled'
    ");
    $stmt->execute([$userId]);

    // 2. Also deactivate all active programs in the table to ensure sync
    $pdo->exec("UPDATE external_programs SET is_active = 0");

    logAdminAction(
        $pdo,
        $userId,
        'disable_external_registration',
        "Disabled external registration and deactivated all programs",
        'system_settings',
        null
    );

    echo json_encode(['success' => true, 'message' => 'External registration disabled']);
}

function logAdminAction($pdo, $adminId, $actionType, $description, $table, $recordId)
{
    $stmt = $pdo->prepare("
        INSERT INTO admin_activity_log 
        (admin_id, action_type, action_description, affected_table, affected_record_id, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->execute([$adminId, $actionType, $description, $table, $recordId, $ipAddress]);
}
