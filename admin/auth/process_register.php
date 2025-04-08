<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/config.php';
require_once dirname(__FILE__) . '/data/UserData.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
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
    
    if (strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters long';
        header('Location: /modular/admin/auth/register.php');
        exit;
    }
    
    $userData = new UserData();
    
    // Check if email already exists
    if ($userData->getUserByEmail($email)) {
        $_SESSION['error'] = 'Email already registered';
        header('Location: /modular/admin/auth/register.php');
        exit;
    }
    
    // Create new user
    $userData->createUser([
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'user'
    ]);
    
    $_SESSION['success'] = 'Registration successful! Please login.';
    header('Location: /modular/admin/auth/login.php');
    exit;
} else {
    header('Location: /modular/admin/auth/register.php');
    exit;
} 