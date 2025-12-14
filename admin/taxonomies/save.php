<?php
/**
 * Save Taxonomy
 */

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/classes/Taxonomy.php';

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /modular/admin/taxonomies');
    exit;
}

// Validate and sanitize input
$taxonomy = [
    'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?? '',
    'slug' => filter_input(INPUT_POST, 'slug', FILTER_SANITIZE_STRING) ?? '',
    'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING) ?? '',
    'hierarchical' => isset($_POST['hierarchical']) ? 1 : 0,
    'public' => isset($_POST['public']) ? 1 : 0,
    'show_in_menu' => isset($_POST['show_in_menu']) ? 1 : 0,
    'show_in_rest' => isset($_POST['show_in_rest']) ? 1 : 0,
    'rest_base' => filter_input(INPUT_POST, 'rest_base', FILTER_SANITIZE_STRING) ?? '',
    'post_types' => isset($_POST['post_types']) && is_array($_POST['post_types']) ? array_map('intval', $_POST['post_types']) : []
];

// Validate required fields
if (empty($taxonomy['name'])) {
    $_SESSION['error'] = 'Name is required.';
    header('Location: /modular/admin/taxonomies/edit.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
    exit;
}

try {
    $taxonomyClass = new Taxonomy();
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update existing taxonomy
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $taxonomyClass->update($id, $taxonomy);
        $_SESSION['success'] = 'Taxonomy updated successfully.';
    } else {
        // Create new taxonomy
        $taxonomyClass->create($taxonomy);
        $_SESSION['success'] = 'Taxonomy created successfully.';
    }
    
    header('Location: /modular/admin/taxonomies');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
    header('Location: /modular/admin/taxonomies/edit.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
    exit;
}

