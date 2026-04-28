<?php
/**
 * Friends system
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Friend {
    
    /**
     * Send friend request
     */
    public static function send_request($user_id, $friend_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'friends';
        
        // Validate users exist
        if (class_exists('Simuze_Security')) {
            if (!Simuze_Security::validate_user_exists($user_id) || !Simuze_Security::validate_user_exists($friend_id)) {
                return false;
            }
        }
        
        // Can't send request to self
        if ($user_id == $friend_id) {
            return false;
        }
        
        // Check if request already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE (user_id = %d AND friend_id = %d) OR (user_id = %d AND friend_id = %d)",
            $user_id,
            $friend_id,
            $friend_id,
            $user_id
        ));
        
        if ($exists) {
            return false;
        }
        
        $data = array(
            'user_id' => $user_id,
            'friend_id' => $friend_id,
            'status' => 'pending',
            'requested_by' => $user_id,
            'created_at' => current_time('mysql'),
        );
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            // Record activity
            Simuze_Activity::record_user_activity(
                $user_id,
                'friend_request_sent',
                null,
                'friends',
                $friend_id
            );
            
            // Create notification
            if (class_exists('Simuze_Notification')) {
                Simuze_Notification::friend_request($friend_id, $user_id);
            }
        }
        
        return $result;
    }
    
    /**
     * Accept friend request
     */
    public static function accept_request($user_id, $friend_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'friends';
        
        $result = $wpdb->update(
            $table,
            array('status' => 'accepted', 'updated_at' => current_time('mysql')),
            array('user_id' => $friend_id, 'friend_id' => $user_id, 'status' => 'pending'),
            array('%s', '%s'),
            array('%d', '%d', '%s')
        );
        
        if ($result) {
            // Record activity
            Simuze_Activity::record_user_activity(
                $user_id,
                'friend_request_accepted',
                null,
                'friends',
                $friend_id
            );
            
            // Create notification for the person who sent the request
            if (class_exists('Simuze_Notification')) {
                Simuze_Notification::friend_request_accepted($friend_id, $user_id);
            }
        }
        
        return $result;
    }
    
    /**
     * Get user friends
     */
    public static function get_friends($user_id, $status = 'accepted') {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'friends';
        $users_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        $friends = $wpdb->get_results($wpdb->prepare(
            "SELECT u.*, f.status, f.created_at as friendship_date
            FROM $table f
            INNER JOIN $users_table u ON (f.friend_id = u.id AND f.user_id = %d) OR (f.user_id = u.id AND f.friend_id = %d)
            WHERE f.status = %s AND u.deleted_at IS NULL
            ORDER BY f.updated_at DESC",
            $user_id,
            $user_id,
            $status
        ), ARRAY_A);
        
        return $friends;
    }
    
    /**
     * Check if users are friends
     */
    public static function are_friends($user_id, $friend_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'friends';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table 
            WHERE ((user_id = %d AND friend_id = %d) OR (user_id = %d AND friend_id = %d))
            AND status = 'accepted'",
            $user_id,
            $friend_id,
            $friend_id,
            $user_id
        ));
        
        return (bool) $exists;
    }
    
    /**
     * Unfriend user
     */
    public static function unfriend($user_id, $friend_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'friends';
        
        $result = $wpdb->delete(
            $table,
            array(
                'user_id' => $user_id,
                'friend_id' => $friend_id,
            ),
            array('%d', '%d')
        );
        
        // Also delete reverse relationship
        $wpdb->delete(
            $table,
            array(
                'user_id' => $friend_id,
                'friend_id' => $user_id,
            ),
            array('%d', '%d')
        );
        
        if ($result) {
            // Record activity
            Simuze_Activity::record_user_activity(
                $user_id,
                'friend_removed',
                null,
                'friends',
                $friend_id
            );
        }
        
        return $result;
    }
    
    /**
     * Reject friend request
     */
    public static function reject_request($user_id, $friend_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'friends';
        
        return $wpdb->delete(
            $table,
            array('user_id' => $friend_id, 'friend_id' => $user_id, 'status' => 'pending'),
            array('%d', '%d', '%s')
        );
    }
    
    /**
     * Get pending friend requests
     */
    public static function get_pending_requests($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'friends';
        $users_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        $requests = $wpdb->get_results($wpdb->prepare(
            "SELECT u.*, f.id as request_id, f.created_at as requested_at
            FROM $table f
            INNER JOIN $users_table u ON f.user_id = u.id
            WHERE f.friend_id = %d AND f.status = 'pending' AND u.deleted_at IS NULL
            ORDER BY f.created_at DESC",
            $user_id
        ), ARRAY_A);
        
        return $requests;
    }
    
    /**
     * Count friends
     */
    public static function count_friends($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'friends';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
            WHERE (user_id = %d OR friend_id = %d) AND status = 'accepted'",
            $user_id,
            $user_id
        ));
    }
    
    /**
     * Count pending requests received
     */
    public static function count_pending_requests($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'friends';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE friend_id = %d AND status = 'pending'",
            $user_id
        ));
    }
    
    /**
     * Get sent friend requests
     */
    public static function get_sent_requests($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'friends';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND status = 'pending' ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);
    }
    
    /**
     * Cancel sent friend request
     */
    public static function cancel_request($user_id, $friend_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'friends';
        
        return $wpdb->delete(
            $table,
            array('user_id' => $user_id, 'friend_id' => $friend_id, 'status' => 'pending'),
            array('%d', '%d', '%s')
        );
    }
}

