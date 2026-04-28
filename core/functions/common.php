<?php
/**
 * Modular CMS — Common utility functions
 *
 * Generic PHP helpers that any module or theme can rely on.
 * No Modular, Simuze, or WordPress-specific knowledge here.
 * Loaded early in bootstrap so modules/themes never need to polyfill these.
 */

// ---------------------------------------------------------------------------
// Time / Date
// ---------------------------------------------------------------------------

if (!function_exists('current_time')) {
    function current_time($type = 'mysql', $gmt = 0) {
        return $type === 'mysql' ? date('Y-m-d H:i:s') : time();
    }
}

if (!function_exists('human_time_diff')) {
    function human_time_diff($from, $to = 0) {
        if (!$to) $to = time();
        $diff = abs($to - $from);
        if ($diff < 60)    return $diff . ' seconds';
        if ($diff < 3600)  return round($diff / 60) . ' minutes';
        if ($diff < 86400) return round($diff / 3600) . ' hours';
        return round($diff / 86400) . ' days';
    }
}

if (!function_exists('mysql2date')) {
    function mysql2date($format, $date, $translate = true) {
        if (empty($date)) return false;
        return date($format, strtotime($date));
    }
}

if (!function_exists('date_i18n')) {
    function date_i18n($format, $timestamp = false, $gmt = false) {
        if ($timestamp === false) $timestamp = time();
        return date($format, $timestamp);
    }
}

if (!function_exists('number_format_i18n')) {
    function number_format_i18n($number, $decimals = 0) {
        return number_format((float) $number, $decimals);
    }
}

// ---------------------------------------------------------------------------
// String / Array utilities
// ---------------------------------------------------------------------------

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($text, $remove_breaks = false) {
        $text = strip_tags($text);
        if ($remove_breaks) $text = preg_replace('/[\r\n\t ]+/', ' ', $text);
        return trim($text);
    }
}

if (!function_exists('wp_trim_words')) {
    function wp_trim_words($text, $num_words = 55, $more = null) {
        if ($more === null) $more = '&hellip;';
        $text  = wp_strip_all_tags($text);
        $words = preg_split('/\s+/', $text, $num_words + 1);
        if (count($words) > $num_words) {
            array_pop($words);
            return implode(' ', $words) . $more;
        }
        return implode(' ', $words);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0) {
        return json_encode($data, $options ?: (JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}

if (!function_exists('maybe_serialize')) {
    function maybe_serialize($data) {
        return (is_array($data) || is_object($data)) ? serialize($data) : $data;
    }
}

if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize($data) {
        if (is_serialized($data)) return @unserialize($data);
        return $data;
    }
}

if (!function_exists('is_serialized')) {
    function is_serialized($data) {
        if (!is_string($data)) return false;
        $data = trim($data);
        if ($data === 'N;') return true;
        if (strlen($data) < 4) return false;
        if ($data[1] !== ':') return false;
        return (bool) preg_match('/^[aOCbids]:[0-9]/', $data);
    }
}

if (!function_exists('absint')) {
    function absint($v) {
        return abs((int) $v);
    }
}

if (!function_exists('shortcode_atts')) {
    function shortcode_atts($defaults, $atts = []) {
        $atts = is_array($atts) ? $atts : [];
        return array_merge($defaults, array_intersect_key($atts, $defaults));
    }
}

if (!function_exists('untrailingslashit')) {
    function untrailingslashit($str) {
        return rtrim($str, '/\\');
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url = null) {
        if ($url === null) {
            $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                 . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                 . ($_SERVER['REQUEST_URI'] ?? '/');
        }
        $parts = parse_url($url);
        $query = [];
        if (!empty($parts['query'])) parse_str($parts['query'], $query);
        foreach ((array) $args as $k => $v) {
            if ($v === false) unset($query[$k]);
            else $query[$k] = $v;
        }
        $base = ($parts['scheme'] ?? 'http') . '://' . ($parts['host'] ?? 'localhost');
        if (!empty($parts['port'])) $base .= ':' . $parts['port'];
        $base .= ($parts['path'] ?? '/');
        if (!empty($query)) $base .= '?' . http_build_query($query);
        if (!empty($parts['fragment'])) $base .= '#' . $parts['fragment'];
        return $base;
    }
}

if (!function_exists('remove_query_arg')) {
    function remove_query_arg($key, $url = null) {
        return add_query_arg(
            is_array($key) ? array_fill_keys($key, false) : [$key => false],
            $url
        );
    }
}

// ---------------------------------------------------------------------------
// Output escaping helpers not already in template-helpers.php
// ---------------------------------------------------------------------------

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        return preg_replace('/[^a-zA-Z0-9\-._~:\/?#\[\]@!$&\'()*+,;=%]/', '', $url) ?: $url;
    }
}

if (!function_exists('esc_js')) {
    function esc_js($text) {
        return addslashes(htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'));
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = '') {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = '') {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = '') {
        echo htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = '') {
        echo htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

// ---------------------------------------------------------------------------
// File / Directory helpers
// ---------------------------------------------------------------------------

if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars)       $chars .= '!@#$%^&*()';
        if ($extra_special_chars) $chars .= '-_ []{}<>~`+=,.;:/?|';
        $password = '';
        $max      = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target) {
        $target = rtrim($target, '/');
        if (empty($target) || is_dir($target)) return true;
        if (file_exists($target)) return false;
        return @mkdir($target, 0755, true);
    }
}

if (!function_exists('size_format')) {
    function size_format($bytes, $decimals = 0) {
        $quant = ['TB' => 1099511627776, 'GB' => 1073741824, 'MB' => 1048576, 'KB' => 1024, 'B' => 1];
        foreach ($quant as $unit => $mag) {
            if ((float) $bytes >= $mag) {
                return number_format($bytes / $mag, $decimals) . ' ' . $unit;
            }
        }
        return false;
    }
}

// ---------------------------------------------------------------------------
// Form helpers
// ---------------------------------------------------------------------------

if (!function_exists('disabled')) {
    function disabled($disabled, $current = true, $echo = true) {
        $result = ($disabled == $current) ? ' disabled="disabled"' : '';
        if ($echo) { echo $result; }
        return $result;
    }
}

// ---------------------------------------------------------------------------
// HTTP / Network helpers
// ---------------------------------------------------------------------------

if (!function_exists('is_ssl')) {
    function is_ssl() {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) return true;
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
        return false;
    }
}

if (!function_exists('wp_redirect')) {
    function wp_redirect($location, $status = 302, $x_redirect_by = '') {
        header('Location: ' . $location, true, $status);
        return true;
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($location, $status = 302) {
        header('Location: ' . $location, true, $status);
        exit;
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = []) {
        if ($title !== '') {
            echo '<h1>' . htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') . '</h1>';
        }
        echo '<p>' . htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') . '</p>';
        exit;
    }
}

if (!function_exists('status_header')) {
    function status_header($code, $description = '') {
        http_response_code((int) $code);
    }
}

if (!function_exists('wp_oembed_get')) {
    function wp_oembed_get($url, $args = []) {
        if (!is_string($url) || trim($url) === '') return '';
        $url    = trim($url);
        $width  = isset($args['width'])  ? (int) $args['width']  : 640;
        $height = isset($args['height']) ? (int) $args['height'] : 360;
        $esc    = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]+)#', $url, $m)) {
            $vid = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            return '<iframe width="' . $width . '" height="' . $height . '" src="https://www.youtube.com/embed/' . $vid . '?rel=0" frameborder="0" allowfullscreen></iframe>';
        }
        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
            $vid = (int) $m[1];
            return '<iframe width="' . $width . '" height="' . $height . '" src="https://player.vimeo.com/video/' . $vid . '" frameborder="0" allowfullscreen></iframe>';
        }
        return '<a href="' . $esc . '" rel="noopener" target="_blank">' . $esc . '</a>';
    }
}

// ---------------------------------------------------------------------------
// Route / query context
// ---------------------------------------------------------------------------

if (!function_exists('get_query_var')) {
    function get_query_var($var, $default = '') {
        $vars = $GLOBALS['simuze_query_vars'] ?? [];
        return $vars[$var] ?? ($_GET[$var] ?? $default);
    }
}

if (!function_exists('set_query_var')) {
    function set_query_var($var, $value) {
        if (!isset($GLOBALS['simuze_query_vars'])) {
            $GLOBALS['simuze_query_vars'] = [];
        }
        $GLOBALS['simuze_query_vars'][$var] = $value;
    }
}
