<?php
/**
 * Commerce REST API endpoints
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Commerce_API {

    private $namespace = 'simuze/v1';

    public function __construct() {
        if (function_exists('add_action')) {
            add_action('rest_api_init', [$this, 'register_routes']);
        } else {
            md_hook_action('rest_api_init', [$this, 'register_routes']);
        }
    }

    public function register_routes() {
        if (!function_exists('register_rest_route')) return;

        register_rest_route($this->namespace, '/products/songs', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_songs'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->namespace, '/products/songs/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_song'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->namespace, '/products/albums', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_albums'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->namespace, '/products/albums/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_album'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->namespace, '/payments/webhook', [
            'methods'             => 'POST',
            'callback'            => [$this, 'payment_webhook'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->namespace, '/payments/methods', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_payment_methods'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->namespace, '/downloads', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_downloads'],
            'permission_callback' => [$this, 'check_auth'],
        ]);
    }

    public function check_auth() {
        $auth = Simuze_Auth::get_instance();
        return $auth->get_current_user() !== null;
    }

    public function check_admin() {
        return isset($GLOBALS['user_role']) && $GLOBALS['user_role'] === 'admin';
    }

    private function error($code, $message, $status = 400) {
        return ['error' => $code, 'message' => $message, 'status' => $status];
    }

    private function is_error($result) {
        return is_array($result) && isset($result['error']);
    }

    private function respond($data) {
        return function_exists('rest_ensure_response') ? rest_ensure_response($data) : $data;
    }

    public function get_songs($request) {
        $args = [
            'limit'            => (int) ($request->get_param('limit') ?: 20),
            'offset'           => (int) ($request->get_param('offset') ?: 0),
            'group_id'         => $request->get_param('group_id'),
            'purchasable_only' => $request->get_param('purchasable') !== 'false',
        ];

        if ($args['group_id']) {
            $songs = Simuze_Song::get_by_group($args['group_id'], $args);
        } else {
            $where  = 'deleted_at IS NULL AND is_active = 1';
            $params = [];
            if ($args['purchasable_only']) {
                $where .= ' AND is_purchasable = 1';
            }
            $limit  = $args['limit'];
            $offset = $args['offset'];
            $results = md_db_query(
                "SELECT * FROM simuze_songs WHERE {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}",
                $params
            );
            $songs = array_map(fn($data) => new Simuze_Song($data), $results);
        }

        return $this->respond(array_map(fn($song) => $song->to_array(), $songs));
    }

    public function get_song($request) {
        $song = Simuze_Song::get($request->get_param('id'));

        if (!$song) {
            return $this->error('not_found', 'Song not found', 404);
        }

        $song_data = $song->to_array();

        // Get MP3 URL
        $mp3_url   = null;
        $file_path = $song->get_file_path();
        if ($file_path && file_exists($file_path)) {
            $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            if ($extension === 'mp3') {
                $mp3_url = $song->get_file_url();
            } elseif (class_exists('Simuze_Audio_Converter')) {
                $converter = Simuze_Audio_Converter::get_instance();
                $mp3_path  = $converter->get_mp3_version($file_path, 'high');
                if ($mp3_path && file_exists($mp3_path)) {
                    $upload = md_upload_dir();
                    $mp3_url = str_replace($upload['basedir'], $upload['baseurl'], $mp3_path);
                } else {
                    $mp3_url = $song->get_file_url();
                }
            } else {
                $mp3_url = $song->get_file_url();
            }
        } else {
            $mp3_url = $song_data['file_url'] ?? null;
        }

        if ($mp3_url) {
            $song_data['mp3_url'] = $mp3_url;
            $song_data['url']     = $mp3_url;
        }

        // Get artist name from group
        $artist_name = '';
        if ($song->group_id && class_exists('Simuze_Group')) {
            $group = Simuze_Group::get($song->group_id);
            if ($group) $artist_name = $group->name;
        }

        $song_data['song_id']     = $song_data['id'];
        $song_data['artist']      = $artist_name;
        $song_data['artist_name'] = $artist_name;
        $song_data['name']        = $song_data['title'];
        $song_data['cover']       = $song_data['artwork_url'] ?? null;
        $song_data['artwork']     = $song_data['artwork_url'] ?? null;

        return $this->respond($song_data);
    }

    public function get_albums($request) {
        $args = [
            'limit'    => (int) ($request->get_param('limit') ?: 20),
            'offset'   => (int) ($request->get_param('offset') ?: 0),
            'group_id' => $request->get_param('group_id'),
        ];

        if ($args['group_id']) {
            $albums = Simuze_Album::get_by_group($args['group_id'], $args);
        } else {
            $limit  = $args['limit'];
            $offset = $args['offset'];
            $results = md_db_query(
                "SELECT * FROM simuze_albums WHERE deleted_at IS NULL AND is_active = 1 ORDER BY release_date DESC LIMIT {$limit} OFFSET {$offset}"
            );
            $albums = array_map(fn($data) => new Simuze_Album($data), $results);
        }

        return $this->respond(array_map(fn($album) => $album->to_array(), $albums));
    }

    public function get_album($request) {
        $album = Simuze_Album::get($request->get_param('id'));

        if (!$album) {
            return $this->error('not_found', 'Album not found', 404);
        }

        return $this->respond($album->to_array());
    }

    public function payment_webhook($request) {
        $payments  = Simuze_Simezu_Payments::get_instance();
        $payload   = $request->get_body();
        $signature = $request->get_header('X-Simezu-Signature');
        $result    = $payments->process_webhook($payload, $signature);

        if ($this->is_error($result)) return $result;

        return $this->respond($result);
    }

    public function get_payment_methods($request) {
        $payments = Simuze_Simezu_Payments::get_instance();
        return $this->respond($payments->get_payment_methods());
    }

    public function get_downloads($request) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        $limit = (int) ($request->get_param('limit') ?: 50);
        $grants = md_db_query(
            "SELECT id, user_id, product_type, product_id, simezu_reference, created_at
             FROM simuze_download_grants
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT " . max(1, min(100, $limit)),
            [$user->id]
        );

        return $this->respond(array_map(function ($row) {
            return [
                'id'               => (int) $row['id'],
                'product_type'     => $row['product_type'],
                'product_id'       => (int) $row['product_id'],
                'simezu_reference' => $row['simezu_reference'],
                'created_at'       => $row['created_at'],
            ];
        }, $grants));
    }
}
