<?php
class User
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function register($data)
    {
        $name = $data['name'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $role = $data['role'];
        $phone = $data['phone'] ?? null;
        $college_id = $data['college_id'] ?? null;
        $college_organization = $data['college_organization'] ?? ($role === 'internal' ? $college_id : null);
        $id_proof = $data['id_proof'] ?? null;
        $external_program_id = $data['external_program_id'] ?? null;
        $registration_source = $data['registration_source'] ?? 'direct';
        $payment_status = $data['payment_status'] ?? 'not_required';
        $payment_method = $data['payment_method'] ?? null;

        // Validation
        if ($this->emailExists($email)) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        if ($role === 'internal' && empty($college_id)) {
            return ['success' => false, 'error' => 'College ID is required for internal students'];
        }

        if ($role === 'external' && empty($id_proof) && $registration_source !== 'google_oauth') {
            return ['success' => false, 'error' => 'ID Proof is required for external participants'];
        }

        // Generate QR Token for External Users (or all users)
        $qrToken = bin2hex(random_bytes(16));
        $transaction_id = $data['transaction_id'] ?? null;
        $department = $data['department'] ?? null;

        $sql = "INSERT INTO users (name, email, password, role, phone, college_id, college_organization, department, id_proof, external_program_id, registration_source, payment_status, payment_method, transaction_id, qr_token) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $name,
                $email,
                $password,
                $role,
                $phone,
                $college_id,
                $college_organization,
                $department,
                $id_proof,
                $external_program_id,
                $registration_source,
                $payment_status,
                $payment_method,
                $transaction_id,
                $qrToken
            ]);
            return ['success' => true, 'message' => 'Registration successful'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function login($identifier, $password)
    {
        // Trim inputs to prevent whitespace errors
        $identifier = trim($identifier);
        $password = trim($password);

        // Search by Email OR College ID OR Phone
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? OR college_id = ? OR phone = ?");
        $stmt->execute([$identifier, $identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Determine Role based on Email Domain
            $finalRole = $user['role'];

            // Only sync roles for non-admin accounts
            if (!in_array($finalRole, ['super_admin', 'event_admin', 'security'])) {
                $email = strtolower($user['email']);

                if (str_ends_with($email, '@ajce.in') || str_ends_with($email, '.ajce.in')) {
                    $finalRole = 'internal';
                } elseif (str_ends_with($email, '@ac.in') || str_ends_with($email, '.ac.in')) {
                    $finalRole = 'staff';
                } else {
                    $finalRole = 'external';
                }

                // Update DB if role changed
                if ($finalRole !== $user['role']) {
                    $upd = $this->pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $upd->execute([$finalRole, $user['id']]);
                }
            }

            // Start Session
            if (session_status() === PHP_SESSION_NONE)
                session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $finalRole;
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            return ['success' => true, 'role' => $finalRole];
        }

        // If user not found, check for AUTO-REGISTRATION or GUEST-BLOCK
        if (!$user && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $email = strtolower($identifier);
            $role = 'external';

            // Flexible check for college domains (including subdomains)
            if (str_ends_with($email, '@ajce.in') || str_ends_with($email, '.ajce.in')) {
                $role = 'internal';
            } elseif (str_ends_with($email, '@ac.in') || str_ends_with($email, '.ac.in')) {
                $role = 'staff';
            }

            if ($role !== 'external') {
                // AUTO-REGISTER INTERNAL/STAFF
                // Extract name from email (e.g., john.doe@ajce.in -> John Doe)
                $nameParts = explode('@', $identifier)[0];
                $name = ucwords(str_replace(['.', '_'], ' ', $nameParts));
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO users (name, email, password, role, registration_source) VALUES (?, ?, ?, ?, 'direct_auto')";
                $ins = $this->pdo->prepare($sql);
                $ins->execute([$name, $identifier, $hashedPassword, $role]);

                $newId = $this->pdo->lastInsertId();

                // Start Session
                if (session_status() === PHP_SESSION_NONE)
                    session_start();
                $_SESSION['user_id'] = $newId;
                $_SESSION['role'] = $role;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $identifier;

                return ['success' => true, 'role' => $role];
            } else {
                // GUEST - Redirect to registration
                return ['success' => false, 'error' => 'registration_required'];
            }
        }

        return ['success' => false, 'error' => 'Invalid credentials'];
    }

    public function emailExists($email)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }

    public function getUser($id)
    {
        $stmt = $this->pdo->prepare("SELECT id, name, email, role, phone, college_id, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    public function createResetToken($email)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update = $this->pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
            $update->execute([$token, $expiry, $email]);
            return $token;
        }
        return false;
    }

    public function resetPassword($token, $newPassword)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $update = $this->pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
            return $update->execute([$hashed, $user['id']]);
        }
        return false;
    }

    public function loginByGoogleId($googleId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        $user = $stmt->fetch();

        if ($user) {
            if (session_status() === PHP_SESSION_NONE)
                session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            return true;
        }
        return false;
    }

    public function createOrLoginGoogleUser($googleId, $email, $name, $picture = null)
    {
        // 1. Determine Role based on Domain (Case-insensitive)
        $email = strtolower($email);
        $role = 'external';

        if (str_ends_with($email, '@ajce.in') || str_ends_with($email, '.ajce.in')) {
            $role = 'internal'; // Student
        } elseif (str_ends_with($email, '@ac.in') || str_ends_with($email, '.ac.in')) {
            $role = 'staff';    // Staff
        }

        $isInternal = ($role !== 'external');

        // Check if user already has a Google account linked
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        $user = $stmt->fetch();

        if (!$user) {
            // Check if email exists in database (to link account)
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch();

            if ($existingUser) {
                // SECURITY: Prevent Admins from linking Google Account
                if (in_array($existingUser['role'], ['super_admin', 'event_admin'])) {
                    return false;
                }

                // For INTERNAL users: Link and sync role
                if ($isInternal) {
                    $update = $this->pdo->prepare("UPDATE users SET google_id = ?, role = ? WHERE id = ?");
                    $update->execute([$googleId, $role, $existingUser['id']]);
                    $user = $existingUser;
                    $user['role'] = $role;
                } else {
                    // For EXTERNAL users: Link Google account (keep existing role)
                    $update = $this->pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                    $update->execute([$googleId, $existingUser['id']]);
                    $user = $existingUser;
                }
            } else {
                // No existing account with this email
                if ($isInternal) {
                    // Auto-register INTERNAL (Student or Staff)
                    $sql = "INSERT INTO users (name, email, role, google_id, registration_source) VALUES (?, ?, ?, ?, 'google_oauth')";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$name, $email, $role, $googleId]);

                    $user = [
                        'id' => $this->pdo->lastInsertId(),
                        'name' => $name,
                        'email' => $email,
                        'role' => $role
                    ];
                } else {
                    // EXTERNAL user NOT registered - require registration first
                    return ['registration_required' => true];
                }
            }
        }

        // Final Security Check: Ensure they are NOT an admin
        if (isset($user['role']) && in_array($user['role'], ['super_admin', 'event_admin'])) {
            return false;
        }

        // Login
        if (session_status() === PHP_SESSION_NONE)
            session_start();

        // Final role sync (Only for internal/staff/external)
        if (!in_array($user['role'], ['super_admin', 'event_admin', 'security'])) {
            if ($user['role'] !== $role) {
                $upd = $this->pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $upd->execute([$role, $user['id']]);
                $user['role'] = $role;
            }
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        return true;
    }
}
