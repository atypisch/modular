<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/config.php';

$page_title = 'Register';
$content = '
    <div class="auth-container">
        <div class="auth-card">
            <h2 class="auth-title">Create an Account</h2>
            ' . (isset($_SESSION['error']) ? '<div class="alert alert-error">' . $_SESSION['error'] . '</div>' : '') . '
            ' . (isset($_SESSION['success']) ? '<div class="alert alert-success">' . $_SESSION['success'] . '</div>' : '') . '
            <form action="/modular/admin/auth/process_register.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" id="name" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary w-full">Register</button>
                </div>
                <div class="text-center">
                    <a href="/modular/admin/auth/login.php" class="text-blue-600 hover:text-blue-800">Already have an account? Login</a>
                </div>
            </form>
        </div>
    </div>
';

$additional_styles = '
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 60vh;
    }
    .auth-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 400px;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #333;
    }
    .form-group input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .btn-primary {
        background: #007bff;
        color: white;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        width: 100%;
    }
    .auth-links {
        margin-top: 1rem;
        text-align: center;
    }
    .auth-links a {
        color: #007bff;
        text-decoration: none;
    }
    .alert {
        padding: 0.75rem;
        margin-bottom: 1rem;
        border-radius: 4px;
    }
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
';

// Clear any existing session messages
unset($_SESSION['error']);
unset($_SESSION['success']);

// Load the theme with additional styles
require THEMES_PATH . '/' . DEFAULT_THEME . '/index.php'; 