<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/config.php';
require_once dirname(__FILE__) . '/data/UserData.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $userData = new UserData();
    $user = $userData->getUserByEmail($email);
    
    if ($user && password_verify($password, $user['password'])) {
        if (!$user['is_confirmed']) {
            $_SESSION['error'] = 'Please confirm your email address before logging in.';
            header('Location: /modular/admin/auth/login.php');
            exit;
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        header('Location: /modular/admin');
        exit;
    } else {
        $_SESSION['error'] = 'Invalid email or password';
        header('Location: /modular/admin/auth/login.php');
        exit;
    }
} else {
    header('Location: /modular/admin/auth/login.php');
    exit;
} 