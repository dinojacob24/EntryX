<?php require_once '../includes/header.php'; ?>

<div style="margin-top: 2rem;">
    <h2 style="text-align: center; margin-bottom: 2rem;">Event Results</h2>

    <div id="results-container" style="display: grid; gap: 2rem;">
        <!-- Filled by JS -->
    </div>
</div>

<script>
    async function loadResults() {
        const res = await fetch('/Project/api/results.php?action=list');
        const data = await res.json();
        const container = document.getElementById('results-container');

        if (data.results.length === 0) {
            container.innerHTML = '<p style="text-align:center; color: var(--text-muted);">No results published yet.</p>';
            return;
        }

        data.results.forEach(result => {
            const div = document.createElement('div');
            div.className = 'glass-panel';
            div.style.padding = '2rem';

            div.innerHTML = `
            <h3 style="color: var(--primary); margin-bottom: 0.5rem;">${result.event_title} - ${result.title}</h3>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">Published on ${new Date(result.published_at).toLocaleDateString()}</p>
            <div style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 8px;">
                ${result.content}
            </div>
        `;
            container.appendChild(div);
        });
    }
    loadResults();
</script>

<?php require_once '../includes/footer.php'; ?>