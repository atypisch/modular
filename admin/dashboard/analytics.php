<?php
/**
 * Dashboard Analytics - Japanese Style
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
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$page_title = 'Analytics';
$dashboard_active = true;

$db = $GLOBALS['db'];

// Get analytics data
$stats = [
    'total_posts' => 0,
    'published_posts' => 0,
    'draft_posts' => 0,
    'total_users' => 0,
    'total_media' => 0
];

try {
    $stats['total_posts'] = $db->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL")->fetchColumn();
    $stats['published_posts'] = $db->query("SELECT COUNT(*) FROM posts WHERE status = 'published' AND deleted_at IS NULL")->fetchColumn();
    $stats['draft_posts'] = $db->query("SELECT COUNT(*) FROM posts WHERE status = 'draft' AND deleted_at IS NULL")->fetchColumn();
    $stats['total_users'] = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn();
    $stats['total_media'] = $db->query("SELECT COUNT(*) FROM media WHERE deleted_at IS NULL")->fetchColumn();
} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Analytics</h1>
    </div>
    
    <div class="grid grid-cols-2 grid-cols-3 grid-cols-5">
        <div class="card">
            <h3 style="font-size: 0.875rem; color: var(--text-color-light); margin-bottom: var(--spacing-sm);">Total Posts</h3>
            <p style="font-size: 2.5rem; font-weight: 500; margin: 0; color: var(--primary-color);"><?php echo $stats['total_posts']; ?></p>
        </div>
        
        <div class="card">
            <h3 style="font-size: 0.875rem; color: var(--text-color-light); margin-bottom: var(--spacing-sm);">Published</h3>
            <p style="font-size: 2.5rem; font-weight: 500; margin: 0; color: var(--success-text);"><?php echo $stats['published_posts']; ?></p>
        </div>
        
        <div class="card">
            <h3 style="font-size: 0.875rem; color: var(--text-color-light); margin-bottom: var(--spacing-sm);">Drafts</h3>
            <p style="font-size: 2.5rem; font-weight: 500; margin: 0; color: var(--text-color-light);"><?php echo $stats['draft_posts']; ?></p>
        </div>
        
        <div class="card">
            <h3 style="font-size: 0.875rem; color: var(--text-color-light); margin-bottom: var(--spacing-sm);">Users</h3>
            <p style="font-size: 2.5rem; font-weight: 500; margin: 0; color: var(--primary-color);"><?php echo $stats['total_users']; ?></p>
        </div>
        
        <div class="card">
            <h3 style="font-size: 0.875rem; color: var(--text-color-light); margin-bottom: var(--spacing-sm);">Media Files</h3>
            <p style="font-size: 2.5rem; font-weight: 500; margin: 0; color: var(--primary-color);"><?php echo $stats['total_media']; ?></p>
        </div>
    </div>
    
    <div class="card mt-4">
        <h3>Analytics Overview</h3>
        <p class="text-muted">Detailed analytics and reporting features coming soon.</p>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
