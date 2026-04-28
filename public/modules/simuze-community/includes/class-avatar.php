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

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Avatar {
    private static $instance = null;
    private $cache_ttl;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->cache_ttl = 24 * 60 * 60; // 24 hours
    }

    public function get_user_avatar_url($user_id, $size = 'medium', $options = []) {
        $avatar_data = $this->get_user_avatar($user_id, $options);
        return $this->pick_url($avatar_data, $size, $this->get_default_avatar_url());
    }

    public function get_group_avatar_url($group_id, $size = 'medium') {
        $avatar_data = $this->get_group_avatar($group_id);
        $default_url = $this->get_default_group_avatar_url();

        if ($avatar_data) {
            $url = $this->pick_url($avatar_data, $size, $default_url);
            if (!empty($url)) return $url;
        }

        return $default_url;
    }

    public function get_user_avatar($user_id, $options = []) {
        if (empty($user_id)) return null;

        $options = array_merge(['cache_only' => false, 'force_refresh' => false], $options);

        $cached     = $this->get_cached_user_avatar($user_id);
        $is_expired = $this->is_user_cache_expired($cached);

        if ($cached && ($options['cache_only'] || !$is_expired) && !$options['force_refresh']) {
            return $this->format_cached_avatar($cached);
        }

        if ($options['cache_only']) {
            return $cached ? $this->format_cached_avatar($cached) : null;
        }

        $api_data = $this->fetch_user_avatar_from_api($user_id);
        if ($api_data) {
            $this->update_user_cache($user_id, $api_data);
            return $api_data;
        }

        return $cached ? $this->format_cached_avatar($cached) : null;
    }

    private function get_cached_user_avatar($user_id) {
        return md_db_row("SELECT * FROM simuze_avatar_cache_user WHERE user_id = ? LIMIT 1", [$user_id]);
    }

    private function is_user_cache_expired($cached) {
        if (empty($cached)) return true;
        if (!empty($cached['expires_at'])) return strtotime($cached['expires_at']) < time();
        if (!empty($cached['cached_at']))  return strtotime($cached['cached_at']) + $this->cache_ttl < time();
        return true;
    }

    private function fetch_user_avatar_from_api($user_id) {
        $endpoint = rtrim(md_home_url('/api/auth'), '/') . '/api/avatar/user/' . rawurlencode($user_id);

        $response = md_http_get($endpoint, ['timeout' => 8]);

        if ($response === false) {
            error_log('Simezu avatar fetch failed for user ' . $user_id);
            return null;
        }

        if ($response['code'] !== 200) {
            error_log('Simezu avatar fetch HTTP ' . $response['code'] . ' for user ' . $user_id);
            return null;
        }

        $body = json_decode($response['body'], true);
        if (empty($body['url'])) return null;

        $base_url = $body['url'];

        return [
            'thumb_url'  => $this->append_size($base_url, 64),
            'medium_url' => $this->append_size($base_url, 200),
            'full_url'   => $this->append_size($base_url, 500),
            'metadata'   => $body,
            'updated_at' => $body['updated'] ?? date('Y-m-d H:i:s'),
        ];
    }

    private function append_size($url, $size) {
        if (empty($url)) return '';
        $sep = (strpos($url, '?') !== false) ? '&' : '?';
        return $url . $sep . 'size=' . intval($size);
    }

    private function update_user_cache($user_id, $api_data) {
        $expires_at = date('Y-m-d H:i:s', time() + $this->cache_ttl);
        $now        = date('Y-m-d H:i:s');

        $data = [
            'user_id'    => $user_id,
            'thumb_url'  => $api_data['thumb_url']  ?? '',
            'medium_url' => $api_data['medium_url'] ?? '',
            'full_url'   => $api_data['full_url']   ?? '',
            'metadata'   => json_encode($api_data['metadata'] ?? []),
            'cached_at'  => $now,
            'updated_at' => $api_data['updated_at'] ?? $now,
            'expires_at' => $expires_at,
        ];

        $exists = md_db_var("SELECT id FROM simuze_avatar_cache_user WHERE user_id = ?", [$user_id]);

        if ($exists) {
            md_db_update('simuze_avatar_cache_user', $data, ['user_id' => $user_id]);
        } else {
            md_db_insert('simuze_avatar_cache_user', $data);
        }
    }

    public function get_group_avatar($group_id) {
        if (empty($group_id)) return null;

        $cached = md_db_row("SELECT * FROM simuze_avatar_cache_group WHERE group_id = ? LIMIT 1", [$group_id]);
        return $cached ? $this->format_cached_avatar($cached) : null;
    }

    public function update_group_cache($group_id, $urls, $metadata = []) {
        $now  = date('Y-m-d H:i:s');
        $data = [
            'group_id'   => $group_id,
            'thumb_url'  => $urls['thumb']  ?? '',
            'medium_url' => $urls['medium'] ?? '',
            'full_url'   => $urls['full']   ?? '',
            'metadata'   => json_encode($metadata),
            'cached_at'  => $now,
            'updated_at' => $now,
        ];

        $exists = md_db_var("SELECT id FROM simuze_avatar_cache_group WHERE group_id = ?", [$group_id]);

        if ($exists) {
            md_db_update('simuze_avatar_cache_group', $data, ['group_id' => $group_id]);
        } else {
            md_db_insert('simuze_avatar_cache_group', $data);
        }
    }

    public function clear_user_cache($user_id) {
        md_db_delete('simuze_avatar_cache_user', ['user_id' => $user_id]);
    }

    public function refresh_user_cache($user_id) {
        $this->clear_user_cache($user_id);
        $data = $this->fetch_user_avatar_from_api($user_id);
        if ($data) {
            $this->update_user_cache($user_id, $data);
        }
    }

    public function clear_group_cache($group_id) {
        md_db_delete('simuze_avatar_cache_group', ['group_id' => $group_id]);
    }

    private function format_cached_avatar($cached) {
        return [
            'thumb_url'  => $cached['thumb_url']  ?? '',
            'medium_url' => $cached['medium_url'] ?? '',
            'full_url'   => $cached['full_url']   ?? '',
            'metadata'   => !empty($cached['metadata']) ? json_decode($cached['metadata'], true) : [],
        ];
    }

    private function pick_url($avatar_data, $size, $fallback) {
        if (!$avatar_data) return $fallback;

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

    public function get_default_avatar_url() {
        $path = md_theme_path('images/profile_default.jpg');
        if (!file_exists($path)) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96"><circle cx="48" cy="48" r="48" fill="#2a2a2a"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#fff" font-size="32">?</text></svg>';
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        }
        return md_asset_url('images/profile_default.jpg');
    }

    public function get_default_group_avatar_url() {
        if (file_exists(md_theme_path('images/default-group.png'))) {
            return md_asset_url('images/default-group.png');
        }
        if (file_exists(md_theme_path('images/default-group.svg'))) {
            return md_asset_url('images/default-group.svg');
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><rect width="50" height="50" fill="#2a2a2a" rx="8"/><g transform="translate(25, 25)"><circle cx="-8" cy="-4" r="6" fill="#a8d5ba"/><circle cx="8" cy="-4" r="6" fill="#a8d5ba"/><circle cx="0" cy="4" r="6" fill="#a8d5ba"/></g></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
