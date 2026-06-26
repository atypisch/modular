<?php
/**
 * Public template helper functions (SSR). Use in themes only.
 * All output must be escaped in templates; these return raw data.
 */

if (!function_exists('get_theme_templates')) {
    /**
     * Discover page templates from the active theme (WordPress-style).
     * Scans theme PHP files for block comments containing "Template Name: Display Name".
     *
     * @return array [ '' => 'Default', 'template_filename' => 'Display Name', ... ]
     */
    function get_theme_templates(): array {
        $templates = [ '' => 'Default' ];
        $themesPath = defined('THEMES_PATH') ? THEMES_PATH : (defined('PUBLIC_PATH') ? PUBLIC_PATH . '/themes' : '');
        $activeTheme = defined('ACTIVE_THEME') ? ACTIVE_THEME : (defined('DEFAULT_THEME') ? DEFAULT_THEME : 'default');
        if ($themesPath === '' || $activeTheme === '') {
            return $templates;
        }
        $themeDir = rtrim($themesPath, '/') . '/' . $activeTheme;
        if (!is_dir($themeDir)) {
            return $templates;
        }
        $files = glob($themeDir . '/*.php');
        if (!is_array($files)) {
            return $templates;
        }
        foreach ($files as $path) {
            $basename = basename($path, '.php');
            $content = @file_get_contents($path, false, null, 0, 2048);
            if ($content === false) {
                continue;
            }
            if (preg_match('/\/\*\*?\s*(.*?)\*\//s', $content, $block) && preg_match('/Template\s+Name\s*:\s*([^\n*]+)/i', $block[1], $name)) {
                $displayName = trim(preg_replace('/[\s*\r\n]+/', ' ', $name[1]));
                $templates[$basename] = $displayName !== '' ? $displayName : $basename;
            }
        }
        asort($templates);
        $templates = [ '' => 'Default' ] + $templates;
        return $templates;
    }
}

if (!function_exists('theme_uri')) {
    function theme_uri(string $path = ''): string {
        $base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
        $theme = defined('ACTIVE_THEME') ? ACTIVE_THEME : (defined('DEFAULT_THEME') ? DEFAULT_THEME : 'default');
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($script !== '' && strpos($script, '/public/') === false) {
            $base .= '/public';
        }
        return $base . '/themes/' . $theme . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('get_setting')) {
    /** Get a single setting value by option_name. Returns default if not found. */
    function get_setting(string $option_name, $default = null) {
        if (!isset($GLOBALS['db'])) return $default;
        try {
            $stmt = $GLOBALS['db']->prepare("SELECT option_value FROM settings WHERE option_name = ? LIMIT 1");
            $stmt->execute([$option_name]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row && $row['option_value'] !== null && $row['option_value'] !== '' ? $row['option_value'] : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('get_site_name')) {
    function get_site_name(): string {
        $v = function_exists('get_setting') ? get_setting('site_name', 'Modular') : 'Modular';
        return (string) $v;
    }
}

if (!function_exists('is_front_page')) {
    /** True when the current request is the site front page (path empty or single slash). */
    function is_front_page(): bool {
        $path = function_exists('current_path') ? current_path() : '';
        return $path === '' || $path === '/';
    }
}

if (!function_exists('get_front_page_id')) {
    /** Post ID of the page set as homepage, or 0 if not set / blog index. */
    function get_front_page_id(): int {
        if (!function_exists('get_setting')) return 0;
        if (get_setting('front_page_type', 'index') !== 'page') return 0;
        $id = get_setting('front_page_id', 0);
        return $id ? (int) $id : 0;
    }
}

if (!function_exists('base_url')) {
    /** Site base URL (no trailing slash). Use for links. */
    function base_url(string $path = ''): string {
        $base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
        return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('trailing_slash')) {
    function trailing_slash(string $url): string {
        return rtrim($url, '/') . '/';
    }
}

if (!function_exists('get_permalink')) {
    /** Permalink for a post by ID. Returns base_url/slug. */
    function get_permalink(int $post_id): string {
        $post = function_exists('get_post') ? get_post($post_id) : null;
        return $post && !empty($post['slug']) ? base_url('/' . $post['slug']) : base_url('/');
    }
}

if (!function_exists('get_pages')) {
    /** Published pages for menus/links. Returns array of objects with ID, post_title, post_name. */
    function get_pages(array $args = []): array {
        if (!isset($GLOBALS['db'])) return [];
        try {
            $stmt = $GLOBALS['db']->query("
                SELECT p.id, p.title, p.slug FROM posts p
                INNER JOIN post_types pt ON p.post_type_id = pt.id
                WHERE pt.slug = 'page' AND p.deleted_at IS NULL AND p.status = 'published'
                ORDER BY p.`order` ASC, p.title ASC
            ");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $o = new \stdClass();
                $o->ID = (int) $r['id'];
                $o->post_title = $r['title'];
                $o->post_name = $r['slug'];
                $out[] = $o;
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('current_path')) {
    /** Current request path (no query string), relative to site base. */
    function current_path(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = strpos($uri, '?') !== false ? strstr($uri, '?', true) : $uri;
        $path = trim($path, '/');
        $base = defined('BASE_PATH') ? trim(BASE_PATH, '/') : '';
        if ($base !== '' && strpos($path, $base) === 0) {
            $path = trim(substr($path, strlen($base)), '/');
        }
        return $path;
    }
}

// -------------------------------------------------------------------------
// WP-style helper aliases – generic, theme-agnostic
// -------------------------------------------------------------------------

if (!function_exists('home_url')) {
    /** Alias for base_url(), kept for theme compatibility. */
    function home_url(string $path = ''): string {
        return base_url($path);
    }
}

if (!function_exists('get_template_directory_uri')) {
    /** URL to the active theme directory (similar to WP's get_template_directory_uri). */
    function get_template_directory_uri(): string {
        return theme_uri('');
    }
}

if (!function_exists('get_template_directory')) {
    /** Filesystem path to the active theme directory. */
    function get_template_directory(): string {
        $theme = defined('ACTIVE_THEME') ? ACTIVE_THEME : (defined('DEFAULT_THEME') ? DEFAULT_THEME : 'default');
        return (defined('THEMES_PATH') ? rtrim(THEMES_PATH, '/') : '') . '/' . $theme;
    }
}

if (!function_exists('get_template_part')) {
    /**
     * Load a template part from the active theme (template-parts/{slug}-{name}.php).
     * Usage: get_template_part('content', 'page'); // loads template-parts/content-page.php
     */
    function get_template_part(string $slug, string $name = ''): void {
        $baseDir = get_template_directory();
        if ($baseDir === '') return;
        $file = $baseDir . '/template-parts/' . $slug . ($name !== '' ? '-' . $name : '') . '.php';
        if (is_file($file)) {
            include $file;
        }
    }
}

if (!function_exists('esc_url')) {
    function esc_url(string $url): string {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($s): string {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html($s): string {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e(string $text, string $domain = ''): void {
        echo esc_html($text);
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e(string $text, string $domain = ''): void {
        echo esc_attr($text);
    }
}

if (!function_exists('checked')) {
    function checked($checked, $current = true, bool $echo = true) {
        $out = ((string) $checked === (string) $current) ? ' checked="checked"' : '';
        if ($echo) {
            echo $out;
        }
        return $out;
    }
}

if (!function_exists('selected')) {
    function selected($selected, $current = true, bool $echo = true) {
        $out = ((string) $selected === (string) $current) ? ' selected="selected"' : '';
        if ($echo) {
            echo $out;
        }
        return $out;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string {
        return trim(strip_tags($str));
    }
}

if (!function_exists('trailingslashit')) {
    /** Alias for trailing_slash() – kept for theme compatibility. */
    function trailingslashit(string $url): string {
        return trailing_slash($url);
    }
}

if (!function_exists('taxonomy_exists')) {
    function taxonomy_exists(string $taxonomy): bool {
        if (!isset($GLOBALS['db'])) return false;
        try {
            $stmt = $GLOBALS['db']->prepare("SELECT id FROM taxonomies WHERE slug = ? LIMIT 1");
            $stmt->execute([$taxonomy]);
            return $stmt->fetchColumn() !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id(): int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return (int) ($_SESSION['user_id'] ?? 0);
    }
}

if (!function_exists('current_user_can')) {
    /**
     * Minimal capability check: supports 'manage_options' (admin) based on roles.
     */
    function current_user_can(string $capability): bool {
        $uid = get_current_user_id();
        if ($uid <= 0) return false;
        if ($capability === 'manage_options') {
            if (!isset($GLOBALS['db'])) return false;
            try {
                $stmt = $GLOBALS['db']->prepare("
                    SELECT r.id
                    FROM user_roles ur
                    INNER JOIN roles r ON ur.role_id = r.id
                    WHERE ur.user_id = ? AND r.name = 'admin'
                    LIMIT 1
                ");
                $stmt->execute([$uid]);
                return $stmt->fetchColumn() !== false;
            } catch (\Throwable $e) {
                return false;
            }
        }
        return false;
    }
}

if (!function_exists('include_header')) {
    /** Include the active theme's header.php. */
    function include_header(): void {
        $theme = defined('ACTIVE_THEME') ? ACTIVE_THEME : (defined('DEFAULT_THEME') ? DEFAULT_THEME : 'default');
        $path = (defined('THEMES_PATH') ? rtrim(THEMES_PATH, '/') : '') . '/' . $theme . '/header.php';
        if (is_file($path)) include $path;
    }
}

if (!function_exists('include_footer')) {
    /** Include the active theme's footer.php. */
    function include_footer(): void {
        $theme = defined('ACTIVE_THEME') ? ACTIVE_THEME : (defined('DEFAULT_THEME') ? DEFAULT_THEME : 'default');
        $path = (defined('THEMES_PATH') ? rtrim(THEMES_PATH, '/') : '') . '/' . $theme . '/footer.php';
        if (is_file($path)) include $path;
    }
}

if (!function_exists('render_menu')) {
    /**
     * Output navigation menu markup. Uses get_menu($location).
     * Modular equivalent of wp_nav_menu – themes should use this only.
     */
    function render_menu(string $location = 'primary', string $menu_class = 'menu'): void {
        $items = get_menu($location);
        if (empty($items)) {
            return;
        }
        echo '<ul class="' . e($menu_class) . '">';
        foreach ($items as $item) {
            $url = $item['url'] ?? '#';
            $label = $item['label'] ?? '';
            echo '<li><a href="' . e($url) . '">' . e($label) . '</a></li>';
        }
        echo '</ul>';
    }
}

if (!function_exists('get_menu')) {
    /**
     * Get navigation menu. Returns array of items: [ ['label' => '', 'url' => '', 'children' => [] ], ... ]
     * Reads from settings 'site_menu_{location}'.
     */
    function get_menu(string $location = 'primary'): array {
        if (!isset($GLOBALS['db'])) return [];
        $base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
        try {
            $stmt = $GLOBALS['db']->prepare("SELECT option_value FROM settings WHERE option_name = ? LIMIT 1");
            $stmt->execute(['site_menu_' . $location]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && $row['option_value'] !== '') {
                $dec = json_decode($row['option_value'], true);
                if (is_array($dec)) {
                    $out = [];
                    foreach ($dec as $item) {
                        $resolved = _get_menu_resolve_item($GLOBALS['db'], $item, $base);
                        if ($resolved !== null) {
                            $out[] = $resolved;
                        }
                    }
                    return $out;
                }
            }
            $stmt = $GLOBALS['db']->query("
                SELECT p.id, p.title, p.slug FROM posts p
                INNER JOIN post_types pt ON p.post_type_id = pt.id
                WHERE pt.slug = 'page' AND p.deleted_at IS NULL AND p.status = 'published'
                ORDER BY p.`order` ASC, p.title ASC LIMIT 20
            ");
            $pages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $out = [];
            foreach ($pages as $p) {
                $out[] = ['label' => $p['title'], 'url' => $base . '/' . $p['slug'], 'children' => []];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('_get_menu_resolve_item')) {
    function _get_menu_resolve_item(\PDO $db, array $item, string $base): ?array {
        $type = $item['type'] ?? 'custom';
        $children = isset($item['children']) && is_array($item['children']) ? $item['children'] : [];
        $resolvedChildren = [];
        foreach ($children as $child) {
            $c = _get_menu_resolve_item($db, $child, $base);
            if ($c !== null) $resolvedChildren[] = $c;
        }
        if ($type === 'post' && !empty($item['post_id'])) {
            $stmt = $db->prepare("
                SELECT p.title, p.slug FROM posts p
                INNER JOIN post_types pt ON p.post_type_id = pt.id
                WHERE p.id = ? AND p.deleted_at IS NULL AND p.status = 'published'
                LIMIT 1
            ");
            $stmt->execute([(int) $item['post_id']]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return null;
            return [
                'label' => $row['title'],
                'url'   => $base . '/' . $row['slug'],
                'children' => $resolvedChildren,
            ];
        }
        $label = $item['label'] ?? '';
        $url = $item['url'] ?? '#';
        if ($label === '' && $url === '#') return null;
        return ['label' => $label, 'url' => $url, 'children' => $resolvedChildren];
    }
}

if (!function_exists('get_posts')) {
    function get_posts(array $args = []): array {
        if (!isset($GLOBALS['db'])) return [];
        $post_type = $args['post_type'] ?? 'post';
        $per_page = (int) ($args['posts_per_page'] ?? 10);
        $offset = (int) ($args['offset'] ?? 0);
        $orderby = $args['orderby'] ?? 'published_at';
        $order = strtoupper($args['order'] ?? 'DESC');
        if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'DESC';
        try {
            $stmt = $GLOBALS['db']->prepare("
                SELECT p.id, p.title, p.slug, p.excerpt, p.published_at, p.created_at
                FROM posts p INNER JOIN post_types pt ON p.post_type_id = pt.id
                WHERE pt.slug = ? AND p.deleted_at IS NULL AND p.status = 'published'
                ORDER BY p.{$orderby} {$order} LIMIT " . $per_page . " OFFSET " . $offset
            );
            $stmt->execute([$post_type]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('get_post')) {
    function get_post(?int $id = null, ?string $slug = null): ?array {
        if (!isset($GLOBALS['db'])) return null;
        try {
            if ($id !== null) {
                $stmt = $GLOBALS['db']->prepare("SELECT p.*, pt.slug as type_slug FROM posts p INNER JOIN post_types pt ON p.post_type_id = pt.id WHERE p.id = ? AND p.deleted_at IS NULL");
                $stmt->execute([$id]);
            } elseif ($slug !== null) {
                $stmt = $GLOBALS['db']->prepare("SELECT p.*, pt.slug as type_slug FROM posts p INNER JOIN post_types pt ON p.post_type_id = pt.id WHERE p.slug = ? AND p.deleted_at IS NULL AND p.status = 'published' LIMIT 1");
                $stmt->execute([$slug]);
            } else {
                return null;
            }
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('md_publish_due_posts')) {
    /**
     * Auto-publish: flip scheduled posts whose publication time has passed to
     * status='published'. Lazy scheduler (no cron) — any CMS/front request promotes
     * due posts, throttled to once per minute via the cache.
     */
    function md_publish_due_posts(): void {
        if (!isset($GLOBALS['db'])) return;
        if (function_exists('cache_get') && cache_get('publish_due_tick') !== null) return;
        if (function_exists('cache_set')) cache_set('publish_due_tick', 1, 60);
        try {
            $stmt = $GLOBALS['db']->prepare(
                "UPDATE posts p
                 INNER JOIN post_types pt ON pt.id = p.post_type_id
                 SET p.status = 'published', p.updated_at = NOW()
                 WHERE p.status = 'scheduled' AND p.published_at IS NOT NULL
                   AND p.published_at <= NOW() AND p.deleted_at IS NULL
                   AND pt.slug NOT IN ('album','track')"
            );
            $stmt->execute();
            if ($stmt->rowCount() > 0 && function_exists('cache_delete_prefix')) {
                cache_delete_prefix('mdposts');
            }
        } catch (\Throwable $e) {
        }
    }
}

if (!function_exists('get_meta')) {
    function get_meta(int $post_id, string $key): mixed {
        // Load each post's meta_data once per request so N meta reads cost one query, not N.
        static $cache = [];
        if ($post_id <= 0 || !isset($GLOBALS['db'])) return null;
        if (!array_key_exists($post_id, $cache)) {
            $cache[$post_id] = null;
            try {
                $stmt = $GLOBALS['db']->prepare("SELECT meta_data FROM posts WHERE id = ? AND deleted_at IS NULL");
                $stmt->execute([$post_id]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row && $row['meta_data'] !== null) {
                    $decoded = json_decode($row['meta_data'], true);
                    $cache[$post_id] = is_array($decoded) ? $decoded : null;
                }
            } catch (\Throwable $e) {
            }
        }
        $meta = $cache[$post_id];
        return is_array($meta) && array_key_exists($key, $meta) ? $meta[$key] : null;
    }
}

if (!function_exists('taxonomy_exists')) {
    function taxonomy_exists(string $taxonomy): bool {
        if (!isset($GLOBALS['db'])) return false;
        try {
            $stmt = $GLOBALS['db']->prepare("SELECT id FROM taxonomies WHERE slug = ? LIMIT 1");
            $stmt->execute([$taxonomy]);
            return $stmt->fetchColumn() !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('get_terms')) {
    /** Get terms for a taxonomy. Returns array of stdClass with id, name, slug, taxonomy, etc. */
    function get_terms(array $args = []): array {
        if (!isset($GLOBALS['db'])) return [];
        $taxonomy = $args['taxonomy'] ?? '';
        $orderby = $args['orderby'] ?? 'name';
        $order = strtoupper($args['order'] ?? 'ASC');
        $number = isset($args['number']) ? (int) $args['number'] : 0;
        $search = isset($args['search']) ? trim((string) $args['search']) : '';
        if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'ASC';
        $col = $orderby === 'term_id' ? 'id' : (in_array($orderby, ['name', 'slug', 'id'], true) ? $orderby : 'name');
        try {
            $sql = "SELECT t.id, t.name, t.slug, t.description, t.parent_id, tax.slug as taxonomy_slug
                FROM terms t
                INNER JOIN taxonomies tax ON t.taxonomy_id = tax.id
                WHERE t.deleted_at IS NULL";
            $params = [];
            if ($taxonomy !== '') {
                $sql .= " AND tax.slug = ?";
                $params[] = $taxonomy;
            }
            if ($search !== '') {
                $sql .= " AND (t.name LIKE ? OR t.slug LIKE ? OR t.description LIKE ?)";
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
            $sql .= " ORDER BY t.`" . $col . "` " . $order;
            if ($number > 0) $sql .= " LIMIT " . $number;
            $stmt = $GLOBALS['db']->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $o = new \stdClass();
                $o->term_id = (int) $r['id'];
                $o->id = (int) $r['id'];
                $o->name = $r['name'];
                $o->slug = $r['slug'];
                $o->description = $r['description'] ?? '';
                $o->parent = (int) ($r['parent_id'] ?? 0);
                $o->taxonomy = $r['taxonomy_slug'] ?? $taxonomy;
                $out[] = $o;
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('get_post_thumbnail_id')) {
    function get_post_thumbnail_id(int $post_id): int {
        if (!isset($GLOBALS['db'])) return 0;
        try {
            $stmt = $GLOBALS['db']->prepare("SELECT featured_image FROM posts WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$post_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $id = $row && $row['featured_image'] !== null ? (int) $row['featured_image'] : 0;
            return $id > 0 ? $id : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('get_attachment_url')) {
    function get_attachment_url(int $media_id): string {
        if (!isset($GLOBALS['db']) || $media_id <= 0) return '';
        try {
            $stmt = $GLOBALS['db']->prepare("SELECT file_path FROM media WHERE id = ?");
            $stmt->execute([$media_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row || empty($row['file_path'])) return '';
            $base = defined('UPLOADS_WEB_PATH') ? rtrim(UPLOADS_WEB_PATH, '/') : (defined('BASE_PATH') ? BASE_PATH . '/public/uploads' : '');
            return $base . '/' . ltrim($row['file_path'], '/');
        } catch (\Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('checked')) {
    function checked($checked, $current = true, bool $echo = true): string {
        $out = ((string) $checked === (string) $current) ? ' checked="checked"' : '';
        if ($echo) echo $out;
        return $out;
    }
}

if (!function_exists('selected')) {
    function selected($selected, $current = true, bool $echo = true): string {
        $out = ((string) $selected === (string) $current) ? ' selected="selected"' : '';
        if ($echo) echo $out;
        return $out;
    }
}

if (!function_exists('admin_base_url')) {
    function admin_base_url(string $path = ''): string {
        $base = defined('ADMIN_URL') ? rtrim(ADMIN_URL, '/') : base_url('/admin');
        return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('nonce')) {
    function nonce(string $action): string {
        return md5($action . (session_id() ?: '') . (defined('BASE_PATH') ? BASE_PATH : ''));
    }
}

if (!function_exists('clean_input')) {
    function clean_input($value): string {
        return trim(strip_tags(stripslashes(is_array($value) ? '' : (string) $value)));
    }
}
