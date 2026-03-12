<?php
/**
 * Review system for admins and editors
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Review {
    
    /**
     * Create review
     */
    public static function create($reviewer_id, $content_type, $content_id, $status = 'pending', $notes = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'reviews';
        
        $data = array(
            'reviewer_id' => $reviewer_id,
            'content_type' => $content_type,
            'content_id' => $content_id,
            'status' => $status,
            'notes' => $notes,
            'created_at' => current_time('mysql'),
        );
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Update review status
     */
    public static function update_status($review_id, $status, $notes = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'reviews';
        
        $data = array(
            'status' => $status,
            'updated_at' => current_time('mysql'),
        );
        
        if ($notes !== null) {
            $data['notes'] = $notes;
        }
        
        return $wpdb->update(
            $table,
            $data,
            array('id' => $review_id),
            null,
            array('%d')
        );
    }
    
    /**
     * Get reviews queue
     */
    public static function get_queue($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'reviews';
        $users_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        $defaults = array(
            'status' => 'pending',
            'content_type' => null,
            'per_page' => 20,
            'page' => 1,
        );
        
        $args = array_merge($defaults, $args);
        
        $where = "r.status = %s";
        $where_values = array($args['status']);
        
        if ($args['content_type']) {
            $where .= " AND r.content_type = %s";
            $where_values[] = $args['content_type'];
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $query = $wpdb->prepare(
            "SELECT r.*, u.username as reviewer_username, u.display_name as reviewer_name
            FROM $table r
            LEFT JOIN $users_table u ON r.reviewer_id = u.id
            WHERE $where ORDER BY r.created_at ASC LIMIT %d OFFSET %d",
            array_merge($where_values, array($args['per_page'], $offset))
        );
        
        $reviews = $wpdb->get_results($query, ARRAY_A);
        
        $count_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table r WHERE $where",
            $where_values
        );
        $total = $wpdb->get_var($count_query);
        
        return array(
            'reviews' => $reviews,
            'total' => (int) $total,
        );
    }
    
    /**
     * Get review by ID
     */
    public static function get($review_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'reviews';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $review_id
        ), ARRAY_A);
    }
    
    /**
     * Get review by content
     */
    public static function get_by_content($content_type, $content_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'reviews';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE content_type = %s AND content_id = %d ORDER BY created_at DESC LIMIT 1",
            $content_type,
            $content_id
        ), ARRAY_A);
    }
}

