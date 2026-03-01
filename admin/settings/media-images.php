<?php
/**
 * Media Image Settings
 * Configure thumbnail sizes, formats, and regeneration actions.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/media-settings.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$page_title = 'Media Image Settings';
$settings_active = true;

$db = $GLOBALS['db'];
$message = '';
$messageType = 'success';

$current = media_get_image_settings();
if (empty($current)) {
    $current = [
        'sizes' => [
            'thumb'  => ['width' => 150,  'height' => 150,  'crop' => true],
            'medium' => ['width' => 768,  'height' => 0,    'crop' => false],
            'large'  => ['width' => 1200, 'height' => 0,    'crop' => false],
        ],
        'format' => 'original',
        'quality' => 85,
        'keep_original' => true,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('csrf_verify') || !csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? 'save';
        if ($action === 'save') {
            $sizes = [];
            $names = $_POST['size_name'] ?? [];
            $widths = $_POST['size_width'] ?? [];
            $heights = $_POST['size_height'] ?? [];
            $crops = $_POST['size_crop'] ?? [];
            foreach ($names as $idx => $name) {
                $name = trim((string)$name);
                if ($name === '') continue;
                $w = (int)($widths[$idx] ?? 0);
                $h = (int)($heights[$idx] ?? 0);
                $crop = isset($crops[$idx]) && $crops[$idx] === '1';
                $sizes[$name] = ['width' => max(0, $w), 'height' => max(0, $h), 'crop' => $crop];
            }
            $format = $_POST['format'] ?? 'original';
            if (!in_array($format, ['original', 'jpg', 'png', 'webp'], true)) {
                $format = 'original';
            }
            $quality = max(10, min(100, (int)($_POST['quality'] ?? 85)));
            $keepOriginal = !empty($_POST['keep_original']);

            $save = [
                'sizes' => $sizes,
                'format' => $format,
                'quality' => $quality,
                'keep_original' => $keepOriginal,
            ];
            if (media_save_image_settings($save)) {
                $message = 'Media image settings saved.';
                $messageType = 'success';
                $current = $save;
            } else {
                $message = 'Failed to save settings.';
                $messageType = 'error';
            }
        } elseif ($action === 'regenerate_thumbnails') {
            // Basic in-request regeneration (small sites). For large installs, move to background job.
            try {
                $stmt = $db->query("SELECT id FROM media WHERE file_type = 'image' AND deleted_at IS NULL");
                $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $controller = new \Modular\API\MediaController($db);
                foreach ($ids as $mid) {
                    $mid = (int)$mid;
                    if ($mid <= 0) continue;
                    // Regenerate by re-reading file and updating variants.
                    $media = $controller->show($mid);
                    if (isset($media['error']) || empty($media['file_path'])) continue;
                    $path = $media['file_path'];
                    // Convert relative web path to filesystem path
                    $root = defined('PUBLIC_PATH') ? PUBLIC_PATH : dirname(dirname(__DIR__)) . '/public';
                    $sourcePath = $root . preg_replace('#^/public#', '', $path);
                    if (!is_file($sourcePath)) continue;
                    $variants = (new \ReflectionClass($controller))->getMethod('generateImageVariants');
                    $variants->setAccessible(true);
                    $generated = $variants->invoke($controller, $sourcePath, $media['filename']);
                    $update = $db->prepare("UPDATE media SET variants = ?, updated_at = NOW() WHERE id = ?");
                    $update->execute([json_encode($generated), $mid]);
                }
                $message = 'Thumbnails regenerated for all images.';
                $messageType = 'success';
            } catch (\Throwable $e) {
                $message = 'Error while regenerating thumbnails: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>Media Image Settings</h1>
        <p>Configure thumbnail sizes, compression and formats used for uploaded images.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                <input type="hidden" name="action" value="save">

                <h3>Thumbnail Sizes</h3>
                <p class="help-text">Configure the image sizes that will be generated for uploads. Width/height 0 means \"auto\" based on aspect ratio.</p>

                <table class="table" style="max-width: 640px;">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Width (px)</th>
                            <th>Height (px)</th>
                            <th>Crop</th>
                        </tr>
                    </thead>
                    <tbody id="media-size-rows">
                        <?php foreach ($current['sizes'] as $name => $cfg): ?>
                        <tr>
                            <td><input type="text" name="size_name[]" class="form-control" value="<?php echo htmlspecialchars($name); ?>"></td>
                            <td><input type="number" name="size_width[]" class="form-control" value="<?php echo (int)($cfg['width'] ?? 0); ?>"></td>
                            <td><input type="number" name="size_height[]" class="form-control" value="<?php echo (int)($cfg['height'] ?? 0); ?>"></td>
                            <td style="text-align:center;">
                                <input type="checkbox" name="size_crop[]" value="1" <?php echo !empty($cfg['crop']) ? 'checked' : ''; ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><input type="text" name="size_name[]" class="form-control" placeholder="new_size"></td>
                            <td><input type="number" name="size_width[]" class="form-control" placeholder="e.g. 400"></td>
                            <td><input type="number" name="size_height[]" class="form-control" placeholder="e.g. 300"></td>
                            <td style="text-align:center;">
                                <input type="checkbox" name="size_crop[]" value="1">
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h3>Output Format & Compression</h3>
                <div class="form-group">
                    <label for="format">Output format</label>
                    <select id="format" name="format" class="form-control" style="max-width: 240px;">
                        <?php
                        $fmt = $current['format'] ?? 'original';
                        $opts = [
                            'original' => 'Keep original format',
                            'jpg' => 'JPEG (photos)',
                            'png' => 'PNG (transparency)',
                            'webp' => 'WebP (modern)',
                        ];
                        foreach ($opts as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo $fmt === $val ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="max-width: 240px;">
                    <label for="quality">Quality (for JPEG/WebP)</label>
                    <input type="number" id="quality" name="quality" class="form-control" min="10" max="100" value="<?php echo (int)($current['quality'] ?? 85); ?>">
                    <div class="help-text">Higher quality increases file size. Recommended: 75–90.</div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="keep_original" value="1" <?php echo !empty($current['keep_original']) ? 'checked' : ''; ?>>
                        Keep original image file
                    </label>
                    <div class="help-text">Uncheck to allow manual deletion of originals after generating thumbnails.</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top: var(--spacing-lg);">
        <div class="card-body">
            <h3>Maintenance</h3>
            <p class="help-text">Use these tools carefully on larger sites; regenerating thumbnails processes all image files.</p>

            <form method="POST">
                <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                <input type="hidden" name="action" value="regenerate_thumbnails">
                <button type="submit" class="btn btn-secondary" data-confirm="Miniaturen voor alle afbeeldingen opnieuw genereren? Dit kan even duren.">
                    <i class="fas fa-redo"></i> Regenerate all thumbnails
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>

