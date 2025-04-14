<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/config/config.php';

$page_title = 'Login';
$content = '
    <div class="auth-container">
        <div class="auth-card">
            <h2 class="auth-title">Login</h2>
            ' . (isset($_SESSION['error']) ? '<div class="alert alert-error">' . $_SESSION['error'] . '</div>' : '') . '
            ' . (isset($_SESSION['success']) ? '<div class="alert alert-success">' . $_SESSION['success'] . '</div>' : '') . '
            <form action="/modular/admin/auth/process_login.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary w-full">Login</button>
                </div>
                <div class="auth-links">
                    <a href="/modular/admin/auth/forgot_password.php" class="text-blue-600 hover:text-blue-800">Forgot Password?</a>
                    <a href="/modular/admin/auth/register.php" class="text-blue-600 hover:text-blue-800">Create an account</a>
                </div>
            </form>
        </div>
    </div>
';

// Clear any existing session messages
unset($_SESSION['error']);
unset($_SESSION['success']);

// Include header
require_once dirname(dirname(__FILE__)) . '/templates/header.php';

// Output content
echo $content;
?>
</body>
</html>