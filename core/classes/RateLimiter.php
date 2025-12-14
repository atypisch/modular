<?php
/**
 * Rate Limiting Middleware
 * Simple in-memory rate limiting (use Redis in production)
 */

namespace Modular;

class RateLimiter {
    private static $requests = [];
    private static $maxRequests = 100;
    private static $window = 3600; // 1 hour
    
    public static function check($identifier, $maxRequests = null, $window = null) {
        $maxRequests = $maxRequests ?? self::$maxRequests;
        $window = $window ?? self::$window;
        
        $now = time();
        $key = $identifier . '_' . floor($now / $window);
        
        if (!isset(self::$requests[$key])) {
            self::$requests[$key] = 0;
        }
        
        self::$requests[$key]++;
        
        // Clean old entries
        self::cleanup($now, $window);
        
        return self::$requests[$key] <= $maxRequests;
    }
    
    public static function getRemaining($identifier, $maxRequests = null) {
        $maxRequests = $maxRequests ?? self::$maxRequests;
        $window = self::$window;
        $now = time();
        $key = $identifier . '_' . floor($now / $window);
        
        $current = self::$requests[$key] ?? 0;
        return max(0, $maxRequests - $current);
    }
    
    private static function cleanup($now, $window) {
        $currentWindow = floor($now / $window);
        foreach (self::$requests as $key => $count) {
            $windowNum = (int)explode('_', $key)[1];
            if ($windowNum < $currentWindow - 1) {
                unset(self::$requests[$key]);
            }
        }
    }
}

