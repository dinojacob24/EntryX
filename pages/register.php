<?php
require_once '../includes/header.php';

// Force clear session to prevent conflicts
if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    session_start();
}
?>

<div style="max-width: 500px; margin: 4rem auto;" class="glass-panel">
    <div style="padding: 2rem;">
        <h2 style="text-align: center; margin-bottom: 2rem;">Register</h2>
        <form id="registerForm">
            <div>
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>
            <div>
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <div>
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div>
                <label>Role</label>
                <select name="role" id="roleSelect">
                    <option value="internal">Internal Student (College)</option>
                    <option value="faculty">Faculty Member</option>
                    <option value="external">External Participant</option>
                </select>
            </div>
            <div id="studentIdField">
                <label>Student ID</label>
                <input type="text" name="student_id">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>

            <div style="margin: 1.5rem 0; display: flex; align-items: center; gap: 1rem;">
                <div style="height: 1px; background: rgba(255,255,255,0.2); flex-grow: 1;"></div>
                <span style="color: var(--text-muted); font-size: 0.9rem;">OR REGISTER WITH</span>
                <div style="height: 1px; background: rgba(255,255,255,0.2); flex-grow: 1;"></div>
            </div>

            <div id="buttonDiv"></div>

            <p style="margin-top: 1.5rem; text-align: center; color: var(--text-muted);">
                Already have an account? <a href="login.php" style="color: var(--primary);">Login</a>
            </p>
        </form>
    </div>
</div>

<!-- Google Config -->
<?php require_once '../config/google_config.php'; ?>
<script src="https://accounts.google.com/gsi/client" async defer></script>

<script>
    const roleSelect = document.getElementById('roleSelect');
    const studentIdField = document.getElementById('studentIdField');

    roleSelect.addEventListener('change', (e) => {
        if (e.target.value === 'internal' || e.target.value === 'faculty') {
            studentIdField.style.display = 'block';
            studentIdField.querySelector('label').innerText = e.target.value === 'faculty' ? 'Faculty ID' : 'Student ID';
        } else {
            studentIdField.style.display = 'none';
            studentIdField.querySelector('input').value = '';
        }
    });

    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const res = await fetch('/Project/api/auth.php?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await res.json();
        if (result.success) {
            alert('Registration successful! Please login.');
            window.location.href = 'login.php';
        } else {
            alert(result.error);
        }
    });

    // Google Register/Login
    function handleCredentialResponse(response) {
        fetch('/Project/api/auth.php?action=google_login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: response.credential })
        })
            .then(res => res.json())
            .then(result => {
                // Auto-login after register
                if (result.success) {
                    if (result.role === 'admin') window.location.href = 'dashboard.php';
                    else if (result.role === 'gatekeeper') window.location.href = 'terminal.php';
                    else window.location.href = 'dashboard.php';
                }
                else alert(result.error || 'Registration Failed');
            });
    }

    // Load Google Button
    window.onload = function () {
        google.accounts.id.initialize({
            client_id: "<?php echo GOOGLE_CLIENT_ID; ?>",
            callback: handleCredentialResponse
        });

        // Calculate optimal width
        const containerWidth = document.getElementById("buttonDiv").clientWidth || 320;

        google.accounts.id.renderButton(
            document.getElementById("buttonDiv"),
            {
                theme: "filled_blue",
                size: "large",
                shape: "pill",
                width: containerWidth + "",
                logo_alignment: "left",
                text: "signup_with" // Distinct text for Register page
            }
        );
    }
</script>

<?php require_once '../includes/footer.php'; ?>