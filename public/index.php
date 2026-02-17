<?php
/**
 * Public front entry point.
 * - Home: no path or path /
 * - Single: ?type=&slug= or PATH_INFO /type_slug/post_slug (two segments), or one segment → post by slug (prefer "page")
 * - Archive: one segment = post type slug → archive-{type}.php or archive.php
 */

define('MODULAR_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);
define('THEMES_PATH', PUBLIC_PATH . '/themes');
define('DEFAULT_THEME', 'default');

if (!defined('ADMIN_PATH')) {
    require_once MODULAR_PATH . '/admin/config/config.php';
}
if (is_file(MODULAR_PATH . '/admin/bootstrap.php')) {
    require_once MODULAR_PATH . '/admin/bootstrap.php';
}

$pathInfo = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';

// Detect post type slug + post slug from query or path
$postTypeSlug = $_GET['type'] ?? $_GET['post_type'] ?? null;
$postSlug = $_GET['slug'] ?? null;
$archiveSegment = null; // one path segment to try as archive (post type slug)

if ($postTypeSlug === null || $postSlug === null) {
    if ($pathInfo !== '') {
        $segments = array_values(array_filter(explode('/', $pathInfo)));
        if (count($segments) >= 2) {
            $postTypeSlug = $segments[0];
            $postSlug = $segments[1];
        } elseif (count($segments) === 1) {
            // Path-by-slug: single segment → resolve published post by slug (prefer post_type "page")
            $slugOnly = $segments[0];
            if (!isset($GLOBALS['db'])) {
                require_once MODULAR_PATH . '/admin/config/config.php';
                require_once MODULAR_PATH . '/core/functions/database.php';
                if (function_exists('run_pending_migrations') && isset($GLOBALS['db'])) {
                    run_pending_migrations();
                }
            }
            if (isset($GLOBALS['db'])) {
                $stmt = $GLOBALS['db']->prepare("
                    SELECT p.id, pt.slug as type_slug FROM posts p
                    INNER JOIN post_types pt ON p.post_type_id = pt.id
                    WHERE p.slug = ? AND p.deleted_at IS NULL AND p.status = 'published'
                    ORDER BY (pt.slug = 'page') DESC
                    LIMIT 1
                ");
                $stmt->execute([$slugOnly]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    $postTypeSlug = $row['type_slug'];
                    $postSlug = $slugOnly;
                } else {
                    $archiveSegment = $slugOnly;
                }
            } else {
                $archiveSegment = $slugOnly;
            }
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
    $post_type_slug = $post['post_type'] ?? $postTypeSlug ?? 'post';
    $themes = ['MDLR-2025', DEFAULT_THEME];
    foreach ($themes as $theme) {
        $theme_path = THEMES_PATH . '/' . $theme;
        $single_type = $theme_path . '/single-' . $post_type_slug . '.php';
        if (is_file($single_type)) {
            include $single_type;
            exit;
        }
        if (is_file($theme_path . '/single.php')) {
            include $theme_path . '/single.php';
            exit;
        }
    }
    http_response_code(500);
    echo 'Theme single template not found.';
    exit;
}

// Archive: one path segment as post type slug (e.g. /blog, /album)
if ($archiveSegment !== null && isset($GLOBALS['db'])) {
    $stmt = $GLOBALS['db']->prepare("SELECT id FROM post_types WHERE slug = ?");
    $stmt->execute([$archiveSegment]);
    $ptRow = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($ptRow) {
        $stmt = $GLOBALS['db']->prepare("
            SELECT p.id, p.title, p.slug, p.status, p.published_at, p.created_at
            FROM posts p
            INNER JOIN post_types pt ON p.post_type_id = pt.id
            WHERE pt.slug = ? AND p.deleted_at IS NULL AND p.status = 'published'
            ORDER BY p.published_at DESC, p.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$archiveSegment]);
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $post_type_slug = $archiveSegment;
        $themes = ['MDLR-2025', DEFAULT_THEME];
        foreach ($themes as $theme) {
            $theme_path = THEMES_PATH . '/' . $theme;
            $archive_type = $theme_path . '/archive-' . $post_type_slug . '.php';
            if (is_file($archive_type)) {
                include $archive_type;
                exit;
            }
            if (is_file($theme_path . '/archive.php')) {
                include $theme_path . '/archive.php';
                exit;
            }
        }
    }
}

// Module-registered public routes (shortcode-equivalent: profile, messages, group)
if (function_exists('apply_filters')) {
    $routeResponse = apply_filters('public_route', null, $pathInfo);
    if ($routeResponse !== null && $routeResponse !== '') {
        echo $routeResponse;
        exit;
    }
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
