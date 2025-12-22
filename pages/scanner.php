<?php
require_once '../includes/header.php';
// Ideally check for 'gatekeeper' or 'admin' role here
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'gatekeeper')) {
    echo "<h2 style='text-align:center;'>Access Denied</h2>";
    exit;
}
?>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<div style="max-width: 600px; margin: 2rem auto; text-align: center;">
    <h2>Event Entry/Exit Scanner</h2>

    <div id="reader" style="width: 100%; margin: 2rem 0; border-radius: 16px; overflow: hidden;"></div>

    <div id="scan-result" class="glass-panel" style="padding: 1rem; display: none;">
        <h3 id="result-message"></h3>
        <p id="result-user" style="font-size: 1.2rem;"></p>
        <span id="result-role" class="status-badge" style="background: var(--primary);"></span>
    </div>

    <div class="glass-panel" style="margin-top: 2rem; padding: 1rem;">
        <h3>Live Inside Count: <span id="live-count" style="color: var(--primary);">0</span></h3>
    </div>
</div>

<script>
    const resultDiv = document.getElementById('scan-result');
    const msgEl = document.getElementById('result-message');
    const userEl = document.getElementById('result-user');
    const roleEl = document.getElementById('result-role');
    const countEl = document.getElementById('live-count');

    let processing = false;

    async function onScanSuccess(decodedText, decodedResult) {
        if (processing) return;
        processing = true;

        try {
            const res = await fetch('/Project/api/attendance.php?action=scan', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ qr_token: decodedText })
            });
            const data = await res.json();

            resultDiv.style.display = 'block';

            if (data.success) {
                msgEl.innerText = data.message;
                userEl.innerText = data.user;
                roleEl.innerText = data.role.toUpperCase();
                countEl.innerText = data.current_count;

                if (data.type === 'entry') msgEl.style.color = '#4ade80';
                else msgEl.style.color = '#f87171';

                // Color Code Roles
                roleEl.className = 'status-badge';
                if (data.role === 'internal') roleEl.style.background = '#3b82f6'; // Blue
                else if (data.role === 'faculty') roleEl.style.background = '#8b5cf6'; // Purple
                else roleEl.style.background = '#f59e0b'; // Orange (External)

                // Audio feedback could be added here
            } else {
                msgEl.innerText = data.error;
                msgEl.style.color = 'red';
            }

            // Pause slightly before next scan logic if needed, 
            // but library handles continuous scanning. 
            // Just delay processing flag reset.
            setTimeout(() => { processing = false; }, 2000);

        } catch (e) {
            console.error(e);
            processing = false;
        }
    }

    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader",
        { fps: 10, qrbox: { width: 250, height: 250 } },
        /* verbose= */ false);
    html5QrcodeScanner.render(onScanSuccess);
</script>

<?php require_once '../includes/footer.php'; ?>