<?php
/**
 * Follow system
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Follow {
    
    /**
     * Follow user or group
     */
    public static function follow($follower_id, $followable_type, $followable_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'follows';
        
        // Validate user exists
        if (class_exists('Simuze_Security')) {
            if (!Simuze_Security::validate_user_exists($follower_id)) {
                return false;
            }
            
            if ($followable_type === 'user' && !Simuze_Security::validate_user_exists($followable_id)) {
                return false;
            }
            
            if ($followable_type === 'group' && !Simuze_Security::validate_group_exists($followable_id)) {
                return false;
            }
        }
        
        // Can't follow self
        if ($followable_type === 'user' && $follower_id == $followable_id) {
            return false;
        }
        
        // Check if already following
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE follower_id = %d AND followable_type = %s AND followable_id = %d",
            $follower_id,
            $followable_type,
            $followable_id
        ));
        
        if ($exists) {
            return false;
        }
        
        $data = array(
            'follower_id' => $follower_id,
            'followable_type' => $followable_type,
            'followable_id' => $followable_id,
            'created_at' => current_time('mysql'),
        );
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            // Record activity
            $action = $followable_type === 'user' ? 'user_followed' : 'group_followed';
            Simuze_Activity::record_user_activity(
                $follower_id,
                $action,
                null,
                'follows',
                $followable_id
            );
        }
        
        return $result;
    }
    
    /**
     * Unfollow user or group
     */
    public static function unfollow($follower_id, $followable_type, $followable_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'follows';
        
        return $wpdb->delete(
            $table,
            array(
                'follower_id' => $follower_id,
                'followable_type' => $followable_type,
                'followable_id' => $followable_id,
            ),
            array('%d', '%s', '%d')
        );
    }
    
    /**
     * Check if user is following
     */
    public static function is_following($follower_id, $followable_type, $followable_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'follows';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE follower_id = %d AND followable_type = %s AND followable_id = %d",
            $follower_id,
            $followable_type,
            $followable_id
        ));
        
        return (bool) $exists;
    }
    
    /**
     * Get followers
     */
    public static function get_followers($followable_type, $followable_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'follows';
        $users_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        $followers = $wpdb->get_results($wpdb->prepare(
            "SELECT u.*, f.created_at as followed_at
            FROM $table f
            INNER JOIN $users_table u ON f.follower_id = u.id
            WHERE f.followable_type = %s AND f.followable_id = %d AND u.deleted_at IS NULL
            ORDER BY f.created_at DESC",
            $followable_type,
            $followable_id
        ), ARRAY_A);
        
        return $followers;
    }
    
    /**
     * Get following
     */
    public static function get_following($follower_id, $followable_type = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'follows';
        
        $where = "follower_id = %d";
        $where_values = array($follower_id);
        
        if ($followable_type) {
            $where .= " AND followable_type = %s";
            $where_values[] = $followable_type;
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY created_at DESC",
            $where_values
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Count followers for user or group
     */
    public static function count_followers($followable_id, $followable_type = 'user') {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'follows';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE followable_type = %s AND followable_id = %d",
            $followable_type,
            $followable_id
        ));
    }
    
    /**
     * Count following for user
     */
    public static function count_following($user_id, $followable_type = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'follows';
        
        if ($followable_type) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE follower_id = %d AND followable_type = %s",
                $user_id,
                $followable_type
            ));
        }
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE follower_id = %d",
            $user_id
        ));
    }
}

