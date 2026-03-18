<?php
/**
 * QR DEBUG — Instantly diagnose why a QR code is failing.
 * Visit: localhost/Project/EntryX/debug_qr_scan.php
 */
require_once 'config/db_connect.php';

$token = trim($_GET['token'] ?? '');
$email = trim($_GET['email'] ?? 'dinojacob24@gmail.com');

echo "<style>
body { font-family: monospace; background: #0a0a0a; color: #00ff88; padding: 2rem; }
h2 { color: #ff4444; }
h3 { color: #3b82f6; }
.ok { color: #00ff88; }
.err { color: #ff4444; }
.warn { color: #f59e0b; }
table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
th, td { border: 1px solid #333; padding: 0.5rem 1rem; text-align: left; }
th { background: #1a1a2e; color: #3b82f6; }
td { background: #0d0d1a; }
</style>";

echo "<h2>🔍 EntryX QR Debug Tool</h2>";

// ── Check user by email ──
echo "<h3>Step 1: Check user by email ($email)</h3>";
$stmt = $pdo->prepare("SELECT id, name, email, role, qr_token, external_program_id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "<p class='ok'>✅ User found in DB</p>";
    echo "<table><tr><th>Field</th><th>Value</th></tr>";
    foreach ($user as $k => $v) {
        $val = $v ?? '<span class="err">NULL ⚠️</span>';
        echo "<tr><td>$k</td><td>$val</td></tr>";
    }
    echo "</table>";
    
    if (empty($user['qr_token'])) {
        echo "<p class='err'>❌ PROBLEM: users.qr_token is NULL for this user! This is why the scan fails.</p>";
        
        // Auto-fix: Generate a QR token for this user
        $newToken = bin2hex(random_bytes(16));
        $pdo->prepare("UPDATE users SET qr_token = ? WHERE id = ?")->execute([$newToken, $user['id']]);
        echo "<p class='ok'>🔧 AUTO-FIXED: Generated new qr_token = $newToken</p>";
        echo "<p class='warn'>⚠️ The user needs to REFRESH their external dashboard to see the new QR code.</p>";
        $user['qr_token'] = $newToken;
    } else {
        echo "<p class='ok'>✅ qr_token is SET: " . htmlspecialchars($user['qr_token']) . "</p>";
    }
} else {
    echo "<p class='err'>❌ No user found with email: $email</p>";
}

// ── Check ALL external users and their QR tokens ──
echo "<h3>Step 2: All External Users + QR Token Status</h3>";
$stmt = $pdo->query("SELECT id, name, email, role, qr_token, external_program_id FROM users WHERE role = 'external' ORDER BY id DESC LIMIT 20");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "<p class='warn'>⚠️ No external users found in database</p>";
} else {
    echo "<table>
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>qr_token</th><th>program_id</th><th>Status</th></tr>";
    foreach ($users as $u) {
        $tokenStatus = empty($u['qr_token']) 
            ? "<span class='err'>❌ NULL</span>" 
            : "<span class='ok'>✅ SET</span>";
        echo "<tr>
            <td>{$u['id']}</td>
            <td>{$u['name']}</td>
            <td>{$u['email']}</td>
            <td>{$u['role']}</td>
            <td style='font-size:0.8em;'>" . (empty($u['qr_token']) ? '<b style="color:red">NULL</b>' : substr($u['qr_token'], 0, 20) . '...') . "</td>
            <td>{$u['external_program_id']}</td>
            <td>$tokenStatus</td>
        </tr>";
    }
    echo "</table>";
    
    // Fix all NULL qr_tokens
    $fixed = 0;
    foreach ($users as $u) {
        if (empty($u['qr_token'])) {
            $newToken = bin2hex(random_bytes(16));
            $pdo->prepare("UPDATE users SET qr_token = ? WHERE id = ?")->execute([$newToken, $u['id']]);
            $fixed++;
        }
    }
    if ($fixed > 0) {
        echo "<p class='ok'>🔧 AUTO-FIXED $fixed user(s) with missing qr_token. They need to refresh their dashboard.</p>";
    }
}

// ── If a token param is provided, test it directly ──
if ($token) {
    echo "<h3>Step 3: Test Token Lookup: $token</h3>";
    
    // Strategy 1
    $s1 = $pdo->prepare("SELECT r.id, r.event_id, r.payment_status, u.name, u.role FROM registrations r JOIN users u ON r.user_id = u.id WHERE r.qr_token = ? OR r.qr_code = ? LIMIT 1");
    $s1->execute([$token, $token]);
    $r1 = $s1->fetch(PDO::FETCH_ASSOC);
    
    if ($r1) {
        echo "<p class='ok'>✅ Strategy 1 MATCH (Event registration): " . json_encode($r1) . "</p>";
    } else {
        echo "<p class='err'>❌ Strategy 1: No match in registrations table</p>";
    }
    
    // Strategy 2
    $s2 = $pdo->prepare("SELECT u.id, u.name, u.role, u.qr_token, ep.program_name FROM users u LEFT JOIN external_programs ep ON u.external_program_id = ep.id WHERE u.qr_token = ?");
    $s2->execute([$token]);
    $r2 = $s2->fetch(PDO::FETCH_ASSOC);
    
    if ($r2) {
        echo "<p class='ok'>✅ Strategy 2 MATCH (User gate pass): " . json_encode($r2) . "</p>";
    } else {
        echo "<p class='err'>❌ Strategy 2: No match in users table</p>";
        echo "<p class='warn'>This token is not in the database. Possible causes:<br>
        1. User's qr_token was NULL when QR was rendered (now fixed)<br>
        2. QR was generated from a stale/empty token<br>
        3. User needs to refresh their dashboard to get a new QR</p>";
    }
}

// ── Show recent attendance attempts ──
echo "<h3>Step 4: Recent Attendance Logs (Last 5)</h3>";
try {
    $stmt = $pdo->query("SELECT al.*, u.name, u.email FROM attendance_logs al JOIN registrations r ON al.registration_id = r.id JOIN users u ON r.user_id = u.id ORDER BY al.id DESC LIMIT 5");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($logs)) {
        echo "<p class='warn'>⚠️ No attendance logs yet (no successful scans)</p>";
    } else {
        echo "<table><tr><th>ID</th><th>Name</th><th>Status</th><th>Entry</th><th>Exit</th></tr>";
        foreach ($logs as $l) {
            echo "<tr><td>{$l['id']}</td><td>{$l['name']}</td><td>{$l['status']}</td><td>{$l['entry_time']}</td><td>{$l['exit_time']}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='err'>❌ " . $e->getMessage() . "</p>";
}

// ── Debug log ──
echo "<h3>Step 5: Recent Attendance Debug Log</h3>";
$logFile = __DIR__ . '/api/attendance_debug.log';
if (file_exists($logFile)) {
    $lines = array_slice(file($logFile), -20);
    echo "<pre style='background:#111;padding:1rem;border:1px solid #333;font-size:0.8em;'>";
    echo htmlspecialchars(implode('', $lines));
    echo "</pre>";
} else {
    echo "<p class='warn'>No debug log found yet (no scans attempted via security dashboard)</p>";
}

echo "<hr><p style='color:#475569;'>Add ?token=YOUR_TOKEN_HERE to test a specific QR token | ?email=user@email.com to check a specific user</p>";
?>
