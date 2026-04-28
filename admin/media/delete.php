<?php
/**
 * Media Delete Handler
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
    $_SESSION['error'] = 'Media ID is required.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/media');
    exit;
}

$db = $GLOBALS['db'];
$userId = $_SESSION['user_id'] ?? 1;

try {
    // Soft delete
    $stmt = $db->prepare("UPDATE media SET deleted_at = NOW(), updated_by = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$userId, $id]);
    
    $_SESSION['success'] = 'Media deleted successfully.';
} catch (Exception $e) {
    $_SESSION['error'] = 'Error deleting media: ' . $e->getMessage();
    error_log("Media delete error: " . $e->getMessage());
}

header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/media');
exit;
