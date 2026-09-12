<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';
require_once CORE_PATH . '/functions/csrf.php';
require_once __DIR__ . '/_helpers.php';

$registrationAllowed = md_auth_registration_allowed();
$page_title = 'Sign in';
$flash = md_auth_flash();

$content = md_auth_shell_open() . '
        <h2 class="auth-h">Welcome back</h2>
        <p class="auth-lead">Sign in to your Modular workspace.</p>
        ' . $flash . '
        <form action="' . htmlspecialchars(md_auth_url('process_login.php')) . '" method="POST" class="auth-form">
          ' . csrf_field() . '
          <div class="field">
            <label class="field-label" for="email">Email</label>
            <input class="inp" type="email" id="email" name="email" placeholder="you@studio.nl" required autocomplete="username">
          </div>
          <div class="field">
            <label class="field-label" for="password">Password</label>
            <div class="auth-pwfield">
              <input class="inp" type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
              <button type="button" class="auth-pwtoggle" data-toggle="password" aria-label="Show password">' . md_auth_icon('eye') . '</button>
            </div>
          </div>
          <div class="auth-rowsplit">
            <label class="auth-check"><input type="checkbox" name="remember" value="1"> Remember me</label>
            <a class="auth-link" href="' . htmlspecialchars(md_auth_url('forgot_password.php')) . '">Forgot password?</a>
          </div>
          <button type="submit" class="btn btn--accent auth-submit">Sign in</button>
        </form>
        ' . ($registrationAllowed
            ? '<div class="auth-alt">New to Modular? <a class="auth-link" href="' . htmlspecialchars(md_auth_url('register.php')) . '">Create an account</a></div>'
            : '') . '
' . md_auth_shell_close();

require_once dirname(__DIR__) . '/templates/header.php';
echo $content;
require_once dirname(__DIR__) . '/templates/footer.php';
