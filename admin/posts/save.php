<?php
/**
 * Posts Save Handler
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

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /modular/admin/posts');
    exit;
}

$db = $GLOBALS['db'];
$userId = $_SESSION['user_id'] ?? 1;

// Validate and sanitize input
$postData = [
    'post_type_id' => filter_input(INPUT_POST, 'post_type_id', FILTER_SANITIZE_NUMBER_INT),
    'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING) ?? '',
    'slug' => filter_input(INPUT_POST, 'slug', FILTER_SANITIZE_STRING) ?? '',
    'excerpt' => filter_input(INPUT_POST, 'excerpt', FILTER_SANITIZE_STRING) ?? '',
    'status' => filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) ?? 'draft',
    'component_data' => filter_input(INPUT_POST, 'component_data', FILTER_UNSAFE_RAW) ?? '[]'
];

// Validate required fields
if (empty($postData['title']) || empty($postData['post_type_id'])) {
    $_SESSION['error'] = 'Title and Post Type are required.';
    header('Location: /modular/admin/posts/edit.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
    exit;
}

// Clean slug
if (empty($postData['slug'])) {
    $postData['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $postData['title'])));
} else {
    $postData['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $postData['slug'])));
}

// Validate component_data JSON
$componentData = json_decode($postData['component_data'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $_SESSION['error'] = 'Invalid JSON in component data.';
    header('Location: /modular/admin/posts/edit.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
    exit;
}

try {
    $postId = isset($_POST['id']) ? filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT) : null;
    
    // Check if post type exists
    $stmt = $db->prepare("SELECT id FROM post_types WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$postData['post_type_id']]);
    if (!$stmt->fetch()) {
        throw new Exception("Post type not found");
    }
    
    // Check slug uniqueness
    $checkStmt = $db->prepare("SELECT id FROM posts WHERE slug = ? AND post_type_id = ?" . ($postId ? " AND id != ?" : ""));
    $checkParams = [$postData['slug'], $postData['post_type_id']];
    if ($postId) {
        $checkParams[] = $postId;
    }
    $checkStmt->execute($checkParams);
    if ($checkStmt->fetch()) {
        throw new Exception("A post with this slug already exists for this post type");
    }
    
    $publishedAt = ($postData['status'] === 'published') ? date('Y-m-d H:i:s') : null;
    
    if ($postId) {
        // Update existing post
        $stmt = $db->prepare("
            UPDATE posts 
            SET post_type_id = ?, slug = ?, title = ?, excerpt = ?, status = ?, 
                component_data = ?, published_at = ?, updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $postData['post_type_id'], $postData['slug'], $postData['title'], 
            $postData['excerpt'], $postData['status'], json_encode($componentData),
            $publishedAt, $userId, $postId
        ]);
        $_SESSION['success'] = 'Post updated successfully.';
    } else {
        // Create new post
        $stmt = $db->prepare("
            INSERT INTO posts 
            (post_type_id, slug, title, excerpt, status, component_data,
             author_id, created_by, updated_by, published_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $postData['post_type_id'], $postData['slug'], $postData['title'],
            $postData['excerpt'], $postData['status'], json_encode($componentData),
            $userId, $userId, $userId, $publishedAt
        ]);
        $_SESSION['success'] = 'Post created successfully.';
    }
    
    header('Location: /modular/admin/posts?post_type_id=' . $postData['post_type_id']);
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
    error_log("Post save error: " . $e->getMessage());
    header('Location: /modular/admin/posts/edit.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
    exit;
}
