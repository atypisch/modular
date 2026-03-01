<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/data/init.php'; // Ensure database is initialized
require_once dirname(dirname(__DIR__)) . '/core/classes/PostType.php';
require_once dirname(dirname(__DIR__)) . '/core/functions/auth.php';
require_once dirname(dirname(__DIR__)) . '/core/functions/admin.php';

// Require authentication
if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'No post type ID provided';
    header('Location: index.php');
    exit;
}

$postType = new PostType();

try {
    // Check if post type exists
    $existing = $postType->getById($_GET['id']);
    if (!$existing) {
        $_SESSION['error'] = 'Post type not found';
        header('Location: index.php');
        exit;
    }

    // Delete the post type
    if ($postType->delete($_GET['id'])) {
        $_SESSION['success'] = 'Post type deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete post type';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
}

header('Location: index.php');
exit;
