<?php
/**
 * Security and permission checks
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Security {

    public static function can_delete($user_id, $content_type, $content_id) {
        $auth         = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();

        if (!$current_user) return false;

        if ($auth->is_admin()) return true;

        switch ($content_type) {
            case 'activity':
            case 'post':
                return self::is_activity_owner($user_id, $content_id);

            case 'message':
                return self::is_message_owner($user_id, $content_id);

            case 'group':
                return self::is_group_admin($user_id, $content_id);

            case 'track':
            case 'album':
                return self::is_music_owner($user_id, $content_type, $content_id);

            default:
                return false;
        }
    }

    public static function can_edit($user_id, $content_type, $content_id) {
        return self::can_delete($user_id, $content_type, $content_id);
    }

    private static function is_activity_owner($user_id, $activity_id) {
        $owner = md_db_var("SELECT user_id FROM simuze_user_activity WHERE id = ?", [$activity_id]);
        if ($owner == $user_id) return true;

        $owner = md_db_var("SELECT user_id FROM simuze_group_activity WHERE id = ?", [$activity_id]);
        return $owner == $user_id;
    }

    private static function is_message_owner($user_id, $message_id) {
        $message = md_db_row("SELECT sender_id, recipient_id FROM simuze_messages WHERE id = ?", [$message_id]);
        if (!$message) return false;

        return $message['sender_id'] == $user_id || $message['recipient_id'] == $user_id;
    }

    private static function is_group_admin($user_id, $group_id) {
        $group = Simuze_Group::get($group_id);
        if (!$group) return false;

        if ($group->creator_id == $user_id) return true;

        return $group->is_member($user_id, 'admin');
    }

    private static function is_music_owner($user_id, $content_type, $content_id) {
        $table  = ($content_type === 'album') ? 'md_music_albums' : 'md_music_uploads';
        $row    = md_db_row("SELECT group_id FROM {$table} WHERE id = ?", [$content_id]);

        if ($row && $row['group_id']) {
            $group = Simuze_Group::get($row['group_id']);
            if ($group && $group->is_member($user_id)) return true;
        }

        return false;
    }

    public static function validate_user_exists($user_id) {
        $user = Simuze_User::get($user_id);
        return $user !== null && $user->status === 'active';
    }

    public static function validate_group_exists($group_id) {
        $group = Simuze_Group::get($group_id);
        return $group !== null;
    }

    public static function sanitize_error($error, $context = '') {
        $sensitive_patterns = ['/password/i', '/secret/i', '/key/i', '/token/i', '/database/i', '/sql/i'];

        foreach ($sensitive_patterns as $pattern) {
            if (preg_match($pattern, $error)) {
                return 'An error occurred. Please try again or contact support.';
            }
        }

        if (function_exists('simuze_log')) {
            simuze_log('Error occurred', ['error' => $error, 'context' => $context]);
        }

        return 'An error occurred. Please try again.';
    }

    public static function can_access_group($user_id, $group_id) {
        $group = Simuze_Group::get($group_id);
        if (!$group) return false;

        if ($group->status === 'public')  return true;
        if ($group->status === 'private') return $group->is_member($user_id);

        return false;
    }

    public static function can_view_profile($viewer_id, $profile_user_id) {
        if (class_exists('Simuze_Privacy')) {
            return Simuze_Privacy::can_view_profile($viewer_id, $profile_user_id);
        }

        if ($viewer_id == $profile_user_id) return true;

        $profile_user = Simuze_User::get($profile_user_id);
        if (!$profile_user) return false;

        return true;
    }
}
