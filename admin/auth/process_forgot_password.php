<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(dirname(__FILE__)) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    // Validate input
    if (empty($email)) {
        $_SESSION['error'] = 'Please enter your email address';
        header('Location: /modular/admin/auth/forgot_password.php');
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
            header('Location: /modular/admin/auth/forgot_password.php');
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

        // Send the reset email
        $reset_link = SITE_URL . "/admin/auth/reset_password.php?token=" . $token;
        error_log("Reset link: " . $reset_link);
        
        $subject = "Password Reset Request";
        $message = "Hello,\n\n";
        $message .= "You have requested to reset your password. Please click the following link to reset it:\n\n";
        $message .= $reset_link . "\n\n";
        $message .= "This link will expire in 1 hour.\n\n";
        $message .= "If you did not request this password reset, please ignore this email.\n\n";
        $message .= "Best regards,\nModular CMS Team";

        $headers = 'From: noreply@modular.com' . "\r\n" .
                  'Reply-To: noreply@modular.com' . "\r\n" .
                  'X-Mailer: PHP/' . phpversion();

        if (mail($email, $subject, $message, $headers)) {
            $_SESSION['success'] = 'A password reset link has been sent to your email address.';
        } else {
            $_SESSION['error'] = 'Failed to send reset email. Please try again later.';
        }

        header('Location: /modular/admin/auth/forgot_password.php');
        exit;

    } catch(PDOException $e) {
        // Log the error for debugging
        error_log("Password reset error: " . $e->getMessage());
        $_SESSION['error'] = 'An error occurred. Please try again later.';
        header('Location: /modular/admin/auth/forgot_password.php');
        exit;
    }
} else {
    header('Location: /modular/admin/auth/forgot_password.php');
    exit;
} 