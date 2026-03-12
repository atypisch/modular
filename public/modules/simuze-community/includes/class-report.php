<?php
/**
 * Report system
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Report {

    /**
     * Create report
     */
    public static function create($reporter_id, $content_type, $content_id, $report_type, $comment = null) {
        $table = 'simuze_reports';

        $data = array(
            'reporter_id' => $reporter_id,
            'content_type' => $content_type,
            'content_id' => $content_id,
            'report_type' => $report_type,
            'comment' => $comment,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        );

        return md_db_insert($table, $data);
    }

    /**
     * Update report status
     */
    public static function update_status($report_id, $status, $reviewed_by = null, $notes = null) {
        $table = 'simuze_reports';

        $data = array(
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        if ($reviewed_by) {
            $data['reviewed_by'] = $reviewed_by;
            $data['reviewed_at'] = date('Y-m-d H:i:s');
        }

        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        return md_db_update(
            $table,
            $data,
            array('id' => $report_id)
        );
    }

    /**
     * Get reports queue
     */
    public static function get_queue($args = array()) {
        $table = 'simuze_reports';
        $users_table = 'simuze_users';

        $defaults = array(
            'status' => 'pending',
            'content_type' => null,
            'per_page' => 20,
            'page' => 1,
        );

        $args = array_merge($defaults, $args);

        $where = "r.status = ?";
        $where_values = array($args['status']);

        if ($args['content_type']) {
            $where .= " AND r.content_type = ?";
            $where_values[] = $args['content_type'];
        }

        $offset = ($args['page'] - 1) * $args['per_page'];

        $reports = md_db_query(
            "SELECT r.*, u.username as reporter_username, u.display_name as reporter_name
            FROM $table r
            LEFT JOIN $users_table u ON r.reporter_id = u.id
            WHERE $where ORDER BY r.created_at ASC LIMIT ? OFFSET ?",
            array_merge($where_values, array($args['per_page'], $offset))
        );

        $total = (int) md_db_var(
            "SELECT COUNT(*) FROM $table r WHERE $where",
            $where_values
        );

        return array(
            'reports' => $reports,
            'total' => $total,
        );
    }

    /**
     * Get report by ID
     */
    public static function get($report_id) {
        $table = 'simuze_reports';

        return md_db_row(
            "SELECT * FROM $table WHERE id = ?",
            array($report_id)
        );
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
