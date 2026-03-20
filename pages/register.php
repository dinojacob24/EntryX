<?php
// === BOOTSTRAP: Load Project Root and Start Session ===
require_once '../config/project_root.php';
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
        <a href="<?php echo isset($_SESSION['user_id']) ? 'student_dashboard.php' : '../index.php'; ?>"
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
                <input type="text" name="name" id="nameInput" required minlength="2" maxlength="100"
                    pattern="^[A-Za-z\s]+$" title="Name should only contain letters and spaces"
                    placeholder="Enter your full name" autocomplete="off"
                    style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
                <small id="nameError" style="color:#ef4444; font-size:0.75rem; display:none; margin-top:0.3rem;">⚠️ Name
                    must be at least 2 characters and contain only letters.</small>
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
                    <small id="emailError" style="color:#ef4444; font-size:0.75rem; display:none; margin-top:0.3rem;">⚠️
                        Please enter a valid email address.</small>
                    <small style="color: var(--p-text-dim); font-size: 0.7rem; display: block; margin-top: 0.5rem;">Use
                        your Gmail for Google Sign-in after registration</small>
                </div>
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">Password
                        <span style="color: var(--p-brand);">*</span></label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" required minlength="8"
                            placeholder="Min 8 characters" autocomplete="new-password"
                            style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white; padding-right: 3rem;">
                        <i class="fa-solid fa-eye" id="togglePassword"
                            style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--p-text-muted); cursor: pointer;"
                            onclick="togglePasswordVisibility()"></i>
                    </div>
                    <small id="passwordLengthHint"
                        style="color:var(--p-text-dim); font-size:0.72rem; display:block; margin-top:0.3rem;">Min 8
                        characters required</small>
                    <small id="passwordLengthError"
                        style="color:#ef4444; font-size:0.72rem; display:none; margin-top:0.3rem;">⚠️ Password must be
                        at least 8 characters.</small>
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
                    <input type="tel" name="phone" id="phoneInput" required placeholder="10-digit mobile number"
                        autocomplete="off" maxlength="10" pattern="[0-9]{10}"
                        style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
                    <small id="phoneError" style="color:#ef4444; font-size:0.75rem; display:none; margin-top:0.3rem;">⚠️
                        Please enter a valid 10-digit phone number.</small>
                </div>
                <div>
                    <label
                        style="display: block; color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.8rem;">WhatsApp
                        Number <span style="color: var(--p-brand);">*</span></label>
                    <input type="tel" name="whatsapp" id="whatsappInput" required placeholder="10-digit WhatsApp number"
                        autocomplete="off" maxlength="10" pattern="[0-9]{10}"
                        style="width: 100%; padding: 1.1rem 1.4rem; background: rgba(255,255,255,0.02); border: 1px solid var(--p-border); border-radius: 12px; color: white;">
                    <small id="whatsappError"
                        style="color:#ef4444; font-size:0.75rem; display:none; margin-top:0.3rem;">⚠️ Please enter a
                        valid 10-digit WhatsApp number.</small>
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
                            <?php if ($activeProgram['is_gst_enabled']): 
                                $baseFee = floatval($activeProgram['registration_fee']);
                                $gstRate = floatval($activeProgram['gst_rate']);
                                $gstAmount = ($baseFee * $gstRate) / 100;
                                $totalAmount = $baseFee + $gstAmount;
                            ?>
                                <div style="font-size: 1.8rem; font-weight: 800; color: #10b981;">
                                    ₹<?php echo number_format($totalAmount, 2); ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--p-text-dim); margin-top: 0.3rem;">
                                    Incl. <?php echo $gstRate; ?>% GST
                                </div>
                            <?php else: ?>
                                <div style="font-size: 1.8rem; font-weight: 800; color: #10b981;">
                                    ₹<?php echo number_format(floatval($activeProgram['registration_fee']), 2); ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--p-text-dim); margin-top: 0.3rem;">
                                    No GST applicable
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($activeProgram['is_gst_enabled']): ?>
                        <!-- GST Breakdown Card -->
                        <div style="background: rgba(16, 185, 129, 0.07); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 14px; padding: 1.2rem 1.5rem; margin-bottom: 1.5rem;">
                            <div style="font-size: 0.78rem; color: #10b981; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.8rem;">
                                <i class="fa-solid fa-receipt"></i> Payment Breakdown
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.88rem; color: var(--p-text-dim); margin-bottom: 0.4rem;">
                                <span>Base Fee</span>
                                <span style="color: white; font-weight: 600;">₹<?php echo number_format($baseFee, 2); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.88rem; color: var(--p-text-dim); margin-bottom: 0.6rem;">
                                <span>GST (<?php echo $gstRate; ?>%)</span>
                                <span style="color: white; font-weight: 600;">₹<?php echo number_format($gstAmount, 2); ?></span>
                            </div>
                            <div style="border-top: 1px solid rgba(16, 185, 129, 0.25); padding-top: 0.6rem; display: flex; justify-content: space-between; font-size: 0.95rem;">
                                <strong style="color: white;">Total Payable</strong>
                                <strong style="color: #10b981; font-size: 1.05rem;">₹<?php echo number_format($totalAmount, 2); ?></strong>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div style="text-align: center; margin-bottom: 1rem;">
                        <div
                            style="display: inline-block; padding: 1rem 2rem; background: rgba(255,255,255,0.05); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                            <i class="fa-solid fa-shield-halved"
                                style="color: #10b981; margin-bottom: 0.5rem; font-size: 1.5rem;"></i>
                            <p style="color: white; font-size: 0.9rem; margin: 0; font-weight: 600;">Secure Payment via
                                Razorpay</p>
                            <p style="color: var(--p-text-dim); font-size: 0.75rem; margin-top: 0.3rem;">You will be
                                redirected to pay after signing up.</p>
                        </div>
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
        const name = formData.get('name');

        // ===== FRONTEND VALIDATION =====
        // Name check: letters and spaces only, min 2 chars
        const nameRegex = /^[A-Za-z\s]{2,100}$/;
        if (!name || !nameRegex.test(name.trim())) {
            document.getElementById('nameError').style.display = 'block';
            Swal.fire('Invalid Name', 'Full name must be at least 2 characters and contain only letters.', 'warning');
            return;
        }
        document.getElementById('nameError').style.display = 'none';

        // Email check: basic regex to catch obviously invalid formats
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!email || !emailRegex.test(email.trim())) {
            document.getElementById('emailError').style.display = 'block';
            Swal.fire('Invalid Email', 'Please enter a valid email address.', 'warning');
            return;
        }
        document.getElementById('emailError').style.display = 'none';

        // Password length check
        if (!password || password.length < 8) {
            document.getElementById('passwordLengthError').style.display = 'block';
            document.getElementById('passwordLengthHint').style.display = 'none';
            Swal.fire('Weak Password', 'Password must be at least 8 characters long.', 'warning');
            return;
        }
        document.getElementById('passwordLengthError').style.display = 'none';

        // Phone number: must be exactly 10 digits
        const phoneRegex = /^[0-9]{10}$/;
        if (!phoneRegex.test(phone)) {
            document.getElementById('phoneError').style.display = 'block';
            Swal.fire('Invalid Phone Number', 'Please enter a valid 10-digit mobile number (digits only).', 'warning');
            document.getElementById('phoneInput').focus();
            return;
        }
        document.getElementById('phoneError').style.display = 'none';

        // WhatsApp number: must be exactly 10 digits
        if (!phoneRegex.test(whatsapp)) {
            document.getElementById('whatsappError').style.display = 'block';
            Swal.fire('Invalid WhatsApp Number', 'Please enter a valid 10-digit WhatsApp number (digits only).', 'warning');
            return;
        }
        document.getElementById('whatsappError').style.display = 'none';
        // ===== END VALIDATION =====

        // Set confirm_password to match password
        formData.set('confirm_password', password);

        // 4. Payment Preparation
        const isPaid = <?php echo ($activeProgram['is_paid'] ? 'true' : 'false'); ?>;

        if (isPaid) {
            formData.append('payment_status', 'pending');
            // We NO LONGER append payment_method or transaction_id (Razorpay handles this post-registration)
        }

        // Show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

        try {
            const res = await fetch('<?php echo $entryx_root; ?>api/auth.php?action=register', {
                method: 'POST',
                body: formData
            });

            const result = await res.json();

            if (result.success) {
                if (result.requires_payment) {
                    Swal.fire({
                        title: 'Preparing Payment...',
                        didOpen: () => Swal.showLoading(),
                        background: '#0a0a0a',
                        color: '#fff',
                        allowOutsideClick: false
                    });

                    try {
                        const rzpRes = await fetch('../api/payment_gateway.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ type: 'program', id: formData.get('external_program_id') })
                        });
                        const order = await rzpRes.json();

                        if (!order.success) throw new Error(order.error || 'Could not create payment order');

                        Swal.close();

                        const options = {
                            key: order.key,
                            amount: order.amount,
                            currency: 'INR',
                            name: 'EntryX',
                            description: order.item_name,
                            order_id: order.order_id,
                            prefill: {
                                name: order.user_name,
                                email: order.user_email,
                                contact: order.user_contact
                            },
                            theme: { color: '#ff1f1f' },
                            handler: async function (response) {
                                Swal.fire({
                                    title: 'Verifying Payment...',
                                    didOpen: () => Swal.showLoading(),
                                    background: '#0a0a0a',
                                    color: '#fff',
                                    allowOutsideClick: false
                                });

                                try {
                                    const vRes = await fetch('../api/payment_verify.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({
                                            razorpay_order_id: response.razorpay_order_id,
                                            razorpay_payment_id: response.razorpay_payment_id,
                                            razorpay_signature: response.razorpay_signature
                                        })
                                    });
                                    const vResult = await vRes.json();

                                    if (vResult.success) {
                                        await Swal.fire({
                                            icon: 'success',
                                            title: 'Registration & Payment Successful!',
                                            text: 'Welcome! Your credentials are now active.',
                                            confirmButtonColor: '#10b981', background: '#0a0a0a', color: '#fff'
                                        });
                                        window.location.href = (result.role === 'external') ? 'external_dashboard.php' : 'student_dashboard.php';
                                    } else {
                                        throw new Error(vResult.error || 'Verification failed');
                                    }
                                } catch (err) {
                                    Swal.fire({ icon: 'error', title: 'Verification Error', text: err.message, confirmButtonColor: '#ff1f1f', background: '#0a0a0a', color: '#fff' })
                                        .then(() => { window.location.href = (result.role === 'external') ? 'external_dashboard.php' : 'student_dashboard.php'; });
                                }
                            },
                            modal: {
                                ondismiss: function () {
                                    Swal.fire({ icon: 'info', title: 'Payment Pending', text: 'You can complete your payment later from your dashboard.', confirmButtonColor: '#ff1f1f', background: '#0a0a0a', color: '#fff' })
                                        .then(() => { window.location.href = (result.role === 'external') ? 'external_dashboard.php' : 'student_dashboard.php'; });
                                }
                            }
                        };

                        const rzp = new Razorpay(options);
                        rzp.open();
                    } catch (err) {
                        Swal.fire({ icon: 'error', title: 'Payment Initialization Error', text: err.message, confirmButtonColor: '#ff1f1f', background: '#0a0a0a', color: '#fff' })
                            .then(() => { window.location.href = (result.role === 'external') ? 'external_dashboard.php' : 'student_dashboard.php'; });
                    }
                } else {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Registration Successful!',
                        text: 'Welcome! Your credentials are now active.',
                        confirmButtonColor: '#10b981'
                    });
                    window.location.href = (result.role === 'external') ? 'external_dashboard.php' : 'student_dashboard.php';
                }
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

    // Real-time password length indicator
    document.getElementById('password').addEventListener('input', function () {
        const hintEl = document.getElementById('passwordLengthHint');
        const errEl = document.getElementById('passwordLengthError');
        if (this.value.length > 0 && this.value.length < 8) {
            errEl.style.display = 'block';
            hintEl.style.display = 'none';
            this.style.borderColor = '#ef4444';
        } else if (this.value.length >= 8) {
            errEl.style.display = 'none';
            hintEl.style.display = 'none';
            this.style.borderColor = '#10b981';
        } else {
            hintEl.style.display = 'block';
            errEl.style.display = 'none';
            this.style.borderColor = '';
        }
    });

    // Real-time phone validation (digits only)
    document.getElementById('phoneInput').addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
        const errEl = document.getElementById('phoneError');
        if (this.value.length > 0 && this.value.length < 10) {
            errEl.style.display = 'block';
            this.style.borderColor = '#ef4444';
        } else if (this.value.length === 10) {
            errEl.style.display = 'none';
            this.style.borderColor = '#10b981';
        } else {
            errEl.style.display = 'none';
            this.style.borderColor = '';
        }
    });

    // Real-time WhatsApp validation (digits only)
    document.getElementById('whatsappInput').addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
        const errEl = document.getElementById('whatsappError');
        if (this.value.length > 0 && this.value.length < 10) {
            errEl.style.display = 'block';
            this.style.borderColor = '#ef4444';
        } else if (this.value.length === 10) {
            errEl.style.display = 'none';
            this.style.borderColor = '#10b981';
        } else {
            errEl.style.display = 'none';
            this.style.borderColor = '';
        }
    });

    // Real-time Name validation
    document.getElementById('nameInput').addEventListener('input', function () {
        const nameRegex = /^[A-Za-z\s]+$/;
        const errEl = document.getElementById('nameError');
        if (this.value.length > 0 && (!nameRegex.test(this.value) || this.value.trim().length < 2)) {
            errEl.style.display = 'block';
            this.style.borderColor = '#ef4444';
        } else if (this.value.trim().length >= 2) {
            errEl.style.display = 'none';
            this.style.borderColor = '#10b981';
        } else {
            errEl.style.display = 'none';
            this.style.borderColor = '';
        }
    });

    // Real-time Email validation
    document.getElementById('emailInput').addEventListener('input', function () {
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        const errEl = document.getElementById('emailError');
        if (this.value.length > 0 && !emailRegex.test(this.value)) {
            errEl.style.display = 'block';
            this.style.borderColor = '#ef4444';
        } else if (this.value.length > 0) {
            errEl.style.display = 'none';
            this.style.borderColor = '#10b981';
        } else {
            errEl.style.display = 'none';
            this.style.borderColor = '';
        }
    });

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

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../includes/footer.php'; ?>