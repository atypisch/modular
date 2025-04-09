<?php
session_start();
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
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Email already registered';
            header('Location: /modular/admin/auth/register.php');
            exit;
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        error_log("Password hashed successfully");

        // Insert new user
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
        error_log("Executing insert with values - Name: " . $name . ", Email: " . $email);
        
        try {
            $result = $stmt->execute([$name, $email, $hashed_password]);
            
            if ($result) {
                error_log("User registration successful");
                $_SESSION['success'] = 'Registration successful! You can now login.';
                header('Location: /modular/admin/auth/login.php');
                exit;
            } else {
                error_log("Insert statement returned false");
                throw new PDOException("Failed to create user");
            }
        } catch (PDOException $insertError) {
            error_log("Insert error: " . $insertError->getMessage());
            error_log("SQL State: " . $insertError->getCode());
            error_log("Error Info: " . print_r($stmt->errorInfo(), true));
            throw $insertError;
        }

    } catch(PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        error_log("Error Info: " . print_r($stmt->errorInfo(), true));
        error_log("Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = 'An error occurred during registration. Please try again.';
        header('Location: /modular/admin/auth/register.php');
        exit;
    }
} else {
    header('Location: /modular/admin/auth/register.php');
    exit;
} 