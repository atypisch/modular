<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
require_once CORE_PATH . '/functions/mail.php';
require_once CORE_PATH . '/classes/RateLimiter.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('csrf_verify') && !csrf_verify()) {
        $_SESSION['error'] = 'Invalid request. Please try again.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/forgot_password.php');
        exit;
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!\Modular\RateLimiter::check('forgot_password_' . $ip, 5, 3600)) {
        $_SESSION['error'] = 'Too many requests. Try again later.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/forgot_password.php');
        exit;
    }
    $email = $_POST['email'] ?? '';

    // Validate input
    if (empty($email)) {
        $_SESSION['error'] = 'Please enter your email address';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/forgot_password.php');
        exit;
    }

    try {
        // First, check if the required columns exist
        $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        error_log("Database columns: " . print_r($columns, true));
        
        if (!in_array('reset_token', $columns) || !in_array('reset_expires', $columns)) {
            // Create the missing columns
            $db->exec("ALTER TABLE users 
                      ADD COLUMN reset_token VARCHAR(64) NULL,
                      ADD COLUMN reset_expires DATETIME NULL");
            error_log("Added reset_token and reset_expires columns to users table");
        }

        // Check if user exists
        $stmt = $db->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        error_log("User found: " . print_r($user, true));

        if (!$user) {
            $_SESSION['error'] = 'No account found with that email address';
            header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/forgot_password.php');
            exit;
        }

        // Generate a unique token
        $token = bin2hex(random_bytes(32));
        error_log("Generated token: " . $token);

        // Store the token in the database with an expiration time (e.g., 1 hour)
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        error_log("Token expires at: " . $expires);

        // Update user with reset token
        $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $result = $stmt->execute([$token, $expires, $user['id']]);
        error_log("Token update result: " . ($result ? "success" : "failed"));

        if (!$result) {
            throw new PDOException("Failed to update reset token");
        }

        // Send the reset email via centralized mail helper
        $reset_link = SITE_URL . "/admin/auth/reset_password.php?token=" . $token;
        error_log("Reset link: " . $reset_link);

        $sent = mail_send_template('password_reset', $email, [
            'reset_link' => $reset_link,
            'subject_fallback' => 'Password Reset Request',
            'body_fallback' => "Hello,\n\nYou have requested to reset your password. Please click the following link to reset it:\n\n{$reset_link}\n\nThis link will expire in 1 hour.\n\nIf you did not request this password reset, please ignore this email.\n\nBest regards,\nModular CMS Team",
        ]);

        if ($sent) {
            $_SESSION['success'] = 'A password reset link has been sent to your email address.';
        } else {
            $_SESSION['error'] = 'Failed to send reset email. Please try again later.';
        }

        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/forgot_password.php');
        exit;

    } catch(PDOException $e) {
        // Log the error for debugging
        error_log("Password reset error: " . $e->getMessage());
        $_SESSION['error'] = 'An error occurred. Please try again later.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/forgot_password.php');
        exit;
    }
} else {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/forgot_password.php');
    exit;
} 