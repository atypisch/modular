<?php
/**
 * Follow system
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Follow {

    public static function follow($follower_id, $followable_type, $followable_id) {
        if (class_exists('Simuze_Security')) {
            if (!Simuze_Security::validate_user_exists($follower_id)) return false;
            if ($followable_type === 'user'  && !Simuze_Security::validate_user_exists($followable_id))  return false;
            if ($followable_type === 'group' && !Simuze_Security::validate_group_exists($followable_id)) return false;
        }

        if ($followable_type === 'user' && $follower_id == $followable_id) return false;

        $exists = md_db_var(
            "SELECT id FROM simuze_follows WHERE follower_id = ? AND followable_type = ? AND followable_id = ?",
            [$follower_id, $followable_type, $followable_id]
        );
        if ($exists) return false;

        $result = md_db_insert('simuze_follows', [
            'follower_id'    => $follower_id,
            'followable_type'=> $followable_type,
            'followable_id'  => $followable_id,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        if ($result) {
            $action = $followable_type === 'user' ? 'user_followed' : 'group_followed';
            Simuze_Activity::record_user_activity($follower_id, $action, null, 'follows', $followable_id);
        }

        return $result;
    }

    public static function unfollow($follower_id, $followable_type, $followable_id) {
        return md_db_delete('simuze_follows', [
            'follower_id'    => $follower_id,
            'followable_type'=> $followable_type,
            'followable_id'  => $followable_id,
        ]);
    }

    public static function is_following($follower_id, $followable_type, $followable_id) {
        return (bool) md_db_var(
            "SELECT id FROM simuze_follows WHERE follower_id = ? AND followable_type = ? AND followable_id = ?",
            [$follower_id, $followable_type, $followable_id]
        );
    }

    public static function get_followers($followable_type, $followable_id) {
        return md_db_query(
            "SELECT u.*, f.created_at as followed_at
             FROM simuze_follows f
             INNER JOIN simuze_users u ON f.follower_id = u.id
             WHERE f.followable_type = ? AND f.followable_id = ? AND u.deleted_at IS NULL
             ORDER BY f.created_at DESC",
            [$followable_type, $followable_id]
        );
    }

    public static function get_following($follower_id, $followable_type = null) {
        if ($followable_type) {
            return md_db_query(
                "SELECT * FROM simuze_follows WHERE follower_id = ? AND followable_type = ? ORDER BY created_at DESC",
                [$follower_id, $followable_type]
            );
        }
        return md_db_query(
            "SELECT * FROM simuze_follows WHERE follower_id = ? ORDER BY created_at DESC",
            [$follower_id]
        );
    }

    public static function count_followers($followable_id, $followable_type = 'user') {
        return (int) md_db_var(
            "SELECT COUNT(*) FROM simuze_follows WHERE followable_type = ? AND followable_id = ?",
            [$followable_type, $followable_id]
        );
    }

    public static function count_following($user_id, $followable_type = null) {
        if ($followable_type) {
            return (int) md_db_var(
                "SELECT COUNT(*) FROM simuze_follows WHERE follower_id = ? AND followable_type = ?",
                [$user_id, $followable_type]
            );
        }
        return (int) md_db_var(
            "SELECT COUNT(*) FROM simuze_follows WHERE follower_id = ?",
            [$user_id]
        );
    }
}

// -------------------------------------------------------------------------
// AJAX handlers
// -------------------------------------------------------------------------

function simuze_ajax_follow_group() {
    if (empty($GLOBALS['is_logged_in'])) {
        echo json_encode(['success' => false, 'data' => 'Not logged in']);
        exit;
    }
    $group_id = (int) ($_POST['group_id'] ?? 0);
    if (!$group_id) {
        echo json_encode(['success' => false, 'data' => 'Invalid group']);
        exit;
    }
    $user_id = (int) ($GLOBALS['current_user']->id ?? 0);
    $result = Simuze_Follow::follow($user_id, 'group', $group_id);
    echo json_encode(['success' => (bool) $result]);
    exit;
}

function simuze_ajax_unfollow_group() {
    if (empty($GLOBALS['is_logged_in'])) {
        echo json_encode(['success' => false, 'data' => 'Not logged in']);
        exit;
    }
    $group_id = (int) ($_POST['group_id'] ?? 0);
    if (!$group_id) {
        echo json_encode(['success' => false, 'data' => 'Invalid group']);
        exit;
    }
    $user_id = (int) ($GLOBALS['current_user']->id ?? 0);
    $result = Simuze_Follow::unfollow($user_id, 'group', $group_id);
    echo json_encode(['success' => (bool) $result]);
    exit;
}

add_action('wp_ajax_simuze_follow_group',   'simuze_ajax_follow_group');
add_action('wp_ajax_simuze_unfollow_group', 'simuze_ajax_unfollow_group');
