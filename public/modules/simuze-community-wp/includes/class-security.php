<?php
/**
 * Security and permission checks
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Security {
    
    /**
     * Check if user can delete content
     */
    public static function can_delete($user_id, $content_type, $content_id) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return false;
        }
        
        // Admin can delete anything
        if ($auth->is_admin()) {
            return true;
        }
        
        // Check ownership based on content type
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
    
    /**
     * Check if user can edit content
     */
    public static function can_edit($user_id, $content_type, $content_id) {
        return self::can_delete($user_id, $content_type, $content_id);
    }
    
    /**
     * Check if user owns activity/post
     */
    private static function is_activity_owner($user_id, $activity_id) {
        global $wpdb;
        
        // Check user activity
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'user_activity';
        $owner = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table WHERE id = %d",
            $activity_id
        ));
        
        if ($owner == $user_id) {
            return true;
        }
        
        // Check group activity
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_activity';
        $owner = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table WHERE id = %d",
            $activity_id
        ));
        
        return $owner == $user_id;
    }
    
    /**
     * Check if user owns message
     */
    private static function is_message_owner($user_id, $message_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'messages';
        
        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT sender_id, recipient_id FROM $table WHERE id = %d",
            $message_id
        ));
        
        if (!$message) {
            return false;
        }
        
        // User can delete their own sent or received messages
        return $message->sender_id == $user_id || $message->recipient_id == $user_id;
    }
    
    /**
     * Check if user is group admin
     */
    private static function is_group_admin($user_id, $group_id) {
        $group = Simuze_Group::get($group_id);
        if (!$group) {
            return false;
        }
        
        // Creator is always admin
        if ($group->creator_id == $user_id) {
            return true;
        }
        
        // Check if user is admin member
        return $group->is_member($user_id, 'admin');
    }
    
    /**
     * Check if user owns music content
     */
    private static function is_music_owner($user_id, $content_type, $content_id) {
        global $wpdb;
        
        if ($content_type === 'track' || $content_type === 'song') {
            // Check if user is member of group that owns the track
            $table = $wpdb->prefix . 'music_uploads';
            $upload = $wpdb->get_row($wpdb->prepare(
                "SELECT group_id FROM $table WHERE id = %d",
                $content_id
            ));
            
            if ($upload && $upload->group_id) {
                $group = Simuze_Group::get($upload->group_id);
                if ($group && $group->is_member($user_id)) {
                    return true;
                }
            }
        } elseif ($content_type === 'album') {
            // Check if user is member of group that owns the album
            $table = $wpdb->prefix . 'music_albums';
            $album = $wpdb->get_row($wpdb->prepare(
                "SELECT group_id FROM $table WHERE id = %d",
                $content_id
            ));
            
            if ($album && $album->group_id) {
                $group = Simuze_Group::get($album->group_id);
                if ($group && $group->is_member($user_id)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Validate user exists
     */
    public static function validate_user_exists($user_id) {
        $user = Simuze_User::get($user_id);
        return $user !== null && $user->status === 'active';
    }
    
    /**
     * Validate group exists
     */
    public static function validate_group_exists($group_id) {
        $group = Simuze_Group::get($group_id);
        return $group !== null;
    }
    
    /**
     * Sanitize error message (prevent data leakage)
     */
    public static function sanitize_error($error, $context = '') {
        // Don't expose sensitive information
        $sensitive_patterns = array(
            '/password/i',
            '/secret/i',
            '/key/i',
            '/token/i',
            '/database/i',
            '/sql/i',
        );
        
        foreach ($sensitive_patterns as $pattern) {
            if (preg_match($pattern, $error)) {
                return 'An error occurred. Please try again or contact support.';
            }
        }
        
        // Log full error for debugging
        if (function_exists('simuze_log')) {
            simuze_log('Error occurred', array(
                'error' => $error,
                'context' => $context,
            ));
        }
        
        // Return generic message for users
        return 'An error occurred. Please try again.';
    }
    
    /**
     * Check if user can access private group content
     */
    public static function can_access_group($user_id, $group_id) {
        $group = Simuze_Group::get($group_id);
        if (!$group) {
            return false;
        }
        
        // Public groups are accessible to everyone
        if ($group->status === 'public') {
            return true;
        }
        
        // Private groups only for members
        if ($group->status === 'private') {
            return $group->is_member($user_id);
        }
        
        return false;
    }
    
    /**
     * Check if user can view profile
     */
    public static function can_view_profile($viewer_id, $profile_user_id) {
        // Use privacy class if available
        if (class_exists('Simuze_Privacy')) {
            return Simuze_Privacy::can_view_profile($viewer_id, $profile_user_id);
        }
        
        // Fallback: users can always view their own profile
        if ($viewer_id == $profile_user_id) {
            return true;
        }
        
        $profile_user = Simuze_User::get($profile_user_id);
        if (!$profile_user) {
            return false;
        }
        
        // Default to public if privacy class not available
        return true;
    }
}

