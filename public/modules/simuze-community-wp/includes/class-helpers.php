<?php
/**
 * Helper functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Helpers {
    
    /**
     * Get current user
     */
    public static function get_current_user() {
        $auth = Simuze_Auth::get_instance();
        return $auth->get_current_user();
    }
    
    /**
     * Check if user is logged in
     */
    public static function is_logged_in() {
        $auth = Simuze_Auth::get_instance();
        return $auth->is_logged_in();
    }
    
    /**
     * Get user by ID (with fallback)
     */
    public static function get_user($user_id) {
        return Simuze_User::get($user_id);
    }
    
    /**
     * Get group by ID (with fallback)
     */
    public static function get_group($group_id) {
        return Simuze_Group::get($group_id);
    }
    
    /**
     * Sanitize output
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map(array(__CLASS__, 'sanitize'), $data);
        }
        return sanitize_text_field($data);
    }
}











