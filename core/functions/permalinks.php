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
function get_permalink_structures(bool $refresh = false): array {
    static $cache = null;
    if ($refresh) {
        $cache = null;
    }
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
    if (isset($structures[$post_type]) && is_string($structures[$post_type]) && $structures[$post_type] !== '') {
        return $structures[$post_type];
    }
    if ($post_type === 'project') {
        return '/projects/{slug}';
    }
    if ($post_type === 'product') {
        return '/products/{slug}';
    }
    return '/{slug}';
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

    get_permalink_structures(true);

    $htaccessPath = get_htaccess_path();
    $basePath     = defined('BASE_PATH') ? BASE_PATH : '/';
    write_htaccess($htaccessPath, $basePath, $structures);

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
    $db = $GLOBALS['db'] ?? null;

    if ($db instanceof PDO) {
        try {
            $stmt = $db->prepare("
                SELECT p.id, pt.hierarchical
                FROM posts p
                INNER JOIN post_types pt ON p.post_type_id = pt.id
                WHERE pt.slug = ? AND p.slug = ? AND p.deleted_at IS NULL
                LIMIT 1
            ");
            $stmt->execute([$post_type, $slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['hierarchical'])) {
                $path = get_hierarchical_post_path((int)$row['id'], $db);
                if ($path !== '') {
                    $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
                    return $base . '/' . ltrim($path, '/');
                }
            }
        } catch (Throwable $e) {
            // Fallback to regular structure
        }
    }

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

    // Fallback: hierarchical post types with nested slugs (/parent/child[/grandchild])
    $segments = array_values(array_filter(explode('/', $path)));
    if (!empty($segments)) {
        $slugCandidate = $segments[count($segments) - 1];
        try {
            $stmt = $db->prepare("
                SELECT p.id, p.slug, pt.slug as post_type_slug
                FROM posts p
                INNER JOIN post_types pt ON p.post_type_id = pt.id
                WHERE p.slug = ? AND pt.hierarchical = 1 AND p.deleted_at IS NULL
            ");
            $stmt->execute([$slugCandidate]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $candidatePath = get_hierarchical_post_path((int)$row['id'], $db);
                if ($candidatePath !== '' && trim($candidatePath, '/') === trim($path, '/')) {
                    return ['post_type' => $row['post_type_slug'], 'slug' => $row['slug']];
                }
            }
        } catch (Throwable $e) {
            return null;
        }
    }

    return null;
}

/**
 * Build a hierarchical slug path (root/child/grandchild) for one post id.
 */
function get_hierarchical_post_path(int $postId, PDO $db): string {
    $parts = [];
    $cursor = $postId;
    $safety = 0;
    while ($cursor !== null && $safety < 20) {
        $safety++;
        $stmt = $db->prepare("SELECT id, slug, parent_id FROM posts WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$cursor]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            break;
        }
        array_unshift($parts, (string)$row['slug']);
        $cursor = $row['parent_id'] !== null ? (int)$row['parent_id'] : null;
    }
    return implode('/', array_filter($parts, 'strlen'));
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

function get_htaccess_path(): string {
    if (defined('MODULAR_PATH')) {
        return MODULAR_PATH . '/.htaccess';
    }
    return dirname(__DIR__, 2) . '/.htaccess';
}

function htaccess_is_writable(?string $path = null): bool {
    $path = $path ?: get_htaccess_path();
    if (is_file($path)) {
        return is_writable($path);
    }
    $dir = dirname($path);
    return is_dir($dir) && is_writable($dir);
}

/**
 * Convert one permalink structure into an Apache RewriteRule, or null when
 * the front controller already covers it (plain /{slug}).
 */
function permalink_structure_to_rewrite_rule(string $post_type, string $structure): ?string {
    $post_type = preg_replace('/[^a-z0-9_-]/', '', strtolower($post_type));
    if ($post_type === '') {
        return null;
    }

    $raw = trim(str_replace('{post_type}', $post_type, $structure), '/');
    if ($raw === '' || $raw === '{slug}') {
        return null;
    }

    $captures = [];
    $parts = preg_split('/(\{[a-z_]+\})/', $raw, -1, PREG_SPLIT_DELIM_CAPTURE);
    $pattern = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        if ($part === '{slug}') {
            $captures[] = 'slug';
            $pattern .= '([^/]+)';
            continue;
        }
        if ($part === '{year}') {
            $captures[] = 'year';
            $pattern .= '([0-9]{4})';
            continue;
        }
        if ($part === '{category}' || preg_match('/^\{[a-z_]+\}$/', $part)) {
            $captures[] = trim($part, '{}');
            $pattern .= '([^/]+)';
            continue;
        }
        $pattern .= preg_replace('/[.\+\?\^\$\[\]\(\)\{\}\|\\\\]/', '\\\\$0', $part);
    }

    $qs = ['post_type=' . $post_type];
    $n = 1;
    foreach ($captures as $token) {
        if ($token === 'slug') {
            $qs[] = 'slug=$' . $n;
            $qs[] = 'name=$' . $n;
        } elseif ($token === 'year') {
            $qs[] = 'year=$' . $n;
        } elseif ($token === 'category') {
            $qs[] = 'term=$' . $n;
        }
        $n++;
    }

    $dollar = '$';
    return 'RewriteRule ^' . $pattern . '/?' . $dollar . ' index.php?' . implode('&', $qs) . ' [L,QSA]';
}

/**
 * Nested Modular Permalinks markers + RewriteRules for the current structures.
 * Infrastructure rules (auth, api, uploads, existing files) stay outside this.
 *
 * @param array<string,string>|null $structures
 */
function build_permalink_htaccess_section(?array $structures = null): string {
    if ($structures === null) {
        $structures = get_permalink_structures();
    }

    uasort($structures, function ($a, $b): int {
        if (!is_string($a) || !is_string($b)) {
            return 0;
        }
        $segsA = substr_count(trim($a, '/'), '/');
        $segsB = substr_count(trim($b, '/'), '/');
        if ($segsA !== $segsB) {
            return $segsB - $segsA;
        }
        return substr_count($a, '{') - substr_count($b, '{');
    });

    $rules = [];
    foreach ($structures as $post_type => $structure) {
        if (!is_string($structure) || $structure === '') {
            continue;
        }
        $rule = permalink_structure_to_rewrite_rule((string) $post_type, $structure);
        if ($rule !== null) {
            $rules[] = $rule;
        }
    }

    $body = $rules
        ? implode("\n", $rules)
        : '# Plain /{slug} types are handled by the front controller below.';

    return "# BEGIN Modular Permalinks\n"
         . $body . "\n"
         . "# END Modular Permalinks";
}

/**
 * Generate a minimal .htaccess block for installs that have no Modular section yet.
 *
 * @param string $base_path  e.g. "/modular" or "/" — the RewriteBase value
 */
function build_htaccess(string $base_path): string {
    $trimmed = trim($base_path, '/');
    $base    = ($trimmed === '') ? '/' : '/' . $trimmed . '/';

    $dollar = '$';
    return "# BEGIN Modular\n"
         . "<IfModule mod_rewrite.c>\n"
         . "RewriteEngine On\n"
         . "RewriteBase {$base}\n"
         . "RewriteRule ^index\\.php{$dollar} - [L]\n"
         . "RewriteCond %{REQUEST_FILENAME} -f [OR]\n"
         . "RewriteCond %{REQUEST_FILENAME} -d\n"
         . "RewriteRule ^ - [L]\n"
         . "RewriteCond %{REQUEST_FILENAME}\\.php -f\n"
         . "RewriteRule ^(.+){$dollar} {$dollar}1.php [L]\n"
         . "RewriteRule ^ index.php [L,QSA]\n"
         . "</IfModule>\n"
         . "# END Modular";
}

/**
 * Insert or replace the nested Permalinks section. Never replaces the outer
 * Modular block, so auth / api / uploads / docs rules stay intact.
 */
function merge_htaccess_permalink_rules(string $existing, string $permalinkBlock, string $base_path): string {
    $permalinkBlock = trim($permalinkBlock);
    $inner = '/# BEGIN Modular Permalinks\s*.*?# END Modular Permalinks[ \t]*\r?\n?/s';

    if (preg_match($inner, $existing, $m, PREG_OFFSET_CAPTURE)) {
        $start = $m[0][1];
        return substr($existing, 0, $start) . $permalinkBlock . "\n" . substr($existing, $start + strlen($m[0][0]));
    }

    $injectBeforeFrontController = '/RewriteRule \^ index\.php \[L,QSA\][ \t]*\r?\n[ \t]*<\/IfModule>\s*# END Modular(?! Permalinks)/s';
    if (preg_match($injectBeforeFrontController, $existing, $m, PREG_OFFSET_CAPTURE)) {
        $start = $m[0][1];
        return substr($existing, 0, $start) . $permalinkBlock . "\n" . substr($existing, $start);
    }

    $injectBeforeClose = '/<\/IfModule>\s*# END Modular(?! Permalinks)/s';
    if (preg_match($injectBeforeClose, $existing, $m, PREG_OFFSET_CAPTURE)) {
        $start = $m[0][1];
        return substr($existing, 0, $start) . $permalinkBlock . "\n" . substr($existing, $start);
    }

    $starter = build_htaccess($base_path);
    if (preg_match($injectBeforeFrontController, $starter, $m, PREG_OFFSET_CAPTURE)) {
        $start = $m[0][1];
        $starter = substr($starter, 0, $start) . $permalinkBlock . "\n" . substr($starter, $start);
    }
    $existing = ltrim($existing);
    return $existing === '' ? $starter . "\n" : $starter . "\n" . $existing;
}

/**
 * Write permalink rewrite rules into .htaccess without replacing the Modular
 * infrastructure block (auth proxy, api/uploads, existing-file short-circuit).
 *
 * @param string $htaccess_path  Absolute path to .htaccess
 * @param string $base_path      RewriteBase value used only when no Modular block exists
 * @param array<string,string>|null $structures
 */
function write_htaccess(string $htaccess_path, string $base_path, ?array $structures = null): bool {
    $permalinkBlock = build_permalink_htaccess_section($structures);
    $existing = is_readable($htaccess_path) ? (string) file_get_contents($htaccess_path) : '';
    $new = merge_htaccess_permalink_rules($existing, $permalinkBlock, $base_path);

    if (@file_put_contents($htaccess_path, $new) === false) {
        error_log("write_htaccess: cannot write to {$htaccess_path}");
        return false;
    }
    return true;
}
