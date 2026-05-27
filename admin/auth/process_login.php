<?php
// Start output buffering to prevent header errors
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// First include config to set up database connection
require_once dirname(__DIR__) . '/config/config.php';
require_once CORE_PATH . '/functions/csrf.php';
require_once CORE_PATH . '/classes/RateLimiter.php';

// Then include init which depends on the database connection
require_once ADMIN_PATH . '/data/init.php';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF: reject state-changing request without valid token
    if (!function_exists('csrf_verify') || !csrf_verify()) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '') . '/auth/login.php');
        ob_end_flush();
        exit;
    }

    // Rate limit: 10 attempts per 15 min per IP (brute-force protection)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!\Modular\RateLimiter::check('login_' . $ip, 10, 900)) {
        $_SESSION['error'] = 'Too many login attempts. Try again later.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '') . '/auth/login.php');
        ob_end_flush();
        exit;
    }

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Email and password are required';
        header('Location: login.php');
        ob_end_flush();
        exit;
    }

    try {
        // Check if user exists
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        // Fetch all rows to fully buffer the result set (avoids MySQL unbuffered cursor conflicts).
        $userRows = $stmt->fetchAll();
        $user = $userRows[0] ?? null;

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['error'] = 'Invalid email or password';
            header('Location: login.php');
            ob_end_flush();
            exit;
        }

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['username'] = $user['username'] ?? $user['email'];
        $_SESSION['user_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['username'] ?? $user['email']);
        
        // Get user role from database
        try {
            $roleStmt = $db->prepare("
                SELECT r.name 
                FROM user_roles ur 
                INNER JOIN roles r ON ur.role_id = r.id 
                WHERE ur.user_id = ? 
                LIMIT 1
            ");
            $roleStmt->execute([$user['id']]);
            // Fetch all to fully buffer (prevents MySQL unbuffered cursor conflicts).
            $roleRows = $roleStmt->fetchAll(PDO::FETCH_COLUMN);
            $role = $roleRows[0] ?? null;
            $_SESSION['user_role'] = $role ?: 'user';
        } catch (\PDOException $e) {
            $_SESSION['user_role'] = 'user';
            error_log("Error fetching user role: " . $e->getMessage());
        }
        
        // Update last login
        try {
            $updateStmt = $db->prepare("
                UPDATE users 
                SET last_login = NOW(), 
                    last_ip = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                $user['id']
            ]);
        } catch (\PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session cookie parameters for better persistence
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => 86400 * 7, // 7 days
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Redirect to intended page or dashboard
        $redirect = $_SESSION['redirect_after_login'] ?? (function_exists('admin_url') ? admin_url('') : (defined('ADMIN_URL') ? ADMIN_URL . '/' : '/modular/admin/'));
        unset($_SESSION['redirect_after_login']);
        
        header('Location: ' . $redirect);
        ob_end_flush();
        exit;

    } catch(PDOException $e) {
        $_SESSION['error'] = 'An error occurred. Please try again later.';
        header('Location: login.php');
        ob_end_flush();
        exit;
    }
} else {
    header('Location: login.php');
    ob_end_flush();
    exit;
} 

// Clean output buffer
ob_end_flush();