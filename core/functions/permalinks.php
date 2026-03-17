<?php
/**
 * Permalink functions
 *
 * Handles permalink structure per post type:
 *   - load/save structures from settings table
 *   - build a post URL from its structure + tokens
 *   - resolve an incoming path back to a post type + slug
 *   - generate and write a clean .htaccess
 *
 * Supported tokens in a structure string:
 *   {slug}       — the post slug (always required)
 *   {post_type}  — the post type slug
 *   {category}   — primary taxonomy term slug
 *   {year}       — four-digit publish year
 */

// ---------------------------------------------------------------------------
// Load / save
// ---------------------------------------------------------------------------

/**
 * Return permalink structures indexed by post-type slug.
 * Falls back to /{slug} for any unknown post type.
 *
 * @return array<string,string>  e.g. ['page' => '/{slug}', 'post' => '/{category}/{slug}']
 */
function get_permalink_structures(): array {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $db = $GLOBALS['db'] ?? null;
    if (!$db instanceof PDO) {
        $cache = [];
        return $cache;
    }

    try {
        $stmt = $db->prepare("SELECT option_value FROM settings WHERE option_name = 'permalink_structure' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cache = ($row && $row['option_value']) ? (json_decode($row['option_value'], true) ?? []) : [];
    } catch (PDOException $e) {
        $cache = [];
    }

    return $cache;
}

/**
 * Get the permalink structure for one post type.
 * Default: /{slug}
 */
function get_permalink_structure(string $post_type): string {
    $structures = get_permalink_structures();
    return $structures[$post_type] ?? '/{slug}';
}

/**
 * Save permalink structures and regenerate .htaccess.
 *
 * @param array<string,string> $structures
 */
function save_permalink_structures(array $structures): bool {
    $db = $GLOBALS['db'] ?? null;
    if (!$db instanceof PDO) {
        return false;
    }

    try {
        $json = json_encode($structures, JSON_UNESCAPED_SLASHES);
        $stmt = $db->prepare("
            INSERT INTO settings (option_name, option_value, autoload, created_at, updated_at)
            VALUES ('permalink_structure', ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE option_value = ?, updated_at = NOW()
        ");
        $stmt->execute([$json, $json]);
    } catch (PDOException $e) {
        error_log('save_permalink_structures: ' . $e->getMessage());
        return false;
    }

    // Invalidate static cache
    // (re-call will re-query; simplest approach without a reset mechanism)
    unset($GLOBALS['_permalink_cache_bust']);

    // Regenerate .htaccess
    $htaccessPath = defined('MODULAR_PATH') ? MODULAR_PATH . '/.htaccess' : dirname(__DIR__, 2) . '/.htaccess';
    $basePath     = defined('BASE_PATH') ? BASE_PATH : '/modular';
    write_htaccess($htaccessPath, $basePath);

    return true;
}

// ---------------------------------------------------------------------------
// URL generation
// ---------------------------------------------------------------------------

/**
 * Build a public URL for a post.
 *
 * @param string $post_type   Post type slug
 * @param string $slug        Post slug
 * @param string $category    Primary taxonomy term slug (used if structure contains {category})
 * @param string $year        Four-digit year (used if structure contains {year})
 */
function get_post_permalink(string $post_type, string $slug, string $category = '', string $year = ''): string {
    $structure = get_permalink_structure($post_type);

    $url = str_replace(
        ['{slug}', '{post_type}', '{category}', '{year}'],
        [$slug,    $post_type,    $category ?: $post_type, $year ?: date('Y')],
        $structure
    );

    $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
    return $base . '/' . ltrim($url, '/');
}

// ---------------------------------------------------------------------------
// URL resolution  (router helper)
// ---------------------------------------------------------------------------

/**
 * Try to resolve a request path to [post_type_slug, post_slug] using stored permalink structures.
 *
 * Returns null when no structure matches.
 *
 * @param string $path   e.g. "some-category/my-post" (no leading slash)
 * @param PDO    $db
 * @return array{post_type:string, slug:string}|null
 */
function permalink_resolve_path(string $path, PDO $db): ?array {
    $structures = get_permalink_structures();
    if (empty($structures)) {
        return null;
    }

    $path = trim($path, '/');

    // Sort: more-specific patterns (more segments / fewer wildcards) first
    uasort($structures, function (string $a, string $b): int {
        $tokensA = substr_count($a, '{');
        $tokensB = substr_count($b, '{');
        $segsA   = substr_count($a, '/');
        $segsB   = substr_count($b, '/');
        // Prefer more path segments; on tie, fewer tokens (more literal text) wins
        if ($segsA !== $segsB) return $segsB - $segsA;
        return $tokensA - $tokensB;
    });

    foreach ($structures as $post_type => $structure) {
        $result = permalink_match_structure($path, $structure, $post_type, $db);
        if ($result !== null) {
            return $result;
        }
    }

    return null;
}

/**
 * Try to match a single structure against a path.
 * Returns [post_type, slug] on match, null otherwise.
 */
function permalink_match_structure(string $path, string $structure, string $post_type, PDO $db): ?array {
    // Build a named-capture regex from the structure
    $pattern = ltrim($structure, '/');

    // Known tokens → named capture groups
    $tokenMap = [
        '{slug}'      => '(?P<slug>[a-z0-9_-]+)',
        '{post_type}' => '(?P<post_type>[a-z0-9_-]+)',
        '{category}'  => '(?P<category>[a-z0-9_-]+)',
        '{year}'      => '(?P<year>\d{4})',
    ];
    $regex = '#^' . str_replace(array_keys($tokenMap), array_values($tokenMap), preg_quote($pattern, '#')) . '$#';
    // preg_quote escapes { and } — undo that for already-substituted groups
    // Actually we must do substitution BEFORE preg_quote; redo:
    $raw = ltrim($structure, '/');
    $regex = '#^' . _permalink_pattern_to_regex($raw) . '$#';

    if (!preg_match($regex, $path, $m)) {
        return null;
    }

    $slug = $m['slug'] ?? null;
    if (!$slug) {
        return null;
    }

    // Verify slug exists in DB for this post type
    try {
        $stmt = $db->prepare("
            SELECT p.slug FROM posts p
            INNER JOIN post_types pt ON p.post_type_id = pt.id
            WHERE pt.slug = ? AND p.slug = ? AND p.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$post_type, $slug]);
        if (!$stmt->fetch()) {
            return null;
        }
    } catch (PDOException $e) {
        return null;
    }

    return ['post_type' => $post_type, 'slug' => $slug];
}

/**
 * Convert a structure pattern to a regex string (without delimiters or anchors).
 */
function _permalink_pattern_to_regex(string $pattern): string {
    $tokenMap = [
        '{slug}'      => '(?P<slug>[a-z0-9_-]+)',
        '{post_type}' => '(?P<pt>[a-z0-9_-]+)',
        '{category}'  => '(?P<category>[a-z0-9_-]+)',
        '{year}'      => '(?P<year>\d{4})',
    ];

    // Split on tokens, quote literal parts, reassemble
    $parts   = preg_split('/(\{[a-z_]+\})/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
    $regex   = '';
    foreach ($parts as $part) {
        if (isset($tokenMap[$part])) {
            $regex .= $tokenMap[$part];
        } else {
            $regex .= preg_quote($part, '#');
        }
    }
    return $regex;
}

// ---------------------------------------------------------------------------
// .htaccess
// ---------------------------------------------------------------------------

/**
 * Generate a minimal .htaccess block.
 *
 * @param string $base_path  e.g. "/modular" or "/" — the RewriteBase value
 */
function build_htaccess(string $base_path): string {
    $trimmed = trim($base_path, '/');
    $base    = ($trimmed === '') ? '/' : '/' . $trimmed . '/';

    return "# BEGIN Modular\n"
         . "<IfModule mod_rewrite.c>\n"
         . "RewriteEngine On\n"
         . "RewriteBase {$base}\n"
         . "RewriteRule ^index\\.php$ - [L]\n"
         . "RewriteCond %{REQUEST_FILENAME} -f [OR]\n"
         . "RewriteCond %{REQUEST_FILENAME} -d\n"
         . "RewriteRule ^ - [L]\n"
         . "RewriteCond %{REQUEST_FILENAME}\\.php -f\n"
         . "RewriteRule ^(.+)$ $1.php [L]\n"
         . "RewriteRule ^ index.php [L,QSA]\n"
         . "</IfModule>\n"
         . "# END Modular";
}

/**
 * Write the generated .htaccess, replacing only the Modular block.
 * Preserves any content outside the BEGIN/END markers.
 *
 * @param string $htaccess_path  Absolute path to .htaccess
 * @param string $base_path      RewriteBase value
 */
function write_htaccess(string $htaccess_path, string $base_path): bool {
    $block    = build_htaccess($base_path);
    $existing = is_readable($htaccess_path) ? file_get_contents($htaccess_path) : '';

    // Replace the existing Modular block, or prepend if absent
    $pattern = '/# BEGIN Modular.*?# END Modular\s*/s';
    if (preg_match($pattern, $existing)) {
        $new = preg_replace($pattern, $block . "\n", $existing);
    } else {
        $new = $block . "\n" . $existing;
    }

    if (@file_put_contents($htaccess_path, $new) === false) {
        error_log("write_htaccess: cannot write to {$htaccess_path}");
        return false;
    }
    return true;
}
