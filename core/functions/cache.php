<?php
/**
 * Simple file-based cache. Use for settings, query results, or rendered output.
 * Cache dir: MODULAR_PATH . '/core/storage/cache' or sys_get_temp_dir() . '/modular_cache'
 */

if (!function_exists('cache_get')) {
    function cache_get(string $key) {
        $path = cache_path($key);
        if (!is_file($path)) {
            return null;
        }
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }
        $payload = @unserialize($data);
        if (!is_array($payload) || !isset($payload['expires']) || !isset($payload['value'])) {
            return null;
        }
        if ($payload['expires'] > 0 && $payload['expires'] < time()) {
            @unlink($path);
            return null;
        }
        return $payload['value'];
    }
}

if (!function_exists('cache_set')) {
    function cache_set(string $key, $value, int $ttlSeconds = 3600): bool {
        $dir = cache_dir();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return false;
        }
        $path = cache_path($key);
        $expires = $ttlSeconds > 0 ? time() + $ttlSeconds : 0;
        $payload = serialize(['expires' => $expires, 'value' => $value]);
        return @file_put_contents($path, $payload, LOCK_EX) !== false;
    }
}

if (!function_exists('cache_delete')) {
    function cache_delete(string $key): bool {
        $path = cache_path($key);
        return is_file($path) && @unlink($path);
    }
}

if (!function_exists('cache_delete_prefix')) {
    /**
     * Delete every cache entry whose key starts with $prefix. Used to invalidate a whole
     * group at once (e.g. all cached post-list queries) after a write. Returns the count removed.
     */
    function cache_delete_prefix(string $prefix): int {
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $prefix);
        $files = @glob(cache_dir() . '/' . $safe . '*') ?: [];
        $removed = 0;
        foreach ($files as $f) {
            if (is_file($f) && @unlink($f)) { $removed++; }
        }
        return $removed;
    }
}

if (!function_exists('cache_remember')) {
    /** Return the cached value for $key, or run $producer, cache its result and return it. */
    function cache_remember(string $key, int $ttlSeconds, callable $producer) {
        $hit = cache_get($key);
        if ($hit !== null) {
            return $hit;
        }
        $value = $producer();
        cache_set($key, $value, $ttlSeconds);
        return $value;
    }
}

if (!function_exists('cache_path')) {
    function cache_path(string $key): string {
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        return cache_dir() . '/' . $safe;
    }
}

if (!function_exists('cache_dir')) {
    function cache_dir(): string {
        if (defined('MODULAR_PATH') && is_dir(MODULAR_PATH . '/core/storage')) {
            return MODULAR_PATH . '/core/storage/cache';
        }
        $d = sys_get_temp_dir() . '/modular_cache';
        if (!is_dir($d)) {
            @mkdir($d, 0755, true);
        }
        return $d;
    }
}
