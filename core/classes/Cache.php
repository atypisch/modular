<?php
/**
 * Caching System
 * Simple file-based cache (can be extended to Redis)
 */

namespace Modular;

class Cache {
    private $cacheDir;
    private $defaultTtl = 3600; // 1 hour
    
    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?? dirname(dirname(__DIR__)) . '/cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached value
     */
    public function get($key) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        // Check expiration
        if (isset($data['expires']) && $data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'] ?? null;
    }
    
    /**
     * Set cached value
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTtl;
        $file = $this->getCacheFile($key);
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        file_put_contents($file, serialize($data));
    }
    
    /**
     * Delete cached value
     */
    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
    
    /**
     * Remember pattern - get or compute
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = call_user_func($callback);
        $this->set($key, $value, $ttl);
        
        return $value;
    }
}


