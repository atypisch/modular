<?php
/**
 * Public front entry point.
 * - Home: no path or path /
 * - Single: ?type=&slug= or PATH_INFO /type_slug/post_slug (two segments), or one segment → post by slug (prefer "page")
 * - Archive: one segment = post type slug → archive-{type}.php or archive.php
 */

if (!defined('MODULAR_PATH')) {
    define('MODULAR_PATH', dirname(__DIR__));
}
if (!defined('ADMIN_PATH')) {
    require_once MODULAR_PATH . '/admin/config/config.php';
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', __DIR__);
}
if (!defined('THEMES_PATH')) {
    define('THEMES_PATH', PUBLIC_PATH . '/themes');
}
if (!defined('DEFAULT_THEME')) {
    define('DEFAULT_THEME', 'default');
}
if (is_file(MODULAR_PATH . '/admin/bootstrap.php')) {
    require_once MODULAR_PATH . '/admin/bootstrap.php';
}
if (is_file(MODULAR_PATH . '/core/functions/template-helpers.php')) {
    require_once MODULAR_PATH . '/core/functions/template-helpers.php';
}

// If someone hits /public/* directly (e.g. http://localhost/modular/public/),
// redirect to the clean root URL (http://localhost/modular/*).
if (isset($_SERVER['REQUEST_URI']) && defined('BASE_PATH') && strpos($_SERVER['REQUEST_URI'], BASE_PATH . '/public/') === 0) {
    $suffix = substr($_SERVER['REQUEST_URI'], strlen(BASE_PATH . '/public'));
    if ($suffix === '' || $suffix === false) {
        $suffix = '/';
    }
    header('Location: ' . SITE_URL . $suffix, true, 301);
    exit;
}

// Determine active theme from settings (fallback to DEFAULT_THEME)
$activeTheme = DEFAULT_THEME;
if (isset($GLOBALS['db'])) {
    try {
        $stmtActiveTheme = $GLOBALS['db']->prepare("SELECT option_value FROM settings WHERE option_name = 'active_theme' LIMIT 1");
        $stmtActiveTheme->execute();
        $rowActiveTheme = $stmtActiveTheme->fetch(\PDO::FETCH_ASSOC);
        if ($rowActiveTheme && !empty($rowActiveTheme['option_value'])) {
            $activeTheme = $rowActiveTheme['option_value'];
        }
    } catch (\Throwable $e) {
        $activeTheme = DEFAULT_THEME;
    }
}
if (!defined('ACTIVE_THEME')) {
    define('ACTIVE_THEME', $activeTheme);
}

$pathInfo = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
if ($pathInfo === '' && isset($_SERVER['REQUEST_URI'])) {
    $req = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
    $base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
    if ($base !== '' && strpos($req, $base) === 0) {
        $pathInfo = trim(substr($req, strlen($base)), '/');
    } elseif ($base === '' && $req !== '') {
        $pathInfo = trim($req, '/');
    }
}

// Fixed routes (Simuze page templates): path segment → theme template name (without .php)
$fixedRoutes = [
    'music' => 'template_music',
    'network' => 'template_network',
    'live' => 'template_live',
    'weblog' => 'template_weblog',
    'dashboard' => 'template_dashboard',
    'login' => 'template_login',
    'signup' => 'template_signup',
    'profile' => 'template_profile',
];
if (function_exists('apply_filters')) {
    $fixedRoutes = apply_filters('public_fixed_routes', $fixedRoutes);
}

// Load permalink functions
if (is_file(MODULAR_PATH . '/core/functions/permalinks.php')) {
    require_once MODULAR_PATH . '/core/functions/permalinks.php';
}

// Admin preview. A logged-in CMS admin asking for ?preview=1 may open any post
// whatever its status, so drafts and scheduled items can be checked before they
// go live. is_admin() gates on the cms_* session keys only, so a front-end
// login can never satisfy it. Everyone else keeps the published-or-due rule.
$md_preview = isset($_GET['preview']) && $_GET['preview'] !== '0'
    && function_exists('is_admin') && is_admin();
$md_status_sql = $md_preview
    ? '1=1'
    : "(p.status = 'published' OR (p.status = 'scheduled' AND p.published_at IS NOT NULL AND p.published_at <= NOW()))";

// Detect post type slug + post slug from query or path
$postTypeSlug = $_GET['type'] ?? $_GET['post_type'] ?? null;
$postSlug = $_GET['slug'] ?? null;
$archiveSegment = null; // one path segment to try as archive (post type slug)

// Try permalink structure resolution first (handles /{category}/{slug}, /events/{slug}, etc.)
if ($postTypeSlug === null && $postSlug === null && $pathInfo !== '' && isset($GLOBALS['db']) && function_exists('permalink_resolve_path')) {
    $resolved = permalink_resolve_path($pathInfo, $GLOBALS['db']);
    if ($resolved !== null) {
        $postTypeSlug = $resolved['post_type'];
        $postSlug     = $resolved['slug'];
    }
}

if ($postTypeSlug === null || $postSlug === null) {
    if ($pathInfo !== '') {
        $segments = array_values(array_filter(explode('/', $pathInfo)));
        if (count($segments) >= 2) {
            $locationTaxonomies = ['country', 'province', 'city'];
            if (in_array($segments[0], $locationTaxonomies, true)) {
                if (!isset($GLOBALS['db'])) {
                    require_once MODULAR_PATH . '/admin/config/config.php';
                    require_once MODULAR_PATH . '/core/functions/database.php';
                    if (function_exists('run_pending_migrations') && isset($GLOBALS['db'])) {
                        run_pending_migrations();
                    }
                }
                if (isset($GLOBALS['db'])) {
                    $taxSlug = $segments[0];
                    $termSlug = $segments[1];
                    $stmt = $GLOBALS['db']->prepare("
                        SELECT t.id, t.name, t.slug, t.description, tax.slug as taxonomy_slug
                        FROM terms t
                        INNER JOIN taxonomies tax ON t.taxonomy_id = tax.id
                        WHERE tax.slug = ? AND t.slug = ? AND t.deleted_at IS NULL
                    ");
                    $stmt->execute([$taxSlug, $termSlug]);
                    $locationTerm = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($locationTerm) {
                        $location_taxonomy = $taxSlug;
                        $location_term_slug = $termSlug;
                        $location_term = $locationTerm;
                        $themes = ['MDLR-2025', 'simuze', $activeTheme];
                        $templateName = 'template_' . $taxSlug;
                        foreach ($themes as $theme) {
                            $theme_path = THEMES_PATH . '/' . $theme;
                            if (is_file($theme_path . '/' . $templateName . '.php')) {
                                include $theme_path . '/' . $templateName . '.php';
                                exit;
                            }
                        }
                        if (is_file(THEMES_PATH . '/MDLR-2025/template_location.php')) {
                            include THEMES_PATH . '/MDLR-2025/template_location.php';
                            exit;
                        }
                    }
                }
            }
            $postTypeSlug = $segments[0];
            $postSlug = $segments[1];
        } elseif (count($segments) === 1) {
            $slugOnly = $segments[0];
            // Fixed route: one segment → load theme template (e.g. /music → template_music.php)
            if (isset($fixedRoutes[$slugOnly])) {
                $templateName = $fixedRoutes[$slugOnly];
                $themes = ['MDLR-2025', 'simuze', $activeTheme];
                foreach ($themes as $theme) {
                    $theme_path = THEMES_PATH . '/' . $theme;
                    $templateFile = $theme_path . '/' . $templateName . '.php';
                    if (is_file($templateFile)) {
                        $route_segment = $slugOnly;
                        include $templateFile;
                        exit;
                    }
                }
            }
            // Path-by-slug: single segment → resolve published post by slug (prefer post_type "page")
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
    require_once MODULAR_PATH . '/core/fields/FieldRegistry.php';
    \Modular\Fields\FieldRegistry::bootDefaultFields();
    require_once MODULAR_PATH . '/core/PostTypeResolver.php';
    $resolver = new \Modular\PostTypeResolver($GLOBALS['db']);
    // Published, or scheduled with publication date in the past
    $stmt = $GLOBALS['db']->prepare("
        SELECT p.id FROM posts p
        INNER JOIN post_types pt ON p.post_type_id = pt.id
        WHERE pt.slug = ? AND p.slug = ? AND p.deleted_at IS NULL
        AND $md_status_sql
    ");
    $stmt->execute([$postTypeSlug, $postSlug]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$row) {
        foreach ([$activeTheme, 'MDLR-2025'] as $theme) {
            $theme_path = THEMES_PATH . '/' . $theme;
            if (is_file($theme_path . '/404.php')) {
                http_response_code(404);
                include $theme_path . '/404.php';
                exit;
            }
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
    $post_template = isset($post['template']) ? trim((string) $post['template']) : '';
    $visibility = $post['visibility'] ?? 'public';
    $post_password = $post['password'] ?? '';

    // Private: only show to logged-in author (theme or auth middleware can enforce)
    if ($visibility === 'private') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $currentUserId = $_SESSION['user_id'] ?? null;
        $isAuthor = $currentUserId && isset($post['author_id']) && (int) $post['author_id'] === (int) $currentUserId;
        $isAdmin = !empty($_SESSION['is_admin']) || !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if (!$isAuthor && !$isAdmin) {
            http_response_code(403);
            foreach ([$activeTheme, 'MDLR-2025', 'default'] as $theme) {
                $tp = THEMES_PATH . '/' . $theme . '/404.php';
                if (is_file($tp)) {
                    include $tp;
                    exit;
                }
            }
            echo 'This content is private.';
            exit;
        }
    }

    // Password protected: require correct password via cookie or query
    if ($visibility === 'password' && $post_password !== '') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $unlockKey = 'modular_post_' . (int) $post['id'];
        $submitted = $_POST['post_password'] ?? $_GET['post_password'] ?? '';
        $cookieVal = $_COOKIE[$unlockKey] ?? '';
        $sessionUnlocked = !empty($_SESSION[$unlockKey]);
        $cookieUnlocked = ($cookieVal !== '' && $cookieVal === $post_password);
        if ($cookieUnlocked && !$sessionUnlocked) {
            $_SESSION[$unlockKey] = true;
        }
        $unlocked = ($submitted !== '' && $submitted === $post_password);
        if ($unlocked) {
            $_SESSION[$unlockKey] = true;
            if (!isset($_COOKIE[$unlockKey]) || $_COOKIE[$unlockKey] !== $submitted) {
                setcookie($unlockKey, $submitted, time() + 86400 * 7, '/');
            }
        }
        if (!$unlocked && !$sessionUnlocked && !$cookieUnlocked) {
            $wrong = isset($_POST['post_password']) || isset($_GET['post_password']);
            foreach ([$activeTheme, 'MDLR-2025', 'default'] as $theme) {
                $tp = THEMES_PATH . '/' . $theme . '/template-password.php';
                if (is_file($tp)) {
                    include $tp;
                    exit;
                }
            }
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Password required</title></head><body>';
            echo '<form method="post" action="">';
            echo '<label>Password: <input type="password" name="post_password" autocomplete="off" required></label> ';
            echo '<button type="submit">View</button>';
            if ($wrong) {
                echo '<p style="color:red;">Incorrect password.</p>';
            }
            echo '</form></body></html>';
            exit;
        }
    }

    $themes = ['MDLR-2025', $activeTheme];
    foreach ($themes as $theme) {
        $theme_path = THEMES_PATH . '/' . $theme;
        // Template-by-slug: if post has template set (e.g. template_music), load that first
        if ($post_template !== '' && is_file($theme_path . '/' . $post_template . '.php')) {
            include $theme_path . '/' . $post_template . '.php';
            exit;
        }
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
            WHERE pt.slug = ? AND p.deleted_at IS NULL
            AND (p.status = 'published' OR (p.status = 'scheduled' AND p.published_at IS NOT NULL AND p.published_at <= NOW()))
            ORDER BY p.published_at DESC, p.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$archiveSegment]);
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $post_type_slug = $archiveSegment;
        $themes = ['MDLR-2025', $activeTheme];
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

// Internal documentation (public pages)
$docSlug = null;
if ($pathInfo === 'documentation' || strpos($pathInfo, 'documentation/') === 0) {
    $docSlug = $pathInfo === 'documentation' ? 'overview' : trim(str_replace('documentation/', '', $pathInfo), '/');
    if ($docSlug === '') $docSlug = 'overview';
    $docFile = MODULAR_PATH . '/core/documentation/' . preg_replace('/[^a-z0-9_-]/', '', $docSlug) . '.php';
    if (is_file($docFile)) {
        $doc = include $docFile;
        $page_title = is_array($doc) && isset($doc['title']) ? $doc['title'] : 'Documentation';
        $doc_content = $doc;
        $doc_slug = $docSlug;
        $theme_path = THEMES_PATH . '/' . $activeTheme;
        if (is_file($theme_path . '/documentation.php')) {
            include $theme_path . '/documentation.php';
            exit;
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
$theme_path = THEMES_PATH . '/' . $activeTheme;
if (is_file($theme_path . '/index.php')) {
    require $theme_path . '/index.php';
} else {
    require THEMES_PATH . '/default/index.php';
}
