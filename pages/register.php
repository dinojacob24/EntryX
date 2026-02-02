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

// Security Check and Data Fetch: Verify if external registration is currently enabled by Super Admin
$activeProgram = null;
try {
    $stmtSettings = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'external_registration_enabled'");
    $isEnabled = $stmtSettings->fetchColumn();

    // Fetch the active program details
    $stmtProgram = $pdo->query("SELECT * FROM external_programs WHERE is_active = 1 LIMIT 1");
    $activeProgram = $stmtProgram->fetch(PDO::FETCH_ASSOC);

    if ($isEnabled !== '1' || !$activeProgram) {
        // Redirect if disabled
        header('Location: ../index.php');
        exit;
    }
} catch (Exception $e) {
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
        style="width: 100%; max-width: 800px; padding: 4rem; border-color: rgba(255, 31, 31, 0.1); position: relative;">
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
                Register for <?php echo htmlspecialchars($activeProgram['program_name']); ?></h2>
            <p style="color: var(--p-text-dim); font-size: 1rem;">
                <?php echo htmlspecialchars($activeProgram['program_description'] ?: 'Join the campus event management network.'); ?>
            </p>
        </div>

        <form id="registerForm" enctype="multipart/form-data" autocomplete="off">
            <!-- Hidden Fields for Program ID -->
            <input type="hidden" name="external_program_id" value="<?php echo $activeProgram['id']; ?>">
            <input type="hidden" name="payment_status"
                value="<?php echo ($activeProgram['is_paid'] ? 'pending' : 'not_required'); ?>">

            <!-- External Registration Only -->
            <input type="hidden" name="role" value="external">

            <!-- Name Field -->
            <div style="margin-bottom: 2rem;">
                <label
                    style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">Full
                    Name <span style="color: var(--p-brand);">*</span></label>
                <input type="text" name="name" required placeholder="Enter your full name" autocomplete="off"
                    style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
            </div>

            <!-- Email Address & Password -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">Email
                        Address <span style="color: var(--p-brand);">*</span></label>
                    <input type="email" name="email" id="emailInput" required placeholder="john@gmail.com"
                        autocomplete="new-email"
                        style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
                    <small style="color: var(--p-text-dim); font-size: 0.7rem; display: block; margin-top: 0.5rem;">Use
                        your Gmail for Google Sign-in after registration</small>
                </div>
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">Password
                        <span style="color: var(--p-brand);">*</span></label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" required placeholder="Create a password"
                            autocomplete="new-password"
                            style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white; padding-right: 3rem;">
                        <i class="fa-solid fa-eye" id="togglePassword"
                            style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--p-text-muted); cursor: pointer;"
                            onclick="togglePasswordVisibility()"></i>
                    </div>
                </div>
            </div>

            <!-- Confirm Password (hidden, matches password) -->
            <input type="hidden" name="confirm_password" id="confirm_password" value="">

            <input type="hidden" name="college_organization" value="External Participant">



            <!-- Contact Number & WhatsApp Number -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">Contact
                        Number <span style="color: var(--p-brand);">*</span></label>
                    <input type="tel" name="phone" id="phoneInput" required placeholder="+91 0000000000"
                        autocomplete="off"
                        style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
                </div>
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">WhatsApp
                        Number <span style="color: var(--p-brand);">*</span></label>
                    <input type="tel" name="whatsapp" required placeholder="+91 0000000000" autocomplete="off"
                        style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
                </div>
            </div>

            <!-- Department & ID Proof -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">Department
                        <span style="color: var(--p-text-dim); font-size: 0.65rem;">(Optional)</span></label>
                    <input type="text" name="department" placeholder="e.g., Computer Science, Marketing"
                        style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
                </div>
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">ID
                        Proof <span style="color: var(--p-text-dim); font-size: 0.65rem;">(Optional)</span></label>
                    <input type="file" name="id_proof" id="idProofInput" accept="image/*,.pdf"
                        style="width: 100%; padding: 0.9rem; color: var(--p-text-dim); border: 1px dashed var(--p-border); border-radius: 12px; background: rgba(0,0,0,0.2); font-size: 0.8rem;">
                </div>
            </div>

            <!-- Payment Methods Section (Only if program is paid) -->
            <?php if ($activeProgram['is_paid']): ?>
                <div id="paymentSection"
                    style="margin-top: 3rem; padding: 2.5rem; background: linear-gradient(135deg, rgba(255,31,31,0.05) 0%, rgba(0,0,0,0.2) 100%); border: 1px solid rgba(255,31,31,0.2); border-radius: 20px; margin-bottom: 2.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <div>
                            <h3 style="color: white; font-size: 1.2rem; font-weight: 700; margin-bottom: 0.3rem;">Entry Fee
                            </h3>
                            <p style="color: var(--p-text-muted); font-size: 0.85rem;">One-time entry fee for this program
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.8rem; font-weight: 800; color: #10b981;">
                                ₹<?php echo number_format($activeProgram['total_amount_with_gst'], 2); ?></div>
                            <?php if ($activeProgram['is_gst_enabled']): ?>
                                <div style="font-size: 0.7rem; color: var(--p-text-dim);">Incl.
                                    <?php echo $activeProgram['gst_rate']; ?>% GST
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- UPI QR Display Area -->
                    <?php if (!empty($activeProgram['payment_upi'])): ?>
                        <div id="upiQrSection"
                            style="display:none; text-align: center; margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 16px;">
                            <h4 style="color: #333; margin-bottom: 1rem; font-size: 0.9rem; font-weight: 700;">Scan to Pay</h4>
                            <div id="paymentQr" style="display: inline-block;"></div>
                            <div style="color: #666; font-size: 0.8rem; margin-top: 0.5rem; margin-bottom: 1.5rem;">UPI ID:
                                <strong><?php echo htmlspecialchars($activeProgram['payment_upi']); ?></strong>
                            </div>

                            <div style="text-align: left; margin-top: 1rem;">
                                <label
                                    style="display: block; color: var(--p-text-muted); font-size: 0.8rem; font-weight: 700; margin-bottom: 0.5rem;">Transaction
                                    / Reference ID <span style="color:red">*</span></label>
                                <input type="text" name="transaction_id" id="transactionIdInput"
                                    placeholder="Enter 12-digit UPI Reference No." autocomplete="off"
                                    style="width: 100%; padding: 10px; border: 1px solid #333; border-radius: 8px; font-size: 0.9rem; background: white; color: #000;"
                                    title="Please enter the correct Transaction/UTR Number">
                                <small style="display: block; color: #666; font-size: 0.75rem; margin-top: 5px;">Required for
                                    payment verification.</small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; letter-spacing: 0.05em;">Select
                        Payment Method</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <label style="cursor: pointer; position: relative;">
                            <input type="radio" name="payment_method" value="upi" checked style="display: none;"
                                onchange="updatePaymentSelect(this)">
                            <div style="padding: 1.2rem; background: rgba(255,255,255,0.03); border: 1px solid var(--p-border); border-radius: 14px; text-align: center; transition: 0.3s;"
                                class="pay-method-card active">
                                <i class="fa-solid fa-mobile-screen-button"
                                    style="font-size: 1.5rem; margin-bottom: 0.5rem; color: #10b981;"></i>
                                <div style="color: white; font-size: 0.85rem; font-weight: 700;">UPI / QR</div>
                            </div>
                        </label>
                        <label style="cursor: pointer; position: relative;">
                            <input type="radio" name="payment_method" value="card" style="display: none;"
                                onchange="updatePaymentSelect(this)">
                            <div style="padding: 1.2rem; background: rgba(255,255,255,0.03); border: 1px solid var(--p-border); border-radius: 14px; text-align: center; transition: 0.3s;"
                                class="pay-method-card">
                                <i class="fa-solid fa-credit-card"
                                    style="font-size: 1.5rem; margin-bottom: 0.5rem; color: #3b82f6;"></i>
                                <div style="color: white; font-size: 0.85rem; font-weight: 700;">Card / Net</div>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

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
    function updatePaymentSelect(el) {
        // Remove active class from all method cards
        document.querySelectorAll('.pay-method-card').forEach(card => card.classList.remove('active'));
        // Add active class to parent's card
        el.nextElementSibling.classList.add('active');

        // Toggle UPI QR
        const upiSection = document.getElementById('upiQrSection');
        if (upiSection) {
            upiSection.style.display = (el.value === 'upi' ? 'block' : 'none');
        }
    }

    // Password Toggle Visibility
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePassword');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }

    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = document.getElementById('submitBtn');
        const originalBtnHtml = submitBtn.innerHTML;

        // Initialize FormData
        const formData = new FormData(e.target);
        const phone = formData.get('phone');
        const whatsapp = formData.get('whatsapp');
        const password = formData.get('password');
        const email = formData.get('email');

        // Set confirm_password to match password
        formData.set('confirm_password', password);

        // 4. Payment Verification
        const isPaid = <?php echo ($activeProgram['is_paid'] ? 'true' : 'false'); ?>;
        const paymentMethod = formData.get('payment_method');
        const transactionInput = document.getElementById('transactionIdInput');

        if (isPaid && paymentMethod === 'upi') {
            const transId = transactionInput.value.trim();
            if (!transId) {
                Swal.fire('Payment Verification Required', 'Please enter the Transaction ID / Reference Number.', 'warning');
                transactionInput.focus();
                return;
            }
            if (transId.length < 10) {
                Swal.fire('Invalid Transaction ID', 'Please enter a valid Transaction/UTR Number.', 'warning');
                return;
            }
        }

        if (isPaid) {
            const confirmPay = await Swal.fire({
                title: 'Confirm Registration',
                text: "Proceed to pay ₹<?php echo number_format($activeProgram['total_amount_with_gst'], 2); ?> for registration?",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Yes, Proceed'
            });
            if (!confirmPay.isConfirmed) return;
            formData.append('payment_status', 'paid');
        }

        // Show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

        try {
            const res = await fetch('/Project/EntryX/api/auth.php?action=register', {
                method: 'POST',
                body: formData
            });

            const result = await res.json();

            if (result.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Registration Successful!',
                    text: 'Welcome! Your credentials are now active.',
                    confirmButtonColor: '#10b981'
                });
                window.location.href = (result.role === 'external') ? 'external_dashboard.php' : 'student_dashboard.php';
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

    // Check for Google Login Redirection Error
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    if (error === 'external_registration_required' || error === 'internal_registration_required') {
        const isInternal = error === 'internal_registration_required';
        Swal.fire({
            icon: 'info',
            title: isInternal ? 'Student Registration Needed' : 'Guest Registration Required',
            text: isInternal
                ? 'Internal students must register here first using their College ID to enable Google Sign-in and passwords.'
                : 'It looks like you are trying to sign in as a Guest. Please register here first to set up your account and upload your ID proof.',
            confirmButtonColor: '#ff1f1f',
            background: '#0a0a0a',
            color: '#fff'
        });
    }
</script>

<style>
    .pay-method-card:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        transform: translateY(-2px);
        border-color: rgba(255, 31, 31, 0.3) !important;
    }

    .pay-method-card.active {
        background: rgba(16, 185, 129, 0.1) !important;
        border-color: #10b981 !important;
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.1);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="../assets/js/qrcode.min.js"></script>
<script>
    // Initialize UPI QR if available
    <?php if ($activeProgram && !empty($activeProgram['payment_upi'])): ?>
        const upiId = "<?php echo $activeProgram['payment_upi']; ?>";
        const amount = "<?php echo $activeProgram['total_amount_with_gst']; ?>";
        const name = "<?php echo htmlspecialchars($activeProgram['program_name']); ?>";

        console.log("UPI Config:", { upiId, amount }); // Debug log

        // UPI Link Format: upi://pay?pa=UPI_ID&pn=NAME&am=AMOUNT&cu=INR
        const upiLink = `upi://pay?pa=${upiId}&pn=${encodeURIComponent(name)}&am=${amount}&cu=INR`;

        const qrContainer = document.getElementById('paymentQr');
        if (qrContainer) {
            new QRCode(qrContainer, {
                text: upiLink,
                width: 180,
                height: 180,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
            // Show initially if default is UPI
            document.getElementById('upiQrSection').style.display = 'block';
        }
    <?php endif; ?>
</script>
<?php require_once '../includes/footer.php'; ?>