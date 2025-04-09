<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/config/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get token from URL
$token = $_GET['token'] ?? '';
error_log("Token received: " . $token);

// Check if token exists
if (empty($token)) {
    $_SESSION['error'] = 'Invalid or expired reset link';
    header('Location: /modular/admin/auth/forgot_password.php');
    exit;
}

try {
    // Check if users table has the required columns
    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    error_log("Database columns: " . print_r($columns, true));

    if (!in_array('reset_token', $columns) || !in_array('reset_expires', $columns)) {
        // Add missing columns
        $db->exec("ALTER TABLE users 
            ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL,
            ADD COLUMN reset_expires DATETIME DEFAULT NULL");
        error_log("Added reset_token and reset_expires columns");
    }

    // Find user with this token
    $stmt = $db->prepare("SELECT id, email, reset_expires FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("User found: " . print_r($user, true));

    if (!$user) {
        $_SESSION['error'] = 'Invalid or expired reset link';
        header('Location: /modular/admin/auth/forgot_password.php');
        exit;
    }

    // Check if token has expired
    if (strtotime($user['reset_expires']) < time()) {
        error_log("Token expired at: " . $user['reset_expires']);
        $_SESSION['error'] = 'Reset link has expired';
        header('Location: /modular/admin/auth/forgot_password.php');
        exit;
    }

    // Set page title and content
    $page_title = 'Reset Password';
    $content = '
    <div class="auth-container">
        <div class="auth-box">
            <h1>Reset Password</h1>
            
            ' . (isset($_SESSION['error']) ? '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>' : '') . '
            ' . (isset($_SESSION['success']) ? '<div class="alert alert-success">' . $_SESSION['success'] . '</div>' : '') . '
            
            <form id="resetPasswordForm" action="process_reset_password.php" method="POST">
                <input type="hidden" name="token" value="' . htmlspecialchars($token) . '">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <div class="password-requirements">
                    <h4>Password Requirements:</h4>
                    <ul>
                        <li id="length" class="invalid">At least 8 characters long</li>
                        <li id="uppercase" class="invalid">At least one uppercase letter</li>
                        <li id="lowercase" class="invalid">At least one lowercase letter</li>
                        <li id="number" class="invalid">At least one number</li>
                        <li id="special" class="invalid">At least one special character</li>
                        <li id="match" class="invalid" style="display: none;">Passwords match</li>
                    </ul>
                </div>
                
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const password = document.getElementById("password");
        const confirm = document.getElementById("confirm_password");
        const matchIndicator = document.getElementById("match");
        let confirmTouched = false;

        function validatePassword() {
            const value = password.value;
            
            // Length check
            document.getElementById("length").className = value.length >= 8 ? "valid" : "invalid";
            
            // Uppercase check
            document.getElementById("uppercase").className = /[A-Z]/.test(value) ? "valid" : "invalid";
            
            // Lowercase check
            document.getElementById("lowercase").className = /[a-z]/.test(value) ? "valid" : "invalid";
            
            // Number check
            document.getElementById("number").className = /[0-9]/.test(value) ? "valid" : "invalid";
            
            // Special character check
            document.getElementById("special").className = /[^A-Za-z0-9]/.test(value) ? "valid" : "invalid";
        }

        function checkPasswordMatch() {
            if (confirmTouched) {
                const passwordsMatch = password.value === confirm.value;
                matchIndicator.style.display = "block";
                matchIndicator.className = passwordsMatch ? "valid" : "invalid";
                matchIndicator.textContent = passwordsMatch ? "✓ Passwords match" : "✗ Passwords do not match";
            }
        }

        password.addEventListener("input", function() {
            validatePassword();
            checkPasswordMatch();
        });

        confirm.addEventListener("input", function() {
            confirmTouched = true;
            checkPasswordMatch();
        });

        // Initial validation
        validatePassword();
    });
    </script>';

    // Load the theme
    require_once dirname(dirname(dirname(__FILE__))) . '/public/themes/default/index.php';

} catch(PDOException $e) {
    error_log("Reset password error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
    header('Location: /modular/admin/auth/forgot_password.php');
    exit;
}

// Clear session messages
unset($_SESSION['error']);
unset($_SESSION['success']); 