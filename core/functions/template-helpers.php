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
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($script !== '' && strpos($script, '/public/') === false) {
            $base .= '/public';
        }
        return $base . '/themes/' . $theme . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('get_site_name')) {
    function get_site_name(): string {
        if (!isset($GLOBALS['db'])) return 'Modular';
        try {
            $stmt = $GLOBALS['db']->query("SELECT option_value FROM settings WHERE option_name = 'site_name' LIMIT 1");
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ? (string) $row['option_value'] : 'Modular';
        } catch (\Throwable $e) {
            return 'Modular';
        }
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

if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}
