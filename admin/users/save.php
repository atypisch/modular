<?php
/**
 * Users Save Handler
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/data/init.php';
require_once CORE_PATH . '/functions/auth.php';
require_once ADMIN_PATH . '/data/userdata.php';

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /modular/admin/users');
    exit;
}

$userData = new userdata();
$update_data = [
    'username' => filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING) ?? '',
    'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '',
    'first_name' => filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING) ?? '',
    'last_name' => filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING) ?? '',
    'role' => filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING) ?? 'editor',
    'status' => filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) ?? 'active'
];

// Validate required fields
if (empty($update_data['username']) || empty($update_data['email'])) {
    $_SESSION['error'] = 'Username and email are required.';
    header('Location: /modular/admin/users/edit.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
    exit;
}

// If password is provided, update it
if (!empty($_POST['password'])) {
    if (strlen($_POST['password']) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters.';
        header('Location: /modular/admin/users/edit.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
        exit;
    }
    $update_data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
}

try {
    if (isset($_POST['id'])) {
        // Update existing user
        if ($userData->updateUser($_POST['id'], $update_data)) {
            $_SESSION['success'] = 'User updated successfully.';
            header('Location: /modular/admin/users');
            exit;
        } else {
            $_SESSION['error'] = 'Failed to update user.';
        }
    } else {
        // Create new user
        if (empty($_POST['password'])) {
            $_SESSION['error'] = 'Password is required for new users.';
        } else {
            $new_id = $userData->createUser($update_data);
            if ($new_id) {
                $_SESSION['success'] = 'User created successfully.';
                header('Location: /modular/admin/users');
                exit;
            } else {
                $_SESSION['error'] = 'Failed to create user.';
            }
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
    error_log("User save error: " . $e->getMessage());
}

header('Location: /modular/admin/users/edit.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
exit;
