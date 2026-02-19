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

// Filters: search + folder
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$folder = isset($_GET['folder']) ? trim((string)$_GET['folder']) : '';

// Get media files
$media = [];
$message = '';
try {
    $where = 'm.deleted_at IS NULL';
    $params = [];
    if ($q !== '') {
        $where .= " AND (
            m.title       LIKE :q
            OR m.caption  LIKE :q
            OR m.description LIKE :q
            OR m.alt_text LIKE :q
            OR m.filename LIKE :q
            OR m.metadata LIKE :q
            OR m.tags     LIKE :q
        )";
        $params[':q'] = '%' . $q . '%';
    }
    if ($folder !== '') {
        $where .= " AND m.folder_path = :folder";
        $params[':folder'] = $folder;
    }

    $sql = "
        SELECT m.*, COALESCE(u.username, u.email, 'Unknown') as uploaded_by_name
        FROM media m
        LEFT JOIN users u ON m.uploaded_by = u.id
        WHERE {$where}
        ORDER BY m.created_at DESC
        LIMIT 100
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $media = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Error loading media: " . $e->getMessage();
    error_log($message);
}

// Get distinct folders for filter dropdown
$folders = [];
try {
    $folderStmt = $db->query("
        SELECT DISTINCT folder_path 
        FROM media 
        WHERE deleted_at IS NULL AND folder_path IS NOT NULL AND folder_path <> ''
        ORDER BY folder_path
    ");
    $folders = $folderStmt->fetchAll(\PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Non-fatal; ignore
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
        <form method="get" class="form-group" style="display:flex; gap:0.75rem; align-items:flex-end; margin-bottom:var(--spacing-md);">
            <div style="flex:1;">
                <label for="media-search" style="display:block; font-size:0.85rem; margin-bottom:0.25rem;">Search</label>
                <input type="search" id="media-search" name="q" class="form-control" placeholder="Search media…" value="<?php echo htmlspecialchars($q); ?>">
            </div>
            <div>
                <label for="media-folder" style="display:block; font-size:0.85rem; margin-bottom:0.25rem;">Folder</label>
                <select id="media-folder" name="folder" class="form-control">
                    <option value="">All folders</option>
                    <?php foreach ($folders as $f): ?>
                        <option value="<?php echo htmlspecialchars($f); ?>" <?php echo $folder === $f ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-secondary btn-inline" style="margin-top: 0.25rem;">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>
        </form>

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
                        <?php
                        $variants = [];
                        if (!empty($item['variants'])) {
                            $decoded = json_decode($item['variants'], true);
                            if (is_array($decoded)) $variants = $decoded;
                        }
                        $thumbPath = $item['file_path'];
                        if (isset($variants['thumb']['path'])) {
                            $thumbPath = $variants['thumb']['path'];
                        }
                        $mime = $item['mime_type'] ?? '';
                        $iconClass = 'fas fa-file';
                        if ($item['file_type'] !== 'image') {
                            if (strpos($mime, 'pdf') !== false) $iconClass = 'fas fa-file-pdf';
                            elseif (strpos($mime, 'audio') !== false) $iconClass = 'fas fa-file-audio';
                            elseif (strpos($mime, 'video') !== false) $iconClass = 'fas fa-file-video';
                            elseif (strpos($mime, 'word') !== false || strpos($mime, 'msword') !== false || strpos($mime, 'officedocument.word') !== false) $iconClass = 'fas fa-file-word';
                            elseif (strpos($mime, 'spreadsheet') !== false || strpos($mime, 'excel') !== false) $iconClass = 'fas fa-file-excel';
                            elseif (strpos($mime, 'zip') !== false || strpos($mime, 'compressed') !== false || strpos($mime, 'rar') !== false) $iconClass = 'fas fa-file-archive';
                        }
                        ?>
                        <?php if ($item['file_type'] === 'image'): ?>
                            <img src="<?php echo htmlspecialchars($thumbPath); ?>" 
                                 alt="<?php echo htmlspecialchars($item['alt_text'] ?? $item['title'] ?? ''); ?>"
                                 style="width: 100%; height: 150px; object-fit: cover; border-radius: var(--radius-sm); margin-bottom: var(--spacing-sm);">
                        <?php else: ?>
                            <div style="width: 100%; height: 150px; background: var(--background-light); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; margin-bottom: var(--spacing-sm);">
                                <i class="<?php echo htmlspecialchars($iconClass); ?>" style="font-size: 3rem; color: var(--text-color-light);"></i>
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
