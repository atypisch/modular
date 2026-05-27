<?php
/**
 * AJAX quick save for posts list: title, slug, status, published_at, excerpt only.
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

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

$token = isset($payload['_csrf_token']) ? (string) $payload['_csrf_token'] : '';
$expected = $_SESSION['_csrf_token'] ?? '';
if ($expected === '' || !hash_equals($expected, $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$postId = isset($payload['id']) ? (int) $payload['id'] : 0;
if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid post id']);
    exit;
}

$title = isset($payload['title']) ? trim((string) $payload['title']) : '';
$slugRaw = isset($payload['slug']) ? trim((string) $payload['slug']) : '';
$status = isset($payload['status']) ? trim((string) $payload['status']) : 'draft';
$publishedAtRaw = isset($payload['published_at']) ? trim((string) $payload['published_at']) : '';
$excerpt = array_key_exists('excerpt', $payload) ? trim((string) $payload['excerpt']) : null;

if ($title === '') {
    echo json_encode(['success' => false, 'error' => 'Title is required.']);
    exit;
}

$allowed = ['draft', 'published', 'scheduled', 'archived'];
if (!in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status.']);
    exit;
}

$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database unavailable']);
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 1);

try {
    $stmt = $db->prepare("SELECT id, post_type_id, slug, title, excerpt, status, published_at FROM posts WHERE id = ? AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$postId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Post not found.']);
        exit;
    }

    $postTypeId = (int) $row['post_type_id'];

    if ($slugRaw === '') {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $title)));
    } else {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $slugRaw)));
    }

    $publishedAt = null;
    if ($publishedAtRaw !== '') {
        $ts = strtotime($publishedAtRaw);
        if ($ts !== false) {
            $publishedAt = date('Y-m-d H:i:s', $ts);
        }
    }

    if ($status === 'published' && $publishedAt !== null && strtotime($publishedAt) > time()) {
        $status = 'scheduled';
    }

    if ($status !== 'published' && $status !== 'scheduled' && $publishedAt === null) {
        if (!empty($row['published_at'])) {
            $publishedAt = $row['published_at'];
        }
    }
    if ($status === 'published' && $publishedAt === null) {
        $publishedAt = date('Y-m-d H:i:s');
    }

    if ($excerpt === null) {
        $excerpt = (string) ($row['excerpt'] ?? '');
    }

    $checkStmt = $db->prepare('SELECT id FROM posts WHERE slug = ? AND post_type_id = ? AND id != ? AND deleted_at IS NULL LIMIT 1');
    $checkStmt->execute([$slug, $postTypeId, $postId]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'A post with this slug already exists for this post type.']);
        exit;
    }

    $upd = $db->prepare('
        UPDATE posts
        SET title = ?, slug = ?, excerpt = ?, status = ?, published_at = ?, updated_by = ?, updated_at = NOW()
        WHERE id = ? AND deleted_at IS NULL
    ');
    $upd->execute([$title, $slug, $excerpt, $status, $publishedAt, $userId, $postId]);

    $row_view = '';
    $row_preview = '';
    if ($slug !== '') {
        $row_view = function_exists('get_permalink') ? get_permalink($postId) : '';
        $row_preview = function_exists('get_post_preview_url') ? get_post_preview_url($postId) : '';
    }

    echo json_encode([
        'success' => true,
        'post' => [
            'id' => $postId,
            'title' => $title,
            'slug' => $slug,
            'status' => $status,
            'published_at' => $publishedAt,
            'excerpt' => $excerpt,
            'view_url' => $row_view,
            'preview_url' => $row_preview,
        ],
    ]);
} catch (Throwable $e) {
    error_log('quick-save: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Save failed.']);
}
