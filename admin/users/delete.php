<?php
/**
 * Users Delete Handler
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$id) {
    $_SESSION['error'] = 'User ID is required.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users');
    exit;
}

// Prevent deleting yourself
if ($id == ($_SESSION['user_id'] ?? 0)) {
    $_SESSION['error'] = 'You cannot delete your own account.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users');
    exit;
}

$db = $GLOBALS['db'];

try {
    // Soft delete
    $stmt = $db->prepare("UPDATE users SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = 'User deleted successfully.';
} catch (Exception $e) {
    $_SESSION['error'] = 'Error deleting user: ' . $e->getMessage();
    error_log("User delete error: " . $e->getMessage());
}

header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users');
exit;
