<?php
/**
 * Activity system
 * Includes activity feed and weblog/post functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Activity {
    
    /**
     * Create a new activity post (weblog entry)
     * Users and Groups can create posts that appear in activity feeds
     */
    public static function create_post($author_id, $content, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'group_id' => null,      // If set, this is a group post
            'title' => null,         // Optional title
            'visibility' => 'public', // public, friends, private
        );
        
        $args = array_merge($defaults, $args);
        
        // Determine which table to use
        if ($args['group_id']) {
            $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_activity';
            $data = array(
                'group_id' => $args['group_id'],
                'user_id' => $author_id,
                'action' => 'post',
                'content' => $content,
                'component' => 'activity',
                'meta' => json_encode(array(
                    'title' => $args['title'],
                    'visibility' => $args['visibility'],
                    'is_post' => true,
                    'image_url' => isset($args['image_url']) ? $args['image_url'] : null,
                    'video_url' => isset($args['video_url']) ? $args['video_url'] : null,
                )),
                'created_at' => current_time('mysql'),
            );
        } else {
            $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'user_activity';
            $data = array(
                'user_id' => $author_id,
                'action' => 'post',
                'content' => $content,
                'component' => 'activity',
                'meta' => json_encode(array(
                    'title' => $args['title'],
                    'visibility' => $args['visibility'],
                    'is_post' => true,
                    'image_url' => isset($args['image_url']) ? $args['image_url'] : null,
                    'video_url' => isset($args['video_url']) ? $args['video_url'] : null,
                )),
                'created_at' => current_time('mysql'),
            );
        }
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update an activity post
     */
    public static function update_post($post_id, $content, $is_group_post = false, $args = array()) {
        global $wpdb;
        
        $table = $is_group_post 
            ? $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_activity'
            : $wpdb->prefix . SIMUZE_DB_PREFIX . 'user_activity';
        
        $update_data = array(
            'content' => $content,
            'updated_at' => current_time('mysql'),
        );
        
        if (!empty($args)) {
            // Get existing meta
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT meta FROM $table WHERE id = %d",
                $post_id
            ));
            $meta = $existing ? json_decode($existing, true) : array();
            
            if (isset($args['title'])) {
                $meta['title'] = $args['title'];
            }
            if (isset($args['visibility'])) {
                $meta['visibility'] = $args['visibility'];
            }
            
            $update_data['meta'] = json_encode($meta);
        }
        
        return $wpdb->update($table, $update_data, array('id' => $post_id));
    }
    
    /**
     * Delete an activity post (soft delete)
     */
    public static function delete_post($post_id, $is_group_post = false) {
        global $wpdb;
        
        $table = $is_group_post 
            ? $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_activity'
            : $wpdb->prefix . SIMUZE_DB_PREFIX . 'user_activity';
        
        return $wpdb->update(
            $table,
            array('deleted_at' => current_time('mysql')),
            array('id' => $post_id)
        );
    }
    
    /**
     * Get a single post
     */
    public static function get_post($post_id, $is_group_post = false) {
        global $wpdb;
        
        $table = $is_group_post 
            ? $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_activity'
            : $wpdb->prefix . SIMUZE_DB_PREFIX . 'user_activity';
        
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND deleted_at IS NULL",
            $post_id
        ), ARRAY_A);
        
        if ($post && !empty($post['meta'])) {
            $post['meta'] = json_decode($post['meta'], true);
        }
        
        return $post;
    }
    
    /**
     * Get posts by user (weblog entries)
     */
    public static function get_user_posts($user_id, $args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'user_activity';
        
        $defaults = array(
            'per_page' => 10,
            'page' => 1,
            'viewer_id' => null, // For visibility filtering
        );
        
        $args = array_merge($defaults, $args);
        
        $where = "user_id = %d AND action = 'post' AND deleted_at IS NULL";
        $params = array($user_id);
        
        // Visibility filtering
        if ($args['viewer_id'] && $args['viewer_id'] != $user_id) {
            // If not own posts, filter by visibility
            $is_friend = class_exists('Simuze_Friend') 
                ? Simuze_Friend::are_friends($user_id, $args['viewer_id']) 
                : false;
            
            if ($is_friend) {
                $where .= " AND (meta IS NULL OR JSON_EXTRACT(meta, '$.visibility') IN ('public', 'friends'))";
            } else {
                $where .= " AND (meta IS NULL OR JSON_EXTRACT(meta, '$.visibility') = 'public')";
            }
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, array($args['per_page'], $offset))
        ), ARRAY_A);
        
        foreach ($posts as &$post) {
            if (!empty($post['meta'])) {
                $post['meta'] = json_decode($post['meta'], true);
            }
        }
        
        return $posts;
    }
    
    /**
     * Get posts by group
     */
    public static function get_group_posts($group_id, $args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_activity';
        
        $defaults = array(
            'per_page' => 10,
            'page' => 1,
        );
        
        $args = array_merge($defaults, $args);
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE group_id = %d AND action = 'post' AND deleted_at IS NULL ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $group_id,
            $args['per_page'],
            $offset
        ), ARRAY_A);
        
        foreach ($posts as &$post) {
            if (!empty($post['meta'])) {
                $post['meta'] = json_decode($post['meta'], true);
            }
        }
        
        return $posts;
    }
    
    /**
     * Record user activity
     */
    public static function record_user_activity($user_id, $action, $content = null, $component = null, $item_id = null, $meta = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'user_activity';
        
        $data = array(
            'user_id' => $user_id,
            'action' => $action,
            'content' => $content,
            'component' => $component,
            'item_id' => $item_id,
            'meta' => is_array($meta) ? json_encode($meta) : $meta,
            'created_at' => current_time('mysql'),
        );
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Get user's own activity
     */
    public static function get_user_activity($user_id, $limit = 20) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'user_activity';
        
        $activity = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);
        
        // Format activity items
        foreach ($activity as &$item) {
            if (!empty($item['meta'])) {
                $item['meta'] = json_decode($item['meta'], true);
            }
        }
        
        return $activity;
    }
    
    /**
     * Record group activity
     */
    public static function record_group_activity($group_id, $user_id, $action, $content = null, $component = null, $item_id = null, $meta = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_activity';
        
        $data = array(
            'group_id' => $group_id,
            'user_id' => $user_id,
            'action' => $action,
            'content' => $content,
            'component' => $component,
            'item_id' => $item_id,
            'meta' => is_array($meta) ? json_encode($meta) : $meta,
            'created_at' => current_time('mysql'),
        );
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Get user activity feed (aggregated)
     */
    public static function get_user_feed($user_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'include_own' => true,
            'include_friends' => true,
            'include_follows' => true,
            'include_groups' => true,
        );
        
        $args = array_merge($defaults, $args);
        
        $user_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'user_activity';
        $group_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_activity';
        $follows_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'follows';
        $friends_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'friends';
        $members_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_members';
        
        $where_parts = array();
        $where_values = array();
        
        // Own activity
        if ($args['include_own']) {
            $where_parts[] = "ua.user_id = %d";
            $where_values[] = $user_id;
        }
        
        // Friends activity
        if ($args['include_friends'] && class_exists('Simuze_Friend')) {
            $friends = Simuze_Friend::get_friends($user_id);
            if (!empty($friends)) {
                $friend_ids = array_column($friends, 'id');
                $placeholders = implode(',', array_fill(0, count($friend_ids), '%d'));
                $where_parts[] = "ua.user_id IN ($placeholders)";
                $where_values = array_merge($where_values, $friend_ids);
            }
        }
        
        // Followed users activity
        if ($args['include_follows']) {
            $followed_users = $wpdb->get_col($wpdb->prepare(
                "SELECT followed_id FROM $follows_table WHERE follower_id = %d AND follow_type = 'user'",
                $user_id
            ));
            if (!empty($followed_users)) {
                $placeholders = implode(',', array_fill(0, count($followed_users), '%d'));
                $where_parts[] = "ua.user_id IN ($placeholders)";
                $where_values = array_merge($where_values, $followed_users);
            }
        }
        
        // Groups user is member of
        if ($args['include_groups']) {
            $group_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT group_id FROM $members_table WHERE user_id = %d AND status = 'active'",
                $user_id
            ));
            if (!empty($group_ids)) {
                $placeholders = implode(',', array_fill(0, count($group_ids), '%d'));
                $where_parts[] = "ga.group_id IN ($placeholders)";
                $where_values = array_merge($where_values, $group_ids);
            }
        }
        
        if (empty($where_parts)) {
            return array();
        }
        
        $where_clause = '(' . implode(' OR ', $where_parts) . ')';
        
        // Combine user and group activity
        $query = "
            SELECT 
                'user' as activity_type,
                ua.id,
                ua.user_id,
                NULL as group_id,
                ua.action,
                ua.content,
                ua.component,
                ua.item_id,
                ua.meta,
                ua.created_at
            FROM $user_table ua
            WHERE $where_clause
            
            UNION ALL
            
            SELECT 
                'group' as activity_type,
                ga.id,
                ga.user_id,
                ga.group_id,
                ga.action,
                ga.content,
                ga.component,
                ga.item_id,
                ga.meta,
                ga.created_at
            FROM $group_table ga
            WHERE ga.group_id IN (" . implode(',', array_fill(0, count($group_ids ?: array(0)), '%d')) . ")
            
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d
        ";
        
        $where_values = array_merge($where_values, $group_ids, array($args['per_page'], ($args['page'] - 1) * $args['per_page']));
        
        $activity = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
        
        // Format activity items
        foreach ($activity as &$item) {
            if ($item['meta']) {
                $item['meta'] = json_decode($item['meta'], true);
            }
        }
        
        return $activity;
    }
    
    /**
     * Get group activity feed
     */
    public static function get_group_feed($group_id, $args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_activity';
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
        );
        
        $args = array_merge($defaults, $args);
        
        $activity = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE group_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $group_id,
            $args['per_page'],
            ($args['page'] - 1) * $args['per_page']
        ), ARRAY_A);
        
        // Format activity items
        foreach ($activity as &$item) {
            if ($item['meta']) {
                $item['meta'] = json_decode($item['meta'], true);
            }
        }
        
        return $activity;
    }
    
    /**
     * Format activity content
     */
    public static function format_activity($activity) {
        $user = Simuze_User::get($activity['user_id']);
        $user_name = $user ? $user->display_name : 'Unknown';
        
        $content = '';
        
        switch ($activity['action']) {
            case 'song_uploaded':
                $content = sprintf('%s uploaded a new song', $user_name);
                break;
            case 'playlist_created':
                $content = sprintf('%s created a new playlist', $user_name);
                break;
            case 'user_followed':
                $followed_user = Simuze_User::get($activity['item_id']);
                if ($followed_user) {
                    $content = sprintf('%s started following %s', $user_name, $followed_user->display_name);
                }
                break;
            case 'group_followed':
                $group = Simuze_Group::get($activity['item_id']);
                if ($group) {
                    $content = sprintf('%s started following %s', $user_name, $group->name);
                }
                break;
            case 'group_joined':
                $group = Simuze_Group::get($activity['item_id']);
                if ($group) {
                    $content = sprintf('%s joined %s', $user_name, $group->name);
                }
                break;
            case 'message_sent':
                $content = sprintf('%s sent a message', $user_name);
                break;
            case 'review_submitted':
                $content = sprintf('%s submitted a review', $user_name);
                break;
            default:
                $content = $activity['content'] ?: sprintf('%s performed an action', $user_name);
        }
        
        return $content;
    }
}

