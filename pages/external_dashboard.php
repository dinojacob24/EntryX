<?php
session_start();
// Access Level Check - Only External Users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'external') {
    header('Location: user_login.php');
    exit;
}

require_once '../includes/header.php';
require_once '../config/db_connect.php';
require_once '../classes/Event.php';
require_once '../classes/Registration.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];
$userEmail = $_SESSION['email'];

$eventObj = new Event($pdo);
$regObj = new Registration($pdo);

// Fetch User's Entry QR Token and Primary Program Details
$stmt = $pdo->prepare("
    SELECT u.qr_token, ep.program_name, ep.start_date, ep.program_description 
    FROM users u 
    LEFT JOIN external_programs ep ON u.external_program_id = ep.id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$primaryPass = $stmt->fetch(PDO::FETCH_ASSOC);

$allEvents = $eventObj->getAllEvents();
$myRegs = $regObj->getUserRegistrations($userId);

// Filter available events
$registeredEventIds = array_column($myRegs, 'event_id');
$availableEvents = array_filter($allEvents, function ($e) use ($registeredEventIds) {
    return !in_array($e['id'], $registeredEventIds);
});

// Calculate stats
$totalEventsJoined = count($myRegs);
$upcomingEventsCount = count($availableEvents);
?>

<div class="dashboard-container" style="padding: 2rem 5%; min-height: 90vh; background: #000;">
    <!-- Top Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
        <div>
            <h1 style="color: white; font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">Hello,
                <?php echo explode(' ', $userName)[0]; ?>! 👋
            </h1>
            <p style="color: var(--p-text-dim);">Welcome to your Guest Experience Hub.</p>
        </div>
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <!-- View Entry QR Code Button -->
            <button onclick="showEntryQR()" class="btn btn-primary"
                style="border-radius: 99px; padding: 0.6rem 1.5rem; display: flex; align-items: center; gap: 0.5rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                <i class="fa-solid fa-qrcode"></i> View Entry QR Code
            </button>
            <div
                style="background: rgba(255,255,255,0.05); padding: 0.6rem 1.5rem; border-radius: 99px; border: 1px solid var(--p-border);">
                <span
                    style="color: #ff1f1f; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-right: 0.8rem;">GUEST</span>
                <span
                    style="color: white; font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($userEmail); ?></span>
            </div>
            <a href="../api/auth.php?action=logout" class="btn btn-outline"
                style="border-radius: 99px; padding: 0.6rem 1.5rem; border-color: rgba(239, 68, 68, 0.3); color: #ef4444; background: none; border-style: solid; border-width: 1px; text-decoration: none;">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>


        </div>
    </div>

    <!-- Stats Grid -->
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-bottom: 4rem;">
        <div class="glass-panel"
            style="padding: 2rem; display: flex; align-items: center; gap: 1.5rem; border-color: rgba(255,31,31,0.1); background: rgba(255,255,255,0.03);">
            <div
                style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); border-radius: 18px; display: flex; align-items: center; justify-content: center; color: #10b981;">
                <i class="fa-solid fa-ticket fa-xl"></i>
            </div>
            <div>
                <div style="font-size: 1.8rem; font-weight: 800; color: white;"><?php echo $totalEventsJoined; ?></div>
                <div
                    style="color: var(--p-text-dim); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                    Events Joined</div>
            </div>
        </div>
        <div class="glass-panel"
            style="padding: 2rem; display: flex; align-items: center; gap: 1.5rem; border-color: rgba(255,31,31,0.1); background: rgba(255,255,255,0.03);">
            <div
                style="width: 60px; height: 60px; background: rgba(59, 130, 246, 0.1); border-radius: 18px; display: flex; align-items: center; justify-content: center; color: #3b82f6;">
                <i class="fa-solid fa-calendar-star fa-xl"></i>
            </div>
            <div>
                <div style="font-size: 1.8rem; font-weight: 800; color: white;"><?php echo $upcomingEventsCount; ?>
                </div>
                <div
                    style="color: var(--p-text-dim); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                    Available Events</div>
            </div>
        </div>
    </div>

    <!-- Main Content - Full Width Events -->
    <div>
        <!-- My Registered Events -->
        <div style="margin-bottom: 4rem;">
            <h3
                style="color: white; font-weight: 800; font-size: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                <i class="fa-solid fa-receipt" style="color: var(--p-brand);"></i> My Event Tickets
            </h3>

            <?php if (empty($myRegs)): ?>
                <div class="glass-panel" style="text-align: center; padding: 4rem; background: rgba(255,255,255,0.01);">
                    <i class="fa-solid fa-ticket-simple fa-3x"
                        style="color: var(--p-text-dim); margin-bottom: 1.5rem; opacity: 0.3;"></i>
                    <p style="color: var(--p-text-dim);">You haven't registered for any additional events yet.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($myRegs as $reg): ?>
                        <div class="glass-panel"
                            style="padding: 1.5rem; border-color: rgba(255,255,255,0.05); transition: 0.3s; cursor: pointer; background: rgba(255,255,255,0.02);"
                            onclick="showTicket(<?php echo htmlspecialchars(json_encode($reg)); ?>)">
                            <div style="display: flex; gap: 1.5rem; align-items: center;">
                                <div
                                    style="width: 50px; height: 50px; background: rgba(255,31,31,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--p-brand); font-weight: 800;">
                                    <?php echo date('d', strtotime($reg['event_date'])); ?>
                                </div>
                                <div>
                                    <h4 style="color: white; font-weight: 700; margin: 0;">
                                        <?php echo htmlspecialchars($reg['event_name']); ?>
                                    </h4>
                                    <p style="color: var(--p-text-dim); font-size: 0.8rem; margin: 0;"><i
                                            class="fa-solid fa-location-dot"></i>
                                        <?php echo htmlspecialchars($reg['venue']); ?></p>
                                    <span
                                        class="status-badge <?php echo in_array($reg['payment_status'], ['free', 'completed']) ? 'status-inside' : 'status-pending'; ?>"
                                        style="margin-top: 0.5rem; display: inline-block;">
                                        <?php echo strtoupper(in_array($reg['payment_status'], ['free', 'completed']) ? 'Confirmed' : $reg['payment_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Hall of Fame / Results section -->
        <div style="margin-bottom: 4rem;">
            <h3
                style="color: white; font-weight: 800; font-size: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                <i class="fa-solid fa-trophy" style="color: #eab308;"></i> Hall of Fame
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
                <?php
                // Fetch recent results
                $resStmt = $pdo->query("SELECT r.*, e.name as event_name FROM results r JOIN events e ON r.event_id = e.id ORDER BY r.published_at DESC LIMIT 3");
                $recentResults = $resStmt->fetchAll();

                if (empty($recentResults)): ?>
                    <div class="glass-panel"
                        style="text-align: center; padding: 3rem; background: rgba(255,255,255,0.01); width: 100%; grid-column: 1/-1;">
                        <p style="color: var(--p-text-dim);">Historical records will appear here once published.</p>
                    </div>
                <?php else:
                    foreach ($recentResults as $res): ?>
                        <div class="glass-panel"
                            style="padding: 1.5rem; border-color: rgba(234, 179, 8, 0.2); background: rgba(234,179,8,0.03); position: relative; overflow: hidden;">
                            <div style="position: absolute; top: -10px; right: -10px; color: rgba(234,179,8,0.1);">
                                <i class="fa-solid fa-medal fa-5x"></i>
                            </div>
                            <h4 style="color: white; font-weight: 800; font-size: 1.2rem; margin-bottom: 0.3rem;">
                                <?php echo htmlspecialchars($res['winner_name']); ?>
                            </h4>
                            <p
                                style="color: #eab308; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1rem;">
                                Winnner - <?php echo htmlspecialchars($res['event_name']); ?></p>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1rem;">
                                <span style="color: var(--p-text-dim); font-size: 0.8rem;">Runner Up:
                                    <?php echo htmlspecialchars($res['runner_up_name']); ?></span>
                                <a href="results.php"
                                    style="color: var(--p-brand); font-size: 0.8rem; font-weight: 700; text-decoration: none;">View
                                    All <i class="fa-solid fa-chevron-right fa-xs"></i></a>
                            </div>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>

        <!-- Available Events -->
        <div>
            <h3
                style="color: white; font-weight: 800; font-size: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                <i class="fa-solid fa-compass" style="color: var(--p-brand);"></i> Explore Events
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
                <?php foreach ($availableEvents as $event): ?>
                    <div class="glass-panel"
                        style="padding: 2rem; border-color: rgba(255,255,255,0.05); display: flex; flex-direction: column; justify-content: space-between; background: rgba(255,255,255,0.02);">
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                                <span
                                    style="padding: 0.4rem 0.8rem; background: rgba(255,31,31,0.1); color: var(--p-brand); border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;"><?php echo htmlspecialchars($event['type']); ?></span>
                                <div style="text-align: right;">
                                    <div style="color: #10b981; font-weight: 800; font-size: 1.1rem;">
                                        <?php
                                        if ($event['is_paid']) {
                                            $basePrice = floatval($event['base_price']);
                                            $displayPrice = $basePrice;

                                            // Check if GST applies to external users
                                            if ($event['is_gst_enabled'] && in_array($event['gst_target'], ['both', 'externals_only'])) {
                                                $gstRate = floatval($event['gst_rate']);
                                                $gstAmount = $basePrice * ($gstRate / 100);
                                                $displayPrice = $basePrice + $gstAmount;
                                            }

                                            echo '₹' . number_format($displayPrice, 2);
                                        } else {
                                            echo 'FREE';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <h4 style="color: white; font-weight: 800; font-size: 1.3rem; margin-bottom: 0.85rem;">
                                <?php echo htmlspecialchars($event['name']); ?>
                            </h4>
                            <p
                                style="color: var(--p-text-dim); font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.5rem; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($event['description']); ?>
                            </p>
                        </div>
                        <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1.5rem; margin-top: auto;">
                            <div
                                style="display: flex; gap: 1rem; margin-bottom: 1.5rem; color: var(--p-text-dim); font-size: 0.8rem; font-weight: 600;">
                                <span><i class="fa-regular fa-calendar"
                                        style="color: var(--p-brand); margin-right: 0.5rem;"></i>
                                    <?php echo date('d M Y', strtotime($event['event_date'])); ?></span>
                                <span><i class="fa-solid fa-location-dot"
                                        style="color: var(--p-brand); margin-right: 0.5rem;"></i>
                                    <?php echo htmlspecialchars($event['venue']); ?></span>
                            </div>
                            <button onclick="handleRegistration(<?php echo htmlspecialchars(json_encode($event)); ?>)"
                                class="btn btn-primary" style="width: 100%; border-radius: 12px; font-weight: 700;">Register
                                Now</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</div>

</div>

<!-- Ticket Modal -->
<div id="ticketModal"
    style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); backdrop-filter: blur(10px); z-index: 1000; align-items: center; justify-content: center; padding: 2rem;">
    <div class="glass-panel"
        style="width: 100%; max-width: 450px; max-height: 90vh; overflow-y: auto; padding: 0; background: white; border-radius: 30px; position: relative; border: none;">
        <button onclick="closeTicket()"
            style="position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(0,0,0,0.1); border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer;"><i
                class="fa-solid fa-times"></i></button>
        <div id="modalHeader"
            style="background: var(--p-brand); padding: 3rem 2rem; text-align: center; color: white; border-radius: 30px 30px 0 0;">
            <p
                style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.2rem; margin-bottom: 0.5rem; opacity: 0.8;">
                Event Ticket</p>
            <h2 id="modalEventName" style="font-weight: 900; margin: 0; font-size: 2rem;"></h2>
        </div>
        <div style="padding: 3rem 2rem; text-align: center;">
            <div id="modalQr"
                style="display: inline-block; padding: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; margin-bottom: 2rem;">
            </div>
            <div style="margin-bottom: 2.5rem;">
                <h4 style="color: #0f172a; font-weight: 800; font-size: 1.4rem; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($userName); ?>
                </h4>
                <div
                    style="display: flex; justify-content: center; gap: 1.5rem; color: #64748b; font-size: 0.9rem; font-weight: 600;">
                    <span id="modalDate"></span>
                    <span id="modalVenue"></span>
                </div>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button class="btn btn-outline"
                    style="flex: 1; border-radius: 12px; font-weight: 700; color: #0f172a; border-color: #e2e8f0; background: none; border-style: solid; border-width: 1px;"
                    onclick="window.print()">
                    <i class="fa-solid fa-download"></i> Download
                </button>
                <button id="cancelBtn" class="btn btn-outline"
                    style="flex: 1; border-radius: 12px; font-weight: 700; color: #ef4444; border-color: #fee2e2; background: #fff1f2; border-style: solid; border-width: 1px;"
                    onclick="">
                    <i class="fa-solid fa-trash"></i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Entry QR Code Modal -->
<div id="entryQRModal"
    style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); backdrop-filter: blur(10px); z-index: 1001; align-items: center; justify-content: center; padding: 2rem;">
    <div class="glass-panel"
        style="width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; padding: 0; background: white; border-radius: 30px; position: relative; border: none;">
        <button onclick="closeEntryQR()"
            style="position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(0,0,0,0.1); border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; z-index: 10;">
            <i class="fa-solid fa-times"></i>
        </button>
        <div
            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 3rem 2rem; text-align: center; color: white; border-radius: 30px 30px 0 0;">
            <i class="fa-solid fa-shield-halved fa-2x" style="margin-bottom: 1rem; opacity: 0.9;"></i>
            <p
                style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.2rem; margin-bottom: 0.5rem; opacity: 0.9;">
                College Entry Pass</p>
            <h2 style="font-weight: 900; margin: 0; font-size: 1.8rem;">
                <?php echo htmlspecialchars($primaryPass['program_name'] ?? 'General Entry'); ?>
            </h2>
        </div>
        <div style="padding: 3rem 2rem; text-align: center;">
            <div id="entryQrContainer"
                style="display: inline-block; padding: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; margin-bottom: 2rem;">
            </div>
            <div style="margin-bottom: 2.5rem;">
                <h4 style="color: #0f172a; font-weight: 800; font-size: 1.4rem; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($userName); ?>
                </h4>
                <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($userEmail); ?>
                </p>
                <p style="color: #64748b; font-size: 0.9rem;">
                    <i class="fa-regular fa-calendar"></i>
                    Registered: <?php echo date('d M Y', strtotime($primaryPass['start_date'] ?? date('Y-m-d'))); ?>
                </p>
            </div>
            <div
                style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 12px; padding: 1rem; margin-bottom: 2rem;">
                <p style="color: #92400e; font-size: 0.85rem; margin: 0; font-weight: 600;">
                    <i class="fa-solid fa-shield-halved"></i> Show this QR code at the college gate for entry
                    verification
                </p>
            </div>
            <button class="btn btn-primary"
                style="width: 100%; border-radius: 12px; font-weight: 700; padding: 1rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);"
                onclick="window.print()">
                <i class="fa-solid fa-download"></i> Download / Print
            </button>
        </div>
    </div>
</div>

</div>
</div>

<!-- Event Payment Modal (Restored) -->
<div id="eventPaymentModal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); backdrop-filter: blur(15px); z-index: 2500; justify-content: center; align-items: center; padding: 1rem;">
    <div class="glass-panel"
        style="max-width: 450px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 2.5rem; border-radius: 32px; border-color: rgba(255,165,0,0.3); background: #fff; color: #000; position: relative;">
        <h3 id="paymentEventName" style="color: #000; font-weight: 800; margin-bottom: 0.5rem;">Event Payment</h3>
        <p style="color: #666; font-size: 0.9rem; margin-bottom: 2rem;">Please scan the QR code to pay the registration
            fee.</p>

        <div
            style="background: #f8fafc; padding: 2rem; border-radius: 24px; text-align: center; margin-bottom: 2rem; border: 1px solid #e2e8f0;">
            <div id="eventPaymentQr" style="display: inline-block; margin-bottom: 1rem;"></div>

            <!-- Fee Breakdown -->
            <div id="paymentFeeBreakdown"
                style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(16, 185, 129, 0.05); border-radius: 12px; display: none;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: #64748b; font-size: 0.85rem;">Base Fee:</span>
                    <span id="paymentBaseAmount"
                        style="color: #0f172a; font-weight: 600; font-size: 0.85rem;">₹0.00</span>
                </div>
                <div id="paymentGstRow" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: #64748b; font-size: 0.85rem;">GST (<span id="paymentGstRate">18</span>%):</span>
                    <span id="paymentGstAmount"
                        style="color: #0f172a; font-weight: 600; font-size: 0.85rem;">₹0.00</span>
                </div>
                <div
                    style="border-top: 1px solid rgba(100, 116, 139, 0.2); padding-top: 0.5rem; display: flex; justify-content: space-between;">
                    <strong style="color: #0f172a; font-size: 0.9rem;">Total Payable:</strong>
                    <strong id="paymentTotalBreakdown" style="color: #10b981; font-size: 0.9rem;">₹0.00</strong>
                </div>
            </div>

            <div style="font-size: 1.8rem; font-weight: 900; color: #10b981; margin-bottom: 0.5rem;" id="paymentAmount">
                ₹0.00</div>
            <div style="color: #64748b; font-size: 0.85rem; font-weight: 600;">UPI ID: <span id="paymentUpiId"
                    style="color: #0f172a;"></span></div>

            <label
                style="display: block; color: #475569; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem; letter-spacing: 0.05em;">Transaction
                ID / UTR No.</label>
            <input type="text" id="eventTransactionId" placeholder="12-digit UTR number" maxlength="12"
                style="width: 100%; padding: 0.8rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 0.9rem; color: #000; outline: none; transition: 0.3s;">
            <small style="color: #94a3b8; font-size: 0.65rem; display: block; margin-top: 0.4rem;">Provide the 12-digit
                UPI transaction number shown in your app</small>
            <div
                style="margin-top: 1rem; padding: 0.8rem; background: rgba(239, 68, 68, 0.05); border: 1px dashed rgba(239, 68, 68, 0.3); border-radius: 10px;">
                <p style="color: #ef4444; font-size: 0.65rem; font-weight: 700; margin: 0; line-height: 1.4;">
                    <i class="fa-solid fa-circle-exclamation"></i> IMPORTANT: Only the exact fee amount shown above will
                    be approved. Random or incorrect IDs will lead to registration cancellation.
                </p>
            </div>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button onclick="document.getElementById('eventPaymentModal').style.display='none'" class="btn btn-outline"
                style="flex: 1; border-radius: 12px; font-weight: 700; color: #64748b; border-color: #e2e8f0; background: #f1f5f9;">
                Cancel
            </button>
            <button id="confirmPaymentBtn" class="btn btn-primary"
                style="flex: 2; border-radius: 12px; font-weight: 700; background: #10b981; border: none;">
                Confirm & Register
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/qrcode.min.js"></script>
<script>
    // Initialize Entry QR Code (not displayed by default)
    const primaryToken = <?php echo json_encode($primaryPass['qr_token'] ?? ''); ?>;
    let entryQR = null;

    function showEntryQR() {
        const modal = document.getElementById('entryQRModal');
        const container = document.getElementById('entryQrContainer');

        // Generate QR if not already generated
        if (!entryQR && primaryToken) {
            entryQR = new QRCode(container, {
                text: primaryToken,
                width: 220,
                height: 220,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeEntryQR() {
        const modal = document.getElementById('entryQRModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Close on backdrop click
    document.getElementById('entryQRModal').onclick = (e) => {
        if (e.target === document.getElementById('entryQRModal')) closeEntryQR();
    };

    // Modal Handling for Event Tickets
    const modal = document.getElementById('ticketModal');
    let activeQr = null;

    function showTicket(reg) {
        document.getElementById('modalEventName').innerText = reg.event_name;
        document.getElementById('modalDate').innerHTML = `<i class="fa-regular fa-calendar"></i> ${reg.event_date}`;
        document.getElementById('modalVenue').innerHTML = `<i class="fa-solid fa-location-dot"></i> ${reg.venue}`;

        // Update cancel button
        const cancelBtn = document.getElementById('cancelBtn');
        cancelBtn.onclick = () => cancelRegistration(reg.id, reg.event_name);

        const modalQrContainer = document.getElementById('modalQr');
        modalQrContainer.innerHTML = '';
        activeQr = new QRCode(modalQrContainer, {
            text: reg.qr_token,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeTicket() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }


    // Event Registration Logic
    let currentPaymentEventId = null;

    async function handleRegistration(event) {
        const eventId = typeof event === 'object' ? event.id : event;
        const isPaid = (typeof event === 'object' && event.is_paid == '1');

        if (isPaid) {
            currentPaymentEventId = eventId;
            const upiId = event.payment_upi || 'admin@upi';
            const basePrice = parseFloat(event.base_price) || 0;

            // Calculate GST if applicable for external users
            let totalAmount = basePrice;
            let gstAmount = 0;
            let gstRate = 0;
            const isGstEnabled = event.is_gst_enabled == '1';
            const gstTarget = event.gst_target || 'both';
            const gstApplies = isGstEnabled && (gstTarget === 'both' || gstTarget === 'externals_only');

            if (gstApplies) {
                gstRate = parseFloat(event.gst_rate) || 18;
                gstAmount = basePrice * (gstRate / 100);
                totalAmount = basePrice + gstAmount;
            }

            document.getElementById('paymentEventName').innerText = event.name || 'Event';
            document.getElementById('paymentAmount').innerText = '₹' + totalAmount.toFixed(2);
            document.getElementById('paymentUpiId').innerText = upiId;
            document.getElementById('eventTransactionId').value = ''; // Clear previous

            // Show/hide breakdown based on GST
            const breakdownDiv = document.getElementById('paymentFeeBreakdown');
            if (gstApplies) {
                breakdownDiv.style.display = 'block';
                document.getElementById('paymentBaseAmount').innerText = '₹' + basePrice.toFixed(2);
                document.getElementById('paymentGstRate').innerText = gstRate.toFixed(0);
                document.getElementById('paymentGstAmount').innerText = '₹' + gstAmount.toFixed(2);
                document.getElementById('paymentTotalBreakdown').innerText = '₹' + totalAmount.toFixed(2);
                document.getElementById('paymentGstRow').style.display = 'flex';
            } else {
                breakdownDiv.style.display = 'none';
            }

            // Generate QR with total amount
            const qrContainer = document.getElementById('eventPaymentQr');
            qrContainer.innerHTML = '';
            const qrUrl = `upi://pay?pa=${upiId}&pn=EntryX&am=${totalAmount.toFixed(2)}&tn=Reg ${eventId}`;

            new QRCode(qrContainer, {
                text: qrUrl,
                width: 150,
                height: 150,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });

            document.getElementById('eventPaymentModal').style.display = 'flex';
            return;
        }

        proceedWithRegistration(eventId);
    }

    // Manual Payment Handler
    document.getElementById('confirmPaymentBtn').onclick = function () {
        const txId = document.getElementById('eventTransactionId').value.trim();
        if (!txId || txId.length < 5) { // Basic length check
            Swal.fire('Error', 'Please enter a valid Transaction ID / UTR Number', 'error');
            return;
        }
        document.getElementById('eventPaymentModal').style.display = 'none';
        proceedWithRegistration(currentPaymentEventId, txId);
    };

    async function proceedWithRegistration(eventId, transactionId = null) {
        const confirmResult = await Swal.fire({
            title: 'Confirm Registration?',
            text: 'Would you like to register for this event?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ff1f1f',
            confirmButtonText: 'Confirm Registration',
            background: '#0a0a0a',
            color: '#fff'
        });

        if (confirmResult.isConfirmed) {
            Swal.fire({
                title: 'Registering...',
                didOpen: () => Swal.showLoading(),
                background: '#0a0a0a',
                color: '#fff'
            });

            try {
                const response = await fetch('../api/registrations.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        event_id: eventId,
                        transaction_id: transactionId
                    })
                });

                const result = await response.json();
                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: result.payment_needed ? 'Registration submitted for verification.' : 'You have been registered successfully.',
                        confirmButtonColor: '#ff1f1f',
                        background: '#0a0a0a',
                        color: '#fff'
                    });
                    const redirectUrl = result.payment_needed ? 'external_dashboard.php?submitted=1' :
                        'external_dashboard.php?registered=1';
                    window.location.href = redirectUrl;
                } else {
                    throw new Error(result.error || 'Registration failed');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    confirmButtonColor: '#ff1f1f',
                    background: '#0a0a0a',
                    color: '#fff'
                });
            }
        }
    }

    async function cancelRegistration(regId, eventName) {
        const confirmResult = await Swal.fire({
            title: 'Cancel registration?',
            text: `Are you sure you want to remove your spot for ${eventName}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, cancel it',
            background: '#0a0a0a',
            color: '#fff'
        });

        if (confirmResult.isConfirmed) {
            try {
                const response = await fetch('../api/registrations.php?action=cancel', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ registration_id: regId })
                });

                const result = await response.json();
                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Cancelled',
                        text: 'Registration removed.',
                        background: '#0a0a0a',
                        color: '#fff'
                    });
                    location.reload();
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        }
    }

    // Show success message if redirected after registration
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('registered') || urlParams.has('submitted')) {
            const isPending = urlParams.has('submitted');
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: isPending ? 'Registration Submitted (Pending Verification)' : 'Registration confirmed!',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                background: isPending ? '#f59e0b' : '#10b981',
                color: '#fff'
            });

            // Clear the URL parameters without refreshing the page
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    });

    // Close on backdrop click
    modal.onclick = (e) => { if (e.target === modal) closeTicket(); }
</script>



<?php require_once '../includes/footer.php'; ?>