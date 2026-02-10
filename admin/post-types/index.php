<?php
/**
 * Post Types Index - Japanese Style
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
require_once CORE_PATH . '/classes/PostType.php';

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$page_title = 'Post Types';
$post_types_active = true;

// Check if database tables exist
require_once CORE_PATH . '/functions/database.php';
$tableCheck = check_database_tables();

if (!$tableCheck['all_exist']) {
    $missingCount = count($tableCheck['missing']);
    $message = "Database tables are missing. Please install the database first.";
    if (in_array('post_types', $tableCheck['missing'])) {
        $message = "The database tables have not been created yet. Please install the database to continue.";
    }
    $postTypes = [];
} else {
    $postType = new PostType();
    $message = '';
    
    try {
        $postTypes = $postType->getAll();
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $postTypes = [];
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1>Post Types</h1>
            <a href="/modular/admin/post-types/edit.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Post Type
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-error" style="margin-bottom: var(--spacing-lg);">
            <div style="display: flex; align-items: start; gap: var(--spacing-md);">
                <div style="flex: 1;">
                    <strong>Database Setup Required</strong>
                    <p style="margin: var(--spacing-sm) 0 0 0;"><?php echo htmlspecialchars($message); ?></p>
                    <?php if (!$tableCheck['all_exist']): ?>
                        <p style="margin: var(--spacing-sm) 0 0 0; font-size: 0.875rem;">
                            <strong><?php echo count($tableCheck['missing']); ?> table(s) missing:</strong> 
                            <?php echo htmlspecialchars(implode(', ', array_slice($tableCheck['missing'], 0, 5))); ?>
                            <?php if (count($tableCheck['missing']) > 5): ?>
                                and <?php echo count($tableCheck['missing']) - 5; ?> more...
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="/modular/admin/install.php" class="btn btn-primary" style="white-space: nowrap;">
                        <i class="fas fa-database"></i> Install Database
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <?php if (empty($postTypes)): ?>
            <div class="empty-state">
                <i class="fas fa-cubes"></i>
                <h3>No Post Types Found</h3>
                <p>Create your first post type to get started.</p>
                <a href="/modular/admin/post-types/edit.php" class="btn btn-primary mt-4">
                    <i class="fas fa-plus"></i> Create Post Type
                </a>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Public</th>
                        <th>In Menu</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($postTypes as $type): ?>
                        <?php
                        $slug = $type['slug'] ?? $type['nicename'] ?? '';
                        $is_public = isset($type['public']) ? (bool)$type['public'] : (isset($type['is_public']) ? (bool)$type['is_public'] : true);
                        $show_in_menu = isset($type['show_in_menu']) ? (bool)$type['show_in_menu'] : true;
                        $description = $type['description'] ?? '';
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($type['name']); ?></strong></td>
                            <td><code style="background: var(--background-subtle); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 13px;"><?php echo htmlspecialchars($slug); ?></code></td>
                            <td><?php echo htmlspecialchars(substr($description, 0, 60)); ?><?php echo strlen($description) > 60 ? '...' : ''; ?></td>
                            <td>
                                <?php if ($is_public): ?>
                                    <span class="badge badge-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($show_in_menu): ?>
                                    <span class="badge badge-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="flex gap-2 justify-end">
                                    <a href="/modular/admin/post-types/edit.php?id=<?php echo $type['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="/modular/admin/post-types/post-types-delete.php?id=<?php echo $type['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       data-confirm="Are you sure you want to delete this post type?">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
