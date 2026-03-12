<?php
/**
 * Notification system
 * Handles all user notifications (friend requests, messages, purchases, etc.)
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Notification {

    /**
     * Create a notification
     */
    public static function create($user_id, $type, $data = array()) {
        $table = 'simuze_notifications';

        $defaults = array(
            'user_id' => $user_id,
            'type' => $type,
            'title' => '',
            'message' => '',
            'link' => null,
            'meta' => null,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        );

        $data = array_merge($defaults, $data);

        // Convert meta to JSON if array
        if (is_array($data['meta'])) {
            $data['meta'] = json_encode($data['meta']);
        }

        $result = md_db_insert($table, $data);

        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * Create notification for friend request
     */
    public static function friend_request($recipient_id, $sender_id) {
        $sender = Simuze_User::get($sender_id);
        if (!$sender) {
            return false;
        }

        return self::create($recipient_id, 'friend_request', array(
            'title' => 'New Friend Request',
            'message' => sprintf('%s wants to be your friend', $sender->display_name),
            'link' => md_home_url('/profile/?tab=friends'),
            'meta' => array(
                'sender_id' => $sender_id,
                'sender_name' => $sender->display_name,
            ),
        ));
    }

    /**
     * Create notification for friend request accepted
     */
    public static function friend_request_accepted($user_id, $friend_id) {
        $friend = Simuze_User::get($friend_id);
        if (!$friend) {
            return false;
        }

        return self::create($user_id, 'friend_accepted', array(
            'title' => 'Friend Request Accepted',
            'message' => sprintf('%s accepted your friend request', $friend->display_name),
            'link' => md_home_url('/profile/?user=' . $friend_id),
            'meta' => array(
                'friend_id' => $friend_id,
                'friend_name' => $friend->display_name,
            ),
        ));
    }

    /**
     * Create notification for new message
     */
    public static function new_message($recipient_id, $sender_id, $message_id, $preview = '') {
        $sender = Simuze_User::get($sender_id);
        if (!$sender) {
            return false;
        }

        return self::create($recipient_id, 'message', array(
            'title' => 'New Message',
            'message' => sprintf('%s sent you a message', $sender->display_name),
            'link' => md_home_url('/profile/?tab=messages&thread=' . $message_id),
            'meta' => array(
                'sender_id' => $sender_id,
                'sender_name' => $sender->display_name,
                'message_id' => $message_id,
                'preview' => $preview,
            ),
        ));
    }

    /**
     * Create notification for mention
     */
    public static function mention($user_id, $mentioned_by_id, $context_type, $context_id, $context_url = null) {
        $mentioned_by = Simuze_User::get($mentioned_by_id);
        if (!$mentioned_by) {
            return false;
        }

        $context_labels = array(
            'message' => 'message',
            'activity' => 'post',
            'group' => 'group post',
        );

        $context_label = $context_labels[$context_type] ?? 'content';

        return self::create($user_id, 'mention', array(
            'title' => 'You were mentioned',
            'message' => sprintf('%s mentioned you in a %s', $mentioned_by->display_name, $context_label),
            'link' => $context_url ?: md_home_url('/profile/'),
            'meta' => array(
                'mentioned_by_id' => $mentioned_by_id,
                'mentioned_by_name' => $mentioned_by->display_name,
                'context_type' => $context_type,
                'context_id' => $context_id,
            ),
        ));
    }

    /**
     * Create notification for track purchase
     */
    public static function track_purchased($seller_id, $buyer_id, $track_id, $order_id) {
        $buyer = Simuze_User::get($buyer_id);
        if (!$buyer) {
            return false;
        }

        return self::create($seller_id, 'purchase', array(
            'title' => 'Track Purchased',
            'message' => sprintf('%s purchased your track', $buyer->display_name),
            'link' => md_home_url('/profile/?tab=seller&order=' . $order_id),
            'meta' => array(
                'buyer_id' => $buyer_id,
                'buyer_name' => $buyer->display_name,
                'track_id' => $track_id,
                'order_id' => $order_id,
            ),
        ));
    }

    /**
     * Create notification for group activity
     */
    public static function group_activity($user_id, $group_id, $activity_type, $activity_id) {
        $group = Simuze_Group::get($group_id);
        if (!$group) {
            return false;
        }

        $labels = array(
            'new_post' => 'New post in',
            'new_track' => 'New track uploaded to',
            'member_joined' => 'New member joined',
        );

        $label = $labels[$activity_type] ?? 'New activity in';

        return self::create($user_id, 'group_activity', array(
            'title' => 'Group Activity',
            'message' => sprintf('%s %s', $label, $group->name),
            'link' => $group->get_url() . '?tab=activity',
            'meta' => array(
                'group_id' => $group_id,
                'group_name' => $group->name,
                'activity_type' => $activity_type,
                'activity_id' => $activity_id,
            ),
        ));
    }

    /**
     * Create notification for group invitation.
     */
    public static function group_invite($recipient_id, $group_id, $invited_by_id) {
        $group   = Simuze_Group::get($group_id);
        $inviter = Simuze_User::get($invited_by_id);
        if (!$group || !$inviter) {
            return false;
        }
        return self::create($recipient_id, 'group_invite', array(
            'title'   => 'Group Invitation',
            'message' => sprintf('%s invited you to join %s', $inviter->display_name ?? $inviter->username, $group->name),
            'link'    => md_home_url('/group/' . $group->slug . '/'),
            'meta'    => array(
                'group_id'   => $group_id,
                'group_name' => $group->name,
                'inviter_id' => $invited_by_id,
            ),
        ));
    }

    /**
     * Get user notifications
     */
    public static function get_user_notifications($user_id, $args = array()) {
        $table = 'simuze_notifications';

        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'unread_only' => false,
            'type' => null,
        );

        $args = array_merge($defaults, $args);

        $where = "user_id = ?";
        $where_values = array($user_id);

        if ($args['unread_only']) {
            $where .= " AND is_read = 0";
        }

        if ($args['type']) {
            $where .= " AND type = ?";
            $where_values[] = $args['type'];
        }

        $offset = ($args['page'] - 1) * $args['per_page'];

        $where_values[] = $args['per_page'];
        $where_values[] = $offset;

        $notifications = md_db_query(
            "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?",
            $where_values
        );

        // Decode meta JSON
        foreach ($notifications as &$notification) {
            if (!empty($notification['meta'])) {
                $notification['meta'] = json_decode($notification['meta'], true);
            }
        }

        return $notifications;
    }

    /**
     * Mark notification as read
     */
    public static function mark_as_read($notification_id, $user_id) {
        $table = 'simuze_notifications';

        return md_db_update(
            $table,
            array('is_read' => 1, 'read_at' => date('Y-m-d H:i:s')),
            array('id' => $notification_id, 'user_id' => $user_id)
        );
    }

    /**
     * Mark all notifications as read for user
     */
    public static function mark_all_as_read($user_id) {
        $table = 'simuze_notifications';

        return md_db_update(
            $table,
            array('is_read' => 1, 'read_at' => date('Y-m-d H:i:s')),
            array('user_id' => $user_id, 'is_read' => 0)
        );
    }

    /**
     * Count unread notifications
     */
    public static function count_unread($user_id) {
        $table = 'simuze_notifications';

        return (int) md_db_var(
            "SELECT COUNT(*) FROM $table WHERE user_id = ? AND is_read = 0",
            array($user_id)
        );
    }

    /**
     * Delete notification
     */
    public static function delete($notification_id, $user_id) {
        $table = 'simuze_notifications';

        return md_db_delete(
            $table,
            array('id' => $notification_id, 'user_id' => $user_id)
        );
    }

    /**
     * Delete all notifications for user
     */
    public static function delete_all($user_id, $read_only = false) {
        $table = 'simuze_notifications';

        $where = array('user_id' => $user_id);

        if ($read_only) {
            $where['is_read'] = 1;
        }

        return md_db_delete($table, $where);
    }
}
