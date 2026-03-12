<?php
/**
 * Activity system
 * Includes activity feed and weblog/post functionality
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Activity {

    /**
     * Create a new activity post (weblog entry)
     * Users and Groups can create posts that appear in activity feeds
     */
    public static function create_post($author_id, $content, $args = array()) {
        $defaults = array(
            'group_id' => null,      // If set, this is a group post
            'title' => null,         // Optional title
            'visibility' => 'public', // public, friends, private
        );

        $args = array_merge($defaults, $args);

        // Determine which table to use
        if ($args['group_id']) {
            $table = 'simuze_group_activity';
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
                'created_at' => date('Y-m-d H:i:s'),
            );
        } else {
            $table = 'simuze_user_activity';
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
                'created_at' => date('Y-m-d H:i:s'),
            );
        }

        $result = md_db_insert($table, $data);

        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * Update an activity post
     */
    public static function update_post($post_id, $content, $is_group_post = false, $args = array()) {
        $table = $is_group_post
            ? 'simuze_group_activity'
            : 'simuze_user_activity';

        $update_data = array(
            'content' => $content,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        if (!empty($args)) {
            // Get existing meta
            $existing = md_db_var(
                "SELECT meta FROM $table WHERE id = ?",
                array($post_id)
            );
            $meta = $existing ? json_decode($existing, true) : array();

            if (isset($args['title'])) {
                $meta['title'] = $args['title'];
            }
            if (isset($args['visibility'])) {
                $meta['visibility'] = $args['visibility'];
            }

            $update_data['meta'] = json_encode($meta);
        }

        return md_db_update($table, $update_data, array('id' => $post_id));
    }

    /**
     * Delete an activity post (soft delete)
     */
    public static function delete_post($post_id, $is_group_post = false) {
        $table = $is_group_post
            ? 'simuze_group_activity'
            : 'simuze_user_activity';

        return md_db_update(
            $table,
            array('deleted_at' => date('Y-m-d H:i:s')),
            array('id' => $post_id)
        );
    }

    /**
     * Get a single post
     */
    public static function get_post($post_id, $is_group_post = false) {
        $table = $is_group_post
            ? 'simuze_group_activity'
            : 'simuze_user_activity';

        $post = md_db_row(
            "SELECT * FROM $table WHERE id = ? AND deleted_at IS NULL",
            array($post_id)
        );

        if ($post && !empty($post['meta'])) {
            $post['meta'] = json_decode($post['meta'], true);
        }

        return $post;
    }

    /**
     * Get posts by user (weblog entries)
     */
    public static function get_user_posts($user_id, $args = array()) {
        $table = 'simuze_user_activity';

        $defaults = array(
            'per_page' => 10,
            'page' => 1,
            'viewer_id' => null, // For visibility filtering
        );

        $args = array_merge($defaults, $args);

        $where = "user_id = ? AND action = 'post' AND deleted_at IS NULL";
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
        $params[] = $args['per_page'];
        $params[] = $offset;

        $posts = md_db_query(
            "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?",
            $params
        );

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
        $table = 'simuze_group_activity';

        $defaults = array(
            'per_page' => 10,
            'page' => 1,
        );

        $args = array_merge($defaults, $args);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $posts = md_db_query(
            "SELECT * FROM $table WHERE group_id = ? AND action = 'post' AND deleted_at IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ?",
            array($group_id, $args['per_page'], $offset)
        );

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
        $table = 'simuze_user_activity';

        $data = array(
            'user_id' => $user_id,
            'action' => $action,
            'content' => $content,
            'component' => $component,
            'item_id' => $item_id,
            'meta' => is_array($meta) ? json_encode($meta) : $meta,
            'created_at' => date('Y-m-d H:i:s'),
        );

        return md_db_insert($table, $data);
    }

    /**
     * Get user's own activity
     */
    public static function get_user_activity($user_id, $limit = 20) {
        $table = 'simuze_user_activity';

        $activity = md_db_query(
            "SELECT * FROM $table WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            array($user_id, $limit)
        );

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
        $table = 'simuze_group_activity';

        $data = array(
            'group_id' => $group_id,
            'user_id' => $user_id,
            'action' => $action,
            'content' => $content,
            'component' => $component,
            'item_id' => $item_id,
            'meta' => is_array($meta) ? json_encode($meta) : $meta,
            'created_at' => date('Y-m-d H:i:s'),
        );

        return md_db_insert($table, $data);
    }

    /**
     * Get user activity feed (aggregated)
     */
    public static function get_user_feed($user_id, $args = array()) {
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'include_own' => true,
            'include_friends' => true,
            'include_follows' => true,
            'include_groups' => true,
        );

        $args = array_merge($defaults, $args);

        $user_table = 'simuze_user_activity';
        $group_table = 'simuze_group_activity';
        $follows_table = 'simuze_follows';
        $members_table = 'simuze_group_members';

        $where_parts = array();
        $where_values = array();

        // Own activity
        if ($args['include_own']) {
            $where_parts[] = "ua.user_id = ?";
            $where_values[] = $user_id;
        }

        // Friends activity
        if ($args['include_friends'] && class_exists('Simuze_Friend')) {
            $friends = Simuze_Friend::get_friends($user_id);
            if (!empty($friends)) {
                $friend_ids = array_column($friends, 'id');
                $placeholders = implode(',', array_fill(0, count($friend_ids), '?'));
                $where_parts[] = "ua.user_id IN ($placeholders)";
                $where_values = array_merge($where_values, $friend_ids);
            }
        }

        // Followed users activity
        $followed_users = array();
        if ($args['include_follows']) {
            $followed_users = md_db_query(
                "SELECT followed_id FROM $follows_table WHERE follower_id = ? AND follow_type = 'user'",
                array($user_id)
            );
            $followed_users = array_column($followed_users, 'followed_id');
            if (!empty($followed_users)) {
                $placeholders = implode(',', array_fill(0, count($followed_users), '?'));
                $where_parts[] = "ua.user_id IN ($placeholders)";
                $where_values = array_merge($where_values, $followed_users);
            }
        }

        // Groups user is member of
        $group_ids = array();
        if ($args['include_groups']) {
            $group_rows = md_db_query(
                "SELECT group_id FROM $members_table WHERE user_id = ? AND status = 'active'",
                array($user_id)
            );
            $group_ids = array_column($group_rows, 'group_id');
            if (!empty($group_ids)) {
                $placeholders = implode(',', array_fill(0, count($group_ids), '?'));
                $where_parts[] = "ga.group_id IN ($placeholders)";
                $where_values = array_merge($where_values, $group_ids);
            }
        }

        if (empty($where_parts)) {
            return array();
        }

        $where_clause = '(' . implode(' OR ', $where_parts) . ')';

        $group_placeholders = implode(',', array_fill(0, count($group_ids ?: array(0)), '?'));

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
            WHERE ga.group_id IN ($group_placeholders)

            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ";

        $final_values = array_merge($where_values, $group_ids ?: array(0), array($args['per_page'], ($args['page'] - 1) * $args['per_page']));

        $activity = md_db_query($query, $final_values);

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
        $table = 'simuze_group_activity';

        $defaults = array(
            'per_page' => 20,
            'page' => 1,
        );

        $args = array_merge($defaults, $args);

        $activity = md_db_query(
            "SELECT * FROM $table WHERE group_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
            array($group_id, $args['per_page'], ($args['page'] - 1) * $args['per_page'])
        );

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

