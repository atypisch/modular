<?php
/**
 * Delete Taxonomy
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

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$id) {
    $_SESSION['error'] = 'Invalid taxonomy ID.';
    header('Location: /modular/admin/taxonomies');
    exit;
}

try {
    $taxonomy = new Taxonomy();
    $taxonomy->delete($id);
    $_SESSION['success'] = 'Taxonomy deleted successfully.';
} catch (Exception $e) {
    $_SESSION['error'] = 'Error deleting taxonomy: ' . $e->getMessage();
    error_log("Error deleting taxonomy: " . $e->getMessage());
}

header('Location: /modular/admin/taxonomies');
exit;


