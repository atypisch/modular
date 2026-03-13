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
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$page_title = 'Dashboard';
$dashboard_active = true;

$db = $GLOBALS['db'] ?? null;
if ($db === null) {
    require_once ADMIN_PATH . '/templates/header.php';
    echo '<div class="container"><div class="alert alert-danger">Database connection is not available. Please <a href="' . ADMIN_URL . '/setup/">run setup</a> or check your configuration in <code>admin/config/config.local.php</code>.</div></div>';
    require_once ADMIN_PATH . '/templates/footer.php';
    exit;
}

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

// Post types for Quick Actions (create specific post type items)
$quickPostTypes = [];
try {
    $stmt = $db->query("
        SELECT id, name
        FROM post_types
        WHERE deleted_at IS NULL
        ORDER BY name
    ");
    $quickPostTypes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Dashboard post types error: " . $e->getMessage());
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container dashboard-container">
    <div class="page-header">
        <h1>Dashboard</h1>
    </div>

    <div class="dashboard-columns">
        <div class="dashboard-column dashboard-drop-column" data-dashboard-column="stats">
            <div class="dashboard-column-inner">
                <div class="card dashboard-stat-card dashboard-card-draggable" data-dashboard-card="summary">
                    <div class="card-header dashboard-card-header">
                        <span class="dashboard-card-title">Summary</span>
                        <button type="button" class="dashboard-card-collapse-btn" aria-label="Collapse" title="Collapse"><i class="fas fa-chevron-up"></i></button>
                    </div>
                    <div class="card-body dashboard-card-body">
                        <ul class="dashboard-summary-list">
                            <li>
                                <span class="dashboard-summary-label">Total posts</span>
                                <span class="dashboard-summary-value"><?php echo (int) $stats['total_posts']; ?></span>
                            </li>
                            <li>
                                <span class="dashboard-summary-label">Published</span>
                                <span class="dashboard-summary-value"><?php echo (int) $stats['published_posts']; ?></span>
                            </li>
                            <li>
                                <span class="dashboard-summary-label">Drafts</span>
                                <span class="dashboard-summary-value"><?php echo (int) $stats['draft_posts']; ?></span>
                            </li>
                            <li>
                                <span class="dashboard-summary-label">Users</span>
                                <span class="dashboard-summary-value"><?php echo (int) $stats['total_users']; ?></span>
                            </li>
                            <li>
                                <span class="dashboard-summary-label">Media files</span>
                                <span class="dashboard-summary-value"><?php echo (int) $stats['total_media']; ?></span>
                            </li>
                            <li>
                                <span class="dashboard-summary-label">Post types</span>
                                <span class="dashboard-summary-value"><?php echo (int) $stats['total_post_types']; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-column dashboard-drop-column" data-dashboard-column="actions">
            <div class="dashboard-column-inner">
                    <div class="card dashboard-actions-card dashboard-card-draggable" data-dashboard-card="quick-actions">
                    <div class="card-header dashboard-card-header">
                        <span class="dashboard-card-title">Quick Actions</span>
                        <button type="button" class="dashboard-card-collapse-btn" aria-label="Collapse" title="Collapse"><i class="fas fa-chevron-up"></i></button>
                    </div>
                    <div class="card-body dashboard-card-body">
                        <p class="dashboard-quick-intro">
                            Start writing content for a specific post type, or manage media and users.
                        </p>
                        <div class="quick-actions">
                            <?php if (!empty($quickPostTypes)): ?>
                                <?php foreach ($quickPostTypes as $type): ?>
                                    <?php
                                    $name = $type['name'] ?? '';
                                    $label = trim($name) !== '' ? 'Add ' . $name . ' item' : 'Add item';
                                    ?>
                                    <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/posts/edit.php?post_type_id=<?php echo (int) $type['id']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($label); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/media/upload.php" class="btn btn-secondary"><i class="fas fa-upload"></i> Upload Media</a>
                            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/users/edit.php" class="btn btn-secondary"><i class="fas fa-user-plus"></i> Add User</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-column dashboard-drop-column" data-dashboard-column="recent">
            <div class="dashboard-column-inner">
                <div class="card dashboard-recent-card dashboard-card-draggable" data-dashboard-card="recent-posts">
                    <div class="card-header dashboard-card-header">
                        <span class="dashboard-card-title">Recent Posts</span>
                        <button type="button" class="dashboard-card-collapse-btn" aria-label="Collapse" title="Collapse"><i class="fas fa-chevron-up"></i></button>
                    </div>
                    <div class="card-body dashboard-card-body">
                        <?php if (empty($recentPosts)): ?>
                            <div class="dashboard-empty-state">
                                <p class="text-muted">No posts yet.</p>
                            </div>
                        <?php else: ?>
                            <ul class="dashboard-recent-list">
                                <?php foreach ($recentPosts as $post): ?>
                                    <li class="dashboard-recent-item">
                                        <div class="dashboard-recent-item-header">
                                            <span class="dashboard-recent-item-title"><?php echo htmlspecialchars($post['title'] ?? 'Untitled'); ?></span>
                                            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/posts/edit.php?id=<?php echo (int) $post['id']; ?>" class="btn btn-sm btn-secondary dashboard-recent-edit" aria-label="Edit post"><i class="fas fa-edit"></i></a>
                                        </div>
                                        <div class="dashboard-recent-item-meta text-muted">
                                            <?php echo htmlspecialchars($post['post_type_name'] ?? ''); ?> • <?php echo date('M j, Y', strtotime($post['created_at'] ?? 'now')); ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/posts" class="btn btn-secondary dashboard-view-all">View All Posts</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    'use strict';
    document.querySelectorAll('.dashboard-card-collapse-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var card = this.closest('.card');
            if (!card) return;
            var body = card.querySelector('.dashboard-card-body');
            var icon = this.querySelector('i');
            if (card.classList.contains('dashboard-card-collapsed')) {
                card.classList.remove('dashboard-card-collapsed');
                if (body) body.style.display = '';
                if (icon) { icon.className = 'fas fa-chevron-up'; }
                this.setAttribute('aria-label', 'Collapse');
                this.setAttribute('title', 'Collapse');
            } else {
                card.classList.add('dashboard-card-collapsed');
                if (body) body.style.display = 'none';
                if (icon) { icon.className = 'fas fa-chevron-down'; }
                this.setAttribute('aria-label', 'Expand');
                this.setAttribute('title', 'Expand');
            }
        });
    });
    if (window.ModularSortable && document.querySelector('.dashboard-column-inner')) {
        document.querySelectorAll('.dashboard-column-inner').forEach(function (col) {
            if (col._dashboardSortable) return;
            col._dashboardSortable = window.ModularSortable.create(col, {
                group: 'dashboard-cards',
                animation: 150,
                handle: '.dashboard-card-header',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen'
            });
        });
    }
})();
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
