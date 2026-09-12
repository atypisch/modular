<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';
require_once CORE_PATH . '/functions/csrf.php';
require_once __DIR__ . '/_helpers.php';

$page_title = 'Reset password';
$flash = md_auth_flash();
$sent = !empty($_GET['sent']);

$content = md_auth_shell_open();

if ($sent) {
    $content .= '
        <div class="auth-success">
          <div class="auth-success-ico">' . md_auth_icon('mail') . '</div>
          <h2 class="auth-h">Check your inbox</h2>
          <p class="auth-lead">If that email is registered, we sent a reset link. It expires in 30 minutes.</p>
          <a class="btn btn--ghost auth-submit" href="' . htmlspecialchars(md_auth_url('login.php')) . '">Back to sign in</a>
        </div>';
} else {
    $content .= '
        <h2 class="auth-h">Reset your password</h2>
        <p class="auth-lead">Enter your email and we will send a reset link.</p>
        ' . $flash . '
        <form action="' . htmlspecialchars(md_auth_url('process_forgot_password.php')) . '" method="POST" class="auth-form">
          ' . csrf_field() . '
          <div class="field">
            <label class="field-label" for="email">Email</label>
            <input class="inp" type="email" id="email" name="email" placeholder="you@studio.nl" required autocomplete="username">
          </div>
          <button type="submit" class="btn btn--accent auth-submit">Send reset link</button>
        </form>
        <div class="auth-alt"><a class="auth-link auth-back" href="' . htmlspecialchars(md_auth_url('login.php')) . '">' . md_auth_icon('chevLeft') . ' Back to sign in</a></div>';
}

$content .= md_auth_shell_close();

require_once dirname(__DIR__) . '/templates/header.php';
echo $content;
require_once dirname(__DIR__) . '/templates/footer.php';
