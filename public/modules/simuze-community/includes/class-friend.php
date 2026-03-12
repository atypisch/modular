<?php
/**
 * Friends system
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Friend {

    public static function send_request($user_id, $friend_id) {
        if (class_exists('Simuze_Security')) {
            if (!Simuze_Security::validate_user_exists($user_id) || !Simuze_Security::validate_user_exists($friend_id)) {
                return false;
            }
        }

        if ($user_id == $friend_id) return false;

        $exists = md_db_var(
            "SELECT id FROM simuze_friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)",
            [$user_id, $friend_id, $friend_id, $user_id]
        );
        if ($exists) return false;

        $result = md_db_insert('simuze_friends', [
            'user_id'      => $user_id,
            'friend_id'    => $friend_id,
            'status'       => 'pending',
            'requested_by' => $user_id,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        if ($result) {
            Simuze_Activity::record_user_activity($user_id, 'friend_request_sent', null, 'friends', $friend_id);
            if (class_exists('Simuze_Notification')) {
                Simuze_Notification::friend_request($friend_id, $user_id);
            }
        }

        return $result;
    }

    public static function accept_request($user_id, $friend_id) {
        $result = md_db_update(
            'simuze_friends',
            ['status' => 'accepted', 'updated_at' => date('Y-m-d H:i:s')],
            ['user_id' => $friend_id, 'friend_id' => $user_id, 'status' => 'pending']
        );

        if ($result) {
            Simuze_Activity::record_user_activity($user_id, 'friend_request_accepted', null, 'friends', $friend_id);
            if (class_exists('Simuze_Notification')) {
                Simuze_Notification::friend_request_accepted($friend_id, $user_id);
            }
        }

        return $result;
    }

    public static function get_friends($user_id, $status = 'accepted') {
        return md_db_query(
            "SELECT u.*, f.status, f.created_at as friendship_date
             FROM simuze_friends f
             INNER JOIN simuze_users u ON (f.friend_id = u.id AND f.user_id = ?) OR (f.user_id = u.id AND f.friend_id = ?)
             WHERE f.status = ? AND u.deleted_at IS NULL
             ORDER BY f.updated_at DESC",
            [$user_id, $user_id, $status]
        );
    }

    public static function are_friends($user_id, $friend_id) {
        return (bool) md_db_var(
            "SELECT id FROM simuze_friends
             WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))
             AND status = 'accepted'",
            [$user_id, $friend_id, $friend_id, $user_id]
        );
    }

    public static function unfriend($user_id, $friend_id) {
        $result = md_db_delete('simuze_friends', ['user_id' => $user_id, 'friend_id' => $friend_id]);
        md_db_delete('simuze_friends', ['user_id' => $friend_id, 'friend_id' => $user_id]);

        if ($result) {
            Simuze_Activity::record_user_activity($user_id, 'friend_removed', null, 'friends', $friend_id);
        }

        return $result;
    }

    public static function reject_request($user_id, $friend_id) {
        return md_db_delete('simuze_friends', ['user_id' => $friend_id, 'friend_id' => $user_id, 'status' => 'pending']);
    }

    public static function get_pending_requests($user_id) {
        return md_db_query(
            "SELECT u.*, f.id as request_id, f.created_at as requested_at
             FROM simuze_friends f
             INNER JOIN simuze_users u ON f.user_id = u.id
             WHERE f.friend_id = ? AND f.status = 'pending' AND u.deleted_at IS NULL
             ORDER BY f.created_at DESC",
            [$user_id]
        );
    }

    public static function count_friends($user_id) {
        return (int) md_db_var(
            "SELECT COUNT(*) FROM simuze_friends WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'",
            [$user_id, $user_id]
        );
    }

    public static function count_pending_requests($user_id) {
        return (int) md_db_var(
            "SELECT COUNT(*) FROM simuze_friends WHERE friend_id = ? AND status = 'pending'",
            [$user_id]
        );
    }

    public static function get_sent_requests($user_id) {
        return md_db_query(
            "SELECT * FROM simuze_friends WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC",
            [$user_id]
        );
    }

    public static function cancel_request($user_id, $friend_id) {
        return md_db_delete('simuze_friends', ['user_id' => $user_id, 'friend_id' => $friend_id, 'status' => 'pending']);
    }
}
