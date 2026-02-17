<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required files
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/subscription.php';
require_once CORE_PATH . '/classes/PostType.php';
require_once CORE_PATH . '/classes/Taxonomy.php';

// Check authentication
if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /modular/admin/post-types');
    exit;
}
if (!csrf_verify()) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: /modular/admin/post-types' . (isset($_POST['id']) ? '/edit.php?id=' . (int)$_POST['id'] : ''));
    exit;
}

// Validate and sanitize input
$post_type = [
    'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?? '',
    'slug' => filter_input(INPUT_POST, 'slug', FILTER_SANITIZE_STRING) ?? '',
    'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING) ?? '',
    'public' => isset($_POST['public']) ? 1 : 0,
    'show_in_menu' => isset($_POST['show_in_menu']) ? 1 : 0
];

// Default mode for new post types is 'fixed'; only allow 'modular' when feature is enabled.
$requestedMode = isset($_POST['mode']) ? trim($_POST['mode']) : 'fixed';
if (!in_array($requestedMode, ['fixed', 'modular'], true)) {
    $requestedMode = 'fixed';
}
if ($requestedMode === 'modular' && !subscription_has_feature('modular_post_types')) {
    $requestedMode = 'fixed';
}

// Validate required fields
if (empty($post_type['name'])) {
    $_SESSION['error'] = 'Name is required.';
    header('Location: /modular/admin/post-types/edit.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
    exit;
}

// Clean up the slug (make it URL-friendly) if not provided
if (empty($post_type['slug'])) {
    $post_type['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $post_type['name'])));
} else {
    $post_type['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $post_type['slug'])));
}

try {
    $postType = new PostType();
    $db = $GLOBALS['db'];
    
    // Get taxonomy IDs from form
    $taxonomyIds = isset($_POST['taxonomies']) && is_array($_POST['taxonomies']) ? array_map('intval', $_POST['taxonomies']) : [];
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update existing post type
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $postType->update($id, $post_type);
        
        // Update taxonomy links
        // Remove all existing links
        $stmt = $db->prepare("DELETE FROM post_type_taxonomies WHERE post_type_id = ?");
        $stmt->execute([$id]);
        
        // Add new links
        if (!empty($taxonomyIds)) {
            $stmt = $db->prepare("INSERT INTO post_type_taxonomies (post_type_id, taxonomy_id) VALUES (?, ?)");
            foreach ($taxonomyIds as $taxId) {
                $stmt->execute([$id, $taxId]);
            }
        }
        
        $_SESSION['success'] = 'Post type updated successfully.';
    } else {
        // Create new post type
        $newId = $postType->create($post_type);
        
        // Link taxonomies
        if (!empty($taxonomyIds)) {
            $stmt = $db->prepare("INSERT INTO post_type_taxonomies (post_type_id, taxonomy_id) VALUES (?, ?)");
            foreach ($taxonomyIds as $taxId) {
                $stmt->execute([$newId, $taxId]);
            }
        }
        
        $_SESSION['success'] = 'Post type created successfully. Now configure its fields.';
        // After creating a new post type, go straight to the Field Builder
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/field-builder/?id=' . (int)$newId);
        exit;
    }
    // For updates, go back to the post types list
    header('Location: /modular/admin/post-types');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
    header('Location: /modular/admin/post-types/edit.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
    exit;
}
