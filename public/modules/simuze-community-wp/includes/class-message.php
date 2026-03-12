<?php
/**
 * Messaging system
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Message {
    
    /**
     * Send message
     */
    public static function send($sender_id, $recipient_id, $message, $subject = null, $group_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'messages';
        
        // Validate message content
        $message = trim($message);
        if (empty($message) || $message === '<br>' || $message === '<p></p>') {
            return false;
        }
        
        // Check privacy settings for messaging
        if (class_exists('Simuze_Privacy')) {
            if (!Simuze_Privacy::can_send_message($sender_id, $recipient_id)) {
                return false;
            }
        }
        
        // Validate user exists
        if (class_exists('Simuze_Security')) {
            if (!Simuze_Security::validate_user_exists($recipient_id)) {
                return false;
            }
        }
        
        // Get or create thread ID
        $thread_id = self::get_thread_id($sender_id, $recipient_id, $group_id);
        
        $data = array(
            'thread_id' => $thread_id,
            'sender_id' => $sender_id,
            'recipient_id' => $recipient_id,
            'group_id' => $group_id,
            'subject' => $subject,
            'message' => $message,
            'is_read' => 0,
            'created_at' => current_time('mysql'),
        );
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            $message_id = $wpdb->insert_id;
            
            // Record activity
            Simuze_Activity::record_user_activity(
                $sender_id,
                'message_sent',
                $message,
                'messages',
                $message_id
            );
            
            // Create notification
            if (class_exists('Simuze_Notification')) {
                $preview = wp_trim_words(strip_tags($message), 20);
                Simuze_Notification::new_message($recipient_id, $sender_id, $message_id, $preview);
            }
            
            // Process mentions
            self::process_mentions($message_id, $message);
            
            return $message_id;
        }
        
        return false;
    }
    
    /**
     * Get thread ID
     */
    private static function get_thread_id($user1_id, $user2_id, $group_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'messages';
        
        if ($group_id) {
            // Group message thread
            $thread_id = $wpdb->get_var($wpdb->prepare(
                "SELECT thread_id FROM $table WHERE group_id = %d LIMIT 1",
                $group_id
            ));
            
            if (!$thread_id) {
                $thread_id = time() . '_' . $group_id;
            }
        } else {
            // 1-on-1 thread
            $thread_id = $wpdb->get_var($wpdb->prepare(
                "SELECT thread_id FROM $table 
                WHERE ((sender_id = %d AND recipient_id = %d) OR (sender_id = %d AND recipient_id = %d))
                AND group_id IS NULL
                LIMIT 1",
                $user1_id,
                $user2_id,
                $user2_id,
                $user1_id
            ));
            
            if (!$thread_id) {
                $thread_id = min($user1_id, $user2_id) . '_' . max($user1_id, $user2_id);
            }
        }
        
        return $thread_id;
    }
    
    /**
     * Get user messages
     */
    public static function get_user_messages($user_id, $args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'messages';
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'thread_id' => null,
        );
        
        $args = array_merge($defaults, $args);
        
        $where = "(sender_id = %d OR recipient_id = %d) AND deleted_at IS NULL";
        $where_values = array($user_id, $user_id);
        
        if ($args['thread_id']) {
            $where .= " AND thread_id = %s";
            $where_values[] = $args['thread_id'];
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($where_values, array($args['per_page'], $offset))
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get message threads
     */
    public static function get_threads($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'messages';
        
        $threads = $wpdb->get_results($wpdb->prepare(
            "SELECT thread_id, MAX(created_at) as last_message_at, 
            COUNT(*) as message_count,
            SUM(CASE WHEN recipient_id = %d AND is_read = 0 THEN 1 ELSE 0 END) as unread_count
            FROM $table
            WHERE (sender_id = %d OR recipient_id = %d) AND deleted_at IS NULL
            GROUP BY thread_id
            ORDER BY last_message_at DESC",
            $user_id,
            $user_id,
            $user_id
        ), ARRAY_A);
        
        return $threads;
    }
    
    /**
     * Mark as read
     */
    public static function mark_as_read($message_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'messages';
        
        return $wpdb->update(
            $table,
            array('is_read' => 1),
            array('id' => $message_id, 'recipient_id' => $user_id),
            array('%d'),
            array('%d', '%d')
        );
    }
    
    /**
     * Count unread messages for user
     */
    public static function count_unread($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'messages';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE recipient_id = %d AND is_read = 0 AND deleted_at IS NULL",
            $user_id
        ));
    }
    
    /**
     * Count unread messages for group (messages sent to the group)
     */
    public static function count_unread_for_group($group_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'messages';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE group_id = %d AND is_read = 0 AND deleted_at IS NULL",
            $group_id
        ));
    }
    
    /**
     * Parse mentions from message
     */
    public static function parse_mentions($message) {
        $mentions = array();
        preg_match_all('/@(\w+)/', $message, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $username) {
                $user = Simuze_User::get_by_username($username);
                if ($user) {
                    $mentions[] = array(
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'display_name' => $user->display_name,
                    );
                }
            }
        }
        
        return $mentions;
    }
    
    /**
     * Process mentions in message
     */
    public static function process_mentions($message_id, $message) {
        $mentions = self::parse_mentions($message);
        
        if (!empty($mentions)) {
            global $wpdb;
            $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'messages';
            
            // Store mentions in meta
            $wpdb->update(
                $table,
                array('mentions' => json_encode($mentions)),
                array('id' => $message_id),
                array('%s'),
                array('%d')
            );
            
            // Record activity for mentioned users
            foreach ($mentions as $mention) {
                Simuze_Activity::record_user_activity(
                    $mention['user_id'],
                    'mentioned',
                    sprintf('You were mentioned in a message'),
                    'messages',
                    $message_id
                );
            }
        }
        
        return $mentions;
    }
    
    /**
     * Send message with mentions support
     */
    public static function send_with_mentions($sender_id, $recipient_id, $message, $subject = null, $group_id = null) {
        $message_id = self::send($sender_id, $recipient_id, $message, $subject, $group_id);
        
        if ($message_id) {
            self::process_mentions($message_id, $message);
        }
        
        return $message_id;
    }
    
    /**
     * Get messages with mentions
     */
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

