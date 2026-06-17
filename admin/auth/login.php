<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once CORE_PATH . '/functions/csrf.php';

// Registration toggle (settings → allow_registration). Off by default; when off,
// the sign-up link is hidden here and the register page itself is blocked.
$registrationAllowed = false;
if (isset($GLOBALS['db'])) {
    try {
        $stmt = $GLOBALS['db']->prepare("SELECT option_value FROM settings WHERE option_name = 'allow_registration' LIMIT 1");
        $stmt->execute();
        $registrationAllowed = in_array((string) $stmt->fetchColumn(), ['1', 'true', 'on', 'yes'], true);
    } catch (Throwable $e) { $registrationAllowed = false; }
}
$registerLink = $registrationAllowed
    ? '<a href="' . (function_exists('admin_url') ? admin_url('auth/register.php') : (defined('ADMIN_URL') ? ADMIN_URL . '/auth/register.php' : '/modular/admin/auth/register.php')) . '">Create an account</a>'
    : '';

$page_title = 'Login';
$content = '
    <div class="auth-container">
        <div class="auth-card">
            <h2 class="auth-title">Login</h2>
            ' . (isset($_SESSION['error']) ? '<div class="alert alert-error" style="margin-bottom: 1.5rem; padding: 0.75rem; background: #fee; border: 1px solid #fcc; border-radius: 6px; color: #c33; font-size: 13px;">' . htmlspecialchars($_SESSION['error']) . '</div>' : '') . '
            ' . (isset($_SESSION['success']) ? '<div class="alert alert-success" style="margin-bottom: 1.5rem; padding: 0.75rem; background: #efe; border: 1px solid #cfc; border-radius: 6px; color: #3c3; font-size: 13px;">' . htmlspecialchars($_SESSION['success']) . '</div>' : '') . '
            <form action="' . (function_exists('admin_url') ? admin_url('auth/process_login.php') : (defined('ADMIN_URL') ? ADMIN_URL . '/auth/process_login.php' : '/modular/admin/auth/process_login.php')) . '" method="POST" class="auth-form">
                ' . csrf_field() . '
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
                <a href="' . (function_exists('admin_url') ? admin_url('auth/forgot_password.php') : (defined('ADMIN_URL') ? ADMIN_URL . '/auth/forgot_password.php' : '/modular/admin/auth/forgot_password.php')) . '">Forgot Password?</a>
                ' . $registerLink . '
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