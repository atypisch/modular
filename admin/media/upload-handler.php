<?php
/**
 * Media Upload Handler
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
        exit;
    }
    $_SESSION['error'] = 'No file uploaded.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/media/upload.php');
    exit;
}
if (!csrf_verify()) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    // For AJAX file uploads from the form, also check CSRF token from query string or header
    $getToken = $_GET['_csrf_token'] ?? '';
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['_csrf_token'] ?? '';
    $getValid = $getToken !== '' && hash_equals($sessionToken, $getToken);
    $headerValid = $headerToken !== '' && hash_equals($sessionToken, $headerToken);
    
    if (!$getValid && !$headerValid) {
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid request. Please try again.']);
            exit;
        }
        $_SESSION['error'] = 'Invalid request. Please try again.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/media/upload.php');
        exit;
    }
}

$db = $GLOBALS['db'];
$userId = $_SESSION['user_id'] ?? 1;

$file = $_FILES['file'];
$title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING) ?? '';
$altText = filter_input(INPUT_POST, 'alt_text', FILTER_SANITIZE_STRING) ?? '';
$folderPath = filter_input(INPUT_POST, 'folder_path', FILTER_SANITIZE_STRING) ?? '';

// Validate file
if ($file['error'] !== UPLOAD_ERR_OK) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $errorMsg = 'File upload error.';
    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $errorMsg]);
        exit;
    }
    $_SESSION['error'] = $errorMsg;
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/media/upload.php');
    exit;
}

$allowedTypes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf',
    'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/webm', 'audio/flac', 'audio/aac', 'audio/mp4',
];
if (!in_array($file['type'], $allowedTypes)) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $errorMsg = 'File type not allowed. Allowed: images, PDF, and audio (MP3, WAV, OGG, WebM, FLAC, AAC).';
    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $errorMsg]);
        exit;
    }
    $_SESSION['error'] = $errorMsg;
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/media/upload.php');
    exit;
}

// Create uploads directory if it doesn't exist (use configurable path)
$uploadDir = defined('UPLOADS_PATH') ? UPLOADS_PATH : (dirname(dirname(dirname(__DIR__))) . '/public/uploads');
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '_' . time() . '.' . $extension;
$filePath = $uploadDir . '/' . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    $_SESSION['error'] = 'Failed to save file.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/media/upload.php');
    exit;
}

// Get file info
$fileSize = filesize($filePath);
$mimeType = $file['type'];
$fileType = strpos($mimeType, 'image/') === 0 ? 'image' : (strpos($mimeType, 'audio/') === 0 ? 'audio' : 'file');

$width = null;
$height = null;
$variants = [];

// Process image if it's an image
if ($fileType === 'image') {
    $imageInfo = @getimagesize($filePath);
    if ($imageInfo) {
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        // Generate variants (simplified - would use MediaController in production)
        $variants = [];
    }
}

// Save to database
try {
    // Store file_path as a path RELATIVE to the uploads directory so downstream
    // URL resolvers can prepend UPLOADS_WEB_PATH without producing a doubled
    // prefix (e.g. /modular/public/uploads/modular/public/uploads/...).
    $relativePath = $filename;
    $uploadsWebBase = defined('UPLOADS_WEB_PATH')
        ? rtrim(UPLOADS_WEB_PATH, '/')
        : (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') . '/public/uploads' : '/public/uploads');
    $webUrl = $uploadsWebBase . '/' . ltrim($relativePath, '/');
    
    $stmt = $db->prepare("
        INSERT INTO media 
        (filename, file_path, file_type, mime_type, file_size, width, height, 
         variants, title, alt_text, folder_path, uploaded_by, created_by, updated_by, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $variantsJson = json_encode($variants);
    $title = $title ?: basename($file['name']);
    
    $stmt->execute([
        $filename, $relativePath, $fileType, $mimeType, $fileSize, $width, $height,
        $variantsJson, $title, $altText, $folderPath ?: null, $userId, $userId, $userId
    ]);
    
    $mediaId = (int)$db->lastInsertId();
    
    // Check if AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'media_id' => $mediaId,
            'filename' => $filename,
            'file_path' => $relativePath,
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'url' => $webUrl,
        ]);
        exit;
    }
    
    $_SESSION['success'] = 'Media uploaded successfully.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/media');
    exit;
    
} catch (Exception $e) {
    // Delete uploaded file on error
    @unlink($filePath);
    $errorMsg = 'Error saving media: ' . $e->getMessage();
    error_log("Media upload error: " . $errorMsg);
    
    // Check if AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $errorMsg
        ]);
        exit;
    }
    
    $_SESSION['error'] = $errorMsg;
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/media/upload.php');
    exit;
}


