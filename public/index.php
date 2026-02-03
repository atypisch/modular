<?php
/**
 * Public front entry point.
 * - Home: no path or path /
 * - Single post: ?type=post_type_slug&slug=post_slug or PATH_INFO /type_slug/post_slug
 */

define('MODULAR_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);
define('THEMES_PATH', PUBLIC_PATH . '/themes');
define('DEFAULT_THEME', 'default');

if (!defined('ADMIN_PATH')) {
    require_once MODULAR_PATH . '/admin/config/config.php';
}

// Detect post type slug + post slug from query or path
$postTypeSlug = $_GET['type'] ?? $_GET['post_type'] ?? null;
$postSlug = $_GET['slug'] ?? null;

if ($postTypeSlug === null || $postSlug === null) {
    $pathInfo = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
    if ($pathInfo !== '') {
        $segments = array_values(array_filter(explode('/', $pathInfo)));
        if (count($segments) >= 2) {
            $postTypeSlug = $segments[0];
            $postSlug = $segments[1];
        }
    }
}

if ($postTypeSlug !== null && $postSlug !== null) {
    // Load config and resolve single post
    if (!isset($GLOBALS['db'])) {
        require_once MODULAR_PATH . '/admin/config/config.php';
        require_once MODULAR_PATH . '/core/functions/database.php';
        if (function_exists('run_pending_migrations') && isset($GLOBALS['db'])) {
            run_pending_migrations();
        }
    }
    if (!isset($GLOBALS['db'])) {
        http_response_code(500);
        echo 'Database unavailable.';
        exit;
    }
    require_once MODULAR_PATH . '/core/PostTypeResolver.php';
    $resolver = new \Modular\PostTypeResolver($GLOBALS['db']);
    $stmt = $GLOBALS['db']->prepare("
        SELECT p.id FROM posts p
        INNER JOIN post_types pt ON p.post_type_id = pt.id
        WHERE pt.slug = ? AND p.slug = ? AND p.deleted_at IS NULL AND p.status = 'published'
    ");
    $stmt->execute([$postTypeSlug, $postSlug]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$row) {
$theme_path = THEMES_PATH . '/MDLR-2025';
if (is_file($theme_path . '/404.php')) {
            http_response_code(404);
            include $theme_path . '/404.php';
            exit;
        }
        http_response_code(404);
        echo 'Post not found.';
        exit;
    }
    $resolved = $resolver->resolvePost((int) $row['id'], ['resolve_relations' => true, 'include_author' => true]);
    if (isset($resolved['error'])) {
        http_response_code($resolved['code'] ?? 500);
        echo $resolved['error'] ?? 'Error loading post.';
        exit;
    }
    $post = $resolved;
    foreach (['MDLR-2025', DEFAULT_THEME] as $theme) {
        $theme_path = THEMES_PATH . '/' . $theme;
        if (is_file($theme_path . '/single.php')) {
            include $theme_path . '/single.php';
            exit;
        }
    }
    http_response_code(500);
    echo 'Theme single template not found.';
    exit;
}

// Home
$page_title = 'Home';
$content = '
    <div class="welcome-section">
        <h1>Welcome to Modular CMS</h1>
        <p>This is a simple content management system built with PHP.</p>
        <p>Features:</p>
        <ul>
            <li>Theme-based design</li>
            <li>Modular architecture</li>
            <li>Simple and clean interface</li>
        </ul>
    </div>
';
$theme_path = THEMES_PATH . '/' . DEFAULT_THEME;
if (is_file($theme_path . '/index.php')) {
    require $theme_path . '/index.php';
} else {
    require THEMES_PATH . '/default/index.php';
}
