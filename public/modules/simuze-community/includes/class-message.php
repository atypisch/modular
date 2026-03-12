<?php
/**
 * Messaging system
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Message {

    public static function send($sender_id, $recipient_id, $message, $subject = null, $group_id = null) {
        $message = trim($message);
        if (empty($message) || $message === '<br>' || $message === '<p></p>') {
            return false;
        }

        if (class_exists('Simuze_Privacy')) {
            if (!Simuze_Privacy::can_send_message($sender_id, $recipient_id)) return false;
        }

        if (class_exists('Simuze_Security')) {
            if (!Simuze_Security::validate_user_exists($recipient_id)) return false;
        }

        $thread_id = self::get_thread_id($sender_id, $recipient_id, $group_id);

        $message_id = md_db_insert('simuze_messages', [
            'thread_id'    => $thread_id,
            'sender_id'    => $sender_id,
            'recipient_id' => $recipient_id,
            'group_id'     => $group_id,
            'subject'      => $subject,
            'message'      => $message,
            'is_read'      => 0,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        if ($message_id) {
            Simuze_Activity::record_user_activity($sender_id, 'message_sent', $message, 'messages', $message_id);

            if (class_exists('Simuze_Notification')) {
                $preview = mb_strimwidth(strip_tags($message), 0, 100, '...');
                Simuze_Notification::new_message($recipient_id, $sender_id, $message_id, $preview);
            }

            self::process_mentions($message_id, $message);

            return $message_id;
        }

        return false;
    }

    private static function get_thread_id($user1_id, $user2_id, $group_id = null) {
        if ($group_id) {
            $thread_id = md_db_var("SELECT thread_id FROM simuze_messages WHERE group_id = ? LIMIT 1", [$group_id]);
            return $thread_id ?: (time() . '_' . $group_id);
        }

        $thread_id = md_db_var(
            "SELECT thread_id FROM simuze_messages
             WHERE ((sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?))
             AND group_id IS NULL
             LIMIT 1",
            [$user1_id, $user2_id, $user2_id, $user1_id]
        );

        return $thread_id ?: (min($user1_id, $user2_id) . '_' . max($user1_id, $user2_id));
    }

    public static function get_user_messages($user_id, $args = array()) {
        $defaults = ['per_page' => 20, 'page' => 1, 'thread_id' => null];
        $args     = array_merge($defaults, $args);

        $where        = '(sender_id = ? OR recipient_id = ?) AND deleted_at IS NULL';
        $where_values = [$user_id, $user_id];

        if ($args['thread_id']) {
            $where         .= ' AND thread_id = ?';
            $where_values[] = $args['thread_id'];
        }

        $offset = ($args['page'] - 1) * $args['per_page'];

        return md_db_query(
            "SELECT * FROM simuze_messages WHERE {$where} ORDER BY created_at DESC LIMIT {$args['per_page']} OFFSET {$offset}",
            $where_values
        );
    }

    public static function get_threads($user_id) {
        return md_db_query(
            "SELECT thread_id, MAX(created_at) as last_message_at,
             COUNT(*) as message_count,
             SUM(CASE WHEN recipient_id = ? AND is_read = 0 THEN 1 ELSE 0 END) as unread_count
             FROM simuze_messages
             WHERE (sender_id = ? OR recipient_id = ?) AND deleted_at IS NULL
             GROUP BY thread_id
             ORDER BY last_message_at DESC",
            [$user_id, $user_id, $user_id]
        );
    }

    public static function mark_as_read($message_id, $user_id) {
        return md_db_update('simuze_messages', ['is_read' => 1], ['id' => $message_id, 'recipient_id' => $user_id]);
    }

    public static function count_unread($user_id) {
        return (int) md_db_var(
            "SELECT COUNT(*) FROM simuze_messages WHERE recipient_id = ? AND is_read = 0 AND deleted_at IS NULL",
            [$user_id]
        );
    }

    public static function count_unread_for_group($group_id) {
        return (int) md_db_var(
            "SELECT COUNT(*) FROM simuze_messages WHERE group_id = ? AND is_read = 0 AND deleted_at IS NULL",
            [$group_id]
        );
    }

    public static function parse_mentions($message) {
        $mentions = [];
        preg_match_all('/@(\w+)/', $message, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $username) {
                $user = Simuze_User::get_by_username($username);
                if ($user) {
                    $mentions[] = [
                        'user_id'      => $user->id,
                        'username'     => $user->username,
                        'display_name' => $user->display_name,
                    ];
                }
            }
        }

        return $mentions;
    }

    public static function process_mentions($message_id, $message) {
        $mentions = self::parse_mentions($message);

        if (!empty($mentions)) {
            md_db_update('simuze_messages', ['mentions' => json_encode($mentions)], ['id' => $message_id]);

            foreach ($mentions as $mention) {
                Simuze_Activity::record_user_activity(
                    $mention['user_id'],
                    'mentioned',
                    'You were mentioned in a message',
                    'messages',
                    $message_id
                );
            }
        }

        return $mentions;
    }

    public static function send_with_mentions($sender_id, $recipient_id, $message, $subject = null, $group_id = null) {
        $message_id = self::send($sender_id, $recipient_id, $message, $subject, $group_id);
        if ($message_id) {
            self::process_mentions($message_id, $message);
        }
        return $message_id;
    }

    public static function get_messages_with_mentions($user_id, $args = array()) {
        $messages = self::get_user_messages($user_id, $args);

        foreach ($messages as &$message) {
            if (!empty($message['mentions'])) {
                $message['mentions'] = json_decode($message['mentions'], true);
            }
        }

        return $messages;
    }
}
