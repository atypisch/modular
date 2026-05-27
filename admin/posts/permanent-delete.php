<?php
/**
 * Permanently delete a post that is already in trash.
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
    header('Location: ' . admin_posts_index_url(admin_posts_resolve_post_type_id(null, $postTypeIdParam), 'trash'));
    exit;
}

$db = $GLOBALS['db'];
$postTypeId = admin_posts_resolve_post_type_id((int) $id, $postTypeIdParam);

try {
    $removed = admin_posts_permanently_delete($db, [(int) $id]);
    if ($removed > 0) {
        $_SESSION['success'] = 'Post permanently deleted.';
    } else {
        $_SESSION['error'] = 'Post not found in trash.';
    }
} catch (Throwable $e) {
    $_SESSION['error'] = 'Error deleting post: ' . $e->getMessage();
    error_log('Post permanent delete error: ' . $e->getMessage());
}

header('Location: ' . admin_posts_index_url($postTypeId, 'trash'));
exit;
