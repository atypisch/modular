<?php
/**
 * AJAX handler: update post layout only (no content data).
 * Used for immediate layout changes (delete/duplicate/move) without full Save.
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

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Optional CSRF protection if helper exists
if (function_exists('csrf_verify') && !csrf_verify()) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$postId = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
$layoutJson = isset($_POST['layout_json']) ? $_POST['layout_json'] : null;

if (!$postId || !$layoutJson) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing id or layout_json']);
    exit;
}

$layoutArr = json_decode($layoutJson, true);
if (!is_array($layoutArr) || !array_key_exists('top', $layoutArr) || !array_key_exists('blocks', $layoutArr)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid layout payload']);
    exit;
}

try {
    $db = $GLOBALS['db'];
    $userId = $_SESSION['user_id'] ?? 1;
    $stmt = $db->prepare("UPDATE posts SET layout = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([json_encode($layoutArr), $userId, $postId]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Update layout error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Database error']);
}

