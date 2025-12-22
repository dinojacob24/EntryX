<?php require_once '../includes/header.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<div style="margin-top: 2rem;">
    <!-- Welcome Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2
                style="font-size: 2rem; background: linear-gradient(to right, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
            </h2>
            <p style="color: var(--text-muted);">Dashboard & Overview</p>
        </div>
        <div style="background: rgba(255,255,255,0.1); padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.9rem;">
            Role: <strong
                style="text-transform: capitalize; color: var(--secondary);"><?php echo $_SESSION['role']; ?></strong>
        </div>
    </div>

    <?php if ($_SESSION['role'] === 'admin'): ?>
        <!-- ================= ADMIN DASHBOARD ================= -->

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">

            <!-- Action Card: Terminal -->
            <div class="glass-panel"
                style="padding: 2rem; display: flex; flex-direction: column; justify-content: space-between; border-left: 4px solid #10b981;">
                <div>
                    <h3 style="margin-bottom: 0.5rem;">Gate Terminal</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Manage Entry, Security Scans, and Walk-in
                        Payments.</p>
                </div>
                <a href="terminal.php" class="btn btn-primary"
                    style="margin-top: 1.5rem; text-align: center; background: linear-gradient(135deg, #10b981, #059669);">
                    🚀 Launch Terminal
                </a>
            </div>

            <!-- Action Card: Results -->
            <div class="glass-panel"
                style="padding: 2rem; display: flex; flex-direction: column; justify-content: space-between; border-left: 4px solid #f59e0b;">
                <div>
                    <h3 style="margin-bottom: 0.5rem;">Results Manager</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Publish event winners and view standings.</p>
                </div>
                <a href="publish_result.php" class="btn"
                    style="margin-top: 1.5rem; text-align: center; background: rgba(255,255,255,0.1);">
                    🏆 Publish Results
                </a>
            </div>

        </div>

        <div class="glass-panel" style="margin-top: 2rem; padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">
                Create New Event</h3>

            <form id="createEventForm" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div style="grid-column: 1 / -1;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #94a3b8;">Event Title</label>
                    <input type="text" name="title" required
                        style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px;">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #94a3b8;">Description</label>
                    <textarea name="description" rows="3"
                        style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px;"></textarea>
                </div>

                <div>
                    <label style="display: block; margin-bottom: 0.5rem; color: #94a3b8;">Date & Time</label>
                    <input type="datetime-local" name="event_date" required
                        style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 0.5rem; color: #94a3b8;">Venue</label>
                    <input type="text" name="venue" required
                        style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 0.5rem; color: #94a3b8;">Base Fee (₹)</label>
                    <input type="number" step="0.01" name="base_fee" required
                        style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 0.5rem; color: #94a3b8;">GST Rate (%)</label>
                    <input type="number" step="0.01" name="gst_rate" value="18"
                        style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px;">
                </div>

                <div
                    style="grid-column: 1 / -1; display: flex; align-items: center; gap: 0.8rem; background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px;">
                    <input type="checkbox" name="has_gst" id="gstCheck"
                        style="width: 20px; height: 20px; accent-color: var(--primary);" checked>
                    <label for="gstCheck" style="cursor: pointer;">Apply GST for External Participants</label>
                </div>

                <div style="grid-column: 1 / -1;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">✨ Publish
                        Event</button>
                </div>
            </form>
        </div>

    <?php else: ?>
        <!-- ================= USER DASHBOARD ================= -->

        <div style="display: grid; grid-template-columns: 1fr 300px; gap: 2rem;">

            <!-- Left Column: Tickets & Events -->
            <div>
                <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; color: #fff;">
                    🎟️ My Active Tickets
                </h3>

                <div id="tickets-grid"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                    <div
                        style="text-align: center; color: var(--text-muted); padding: 2rem; background: rgba(255,255,255,0.05); border-radius: 12px; grid-column: 1/-1;">
                        Loading tickets...
                    </div>
                </div>

                <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; color: #fff;">
                    📅 Upcoming Events
                </h3>
                <div id="events-grid" style="display: grid; gap: 1rem;">
                    <!-- Loaded via JS -->
                </div>
            </div>

            <!-- Right Column: Profile Card -->
            <div>
                <div class="glass-panel" style="padding: 2rem; position: sticky; top: 2rem;">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <img src="<?php echo isset($_SESSION['user_id']) ? 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['name']) . '&background=random&size=128' : ''; ?>"
                            style="width: 80px; height: 80px; border-radius: 50%; margin-bottom: 1rem; border: 3px solid #6366f1;">
                        <h3 style="font-size: 1.2rem;"><?php echo $_SESSION['name']; ?></h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; word-break: break-all;">
                            <?php echo $_SESSION['email'] ?? ''; ?></p>
                        <span
                            style="display: inline-block; background: rgba(99, 102, 241, 0.2); color: #818cf8; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; margin-top: 0.5rem; text-transform: capitalize;">
                            <?php echo $_SESSION['role']; ?>
                        </span>
                    </div>

                    <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;">
                        <a href="/Project/index.php" class="btn btn-primary" style="width: 100%; text-align: center;">Browse
                            All Events</a>
                        <a href="/Project/pages/results.php" class="btn"
                            style="width: 100%; text-align: center; background: rgba(255,255,255,0.05); margin-top: 1rem;">View
                            Results</a>
                    </div>
                </div>
            </div>

        </div>

        <script>
            // Load Tickets
            async function loadTickets() {
                const res = await fetch('/Project/api/events.php?action=my_tickets');
                const data = await res.json();
                const container = document.getElementById('tickets-grid');
                container.innerHTML = '';

                if (data.tickets.length === 0) {
                    container.innerHTML = `
                        <div style="grid-column: 1/-1; text-align: center; padding: 2rem; background: rgba(255,255,255,0.03); border-radius: 12px;">
                            <p style="color: var(--text-muted);">No tickets yet. Register for an event below!</p>
                        </div>
                    `;
                } else {
                    data.tickets.forEach(ticket => {
                        const div = document.createElement('div');
                        div.className = 'glass-panel';
                        div.style.padding = '0';
                        div.style.overflow = 'hidden';
                        div.innerHTML = `
                            <div style="background: linear-gradient(135deg, #4f46e5, #ec4899); padding: 1rem; color: white;">
                                <h4 style="margin: 0;">${ticket.title}</h4>
                            </div>
                            <div style="padding: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.9rem; color: #94a3b8;">📅 ${new Date(ticket.event_date).toLocaleDateString()}</div>
                                    <div style="font-size: 0.9rem; color: #94a3b8;">📍 ${ticket.venue}</div>
                                </div>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=${ticket.qr_token}" alt="QR" style="border-radius: 4px;">
                            </div>
                        `;
                        container.appendChild(div);
                    });
                }
            }

            // Load Events List
            async function loadEvents() {
                const res = await fetch('/Project/api/events.php?action=list');
                const data = await res.json();
                const container = document.getElementById('events-grid');

                data.events.forEach(event => {
                    const div = document.createElement('div');
                    div.className = 'glass-panel';
                    div.style.padding = '1.25rem';
                    div.style.display = 'flex';
                    div.style.justifyContent = 'space-between';
                    div.style.alignItems = 'center';

                    div.innerHTML = `
                        <div>
                            <h4 style="font-size: 1.1rem; color: #f8fafc;">${event.title}</h4>
                            <p style="color: var(--text-muted); font-size: 0.9rem;">${event.venue} • ₹${event.base_fee}</p>
                        </div>
                        <a href="/Project/index.php" class="btn" style="background: rgba(255,255,255,0.1); font-size: 0.9rem;">Details</a>
                     `;
                    container.appendChild(div);
                });
            }

            loadTickets();
            loadEvents();
        </script>
    <?php endif; ?>
</div>

<?php if ($_SESSION['role'] === 'admin'): ?>
    <script>
        document.getElementById('createEventForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.has_gst = formData.get('has_gst') === 'on' ? 1 : 0;

            const res = await fetch('/Project/api/events.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (res.ok) {
                alert('✨ Event Created Successfully!');
                window.location.reload();
            } else {
                alert('❌ Failed to create event');
            }
        });
    </script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>