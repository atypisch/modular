<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(dirname(__FILE__)) . '/config/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Log the received data (without password)
    error_log("Registration attempt - Email: " . $email . ", Name: " . $name);

    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: /modular/admin/auth/register.php');
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match';
        header('Location: /modular/admin/auth/register.php');
        exit;
    }

    // Validate password requirements
    if (strlen($password) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters long';
        header('Location: /modular/admin/auth/register.php');
        exit;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $_SESSION['error'] = 'Password must contain at least one uppercase letter';
        header('Location: /modular/admin/auth/register.php');
        exit;
    }

    if (!preg_match('/[a-z]/', $password)) {
        $_SESSION['error'] = 'Password must contain at least one lowercase letter';
        header('Location: /modular/admin/auth/register.php');
        exit;
    }

    if (!preg_match('/[0-9]/', $password)) {
        $_SESSION['error'] = 'Password must contain at least one number';
        header('Location: /modular/admin/auth/register.php');
        exit;
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $_SESSION['error'] = 'Password must contain at least one special character';
        header('Location: /modular/admin/auth/register.php');
        exit;
    }

    try {
        // Load userdata class
        require_once dirname(dirname(__FILE__)) . '/data/init.php';
        require_once dirname(dirname(__FILE__)) . '/data/userdata.php';
        
        $userData = new userdata();
        
        // Check if email already exists
        $existing = $userData->getUserByEmail($email);
        if ($existing) {
            $_SESSION['error'] = 'Email already registered';
            header('Location: /modular/admin/auth/register.php');
            exit;
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        error_log("Password hashed successfully");

        // Split full_name into first_name and last_name
        $name_parts = explode(' ', $name, 2);
        $first_name = $name_parts[0] ?? '';
        $last_name = $name_parts[1] ?? '';
        $username = $email; // Use email as username by default

        // Create user using userdata class
        $user_data = [
            'username' => $username,
            'email' => $email,
            'password' => $hashed_password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 'editor'
        ];
        
        error_log("Creating user with data - First Name: " . $first_name . ", Last Name: " . $last_name . ", Email: " . $email);
        
        $new_user_id = $userData->createUser($user_data);
        
        if ($new_user_id) {
            error_log("User registration successful, ID: " . $new_user_id);
            $_SESSION['success'] = 'Registration successful! You can now login.';
            header('Location: /modular/admin/auth/login.php');
            exit;
        } else {
            throw new Exception("Failed to create user");
        }

    } catch(Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = 'An error occurred during registration. Please try again.';
        header('Location: /modular/admin/auth/register.php');
        exit;
    }
} else {
    header('Location: /modular/admin/auth/register.php');
    exit;
} 