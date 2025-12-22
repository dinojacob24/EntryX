let currentEvent = null;

// Initialize
document.addEventListener('DOMContentLoaded', loadEvents);

async function loadEvents() {
    const res = await fetch('/Project/api/events.php?action=list');
    const data = await res.json();
    const select = document.getElementById('eventSelect');
    
    select.innerHTML = '';
    if(data.events.length === 0) {
        select.innerHTML = '<option>No Events Found</option>';
        return;
    }

    data.events.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.id;
        opt.innerText = e.title;
        select.appendChild(opt);
    });

    // Select first by default
    select.value = data.events[0].id;
    updateEventDetails(data.events[0]);

    select.addEventListener('change', (e) => {
        const evt = data.events.find(ev => ev.id == e.target.value);
        updateEventDetails(evt);
    });
}

function updateEventDetails(evt) {
    currentEvent = evt;
    document.getElementById('eventDetails').style.display = 'block';
    document.getElementById('eventFee').innerText = '₹' + evt.base_fee;
    document.getElementById('eventGst').innerText = evt.has_gst == 1 ? evt.gst_rate + '%' : 'N/A';
    calculateFee();
}

// Mode Toggle
function setMode(mode) {
    document.getElementById('manualRole').value = mode;
    document.getElementById('btnInternal').style.opacity = mode === 'internal' ? '1' : '0.5';
    document.getElementById('btnExternal').style.opacity = mode === 'external' ? '1' : '0.5';

    if(mode === 'internal') {
        document.getElementById('field-id').style.display = 'block';
    } else {
        document.getElementById('field-id').style.display = 'none';
    }
    calculateFee();
}

function calculateFee() {
    if(!currentEvent) return;
    
    const role = document.getElementById('manualRole').value;
    let base = parseFloat(currentEvent.base_fee);
    let gst = 0;

    // Logic: Internal usually free or base only (no GST). External base + GST.
    // Assuming "Paid Entry" means everyone pays base, but only Externals pay GST (as per earlier instructions)
    // Or if Internal is Free, set base to 0? 
    // "the internals will be freely entered" -> So Base = 0 for Internal.
    
    if(role === 'internal') {
        base = 0; // Internals Free
    } else {
        // Externals Pay
        if(currentEvent.has_gst == 1) {
            gst = (base * parseFloat(currentEvent.gst_rate)) / 100;
        }
    }

    let total = base + gst;

    document.getElementById('calcBase').innerText = '₹' + base.toFixed(2);
    document.getElementById('calcGst').innerText = '₹' + gst.toFixed(2);
    document.getElementById('calcTotal').innerText = '₹' + total.toFixed(2);
}

// Manual Entry Submit
document.getElementById('manualForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    if(!confirm("Confirm Payment Collection & Entry?")) return;

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.event_id = currentEvent.id;

    const res = await fetch('/Project/api/terminal_action.php?action=manual_entry', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });

    const result = await res.json();
    if(result.success) {
        alert("Entry Successful! Ticket Created.");
        e.target.reset();
        calculateFee(); // Reset numbers
    } else {
        alert(result.error);
    }
});

// Scanner Logic
function onScanSuccess(decodedText, decodedResult) {
    // Throttle logic handled by API somewhat, but let's do simple fetch
    fetch('/Project/api/terminal_action.php?action=scan', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ 
            qr_token: decodedText,
            event_id: currentEvent ? currentEvent.id : null
        })
    })
    .then(res => res.json())
    .then(data => {
        const resDiv = document.getElementById('scanResult');
        const h3 = document.getElementById('scanMsg');
        const p = document.getElementById('scanUser');
        
        resDiv.style.display = 'block';
        if(data.success) {
            h3.innerText = data.message;
            h3.style.color = data.type === 'entry' ? '#4ade80' : '#f87171';
            p.innerText = data.user + " (" + data.role + ")";
        } else {
            h3.innerText = "Error";
            h3.style.color = 'red';
            p.innerText = data.error;
        }
        
        // Hide after 3s
        setTimeout(() => { resDiv.style.display = 'none'; }, 3000);
    });
}

const html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
html5QrcodeScanner.render(onScanSuccess);
