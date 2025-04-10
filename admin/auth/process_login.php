<?php
// Start output buffering to prevent header errors
ob_start();

session_start();

// First include config to set up database connection
require_once dirname(__DIR__) . '/config/config.php';

// Then include init which depends on the database connection
require_once dirname(__DIR__) . '/admin/data/init.php';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Email and password are required';
        header('Location: login.php');
        ob_end_flush();
        exit;
    }

    try {
        // Check if user exists
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['error'] = 'Invalid email or password';
            header('Location: login.php');
            ob_end_flush();
            exit;
        }

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // Redirect to dashboard
        header('Location: /modular/admin/');
        ob_end_flush();
        exit;

    } catch(PDOException $e) {
        $_SESSION['error'] = 'An error occurred. Please try again later.';
        header('Location: login.php');
        ob_end_flush();
        exit;
    }
} else {
    header('Location: login.php');
    ob_end_flush();
    exit;
} 

// Clean output buffer
ob_end_flush();