<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once CORE_PATH . '/functions/csrf.php';

// Clear any existing messages
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

$page_title = 'Register';
$loginUrl = (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin');
$loginUrl = rtrim((string) $loginUrl, '/') . '/auth/login.php';

// Block registration unless explicitly enabled (settings → allow_registration).
$registrationAllowed = false;
if (isset($GLOBALS['db'])) {
    try {
        $stmt = $GLOBALS['db']->prepare("SELECT option_value FROM settings WHERE option_name = 'allow_registration' LIMIT 1");
        $stmt->execute();
        $registrationAllowed = in_array((string) $stmt->fetchColumn(), ['1', 'true', 'on', 'yes'], true);
    } catch (Throwable $e) { $registrationAllowed = false; }
}
if (!$registrationAllowed) {
    $_SESSION['error'] = 'Registration is disabled.';
    header('Location: ' . $loginUrl);
    exit;
}

$content = '
    <div class="auth-container">
        <div class="auth-card">
            <h2 class="auth-title">Create Account</h2>
            ' . ($error ? '<div class="alert alert-error">' . htmlspecialchars($error) . '</div>' : '') . '
            ' . ($success ? '<div class="alert alert-success">' . htmlspecialchars($success) . '</div>' : '') . '
            <form action="' . (defined('ADMIN_URL') ? ADMIN_URL : '') . '/auth/process_register.php" method="POST" class="auth-form">
                ' . csrf_field() . '
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                    <div class="password-requirements">
                        <div class="requirement" data-requirement="length">
                            <span class="icon">✗</span>
                            <span class="text">At least 8 characters</span>
                        </div>
                        <div class="requirement" data-requirement="uppercase">
                            <span class="icon">✗</span>
                            <span class="text">At least one uppercase letter</span>
                        </div>
                        <div class="requirement" data-requirement="lowercase">
                            <span class="icon">✗</span>
                            <span class="text">At least one lowercase letter</span>
                        </div>
                        <div class="requirement" data-requirement="number">
                            <span class="icon">✗</span>
                            <span class="text">At least one number</span>
                        </div>
                        <div class="requirement" data-requirement="special">
                            <span class="icon">✗</span>
                            <span class="text">At least one special character</span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                    <div class="password-match" style="display: none;">
                        <span class="icon">✗</span>
                        <span class="text">Passwords do not match</span>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary w-full" id="submit-btn" disabled>Register</button>
                </div>
                <div class="auth-links">
                    <a href="' . esc_url($loginUrl) . '" class="text-blue-600 hover:text-blue-800">Already have an account? Login</a>
                </div>
            </form>
        </div>
    </div>
';

// Include header
require_once dirname(dirname(__FILE__)) . '/templates/header.php';

// Output content
echo $content;
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submit-btn');
        const matchElement = document.querySelector('.password-match');
        const requirements = {
            length: /.{8,}/,
            uppercase: /[A-Z]/,
            lowercase: /[a-z]/,
            number: /[0-9]/,
            special: /[^A-Za-z0-9]/
        };

        function updateRequirement(requirement, isValid) {
            const element = document.querySelector(`[data-requirement="${requirement}"]`);
            const icon = element.querySelector('.icon');
            const text = element.querySelector('.text');
            
            if (isValid) {
                icon.textContent = '✓';
                icon.style.color = '#28a745';
                text.style.color = '#28a745';
            } else {
                icon.textContent = '✗';
                icon.style.color = '#dc3545';
                text.style.color = '#dc3545';
            }
        }

        function validatePassword() {
            const value = password.value;
            let isValid = true;

            for (const [requirement, regex] of Object.entries(requirements)) {
                const requirementValid = regex.test(value);
                updateRequirement(requirement, requirementValid);
                if (!requirementValid) isValid = false;
            }

            return isValid;
        }

        function validateConfirmPassword() {
            const isMatch = password.value === confirmPassword.value && password.value !== '';
            matchElement.style.display = confirmPassword.value ? 'block' : 'none';
            
            const icon = matchElement.querySelector('.icon');
            const text = matchElement.querySelector('.text');
            
            if (isMatch) {
                icon.textContent = '✓';
                icon.style.color = '#28a745';
                text.textContent = 'Passwords match';
                text.style.color = '#28a745';
            } else {
                icon.textContent = '✗';
                icon.style.color = '#dc3545';
                text.textContent = 'Passwords do not match';
                text.style.color = '#dc3545';
            }

            return isMatch;
        }

        function updateSubmitButton() {
            submitBtn.disabled = !(validatePassword() && validateConfirmPassword());
        }

        password.addEventListener('input', () => {
            validatePassword();
            if (confirmPassword.value) validateConfirmPassword();
            updateSubmitButton();
        });

        confirmPassword.addEventListener('input', () => {
            validateConfirmPassword();
            updateSubmitButton();
        });
    });
</script>
<?php require_once dirname(dirname(__FILE__)) . '/templates/footer.php'; ?>