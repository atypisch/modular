<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('csrf_verify') && !csrf_verify()) {
        $_SESSION['error'] = 'Invalid request. Please try again.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/reset_password.php?token=' . urlencode($_POST['token'] ?? ''));
        exit;
    }
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($token) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/reset_password.php?token=' . $token);
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/reset_password.php?token=' . $token);
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

        // Check if token exists and is not expired
        $stmt = $db->prepare("SELECT id, reset_token, reset_expires FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        error_log("User with token: " . print_r($user, true));

        if (!$user) {
            error_log("No user found with token: " . $token);
            $_SESSION['error'] = 'Invalid or expired reset link';
            header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/forgot_password.php');
            exit;
        }

        // Check if token is expired
        $now = date('Y-m-d H:i:s');
        if ($user['reset_expires'] < $now) {
            error_log("Token expired. Expires: " . $user['reset_expires'] . ", Now: " . $now);
            $_SESSION['error'] = 'Invalid or expired reset link';
            header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/forgot_password.php');
            exit;
        }

        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update the user's password and clear the reset token
        $stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $result = $stmt->execute([$hashed_password, $user['id']]);
        error_log("Password update result: " . ($result ? "success" : "failed"));

        if (!$result) {
            throw new PDOException("Failed to update password");
        }

        $_SESSION['success'] = 'Your password has been reset successfully. You can now login with your new password.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
        exit;

    } catch(PDOException $e) {
        // Log the error for debugging
        error_log("Password reset error: " . $e->getMessage());
        $_SESSION['error'] = 'An error occurred. Please try again later.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/reset_password.php?token=' . $token);
        exit;
    }
} else {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/forgot_password.php');
    exit;
} 