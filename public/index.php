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

// HARD ROUTING INTERCEPT: let SimuzeKernel own routing when simuze theme is active
if (ACTIVE_THEME === 'simuze' && defined('MODULES_PATH')) {
    $kernelPath = MODULES_PATH . '/simuze-community/SimuzeKernel.php';
    if (is_file($kernelPath)) {
        require_once $kernelPath;
        if (class_exists('SimuzeKernel')) {
            $kernelResponse = SimuzeKernel::handle($_SERVER);
            if ($kernelResponse !== null && $kernelResponse !== '') {
                echo $kernelResponse;
                exit;
            }
        }
    }
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

// SIMEZU auth proxy – frontend points to this; proxy adds App API Token and forwards to Simezu
if (strpos($pathInfo, 'api/auth') === 0) {
    $rest = substr($pathInfo, strlen('api/auth'));
    $GLOBALS['simezu_auth_path'] = trim($rest, '/');
    require __DIR__ . '/api/simezu-auth-proxy.php';
    exit;
}

// Simuze REST API (Modular): POST/GET to api/simuze/v1/* (e.g. groups, users/search)
if (strpos($pathInfo, 'api/simuze/v1/') === 0 || $pathInfo === 'api/simuze/v1') {
    $restPath = $pathInfo === 'api/simuze/v1' ? '' : trim(substr($pathInfo, strlen('api/simuze/v1/')), '/');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (class_exists('Simuze_REST_API')) {
        $request = class_exists('Simuze_REST_Request_Adapter') ? new Simuze_REST_Request_Adapter($restPath, $method) : null;
        if ($request && $restPath === 'groups' && $method === 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            $api = Simuze_REST_API::get_instance();
            $result = $api->create_group($request);
            if (is_wp_error($result)) {
                $err_data = $result->get_error_data();
                $status = (is_array($err_data) && isset($err_data['status'])) ? (int) $err_data['status'] : 500;
                http_response_code($status);
                echo wp_json_encode(['code' => $result->get_error_code(), 'message' => $result->get_error_message()]);
            } else {
                echo wp_json_encode($result->get_data());
            }
            exit;
        }
    }
}

// Simuze Community AJAX endpoint (replaces admin-ajax.php for module)
// Accept both spellings: simuze-ajax (legacy) and simezu-ajax (matches actual file name).
if (in_array($pathInfo, ['api/simuze-ajax', 'api/simuze-ajax.php', 'api/simezu-ajax', 'api/simezu-ajax.php'], true)) {
    require __DIR__ . '/api/simezu-ajax.php';
    exit;
}

// Simezu payment webhook
if (in_array($pathInfo, ['api/simezu-webhook', 'api/simezu-webhook.php'], true)) {
    require __DIR__ . '/api/simezu-webhook.php';
    exit;
}

// High-priority music album route: /music/album/{slug}
// This bypasses generic post routing and ensures Modular uses md_music_albums
// + MusicRepository for single album pages (no WordPress dependency).
if (strpos($pathInfo, 'music/album/') === 0 && defined('MODULES_PATH')) {
    // Lazily ensure DB + MusicRepository are available
    if (!isset($GLOBALS['db'])) {
        require_once MODULAR_PATH . '/admin/config/config.php';
        require_once MODULAR_PATH . '/core/functions/database.php';
        if (function_exists('run_pending_migrations') && isset($GLOBALS['db'])) {
            run_pending_migrations();
        }
    }
    $segments = array_values(array_filter(explode('/', $pathInfo)));
    $album_slug = $segments[2] ?? '';
    $album      = class_exists('MusicRepository') ? MusicRepository::findAlbumBySlug($album_slug) : null;
    if ($album) {
        $group = null;
        if (!empty($album['group_id']) && class_exists('Simuze_Group')) {
            try {
                $group = Simuze_Group::get((int) $album['group_id']);
            } catch (\Throwable $e) {
                $group = null;
            }
        }
        $tracks = class_exists('MusicRepository') ? MusicRepository::findTracksByAlbum((int) $album['id']) : [];
        // Use the theme's clean template-part (no WP deps, proper CSS classes)
        $theme_path = THEMES_PATH . '/' . ACTIVE_THEME;
        $album_single_tpl = $theme_path . '/template-parts/view-album.php';
        // Fallback to module template if theme template-part missing
        if (!is_file($album_single_tpl)) {
            $album_single_tpl = MODULES_PATH . '/simuze-community/templates/album-single.php';
        }
        if (is_file($album_single_tpl)) {
            // Variables expected by template-parts/view-album.php
            $item_id   = (int) $album['id'];
            $item_type = !empty($album['is_album']) ? 'album' : 'single';
            $group_id  = (int) ($album['group_id'] ?? 0);
            $page_title = $album['album_name'] ?? 'Album';
            ob_start();
            if (is_file($theme_path . '/header.php')) {
                include $theme_path . '/header.php';
            }
            include $album_single_tpl;
            if (is_file($theme_path . '/footer.php')) {
                include $theme_path . '/footer.php';
            }
            echo ob_get_clean();
            exit;
        }
    }
    // If album niet gevonden of template ontbreekt, verder laten gaan naar normale router (404).
}

// Fixed routes (Simuze page templates): path segment → theme template name (without .php)
$fixedRoutes = [
    'music' => 'template_music',
    'search' => 'template_music',
    'network' => 'template_network',
    'live' => 'template_live',
    'weblog' => 'template_weblog',
    'dashboard' => 'template_dashboard',
    'account' => 'template_profile',
    'login' => 'template_login',
    'signup' => 'template_signup',
    'forgot-password' => 'template_forgot_password',
    'profile' => 'template_profile',
    'about' => 'template_about',
    'howto' => 'template_howto',
    'singles' => 'template_singles',
    'store' => 'template_store',
    'subscription' => 'template_subscription',
];
if (function_exists('apply_filters')) {
    $fixedRoutes = apply_filters('public_fixed_routes', $fixedRoutes);
}

// Module public routes (profile, group, messages, etc.) – before post resolution
$communityPrefixes = ['profile', 'user', 'group', 'groups', 'messages', 'discography', 'activity', 'friends'];
$firstSegment = $pathInfo !== '' ? trim(explode('/', $pathInfo)[0], '/') : '';
if ($firstSegment !== '' && (in_array($firstSegment, $communityPrefixes, true) || $pathInfo === 'profile')) {
    if (function_exists('apply_filters')) {
        $routeResponse = apply_filters('public_route', null, $pathInfo);
        if ($routeResponse !== null && $routeResponse !== '') {
            echo $routeResponse;
            exit;
        }
    }
}

// Detect post type slug + post slug from query or path
$postTypeSlug = $_GET['type'] ?? $_GET['post_type'] ?? null;
$postSlug = $_GET['slug'] ?? null;
$archiveSegment = null; // one path segment to try as archive (post type slug)

if ($postTypeSlug === null || $postSlug === null) {
    if ($pathInfo !== '') {
        $segments = array_values(array_filter(explode('/', $pathInfo)));
        if (count($segments) >= 2) {
            // Clean group handles with subpages, e.g. /{handle}/dashboard or /{handle}/members
            // Must be resolved BEFORE generic "post type + slug" handling to avoid 404s.
            if (class_exists('Simuze_Group')) {
                try {
                    $maybeGroup = \Simuze_Group::get_by_unique_handle($segments[0]);
                } catch (\Throwable $e) {
                    $maybeGroup = null;
                }
                if ($maybeGroup && function_exists('apply_filters')) {
                    $routeResponse = apply_filters('public_route', null, $pathInfo);
                    if ($routeResponse !== null && $routeResponse !== '') {
                        echo $routeResponse;
                        exit;
                    }
                }
            }

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
                                if ($theme === 'simuze' && is_dir($theme_path . '/inc')) {
                                    $GLOBALS['simuze_theme_path'] = $theme_path;
                                    $GLOBALS['route_segment'] = $taxSlug;
                                    if (!function_exists('get_header')) {
                                        require_once $theme_path . '/inc/modular-bridge.php';
                                    }
                                }
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
            // Music sub-routes: /music/albums, /music/tracks
            if (count($segments) >= 2 && $segments[0] === 'music' && in_array($segments[1], ['albums', 'tracks'], true)) {
                $musicSubTemplate = 'template_' . $segments[1];
                $themes = ['simuze', 'MDLR-2025', $activeTheme];
                foreach ($themes as $theme) {
                    $theme_path = THEMES_PATH . '/' . $theme;
                    if (is_file($theme_path . '/' . $musicSubTemplate . '.php')) {
                        $route_segment = $segments[1];
                        $route_parent = 'music';
                        if ($theme === 'simuze' && is_dir($theme_path . '/inc')) {
                            $GLOBALS['simuze_theme_path'] = $theme_path;
                            $GLOBALS['route_segment'] = $route_segment;
                            if (!function_exists('get_header')) {
                                require_once $theme_path . '/inc/modular-bridge.php';
                            }
                        }
                        include $theme_path . '/' . $musicSubTemplate . '.php';
                        exit;
                    }
                }
            }
            $postTypeSlug = $segments[0];
            $postSlug = $segments[1];
        } elseif (count($segments) === 1) {
            $slugOnly = $segments[0];
            // Fixed route: one segment → load theme template (e.g. /music → template_music.php, /dashboard → template_dashboard)
            if (isset($fixedRoutes[$slugOnly])) {
                $templateName = $fixedRoutes[$slugOnly];
                // Prefer simuze for network so groups/group types and map load correctly
                $themes = ($slugOnly === 'network') ? ['simuze', 'MDLR-2025', $activeTheme] : ['MDLR-2025', 'simuze', $activeTheme];
                foreach ($themes as $theme) {
                    $theme_path = THEMES_PATH . '/' . $theme;
                    $templateFile = $theme_path . '/' . $templateName . '.php';
                    if (is_file($templateFile)) {
                        $route_segment = $slugOnly;
                        if ($theme === 'simuze' && is_dir($theme_path . '/inc')) {
                            $GLOBALS['simuze_theme_path'] = $theme_path;
                            $GLOBALS['route_segment'] = $route_segment;
                            if (!function_exists('get_header')) {
                                require_once $theme_path . '/inc/modular-bridge.php';
                            }
                            // Page-like routes: set simuze_posts from page by slug so template can show content
                            $pageSlugRoutes = ['about' => 'template_about', 'weblog' => 'template_weblog', 'howto' => 'template_howto'];
                            if (isset($pageSlugRoutes[$slugOnly]) && isset($GLOBALS['db']) && function_exists('get_post')) {
                                $pagePost = get_post(null, $slugOnly);
                                if ($pagePost && isset($pagePost['type_slug']) && $pagePost['type_slug'] === 'page') {
                                    $GLOBALS['simuze_posts'] = [$pagePost];
                                }
                            }
                        }
                        // Ensure simuze-community module is loaded for network page (groups + group types filter)
                        if ($slugOnly === 'network' && defined('MODULES_PATH')) {
                            // Run pending migrations so simuze_group_types / simuze_groups exist (e.g. 004_simuze_community_tables)
                            if (isset($GLOBALS['db']) && function_exists('run_pending_migrations')) {
                                run_pending_migrations();
                            }
                            if (!class_exists('Simuze_Group')) {
                                $mod = MODULES_PATH . '/simuze-community/module.php';
                                if (is_file($mod)) {
                                    require_once $mod;
                                }
                            }
                            // Run module migrations so default group types are present
                            if (class_exists('Simuze_Database') && isset($GLOBALS['db'])) {
                                Simuze_Database::get_instance()->maybe_run_migrations();
                            }
                        }
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
        AND (p.status = 'published' OR (p.status = 'scheduled' AND p.published_at IS NOT NULL AND p.published_at <= NOW()))
    ");
    $stmt->execute([$postTypeSlug, $postSlug]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$row) {
        foreach ([$activeTheme, 'MDLR-2025'] as $theme) {
            $theme_path = THEMES_PATH . '/' . $theme;
            if (is_file($theme_path . '/404.php')) {
                if ($theme === 'simuze' && is_dir($theme_path . '/inc')) {
                    require_once $theme_path . '/inc/modular-bridge.php';
                }
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
                    if ($theme === 'simuze' && is_dir(THEMES_PATH . '/simuze/inc')) {
                        require_once THEMES_PATH . '/simuze/inc/modular-bridge.php';
                    }
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
            if ($theme === 'simuze' && is_dir($theme_path . '/inc')) {
                $GLOBALS['simuze_theme_path'] = $theme_path;
                if (!function_exists('get_header')) require_once $theme_path . '/inc/modular-bridge.php';
            }
            if (is_array($post) && function_exists('_simuze_post_array_to_object')) {
                $post = _simuze_post_array_to_object($post);
                $GLOBALS['post'] = $post;
            }
            include $theme_path . '/' . $post_template . '.php';
            exit;
        }
        $single_type = $theme_path . '/single-' . $post_type_slug . '.php';
        if (is_file($single_type)) {
            if ($theme === 'simuze' && is_dir($theme_path . '/inc')) {
                $GLOBALS['simuze_theme_path'] = $theme_path;
                if (!function_exists('get_header')) require_once $theme_path . '/inc/modular-bridge.php';
            }
            if (is_array($post) && function_exists('_simuze_post_array_to_object')) {
                $post = _simuze_post_array_to_object($post);
                $GLOBALS['post'] = $post;
            }
            include $single_type;
            exit;
        }
        if (is_file($theme_path . '/single.php')) {
            if ($theme === 'simuze' && is_dir($theme_path . '/inc')) {
                $GLOBALS['simuze_theme_path'] = $theme_path;
                if (!function_exists('get_header')) require_once $theme_path . '/inc/modular-bridge.php';
            }
            if (is_array($post) && function_exists('_simuze_post_array_to_object')) {
                $post = _simuze_post_array_to_object($post);
                $GLOBALS['post'] = $post;
            }
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
                if ($theme === 'simuze' && is_dir($theme_path . '/inc')) {
                    require_once $theme_path . '/inc/modular-bridge.php';
                }
                include $archive_type;
                exit;
            }
            if (is_file($theme_path . '/archive.php')) {
                if ($theme === 'simuze' && is_dir($theme_path . '/inc')) {
                    require_once $theme_path . '/inc/modular-bridge.php';
                }
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

// Home: respect front page setting (page or blog index)
$front_page_id = function_exists('get_front_page_id') ? get_front_page_id() : 0;
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
if ($activeTheme === 'simuze' && is_dir($theme_path . '/inc')) {
    $GLOBALS['simuze_theme_path'] = $theme_path;
    require_once $theme_path . '/inc/modular-bridge.php';
    if (isset($GLOBALS['db'])) {
        if ($front_page_id > 0 && function_exists('get_post')) {
            $front_post = get_post($front_page_id);
            if ($front_post) {
                $page_title = $front_post['title'] ?? $page_title;
                $GLOBALS['simuze_posts'] = [$front_post];
            } else {
                $GLOBALS['simuze_posts'] = get_posts(['post_type' => 'post', 'posts_per_page' => 10]);
            }
        } else {
            $home_post = function_exists('get_post') ? get_post(null, 'home') : null;
            if ($home_post && isset($home_post['type_slug']) && $home_post['type_slug'] === 'page') {
                $page_title = $home_post['title'] ?? $page_title;
                $GLOBALS['simuze_posts'] = [$home_post];
            } else {
                $GLOBALS['simuze_posts'] = get_posts(['post_type' => 'post', 'posts_per_page' => 10]);
            }
        }
    } else {
        $GLOBALS['simuze_posts'] = [];
    }
}

// If the active theme provides a dedicated homepage template, prefer it
if (is_file($theme_path . '/template_homepage.php')) {
    require $theme_path . '/template_homepage.php';
    exit;
}

if (is_file($theme_path . '/index.php')) {
    require $theme_path . '/index.php';
} else {
    require THEMES_PATH . '/default/index.php';
}
