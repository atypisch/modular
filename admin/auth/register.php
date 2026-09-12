<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';
require_once CORE_PATH . '/functions/csrf.php';
require_once __DIR__ . '/_helpers.php';

if (!md_auth_registration_allowed()) {
    $_SESSION['error'] = 'Registration is disabled.';
    header('Location: ' . md_auth_url('login.php'));
    exit;
}

$page_title = 'Create account';
$flash = md_auth_flash();

$content = md_auth_shell_open() . '
        <h2 class="auth-h">Create your account</h2>
        <p class="auth-lead">Start modelling content in minutes.</p>
        ' . $flash . '
        <form action="' . htmlspecialchars(md_auth_url('process_register.php')) . '" method="POST" class="auth-form" id="register-form">
          ' . csrf_field() . '
          <div class="field">
            <label class="field-label" for="full_name">Full name</label>
            <input class="inp" type="text" id="full_name" name="full_name" placeholder="Marten Timan" required autocomplete="name">
          </div>
          <div class="field">
            <label class="field-label" for="email">Work email</label>
            <input class="inp" type="email" id="email" name="email" placeholder="you@studio.nl" required autocomplete="username">
          </div>
          <div class="field">
            <label class="field-label" for="password">Password</label>
            <div class="auth-pwfield">
              <input class="inp" type="password" id="password" name="password" placeholder="At least 8 characters" required autocomplete="new-password">
              <button type="button" class="auth-pwtoggle" data-toggle="password" aria-label="Show password">' . md_auth_icon('eye') . '</button>
            </div>
            <div class="auth-strength" id="strength" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <ul class="password-requirements">
              <li class="requirement" data-requirement="length">At least 8 characters</li>
              <li class="requirement" data-requirement="uppercase">One uppercase letter</li>
              <li class="requirement" data-requirement="lowercase">One lowercase letter</li>
              <li class="requirement" data-requirement="number">One number</li>
              <li class="requirement" data-requirement="special">One special character</li>
            </ul>
          </div>
          <div class="field">
            <label class="field-label" for="confirm_password">Confirm password</label>
            <input class="inp" type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
            <div class="password-match" id="password-match" hidden></div>
          </div>
          <button type="submit" class="btn btn--accent auth-submit" id="submit-btn" disabled>Create account</button>
        </form>
        <div class="auth-alt">Already have an account? <a class="auth-link" href="' . htmlspecialchars(md_auth_url('login.php')) . '">Sign in</a></div>
' . md_auth_shell_close();

$additional_scripts = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {
  var password = document.getElementById('password');
  var confirmPassword = document.getElementById('confirm_password');
  var submitBtn = document.getElementById('submit-btn');
  var matchEl = document.getElementById('password-match');
  var bars = document.querySelectorAll('#strength span');
  var requirements = {
    length: /.{8,}/,
    uppercase: /[A-Z]/,
    lowercase: /[a-z]/,
    number: /[0-9]/,
    special: /[^A-Za-z0-9]/
  };

  function strengthScore(v) {
    var s = 0;
    if (v.length >= 8) s++;
    if (/[A-Z]/.test(v)) s++;
    if (/[0-9]/.test(v)) s++;
    if (/[^A-Za-z0-9]/.test(v)) s++;
    return s;
  }

  function validatePassword() {
    var value = password.value;
    var ok = true;
    Object.keys(requirements).forEach(function (key) {
      var valid = requirements[key].test(value);
      var el = document.querySelector('[data-requirement="' + key + '"]');
      if (el) el.classList.toggle('is-ok', valid);
      if (!valid) ok = false;
    });
    var score = strengthScore(value);
    bars.forEach(function (b, i) { b.classList.toggle('on', i < score); });
    return ok;
  }

  function validateConfirm() {
    var match = password.value !== '' && password.value === confirmPassword.value;
    if (!confirmPassword.value) {
      matchEl.hidden = true;
      return false;
    }
    matchEl.hidden = false;
    matchEl.textContent = match ? 'Passwords match' : 'Passwords do not match';
    matchEl.classList.toggle('is-ok', match);
    matchEl.classList.toggle('is-bad', !match);
    return match;
  }

  function update() {
    submitBtn.disabled = !(validatePassword() && validateConfirm());
  }

  password.addEventListener('input', update);
  confirmPassword.addEventListener('input', update);
  update();
});
</script>
JS;

require_once dirname(__DIR__) . '/templates/header.php';
echo $content;
require_once dirname(__DIR__) . '/templates/footer.php';
