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
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
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
            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/media/upload.php" class="btn btn-primary">
                <i class="fas fa-upload"></i> Upload Media
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="media-toolbar">
        <form method="get" class="media-toolbar-form">
            <input type="search" id="media-search" name="q" class="media-toolbar-input media-toolbar-search" placeholder="Search media…" value="<?php echo htmlspecialchars($q); ?>">
            <select id="media-folder" name="folder" class="media-toolbar-input media-toolbar-folder">
                <option value="">All folders</option>
                <?php foreach ($folders as $f): ?>
                    <option value="<?php echo htmlspecialchars($f); ?>" <?php echo $folder === $f ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($f); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary media-toolbar-btn">
                <i class="fas fa-search"></i> Filter
            </button>
        </form>
        <button type="button" class="btn btn-primary media-toolbar-btn" id="media-create-folder-btn" aria-label="Create folder">
            <i class="fas fa-folder-plus"></i> Create folder
        </button>
    </div>

    <?php if ($folder !== ''): ?>
    <div class="media-folder-bar">
        <span class="media-folder-label">In folder:</span>
        <strong class="media-folder-current"><?php echo htmlspecialchars($folder); ?></strong>
        <a href="<?php echo $q !== '' ? '?q=' . rawurlencode($q) : '?'; ?>" class="media-folder-back">All folders</a>
    </div>
    <?php endif; ?>

    <div class="card">
        <?php if (empty($media)): ?>
            <div class="empty-state">
                <i class="fas fa-images"></i>
                <?php if ($folder !== ''): ?>
                <h3>This folder is empty</h3>
                <p>Upload a file to add content to this folder.</p>
                <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/media/upload.php?folder_path=<?php echo rawurlencode($folder); ?>" class="btn btn-primary mt-4">
                    <i class="fas fa-upload"></i> Upload here
                </a>
                <a href="<?php echo ADMIN_URL; ?>/media" class="btn btn-secondary mt-4">All folders</a>
                <?php else: ?>
                <h3>No Media Files</h3>
                <p>Upload your first media file to get started.</p>
                <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/media/upload.php" class="btn btn-primary mt-4">
                    <i class="fas fa-upload"></i> Upload Media
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid media-grid" style="gap: var(--spacing-md);">
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
                        <div class="media-item-actions">
                            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/media/edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/media/delete.php?id=<?php echo $item['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               data-confirm="Are you sure you want to delete this file?">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="media-create-folder-modal" class="modular-modal-overlay" style="display: none;" aria-hidden="true">
    <div class="modular-modal-dialog">
        <div class="modular-modal-header">Create folder</div>
        <div class="modular-modal-body">
            <p class="help-text" style="margin-top: 0;">Enter a folder path (e.g. <code>blog/2025</code> or <code>assets/icons</code>). You will be taken to that folder; it will appear in the list once you upload files to it.</p>
            <label for="media-new-folder-path" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">Folder path</label>
            <input type="text" id="media-new-folder-path" class="media-toolbar-input" style="width: 100%; max-width: 320px;" placeholder="e.g. blog/2025" autocomplete="off">
        </div>
        <div class="modular-modal-footer">
            <button type="button" class="btn btn-secondary" id="media-create-folder-cancel">Cancel</button>
            <button type="button" class="btn btn-primary" id="media-create-folder-submit">Create folder</button>
        </div>
    </div>
</div>

<script>
(function() {
    var btn = document.getElementById('media-create-folder-btn');
    var modal = document.getElementById('media-create-folder-modal');
    var input = document.getElementById('media-new-folder-path');
    var cancel = document.getElementById('media-create-folder-cancel');
    var submit = document.getElementById('media-create-folder-submit');
    if (!btn || !modal || !input) return;
    function openModal() {
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        input.value = '';
        input.focus();
    }
    function closeModal() {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }
    btn.addEventListener('click', openModal);
    cancel.addEventListener('click', closeModal);
    submit.addEventListener('click', function() {
        var path = (input.value || '').trim().replace(/^\/+|\/+$/g, '');
        if (!path) return;
        var params = new URLSearchParams(window.location.search);
        params.set('folder', path);
        window.location.href = (window.location.pathname || '') + '?' + params.toString();
    });
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
})();
</script>

<style>
/* Media toolbar: one row, inputs + Filter button same height, never stack */
.media-toolbar {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--background-subtle);
    border-radius: var(--radius-md);
}
form.media-toolbar-form {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center;
    justify-content: flex-start;
    gap: 0.5rem;
    flex: 1 1 auto;
    min-width: 0;
}
form.media-toolbar-form > * {
    flex: 0 0 auto;
}
/* PrimeVue-style compact inputs: same height as button */
.media-toolbar-input {
    display: inline-block;
    height: 34px;
    padding: 0 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--text-color, #374151);
    background: var(--background-light, #fff);
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 6px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    box-sizing: border-box;
}
.media-toolbar-input:focus {
    outline: none;
    border-color: var(--btn-default-bg, #71C1D2);
    box-shadow: 0 0 0 2px rgba(113, 193, 210, 0.2);
}
.media-toolbar-input::placeholder {
    color: var(--text-color-lighter, #9ca3af);
}
.media-toolbar-search {
    width: 250px;
    min-width: 250px;
}
.media-toolbar-folder {
    width: auto;
    min-width: 120px;
}
.media-toolbar-btn {
    height: 34px;
    padding: 0 1rem;
    font-size: 0.875rem;
    line-height: 32px;
    flex-shrink: 0;
}
#media-create-folder-btn {
    margin-left: auto;
}
.media-folder-bar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    color: var(--text-color-light);
}
.media-folder-label { color: var(--text-color-light); }
.media-folder-current { color: var(--text-color); }
.media-folder-back { margin-left: 0.5rem; }
.media-item-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.media-item-actions .btn { width: auto; }
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: var(--spacing-md);
}
</style>
<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
