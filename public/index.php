<?php
/**
 * Public front entry point.
 * - Home: no path or path /
 * - Single: ?type=&slug= or PATH_INFO /type_slug/post_slug (two segments), or one segment → post by slug (prefer "page")
 * - Archive: one segment = post type slug → archive-{type}.php or archive.php
 */

// Start session before any output to prevent session_start() warnings from csrf.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
if (is_file(MODULAR_PATH . '/core/functions/http.php')) {
    require_once MODULAR_PATH . '/core/functions/http.php';
}
if (is_file(MODULAR_PATH . '/core/functions/database.php')) {
    require_once MODULAR_PATH . '/core/functions/database.php';
}
// Load Simuze Community repositories used by theme templates and template-parts
if (!defined('SIMUZE_MODULAR_RUN')) {
    define('SIMUZE_MODULAR_RUN', true);
}
$_sc_inc = MODULAR_PATH . '/public/modules/simuze-community/includes';
foreach (['class-music-repository', 'class-user-repository', 'class-group-repository', 'class-group', 'class-user', 'class-auth', 'class-event-repository', 'class-event-ticketing', 'class-venue-repository'] as $_repo) {
    if (is_file($_sc_inc . '/' . $_repo . '.php')) {
        require_once $_sc_inc . '/' . $_repo . '.php';
    }
}
unset($_sc_inc, $_repo);
if (is_file(MODULAR_PATH . '/core/functions/template-helpers.php')) {
    require_once MODULAR_PATH . '/core/functions/template-helpers.php';
}
if (is_file(MODULAR_PATH . '/core/functions/cache.php')) {
    require_once MODULAR_PATH . '/core/functions/cache.php';
}
// Promote any scheduled posts whose publication time has arrived (lazy, throttled).
if (function_exists('md_publish_due_posts')) {
    md_publish_due_posts();
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

// Load theme functions.php early so theme helpers are available before include_header()
$_themeFunctionsFile = THEMES_PATH . '/' . $activeTheme . '/functions.php';
if (is_file($_themeFunctionsFile)) {
    require_once $_themeFunctionsFile;
}
unset($_themeFunctionsFile);

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
    'shows'     => 'template_shows',
    'board'     => 'template_board',
    'messages'  => 'template_messages',
    'music'     => 'template_music',
    'network'   => 'template_network',
    'live'      => 'template_live',
    'weblog'    => 'template_weblog',
    'faq'       => 'template_faq',
    'dashboard' => 'template_dashboard',
    'login'            => 'template_login',
    'signup'           => 'template_signup',
    'profile'          => 'template_profile',
    'forgot-password'  => 'template_forgot_password',
    'account'          => 'template_account',
];
if (function_exists('apply_filters')) {
    $fixedRoutes = apply_filters('public_fixed_routes', $fixedRoutes);
}

// Fixed routes must be checked BEFORE permalink resolution — otherwise posts with
// matching slugs (e.g. a page with slug "network") intercept the route first.
if ($pathInfo !== '') {
    $_frSegs = array_values(array_filter(explode('/', $pathInfo)));
    // /dashboard → redirect to /user/{username} or /login
    if (count($_frSegs) === 1 && $_frSegs[0] === 'dashboard') {
        if (!empty($GLOBALS['is_logged_in']) && !empty($GLOBALS['current_user'])) {
            $uname = $GLOBALS['current_user']->username ?? ($GLOBALS['current_user']->unique_identifier ?? null);
            if ($uname) {
                header('Location: ' . md_home_url('/user/' . rawurlencode($uname) . '/'), true, 302);
            } else {
                header('Location: ' . md_home_url('/login'), true, 302);
            }
        } else {
            header('Location: ' . md_home_url('/login'), true, 302);
        }
        exit;
    }
    if (count($_frSegs) === 1 && isset($fixedRoutes[$_frSegs[0]])) {
        $templateName = $fixedRoutes[$_frSegs[0]];
        $themes = ['MDLR-2025', 'simuze', $activeTheme];
        foreach ($themes as $theme) {
            $theme_path = THEMES_PATH . '/' . $theme;
            $templateFile = $theme_path . '/' . $templateName . '.php';
            if (is_file($templateFile)) {
                $route_segment = $_frSegs[0];
                include $templateFile;
                exit;
            }
        }
    }
    // /dashboard/tickets → user ticket history
    if (count($_frSegs) === 2 && $_frSegs[0] === 'dashboard' && $_frSegs[1] === 'tickets') {
        foreach (['simuze', $activeTheme] as $_theme) {
            $_tpl = THEMES_PATH . '/' . $_theme . '/template_dashboard_tickets.php';
            if (is_file($_tpl)) { include $_tpl; exit; }
        }
        unset($_theme, $_tpl);
    }
    // /dashboard/shows/{id} → organiser dashboard
    if (count($_frSegs) === 3 && $_frSegs[0] === 'dashboard' && $_frSegs[1] === 'shows' && ctype_digit((string) $_frSegs[2])) {
        $_show_id = (int) $_frSegs[2];
        foreach (['simuze', $activeTheme] as $_theme) {
            $_tpl = THEMES_PATH . '/' . $_theme . '/template_dashboard_show_organiser.php';
            if (is_file($_tpl)) { include $_tpl; exit; }
        }
        unset($_theme, $_tpl, $_show_id);
    }
    // /shows/add → dedicated show creation page
    if (count($_frSegs) === 2 && $_frSegs[0] === 'shows' && $_frSegs[1] === 'add') {
        foreach (['simuze', $activeTheme] as $_theme) {
            $_tpl = THEMES_PATH . '/' . $_theme . '/template_shows_add.php';
            if (is_file($_tpl)) {
                include $_tpl;
                exit;
            }
        }
        unset($_theme, $_tpl);
    }
    // Legacy /shows/start → /shows/add (preserve query string)
    if (count($_frSegs) === 2 && $_frSegs[0] === 'shows' && $_frSegs[1] === 'start') {
        $_qs = isset($_SERVER['QUERY_STRING']) ? trim((string) $_SERVER['QUERY_STRING']) : '';
        $_shows_add_redirect = md_home_url('/shows/add') . ($_qs !== '' ? '?' . $_qs : '');
        header('Location: ' . $_shows_add_redirect, true, 301);
        exit;
    }
    // /shows/{slug}/edit → edit an existing show (reuses template_shows_add.php in edit mode)
    if (count($_frSegs) === 3 && $_frSegs[0] === 'shows' && $_frSegs[2] === 'edit') {
        $_editSlug = $_frSegs[1];
        if (class_exists('EventRepository')) {
            $edit_show = EventRepository::findBySlug($_editSlug);
        }
        if (!empty($edit_show)) {
            foreach (['simuze', $activeTheme] as $_theme) {
                $_tpl = THEMES_PATH . '/' . $_theme . '/template_shows_add.php';
                if (is_file($_tpl)) {
                    include $_tpl;
                    exit;
                }
            }
        } else {
            // Show not found — redirect to shows list
            header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/shows');
            exit;
        }
        unset($_theme, $_tpl, $_editSlug);
    }
    // /board/post → post creation form (served by template_board.php with ?mode=post)
    if (count($_frSegs) === 2 && $_frSegs[0] === 'board' && $_frSegs[1] === 'post') {
        $_GET['mode'] = 'post';
        foreach (['simuze', $activeTheme] as $_theme) {
            $_tpl = THEMES_PATH . '/' . $_theme . '/template_board.php';
            if (is_file($_tpl)) {
                include $_tpl;
                exit;
            }
        }
        unset($_theme, $_tpl);
    }
    // /board/{numeric_id} → single post view (template_board.php reads $_GET['board_id'])
    if (count($_frSegs) === 2 && $_frSegs[0] === 'board' && ctype_digit((string) $_frSegs[1])) {
        $_GET['board_id'] = (int) $_frSegs[1];
        foreach (['simuze', $activeTheme] as $_theme) {
            $_tpl = THEMES_PATH . '/' . $_theme . '/template_board.php';
            if (is_file($_tpl)) {
                include $_tpl;
                exit;
            }
        }
        unset($_theme, $_tpl);
    }
    // /messages/new → compose reply (template_messages.php; use ?board_post=id)
    if (count($_frSegs) === 2 && $_frSegs[0] === 'messages' && $_frSegs[1] === 'new') {
        foreach (['simuze', $activeTheme] as $_theme) {
            $_tpl = THEMES_PATH . '/' . $_theme . '/template_messages.php';
            if (is_file($_tpl)) {
                include $_tpl;
                exit;
            }
        }
        unset($_theme, $_tpl);
    }
    unset($_frSegs);
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

// Simuze-specific routes: pretty group URLs, pretty music URLs, music/album/{slug}, music/track/{slug}, group/{slug}[/*]
if ($pathInfo !== '') {
    $segsCheck = array_values(array_filter(explode('/', $pathInfo)));

    // Known group sub-page tabs (used for pretty URLs /{group_slug}/{tab})
    $_groupTabs = ['music','members','activity','artists','discography','dashboard','settings','sales','shows'];

    // /{group_slug} → group public profile (single-segment pretty URL)
    // /{group_slug}/{tab} → group sub-page (two-segment pretty URL where tab is a known group tab)
    // These run before album/track lookup so /boyle/ and /boyle/music/ resolve correctly.
    if (count($segsCheck) >= 1) {
        $_reservedSlugs = ['shows','board','messages','music','network','live','weblog','dashboard','login','signup','profile','forgot-password','account','group','show','start-a-show','city','user','documentation','admin','api','about','howto','store','subscription'];
        $_potentialGroup = $segsCheck[0];
        $_potentialTab   = isset($segsCheck[1]) ? $segsCheck[1] : '';
        // Only intercept when: first segment is not reserved AND (only 1 segment OR second segment is a known group tab)
        if (!in_array($_potentialGroup, $_reservedSlugs, true) && ($_potentialTab === '' || in_array($_potentialTab, $_groupTabs, true))) {
            $_grpPath = 'group/' . $_potentialGroup . ($_potentialTab !== '' ? '/' . $_potentialTab : '');
            if (function_exists('apply_filters')) {
                $_grpResp = apply_filters('public_route', null, $_grpPath);
                if ($_grpResp !== null && $_grpResp !== '') {
                    echo $_grpResp;
                    exit;
                }
            }
        }
        unset($_reservedSlugs, $_potentialGroup, $_potentialTab, $_grpPath, $_grpResp);
    }

    // Pretty music URLs:
    // - /{artist_slug}/{album_slug}
    // - /{artist_slug}/{album_slug}/{track_slug}
    if (count($segsCheck) >= 2) {
        $reservedFirst = ['music','network','live','weblog','dashboard','login','signup','profile','group','show','start-a-show','city','user','documentation','admin','api'];
        $firstSeg = $segsCheck[0];
        // Also skip if second segment is a group tab (already handled above)
        if (!in_array($firstSeg, $reservedFirst, true) && !in_array($segsCheck[1], $_groupTabs, true) && class_exists('MusicRepository')) {
            $artistSlug = $firstSeg;
            $albumSlug  = $segsCheck[1];
            $albumTpl   = THEMES_PATH . '/' . $activeTheme . '/template-parts/view-album.php';
            $trackTpl   = THEMES_PATH . '/' . $activeTheme . '/template-parts/view-track.php';

            // /{artist}/{album}/{track} → track view
            if (count($segsCheck) >= 3 && is_file($trackTpl)) {
                $trackSlug = $segsCheck[2];
                $trackRow = null;
                try {
                    $sql = "
                        SELECT u.*
                        FROM md_music_uploads u
                        INNER JOIN md_music_albums a ON u.album_id = a.id
                        INNER JOIN simuze_groups g ON a.group_id = g.id
                        WHERE g.unique_name = ?
                          AND a.album_nicename = ?
                          AND LOWER(REPLACE(REPLACE(u.song_name,' ','-'),\"'\",'')) = ?
                          AND u.deleted_at IS NULL
                        LIMIT 1
                    ";
                    $trackRow = md_db_row($sql, [$artistSlug, $albumSlug, $trackSlug]);
                } catch (\Throwable $e) {
                    $trackRow = null;
                }
                if ($trackRow) {
                    $item_id = (int) $trackRow['id'];
                    $item_type = 'track';
                    include $trackTpl;
                    exit;
                }
            }

            // /{artist}/{album} → album or single view (respect is_album flag)
            if (is_file($albumTpl)) {
                $albumRow = \MusicRepository::findAlbumByArtistAndSlug($artistSlug, $albumSlug);
                if ($albumRow) {
                    $item_type = !empty($albumRow['is_album']) ? 'album' : 'single';
                    $item_id   = (int) $albumRow['id'];
                    if (!empty($albumRow['group_id']) && empty($group_id)) {
                        $group_id = (int) $albumRow['group_id'];
                    }
                    include $albumTpl;
                    exit;
                }
            }
        }
    }
    // music/albums/ — albums discovery page (/music/albums)
    if (count($segsCheck) >= 1 && $segsCheck[0] === 'music' && ($segsCheck[1] ?? '') === 'albums') {
        $tplFile = THEMES_PATH . '/' . $activeTheme . '/template_albums.php';
        if (is_file($tplFile)) { include $tplFile; exit; }
    }
    // music/tracks/ — tracks discovery page
    if (count($segsCheck) >= 1 && $segsCheck[0] === 'music' && ($segsCheck[1] ?? '') === 'tracks') {
        $tplFile = THEMES_PATH . '/' . $activeTheme . '/template_tracks.php';
        if (is_file($tplFile)) { include $tplFile; exit; }
    }
    // music/album/{slug} — 301 redirect to canonical /{group}/{album}
    if (count($segsCheck) >= 3 && $segsCheck[0] === 'music' && $segsCheck[1] === 'album') {
        $albumSlug = $segsCheck[2];
        if (isset($GLOBALS['db'])) {
            $albumRow = md_db_row(
                "SELECT a.album_nicename, g.unique_name
                 FROM md_music_albums a
                 JOIN simuze_groups g ON g.id = a.group_id
                 WHERE a.album_nicename = ? AND a.deleted_at IS NULL LIMIT 1",
                [$albumSlug]
            );
            if ($albumRow && !empty($albumRow['unique_name'])) {
                header('Location: ' . md_home_url('/' . $albumRow['unique_name'] . '/' . $albumRow['album_nicename']), true, 301);
                exit;
            }
        }
        // Album not found or no group — fall through to 404
    }
    // music/track/{slug}
    if (count($segsCheck) >= 3 && $segsCheck[0] === 'music' && $segsCheck[1] === 'track') {
        $trackSlug = $segsCheck[2];
        $trackTpl  = THEMES_PATH . '/' . $activeTheme . '/template-parts/view-track.php';
        if (is_file($trackTpl) && isset($GLOBALS['db'])) {
            $trackRow = $GLOBALS['db']->prepare("SELECT id FROM md_music_uploads WHERE LOWER(REPLACE(REPLACE(song_name,' ','-'),\"'\",'')) = ? AND deleted_at IS NULL LIMIT 1");
            $trackRow->execute([$trackSlug]);
            $trackRow = $trackRow->fetch(\PDO::FETCH_ASSOC);
            if ($trackRow) {
                $item_id   = (int) $trackRow['id'];
                $item_type = 'track';
                include $trackTpl;
                exit;
            }
        }
    }
    // /group/{slug}[/{tab}] → 301 redirect to canonical pretty URL (no /group/ prefix in Simuze)
    if (count($segsCheck) >= 2 && $segsCheck[0] === 'group') {
        if (class_exists('Simuze_Group')) {
            $group = Simuze_Group::get_by_unique_name($segsCheck[1]);
            if ($group) {
                $canonical = rtrim($group->get_url(), '/');
                $tab = $segsCheck[2] ?? '';
                if ($tab !== '' && in_array($tab, $_groupTabs, true)) {
                    $canonical .= '/' . $tab;
                }
                $_qs_g = isset($_SERVER['QUERY_STRING']) ? trim((string) $_SERVER['QUERY_STRING']) : '';
                header('Location: ' . $canonical . '/' . ($_qs_g !== '' ? '?' . $_qs_g : ''), true, 301);
                exit;
            }
        }
        // Group not found — fall through to 404
    }
    // show/{slug} — event detail page
    if (count($segsCheck) >= 2 && $segsCheck[0] === 'show') {
        $showSlug = $segsCheck[1];
        $showTpl  = THEMES_PATH . '/' . $activeTheme . '/template-parts/view-show.php';
        if (is_file($showTpl) && class_exists('EventRepository')) {
            $show = EventRepository::findBySlug($showSlug);
            if ($show) {
                include $showTpl;
                exit;
            }
        }
    }
    // start-a-show — redirect to /shows (form is now embedded there)
    if (count($segsCheck) >= 1 && $segsCheck[0] === 'start-a-show') {
        $_qs = isset($_SERVER['QUERY_STRING']) ? trim((string) $_SERVER['QUERY_STRING']) : '';
        $_target = md_home_url('/shows/add') . ($_qs !== '' ? '?' . $_qs : '');
        header('Location: ' . $_target);
        exit;
    }
    // city/{slug} — city scene hub page
    if (count($segsCheck) >= 2 && $segsCheck[0] === 'city') {
        $citySlug = $segsCheck[1];
        $cityTpl  = THEMES_PATH . '/' . $activeTheme . '/template-parts/view-city.php';
        if (is_file($cityTpl) && class_exists('VenueRepository')) {
            $_qs = isset($_SERVER['QUERY_STRING']) ? trim((string) $_SERVER['QUERY_STRING']) : '';

            // Canonicalize numeric duplicate slugs, e.g. /city/utrecht-1 -> /city/utrecht
            if (is_string($citySlug) && preg_match('/^(.*)-(\d+)$/', $citySlug, $m)) {
                $baseSlug = trim((string) ($m[1] ?? ''));
                if ($baseSlug !== '' && $baseSlug !== $citySlug) {
                    $baseCity = VenueRepository::findCityBySlug($baseSlug);
                    if ($baseCity) {
                        $city = VenueRepository::findCityBySlug($citySlug);
                        $sameName = $city && !empty($city['name']) && !empty($baseCity['name'])
                            ? (strcasecmp((string) $city['name'], (string) $baseCity['name']) === 0)
                            : true; // If we can't compare, still redirect to canonical base slug.
                        if ($sameName) {
                            header('Location: ' . md_home_url('/city/' . $baseSlug) . ($_qs !== '' ? '?' . $_qs : ''), true, 301);
                            exit;
                        }
                    }
                }
            }

            $city = VenueRepository::findCityBySlug($citySlug);
            if ($city) {
                include $cityTpl;
                exit;
            }
        }
    }
    // province/{slug} — province hub page (uses simuze_groups)
    if (count($segsCheck) >= 2 && $segsCheck[0] === 'province') {
        $provinceSlug = $segsCheck[1];
        $provinceTpl  = THEMES_PATH . '/' . $activeTheme . '/template-parts/view-province.php';
        if (is_file($provinceTpl) && class_exists('Simuze_Group')) {
            $province = Simuze_Group::findByTypeAndSlug('province', $provinceSlug);
            if ($province) {
                include $provinceTpl;
                exit;
            }
        }
    }
    // country/{slug} — country hub page (uses simuze_groups)
    if (count($segsCheck) >= 2 && $segsCheck[0] === 'country') {
        $countrySlug = $segsCheck[1];
        $countryTpl  = THEMES_PATH . '/' . $activeTheme . '/template_country.php';
        if (is_file($countryTpl) && class_exists('Simuze_Group')) {
            $_qs = isset($_SERVER['QUERY_STRING']) ? trim((string) $_SERVER['QUERY_STRING']) : '';

            // Canonicalize Netherlands country slug to /country/the-netherlands
            $cs = strtolower(trim((string) $countrySlug));
            if ($cs === 'netherlands' || $cs === 'nederland') {
                header('Location: ' . md_home_url('/country/the-netherlands') . ($_qs !== '' ? '?' . $_qs : ''), true, 301);
                exit;
            }

            $group = Simuze_Group::findByTypeAndSlug('country', $countrySlug);
            // Fallback: sometimes callers use unique_name instead of slug.
            if (!$group) {
                $candidate = Simuze_Group::get_by_unique_name($countrySlug);
                if ($candidate) {
                    $gt = $candidate->get_group_type();
                    if (!empty($gt) && !empty($gt['slug']) && $gt['slug'] === 'country') {
                        $group = $candidate;
                    }
                }
            }
            if ($group) {
                include $countryTpl;
                exit;
            }
        }
    }
    // user/{username}[/tab] — route to module's user/profile template
    if (count($segsCheck) >= 2 && $segsCheck[0] === 'user') {
        if (function_exists('apply_filters')) {
            $userResponse = apply_filters('public_route', null, $pathInfo);
            if ($userResponse !== null && $userResponse !== '') {
                echo $userResponse;
                exit;
            }
        }
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
                    WHERE p.slug = ? AND p.deleted_at IS NULL
                    AND $md_status_sql
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
// If a static homepage is configured, use the same template structure as the default
// home, but inject the selected post's content so CSS/layout/navigation stay identical.
if ($pathInfo === '' || $pathInfo === '/') {
    if (!isset($GLOBALS['db'])) {
        require_once MODULAR_PATH . '/admin/config/config.php';
        require_once MODULAR_PATH . '/core/functions/database.php';
        if (function_exists('run_pending_migrations') && isset($GLOBALS['db'])) {
            run_pending_migrations();
        }
    }
    if (isset($GLOBALS['db'])) {
        // Load settings helper
        if (!function_exists('get_setting') && is_file(MODULAR_PATH . '/core/functions/template-helpers.php')) {
            require_once MODULAR_PATH . '/core/functions/template-helpers.php';
        }
        $frontType = function_exists('get_setting') ? get_setting('front_page_type', 'index') : 'index';
        $frontId = (int) (function_exists('get_setting') ? get_setting('front_page_id', 0) : 0);
        if ($frontType === 'page' && $frontId > 0) {
            require_once MODULAR_PATH . '/core/fields/FieldRegistry.php';
            \Modular\Fields\FieldRegistry::bootDefaultFields();
            require_once MODULAR_PATH . '/core/PostTypeResolver.php';
            $resolver = new \Modular\PostTypeResolver($GLOBALS['db']);
            $resolved = $resolver->resolvePost($frontId, ['resolve_relations' => true, 'include_author' => true]);
            if (!isset($resolved['error'])) {
                $post = $resolved;
                // Attach raw data column so templates can read field values directly
                if (!isset($post['data'])) {
                    try {
                        $rawStmt = $GLOBALS['db']->prepare("SELECT data FROM posts WHERE id = ? LIMIT 1");
                        $rawStmt->execute([$frontId]);
                        $rawRow = $rawStmt->fetch(\PDO::FETCH_ASSOC);
                        $post['data'] = ($rawRow && !empty($rawRow['data'])) ? json_decode($rawRow['data'], true) : [];
                    } catch (\Throwable $e) {
                        $post['data'] = [];
                    }
                }
                $page_title = isset($post['title']) ? $post['title'] : 'Home';
                $front_page_template = isset($post['template']) ? trim((string) $post['template']) : '';

                // Build simple HTML content from component_data or excerpt/body,
                // similar to the single.php template but rendered into $content.
                $content_html = '';
                if (!empty($post['component_data']) && is_string($post['component_data'])) {
                    $post['component_data'] = json_decode($post['component_data'], true);
                }
                if (!empty($post['component_data']) && is_array($post['component_data'])) {
                    ob_start();
                    echo '<div class="content-area">';
                    foreach ($post['component_data'] as $block) {
                        if (empty($block['type'])) {
                            continue;
                        }
                        echo '<div class="block block-' . htmlspecialchars($block['type'], ENT_QUOTES, 'UTF-8') . '">';
                        if (!empty($block['data']['content'])) {
                            echo $block['data']['content'];
                        } elseif (!empty($block['data']['html'])) {
                            echo $block['data']['html'];
                        }
                        echo '</div>';
                    }
                    echo '</div>';
                    $content_html = ob_get_clean();
                } elseif (!empty($post['excerpt'])) {
                    $content_html = '<p class="lead">' . htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8') . '</p>';
                } elseif (!empty($post['title'])) {
                    $content_html = '<p>' . htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') . '</p>';
                }

                if ($content_html !== '') {
                    $content = $content_html;
                }
            }
        }
    }
}

// Default Modular home (fallback when no static page is configured or resolution fails)
if (!isset($page_title)) {
    $page_title = 'Home';
}
if (!isset($content)) {
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
}
$theme_path = THEMES_PATH . '/' . $activeTheme;
if (isset($front_page_template) && $front_page_template !== '' && is_file($theme_path . '/' . $front_page_template . '.php')) {
    require $theme_path . '/' . $front_page_template . '.php';
    exit;
}
if (is_file($theme_path . '/index.php')) {
    require $theme_path . '/index.php';
} else {
    require THEMES_PATH . '/default/index.php';
}
