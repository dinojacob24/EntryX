<?php
require_once '../includes/header.php';
if ($_SESSION['role'] !== 'admin') {
    header('Location: /Project/');
    exit;
}
?>

<div style="max-width: 800px; margin: 2rem auto;">
    <h2>Manage Results</h2>

    <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem;">
        <h3>Publish New Result</h3>
        <form id="resultForm">
            <div>
                <label>Select Event</label>
                <select name="event_id" id="eventSelect" required>
                    <!-- Populated by JS -->
                </select>
            </div>
            <div>
                <label>Result Title</label>
                <input type="text" name="title" placeholder="e.g. Winners List" required>
            </div>
            <div>
                <label>Content (HTML/Text)</label>
                <textarea name="content" rows="6" placeholder="<p>1st Place: John Doe</p>..." required></textarea>
                <p style="font-size: 0.8rem; color: var(--text-muted);">You can use basic HTML for tables.</p>
            </div>
            <button type="submit" class="btn btn-primary">Publish</button>
        </form>
    </div>
</div>

<script>
    async function loadEvents() {
        const res = await fetch('/Project/api/events.php?action=list');
        const data = await res.json();
        const select = document.getElementById('eventSelect');
        data.events.forEach(e => {
            const opt = document.createElement('option');
            opt.value = e.id;
            opt.innerText = e.title;
            select.appendChild(opt);
        });
    }

    document.getElementById('resultForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const res = await fetch('/Project/api/results.php?action=publish', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (res.ok) {
            alert("Result Published!");
            e.target.reset();
        } else {
            alert("Failed to publish.");
        }
    });

    loadEvents();
</script>

<?php require_once '../includes/footer.php'; ?>