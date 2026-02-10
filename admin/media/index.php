<?php
/**
 * Media Library Index - Japanese Style
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

$page_title = 'Media Library';
$media_active = true;

$db = $GLOBALS['db'];

// Get media files
$media = [];
$message = '';
try {
    $stmt = $db->query("
        SELECT m.*, COALESCE(u.username, u.email, 'Unknown') as uploaded_by_name
        FROM media m
        LEFT JOIN users u ON m.uploaded_by = u.id
        WHERE m.deleted_at IS NULL
        ORDER BY m.created_at DESC
        LIMIT 100
    ");
    $media = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Error loading media: " . $e->getMessage();
    error_log($message);
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1>Media Library</h1>
            <a href="/modular/admin/media/upload.php" class="btn btn-primary">
                <i class="fas fa-upload"></i> Upload Media
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <?php if (empty($media)): ?>
            <div class="empty-state">
                <i class="fas fa-images"></i>
                <h3>No Media Files</h3>
                <p>Upload your first media file to get started.</p>
                <a href="/modular/admin/media/upload.php" class="btn btn-primary mt-4">
                    <i class="fas fa-upload"></i> Upload Media
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 grid-cols-3 grid-cols-4" style="gap: var(--spacing-md);">
                <?php foreach ($media as $item): ?>
                    <div style="background: var(--background-subtle); border-radius: var(--radius-md); padding: var(--spacing-md); border: 1px solid var(--border-color);">
                        <?php if ($item['file_type'] === 'image'): ?>
                            <img src="<?php echo htmlspecialchars($item['file_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['alt_text'] ?? $item['title'] ?? ''); ?>"
                                 style="width: 100%; height: 150px; object-fit: cover; border-radius: var(--radius-sm); margin-bottom: var(--spacing-sm);">
                        <?php else: ?>
                            <div style="width: 100%; height: 150px; background: var(--background-light); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; margin-bottom: var(--spacing-sm);">
                                <i class="fas fa-file" style="font-size: 3rem; color: var(--text-color-light);"></i>
                            </div>
                        <?php endif; ?>
                        <div style="font-size: 13px; color: var(--text-color); margin-bottom: 0.25rem;">
                            <strong><?php echo htmlspecialchars($item['title'] ?? $item['filename']); ?></strong>
                        </div>
                        <div style="font-size: 12px; color: var(--text-color-light); margin-bottom: var(--spacing-sm);">
                            <?php echo number_format($item['file_size'] / 1024, 1); ?> KB
                        </div>
                        <div class="flex gap-2">
                            <a href="/modular/admin/media/edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary" style="flex: 1;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="/modular/admin/media/delete.php?id=<?php echo $item['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               data-confirm="Are you sure?">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
