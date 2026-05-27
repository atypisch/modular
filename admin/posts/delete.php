<?php
/**
 * Posts Delete Handler
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

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$postTypeIdParam = $_GET['post_type_id'] ?? null;

if (!$id) {
    $_SESSION['error'] = 'Post ID is required.';
    header('Location: ' . admin_posts_index_url(admin_posts_resolve_post_type_id(null, $postTypeIdParam)));
    exit;
}

$db = $GLOBALS['db'];
$userId = $_SESSION['user_id'] ?? 1;
$postTypeId = admin_posts_resolve_post_type_id((int) $id, $postTypeIdParam);

try {
    // Soft delete
    $stmt = $db->prepare("UPDATE posts SET deleted_at = NOW(), updated_by = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$userId, $id]);
    
    $_SESSION['success'] = 'Post moved to trash.';
} catch (Exception $e) {
    $_SESSION['error'] = 'Error deleting post: ' . $e->getMessage();
    error_log("Post delete error: " . $e->getMessage());
}

header('Location: ' . admin_posts_index_url($postTypeId));
exit;
