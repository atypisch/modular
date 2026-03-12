<?php
/**
 * Report system
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Report {
    
    /**
     * Create report
     */
    public static function create($reporter_id, $content_type, $content_id, $report_type, $comment = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'reports';
        
        $data = array(
            'reporter_id' => $reporter_id,
            'content_type' => $content_type,
            'content_id' => $content_id,
            'report_type' => $report_type,
            'comment' => $comment,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        );
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Update report status
     */
    public static function update_status($report_id, $status, $reviewed_by = null, $notes = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'reports';
        
        $data = array(
            'status' => $status,
            'updated_at' => current_time('mysql'),
        );
        
        if ($reviewed_by) {
            $data['reviewed_by'] = $reviewed_by;
            $data['reviewed_at'] = current_time('mysql');
        }
        
        if ($notes !== null) {
            $data['notes'] = $notes;
        }
        
        return $wpdb->update(
            $table,
            $data,
            array('id' => $report_id),
            null,
            array('%d')
        );
    }
    
    /**
     * Get reports queue
     */
    public static function get_queue($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'reports';
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
            "SELECT r.*, u.username as reporter_username, u.display_name as reporter_name
            FROM $table r
            LEFT JOIN $users_table u ON r.reporter_id = u.id
            WHERE $where ORDER BY r.created_at ASC LIMIT %d OFFSET %d",
            array_merge($where_values, array($args['per_page'], $offset))
        );
        
        $reports = $wpdb->get_results($query, ARRAY_A);
        
        $count_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table r WHERE $where",
            $where_values
        );
        $total = $wpdb->get_var($count_query);
        
        return array(
            'reports' => $reports,
            'total' => (int) $total,
        );
    }
    
    /**
     * Get report by ID
     */
    public static function get($report_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'reports';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $report_id
        ), ARRAY_A);
    }
    
    /**
     * Mark as resolved
     */
    public static function mark_resolved($report_id, $reviewed_by, $notes = null) {
        return self::update_status($report_id, 'resolved', $reviewed_by, $notes);
    }
    
    /**
     * Mark as removed
     */
    public static function mark_removed($report_id, $reviewed_by, $notes = null) {
        return self::update_status($report_id, 'removed', $reviewed_by, $notes);
    }
}

