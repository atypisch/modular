<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/data/init.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/classes/Taxonomy.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['taxonomy_id'])) {
    $_SESSION['error'] = 'No term ID or taxonomy ID provided for deletion.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/taxonomies');
    exit;
}

$term_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$taxonomy_id = filter_input(INPUT_GET, 'taxonomy_id', FILTER_SANITIZE_NUMBER_INT);

$db = $GLOBALS['db'];
$taxonomyObj = new Modular\Taxonomy($db);

try {
    $taxonomyObj->deleteTerm($term_id);
    $_SESSION['success'] = 'Term deleted successfully.';
} catch (Exception $e) {
    $_SESSION['error'] = 'Error deleting term: ' . $e->getMessage();
}

header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/taxonomies/terms.php?taxonomy_id=' . $taxonomy_id);
exit;


