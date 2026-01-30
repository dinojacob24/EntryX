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
        $id_proof = $data['id_proof'] ?? null;

        // Validation
        if ($this->emailExists($email)) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        // Automatic Internal/External detection (optional override)
        // If college email domain (e.g., @college.edu), force internal?
        // For now, trust the role passed but validate fields.

        if ($role === 'internal' && empty($college_id)) {
            return ['success' => false, 'error' => 'College ID is required for internal students'];
        }

        if ($role === 'external' && empty($id_proof)) {
            // Note: File upload handling should happen before calling this, passing the path here.
            return ['success' => false, 'error' => 'ID Proof is required for external participants'];
        }

        $sql = "INSERT INTO users (name, email, password, role, phone, college_id, id_proof) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$name, $email, $password, $role, $phone, $college_id, $id_proof]);
            return ['success' => true, 'message' => 'Registration successful'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function login($email, $password)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Start Session
            if (session_status() === PHP_SESSION_NONE)
                session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            return ['success' => true, 'role' => $user['role']];
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
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            return true;
        }
        return false; // User needs to register
    }
    public function createOrLoginGoogleUser($googleId, $email, $name, $picture = null)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        $user = $stmt->fetch();

        if (!$user) {
            // Check if email exists to link account, otherwise create new
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch();

            if ($existingUser) {
                // SECURITY: Prevent Admins from linking Google Account
                if (in_array($existingUser['role'], ['super_admin', 'event_admin'])) {
                    return false; // Admins must use password
                }

                // Link account
                $update = $this->pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $update->execute([$googleId, $existingUser['id']]);
                $user = $existingUser;
            } else {
                // Register new external user
                $sql = "INSERT INTO users (name, email, role, google_id) VALUES (?, ?, 'external', ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$name, $email, $googleId]);

                $user = [
                    'id' => $this->pdo->lastInsertId(),
                    'name' => $name,
                    'email' => $email,
                    'role' => 'external'
                ];
            }
        }

        // Final Security Check: If user is found/linked, ensure they are NOT an admin
        if (in_array($user['role'], ['super_admin', 'event_admin'])) {
            return false;
        }

        // Login
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        return true;
    }
}