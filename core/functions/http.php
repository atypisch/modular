<?php
/**
 * Modular HTTP & path utility functions.
 *
 * Replaces WordPress:
 *   wp_remote_get()          → md_http_get()
 *   wp_remote_post()         → md_http_post()
 *   wp_upload_dir()          → md_upload_dir()
 *   home_url()               → md_home_url()
 *   admin_url()              → md_url('/admin/...')
 *   get_template_directory_uri() → md_asset_url()
 *   get_template_directory()     → md_theme_path()
 *   wp_die()                 → md_die()
 *   wp_json_encode()         → md_json()
 *   esc_html()               → md_esc_html()
 *   esc_attr()               → md_esc_attr()
 *   esc_url()                → md_esc_url()
 *   esc_js()                 → md_esc_js()
 *   sanitize_text_field()    → md_sanitize_text()
 *   is_wp_error()            → (check === false or instanceof \Exception)
 *   status_header()          → http_response_code() directly
 */

// ---------------------------------------------------------------------------
// URL helpers
// ---------------------------------------------------------------------------

if (!function_exists('md_home_url')) {
    /**
     * Return the site base URL with optional appended path.
     * Uses BASE_PATH constant (set in config.local.php) for subfolder installs.
     * Examples: md_home_url('/group/rock-band/') → '/simuze-mdlr/group/rock-band/'
     */
    function md_home_url(string $path = ''): string {
        $base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
        if ($path !== '') {
            $base .= '/' . ltrim($path, '/');
        }
        return $base ?: '/';
    }
}

if (!function_exists('md_url')) {
    /**
     * Build an internal site URL.
     * md_url('/api/music/upload')  → '/simuze-mdlr/api/music/upload'
     * md_url('/admin/users')       → '/simuze-mdlr/admin/users'
     */
    function md_url(string $path = ''): string {
        return md_home_url($path);
    }
}

if (!function_exists('md_asset_url')) {
    /**
     * Return the URL to a theme asset (replaces get_template_directory_uri()).
     * md_asset_url('/css/app.css') → '/simuze-mdlr/public/themes/simuze/css/app.css'
     */
    function md_asset_url(string $path = ''): string {
        $base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
        $theme = defined('ACTIVE_THEME') ? ACTIVE_THEME : (defined('DEFAULT_THEME') ? DEFAULT_THEME : 'simuze');
        $themePath = $base . '/public/themes/' . $theme;
        if ($path !== '') {
            $themePath .= '/' . ltrim($path, '/');
        }
        return $themePath;
    }
}

if (!function_exists('md_theme_path')) {
    /**
     * Return the filesystem path to the active theme directory
     * (replaces get_template_directory()).
     */
    function md_theme_path(string $path = ''): string {
        $theme = defined('ACTIVE_THEME') ? ACTIVE_THEME : (defined('DEFAULT_THEME') ? DEFAULT_THEME : 'simuze');
        $base  = defined('THEMES_PATH') ? THEMES_PATH : (defined('PUBLIC_PATH') ? PUBLIC_PATH . '/themes' : '');
        $dir   = rtrim($base, '/') . '/' . $theme;
        if ($path !== '') {
            $dir .= '/' . ltrim($path, '/');
        }
        return $dir;
    }
}

// ---------------------------------------------------------------------------
// Upload directory
// ---------------------------------------------------------------------------

if (!function_exists('md_upload_dir')) {
    /**
     * Return an array describing the uploads directory (replaces wp_upload_dir()).
     * Keys: basedir, baseurl, path, url, error
     */
    function md_upload_dir(): array {
        $basedir = defined('UPLOADS_PATH') ? UPLOADS_PATH : (defined('PUBLIC_PATH') ? PUBLIC_PATH . '/uploads' : '');
        $baseurl = defined('UPLOADS_WEB_PATH') ? UPLOADS_WEB_PATH : (md_home_url('/public/uploads'));
        return [
            'basedir'  => $basedir,
            'baseurl'  => $baseurl,
            'path'     => $basedir,
            'url'      => $baseurl,
            'error'    => false,
        ];
    }
}

// ---------------------------------------------------------------------------
// HTTP client (replaces wp_remote_get / wp_remote_post)
// ---------------------------------------------------------------------------

if (!function_exists('md_http_get')) {
    /**
     * Perform an HTTP GET request.
     * Returns ['body' => string, 'code' => int, 'headers' => array] or false on error.
     */
    function md_http_get(string $url, array $args = []) {
        $timeout = (int) ($args['timeout'] ?? 15);
        $headers = $args['headers'] ?? [];

        $ctx_opts = [
            'http' => [
                'method'          => 'GET',
                'timeout'         => $timeout,
                'ignore_errors'   => true,
                'follow_location' => 1,
            ],
            'ssl' => ['verify_peer' => true],
        ];

        if (!empty($headers)) {
            $headerLines = [];
            foreach ($headers as $k => $v) {
                $headerLines[] = "{$k}: {$v}";
            }
            $ctx_opts['http']['header'] = implode("\r\n", $headerLines);
        }

        $ctx  = stream_context_create($ctx_opts);
        $body = @file_get_contents($url, false, $ctx);

        if ($body === false) {
            return false;
        }

        $code = 200;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $h) {
                if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) {
                    $code = (int) $m[1];
                }
            }
        }

        return ['body' => $body, 'code' => $code, 'headers' => $http_response_header ?? []];
    }
}

if (!function_exists('md_http_post')) {
    /**
     * Perform an HTTP POST request.
     * $args['body'] — string or array (array is form-encoded).
     * Returns ['body' => string, 'code' => int, 'headers' => array] or false on error.
     */
    function md_http_post(string $url, array $args = []) {
        $timeout  = (int) ($args['timeout'] ?? 15);
        $headers  = $args['headers'] ?? [];
        $body_raw = $args['body'] ?? '';

        if (is_array($body_raw)) {
            $body_raw = http_build_query($body_raw);
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            }
        }

        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = "{$k}: {$v}";
        }

        $ctx = stream_context_create([
            'http' => [
                'method'          => 'POST',
                'timeout'         => $timeout,
                'ignore_errors'   => true,
                'follow_location' => 1,
                'header'          => implode("\r\n", $headerLines),
                'content'         => $body_raw,
            ],
            'ssl' => ['verify_peer' => true],
        ]);

        $body = @file_get_contents($url, false, $ctx);

        if ($body === false) {
            return false;
        }

        $code = 200;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $h) {
                if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) {
                    $code = (int) $m[1];
                }
            }
        }

        return ['body' => $body, 'code' => $code, 'headers' => $http_response_header ?? []];
    }
}

if (!function_exists('md_http_body')) {
    /** Extract the body string from an md_http_get/post response. */
    function md_http_body($response): string {
        return is_array($response) ? ($response['body'] ?? '') : '';
    }
}

if (!function_exists('md_http_code')) {
    /** Extract the HTTP status code from an md_http_get/post response. */
    function md_http_code($response): int {
        return is_array($response) ? (int) ($response['code'] ?? 0) : 0;
    }
}

// ---------------------------------------------------------------------------
// Error / output helpers
// ---------------------------------------------------------------------------

if (!function_exists('md_die')) {
    /**
     * Terminate the request with an error message (replaces wp_die()).
     * For AJAX callers sends JSON; otherwise sends a plain-text page.
     */
    function md_die(string $message = 'An error occurred.', int $status = 400): void {
        http_response_code($status);
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $wants_json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        if ($is_ajax || $wants_json) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
        } else {
            echo '<html><body><h1>Error</h1><p>' . htmlspecialchars($message, ENT_QUOTES) . '</p></body></html>';
        }
        exit;
    }
}

if (!function_exists('md_json')) {
    /**
     * JSON-encode a value (replaces wp_json_encode()).
     */
    function md_json($value, int $flags = 0): string {
        return json_encode($value, $flags | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

// ---------------------------------------------------------------------------
// Escaping helpers (replaces WP esc_* functions)
// ---------------------------------------------------------------------------

if (!function_exists('md_esc_html')) {
    function md_esc_html(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('md_esc_attr')) {
    function md_esc_attr(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('md_esc_url')) {
    function md_esc_url(string $url): string {
        $url = strip_tags($url);
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('md_esc_js')) {
    /** Escape a string for use in a JS string literal. */
    function md_esc_js(string $text): string {
        return json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

if (!function_exists('md_sanitize_text')) {
    /** Strip tags and trim whitespace (replaces sanitize_text_field()). */
    function md_sanitize_text(string $text): string {
        return trim(strip_tags($text));
    }
}

if (!function_exists('md_sanitize_title')) {
    /**
     * Convert a string to a URL-friendly slug (replaces sanitize_title()).
     * md_sanitize_title('Hello World!') → 'hello-world'
     */
    function md_sanitize_title(string $str): string {
        $str = mb_strtolower(trim($str), 'UTF-8');
        $str = preg_replace('/[^a-z0-9]+/', '-', $str);
        return trim($str, '-');
    }
}

if (!function_exists('md_locate_template')) {
    /**
     * Return the filesystem path to a template file in the active theme
     * (replaces locate_template() without loading).
     * md_locate_template('template-parts/album-grid.php')
     */
    function md_locate_template(string $template): string {
        $theme = defined('ACTIVE_THEME') ? ACTIVE_THEME : (defined('DEFAULT_THEME') ? DEFAULT_THEME : 'simuze');
        $base  = defined('THEMES_PATH') ? THEMES_PATH : (defined('PUBLIC_PATH') ? PUBLIC_PATH . '/themes' : '');
        $path  = rtrim($base, '/') . '/' . $theme . '/' . ltrim($template, '/');
        return is_file($path) ? $path : '';
    }
}
