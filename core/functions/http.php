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

/**
 * Internal helper: execute an HTTP request via cURL (preferred) or file_get_contents fallback.
 * @internal
 */
function _md_http_request(string $method, string $url, array $args = []) {
    $timeout  = (int) ($args['timeout'] ?? 15);
    $headers  = $args['headers'] ?? [];
    $body_raw = $args['body'] ?? '';

    if (is_array($body_raw)) {
        $body_raw = http_build_query($body_raw);
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
    }

    // --- cURL (preferred: reliably sends Authorization and all custom headers) ---
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // localhost loopback

        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = "{$k}: {$v}";
        }
        if (!empty($headerLines)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body_raw);
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($body_raw !== '') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body_raw);
            }
        }

        // Capture response headers to extract status code
        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$responseHeaders) {
            $responseHeaders[] = rtrim($header);
            return strlen($header);
        });

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            return false;
        }
        return ['body' => (string) $body, 'code' => $code, 'headers' => $responseHeaders];
    }

    // --- file_get_contents fallback ---
    $headerLines = [];
    foreach ($headers as $k => $v) {
        $headerLines[] = "{$k}: {$v}";
    }
    $ctx_opts = [
        'http' => [
            'method'          => $method,
            'timeout'         => $timeout,
            'ignore_errors'   => true,
            'follow_location' => 1,
            'header'          => implode("\r\n", $headerLines),
        ],
        'ssl' => ['verify_peer' => false],
    ];
    if ($body_raw !== '') {
        $ctx_opts['http']['content'] = $body_raw;
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

if (!function_exists('md_http_get')) {
    /**
     * Perform an HTTP GET request.
     * Returns ['body' => string, 'code' => int, 'headers' => array] or false on error.
     */
    function md_http_get(string $url, array $args = []) {
        return _md_http_request('GET', $url, $args);
    }
}

if (!function_exists('md_http_post')) {
    /**
     * Perform an HTTP POST request.
     * $args['body'] — string or array (array is form-encoded).
     * Returns ['body' => string, 'code' => int, 'headers' => array] or false on error.
     */
    function md_http_post(string $url, array $args = []) {
        return _md_http_request('POST', $url, $args);
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
// Outbound fetches of user-supplied URLs (SSRF-safe)
// ---------------------------------------------------------------------------

if (!function_exists('md_registrable_domain')) {
    /**
     * The registrable domain of a host ("www.ekko.nl" -> "ekko.nl").
     *
     * No Public Suffix List dependency: the last two labels, or the last three when
     * the last two form a known multi-part public suffix (co.uk, com.au, ...). This is
     * exact for single-label suffixes such as .nl .be .de .fr .eu .com .org, which is
     * what European venues use. For a multi-part suffix that is not listed it returns
     * the suffix itself (e.g. "gov.xx"), so two unrelated sites on it would compare
     * equal; callers that grant trust combine it with md_is_shared_host_domain() and
     * never auto-trust bare suffix-looking results (see md_is_shared_host_domain()).
     * IP literals and single-label hosts return ''.
     */
    function md_registrable_domain(string $host): string {
        $host = strtolower(trim($host, " \t\n\r\0\x0B."));
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP) || strpos($host, '.') === false) {
            return '';
        }
        $labels = explode('.', $host);
        $n = count($labels);
        static $multi = [
            'co.uk', 'org.uk', 'ac.uk', 'gov.uk', 'me.uk', 'ltd.uk', 'plc.uk', 'net.uk', 'sch.uk',
            'com.au', 'net.au', 'org.au', 'edu.au', 'gov.au', 'co.nz', 'org.nz', 'net.nz',
            'com.br', 'com.mx', 'com.ar', 'co.za', 'co.jp', 'ne.jp', 'or.jp', 'co.in', 'co.kr',
            'com.tr', 'com.pl', 'com.ua', 'com.cn', 'com.hk', 'com.sg', 'co.il', 'com.es', 'com.pt',
        ];
        if ($n >= 3 && in_array($labels[$n - 2] . '.' . $labels[$n - 1], $multi, true)) {
            return implode('.', array_slice($labels, -3));
        }
        return implode('.', array_slice($labels, -2));
    }
}

if (!function_exists('md_is_shared_host_domain')) {
    /**
     * True for registrable domains where many unrelated people own addresses or pages
     * (webmail, site builders, social platforms). Owning an address or a page there
     * proves nothing about who runs an organisation.
     */
    function md_is_shared_host_domain(string $domain): bool {
        static $shared = [
            'gmail.com', 'googlemail.com', 'google.com', 'outlook.com', 'hotmail.com', 'live.com', 'live.nl',
            'msn.com', 'yahoo.com', 'icloud.com', 'me.com', 'protonmail.com', 'proton.me', 'gmx.com', 'gmx.net',
            'kpnmail.nl', 'ziggo.nl', 'hetnet.nl', 'planet.nl', 'home.nl', 'xs4all.nl', 'upcmail.nl', 'telfort.nl',
            'wixsite.com', 'wix.com', 'squarespace.com', 'wordpress.com', 'blogspot.com', 'weebly.com',
            'webflow.io', 'github.io', 'netlify.app', 'vercel.app', 'pages.dev', 'carrd.co', 'jimdosite.com',
            'bandcamp.com', 'facebook.com', 'instagram.com', 'linktr.ee', 'soundcloud.com', 'tiktok.com',
            'x.com', 'twitter.com', 'youtube.com', 'spotify.com',
        ];
        return in_array(strtolower($domain), $shared, true);
    }
}

if (!function_exists('md_is_public_ip')) {
    /** True when an IPv4/IPv6 address is publicly routable (not private, loopback, link-local, reserved). */
    function md_is_public_ip(string $ip): bool {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        $bin = inet_pton($ip);
        if ($bin === false) {
            return false;
        }
        if (strlen($bin) === 16) {
            // IPv4-mapped (::ffff:a.b.c.d) and NAT64 (64:ff9b::/96): judge the embedded IPv4.
            if (substr($bin, 0, 12) === str_repeat("\0", 10) . "\xff\xff" || substr($bin, 0, 12) === "\x00\x64\xff\x9b" . str_repeat("\0", 8)) {
                return md_is_public_ip(inet_ntop(substr($bin, 12)));
            }
            $first = ord($bin[0]);
            if ($bin === str_repeat("\0", 15) . "\1" || $bin === str_repeat("\0", 16)) return false; // ::1, ::
            if (($first & 0xfe) === 0xfc) return false;                                           // fc00::/7 unique local
            if ($first === 0xfe && (ord($bin[1]) & 0xc0) === 0x80) return false;                   // fe80::/10 link-local
            if ($first === 0xff) return false;                                                     // ff00::/8 multicast
            if (substr($bin, 0, 4) === "\x20\x01\x0d\xb8") return false;                           // 2001:db8::/32 docs
        } else {
            $long = ip2long($ip);
            foreach ([['0.0.0.0', 8], ['100.64.0.0', 10], ['169.254.0.0', 16], ['192.0.0.0', 24], ['198.18.0.0', 15], ['224.0.0.0', 4], ['240.0.0.0', 4]] as [$net, $bits]) {
                $mask = -1 << (32 - $bits);
                if (($long & $mask) === (ip2long($net) & $mask)) {
                    return false;
                }
            }
        }
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}

if (!function_exists('md_http_get_safe')) {
    /**
     * GET a URL that came from a user or a third party without letting it reach
     * internal services.
     *
     * Every hop, including each redirect: HTTPS only (unless allow_http), no
     * credentials in the URL, a hostname rather than an IP literal, default ports only,
     * every resolved address publicly routable, and the connection pinned to the address
     * that was checked (defeats DNS rebinding). Redirects are followed manually, at most
     * max_redirects times. With pin_registrable_domain set, every hop must stay on that
     * registrable domain. The body is capped at max_bytes.
     *
     * $opts: timeout (5), connect_timeout (3), max_redirects (3), max_bytes (1 MiB),
     *        user_agent, headers (assoc), pin_registrable_domain (''), allow_http (false).
     *
     * @return array{ok:bool, code:int, body:string, headers:array<string,string>, final_url:string, error:string, resolved_ip:string}
     */
    function md_http_get_safe(string $url, array $opts = []): array {
        $timeout   = (int) ($opts['timeout'] ?? 5);
        $max_redir = (int) ($opts['max_redirects'] ?? 3);
        $max_bytes = (int) ($opts['max_bytes'] ?? 1048576);
        $pin       = strtolower((string) ($opts['pin_registrable_domain'] ?? ''));
        $allow_http = !empty($opts['allow_http']);
        $out = ['ok' => false, 'code' => 0, 'body' => '', 'headers' => [], 'final_url' => $url, 'error' => '', 'resolved_ip' => ''];

        if (!function_exists('curl_init')) {
            $out['error'] = 'curl_unavailable';
            return $out;
        }

        for ($hop = 0; $hop <= $max_redir; $hop++) {
            $out['final_url'] = $url;
            $p = parse_url($url);
            $scheme = strtolower((string) ($p['scheme'] ?? ''));
            $host = strtolower((string) ($p['host'] ?? ''));
            if (!in_array($scheme, $allow_http ? ['https', 'http'] : ['https'], true)) {
                $out['error'] = 'scheme_not_allowed';
                return $out;
            }
            if ($host === '' || isset($p['user']) || isset($p['pass'])) {
                $out['error'] = 'invalid_url';
                return $out;
            }
            $host = trim($host, '[]');
            if (filter_var($host, FILTER_VALIDATE_IP) || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || strpos($host, '.') === false) {
                $out['error'] = 'host_not_allowed';
                return $out;
            }
            $port = isset($p['port']) ? (int) $p['port'] : ($scheme === 'https' ? 443 : 80);
            if (!in_array($port, [443, 80], true)) {
                $out['error'] = 'port_not_allowed';
                return $out;
            }
            if ($pin !== '' && md_registrable_domain($host) !== $pin) {
                $out['error'] = $hop === 0 ? 'domain_mismatch' : 'redirect_off_domain';
                return $out;
            }

            $records = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];
            $ips = [];
            foreach ($records as $rec) {
                if (!empty($rec['ip'])) $ips[] = $rec['ip'];
                if (!empty($rec['ipv6'])) $ips[] = $rec['ipv6'];
            }
            if (!$ips) {
                $ips = @gethostbynamel($host) ?: [];
            }
            if (!$ips) {
                $out['error'] = 'dns_failed';
                return $out;
            }
            foreach ($ips as $ip) {
                if (!md_is_public_ip($ip)) {
                    $out['error'] = 'private_ip';
                    return $out;
                }
            }
            $v4 = array_values(array_filter($ips, fn($ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)));
            $ip = $v4[0] ?? $ips[0];
            $out['resolved_ip'] = $ip;

            $headers = [];
            $body = '';
            $too_large = false;
            $ch = curl_init($url);
            $header_lines = [];
            foreach ((array) ($opts['headers'] ?? []) as $k => $v) {
                if ($v !== null && $v !== '') $header_lines[] = "{$k}: {$v}";
            }
            curl_setopt_array($ch, [
                CURLOPT_RESOLVE        => [$host . ':' . $port . ':' . (strpos($ip, ':') !== false ? '[' . $ip . ']' : $ip)],
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS      => $allow_http ? (CURLPROTO_HTTPS | CURLPROTO_HTTP) : CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT => (int) ($opts['connect_timeout'] ?? 3),
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_ENCODING       => '',
                CURLOPT_USERAGENT      => (string) ($opts['user_agent'] ?? 'Modular/1.0'),
                CURLOPT_HTTPHEADER     => $header_lines,
                CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$headers) {
                    $len = strlen($line);
                    if (strpos($line, ':') !== false) {
                        [$k, $v] = explode(':', $line, 2);
                        $headers[strtolower(trim($k))] = trim($v);
                    } elseif (preg_match('#^HTTP/#i', $line)) {
                        $headers = [];
                    }
                    return $len;
                },
                CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) use (&$body, &$too_large, $max_bytes) {
                    if (strlen($body) + strlen($chunk) > $max_bytes) {
                        $too_large = true;
                        return -1;
                    }
                    $body .= $chunk;
                    return strlen($chunk);
                },
            ]);
            $exec = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $curl_err = curl_error($ch);
            curl_close($ch);

            if ($too_large) {
                $out['error'] = 'too_large';
                return $out;
            }
            if ($exec === false) {
                $out['error'] = 'fetch_failed: ' . $curl_err;
                return $out;
            }
            $out['code'] = $code;
            $out['headers'] = $headers;

            if (in_array($code, [301, 302, 303, 307, 308], true) && !empty($headers['location'])) {
                if ($hop === $max_redir) {
                    $out['error'] = 'too_many_redirects';
                    return $out;
                }
                $url = md_resolve_relative_url($url, $headers['location']);
                continue;
            }
            $out['body'] = $body;
            $out['ok'] = ($code >= 200 && $code < 300) || $code === 304;
            if (!$out['ok']) {
                $out['error'] = 'http_' . $code;
            }
            return $out;
        }
        $out['error'] = 'too_many_redirects';
        return $out;
    }
}

if (!function_exists('md_resolve_relative_url')) {
    /** Resolve a (possibly relative) URL against a base URL. */
    function md_resolve_relative_url(string $base, string $ref): string {
        $ref = trim($ref);
        if ($ref === '') {
            return $base;
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $ref)) {
            return $ref;
        }
        $b = parse_url($base);
        $scheme = ($b['scheme'] ?? 'https');
        $authority = ($b['host'] ?? '') . (isset($b['port']) ? ':' . $b['port'] : '');
        if (strpos($ref, '//') === 0) {
            return $scheme . ':' . $ref;
        }
        if ($ref[0] === '?') {
            return $scheme . '://' . $authority . ($b['path'] ?? '/') . $ref;
        }
        if ($ref[0] === '#') {
            return strtok($base, '#') . $ref;
        }
        if ($ref[0] === '/') {
            $path = $ref;
        } else {
            $dir = preg_replace('#/[^/]*$#', '/', $b['path'] ?? '/');
            $path = $dir . $ref;
        }
        $query = '';
        if (($qpos = strpos($path, '?')) !== false) {
            $query = substr($path, $qpos);
            $path = substr($path, 0, $qpos);
        }
        $segments = [];
        foreach (explode('/', $path) as $seg) {
            if ($seg === '..') {
                array_pop($segments);
            } elseif ($seg !== '.') {
                $segments[] = $seg;
            }
        }
        $path = implode('/', $segments);
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }
        return $scheme . '://' . $authority . $path . $query;
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
