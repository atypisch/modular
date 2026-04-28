<?php
/**
 * Privacy settings system
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Privacy {

    /**
     * Get user privacy settings
     */
    public static function get_user_settings($user_id) {
        $table = 'simuze_profiles';

        $profile = md_db_row(
            "SELECT privacy_settings FROM $table WHERE user_id = ?",
            array($user_id)
        );

        if ($profile && !empty($profile['privacy_settings'])) {
            $settings = json_decode($profile['privacy_settings'], true);
        } else {
            // Default privacy settings
            $settings = array(
                'profile_visibility' => 'public',
                'activity_visibility' => 'public',
                'message_restriction' => 'everyone', // everyone, friends, none
                'show_email' => false,
                'show_location' => true,
            );
        }

        return $settings;
    }

    /**
     * Update user privacy settings
     */
    public static function update_user_settings($user_id, $settings) {
        $table = 'simuze_profiles';

        // Get existing settings and merge
        $existing = self::get_user_settings($user_id);
        $settings = array_merge($existing, $settings);

        // Validate settings
        $valid_visibility = array('public', 'friends', 'private');
        if (isset($settings['profile_visibility']) && !in_array($settings['profile_visibility'], $valid_visibility)) {
            $settings['profile_visibility'] = 'public';
        }

        if (isset($settings['activity_visibility']) && !in_array($settings['activity_visibility'], $valid_visibility)) {
            $settings['activity_visibility'] = 'public';
        }

        $valid_message_restriction = array('everyone', 'friends', 'none');
        if (isset($settings['message_restriction']) && !in_array($settings['message_restriction'], $valid_message_restriction)) {
            $settings['message_restriction'] = 'everyone';
        }

        $result = md_db_update(
            $table,
            array('privacy_settings' => json_encode($settings)),
            array('user_id' => $user_id)
        );

        return $result !== false;
    }

    /**
     * Get group privacy settings
     */
    public static function get_group_settings($group_id) {
        $group = Simuze_Group::get($group_id);
        if (!$group) {
            return null;
        }

        $settings = array(
            'visibility' => $group->status, // public, private
            'join_restriction' => 'open', // open, invite_only, closed
            'activity_visibility' => 'public', // public, members_only
        );

        // Get additional settings from group meta if available
        if (!empty($group->settings)) {
            $group_settings = is_string($group->settings) ? json_decode($group->settings, true) : $group->settings;
            if (is_array($group_settings)) {
                $settings = array_merge($settings, $group_settings);
            }
        }

        return $settings;
    }

    /**
     * Update group privacy settings
     */
    public static function update_group_settings($group_id, $settings) {
        $group = Simuze_Group::get($group_id);
        if (!$group) {
            return false;
        }

        // Validate settings
        $valid_visibility = array('public', 'private');
        if (isset($settings['visibility']) && in_array($settings['visibility'], $valid_visibility)) {
            $group->update(array('status' => $settings['visibility']));
        }

        // Store additional settings in meta
        $existing_settings = self::get_group_settings($group_id);
        $merged_settings = array_merge($existing_settings, $settings);

        // Remove visibility as it's stored in status
        unset($merged_settings['visibility']);

        $group->update(array('settings' => json_encode($merged_settings)));

        return true;
    }

    /**
     * Check if user can view profile
     */
    public static function can_view_profile($viewer_id, $profile_user_id) {
        // Users can always view their own profile
        if ($viewer_id == $profile_user_id) {
            return true;
        }

        $settings = self::get_user_settings($profile_user_id);

        if ($settings['profile_visibility'] === 'public') {
            return true;
        }

        if ($settings['profile_visibility'] === 'friends') {
            return Simuze_Friend::are_friends($viewer_id, $profile_user_id);
        }

        // private
        return false;
    }

    /**
     * Check if user can view activity
     */
    public static function can_view_activity($viewer_id, $activity_user_id) {
        // Users can always view their own activity
        if ($viewer_id == $activity_user_id) {
            return true;
        }

        $settings = self::get_user_settings($activity_user_id);

        if ($settings['activity_visibility'] === 'public') {
            return true;
        }

        if ($settings['activity_visibility'] === 'friends') {
            return Simuze_Friend::are_friends($viewer_id, $activity_user_id);
        }

        // private
        return false;
    }

    /**
     * Check if user can send message
     */
    public static function can_send_message($sender_id, $recipient_id) {
        $settings = self::get_user_settings($recipient_id);

        if ($settings['message_restriction'] === 'everyone') {
            return true;
        }

        if ($settings['message_restriction'] === 'friends') {
            return Simuze_Friend::are_friends($sender_id, $recipient_id);
        }

        // none
        return false;
    }
}
