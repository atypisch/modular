<?php
/**
 * Save Role - update display_name, description, capabilities
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(dirname(__DIR__)) . '/config/config.php';
}
require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/database.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users/roles.php');
    exit;
}
if (!csrf_verify()) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users/roles/edit.php' . (isset($_POST['id']) ? '?id=' . (int)$_POST['id'] : ''));
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$display_name = trim($_POST['display_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$capabilities = isset($_POST['capabilities']) && is_array($_POST['capabilities'])
    ? array_map('intval', array_filter($_POST['capabilities'])) : [];

if ($id <= 0 || $display_name === '') {
    $_SESSION['error'] = 'Invalid role or missing display name.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users/roles.php');
    exit;
}

$db = $GLOBALS['db'];

try {
    $stmt = $db->prepare("SELECT id FROM roles WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'Role not found.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users/roles.php');
        exit;
    }

    $stmt = $db->prepare("UPDATE roles SET display_name = ?, description = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$display_name, $description ?: null, $id]);

    $db->prepare("DELETE FROM role_capabilities WHERE role_id = ?")->execute([$id]);

    if (!empty($capabilities)) {
        $stmt = $db->prepare("INSERT INTO role_capabilities (role_id, capability_id) VALUES (?, ?)");
        foreach ($capabilities as $capId) {
            if ($capId > 0) {
                $stmt->execute([$id, $capId]);
            }
        }
    }

    $_SESSION['success'] = 'Role updated successfully.';
} catch (PDOException $e) {
    error_log('Roles save: ' . $e->getMessage());
    $_SESSION['error'] = 'Failed to save role.';
}

header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users/roles/edit.php?id=' . $id);
exit;
