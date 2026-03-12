<?php
/**
 * Public template helper functions (SSR). Use in themes only.
 * All output must be escaped in templates; these return raw data.
 */

if (!function_exists('get_theme_templates')) {
    /**
     * Discover page templates from the active theme (WordPress-style).
     * Scans theme PHP files for block comments containing "Template Name: Display Name".
     * Use in theme files: /* Template Name: My Template * /
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
        // Theme assets live under public/themes so URLs must include /public/ for static file serving
        $themeBase = $base . '/public/themes/' . $theme;
        return $themeBase . ($path !== '' ? '/' . ltrim($path, '/') : '');
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
        $v = get_setting('site_name', 'Modular');
        return (string) $v;
    }
}

if (!function_exists('simuze_get_location_group_icon_url')) {
    /**
     * Return a small OpenStreetMap-based thumbnail for a location group (e.g. country).
     * Falls back to the group's avatar URL when no map-based avatar is available.
     *
     * @param object $group  Instance of Simuze_Group or compatible object
     * @param string $level  'country', 'province', 'city', etc.
     * @return string|null
     */
    function simuze_get_location_group_icon_url($group, string $level = 'country'): ?string {
        if (!is_object($group) || !method_exists($group, 'get_avatar_url')) {
            return null;
        }
        // Location groups already use OSM tiles in get_avatar_url() when lat/lng is present.
        $url = $group->get_avatar_url('thumb');
        return is_string($url) && $url !== '' ? $url : null;
    }
}

if (!function_exists('simuze_get_location_map_icon_url')) {
    /**
     * Return a map thumbnail URL for city/province groups, suitable for use as an icon.
     * Uses the group's avatar (which is backed by OpenStreetMap tiles for location types).
     *
     * @param object $group  Instance of Simuze_Group or compatible object
     * @param string $level  'city' or 'province'
     * @return string|null
     */
    function simuze_get_location_map_icon_url($group, string $level = 'city'): ?string {
        if (!is_object($group) || !method_exists($group, 'get_avatar_url')) {
            return null;
        }
        $url = $group->get_avatar_url('thumb');
        return is_string($url) && $url !== '' ? $url : null;
    }
}

if (!function_exists('is_front_page')) {
    /** True when the current request is the site front page (path empty or single slash). */
    function is_front_page(): bool {
        $path = current_path();
        return $path === '' || $path === '/';
    }
}

if (!function_exists('get_front_page_id')) {
    /** Post ID of the page set as homepage, or 0 if not set / blog index. */
    function get_front_page_id(): int {
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

if (!function_exists('include_template_part')) {
    /** Include the active theme's template-parts/{$slug}-{$name}.php. */
    function include_template_part(string $slug, string $name = ''): void {
        $theme = defined('ACTIVE_THEME') ? ACTIVE_THEME : (defined('DEFAULT_THEME') ? DEFAULT_THEME : 'default');
        $base = (defined('THEMES_PATH') ? rtrim(THEMES_PATH, '/') : '') . '/' . $theme . '/template-parts';
        $file = $base . '/' . $slug . ($name !== '' ? '-' . $name : '') . '.php';
        if (is_file($file)) include $file;
    }
}

if (!function_exists('get_the_ID')) {
    /** Current post ID from $GLOBALS['post']. */
    function get_the_ID(): int {
        $post = $GLOBALS['post'] ?? null;
        if (is_object($post) && isset($post->ID)) return (int) $post->ID;
        if (is_object($post) && isset($post->id)) return (int) $post->id;
        if (is_array($post) && isset($post['id'])) return (int) $post['id'];
        return 0;
    }
}

if (!function_exists('the_title')) {
    /** Output current post title (escaped). */
    function the_title(string $before = '', string $after = ''): void {
        $post = $GLOBALS['post'] ?? null;
        $title = '';
        if (is_object($post) && isset($post->post_title)) $title = (string) $post->post_title;
        elseif (is_object($post) && isset($post->title)) $title = (string) $post->title;
        elseif (is_array($post) && isset($post['title'])) $title = (string) $post['title'];
        echo $before . e($title) . $after;
    }
}

if (!function_exists('get_the_date')) {
    /** Formatted publish date for current post. */
    function get_the_date(string $format = 'Y-m-d'): string {
        $post = $GLOBALS['post'] ?? null;
        $date = null;
        if (is_object($post) && isset($post->published_at)) $date = $post->published_at;
        elseif (is_object($post) && isset($post->post_date)) $date = $post->post_date;
        elseif (is_array($post) && isset($post['published_at'])) $date = $post['published_at'];
        if (!$date) return '';
        return date($format, is_numeric($date) ? (int) $date : strtotime($date));
    }
}

if (!function_exists('get_the_author')) {
    /** Author display name for current post. */
    function get_the_author(): string {
        $post = $GLOBALS['post'] ?? null;
        $author_id = null;
        if (is_object($post) && isset($post->author_id)) $author_id = (int) $post->author_id;
        elseif (is_array($post) && isset($post['author_id'])) $author_id = (int) $post['author_id'];
        if (!$author_id || !isset($GLOBALS['db'])) return '';
        try {
            $stmt = $GLOBALS['db']->prepare('SELECT display_name, name FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$author_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ? (trim($row['display_name'] ?? '') !== '' ? $row['display_name'] : $row['name']) : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('edit_post_link')) {
    /** Output a link to edit the post in admin. */
    function edit_post_link(int $post_id = 0, string $text = 'Edit', string $before = '', string $after = ''): void {
        if ($post_id <= 0 && function_exists('get_the_ID')) $post_id = get_the_ID();
        if ($post_id <= 0) return;
        $url = admin_base_url('posts/edit.php?id=' . $post_id);
        echo $before . '<a href="' . e($url) . '">' . e($text) . '</a>' . $after;
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
     * Reads from settings 'site_menu_{location}'. Items with type 'post' and post_id are resolved to current title/slug.
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
                ORDER BY p.\`order\` ASC, p.title ASC LIMIT 20
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
SELECT p.id, p.title, p.slug, p.excerpt, p.published_at, p.created_at, p.author_id
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

if (!function_exists('get_post_parent_id')) {
    /** Parent post ID for a post (from posts.parent_id). */
    function get_post_parent_id(int $post_id): int {
        $post = get_post($post_id);
        return ($post && isset($post['parent_id']) && $post['parent_id'] !== null) ? (int) $post['parent_id'] : 0;
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

if (!function_exists('get_meta')) {
    function get_meta(int $post_id, string $key): mixed {
        if (!isset($GLOBALS['db'])) return null;
        try {
            $stmt = $GLOBALS['db']->prepare("SELECT meta_data FROM posts WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$post_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row || $row['meta_data'] === null) return null;
            $meta = json_decode($row['meta_data'], true);
            return is_array($meta) && array_key_exists($key, $meta) ? $meta[$key] : null;
        } catch (\Throwable $e) {
            return null;
        }
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
    /**
     * Get terms for a taxonomy (WP-style). Returns array of stdClass with id, name, slug, taxonomy, etc.
     */
    function get_terms(array $args = []): array {
        if (!isset($GLOBALS['db'])) return [];
        $taxonomy = $args['taxonomy'] ?? '';
        $hide_empty = !empty($args['hide_empty']);
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

if (!function_exists('get_post_terms')) {
    /** Terms linked to a post for a taxonomy (e.g. category, post_tag). Returns array of stdClass. */
    function get_post_terms(int $post_id, string $taxonomy): array {
        if (!isset($GLOBALS['db']) || $post_id <= 0) return [];
        try {
            $stmt = $GLOBALS['db']->prepare("
                SELECT t.id, t.name, t.slug FROM terms t
                INNER JOIN taxonomies tax ON t.taxonomy_id = tax.id
                INNER JOIN post_terms pt ON pt.term_id = t.id
                WHERE pt.post_id = ? AND tax.slug = ? AND t.deleted_at IS NULL
            ");
            $stmt->execute([$post_id, $taxonomy]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $o = new \stdClass();
                $o->term_id = (int) $r['id'];
                $o->name = $r['name'];
                $o->slug = $r['slug'];
                $out[] = $o;
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('get_term_link')) {
    /** URL for a term archive (category or tag). */
    function get_term_link(object $term, string $taxonomy = 'category'): string {
        $slug = $term->slug ?? '';
        if ($slug === '') return base_url('/');
        return base_url('/' . $taxonomy . '/' . $slug);
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
    /** Output checked="checked" when $checked equals $current. */
    function checked($checked, $current = true, bool $echo = true): string {
        $out = ((string) $checked === (string) $current) ? ' checked="checked"' : '';
        if ($echo) echo $out;
        return $out;
    }
}

if (!function_exists('selected')) {
    /** Output selected="selected" when $selected equals $current. */
    function selected($selected, $current = true, bool $echo = true): string {
        $out = ((string) $selected === (string) $current) ? ' selected="selected"' : '';
        if ($echo) echo $out;
        return $out;
    }
}

if (!function_exists('admin_base_url')) {
    /** Admin base URL (no trailing slash). */
    function admin_base_url(string $path = ''): string {
        $base = defined('ADMIN_URL') ? rtrim(ADMIN_URL, '/') : base_url('/admin');
        return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('nonce')) {
    /** Generate a simple nonce for forms/AJAX. */
    function nonce(string $action): string {
        return md5($action . (session_id() ?: '') . (defined('BASE_PATH') ? BASE_PATH : ''));
    }
}

if (!function_exists('clean_input')) {
    /** Trim and strip tags from input. For GET/POST string values. */
    function clean_input($value): string {
        return trim(strip_tags(stripslashes(is_array($value) ? '' : (string) $value)));
    }
}

if (!function_exists('add_query_arg')) {
    /** Append a query parameter to a URL. */
    function add_query_arg(string $key, string $value, string $url): string {
        $sep = strpos($url, '?') !== false ? '&' : '?';
        return $url . $sep . rawurlencode($key) . '=' . rawurlencode($value);
    }
}

if (!function_exists('esc_js')) {
    /** Escape string for use in JavaScript (e.g. inside HTML attribute). */
    function esc_js(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trim_words')) {
    /** Limit string to a number of words. */
    function trim_words(string $text, int $num_words = 55, string $more = '&hellip;'): string {
        $words = preg_split('/\s+/u', trim($text), $num_words + 1, PREG_SPLIT_NO_EMPTY);
        if (count($words) <= $num_words) return $text;
        return implode(' ', array_slice($words, 0, $num_words)) . $more;
    }
}

if (!function_exists('get_the_excerpt')) {
    /** Excerpt for current post from $GLOBALS['post']. */
    function get_the_excerpt(): string {
        $post = $GLOBALS['post'] ?? null;
        if (is_object($post) && isset($post->excerpt)) return (string) $post->excerpt;
        if (is_array($post) && isset($post['excerpt'])) return (string) $post['excerpt'];
        return '';
    }
}
