<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(dirname(__FILE__)) . '/config/config.php';

$page_title = 'Login';
$content = '
    <div class="auth-container">
        <div class="auth-card">
            <h2 class="auth-title">Login</h2>
            ' . (isset($_SESSION['error']) ? '<div class="alert alert-error" style="margin-bottom: 1.5rem; padding: 0.75rem; background: #fee; border: 1px solid #fcc; border-radius: 6px; color: #c33; font-size: 13px;">' . htmlspecialchars($_SESSION['error']) . '</div>' : '') . '
            ' . (isset($_SESSION['success']) ? '<div class="alert alert-success" style="margin-bottom: 1.5rem; padding: 0.75rem; background: #efe; border: 1px solid #cfc; border-radius: 6px; color: #3c3; font-size: 13px;">' . htmlspecialchars($_SESSION['success']) . '</div>' : '') . '
            <form action="/modular/admin/auth/process_login.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required autocomplete="current-password">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
            <div class="auth-links" style="clear: both;">
                <a href="/modular/admin/auth/forgot_password.php">Forgot Password?</a>
                <a href="/modular/admin/auth/register.php">Create an account</a>
            </div>
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

require_once dirname(dirname(__FILE__)) . '/templates/footer.php';