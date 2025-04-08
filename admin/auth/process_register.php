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
    
    // Enhanced password validation
    if (strlen($password) < 12) {
        $_SESSION['error'] = 'Password must be at least 12 characters long';
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
    
    $userData = new UserData();
    
    // Check if email already exists
    if ($userData->getUserByEmail($email)) {
        $_SESSION['error'] = 'Email already registered';
        header('Location: /modular/admin/auth/register.php');
        exit;
    }
    
    // Generate confirmation token
    $confirmation_token = bin2hex(random_bytes(32));
    
    // Create new user with confirmation token
    $userData->createUser([
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'user',
        'confirmation_token' => $confirmation_token,
        'is_confirmed' => 0
    ]);
    
    // Send confirmation email
    $confirmation_link = SITE_URL . '/admin/auth/confirm.php?token=' . $confirmation_token;
    $to = $email;
    $subject = 'Confirm your Modular CMS account';
    $message = "Hello {$name},\n\n";
    $message .= "Thank you for registering with Modular CMS. Please click the following link to confirm your account:\n\n";
    $message .= $confirmation_link . "\n\n";
    $message .= "If you did not register for this account, please ignore this email.\n\n";
    $message .= "Best regards,\nModular CMS Team";
    
    $headers = 'From: noreply@modular.com' . "\r\n" .
               'Reply-To: noreply@modular.com' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    mail($to, $subject, $message, $headers);
    
    $_SESSION['success'] = 'Registration successful! Please check your email to confirm your account.';
    header('Location: /modular/admin/auth/login.php');
    exit;
} else {
    header('Location: /modular/admin/auth/register.php');
    exit;
} 