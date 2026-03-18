<?php
/**
 * Deep QR Fix - Check and repair all event registrations
 * Visit: localhost/Project/EntryX/debug_fix_registrations.php
 */
require_once 'config/db_connect.php';

echo "<style>
body { font-family: monospace; background: #0a0a0a; color: #00ff88; padding: 2rem; }
h2 { color: #ff4444; } h3 { color: #3b82f6; }
.ok { color: #00ff88; } .err { color: #ff4444; } .warn { color: #f59e0b; }
table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
th, td { border: 1px solid #333; padding: 0.5rem 1rem; text-align: left; font-size: 0.85em; }
th { background: #1a1a2e; color: #3b82f6; }
td { background: #0d0d1a; }
</style>";

echo "<h2>🔧 EntryX Deep Registration & QR Fix</h2>";

// ── 1. Show ALL registrations ──
echo "<h3>All Registrations in DB</h3>";
try {
    $stmt = $pdo->query("
        SELECT r.id, r.user_id, r.event_id, r.payment_status, r.qr_token, r.qr_code, 
               u.name, u.email, e.name as event_name
        FROM registrations r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN events e ON r.event_id = e.id
        ORDER BY r.id DESC
    ");
    $regs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($regs)) {
        echo "<p class='warn'>⚠️ No registrations found in database</p>";
    } else {
        echo "<p>Total registrations: " . count($regs) . "</p>";
        echo "<table><tr><th>ID</th><th>User</th><th>Event</th><th>Payment</th><th>qr_token</th><th>qr_code</th><th>Status</th></tr>";
        $fixed = 0;
        foreach ($regs as $r) {
            $qtOk = !empty($r['qr_token']);
            $qcOk = !empty($r['qr_code']);
            $status = ($qtOk || $qcOk) ? "<span class='ok'>✅ OK</span>" : "<span class='err'>❌ BOTH NULL</span>";
            
            // If one exists but not the other, fix it
            if ($qtOk && !$qcOk) {
                $pdo->prepare("UPDATE registrations SET qr_code = qr_token WHERE id = ?")->execute([$r['id']]);
                $status = "<span class='ok'>🔧 Fixed: qr_code updated</span>";
                $fixed++;
            } elseif (!$qtOk && $qcOk) {
                $pdo->prepare("UPDATE registrations SET qr_token = qr_code WHERE id = ?")->execute([$r['id']]);
                $status = "<span class='ok'>🔧 Fixed: qr_token updated</span>";
                $fixed++;
            } elseif (!$qtOk && !$qcOk) {
                // Both NULL — generate a new token
                $newToken = bin2hex(random_bytes(16)) . '-' . $r['user_id'] . '-' . $r['event_id'];
                $pdo->prepare("UPDATE registrations SET qr_token = ?, qr_code = ? WHERE id = ?")->execute([$newToken, $newToken, $r['id']]);
                $status = "<span class='warn'>🔧 GENERATED new token</span>";
                $r['qr_token'] = $newToken;
                $fixed++;
            }
            
            echo "<tr>
                <td>{$r['id']}</td>
                <td>{$r['name']}<br><small>{$r['email']}</small></td>
                <td>{$r['event_name']}</td>
                <td>{$r['payment_status']}</td>
                <td style='font-size:0.7em;'>" . ($r['qr_token'] ? substr($r['qr_token'],0,20).'...' : '<b style="color:red">NULL</b>') . "</td>
                <td style='font-size:0.7em;'>" . ($r['qr_code'] ? substr($r['qr_code'],0,20).'...' : '<b style="color:red">NULL</b>') . "</td>
                <td>$status</td>
            </tr>";
        }
        echo "</table>";
        if ($fixed > 0) echo "<p class='ok'>✅ Fixed $fixed registration(s)</p>";
    }
} catch (Exception $e) {
    echo "<p class='err'>Error: " . $e->getMessage() . "</p>";
}

// ── 2. Show ALL events ──
echo "<h3>All Events in DB</h3>";
try {
    $stmt = $pdo->query("SELECT id, name, type, status, event_date FROM events ORDER BY id DESC LIMIT 10");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($events)) {
        echo "<p class='warn'>No events found</p>";
    } else {
        echo "<table><tr><th>ID</th><th>Name</th><th>Type</th><th>Status</th><th>Date</th></tr>";
        foreach ($events as $e) {
            echo "<tr><td>{$e['id']}</td><td>{$e['name']}</td><td>{$e['type']}</td><td>{$e['status']}</td><td>{$e['event_date']}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='err'>Error: " . $e->getMessage() . "</p>";
}

// ── 3. Token test from URL param ──
$testToken = trim($_GET['token'] ?? '');
if ($testToken) {
    echo "<h3>Testing Token: $testToken</h3>";
    
    $s1 = $pdo->prepare("SELECT r.id, r.event_id, r.payment_status, r.qr_token, r.qr_code, u.name FROM registrations r JOIN users u ON r.user_id = u.id WHERE r.qr_token = ? OR r.qr_code = ? LIMIT 1");
    $s1->execute([$testToken, $testToken]);
    $r1 = $s1->fetch(PDO::FETCH_ASSOC);
    echo $r1 ? "<p class='ok'>✅ FOUND in registrations: " . json_encode($r1) . "</p>"
             : "<p class='err'>❌ NOT found in registrations</p>";
    
    $s2 = $pdo->prepare("SELECT id, name, role, qr_token FROM users WHERE qr_token = ?");
    $s2->execute([$testToken]);
    $r2 = $s2->fetch(PDO::FETCH_ASSOC);
    echo $r2 ? "<p class='ok'>✅ FOUND in users: " . json_encode($r2) . "</p>"
             : "<p class='err'>❌ NOT found in users</p>";
}

// ── 4. Show attendance_logs ──
echo "<h3>Attendance Logs</h3>";
try {
    $stmt = $pdo->query("SELECT al.*, u.name, u.email, e.name as event_name FROM attendance_logs al JOIN registrations r ON al.registration_id = r.id JOIN users u ON r.user_id = u.id JOIN events e ON r.event_id = e.id ORDER BY al.id DESC LIMIT 10");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($logs)) {
        echo "<p class='warn'>No attendance logs yet</p>";
    } else {
        echo "<table><tr><th>ID</th><th>User</th><th>Event</th><th>Status</th><th>Entry</th><th>Exit</th></tr>";
        foreach ($logs as $l) {
            echo "<tr><td>{$l['id']}</td><td>{$l['name']}</td><td>{$l['event_name']}</td><td>{$l['status']}</td><td>{$l['entry_time']}</td><td>{$l['exit_time']}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='err'>Error: " . $e->getMessage() . "</p>";
}

// ── 5. Show debug log ──
echo "<h3>Last 30 Debug Log Lines</h3>";
$logFile = __DIR__ . '/api/attendance_debug.log';
if (file_exists($logFile)) {
    $lines = array_slice(file($logFile), -30);
    echo "<pre style='background:#111;padding:1rem;border:1px solid #333;overflow-x:auto;'>" . htmlspecialchars(implode('', $lines)) . "</pre>";
} else {
    echo "<p class='warn'>No debug log yet</p>";
}

echo "<p style='color:#555;margin-top:2rem;'>Add ?token=TOKEN to test a specific token</p>";
?>
