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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/taxonomies');
    exit;
}
if (!csrf_verify()) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    $tid = filter_input(INPUT_POST, 'taxonomy_id', FILTER_SANITIZE_NUMBER_INT);
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/taxonomies/terms/edit.php?taxonomy_id=' . ($tid ?: '') . (isset($_POST['id']) ? '&id=' . (int)$_POST['id'] : ''));
    exit;
}

$db = $GLOBALS['db'];
$taxonomyObj = new Modular\Taxonomy($db);

$taxonomy_id = filter_input(INPUT_POST, 'taxonomy_id', FILTER_SANITIZE_NUMBER_INT);
$term_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$taxonomy_id) {
    $_SESSION['error'] = 'No taxonomy ID provided.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/taxonomies');
    exit;
}

$term_data = [
    'taxonomy_id' => $taxonomy_id,
    'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?? '',
    'slug' => filter_input(INPUT_POST, 'slug', FILTER_SANITIZE_STRING) ?? '',
    'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING) ?? '',
    'parent_id' => filter_input(INPUT_POST, 'parent_id', FILTER_SANITIZE_NUMBER_INT) ?: null
];

if (empty($term_data['name'])) {
    $_SESSION['error'] = 'Term name is required.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/taxonomies/terms/edit.php' . ($term_id ? '?id=' . $term_id : '') . '&taxonomy_id=' . $taxonomy_id);
    exit;
}

if (empty($term_data['slug'])) {
    $term_data['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $term_data['name'])));
} else {
    $term_data['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $term_data['slug'])));
}

try {
    if ($term_id) {
        $taxonomyObj->updateTerm($term_id, $term_data);
        $_SESSION['success'] = 'Term updated successfully.';
    } else {
        $taxonomyObj->createTerm($term_data);
        $_SESSION['success'] = 'Term created successfully.';
    }
    
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/taxonomies/terms.php?taxonomy_id=' . $taxonomy_id);
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/taxonomies/terms/edit.php' . ($term_id ? '?id=' . $term_id : '') . '&taxonomy_id=' . $taxonomy_id);
    exit;
}


