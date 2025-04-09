<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(__FILE__) . '/data/UserData.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header('Location: /modular/admin/auth/login.php');
        exit;
    }

    try {
        // Check if user exists
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['error'] = 'Invalid email or password';
            header('Location: /modular/admin/auth/login.php');
            exit;
        }

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // Redirect to dashboard
        header('Location: /modular/admin/');
        exit;

    } catch(PDOException $e) {
        $_SESSION['error'] = 'An error occurred. Please try again later.';
        header('Location: /modular/admin/auth/login.php');
        exit;
    }
} else {
    header('Location: /modular/admin/auth/login.php');
    exit;
} 