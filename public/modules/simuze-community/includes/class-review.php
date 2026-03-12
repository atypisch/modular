<?php
/**
 * Review system for admins and editors
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Review {

    public static function create($reviewer_id, $content_type, $content_id, $status = 'pending', $notes = null) {
        return md_db_insert('simuze_reviews', [
            'reviewer_id'  => $reviewer_id,
            'content_type' => $content_type,
            'content_id'   => $content_id,
            'status'       => $status,
            'notes'        => $notes,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public static function update_status($review_id, $status, $notes = null) {
        $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($notes !== null) $data['notes'] = $notes;
        return md_db_update('simuze_reviews', $data, ['id' => $review_id]);
    }

    public static function get_queue($args = array()) {
        $defaults = ['status' => 'pending', 'content_type' => null, 'per_page' => 20, 'page' => 1];
        $args     = array_merge($defaults, $args);

        $where        = 'r.status = ?';
        $where_values = [$args['status']];

        if ($args['content_type']) {
            $where         .= ' AND r.content_type = ?';
            $where_values[] = $args['content_type'];
        }

        $offset = ($args['page'] - 1) * $args['per_page'];

        $total = (int) md_db_var(
            "SELECT COUNT(*) FROM simuze_reviews r WHERE {$where}",
            $where_values
        );

        $reviews = md_db_query(
            "SELECT r.*, u.username as reviewer_username, u.display_name as reviewer_name
             FROM simuze_reviews r
             LEFT JOIN simuze_users u ON r.reviewer_id = u.id
             WHERE {$where}
             ORDER BY r.created_at ASC
             LIMIT {$args['per_page']} OFFSET {$offset}",
            $where_values
        );

        return ['reviews' => $reviews, 'total' => $total];
    }

    public static function get($review_id) {
        return md_db_row("SELECT * FROM simuze_reviews WHERE id = ?", [$review_id]);
    }

    public static function get_by_content($content_type, $content_id) {
        return md_db_row(
            "SELECT * FROM simuze_reviews WHERE content_type = ? AND content_id = ? ORDER BY created_at DESC LIMIT 1",
            [$content_type, $content_id]
        );
    }
}
