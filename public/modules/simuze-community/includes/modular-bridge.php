<?php
/**
 * Modular CMS bridge for Simuze Community
 * Provides WP-compatible API (wpdb, get_option) when running inside Modular so plugin classes work unchanged.
 * Only loaded when module.php is the entry point (SIMUZE_MODULAR_RUN defined).
 */

if (!defined('SIMUZE_MODULAR_RUN')) {
    return;
}

// WP DB output format constants
if (!defined('OBJECT'))  define('OBJECT',  'OBJECT');
if (!defined('ARRAY_A')) define('ARRAY_A', 'ARRAY_A');
if (!defined('ARRAY_N')) define('ARRAY_N', 'ARRAY_N');

if (!function_exists('is_admin')) {
    function is_admin() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return (strpos($uri, '/admin') !== false && strpos($uri, '/admin/api') !== 0);
    }
}

// Stub WP admin menu functions (Modular uses admin_menu_items filter instead)
if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback = null, $icon = '', $position = null) {
        return null;
    }
}
if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = null) {
        return null;
    }
}
if (!function_exists('register_setting')) {
    function register_setting($option_group, $option_name, $args = []) {
        return null;
    }
}
// URL and redirect helpers
if (!function_exists('home_url')) {
    function home_url($path = '') {
        $base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
        if ($base === '' && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base = $scheme . '://' . $_SERVER['HTTP_HOST'] . (dirname($_SERVER['SCRIPT_NAME'] ?? '') === '/' ? '' : rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'));
        }
        return $base . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}
if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($location, $status = 302) {
        header('Location: ' . $location, true, $status);
        exit;
    }
}
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0) {
        return json_encode($data, $options ?: (JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return ($thing instanceof WP_Error);
    }
}
if (!function_exists('is_ssl')) {
    function is_ssl() {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
        return false;
    }
}
if (!class_exists('WP_Error')) {
    class WP_Error {
        public $errors = [], $error_data = [];
        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) $this->errors[$code][] = $message;
            if (!empty($data)) $this->error_data[$code] = $data;
        }
        public function get_error_code() {
            $codes = array_keys($this->errors);
            return $codes ? $codes[0] : '';
        }
        public function get_error_message($code = null) {
            $code = $code ?: $this->get_error_code();
            return isset($this->errors[$code][0]) ? $this->errors[$code][0] : '';
        }
        public function get_error_data($code = null) {
            $code = $code ?: $this->get_error_code();
            return isset($this->error_data[$code]) ? $this->error_data[$code] : null;
        }
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return is_string($str) ? trim(strip_tags($str)) : '';
    }
}
if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        return preg_replace('/[^a-zA-Z0-9\-._~:\/?#\[\]@!$&\'()*+,;=%]/', '', $url) ?: $url;
    }
}
// Query vars for route context (profile tab, group handle, etc.)
if (!function_exists('get_query_var')) {
    function get_query_var($var, $default = '') {
        $vars = $GLOBALS['simuze_query_vars'] ?? [];
        return isset($vars[$var]) ? $vars[$var] : (isset($_GET[$var]) ? $_GET[$var] : $default);
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
if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        return null;
    }
}
if (!function_exists('shortcode_atts')) {
    function shortcode_atts($defaults, $atts = []) {
        $atts = is_array($atts) ? $atts : [];
        return array_merge($defaults, array_intersect_key($atts, $defaults));
    }
}
if (!function_exists('esc_url')) {
    function esc_url($url) {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (!function_exists('get_transient')) {
    function get_transient($key) {
        return simuze_modular_get_option('_transient_' . $key, false);
    }
}
if (!function_exists('set_transient')) {
    function set_transient($key, $value, $expiration = 0) {
        return update_option('_transient_' . $key, is_string($value) ? $value : json_encode($value));
    }
}
if (!function_exists('current_time')) {
    function current_time($type = 'mysql', $gmt = 0) {
        return $type === 'mysql' ? date('Y-m-d H:i:s') : time();
    }
}
if (!function_exists('check_admin_referer')) {
    function check_admin_referer($action = -1, $query_arg = '_wpnonce') {
        $nonce = isset($_REQUEST[$query_arg]) ? $_REQUEST[$query_arg] : '';
        return $nonce !== '';
    }
}
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = []) {
        if ($title !== '') {
            echo '<h1>' . htmlspecialchars($title) . '</h1>';
        }
        echo '<p>' . htmlspecialchars($message) . '</p>';
        exit;
    }
}
if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data) {
        return is_string($data) ? strip_tags($data) : $data;
    }
}
// oEmbed stub: return embed HTML for video URLs (YouTube, Vimeo) or a safe link fallback.
if (!function_exists('wp_oembed_get')) {
    function wp_oembed_get($url, $args = []) {
        if (!is_string($url) || trim($url) === '') {
            return '';
        }
        $url = trim($url);
        $width  = isset($args['width'])  ? (int) $args['width']  : 640;
        $height = isset($args['height']) ? (int) $args['height'] : 360;
        $esc_url = function_exists('esc_url') ? esc_url($url) : htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        // YouTube: watch and youtu.be
        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]+)#', $url, $m)) {
            $vid = $m[1];
            return '<iframe width="' . (int) $width . '" height="' . (int) $height . '" src="https://www.youtube.com/embed/' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8') . '?rel=0" frameborder="0" allowfullscreen></iframe>';
        }
        // Vimeo
        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
            $vid = $m[1];
            return '<iframe width="' . (int) $width . '" height="' . (int) $height . '" src="https://player.vimeo.com/video/' . (int) $vid . '" frameborder="0" allowfullscreen></iframe>';
        }
        // Fallback: safe link
        return '<a href="' . $esc_url . '" rel="noopener" target="_blank">' . $esc_url . '</a>';
    }
}
// REST API base URL for Modular (frontend posts to api/simuze/v1/groups etc.)
if (!function_exists('rest_url')) {
    function rest_url($path = '') {
        $base = function_exists('home_url') ? home_url() : (defined('SITE_URL') ? SITE_URL : '');
        $base = rtrim($base, '/');
        $path = ltrim(preg_replace('#^simuze/v1/?#', '', $path), '/');
        return $base . '/api/simuze/v1/' . ($path !== '' ? $path . (substr($path, -1) === '/' ? '' : '') : '');
    }
}
// Used by save_cropped_image and other REST/upload code
if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars) $chars .= '!@#$%^&*()';
        if ($extra_special_chars) $chars .= '-_ []{}<>~`+=,.;:/?|';
        $password = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }
}
// Request adapter for Modular REST dispatch (api/simuze/v1/*)
if (!class_exists('Simuze_REST_Request_Adapter')) {
    class Simuze_REST_Request_Adapter {
        private $params;
        private $files;
        private $content_type;
        public function __construct($route_path = '', $method = '') {
            $this->params = array_merge($_GET ?? [], $_POST ?? []);
            $this->files = $_FILES ?? [];
            $ct = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
            $this->content_type = ['value' => $ct];
        }
        public function get_params() { return $this->params; }
        public function get_file_params() { return $this->files; }
        public function get_content_type() { return $this->content_type; }
        public function get_json_params() {
            $raw = file_get_contents('php://input');
            if ($raw === false || $raw === '') return null;
            $json = json_decode($raw, true);
            return is_array($json) ? $json : null;
        }
    }
}
// REST response wrapper so REST API can return rest_ensure_response($data)
if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response($data) {
        return new Simuze_REST_Response($data);
    }
}
if (!class_exists('Simuze_REST_Response')) {
    class Simuze_REST_Response {
        private $data;
        public function __construct($data) { $this->data = $data; }
        public function get_data() { return $this->data; }
    }
}
// Modular: admin URL and AJAX endpoint URL (frontend posts to /api/simuze-ajax)
if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        if (ltrim($path, '/') === 'admin-ajax.php') {
            $base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : (function_exists('base_url') ? rtrim(base_url(), '/') : '');
            return $base . '/api/simezu-ajax.php';
        }
        $base = defined('ADMIN_URL') ? rtrim(ADMIN_URL, '/') : (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') . '/admin' : '/admin');
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}
// HTTP API stubs (use cURL or file_get_contents)
if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = []) {
        return simuze_modular_http_request('GET', $url, $args);
    }
}
if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = []) {
        return simuze_modular_http_request('POST', $url, $args);
    }
}
if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return is_array($response) && isset($response['body']) ? $response['body'] : '';
    }
}
if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return is_array($response) && isset($response['response']['code']) ? (int)$response['response']['code'] : 0;
    }
}
if (!function_exists('simuze_modular_http_request')) {
    function simuze_modular_http_request($method, $url, $args = []) {
        $headers = $args['headers'] ?? [];
        $body = $args['body'] ?? null;
        $timeout = $args['timeout'] ?? 15;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $outHeaders = [];
        foreach ($headers as $k => $v) {
            $outHeaders[] = $k . ': ' . $v;
        }
        if (!empty($outHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $outHeaders);
        }
        if ($method === 'POST' && $body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? http_build_query($body) : $body);
        }
        $respBody = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['body' => $respBody, 'response' => ['code' => $code]];
    }
}

/**
 * PDO wrapper that mimics $wpdb for plugin code (get_var, get_col, get_row, prepare, query, insert, update, get_charset_collate).
 */
class Simuze_Wpdb_Compat {
    /** @var PDO */
    public $db;
    /** @var string Table prefix (empty in Modular — simuze tables are prefixed by SIMUZE_DB_PREFIX) */
    public $prefix = '';
    /** @var int Last insert ID */
    public $insert_id = 0;
    /** @var string|null Last error */
    public $last_error = null;

    // WP-specific table stubs: return recognisable non-existent names so SQL
    // referencing them fails cleanly (not fatally) when wrapped in try/catch.
    public $posts    = '__wp_posts_not_available__';
    public $postmeta = '__wp_postmeta_not_available__';

    public function __construct(PDO $db, $prefix = 'simuze_') {
        $this->db = $db;
        $this->prefix = $prefix;
    }

    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }

    public function esc_like($str) {
        return addcslashes((string) $str, '%_\\');
    }

    public function prepare($query, ...$args) {
        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }
        // Convert WP-style %d, %s, %f to PDO placeholders ?
        $query = preg_replace('/%([dsf])/', '?', $query);
        // MySQL does not support parameterized SHOW/DESCRIBE statements.
        // For these, interpolate values directly (safe: table/column names from internal code only).
        $upper = strtoupper(ltrim($query));
        if (preg_match('/^(SHOW|DESCRIBE|DESC)\s/i', $upper) && count($args) > 0) {
            $interpolated = $query;
            foreach (array_values($args) as $val) {
                $escaped = $this->db->quote((string) $val); // returns 'value' with quotes
                $interpolated = preg_replace('/\?/', $escaped, $interpolated, 1);
            }
            return $interpolated; // get_var etc. will execute this as a plain string
        }
        try {
            $stmt = $this->db->prepare($query);
        } catch (\PDOException $e) {
            error_log('Simuze_Wpdb_Compat::prepare() PDOException: ' . $e->getMessage() . ' | SQL: ' . $query);
            return $query;
        }
        if ($stmt === false) {
            return $query;
        }
        return new Simuze_Prepared_Statement($stmt, array_values($args));
    }

    public function query($query) {
        try {
            $this->db->exec($query);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function get_var($query, $x = 0, $y = 0) {
        try {
            if ($query instanceof Simuze_Prepared_Statement) {
                $query->execute();
                $row = $query->stmt->fetch(PDO::FETCH_NUM);
                return $row ? ($row[$x] ?? null) : null;
            }
            $stmt = $this->db->query($query);
            if ($stmt === false) return null;
            $row = $stmt->fetch(PDO::FETCH_NUM);
            return $row ? ($row[$x] ?? null) : null;
        } catch (\PDOException $e) {
            $this->last_error = $e->getMessage();
            return null;
        }
    }

    public function get_col($query, $x = 0) {
        try {
            if ($query instanceof Simuze_Prepared_Statement) {
                $query->execute();
                return $query->stmt->fetchAll(PDO::FETCH_COLUMN, $x);
            }
            $stmt = $this->db->query($query);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, $x) : [];
        } catch (\PDOException $e) {
            $this->last_error = $e->getMessage();
            return [];
        }
    }

    public function get_row($query, $output = OBJECT, $y = 0) {
        try {
            if ($query instanceof Simuze_Prepared_Statement) {
                $query->execute();
                $row = $query->stmt->fetch($output === ARRAY_A ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);
                return $row ?: null;
            }
            $stmt = $this->db->query($query);
            if ($stmt === false) return null;
            $row = $stmt->fetch($output === ARRAY_A ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);
            return $row ?: null;
        } catch (\PDOException $e) {
            $this->last_error = $e->getMessage();
            return null;
        }
    }

    public function get_results($query, $output = OBJECT) {
        try {
            if ($query instanceof Simuze_Prepared_Statement) {
                $query->execute();
                return $query->stmt->fetchAll($output === ARRAY_A ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);
            }
            $stmt = $this->db->query($query);
            return $stmt ? $stmt->fetchAll($output === ARRAY_A ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ) : [];
        } catch (\PDOException $e) {
            $this->last_error = $e->getMessage();
            return [];
        }
    }

    public function insert($table, $data, $format = null) {
        $cols = array_keys($data);
        $placeholders = array_map(function () { return '?'; }, $data);
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
        try {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) return false;
            $result = $stmt->execute(array_values($data));
            if ($result) { $this->insert_id = (int) $this->db->lastInsertId(); }
            return $result;
        } catch (\PDOException $e) {
            $this->last_error = $e->getMessage();
            return false;
        }
    }

    public function update($table, $data, $where, $format = null, $where_format = null) {
        $set = [];
        $values = array_values($data);
        foreach (array_keys($data) as $k) {
            $set[] = "`$k` = ?";
        }
        $where_clause = [];
        foreach ($where as $k => $v) {
            $where_clause[] = "`$k` = ?";
            $values[] = $v;
        }
        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $set) . ' WHERE ' . implode(' AND ', $where_clause);
        $stmt = $this->db->prepare($sql);
        return $stmt ? $stmt->execute($values) : false;
    }
}

class Simuze_Prepared_Statement {
    public $stmt;
    public $args;

    public function __construct(PDOStatement $stmt, array $args) {
        $this->stmt = $stmt;
        $this->args = $args;
    }

    public function execute() {
        return $this->stmt->execute($this->args);
    }
}

/**
 * get_option / update_option / delete_option using Modular settings table
 */
if (!function_exists('simuze_modular_get_option')) {
    function simuze_modular_get_option($key, $default = false) {
        if (!isset($GLOBALS['db'])) {
            return $default;
        }
        try {
            $stmt = $GLOBALS['db']->prepare("SELECT option_value FROM settings WHERE option_name = ?");
            $stmt->execute([$key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return $default;
            $v = $row['option_value'];
            if (in_array($v, ['true', 'false', '1', '0'], true)) return $v === 'true' || $v === '1';
            if (is_numeric($v)) return strpos($v, '.') !== false ? (float)$v : (int)$v;
            return $v;
        } catch (Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('simuze_modular_update_option')) {
    function simuze_modular_update_option($key, $value) {
        if (!isset($GLOBALS['db'])) return false;
        if (is_bool($value)) $value = $value ? '1' : '0';
        $value = (string)$value;
        try {
            $stmt = $GLOBALS['db']->prepare("INSERT INTO settings (option_name, option_value, autoload, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW()) ON DUPLICATE KEY UPDATE option_value = VALUES(option_value), updated_at = NOW()");
            return $stmt->execute([$key, $value]);
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('simuze_modular_delete_option')) {
    function simuze_modular_delete_option($key) {
        if (!isset($GLOBALS['db'])) return false;
        try {
            $stmt = $GLOBALS['db']->prepare("DELETE FROM settings WHERE option_name = ?");
            return $stmt->execute([$key]);
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * get_option / update_option / delete_option for plugin code (use Modular settings table).
 * Only defined when bridge is loaded (Modular context).
 */
if (!function_exists('get_option')) {
    function get_option($key, $default = false) {
        return simuze_modular_get_option($key, $default);
    }
}
if (!function_exists('update_option')) {
    function update_option($key, $value) {
        return simuze_modular_update_option($key, $value);
    }
}
if (!function_exists('delete_option')) {
    function delete_option($key) {
        return simuze_modular_delete_option($key);
    }
}

// Output escaping / i18n stubs
if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_js')) {
    function esc_js($text) { return addslashes(htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8')); }
}
if (!function_exists('esc_textarea')) {
    /**
     * Minimal esc_textarea implementation for Modular.
     * Mirrors WordPress behavior closely enough for our templates:
     * - treats input as string
     * - converts special chars to HTML entities
     */
    function esc_textarea($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = '') { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = '') { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('__')) {
    function __($text, $domain = '') { return $text; }
}
if (!function_exists('_e')) {
    function _e($text, $domain = '') { echo $text; }
}
if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain = '') { return $number == 1 ? $single : $plural; }
}
if (!function_exists('_x')) {
    function _x($text, $context, $domain = '') { return $text; }
}
if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = '') { echo htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = '') { echo htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('number_format_i18n')) {
    function number_format_i18n($number, $decimals = 0) { return number_format((float)$number, $decimals); }
}
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return bin2hex(random_bytes(16));
    }
}
if (!function_exists('wp_nonce_url')) {
    function wp_nonce_url($actionurl, $action = -1, $name = '_wpnonce') {
        $nonce = wp_create_nonce($action);
        $sep = (strpos($actionurl, '?') !== false) ? '&' : '?';
        return $actionurl . $sep . $name . '=' . $nonce;
    }
}
if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true, $echo = true) {
        $nonce = wp_create_nonce($action);
        $field = '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($nonce) . '">';
        if ($echo) { echo $field; }
        return $field;
    }
}
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) { return !empty($nonce); }
}
if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) {
        $args = array_slice(func_get_args(), 1);
        return $value;
    }
}
if (!function_exists('do_shortcode')) {
    function do_shortcode($content) { return $content; }
}
if (!function_exists('absint')) {
    function absint($v) { return abs((int)$v); }
}
if (!function_exists('intval')) {
    // intval is a PHP built-in, but just in case
}
if (!function_exists('checked')) {
    function checked($checked, $current = true, $echo = true) {
        $result = ($checked == $current) ? ' checked="checked"' : '';
        if ($echo) { echo $result; }
        return $result;
    }
}
if (!function_exists('selected')) {
    function selected($selected, $current = true, $echo = true) {
        $result = ($selected == $current) ? ' selected="selected"' : '';
        if ($echo) { echo $result; }
        return $result;
    }
}
if (!function_exists('disabled')) {
    function disabled($disabled, $current = true, $echo = true) {
        $result = ($disabled == $current) ? ' disabled="disabled"' : '';
        if ($echo) { echo $result; }
        return $result;
    }
}
if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = []) {
        if (is_object($args)) $args = get_object_vars($args);
        if (is_string($args)) {
            parse_str($args, $parsed);
            $args = $parsed;
        }
        if (!is_array($args)) $args = [];
        return array_merge((array) $defaults, $args);
    }
}
if (!function_exists('wp_redirect')) {
    function wp_redirect($location, $status = 302, $x_redirect_by = 'WordPress') {
        header('Location: ' . $location, true, $status);
        return true;
    }
}
if (!function_exists('get_template_directory_uri')) {
    function get_template_directory_uri() {
        // In Modular, return the active theme's web-accessible URL
        $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : home_url();
        $theme = defined('ACTIVE_THEME') ? ACTIVE_THEME : (defined('DEFAULT_THEME') ? DEFAULT_THEME : 'simuze');
        return $base . '/public/themes/' . $theme;
    }
}
if (!function_exists('get_template_directory')) {
    function get_template_directory() {
        $path = defined('THEMES_PATH') ? THEMES_PATH : (defined('PUBLIC_PATH') ? PUBLIC_PATH . '/themes' : __DIR__ . '/../../../themes');
        $theme = defined('ACTIVE_THEME') ? ACTIVE_THEME : (defined('DEFAULT_THEME') ? DEFAULT_THEME : 'simuze');
        return rtrim($path, '/') . '/' . $theme;
    }
}
if (!function_exists('trailingslashit')) {
    function trailingslashit($str) { return rtrim($str, '/\\') . '/'; }
}
if (!function_exists('untrailingslashit')) {
    function untrailingslashit($str) { return rtrim($str, '/\\'); }
}
if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url = null) {
        if ($url === null) {
            $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
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
        return add_query_arg(is_array($key) ? array_fill_keys($key, false) : [$key => false], $url);
    }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '') {
        if ($show === 'name') return defined('SITE_NAME') ? SITE_NAME : 'Simuze';
        if ($show === 'url') return defined('SITE_URL') ? SITE_URL : home_url();
        return '';
    }
}
if (!function_exists('current_user_can')) {
    /**
     * Minimal capability check for Modular context.
     * Used by Simuze_Auth::is_admin() and REST endpoints.
     *
     * We don't have WordPress roles here, so we map to:
     * - manage_options → true when a Simuze admin is detected
     * - everything else → false by default
     */
    function current_user_can($capability) {
        // Prefer Simuze_Auth if available
        if (class_exists('Simuze_Auth')) {
            try {
                $auth = \Simuze_Auth::get_instance();
                if ($capability === 'manage_options') {
                    return $auth->is_admin();
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }

        // Fallback: check session flag set by auth integration
        if ($capability === 'manage_options') {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                return true;
            }
            if (!empty($_SESSION['is_admin'])) {
                return true;
            }
        }

        return false;
    }
}
if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        $uploadsPath = defined('UPLOADS_PATH') ? UPLOADS_PATH : (defined('PUBLIC_PATH') ? PUBLIC_PATH . '/uploads' : __DIR__ . '/../../uploads');
        $uploadsUrl  = defined('UPLOADS_WEB_PATH') ? UPLOADS_WEB_PATH : '/uploads';
        if (!empty($_SERVER['HTTP_HOST']) && (strpos($uploadsUrl, 'http') !== 0)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base = (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '');
            $uploadsUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $base . (strpos($uploadsUrl, '/') === 0 ? '' : '/') . $uploadsUrl;
        }
        return ['path' => $uploadsPath, 'url' => $uploadsUrl, 'basedir' => $uploadsPath, 'baseurl' => $uploadsUrl, 'error' => false];
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

/**
 * Single general crop-and-save for all images (album covers, avatars, headers).
 * Decodes base64 data URL, saves to directory, returns public URL.
 * Used by group create/update, user avatar/header, and album artwork.
 *
 * @param string $base64_data Data URL e.g. data:image/png;base64,...
 * @param string $upload_dir  Absolute directory path to save the file
 * @param string $prefix      Filename prefix (avatar, header, cover, etc.)
 * @return string|false      Public URL of saved image or false on failure
 */
if (!function_exists('simuze_save_cropped_image')) {
    function simuze_save_cropped_image($base64_data, $upload_dir, $prefix = 'image') {
        if (!is_string($base64_data) || $base64_data === '') {
            return false;
        }
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_data, $m)) {
            $base64_data = substr($base64_data, strpos($base64_data, ',') + 1);
            $type = strtolower($m[1]);
            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                return false;
            }
            $decoded = base64_decode($base64_data, true);
            if ($decoded === false) {
                return false;
            }
        } else {
            return false;
        }
        $ext = ($type === 'jpeg') ? 'jpg' : $type;
        $filename = $prefix . '_' . time() . '_' . (function_exists('wp_generate_password') ? wp_generate_password(8, false) : bin2hex(random_bytes(4))) . '.' . $ext;
        if (!is_dir($upload_dir) && function_exists('wp_mkdir_p')) {
            wp_mkdir_p($upload_dir);
        }
        $filepath = rtrim($upload_dir, '/') . '/' . $filename;
        if (file_put_contents($filepath, $decoded) === false) {
            return false;
        }
        $upload_info = function_exists('wp_upload_dir') ? wp_upload_dir() : ['baseurl' => '/uploads', 'basedir' => rtrim($upload_dir, '/')];
        $baseurl = $upload_info['baseurl'];
        $basedir = rtrim($upload_info['basedir'], '/');
        $upload_dir_clean = rtrim($upload_dir, '/');
        $rel = (strpos($upload_dir_clean, $basedir) === 0)
            ? trim(substr($upload_dir_clean, strlen($basedir)), '/')
            : (strpos($upload_dir_clean, 'simuze-groups') !== false ? 'simuze-groups' : (strpos($upload_dir_clean, 'simuze-headers') !== false ? trim(str_replace($basedir . '/', '', $upload_dir_clean), '/') : 'simuze-users'));
        return rtrim($baseurl, '/') . '/' . $rel . ($rel !== '' ? '/' : '') . $filename;
    }
}

/**
 * Ensure Cropper.js assets are loaded once (front-end image cropping).
 * Shared by profile settings, group settings, and create-group flows.
 */
if (!function_exists('simuze_output_cropper_assets')) {
    function simuze_output_cropper_assets(): void {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $version = '1.6.1';
        $css_url = 'https://cdn.jsdelivr.net/npm/cropperjs@' . $version . '/dist/cropper.min.css';
        $js_url  = 'https://cdn.jsdelivr.net/npm/cropperjs@' . $version . '/dist/cropper.min.js';

        echo '<link rel="stylesheet" href="' . htmlspecialchars($css_url, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
        echo '<script src="' . htmlspecialchars($js_url, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
    }
}
if (!function_exists('simuze_get_album_url')) {
    /**
     * Build a Modular album URL from an album post-ID or md_music_albums.id.
     * Looks up album_nicename from md_music_albums (by post_id or id).
     */
    function simuze_get_album_url($album_id, $group_id = null) {
        if (empty($album_id)) return '';
        $row  = md_db_row("SELECT id, album_nicename FROM md_music_albums WHERE post_id = ? OR id = ? LIMIT 1", [$album_id, $album_id]);
        $slug = (!empty($row) && !empty($row['album_nicename'])) ? $row['album_nicename'] : ((!empty($row)) ? $row['id'] : $album_id);
        return md_home_url('/music/album/' . $slug);
    }
}
if (!function_exists('size_format')) {
    function size_format($bytes, $decimals = 0) {
        $quant = ['TB' => 1099511627776, 'GB' => 1073741824, 'MB' => 1048576, 'KB' => 1024, 'B' => 1];
        foreach ($quant as $unit => $mag) {
            if (doubleval($bytes) >= $mag) return number_format($bytes / $mag, $decimals) . ' ' . $unit;
        }
        return false;
    }
}
if (!function_exists('human_time_diff')) {
    function human_time_diff($from, $to = 0) {
        if (!$to) $to = time();
        $diff = abs($to - $from);
        if ($diff < 60) return $diff . ' seconds';
        if ($diff < 3600) return round($diff/60) . ' minutes';
        if ($diff < 86400) return round($diff/3600) . ' hours';
        return round($diff/86400) . ' days';
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
if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($text, $remove_breaks = false) {
        $text = strip_tags($text);
        if ($remove_breaks) $text = preg_replace('/[\r\n\t ]+/', ' ', $text);
        return trim($text);
    }
}
if (!function_exists('wp_trim_words')) {
    function wp_trim_words($text, $num_words = 55, $more = null) {
        if (null === $more) $more = '&hellip;';
        $text = wp_strip_all_tags($text);
        $words = preg_split('/\s+/', $text, $num_words + 1);
        if (count($words) > $num_words) {
            array_pop($words);
            return implode(' ', $words) . $more;
        }
        return implode(' ', $words);
    }
}
if (!function_exists('maybe_serialize')) {
    function maybe_serialize($data) {
        return is_array($data) || is_object($data) ? serialize($data) : $data;
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
        return (bool)preg_match('/^[aOCbids]:[0-9]/', $data);
    }
}

// ---------------------------------------------------------------------------
// WP enqueue / nonce / admin stubs
// ---------------------------------------------------------------------------
if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style() {} // Modular loads CSS in <head> directly
}
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script() {} // Modular loads JS in <head> directly
}
if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        // Output inline <script> — we're already inside the page body
        echo '<script>var ' . $object_name . ' = ' . wp_json_encode($l10n) . ';</script>' . "\n";
    }
}
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        if (session_status() === PHP_SESSION_NONE) @session_start();
        $key = 'nonce_' . md5((string) $action);
        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = bin2hex(random_bytes(16));
        }
        return $_SESSION[$key];
    }
}
if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : home_url();
        if ($path === '' || $path === 'admin-ajax.php') {
            return $base . '/api/simezu-ajax.php';
        }
        return $base . '/api/' . ltrim($path, '/');
    }
}
if (!function_exists('locate_template')) {
    function locate_template($template_names, $load = false, $require_once = true) {
        $dir = get_template_directory();
        foreach ((array) $template_names as $name) {
            $file = $dir . '/' . ltrim($name, '/');
            if (is_file($file)) {
                if ($load) {
                    if ($require_once) require_once $file; else require $file;
                }
                return $file;
            }
        }
        return '';
    }
}

// ---------------------------------------------------------------------------
// Music helper stubs (used by group-music.php and theme templates)
// ---------------------------------------------------------------------------
if (!function_exists('simuze_get_albums_by_status')) {
    /**
     * Fetch albums for a group filtered by status, formatted for album-grid.php.
     */
    function simuze_get_albums_by_status($group_id, $status, $user_id = null) {
        $sql    = "SELECT * FROM md_music_albums WHERE group_id = ? AND status = ? AND deleted_at IS NULL";
        $params = [(int) $group_id, $status];
        if ($user_id !== null) {
            $sql     .= " AND created_by = ?";
            $params[] = (int) $user_id;
        }
        $sql .= " ORDER BY release_date DESC, created_at DESC";
        $rows = md_db_query($sql, $params);
        if (!$rows) return [];
        $upload = md_upload_dir();
        $albums = [];
        foreach ($rows as $row) {
            $track_count = (int) md_db_var(
                "SELECT COUNT(*) FROM md_music_uploads WHERE album_id = ? AND status != 'deleted'",
                [(int) $row['id']]
            );
            $artwork_url = '';
            $raw_art = !empty($row['artwork_path']) ? $row['artwork_path'] : ($row['artwork_thumbnail_path'] ?? '');
            if (!empty($raw_art)) {
                // If it looks like a web-relative URL, serve from site root
                if (str_starts_with($raw_art, '/') && !str_starts_with($raw_art, $upload['basedir'])) {
                    $artwork_url = md_home_url($raw_art);
                } else {
                    $rel = ltrim(str_replace($upload['basedir'], '', $raw_art), '/');
                    $artwork_url = $upload['baseurl'] . '/' . $rel;
                }
            }
            $nicename  = !empty($row['album_nicename']) ? $row['album_nicename'] : $row['id'];
            $album_url = md_home_url('/music/album/' . $nicename);
            $rel_date  = $row['release_date'] ?: $row['created_at'];
            $fmt_date  = !empty($rel_date) ? date('Y-m-d', strtotime($rel_date)) : '';
            $albums[] = [
                'id'                     => $row['id'],
                'album_name'             => $row['album_name'],
                'description'            => $row['description'],
                'artwork_url'            => $artwork_url,
                'artwork_thumbnail_url'  => $artwork_url,
                'release_date'           => $rel_date,
                'formatted_release_date' => $fmt_date,
                'post_date'              => $row['created_at'],
                'track_count'            => $track_count,
                'view_url'               => $album_url,
                'album_url'              => $album_url,
                'album_post_id'          => $row['post_id'] ?? null,
                'is_single'              => isset($row['is_album']) && $row['is_album'] == 0,
                'group_id'               => $row['group_id'],
                'status'                 => $row['status'],
                'artist_name'            => '',
                'artist_id'              => $row['artist_id'] ?? null,
            ];
        }
        return $albums;
    }
}
if (!function_exists('simuze_get_album_for_editing')) {
    /**
     * Fetch a single album with its tracks for the edit form.
     * Returns false if not found or permission denied.
     */
    function simuze_get_album_for_editing($album_id, $group_id = null, $user_id = null) {
        $sql    = "SELECT * FROM md_music_albums WHERE id = ? AND deleted_at IS NULL";
        $params = [(int) $album_id];
        if ($group_id) {
            $sql     .= " AND group_id = ?";
            $params[] = (int) $group_id;
        }
        $row = md_db_row($sql, $params);
        if (!$row) return false;
        $tracks = md_db_query(
            "SELECT * FROM md_music_uploads WHERE album_id = ? AND status != 'deleted' ORDER BY id ASC",
            [(int) $row['id']]
        ) ?: [];
        $upload  = md_upload_dir();
        $raw_art = !empty($row['artwork_path']) ? $row['artwork_path'] : ($row['artwork_thumbnail_path'] ?? '');
        $artwork_url = '';
        if (!empty($raw_art)) {
            if (str_starts_with($raw_art, '/') && !str_starts_with($raw_art, $upload['basedir'])) {
                $artwork_url = md_home_url($raw_art);
            } else {
                $rel = ltrim(str_replace($upload['basedir'], '', $raw_art), '/');
                $artwork_url = $upload['baseurl'] . '/' . $rel;
            }
        }
        return array_merge($row, [
            'tracks'                => $tracks,
            'artwork_url'           => $artwork_url,
            'artwork_thumbnail_url' => $artwork_url,
            'track_count'           => count($tracks),
        ]);
    }
}

// User data (reads from simuze_users)
if (!function_exists('get_userdata')) {
    function get_userdata($user_id) {
        if (empty($user_id) || !isset($GLOBALS['db'])) return false;
        try {
            $stmt = $GLOBALS['db']->prepare("SELECT id, username, email, display_name FROM simuze_users WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return false;
            $user = new stdClass();
            $user->ID           = (int) $row['id'];
            $user->user_login   = $row['username'] ?? '';
            $user->user_email   = $row['email'] ?? '';
            $user->display_name = $row['display_name'] ?? $row['username'] ?? 'Unknown';
            return $user;
        } catch (\PDOException $e) {
            return false;
        }
    }
}

// HTTP status helper
if (!function_exists('status_header')) {
    function status_header($code, $description = '') {
        http_response_code((int) $code);
    }
}

// BuddyPress group stubs — delegate to Simuze_Group when available
if (!function_exists('groups_get_group')) {
    function groups_get_group($args) {
        $group_id = is_array($args) ? ($args['group_id'] ?? 0) : (int)$args;
        if (!$group_id) return false;
        return class_exists('Simuze_Group') ? Simuze_Group::get((int)$group_id) : false;
    }
}
if (!function_exists('bp_get_group_url')) {
    function bp_get_group_url($group) {
        if ($group && method_exists($group, 'get_url')) return trailingslashit($group->get_url());
        if ($group && !empty($group->slug)) return home_url('/group/' . $group->slug . '/');
        return home_url('/groups/');
    }
}

// Install global $wpdb compat when bridge is loaded
if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
    $GLOBALS['wpdb'] = new Simuze_Wpdb_Compat($GLOBALS['db'], '');
}
