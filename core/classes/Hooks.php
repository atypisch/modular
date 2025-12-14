<?php
/**
 * Hook System
 * Implements WordPress-style actions and filters
 */

namespace Modular;

class Hooks {
    private static $actions = [];
    private static $filters = [];
    
    /**
     * Add an action hook
     */
    public static function addAction($hook, $callback, $priority = 10) {
        if (!isset(self::$actions[$hook])) {
            self::$actions[$hook] = [];
        }
        self::$actions[$hook][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        // Sort by priority
        usort(self::$actions[$hook], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }
    
    /**
     * Execute an action
     */
    public static function doAction($hook, ...$args) {
        if (!isset(self::$actions[$hook])) {
            return;
        }
        
        foreach (self::$actions[$hook] as $action) {
            call_user_func_array($action['callback'], $args);
        }
    }
    
    /**
     * Add a filter
     */
    public static function addFilter($hook, $callback, $priority = 10) {
        if (!isset(self::$filters[$hook])) {
            self::$filters[$hook] = [];
        }
        self::$filters[$hook][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        // Sort by priority
        usort(self::$filters[$hook], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }
    
    /**
     * Apply filters
     */
    public static function applyFilters($hook, $value, ...$args) {
        if (!isset(self::$filters[$hook])) {
            return $value;
        }
        
        foreach (self::$filters[$hook] as $filter) {
            $value = call_user_func_array($filter['callback'], array_merge([$value], $args));
        }
        
        return $value;
    }
    
    /**
     * Remove an action or filter
     */
    public static function remove($type, $hook, $callback) {
        $hooks = $type === 'action' ? self::$actions : self::$filters;
        
        if (!isset($hooks[$hook])) {
            return false;
        }
        
        foreach ($hooks[$hook] as $key => $item) {
            if ($item['callback'] === $callback) {
                unset($hooks[$hook][$key]);
                return true;
            }
        }
        
        return false;
    }
}

