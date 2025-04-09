<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/config/config.php';

// Clear any existing messages
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// Load the theme with additional styles from config
require_once dirname(dirname(dirname(__FILE__))) . '/public/themes/' . DEFAULT_THEME . '/index.php';

$page_title = 'Register';
$content = '
    <div class="auth-container">
        <div class="auth-card">
            <h2 class="auth-title">Create Account</h2>
            ' . ($error ? '<div class="alert alert-error">' . htmlspecialchars($error) . '</div>' : '') . '
            ' . ($success ? '<div class="alert alert-success">' . htmlspecialchars($success) . '</div>' : '') . '
            <form action="/modular/admin/auth/process_register.php" method="POST" class="auth-form">
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
                    <a href="/modular/admin/auth/login.php" class="text-blue-600 hover:text-blue-800">Already have an account? Login</a>
                </div>
            </form>
        </div>
    </div>
';

// Render the page
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

        function checkPasswordMatch() {
            const icon = matchElement.querySelector('.icon');
            const text = matchElement.querySelector('.text');
            
            // Only show the match element if confirm password has been touched
            if (confirmPassword.value.length > 0) {
                matchElement.style.display = 'block';
                const passwordsMatch = password.value === confirmPassword.value && password.value !== '';

                if (passwordsMatch) {
                    icon.textContent = '✓';
                    icon.style.color = '#28a745';
                    text.style.color = '#28a745';
                    text.textContent = 'Passwords match';
                } else {
                    icon.textContent = '✗';
                    icon.style.color = '#dc3545';
                    text.style.color = '#dc3545';
                    text.textContent = 'Passwords do not match';
                }

                return passwordsMatch;
            } else {
                matchElement.style.display = 'none';
                return false;
            }
        }

        function validatePassword() {
            let allValid = true;
            for (const [requirement, regex] of Object.entries(requirements)) {
                const isValid = regex.test(password.value);
                updateRequirement(requirement, isValid);
                if (!isValid) allValid = false;
            }
            return allValid;
        }

        function updateSubmitButton() {
            const passwordValid = validatePassword();
            const passwordsMatch = checkPasswordMatch();
            submitBtn.disabled = !(passwordValid && passwordsMatch);
        }

        password.addEventListener('input', updateSubmitButton);
        confirmPassword.addEventListener('input', updateSubmitButton);
    });
</script> 