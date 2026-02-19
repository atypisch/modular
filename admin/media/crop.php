<?php
/**
 * Media image crop endpoint.
 * Expects: POST id, variant, x, y, width, height (all in source image pixels).
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
    http_response_code(403);
    echo 'Not authorized';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !function_exists('csrf_verify') || !csrf_verify()) {
    http_response_code(400);
    echo 'Invalid request';
    exit;
}

$db = $GLOBALS['db'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$variant = isset($_POST['variant']) ? preg_replace('/[^a-z0-9_\-]/i', '', $_POST['variant']) : '';
$x = isset($_POST['x']) ? (int)$_POST['x'] : 0;
$y = isset($_POST['y']) ? (int)$_POST['y'] : 0;
$w = isset($_POST['w']) ? (int)$_POST['w'] : 0;
$h = isset($_POST['h']) ? (int)$_POST['h'] : 0;

if ($id <= 0 || $variant === '' || $w <= 0 || $h <= 0) {
    http_response_code(400);
    echo 'Missing or invalid parameters';
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM media WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$media || $media['file_type'] !== 'image') {
        http_response_code(404);
        echo 'Image not found';
        exit;
    }

    // Determine source file path
    $relative = $media['file_path'];
    $root = defined('PUBLIC_PATH') ? PUBLIC_PATH : dirname(dirname(__DIR__)) . '/public';
    $sourcePath = $root . preg_replace('#^/public#', '', $relative);
    if (!is_file($sourcePath)) {
        http_response_code(404);
        echo 'Source file missing';
        exit;
    }

    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
        http_response_code(400);
        echo 'Invalid image';
        exit;
    }
    $mime = $imageInfo['mime'];

    switch ($mime) {
        case 'image/jpeg':
            $src = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $src = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $src = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $src = imagecreatefromwebp($sourcePath);
            break;
        default:
            http_response_code(400);
            echo 'Unsupported image type';
            exit;
    }
    if (!$src) {
        http_response_code(400);
        echo 'Failed to open image';
        exit;
    }

    // Load settings for target size
    if (!function_exists('media_get_image_settings')) {
        require_once CORE_PATH . '/functions/media-settings.php';
    }
    $settings = media_get_image_settings();
    $sizes = $settings['sizes'] ?? [];
    if (!isset($sizes[$variant])) {
        // Fallback: use crop box size as final size
        $targetW = $w;
        $targetH = $h;
    } else {
        $cfg = $sizes[$variant];
        $targetW = (int)($cfg['width'] ?? $w);
        $targetH = (int)($cfg['height'] ?? $h);
        if ($targetW <= 0) $targetW = $w;
        if ($targetH <= 0) $targetH = $h;
    }

    // Clamp crop rectangle within source
    $srcW = imagesx($src);
    $srcH = imagesy($src);
    if ($x < 0) $x = 0;
    if ($y < 0) $y = 0;
    if ($x + $w > $srcW) $w = $srcW - $x;
    if ($y + $h > $srcH) $h = $srcH - $y;

    $dst = imagecreatetruecolor($targetW, $targetH);
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);
    }

    imagecopyresampled($dst, $src, 0, 0, $x, $y, $targetW, $targetH, $w, $h);

    $extension = pathinfo($media['filename'], PATHINFO_EXTENSION);
    $baseName = pathinfo($media['filename'], PATHINFO_FILENAME);
    $variantFilename = $baseName . '_' . $variant . '.' . $extension;
    $uploadDir = defined('UPLOADS_PATH') ? UPLOADS_PATH : dirname(dirname(dirname(__DIR__))) . '/public/uploads';
    $variantPath = $uploadDir . '/' . $variantFilename;

    $ok = false;
    if ($mime === 'image/jpeg') {
        $ok = imagejpeg($dst, $variantPath, 90);
    } elseif ($mime === 'image/png') {
        $ok = imagepng($dst, $variantPath, 6);
    } elseif ($mime === 'image/gif') {
        $ok = imagegif($dst, $variantPath);
    } elseif ($mime === 'image/webp') {
        $ok = imagewebp($dst, $variantPath, 90);
    }

    imagedestroy($src);
    imagedestroy($dst);

    if (!$ok) {
        http_response_code(500);
        echo 'Failed to save cropped image';
        exit;
    }

    // Update variants JSON
    $variants = [];
    if (!empty($media['variants'])) {
        $decoded = json_decode($media['variants'], true);
        if (is_array($decoded)) $variants = $decoded;
    }
    $variants[$variant] = [
        'filename' => $variantFilename,
        'path' => (defined('UPLOADS_WEB_PATH') ? rtrim(UPLOADS_WEB_PATH, '/') . '/' : '/public/uploads/') . $variantFilename,
        'width' => $targetW,
        'height' => $targetH,
    ];

    $update = $db->prepare("UPDATE media SET variants = ?, updated_at = NOW() WHERE id = ?");
    $update->execute([json_encode($variants), $id]);

    echo 'ok';
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
    exit;
}

