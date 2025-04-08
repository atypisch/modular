<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/config.php';
require_once dirname(__FILE__) . '/data/UserData.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['error'] = 'Invalid confirmation link';
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$userData = new UserData();
$user = $userData->getUserByConfirmationToken($token);

if (!$user) {
    $_SESSION['error'] = 'Invalid or expired confirmation link';
    header('Location: /modular/admin/auth/login.php');
    exit;
}

if ($userData->confirmUser($token)) {
    $_SESSION['success'] = 'Your account has been confirmed! You can now log in.';
} else {
    $_SESSION['error'] = 'Failed to confirm your account. Please try again or contact support.';
}

header('Location: /modular/admin/auth/login.php');
exit; 