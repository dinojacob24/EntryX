<?php
// Session and Authentication Checks MUST come before any includes
if (session_status() === PHP_SESSION_NONE) {
session_set_cookie_params(0, '/Project/EntryX');
session_start();
}
require_once '../config/db_connect.php';

// Force clear session ONLY if not an admin (to allow administrators to test without being logged out)
if (isset($_SESSION['user_id'])) {
if (!in_array($_SESSION['role'] ?? '', ['super_admin', 'event_admin', 'security'])) {
session_unset();
session_destroy();
session_start();
}
}

// Security Check: Verify if external registration is currently enabled by Super Admin
try {
$stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'external_registration_enabled'");
$isEnabled = $stmt->fetchColumn();

// Also check if there's at least one active program
$stmtProgram = $pdo->query("SELECT COUNT(*) FROM external_programs WHERE is_active = 1");
$hasActiveProgram = $stmtProgram->fetchColumn();

if ($isEnabled !== '1' || $hasActiveProgram == 0) {
// Redirect if disabled
header('Location: ../index.php');
exit;
}
} catch (Exception $e) {
// If error, safer to redirect
header('Location: ../index.php');
exit;
}

require_once '../includes/header.php';
?>


<section
    style="min-height: 85vh; display: flex; align-items: center; justify-content: center; padding: 4rem 0; position: relative; overflow: hidden;">
    <!-- Background Glow -->
    <div
        style="position: absolute; top: 10%; right: 10%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(255,31,31,0.03) 0%, transparent 70%); filter: blur(100px); pointer-events: none;">
    </div>

    <div class="glass-panel reveal"
        style="width: 100%; max-width: 650px; padding: 4rem; border-color: rgba(255, 31, 31, 0.1); position: relative;">
        <!-- Back Link -->
        <a href="../index.php"
            style="position: absolute; top: 1.5rem; left: 1.5rem; color: var(--p-text-muted); text-decoration: none; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; display: flex; align-items: center; gap: 0.5rem; transition: 0.3s;"
            onmouseover="this.style.color='white'; this.style.transform='translateX(-5px)';"
            onmouseout="this.style.color='var(--p-text-muted)'; this.style.transform='translateX(0)';">
            <i class="fa-solid fa-arrow-left"></i> Back to Home
        </a>
        <div style="text-align: center; margin-bottom: 3.5rem;">
            <div
                style="width: 72px; height: 72px; background: var(--grad-crimson); border-radius: 22px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: white; box-shadow: 0 10px 25px var(--p-brand-glow);">
                <i class="fa-solid fa-user-astronaut fa-2xl"></i>
            </div>
            <h2
                style="color: white; margin-bottom: 0.5rem; font-size: 2.2rem; font-weight: 800; letter-spacing: -0.02em;">
                Register</h2>
            <p style="color: var(--p-text-dim); font-size: 1rem;">Join the campus event management network.</p>
        </div>

        <form id="registerForm" enctype="multipart/form-data">
            <div style="margin-bottom: 2.5rem;">
                <label
                    style="display: block; color: var(--p-text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1rem;">Account
                    Type</label>
                <select name="role" id="roleSelect" onchange="toggleFields()"
                    style="width: 100%; padding: 1.2rem; background: rgba(255,255,255,0.03); border: 1px solid var(--p-border); border-radius: 14px; color: white; cursor: pointer;">
                    <option value="internal">Student / Staff</option>
                    <option value="external">Guest / External</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">Full
                        Name</label>
                    <input type="text" name="name" required placeholder="John Doe"
                        style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
                </div>
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">Email
                        Address</label>
                    <input type="email" name="email" required placeholder="john@example.com"
                        style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">Password</label>
                    <input type="password" name="password" id="password" required minlength="8" placeholder="••••••••"
                        style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
                </div>
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">Confirm
                        Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required placeholder="••••••••"
                        style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
                </div>
            </div>

            <!-- Conditional Fields -->
            <div id="internalFields" style="margin-bottom: 2.5rem;">
                <label
                    style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">College
                    ID</label>
                <input type="text" name="college_id" id="collegeIdInput" placeholder="e.g. 23MCA001"
                    style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
            </div>

            <div id="externalFields" style="display: none; margin-bottom: 2.5rem;">
                <div style="margin-bottom: 2rem;">
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">Phone
                        Number</label>
                    <input type="tel" name="phone" id="phoneInput" placeholder="+91 0000000000"
                        style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
                </div>
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">ID
                        Proof (College ID / Govt ID)</label>
                    <input type="file" name="id_proof" id="idProofInput" accept="image/*,.pdf"
                        style="width: 100%; padding: 1rem; color: var(--p-text-dim); border: 1px dashed var(--p-border); border-radius: 12px; background: rgba(0,0,0,0.2);">
                </div>
            </div>

            <div id="passwordMatchError"
                style="color: var(--p-brand); font-size: 0.85rem; font-weight: 600; text-align: center; margin-bottom: 2rem; display: none;">
                Passwords do not match.
            </div>

            <button type="submit" class="btn btn-primary"
                style="width: 100%; padding: 1.2rem; font-size: 1.1rem; font-weight: 800; border-radius: 16px;"
                id="submitBtn">
                Sign Up <i class="fa-solid fa-user-plus" style="margin-left: 0.5rem;"></i>
            </button>

            <p style="text-align: center; margin-top: 3rem; color: var(--p-text-muted); font-size: 0.95rem;">
                Already have an account? <a href="user_login.php"
                    style="color: white; font-weight: 700; text-decoration: none; border-bottom: 1px solid var(--p-brand);">Login</a>
            </p>
        </form>
    </div>
</section>


<script>
    function toggleFields() {
        const role = document.getElementById('roleSelect').value;
        const internalFields = document.getElementById('internalFields');
        const externalFields = document.getElementById('externalFields');

        if (role === 'internal') {
            internalFields.style.display = 'block';
            externalFields.style.display = 'none';
            document.getElementById('collegeIdInput').required = true;
            document.getElementById('phoneInput').required = false;
            document.getElementById('idProofInput').required = false;
        } else {
            internalFields.style.display = 'none';
            externalFields.style.display = 'block';
            document.getElementById('collegeIdInput').required = false;
            document.getElementById('phoneInput').required = true;
            document.getElementById('idProofInput').required = true;
        }
    }

    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const matchError = document.getElementById('passwordMatchError');
        const submitBtn = document.getElementById('submitBtn');

        // Reset state
        matchError.style.display = 'none';

        // 1. Password Match Validation
        if (password !== confirmPassword) {
            matchError.style.display = 'block';
            document.getElementById('confirm_password').focus();
            return;
        }

        // 2. Password Strength Check (Simple regex)
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
        if (!passwordRegex.test(password)) {
            alert('Password does not meet the security requirements.');
            document.getElementById('password').focus();
            return;
        }

        // Show loading state
        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

        const formData = new FormData(e.target);

        try {
            const res = await fetch('/Project/EntryX/api/auth.php?action=register', {
                method: 'POST',
                body: formData
            });

            const result = await res.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Registration Successful!',
                    text: 'Your account has been created. You can now log in.',
                    confirmButtonColor: '#ff1f1f'
                }).then(() => {
                    window.location.href = 'user_login.php';
                });
            } else {
                Swal.fire('Failed', result.error || 'Registration failed', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'An error occurred. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        }
    });

    // Real-time password match check
    document.getElementById('confirm_password').addEventListener('input', function (e) {
        const password = document.getElementById('password').value;
        const confirm = e.target.value;
        const error = document.getElementById('passwordMatchError');

        if (confirm !== password && confirm.length > 0) {
            error.style.display = 'block';
        } else {
            error.style.display = 'none';
        }
    });

    // Initialize correct state
    toggleFields();
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../includes/footer.php'; ?>