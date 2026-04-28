<?php
/**
 * Avatar System
 * Handles avatar caching and retrieval from SIMEZU Avatar API
 * 
 * Features:
 * - 24-hour caching of avatar metadata
 * - Direct CDN URLs (no proxying)
 * - Support for thumb_url, medium_url, full_url variants
 * - Fallback to local default avatar
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Avatar {
    private static $instance = null;
    private $wpdb;
    private $user_cache_table;
    private $group_cache_table;
    private $cache_ttl;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->user_cache_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'avatar_cache_user';
        $this->group_cache_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'avatar_cache_group';
        $this->cache_ttl = 24 * 60 * 60; // 24 hours in seconds
    }

    /**
     * Public helper: get user avatar URL (Simezu source)
     */
    public function get_user_avatar_url($user_id, $size = 'medium', $options = array()) {
        $avatar_data = $this->get_user_avatar($user_id, $options);
        return $this->pick_url($avatar_data, $size, $this->get_default_avatar_url());
    }

    /**
     * Public helper: get group avatar URL (local source)
     */
    public function get_group_avatar_url($group_id, $size = 'medium') {
        $avatar_data = $this->get_group_avatar($group_id);
        $default_url = $this->get_default_group_avatar_url();
        
        // If we have avatar data with URLs, use it
        if ($avatar_data) {
            $url = $this->pick_url($avatar_data, $size, $default_url);
            // Return the URL if it's not empty (pick_url already handles fallback)
            if (!empty($url)) {
                return $url;
            }
        }
        
        // Fallback to default
        return $default_url;
    }

    /**
     * Get Simezu user avatar with cache-first strategy
     * Options:
     * - cache_only: bool (default false) -> never call remote API, even if stale/missing
     * - force_refresh: bool (default false) -> bypass cache and refetch
     */
    public function get_user_avatar($user_id, $options = array()) {
        if (empty($user_id)) {
            return null;
        }

        $defaults = array(
            'cache_only' => false,
            'force_refresh' => false,
        );
        $options = wp_parse_args($options, $defaults);

        $cached = $this->get_cached_user_avatar($user_id);
        $is_expired = $this->is_user_cache_expired($cached);

        if ($cached && ($options['cache_only'] || !$is_expired) && !$options['force_refresh']) {
            return $this->format_cached_avatar($cached);
        }

        if ($options['cache_only']) {
            return $cached ? $this->format_cached_avatar($cached) : null;
        }

        // Try refreshing from Simezu API
        $api_data = $this->fetch_user_avatar_from_api($user_id);
        if ($api_data) {
            $this->update_user_cache($user_id, $api_data);
            return $api_data;
        }

        // Fall back to cached even if expired when API fails
        return $cached ? $this->format_cached_avatar($cached) : null;
    }

    /**
     * Cache lookup for Simezu user avatar
     */
    private function get_cached_user_avatar($user_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->user_cache_table} WHERE user_id = %s LIMIT 1",
                $user_id
            ),
            ARRAY_A
        );
    }

    private function is_user_cache_expired($cached) {
        if (empty($cached)) {
            return true;
        }
        if (!empty($cached['expires_at'])) {
            return strtotime($cached['expires_at']) < time();
        }
        if (!empty($cached['cached_at'])) {
            return strtotime($cached['cached_at']) + $this->cache_ttl < time();
        }
        return true;
    }

    /**
     * Fetch avatar metadata directly from Simezu API (no proxy)
     * Endpoint: GET {base}/api/avatar/user/{userId}
     * Response: { status, url, updated }
     */
    private function fetch_user_avatar_from_api($user_id) {
        $base = get_option('simuze_simezu_base_url', '');
        if (empty($base)) {
            $auth_api = get_option('simuze_auth_api_url', 'http://localhost/simezu/api.php/auth');
            $base = str_replace('/api.php/auth', '', $auth_api);
        }
        $base = trailingslashit(rtrim($base, '/'));
        // Ensure we don't accidentally double the /api path
        $endpoint = rtrim($base, '/') . '/api/avatar/user/' . rawurlencode($user_id);

        $response = wp_remote_get($endpoint, array(
            'timeout' => 8,
            'sslverify' => false,
        ));

        if (is_wp_error($response)) {
            error_log('Simezu avatar fetch failed: ' . $response->get_error_message());
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            error_log('Simezu avatar fetch HTTP ' . $code . ' for user ' . $user_id);
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['url'])) {
            return null;
        }

        $base_url = $body['url'];
        $thumb = $this->append_size($base_url, 64);
        $medium = $this->append_size($base_url, 200);
        $full = $this->append_size($base_url, 500);

        return array(
            'thumb_url' => $thumb,
            'medium_url' => $medium,
            'full_url' => $full,
            'metadata' => $body,
            'updated_at' => $body['updated'] ?? current_time('mysql'),
        );
    }

    private function append_size($url, $size) {
        if (empty($url)) {
            return '';
        }
        $separator = (strpos($url, '?') !== false) ? '&' : '?';
        return $url . $separator . 'size=' . intval($size);
    }

    private function update_user_cache($user_id, $api_data) {
        $expires_at = date('Y-m-d H:i:s', time() + $this->cache_ttl);
        $data = array(
            'user_id' => $user_id,
            'thumb_url' => $api_data['thumb_url'] ?? '',
            'medium_url' => $api_data['medium_url'] ?? '',
            'full_url' => $api_data['full_url'] ?? '',
            'metadata' => json_encode($api_data['metadata'] ?? array()),
            'cached_at' => current_time('mysql'),
            'updated_at' => $api_data['updated_at'] ?? current_time('mysql'),
            'expires_at' => $expires_at,
        );

        $exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->user_cache_table} WHERE user_id = %s",
            $user_id
        ));

        if ($exists) {
            $this->wpdb->update(
                $this->user_cache_table,
                $data,
                array('user_id' => $user_id),
                array('%s','%s','%s','%s','%s','%s','%s'),
                array('%s')
            );
        } else {
            $this->wpdb->insert(
                $this->user_cache_table,
                $data,
                array('%s','%s','%s','%s','%s','%s','%s')
            );
        }
    }

    /**
     * Group avatar: cache-only (local storage)
     */
    public function get_group_avatar($group_id) {
        if (empty($group_id)) {
            return null;
        }

        $cached = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->group_cache_table} WHERE group_id = %d LIMIT 1",
                $group_id
            ),
            ARRAY_A
        );

        return $cached ? $this->format_cached_avatar($cached) : null;
    }

    public function update_group_cache($group_id, $urls, $metadata = array()) {
        $data = array(
            'group_id' => $group_id,
            'thumb_url' => $urls['thumb'] ?? '',
            'medium_url' => $urls['medium'] ?? '',
            'full_url' => $urls['full'] ?? '',
            'metadata' => json_encode($metadata),
            'cached_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        $exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->group_cache_table} WHERE group_id = %d",
            $group_id
        ));

        if ($exists) {
            $this->wpdb->update(
                $this->group_cache_table,
                $data,
                array('group_id' => $group_id),
                array('%d','%s','%s','%s','%s','%s','%s'),
                array('%d')
            );
        } else {
            $this->wpdb->insert(
                $this->group_cache_table,
                $data,
                array('%d','%s','%s','%s','%s','%s','%s')
            );
        }
    }

    public function clear_user_cache($user_id) {
        $this->wpdb->delete($this->user_cache_table, array('user_id' => $user_id), array('%s'));
    }

    /**
     * Force refresh for a Simezu user after an upload/change.
     */
    public function refresh_user_cache($user_id) {
        $this->clear_user_cache($user_id);
        $data = $this->fetch_user_avatar_from_api($user_id);
        if ($data) {
            $this->update_user_cache($user_id, $data);
        }
    }

    public function clear_group_cache($group_id) {
        $this->wpdb->delete($this->group_cache_table, array('group_id' => $group_id), array('%d'));
    }

    private function format_cached_avatar($cached) {
        $metadata = !empty($cached['metadata']) ? json_decode($cached['metadata'], true) : array();
        return array(
            'thumb_url' => $cached['thumb_url'] ?? '',
            'medium_url' => $cached['medium_url'] ?? '',
            'full_url' => $cached['full_url'] ?? '',
            'metadata' => $metadata,
        );
    }

    private function pick_url($avatar_data, $size, $fallback) {
        if (!$avatar_data) {
            return $fallback;
        }

        if ($size === 'thumb' || (is_numeric($size) && intval($size) <= 64)) {
            $url = $avatar_data['thumb_url'] ?? '';
        } elseif ($size === 'full' || (is_numeric($size) && intval($size) >= 500)) {
            $url = $avatar_data['full_url'] ?? '';
        } else {
            $url = $avatar_data['medium_url'] ?? '';
        }

        if (empty($url)) {
            $url = $avatar_data['full_url'] ?? $avatar_data['medium_url'] ?? $avatar_data['thumb_url'] ?? '';
        }

        return !empty($url) ? $url : $fallback;
    }

    /**
     * Local defaults
     */
    public function get_default_avatar_url() {
        $default_avatar = get_template_directory_uri() . '/images/profile_default.jpg';
        $default_path = get_template_directory() . '/images/profile_default.jpg';
        if (!file_exists($default_path)) {
            return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96"><circle cx="48" cy="48" r="48" fill="#2a2a2a"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#fff" font-size="32">?</text></svg>');
        }
        return $default_avatar;
    }

    public function get_default_group_avatar_url() {
        // Try PNG first
        $default_avatar = get_template_directory_uri() . '/images/default-group.png';
        $default_path = get_template_directory() . '/images/default-group.png';
        if (file_exists($default_path)) {
            return $default_avatar;
        }
        
        // Try SVG as fallback
        $default_svg = get_template_directory_uri() . '/images/default-group.svg';
        $default_svg_path = get_template_directory() . '/images/default-group.svg';
        if (file_exists($default_svg_path)) {
            return $default_svg;
        }
        
        // Generate inline SVG as final fallback
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><rect width="50" height="50" fill="#2a2a2a" rx="8"/><g transform="translate(25, 25)"><circle cx="-8" cy="-4" r="6" fill="#a8d5ba"/><circle cx="8" cy="-4" r="6" fill="#a8d5ba"/><circle cx="0" cy="4" r="6" fill="#a8d5ba"/></g></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

