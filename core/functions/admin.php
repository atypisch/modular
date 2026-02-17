<?php
/**
 * Helper functions for the admin interface
 */

if (!function_exists('check_active_path')) {
    /**
     * Check if the current page matches a given path
     */
    function check_active_path($path) {
        $current_uri = $_SERVER['REQUEST_URI'];
        // Remove query string for comparison
        $current_path = strtok($current_uri, '?');
        $check_path = strtok($path, '?');
        return ($current_path == $check_path || strpos($current_path, $check_path) !== false);
    }
}

if (!function_exists('is_post_types_page')) {
    /**
     * Check if we're on a post types admin page
     * Exclude posts pages (which also contain 'post' in the path)
     */
    function is_post_types_page() {
        $uri = $_SERVER['REQUEST_URI'];
        // Check if we're on a post-types page, but NOT on a posts page
        $isPostTypes = strpos($uri, '/modular/admin/post-types') !== false;
        $isPosts = strpos($uri, '/modular/admin/posts') !== false;
        return $isPostTypes && !$isPosts;
    }
}

if (!function_exists('is_media_page')) {
    /**
     * Check if we're on a media admin page
     */
    function is_media_page() {
        return strpos($_SERVER['REQUEST_URI'], '/modular/admin/media') !== false;
    }
}

if (!function_exists('is_users_page')) {
    /**
     * Check if we're on a users admin page
     */
    function is_users_page() {
        return strpos($_SERVER['REQUEST_URI'], '/modular/admin/users') !== false;
    }
}

if (!function_exists('is_settings_page')) {
    /**
     * Check if we're on a settings admin page
     */
    function is_settings_page() {
        return strpos($_SERVER['REQUEST_URI'], '/modular/admin/settings') !== false;
    }
}

if (!function_exists('is_profile_page')) {
    /**
     * Check if we're on a profile admin page
     */
    function is_profile_page() {
        return strpos($_SERVER['REQUEST_URI'], '/modular/admin/profile') !== false;
    }
}

if (!function_exists('is_forms_page')) {
    /**
     * Check if we're on a forms admin page
     */
    function is_forms_page() {
        return strpos($_SERVER['REQUEST_URI'], '/modular/admin/forms') !== false;
    }
}

if (!function_exists('is_actions_page')) {
    /**
     * Check if we're on an actions admin page
     */
    function is_actions_page() {
        return strpos($_SERVER['REQUEST_URI'], '/modular/admin/actions') !== false;
    }
}

if (!function_exists('is_modules_page')) {
    /**
     * Check if we're on a modules admin page
     */
    function is_modules_page() {
        return strpos($_SERVER['REQUEST_URI'], '/modular/admin/modules') !== false;
    }
}

if (!function_exists('is_module_active')) {
    /**
     * Check if a module is active by slug.
     */
    function is_module_active($slug) {
        $m = $GLOBALS['module_manager'] ?? null;
        return $m && $m->isModuleActive($slug);
    }
}

if (!function_exists('is_posts_page')) {
    /**
     * Check if we're on a posts admin page
     * This should NOT match post-types pages
     */
    function is_posts_page() {
        $uri = $_SERVER['REQUEST_URI'];
        // Check if we're on a posts page, but NOT on a post-types page
        $isPosts = strpos($uri, '/modular/admin/posts') !== false;
        $isPostTypes = strpos($uri, '/modular/admin/post-types') !== false;
        return $isPosts && !$isPostTypes;
    }
}
