<?php
session_start();
// Access Control - Authorized Personnel Only
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['security', 'super_admin', 'event_admin'])) {
    header('Location: admin_login.php');
    exit;
}

require_once '../includes/header.php';
require_once '../config/db_connect.php';
require_once '../classes/Event.php';

$eventId = $_GET['event_id'] ?? null;
if (!$eventId) {
    header('Location: security_dashboard.php');
    exit;
}

$eventObj = new Event($pdo);
$event = $eventObj->getEventById($eventId);
?>

<style>
    :root {
        --scan-brand: #3b82f6;
        --scan-success: #10b981;
        --scan-error: #ef4444;
        --scan-bg: #020617;
        --scan-card: rgba(15, 23, 42, 0.8);
    }

    body {
        background: var(--scan-bg);
        color: white;
        font-family: 'Outfit', sans-serif;
        min-height: 100vh;
        overflow-x: hidden;
    }

    .scanner-terminal {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }

    .scanner-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2.5rem;
    }

    .back-btn {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.8rem 1.5rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 14px;
        color: white;
        text-decoration: none;
        font-weight: 700;
        transition: 0.3s;
    }

    .back-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateX(-5px);
    }

    .scanner-chamber-box {
        position: relative;
        background: var(--scan-card);
        border-radius: 40px;
        padding: 1.5rem;
        border: 2px solid rgba(59, 130, 246, 0.2);
        box-shadow: 0 0 50px rgba(59, 130, 246, 0.1);
        margin-bottom: 3rem;
        overflow: hidden;
    }

    #reader {
        width: 100% !important;
        border: none !important;
        border-radius: 24px;
        overflow: hidden;
    }

    #reader video {
        border-radius: 24px;
    }

    /* Target the HTML5-QRCode generated container */
    #reader__scan_region {
        background: #000 !important;
    }

    .scan-overlay {
        position: absolute;
        inset: 2rem;
        border: 2px dashed rgba(255, 255, 255, 0.2);
        border-radius: 24px;
        pointer-events: none;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .scan-line {
        position: absolute;
        width: 100%;
        height: 2px;
        background: var(--scan-brand);
        box-shadow: 0 0 15px var(--scan-brand);
        top: 0;
        left: 0;
        animation: scanMove 3s infinite linear;
    }

    @keyframes scanMove {
        0% {
            top: 0;
        }

        100% {
            top: 100%;
        }
    }

    .status-alert {
        position: fixed;
        top: 2rem;
        left: 50%;
        transform: translateX(-50%);
        padding: 1.5rem 3rem;
        border-radius: 20px;
        font-weight: 800;
        font-size: 1.5rem;
        z-index: 2000;
        display: none;
        animation: slideUpIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    }

    @keyframes slideUpIn {
        from {
            opacity: 0;
            transform: translate(-50%, 20px);
        }

        to {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    }

    .log-container {
        background: var(--scan-card);
        border-radius: 32px;
        padding: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .log-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.2rem;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.02);
        margin-bottom: 1rem;
        animation: fadeIn 0.5s ease-out;
        border-left: 4px solid #475569;
    }

    .log-item.entry {
        border-left-color: var(--scan-success);
    }

    .log-item.exit {
        border-left-color: var(--scan-brand);
    }

    .log-item.denied {
        border-left-color: var(--scan-error);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
</style>

<div class="scanner-terminal">
    <div class="scanner-nav">
        <a href="security_dashboard.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Exit Terminal
        </a>
        <div style="text-align: right;">
            <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Scanning For
            </div>
            <div style="font-size: 1.2rem; font-weight: 800;"><?php echo htmlspecialchars($event['name']); ?></div>
        </div>
    </div>

    <div class="scanner-chamber-box">
        <div class="scan-overlay">
            <div class="scan-line" id="scanLine"></div>
        </div>
        <div id="reader"></div>
        <div id="scanMsg" style="text-align: center; padding: 1rem; color: #94a3b8; font-weight: 600;">
            <i class="fa-solid fa-circle-notch fa-spin"></i> Initializing Vanguard Optical Sensor...
        </div>
    </div>

    <div class="log-container">
        <h3 style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fa-solid fa-list-check" style="color: var(--scan-brand); margin-right: 0.8rem;"></i>
                Operational Log</span>
            <span
                style="font-size: 0.8rem; background: rgba(255,255,255,0.05); padding: 0.3rem 0.8rem; border-radius: 100px; color: #94a3b8;">Real-time</span>
        </h3>
        <div id="scanLog">
            <!-- Logs appear here -->
            <div id="emptyLog" style="text-align: center; padding: 3rem; color: #475569;">
                <i class="fa-solid fa-fingerprint fa-3x" style="margin-bottom: 1rem; opacity: 0.2;"></i>
                <p>Waiting for scan events...</p>
            </div>
        </div>
    </div>
</div>

<div id="visualAlert" class="status-alert"></div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    const eventId = <?php echo $eventId; ?>;
    let isProcessing = false;

    function showAlert(msg, color, bgColor) {
        const alert = document.getElementById('visualAlert');
        alert.textContent = msg;
        alert.style.color = color;
        alert.style.backgroundColor = bgColor;
        alert.style.display = 'block';

        // Vibrate if available (Great for mobile security teams)
        if (navigator.vibrate) {
            navigator.vibrate(msg.includes('DENIED') ? [200, 100, 200] : 150);
        }

        setTimeout(() => {
            alert.style.display = 'none';
        }, 2000);
    }

    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessing) return;
        isProcessing = true;

        document.getElementById('scanMsg').innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Processing High-Sec Payload...`;
        document.getElementById('scanLine').style.background = '#fbbf24'; // Yellow for processing

        fetch('../api/attendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ qr_token: decodedText, event_id: eventId })
        })
            .then(res => res.json())
            .then(data => {
                const emptyLog = document.getElementById('emptyLog');
                if (emptyLog) emptyLog.remove();

                const log = document.getElementById('scanLog');
                const li = document.createElement('div');

                if (data.success) {
                    const isEntry = data.type === 'entry';
                    const statusColor = isEntry ? '#10b981' : '#3b82f6';
                    const statusBg = isEntry ? 'rgba(16, 185, 129, 0.95)' : 'rgba(59, 130, 246, 0.95)';

                    showAlert(data.message.toUpperCase(), '#fff', statusBg);

                    li.className = `log-item ${data.type}`;
                    const roleBadge = data.user_role === 'external' ?
                        `<span style="background:rgba(245,158,11,0.15); color:#f59e0b; padding:2px 8px; border-radius:6px; font-size:0.7rem; font-weight:700; margin-left:8px;">EXTERNAL</span>` :
                        `<span style="background:rgba(16,185,129,0.15); color:#10b981; padding:2px 8px; border-radius:6px; font-size:0.7rem; font-weight:700; margin-left:8px;">INTERNAL</span>`;

                    li.innerHTML = `
                    <div>
                        <div style="font-weight: 800; font-size: 1.1rem;">${data.user_name}${roleBadge}</div>
                        <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">
                            <i class="fa-solid ${isEntry ? 'fa-arrow-right-to-bracket' : 'fa-arrow-right-from-bracket'}"></i> 
                            ${data.type} recorded
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700;">${data.time}</div>
                        <div style="font-size: 0.75rem; color: var(--scan-success);">SECURE_ACCESS</div>
                    </div>
                `;
                } else {
                    showAlert('ACCESS DENIED', '#fff', 'rgba(239, 68, 68, 0.95)');
                    li.className = 'log-item denied';
                    li.innerHTML = `
                    <div>
                        <div style="font-weight: 800; font-size: 1.1rem; color: var(--scan-error);">INVALID ACCESS</div>
                        <div style="font-size: 0.8rem; color: #94a3b8;">Reason: ${data.error}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700;">FAILED</div>
                    </div>
                `;
                }

                log.prepend(li);

                // Reset state
                setTimeout(() => {
                    isProcessing = false;
                    document.getElementById('scanMsg').innerHTML = `<i class="fa-solid fa-circle-check"></i> Sensor Ready - Scanning...`;
                    document.getElementById('scanLine').style.background = 'var(--scan-brand)';
                }, 1000);
            })
            .catch(err => {
                console.error(err);
                isProcessing = false;
                showAlert('SYSTEM ERROR', '#fff', '#ef4444');
            });
    }

    function onScanFailure(error) {
        // Silent failure for performance
    }

    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader",
        {
            fps: 15,
            qrbox: { width: 300, height: 300 },
            aspectRatio: 1.0,
            rememberLastUsedCamera: true
        },
        false);

    html5QrcodeScanner.render(onScanSuccess, onScanFailure);

    // Initial message update
    setTimeout(() => {
        document.getElementById('scanMsg').innerHTML = `<i class="fa-solid fa-video"></i> Sensor Ready - Point at Participant QR`;
    }, 1500);
</script>

<?php require_once '../includes/footer.php'; ?>