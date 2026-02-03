<?php
/**
 * Dashboard Index - Japanese Style
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

$page_title = 'Dashboard';
$dashboard_active = true;

$db = $GLOBALS['db'];

// Get statistics
$stats = [
    'total_posts' => 0,
    'published_posts' => 0,
    'draft_posts' => 0,
    'total_users' => 0,
    'total_media' => 0,
    'total_post_types' => 0
];

try {
    $stats['total_posts'] = $db->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL")->fetchColumn();
    $stats['published_posts'] = $db->query("SELECT COUNT(*) FROM posts WHERE status = 'published' AND deleted_at IS NULL")->fetchColumn();
    $stats['draft_posts'] = $db->query("SELECT COUNT(*) FROM posts WHERE status = 'draft' AND deleted_at IS NULL")->fetchColumn();
    $stats['total_users'] = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn();
    $stats['total_media'] = $db->query("SELECT COUNT(*) FROM media WHERE deleted_at IS NULL")->fetchColumn();
    $stats['total_post_types'] = $db->query("SELECT COUNT(*) FROM post_types WHERE deleted_at IS NULL")->fetchColumn();
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

// Get recent posts
$recentPosts = [];
try {
    $stmt = $db->query("
        SELECT p.*, pt.name as post_type_name
        FROM posts p
        INNER JOIN post_types pt ON p.post_type_id = pt.id
        WHERE p.deleted_at IS NULL
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $recentPosts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Dashboard recent posts error: " . $e->getMessage());
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Dashboard</h1>
    </div>

    <div class="grid grid-cols-2 grid-cols-3 grid-cols-4">
        <div class="card">
            <h3 style="font-size: 0.875rem; color: var(--text-color-light); margin-bottom: var(--spacing-sm);">Total Posts</h3>
            <p style="font-size: 2rem; font-weight: 500; margin: 0; color: var(--primary-color);"><?php echo $stats['total_posts']; ?></p>
        </div>
        
        <div class="card">
            <h3 style="font-size: 0.875rem; color: var(--text-color-light); margin-bottom: var(--spacing-sm);">Published</h3>
            <p style="font-size: 2rem; font-weight: 500; margin: 0; color: var(--success-text);"><?php echo $stats['published_posts']; ?></p>
        </div>
        
        <div class="card">
            <h3 style="font-size: 0.875rem; color: var(--text-color-light); margin-bottom: var(--spacing-sm);">Drafts</h3>
            <p style="font-size: 2rem; font-weight: 500; margin: 0; color: var(--text-color-light);"><?php echo $stats['draft_posts']; ?></p>
        </div>
        
        <div class="card">
            <h3 style="font-size: 0.875rem; color: var(--text-color-light); margin-bottom: var(--spacing-sm);">Users</h3>
            <p style="font-size: 2rem; font-weight: 500; margin: 0; color: var(--primary-color);"><?php echo $stats['total_users']; ?></p>
        </div>
        
        <div class="card">
            <h3 style="font-size: 0.875rem; color: var(--text-color-light); margin-bottom: var(--spacing-sm);">Media Files</h3>
            <p style="font-size: 2rem; font-weight: 500; margin: 0; color: var(--primary-color);"><?php echo $stats['total_media']; ?></p>
        </div>
        
        <div class="card">
            <h3 style="font-size: 0.875rem; color: var(--text-color-light); margin-bottom: var(--spacing-sm);">Post Types</h3>
            <p style="font-size: 2rem; font-weight: 500; margin: 0; color: var(--primary-color);"><?php echo $stats['total_post_types']; ?></p>
        </div>
    </div>

    <div class="grid grid-cols-2">
        <div class="card">
            <h3>Quick Actions</h3>
            <div class="quick-actions" style="margin-top: var(--spacing-md);">
                <a href="/modular/admin/post-types/edit.php" class="btn btn-secondary">
                    <i class="fas fa-cube"></i> Create Post Type
                </a>
                <a href="/modular/admin/posts/edit.php" class="btn btn-secondary">
                    <i class="fas fa-file-alt"></i> Create Post
                </a>
                <a href="/modular/admin/media/upload.php" class="btn btn-secondary">
                    <i class="fas fa-upload"></i> Upload Media
                </a>
                <a href="/modular/admin/users/edit.php" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i> Add User
                </a>
            </div>
        </div>
        
        <div class="card">
            <h3>Recent Posts</h3>
            <?php if (empty($recentPosts)): ?>
                <div class="empty-state" style="padding: var(--spacing-lg);">
                    <p class="text-muted">No posts yet.</p>
                </div>
            <?php else: ?>
                <div style="margin-top: var(--spacing-md);">
                    <?php foreach ($recentPosts as $post): ?>
                        <div style="padding: var(--spacing-md); border-bottom: 1px solid var(--border-light);">
                            <div class="flex justify-between items-start">
                                <div style="flex: 1;">
                                    <strong><?php echo htmlspecialchars($post['title'] ?? 'Untitled'); ?></strong>
                                    <div class="text-muted" style="margin-top: 0.25rem; font-size: 13px;">
                                        <?php echo htmlspecialchars($post['post_type_name'] ?? ''); ?> • 
                                        <?php echo date('M j, Y', strtotime($post['created_at'] ?? 'now')); ?>
                                    </div>
                                </div>
                                <a href="/modular/admin/posts/edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: var(--spacing-md);">
                    <a href="/modular/admin/posts" class="btn btn-secondary" style="width: 100%;">
                        View All Posts
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
