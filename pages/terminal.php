<?php
require_once '../includes/header.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Gatekeepers can use this too
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'gatekeeper') {
        echo "<h2 style='text-align:center;'>Access Denied. Admins/Gatekeepers Only.</h2>";
        exit;
    }
}
?>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<div style="max-width: 1000px; margin: 2rem auto;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2>🔐 Gate Entry Terminal</h2>
        <a href="dashboard.php" class="btn" style="background: rgba(255,255,255,0.1);">Back to Dashboard</a>
    </div>

    <!-- Event Selector -->
    <div class="glass-panel" style="padding: 1.5rem; margin-bottom: 2rem;">
        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Select Active Event</label>
        <select id="eventSelect" style="font-size: 1.1rem; padding: 0.5rem;">
            <option value="">-- Loading Events --</option>
        </select>
        <div id="eventDetails" style="margin-top: 1rem; font-size: 0.9rem; color: #cbd5e1; display: none;">
            Fee: <span id="eventFee"></span> | GST: <span id="eventGst"></span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">

        <!-- Column 1: Scanner -->
        <div>
            <h3 style="margin-bottom: 1rem;">📸 Scan Entry</h3>
            <div id="reader"
                style="width: 100%; border-radius: 12px; overflow: hidden; border: 2px solid rgba(255,255,255,0.1);">
            </div>

            <div id="scanResult" class="glass-panel"
                style="margin-top: 1rem; padding: 1rem; text-align: center; display: none;">
                <h3 id="scanMsg"></h3>
                <p id="scanUser"></p>
            </div>
        </div>

        <!-- Column 2: Walk-In / Manual -->
        <div class="glass-panel" style="padding: 1.5rem; height: fit-content;">
            <h3 style="margin-bottom: 1rem;">📝 Walk-In / Manual Entry</h3>

            <div style="margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 1rem;">
                <button onclick="setMode('internal')" id="btnInternal" class="btn btn-primary"
                    style="opacity: 0.5;">Internal ID</button>
                <button onclick="setMode('external')" id="btnExternal" class="btn btn-primary">External
                    (Walk-in)</button>
            </div>

            <form id="manualForm">
                <input type="hidden" name="role" id="manualRole" value="external">

                <div id="field-name">
                    <label>Visitor Name</label>
                    <input type="text" name="name" required>
                </div>

                <div id="field-email">
                    <label>Email / Phone</label>
                    <input type="text" name="email" required placeholder="For record keeping">
                </div>

                <div id="field-id" style="display: none;">
                    <label>Student/Faculty ID</label>
                    <input type="text" name="student_id">
                </div>

                <div class="glass-panel" style="background: rgba(0,0,0,0.2); padding: 1rem; margin: 1rem 0;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Entry Fee:</span>
                        <span id="calcBase">₹0.00</span>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted);">
                        <span>GST:</span>
                        <span id="calcGst">₹0.00</span>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; font-weight: bold; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 1.2rem;">
                        <span>TOTAL TO COLLECT:</span>
                        <span id="calcTotal" style="color: #4ade80;">₹0.00</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.1rem; padding: 1rem;">
                    ✅ Collect Payment & Admit
                </button>
            </form>
        </div>

    </div>
</div>

<script src="/Project/assets/js/terminal.js"></script>

<?php require_once '../includes/footer.php'; ?>