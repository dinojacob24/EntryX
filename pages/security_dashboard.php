<?php
// Session check MUST be at the very top
session_start();

// Access Control - Security and Admins only
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['security', 'super_admin', 'event_admin'])) {
    header('Location: sub_admin_login.php');
    exit;
}

$hideFooter = true;
$hideHeaderNav = true;
$useCustomLayout = true; // Use full screen layout without standard containers
require_once '../includes/header.php';
require_once '../config/db_connect.php';
require_once '../classes/Event.php';

$userName = $_SESSION['name'];
$eventObj = new Event($pdo);
// Security sees all upcoming/ongoing events
$events = $eventObj->getAllEvents();

// Initial Event ID for scanner (first one if exists)
$initialEventId = !empty($events) ? $events[0]['id'] : null;

// Fetch live entry stats
$stmtTotal = $pdo->query("SELECT COUNT(*) FROM attendance_logs WHERE status = 'inside'");
$totalInside = $stmtTotal->fetchColumn();

$stmtToday = $pdo->query("SELECT COUNT(*) FROM attendance_logs WHERE DATE(entry_time) = CURDATE()");
$entriesToday = $stmtToday->fetchColumn();
?>

<style>
    :root {
        --sec-brand: #3b82f6;
        --sec-success: #10b981;
        --sec-error: #ef4444;
        --sec-dark: #020617;
        --sec-panel: rgba(15, 23, 42, 0.8);
        --sec-border: rgba(59, 130, 246, 0.2);
    }

    body {
        background: var(--sec-dark);
        color: #f8fafc;
        font-family: 'Outfit', sans-serif;
        margin: 0;
        min-height: 100vh;
        overflow: hidden;
        /* Prevent body scroll */
    }

    /* Hide standard site header navigation */
    nav,
    .top-nav,
    header:not(.gate-header) {
        display: none !important;
    }

    .unified-terminal {
        display: grid;
        grid-template-columns: 350px 1fr;
        height: 100vh;
        overflow: hidden;
    }

    /* Left Sidebar: Stats & Event Control */
    .sidebar-control {
        background: rgba(15, 23, 42, 0.95);
        border-right: 1px solid var(--sec-border);
        padding: 2rem;
        display: flex;
        flex-direction: column;
        gap: 2rem;
        overflow-y: auto;
    }

    .stat-box-term {
        padding: 1.5rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--sec-border);
        border-radius: 20px;
    }

    .stat-label {
        font-size: 0.75rem;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 0.5rem;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: white;
    }

    .capacity-bar-container {
        height: 8px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 40px;
        overflow: hidden;
        margin-top: 0.8rem;
    }

    .capacity-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--sec-brand), var(--sec-success));
        width: 0%;
        transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .recent-profile-card {
        background: rgba(59, 130, 246, 0.05);
        border: 1px solid var(--sec-border);
        border-radius: 20px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-top: 1rem;
        opacity: 0;
        transform: translateY(10px);
        transition: 0.3s;
    }

    .recent-profile-card.active {
        opacity: 1;
        transform: translateY(0);
    }

    .event-selector-list {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }

    .event-btn-choice {
        padding: 1rem;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        color: #94a3b8;
        text-align: left;
        cursor: pointer;
        transition: 0.3s;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .event-btn-choice.active {
        background: rgba(59, 130, 246, 0.1);
        border-color: var(--sec-brand);
        color: white;
    }

    /* Right Main: Scanning Hub */
    .scanning-hub {
        padding: 2rem;
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 2rem;
        background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.05) 0%, transparent 50%);
    }

    .scanner-chamber {
        width: 100%;
        max-width: 600px;
        aspect-ratio: 1;
        margin: 0 auto;
        background: #000;
        border-radius: 32px;
        border: 2px solid var(--sec-border);
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 50px rgba(59, 130, 246, 0.1);
    }

    #reader {
        width: 100% !important;
        height: 100% !important;
        border: none !important;
    }

    #reader__scan_region {
        background: #000 !important;
    }

    #reader__dashboard {
        display: none !important;
    }

    #reader video,
    #reader canvas {
        object-fit: cover !important;
        width: 100% !important;
        height: 100% !important;
        border-radius: 30px;
    }

    .scan-overlay-vanguard {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 10;
        border: 40px solid rgba(2, 6, 23, 0.6);
    }

    .scan-target {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 300px;
        height: 300px;
        border: 2px solid var(--sec-brand);
        border-radius: 24px;
        box-shadow: 0 0 0 1000px rgba(2, 6, 23, 0.6);
    }

    .laser-line {
        position: absolute;
        width: 100%;
        height: 4px;
        background: var(--sec-brand);
        box-shadow: 0 0 20px var(--sec-brand);
        top: 0;
        animation: laserMove 4s infinite ease-in-out;
    }

    @keyframes laserMove {

        0%,
        100% {
            top: 0;
        }

        50% {
            top: 100%;
        }
    }

    .live-entry-log {
        height: 200px;
        background: var(--sec-panel);
        backdrop-filter: blur(20px);
        border: 1px solid var(--sec-border);
        border-radius: 24px;
        padding: 1.5rem;
        overflow-y: auto;
    }

    .log-entry-row {
        display: flex;
        justify-content: space-between;
        padding: 0.8rem 1rem;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.02);
        margin-bottom: 0.5rem;
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .status-floating {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: 4rem;
        border-radius: 40px;
        font-size: 4rem;
        font-weight: 900;
        z-index: 1000;
        display: none;
        box-shadow: 0 0 150px rgba(0, 0, 0, 0.9);
        text-align: center;
        width: 80%;
        max-width: 800px;
        animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes popIn {
        from {
            transform: translate(-50%, -50%) scale(0.5);
            opacity: 0;
        }

        to {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }
    }

    .initialize-overlay {
        position: absolute;
        inset: 0;
        background: rgba(2, 6, 23, 0.9);
        backdrop-filter: blur(10px);
        z-index: 100;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2rem;
        border-radius: 30px;
        transition: 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .initialize-overlay.active {
        opacity: 0;
        pointer-events: none;
        transform: scale(1.1);
    }

    .manual-entry-bar {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--sec-border);
        border-radius: 16px;
        display: flex;
        padding: 0.5rem;
        margin-top: 1rem;
    }

    .manual-entry-bar input {
        background: transparent;
        border: none;
        color: white;
        padding: 0.8rem 1rem;
        flex: 1;
        outline: none;
        font-weight: 600;
    }

    .manual-entry-bar button {
        background: var(--sec-brand);
        border: none;
        color: white;
        padding: 0 1.5rem;
        border-radius: 12px;
        cursor: pointer;
        font-weight: 800;
        transition: 0.3s;
    }

    .manual-entry-bar button:hover {
        background: #2563eb;
    }

    .camera-controls {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1rem;
    }

    .cam-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        padding: 0.6rem 1rem;
        border-radius: 10px;
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .cam-btn.active {
        background: var(--sec-brand);
        border-color: var(--sec-brand);
    }

    .header-compact {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn-exit {
        color: #94a3b8;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
</style>

<div class="unified-terminal">
    <!-- Sidebar -->
    <aside class="sidebar-control">
        <div class="header-compact">
            <h2 style="font-size: 1.5rem; margin:0;">GATE TERMINAL</h2>
            <a href="/Project/EntryX/api/auth.php?action=logout" class="btn-exit" style="color: #ef4444;">
                <i class="fa-solid fa-power-off"></i>
            </a>
        </div>

        <div class="stat-box-term">
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <div class="stat-label">System Load</div>
                <div id="capacityPercent" style="font-size: 0.75rem; color: var(--sec-brand); font-weight: 800;">0%
                </div>
            </div>
            <div class="stat-value" id="countInside"><?php echo $totalInside; ?></div>
            <div class="capacity-bar-container">
                <div id="capacityFill" class="capacity-fill"></div>
            </div>
        </div>

        <div class="stat-box-term">
            <div class="stat-label">Daily Cycle</div>
            <div class="stat-value" id="countToday"><?php echo $entriesToday; ?></div>
        </div>

        <input type="hidden" id="activeEventId" value="<?php echo $initialEventId; ?>">


        <div style="margin-top: auto; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05);">
            <div style="font-size: 0.8rem; color: #475569;">
                <i class="fa-solid fa-user-shield"></i> Officer: <?php echo htmlspecialchars($userName); ?>
            </div>
        </div>
    </aside>

    <!-- Main Scanning Area -->
    <main class="scanning-hub">
        <div style="display: flex; justify-content: space-between; align-items: baseline;">
            <h1 style="font-size: 2.2rem; margin: 0; letter-spacing: -0.02em;">Vanguard Optical Sensor</h1>
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <button class="cam-btn" onclick="toggleFullScreen()"><i class="fa-solid fa-expand"></i>
                    FULLSCREEN</button>
                <div id="liveTime" style="font-weight: 800; font-size: 1.2rem; color: var(--sec-brand);">00:00:00</div>
            </div>
        </div>

        <div class="scanner-chamber">
            <div id="initOverlay" class="initialize-overlay">
                <div
                    style="width: 100px; height: 100px; background: rgba(59, 130, 246, 0.1); border-radius: 30px; display: flex; align-items: center; justify-content: center; color: var(--sec-brand);">
                    <i class="fa-solid fa-camera fa-3x"></i>
                </div>
                <div style="text-align: center;">
                    <h3 style="margin-bottom: 0.5rem;">Vanguard Optical Sensor</h3>
                    <p style="color: #94a3b8; font-size: 0.9rem;">Hardware initialization required to proceed</p>
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem; width: 100%; max-width: 300px;">
                    <button class="btn btn-primary"
                        style="padding: 1.2rem; border-radius: 16px; width: 100%; font-weight: 800; background: #10b981; border: none;"
                        onclick="startScanner('entry')">
                        <i class="fa-solid fa-right-to-bracket"></i> START ENTRY SCAN
                    </button>
                    <button class="btn btn-primary"
                        style="padding: 1.2rem; border-radius: 16px; width: 100%; font-weight: 800; background: #ef4444; border: none;"
                        onclick="startScanner('exit')">
                        <i class="fa-solid fa-right-from-bracket"></i> START EXIT SCAN
                    </button>
                </div>
            </div>
            <div class="scan-overlay-vanguard" id="vanguardGrid" style="display: none;">
                <div class="scan-target">
                    <div class="laser-line"></div>
                </div>
            </div>
            <div id="reader"></div>
        </div>

        <div id="cameraControls" class="camera-controls" style="display: none;">
            <button class="cam-btn" onclick="toggleFlash()"><i class="fa-solid fa-bolt"></i> Flashlight</button>
            <button class="cam-btn" onclick="switchCamera()"><i class="fa-solid fa-camera-rotate"></i> Switch
                Camera</button>
            <button class="cam-btn"
                style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3); color: #ef4444;"
                onclick="stopScanner()">
                <i class="fa-solid fa-video-slash"></i> OFF SENSOR
            </button>
        </div>

        <div style="display: flex; gap: 1rem; align-items: stretch;">
            <div id="recentClearance" class="recent-profile-card" style="flex: 2; margin-top: 0;">
                <div
                    style="width: 50px; height: 50px; background: var(--sec-brand); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                    <i class="fa-solid fa-user-check fa-lg"></i>
                </div>
                <div>
                    <div id="clearanceName" style="font-weight: 800; font-size: 1.1rem; color: white;">Awaiting Scan...
                    </div>
                    <div id="clearanceDetails" style="font-size: 0.8rem; color: var(--sec-brand); font-weight: 700;">No
                        active participant</div>
                </div>
            </div>

            <button id="nextScanBtn" class="btn"
                style="flex: 1; display: none; background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; border-radius: 20px; font-weight: 800;"
                onclick="manualReset()">
                <i class="fa-solid fa-forward"></i> NEXT SCAN
            </button>
        </div>

        <div class="live-entry-log" id="terminalLog">
            <div style="color: #475569; text-align: center; padding: 2rem;">
                <i class="fa-solid fa-wifi fa-fade"></i> Terminal Ready. Waiting for participant signal...
            </div>
        </div>
    </main>
</div>

<div id="floatStatus" class="status-floating"></div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    // Sound effects
    const successSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2013/2013-preview.mp3');
    const errorSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2019/2019-preview.mp3');

    let isInitializing = false;
    let currentScanMode = 'entry'; // 'entry' or 'exit'

    async function startScanner(mode = 'entry') {
        if (isInitializing) return;
        currentScanMode = mode;

        const actionText = mode === 'entry' ? 'scan entry tickets' : 'scan exit passes';
        const titleText = mode === 'entry' ? 'Enable Entry Optical Sensor?' : 'Enable Exit Optical Sensor?';
        const confirmColor = mode === 'entry' ? '#10b981' : '#ef4444';

        // 1. User Intent Confirmation (UI Alert)
        const confirm = await Swal.fire({
            title: titleText,
            text: `System needs camera access to ${actionText}.`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Start Camera',
            cancelButtonText: 'Cancel',
            background: '#0a0a0a',
            color: '#fff',
            confirmButtonColor: confirmColor,
            cancelButtonColor: 'rgba(255,255,255,0.1)'
        });

        if (!confirm.isConfirmed) return;

        isInitializing = true;
        const initOverlay = document.getElementById('initOverlay');
        const scannerGrid = document.getElementById('vanguardGrid');
        const cameraControls = document.getElementById('cameraControls');
        const gateCondition = document.getElementById('gateCondition');

        // Visual Feedback for Mode
        if (mode === 'exit') {
            document.querySelector('.scan-target').style.borderColor = '#ef4444';
            document.querySelector('.laser-line').style.background = '#ef4444';
            document.querySelector('.laser-line').style.boxShadow = '0 0 20px #ef4444';
        } else {
            document.querySelector('.scan-target').style.borderColor = '#3b82f6';
            document.querySelector('.laser-line').style.background = '#3b82f6';
            document.querySelector('.laser-line').style.boxShadow = '0 0 20px #3b82f6';
        }

        try {
            // 2. Clear existing scanner session
            if (html5QrCode) {
                try {
                     // Check if scanning
                     if(html5QrCode.isScanning) {
                         await html5QrCode.stop();
                     }
                     html5QrCode.clear();
                } catch (e) { console.log(e); }
            }

            // 3. Request Camera - DIRECT BROWSER API FIRST
            html5QrCode = new Html5Qrcode("reader");
            const config = { fps: 20, qrbox: { width: 300, height: 300 } };
            
            try {
                // First, verify we can even touch the camera via browser API
                // This bypasses the library to confirm hardware access
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                
                // If we get here, browser has granted access!
                // Stop this test stream immediately so library can use it
                stream.getTracks().forEach(track => track.stop());
                
                // Now get the specific device ID we want to use
                const devices = await Html5Qrcode.getCameras();
                if (devices && devices.length) {
                    // Filter: Try to find 'back' or 'environment' camera if desired, else 'user'
                    // For now, let's just grab the first available one to GUARANTEE it works
                    const cameraId = devices[0].id; // Simply take the first valid one

                    await html5QrCode.start(cameraId, config, onScanSuccess);
                } else {
                    throw new Error("Browser granted permission but no camera devices found.");
                }

            } catch (cameraErr) {
                console.error("Direct Access Failed:", cameraErr);
                
                // Fallback: Last ditch effort, let library try its own magic blindly
                // This handles cases where getUserMedia behaves oddly on some OS/Browsers
                console.warn("Retrying with blind library start...");
                await html5QrCode.start({ facingMode: "user" }, config, onScanSuccess);
            }

            // 4. Success State
            initOverlay.classList.add('active');
            scannerGrid.style.display = 'block';
            cameraControls.style.display = 'flex';
            addToLog(`Sensor: ${mode.toUpperCase()} MODE initialized`, "info");

            if (gateCondition) {
                gateCondition.textContent = mode === 'entry' ? 'ENTRY ACTIVE' : 'EXIT ACTIVE';
                gateCondition.style.color = mode === 'entry' ? '#10b981' : '#ef4444';
            }

        } catch (err) {
            console.error("Scanner Error:", err);
            
            // Specific Error Handling for Permissions
            let errorMsg = 'Could not access camera.';
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                errorMsg = 'Camera permission was denied. Please allow camera access in your browser address bar.';
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                errorMsg = 'No camera device found on this system.';
            } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                 errorMsg = 'Camera is already in use by another application.';
            }

            Swal.fire({
                title: 'Sensor Error',
                text: errorMsg,
                icon: 'error',
                background: '#0a0a0a',
                color: '#fff',
                confirmButtonText: 'Retry'
            });
            addToLog("Error: " + errorMsg, "error");
        } finally {
            isInitializing = false;
        }
    }

    async function stopScanner() {
        if (!html5QrCode) return;

        try {
            await html5QrCode.stop();
            html5QrCode = null;

            document.getElementById('initOverlay').classList.remove('active');
            document.getElementById('vanguardGrid').style.display = 'none';
            document.getElementById('cameraControls').style.display = 'none';
            document.getElementById('gateCondition').textContent = 'SECURE';
            addToLog("Sensor: Hardware decommissioned", "info");
        } catch (err) {
            console.error("Stop error:", err);
            // Forced reset if stop fails
            location.reload();
        }
    }

    let currentCamera = 'user';
    async function switchCamera() {
        currentCamera = currentCamera === 'environment' ? 'user' : 'environment';
        await stopScanner(); // Stop first
        await startScanner(currentScanMode); // Restart with new mode
    }

    async function resetTerminal() {
        if (html5QrCode) {
            try {
                await html5QrCode.stop();
            } catch (e) { }
        }
        location.reload();
    }

    function manualReset() {
        isProcessing = false;
        document.getElementById('nextScanBtn').style.display = 'none';
        document.getElementById('gateCondition').style.color = '#10b981';
        document.getElementById('gateCondition').textContent = 'ACTIVE';
        addToLog("System: Manual unlock triggered", "info");
    }

    // Removed showEventList as requested


    function processManualEntry() {
        const id = document.getElementById('manualTicketId').value.trim();
        if (!id) return;

        onScanSuccess(id);
        document.getElementById('manualTicketId').value = '';
    }

    function toggleFlash() {
        if (!html5QrCode) return;
        const state = html5QrCode.getState();
        if (state !== Html5QrcodeScannerState.SCANNING) return;

        // This is a browser-dependent feature, might not work on all devices
        html5QrCode.applyVideoConstraints({
            advanced: [{ torch: true }]
        }).catch(() => {
            addToLog("Sensor: Flashlight not supported on this device", "error");
        });
    }

    function updateCapacity() {
        const total = 500; // Expected capacity, can be dynamic
        const percent = Math.min((insideNow / total) * 100, 100);
        document.getElementById('capacityFill').style.width = percent + '%';
        document.getElementById('capacityPercent').textContent = Math.round(percent) + '%';
    }

    function toggleFullScreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }

    function onScanSuccess(decodedText) {
        if (isProcessing) return;

        const eId = document.getElementById('activeEventId').value;
        if (!eId || eId == 0) {
            addToLog("Error: No active event found in system.", "error");
            Swal.fire('Configuration Error', 'No active event found. Please contact Administrator to create an event first.', 'error');
            return;
        }

        isProcessing = true;
        document.getElementById('nextScanBtn').style.display = 'flex';
        document.getElementById('gateCondition').textContent = 'PROCESSING';

        fetch('../api/attendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ qr_token: decodedText, event_id: eId, mode: currentScanMode })
        })
            .then(res => res.json())
            .then(data => {
                const profile = document.getElementById('recentClearance');

                if (data.success) {
                    successSound.play().catch(e => { });
                    showFeedback(data.type.toUpperCase() + ' GRANTED');
                    addToLog(`${data.user_name} - ${data.message}`, data.type);

                    document.getElementById('clearanceName').textContent = data.user_name;
                    document.getElementById('clearanceDetails').textContent = `${data.type.toUpperCase()} • ${new Date().toLocaleTimeString()}`;
                    profile.classList.add('active');

                    document.getElementById('gateCondition').style.color = '#10b981';
                    document.getElementById('gateCondition').textContent = 'CLEAR';

                    if (data.type === 'entry') {
                        entriesToday++;
                        insideNow++;
                    } else if (data.type === 'exit') {
                        insideNow--;
                    }

                    document.getElementById('countToday').textContent = entriesToday;
                    document.getElementById('countInside').textContent = insideNow;
                    updateCapacity();
                } else {
                    errorSound.play().catch(e => { });
                    showFeedback('DENIED', true);
                    addToLog(`Failed: ${data.error}`, 'error');

                    document.getElementById('gateCondition').style.color = '#ef4444';
                    document.getElementById('gateCondition').textContent = 'BLOCKED!';
                    profile.classList.remove('active');
                }

                // Auto-reset after 3 seconds, or manual override available
                setTimeout(() => {
                    if (isProcessing) manualReset();
                }, 3000);
            })
            .catch(err => {
                console.error(err);
                manualReset();
            });
    }

    // Initialize UI
    updateCapacity();

    // Live Clock
    setInterval(() => {
        document.getElementById('liveTime').textContent = new Date().toLocaleTimeString('en-US', { hour12: false });
    }, 1000);
</script>

<?php require_once '../includes/footer.php'; ?>