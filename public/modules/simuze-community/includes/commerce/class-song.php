<?php
/**
 * Song (Product) class for Simuze Commerce
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Song {

    public $id;
    public $group_id;
    public $title;
    public $slug;
    public $description;
    public $price;
    public $download_price_min;
    public $download_price_default;
    public $file_path;
    public $file_size;
    public $duration;
    public $artwork_url;
    public $genre;
    public $license;
    public $is_active;
    public $is_purchasable;
    public $download_count;
    public $created_at;
    public $updated_at;

    public function __construct($data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public static function get($id) {
        $data = md_db_row("SELECT * FROM simuze_songs WHERE id = ? AND deleted_at IS NULL", [$id]);
        return $data ? new self($data) : null;
    }

    public static function get_by_slug($slug) {
        $data = md_db_row("SELECT * FROM simuze_songs WHERE slug = ? AND deleted_at IS NULL", [$slug]);
        return $data ? new self($data) : null;
    }

    public static function create($data) {
        if (empty($data['slug'])) {
            $data['slug'] = preg_replace('/[^a-z0-9-]+/', '-', strtolower($data['title']));
            $data['slug'] = trim($data['slug'], '-');

            $original_slug = $data['slug'];
            $counter       = 1;
            while (self::get_by_slug($data['slug'])) {
                $data['slug'] = $original_slug . '-' . $counter;
                $counter++;
            }
        }

        $data = array_merge(['is_active' => 1, 'is_purchasable' => 1, 'license' => 'cc-by', 'download_count' => 0], $data);

        $new_id = md_db_insert('simuze_songs', $data);
        return $new_id ? self::get($new_id) : null;
    }

    public function update($data) {
        $result = md_db_update('simuze_songs', $data, ['id' => $this->id]);

        if ($result !== false) {
            $updated = self::get($this->id);
            if ($updated) {
                foreach (get_object_vars($updated) as $key => $value) {
                    $this->$key = $value;
                }
            }
            return true;
        }

        return false;
    }

    public function delete() {
        return md_db_update('simuze_songs', ['deleted_at' => date('Y-m-d H:i:s')], ['id' => $this->id]);
    }

    public static function get_by_group($group_id, $args = []) {
        $defaults = ['limit' => 50, 'offset' => 0, 'active_only' => true, 'purchasable_only' => false, 'orderby' => 'created_at', 'order' => 'DESC'];
        $args     = array_merge($defaults, $args);

        $allowed_orderby = ['created_at', 'title', 'price', 'download_count'];
        $orderby         = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order           = $args['order'] === 'ASC' ? 'ASC' : 'DESC';

        $where  = 'group_id = ? AND deleted_at IS NULL';
        $params = [$group_id];

        if ($args['active_only']) {
            $where .= ' AND is_active = 1';
        }
        if ($args['purchasable_only']) {
            $where .= ' AND is_purchasable = 1';
        }

        $songs = md_db_query(
            "SELECT * FROM simuze_songs WHERE {$where} ORDER BY {$orderby} {$order} LIMIT {$args['limit']} OFFSET {$args['offset']}",
            $params
        );

        return array_map(fn($data) => new self($data), $songs);
    }

    public function get_final_price($buyer_id = null) {
        $base_price = floatval($this->price);
        if ($base_price <= 0) {
            return ['base_price' => 0, 'final_price' => 0, 'discount_amount' => 0, 'discount_reasons' => ['Free download']];
        }
        return [
            'base_price'       => $base_price,
            'final_price'      => round($base_price, 2),
            'discount_amount'  => 0,
            'discount_reasons' => [],
        ];
    }

    public function get_file_url() {
        if (empty($this->file_path)) return null;
        $upload = md_upload_dir();
        return rtrim($upload['baseurl'], '/') . '/' . ltrim($this->file_path, '/');
    }

    public function get_file_path() {
        if (empty($this->file_path)) return null;
        $upload = md_upload_dir();
        return rtrim($upload['basedir'], '/') . '/' . ltrim($this->file_path, '/');
    }

    public function get_group() {
        return !empty($this->group_id) ? Simuze_Group::get($this->group_id) : null;
    }

    public function get_albums() {
        $albums = md_db_query(
            "SELECT a.* FROM simuze_albums a
             INNER JOIN simuze_album_songs aso ON a.id = aso.album_id
             WHERE aso.song_id = ? AND a.deleted_at IS NULL",
            [$this->id]
        );
        return array_map(fn($data) => new Simuze_Album($data), $albums);
    }

    public function increment_downloads() {
        md_db_exec("UPDATE simuze_songs SET download_count = download_count + 1 WHERE id = ?", [$this->id]);
        $this->download_count++;
    }

    public function get_url() {
        return md_home_url('/track/' . $this->slug . '/');
    }

    public function to_array() {
        $pricing = $this->get_final_price();

        return [
            'id'             => $this->id,
            'group_id'       => $this->group_id,
            'title'          => $this->title,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'price'          => $this->price,
            'final_price'    => $pricing['final_price'],
            'discount_amount' => $pricing['discount_amount'],
            'file_url'       => $this->get_file_url(),
            'artwork_url'    => $this->artwork_url,
            'genre'          => $this->genre,
            'license'        => $this->license,
            'duration'       => $this->duration,
            'is_active'      => $this->is_active,
            'is_purchasable' => $this->is_purchasable,
            'download_count' => $this->download_count,
            'url'            => $this->get_url(),
            'created_at'     => $this->created_at,
        ];
    }
}
