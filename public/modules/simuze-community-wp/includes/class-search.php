<?php
/**
 * Search functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Search {
    
    /**
     * Search users
     */
    public static function search_users($query, $args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
        );
        
        $args = array_merge($defaults, $args);
        
        $search_term = '%' . $wpdb->esc_like($query) . '%';
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE (username LIKE %s OR display_name LIKE %s OR email LIKE %s)
            AND status = 'active'
            AND deleted_at IS NULL
            ORDER BY 
                CASE 
                    WHEN username LIKE %s THEN 1
                    WHEN display_name LIKE %s THEN 2
                    ELSE 3
                END,
                display_name ASC
            LIMIT %d OFFSET %d",
            $search_term,
            $search_term,
            $search_term,
            $wpdb->esc_like($query) . '%',
            $wpdb->esc_like($query) . '%',
            $args['per_page'],
            $offset
        ), ARRAY_A);
        
        return $results;
    }
    
    /**
     * Search groups
     */
    public static function search_groups($query, $args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'group_type' => null,
        );
        
        $args = array_merge($defaults, $args);
        
        $search_term = '%' . $wpdb->esc_like($query) . '%';
        
        $where = "name LIKE %s AND status = 'public' AND deleted_at IS NULL";
        $where_values = array($search_term);
        
        if ($args['group_type']) {
            $where .= " AND group_type_id = (SELECT id FROM {$wpdb->prefix}simuze_group_types WHERE slug = %s)";
            $where_values[] = $args['group_type'];
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $where_values[] = $args['per_page'];
        $where_values[] = $offset;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE $where
            ORDER BY name ASC
            LIMIT %d OFFSET %d",
            $where_values
        ), ARRAY_A);
        
        return $results;
    }
    
    /**
     * Search tracks/albums
     */
    public static function search_music($query, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'type' => null, // 'track', 'album', or null for both
        );
        
        $args = array_merge($defaults, $args);
        
        $search_term = '%' . $wpdb->esc_like($query) . '%';
        $results = array();
        
        // Search tracks
        if (!$args['type'] || $args['type'] === 'track') {
            $tracks_table = $wpdb->prefix . 'music_uploads';
            $tracks = $wpdb->get_results($wpdb->prepare(
                "SELECT mu.*, ma.album_name, ma.group_id
                FROM $tracks_table mu
                LEFT JOIN {$wpdb->prefix}music_albums ma ON mu.album_id = ma.id
                WHERE (mu.song_name LIKE %s OR mu.title LIKE %s)
                AND mu.status = 'ready'
                ORDER BY mu.upload_date DESC
                LIMIT %d",
                $search_term,
                $search_term,
                $args['per_page']
            ), ARRAY_A);
            
            foreach ($tracks as $track) {
                $results[] = array(
                    'type' => 'track',
                    'id' => $track->id,
                    'title' => $track->song_name ?: $track->title,
                    'group_id' => $track->group_id,
                    'album_id' => $track->album_id,
                    'album_name' => $track->album_name,
                );
            }
        }
        
        // Search albums
        if (!$args['type'] || $args['type'] === 'album') {
            $albums_table = $wpdb->prefix . 'music_albums';
            $albums = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $albums_table
                WHERE album_name LIKE %s
                AND status = 'published'
                ORDER BY release_date DESC
                LIMIT %d",
                $search_term,
                $args['per_page']
            ), ARRAY_A);
            
            foreach ($albums as $album) {
                $results[] = array(
                    'type' => 'album',
                    'id' => $album->id,
                    'title' => $album->album_name,
                    'group_id' => $album->group_id,
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Global search (all content types)
     */
    public static function global_search($query, $args = array()) {
        $defaults = array(
            'per_page' => 10,
            'page' => 1,
            'types' => array('users', 'groups', 'music'), // What to search
        );
        
        $args = array_merge($defaults, $args);
        
        $results = array(
            'users' => array(),
            'groups' => array(),
            'music' => array(),
        );
        
        if (in_array('users', $args['types'])) {
            $results['users'] = self::search_users($query, array(
                'per_page' => $args['per_page'],
                'page' => $args['page'],
            ));
        }
        
        if (in_array('groups', $args['types'])) {
            $results['groups'] = self::search_groups($query, array(
                'per_page' => $args['per_page'],
                'page' => $args['page'],
            ));
        }
        
        if (in_array('music', $args['types'])) {
            $results['music'] = self::search_music($query, array(
                'per_page' => $args['per_page'],
                'page' => $args['page'],
            ));
        }
        
        return $results;
    }
}








