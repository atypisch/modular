<?php
/**
 * Media Edit Page - Japanese Style
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

$page_title = 'Edit Media';
$media_active = true;

$db = $GLOBALS['db'];
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$id) {
    header('Location: /modular/admin/media');
    exit;
}

// Load media
$media = null;
try {
    $stmt = $db->prepare("SELECT * FROM media WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    $media = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if (!$media) {
        $_SESSION['error'] = 'Media not found.';
        header('Location: /modular/admin/media');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Error loading media: ' . $e->getMessage();
    header('Location: /modular/admin/media');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $_SESSION['error'] = 'Invalid request. Please try again.';
        header('Location: /modular/admin/media/edit.php?id=' . (int)$id);
        exit;
    }
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING) ?? '';
    $altText = filter_input(INPUT_POST, 'alt_text', FILTER_SANITIZE_STRING) ?? '';
    $folderPath = filter_input(INPUT_POST, 'folder_path', FILTER_SANITIZE_STRING) ?? '';
    $caption = filter_input(INPUT_POST, 'caption', FILTER_SANITIZE_STRING) ?? '';
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING) ?? '';
    
    try {
        $stmt = $db->prepare("
            UPDATE media 
            SET title = ?, alt_text = ?, folder_path = ?, caption = ?, description = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $altText, $folderPath ?: null, $caption, $description, $id]);
        
        $_SESSION['success'] = 'Media updated successfully.';
        header('Location: /modular/admin/media');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error updating media: ' . $e->getMessage();
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1>Edit Media</h1>
            <a href="/modular/admin/media" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Media Library
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-2" style="gap: var(--spacing-xl);">
        <div class="card">
            <?php
            $variants = [];
            if (!empty($media['variants'])) {
                $decoded = json_decode($media['variants'], true);
                if (is_array($decoded)) $variants = $decoded;
            }
            $previewPath = $media['file_path'];
            if (isset($variants['medium']['path'])) {
                $previewPath = $variants['medium']['path'];
            } elseif (isset($variants['thumb']['path'])) {
                $previewPath = $variants['thumb']['path'];
            }
            ?>
            <?php if ($media['file_type'] === 'image'): ?>
                <img src="<?php echo htmlspecialchars($previewPath); ?>" 
                     alt="<?php echo htmlspecialchars($media['alt_text'] ?? ''); ?>"
                     style="width: 100%; border-radius: var(--radius-md); margin-bottom: var(--spacing-md);">
            <?php else: ?>
                <div style="width: 100%; height: 300px; background: var(--background-subtle); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; margin-bottom: var(--spacing-md);">
                    <i class="fas fa-file" style="font-size: 4rem; color: var(--text-color-light);"></i>
                </div>
            <?php endif; ?>
            
            <div class="text-muted" style="font-size: 13px;">
                <div><strong>Filename:</strong> <?php echo htmlspecialchars($media['filename']); ?></div>
                <div><strong>Size:</strong> <?php echo number_format($media['file_size'] / 1024, 1); ?> KB</div>
                <div><strong>Type:</strong> <?php echo htmlspecialchars($media['mime_type']); ?></div>
                <?php if ($media['width'] && $media['height']): ?>
                    <div><strong>Dimensions:</strong> <?php echo $media['width']; ?> × <?php echo $media['height']; ?> px</div>
                <?php endif; ?>
                <?php if (!empty($media['folder_path'])): ?>
                    <div><strong>Folder:</strong> <?php echo htmlspecialchars($media['folder_path']); ?></div>
                <?php endif; ?>
            </div>

            <?php if ($media['file_type'] === 'image' && !empty($variants)): ?>
                <hr class="section-divider">
                <h3 style="margin-top:0;">Image Variants</h3>
                <p class="help-text">Crop thumbnails for specific sizes. Enter crop rectangle in pixels based on the original image.</p>
                <?php foreach ($variants as $vName => $vData): ?>
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label><?php echo htmlspecialchars($vName); ?> (<?php echo (int)($vData['width'] ?? 0); ?> × <?php echo (int)($vData['height'] ?? 0); ?>)</label>
                        <div style="display:flex;gap:0.5rem;align-items:flex-end;flex-wrap:wrap;">
                            <div style="display:flex;gap:0.25rem;align-items:flex-end;">
                                <input type="number" class="form-control form-control-sm" style="width:5rem;" placeholder="x" data-crop-x="<?php echo htmlspecialchars($vName); ?>">
                                <input type="number" class="form-control form-control-sm" style="width:5rem;" placeholder="y" data-crop-y="<?php echo htmlspecialchars($vName); ?>">
                                <input type="number" class="form-control form-control-sm" style="width:5rem;" placeholder="w" data-crop-w="<?php echo htmlspecialchars($vName); ?>">
                                <input type="number" class="form-control form-control-sm" style="width:5rem;" placeholder="h" data-crop-h="<?php echo htmlspecialchars($vName); ?>">
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" data-crop-btn="<?php echo htmlspecialchars($vName); ?>">
                                <i class="fas fa-crop"></i> Crop &amp; Save
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <script>
                (function() {
                    var buttons = document.querySelectorAll('[data-crop-btn]');
                    buttons.forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var variant = btn.getAttribute('data-crop-btn');
                            var x = document.querySelector('[data-crop-x=\"' + variant + '\"]');
                            var y = document.querySelector('[data-crop-y=\"' + variant + '\"]');
                            var w = document.querySelector('[data-crop-w=\"' + variant + '\"]');
                            var h = document.querySelector('[data-crop-h=\"' + variant + '\"]');
                            var xv = parseInt(x && x.value || '0', 10);
                            var yv = parseInt(y && y.value || '0', 10);
                            var wv = parseInt(w && w.value || '0', 10);
                            var hv = parseInt(h && h.value || '0', 10);
                            if (!wv || !hv) {
                                (window.ModularModal && window.ModularModal.alert('Vul breedte en hoogte in voor het bijsnijden.')) || alert('Vul breedte en hoogte in voor het bijsnijden.');
                                return;
                            }
                            var formData = new FormData();
                            formData.append('id', '<?php echo (int)$media['id']; ?>');
                            formData.append('variant', variant);
                            formData.append('x', isNaN(xv) ? 0 : xv);
                            formData.append('y', isNaN(yv) ? 0 : yv);
                            formData.append('w', wv);
                            formData.append('h', hv);
                            formData.append('<?php echo htmlspecialchars(csrf_token_name() ?? "_csrf_token"); ?>', '<?php echo htmlspecialchars(csrf_token() ?? ""); ?>');

                            fetch('/modular/admin/media/crop.php', {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin'
                            }).then(function(res) { return res.text(); }).then(function(text) {
                                if (text.trim() === 'ok') {
                                    location.reload();
                                } else {
                                    (window.ModularModal && window.ModularModal.alert('Bijsnijden mislukt: ' + text)) || alert('Bijsnijden mislukt: ' + text);
                                }
                            }).catch(function(err) {
                                (window.ModularModal && window.ModularModal.alert('Bijsnijden mislukt. Controleer de console.')) || alert('Bijsnijden mislukt. Controleer de console.');
                                console.error(err);
                            });
                        });
                    });
                })();
                </script>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" class="form-control" 
                           value="<?php echo htmlspecialchars($media['title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="alt_text">Alt Text</label>
                    <input type="text" name="alt_text" id="alt_text" class="form-control" 
                           value="<?php echo htmlspecialchars($media['alt_text'] ?? ''); ?>">
                    <div class="help-text">Alternative text for accessibility (for images).</div>
                </div>
                
                <div class="form-group">
                    <label for="folder_path">Folder</label>
                    <input type="text" name="folder_path" id="folder_path" class="form-control" 
                           value="<?php echo htmlspecialchars($media['folder_path'] ?? ''); ?>">
                    <div class="help-text">Optional folder path to organise this media item.</div>
                </div>
                
                <div class="form-group">
                    <label for="caption">Caption</label>
                    <input type="text" name="caption" id="caption" class="form-control" 
                           value="<?php echo htmlspecialchars($media['caption'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4"><?php echo htmlspecialchars($media['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <a href="/modular/admin/media" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
