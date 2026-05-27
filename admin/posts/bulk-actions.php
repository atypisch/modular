<?php
/**
 * Bulk actions for posts list: soft delete and status changes.
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

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('posts'));
    exit;
}

if (!csrf_verify()) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: ' . admin_url('posts'));
    exit;
}

$db = $GLOBALS['db'];
$userId = (int) ($_SESSION['user_id'] ?? 1);

$action = isset($_POST['bulk_action']) ? trim((string) $_POST['bulk_action']) : '';
$ids = isset($_POST['post_ids']) && is_array($_POST['post_ids']) ? $_POST['post_ids'] : [];
$ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($id) {
    return $id > 0;
})));

$redirectPt = isset($_POST['redirect_post_type_id']) ? trim((string) $_POST['redirect_post_type_id']) : '';
$redirectUrl = admin_url('posts');
if ($redirectPt !== '' && ctype_digit($redirectPt)) {
    $redirectUrl = admin_url('posts?post_type_id=' . (int) $redirectPt);
}

if (empty($ids)) {
    $_SESSION['error'] = 'No posts selected.';
    header('Location: ' . $redirectUrl);
    exit;
}

if (count($ids) > 500) {
    $_SESSION['error'] = 'Too many posts selected.';
    header('Location: ' . $redirectUrl);
    exit;
}

$allowedActions = ['trash', 'draft', 'published', 'archived'];
if (!in_array($action, $allowedActions, true)) {
    $_SESSION['error'] = 'Invalid bulk action.';
    header('Location: ' . $redirectUrl);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));

try {
    if ($action === 'trash') {
        $sql = "UPDATE posts SET deleted_at = NOW(), updated_by = ?, updated_at = NOW() WHERE id IN ($placeholders) AND deleted_at IS NULL";
        $params = array_merge([$userId], $ids);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $n = $stmt->rowCount();
        $_SESSION['success'] = $n === 1 ? '1 post moved to trash.' : $n . ' posts moved to trash.';
    } else {
        $newStatus = $action;
        if ($newStatus === 'published') {
            $sql = "UPDATE posts SET status = 'published', published_at = COALESCE(published_at, NOW()), updated_by = ?, updated_at = NOW() WHERE id IN ($placeholders) AND deleted_at IS NULL";
            $params = array_merge([$userId], $ids);
        } else {
            $sql = "UPDATE posts SET status = ?, updated_by = ?, updated_at = NOW() WHERE id IN ($placeholders) AND deleted_at IS NULL";
            $params = array_merge([$newStatus, $userId], $ids);
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $n = $stmt->rowCount();
        $_SESSION['success'] = $n === 1 ? '1 post updated.' : $n . ' posts updated.';
    }
} catch (Throwable $e) {
    error_log('bulk-actions: ' . $e->getMessage());
    $_SESSION['error'] = 'Bulk action failed.';
}

header('Location: ' . $redirectUrl);
exit;
