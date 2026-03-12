<?php
/**
 * Search functionality
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Search {

    public static function search_users($query, $args = array()) {
        $defaults = ['per_page' => 20, 'page' => 1];
        $args     = array_merge($defaults, $args);

        $like        = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
        $prefix_like = str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
        $offset      = ($args['page'] - 1) * $args['per_page'];

        return md_db_query(
            "SELECT * FROM simuze_users
             WHERE (username LIKE ? OR display_name LIKE ? OR email LIKE ?)
             AND status = 'active'
             AND deleted_at IS NULL
             ORDER BY
                 CASE
                     WHEN username LIKE ? THEN 1
                     WHEN display_name LIKE ? THEN 2
                     ELSE 3
                 END,
                 display_name ASC
             LIMIT {$args['per_page']} OFFSET {$offset}",
            [$like, $like, $like, $prefix_like, $prefix_like]
        );
    }

    public static function search_groups($query, $args = array()) {
        $defaults = ['per_page' => 20, 'page' => 1, 'group_type' => null];
        $args     = array_merge($defaults, $args);

        $like         = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
        $where        = "name LIKE ? AND status = 'public' AND deleted_at IS NULL";
        $where_values = [$like];

        if ($args['group_type']) {
            $where         .= ' AND group_type_id = (SELECT id FROM simuze_group_types WHERE slug = ?)';
            $where_values[] = $args['group_type'];
        }

        $offset = ($args['page'] - 1) * $args['per_page'];

        return md_db_query(
            "SELECT * FROM simuze_groups WHERE {$where} ORDER BY name ASC LIMIT {$args['per_page']} OFFSET {$offset}",
            $where_values
        );
    }

    public static function search_music($query, $args = array()) {
        $defaults = ['per_page' => 20, 'page' => 1, 'type' => null];
        $args     = array_merge($defaults, $args);

        $like    = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
        $results = [];

        if (!$args['type'] || $args['type'] === 'track') {
            $tracks = md_db_query(
                "SELECT mu.*, ma.album_name, ma.group_id
                 FROM md_music_uploads mu
                 LEFT JOIN md_music_albums ma ON mu.album_id = ma.id
                 WHERE (mu.song_name LIKE ? OR mu.title LIKE ?)
                 AND mu.status = 'ready'
                 ORDER BY mu.upload_date DESC
                 LIMIT {$args['per_page']}",
                [$like, $like]
            );

            foreach ($tracks as $track) {
                $results[] = [
                    'type'       => 'track',
                    'id'         => $track['id'],
                    'title'      => $track['song_name'] ?: $track['title'],
                    'group_id'   => $track['group_id'],
                    'album_id'   => $track['album_id'],
                    'album_name' => $track['album_name'],
                ];
            }
        }

        if (!$args['type'] || $args['type'] === 'album') {
            $albums = md_db_query(
                "SELECT * FROM md_music_albums
                 WHERE album_name LIKE ?
                 AND status = 'published'
                 ORDER BY release_date DESC
                 LIMIT {$args['per_page']}",
                [$like]
            );

            foreach ($albums as $album) {
                $results[] = [
                    'type'     => 'album',
                    'id'       => $album['id'],
                    'title'    => $album['album_name'],
                    'group_id' => $album['group_id'],
                ];
            }
        }

        return $results;
    }

    public static function global_search($query, $args = array()) {
        $defaults = ['per_page' => 10, 'page' => 1, 'types' => ['users', 'groups', 'music']];
        $args     = array_merge($defaults, $args);

        $results = ['users' => [], 'groups' => [], 'music' => []];

        if (in_array('users', $args['types'])) {
            $results['users'] = self::search_users($query, ['per_page' => $args['per_page'], 'page' => $args['page']]);
        }

        if (in_array('groups', $args['types'])) {
            $results['groups'] = self::search_groups($query, ['per_page' => $args['per_page'], 'page' => $args['page']]);
        }

        if (in_array('music', $args['types'])) {
            $results['music'] = self::search_music($query, ['per_page' => $args['per_page'], 'page' => $args['page']]);
        }

        return $results;
    }
}
