<?php
/**
 * Delete Form
 */

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$id) {
    $_SESSION['error'] = 'Invalid form ID.';
    header('Location: /modular/admin/forms');
    exit;
}

$db = $GLOBALS['db'];

try {
    $stmt = $db->prepare("DELETE FROM forms WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = 'Form deleted successfully.';
} catch (Exception $e) {
    $_SESSION['error'] = 'Error deleting form: ' . $e->getMessage();
    error_log("Error deleting form: " . $e->getMessage());
}

header('Location: /modular/admin/forms');
exit;

