<?php
/**
 * Users Bulk Actions Handler
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users');
    exit;
}

$action = $_POST['bulk_action'] ?? '';
$ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];

if (!$action || empty($ids)) {
    $_SESSION['error'] = 'Select at least one user and a bulk action.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users');
    exit;
}

// Prevent deleting or deactivating yourself
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
if (in_array($currentUserId, $ids, true) && $action === 'delete') {
    $ids = array_values(array_filter($ids, function ($id) use ($currentUserId) {
        return $id !== $currentUserId;
    }));
    if (empty($ids)) {
        $_SESSION['error'] = 'You cannot delete your own account.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users');
        exit;
    }
}

try {
    $db = $GLOBALS['db'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    switch ($action) {
        case 'activate':
            $stmt = $db->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE id IN ($placeholders) AND deleted_at IS NULL");
            $stmt->execute($ids);
            $_SESSION['success'] = 'Selected users activated.';
            break;
        case 'deactivate':
            $stmt = $db->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id IN ($placeholders) AND deleted_at IS NULL");
            $stmt->execute($ids);
            $_SESSION['success'] = 'Selected users deactivated.';
            break;
        case 'role_admin':
        case 'role_editor':
        case 'role_user':
            $role = str_replace('role_', '', $action);
            $stmt = $db->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id IN ($placeholders) AND deleted_at IS NULL");
            $params = array_merge([$role], $ids);
            $stmt->execute($params);
            $_SESSION['success'] = 'Role updated for selected users.';
            break;
        case 'delete':
            $stmt = $db->prepare("UPDATE users SET deleted_at = NOW(), updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $_SESSION['success'] = 'Selected users deleted.';
            break;
        default:
            $_SESSION['error'] = 'Invalid bulk action.';
            header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users');
            exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Bulk action failed: ' . $e->getMessage();
    error_log('Users bulk error: ' . $e->getMessage());
}

header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users');
exit;

