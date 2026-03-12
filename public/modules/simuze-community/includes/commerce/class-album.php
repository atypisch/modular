<?php
/**
 * Album class for Simuze Commerce
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Album {

    public $id;
    public $group_id;
    public $title;
    public $slug;
    public $description;
    public $price;
    public $download_price_min;
    public $download_price_default;
    public $cover_image;
    public $release_date;
    public $is_active;
    public $is_purchasable;
    public $download_count;
    public $created_at;
    public $updated_at;

    public function __construct($data = array()) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get album by ID
     */
    public static function get($id) {
        $table = 'md_music_albums';

        $data = md_db_row(
            "SELECT * FROM $table WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );

        if ($data) {
            return new self($data);
        }

        return null;
    }

    /**
     * Get album by slug
     */
    public static function get_by_slug($slug) {
        $table = 'md_music_albums';

        $data = md_db_row(
            "SELECT * FROM $table WHERE slug = ? AND deleted_at IS NULL",
            [$slug]
        );

        if ($data) {
            return new self($data);
        }

        return null;
    }

    /**
     * Create new album
     */
    public static function create($data) {
        $table = 'md_music_albums';

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['title']);

            // Ensure unique slug
            $original_slug = $data['slug'];
            $counter = 1;
            while (self::get_by_slug($data['slug'])) {
                $data['slug'] = $original_slug . '-' . $counter;
                $counter++;
            }
        }

        $defaults = array(
            'is_active' => 1,
            'is_purchasable' => 1,
            'download_count' => 0,
        );

        $data = array_merge($defaults, $data);

        $insert_id = md_db_insert($table, $data);

        if ($insert_id) {
            return self::get($insert_id);
        }

        return null;
    }

    /**
     * Update album
     */
    public function update($data) {
        $table = 'md_music_albums';

        $result = md_db_update(
            $table,
            $data,
            array('id' => $this->id)
        );

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

    /**
     * Soft delete album
     */
    public function delete() {
        $table = 'md_music_albums';

        return md_db_update(
            $table,
            array('deleted_at' => date('Y-m-d H:i:s')),
            array('id' => $this->id)
        );
    }

    /**
     * Get albums by group
     */
    public static function get_by_group($group_id, $args = array()) {
        $table = 'md_music_albums';

        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'active_only' => true,
            'orderby' => 'release_date',
            'order' => 'DESC',
        );

        $args = array_merge($defaults, $args);

        $where = "group_id = ? AND deleted_at IS NULL";
        $params = array($group_id);

        if ($args['active_only']) {
            $where .= " AND is_active = 1";
        }

        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);

        $albums = md_db_query(
            "SELECT * FROM $table WHERE $where ORDER BY $orderby LIMIT ? OFFSET ?",
            array_merge($params, array($args['limit'], $args['offset']))
        );

        return array_map(function($data) {
            return new self($data);
        }, $albums ?: []);
    }

    /**
     * Get songs in this album
     */
    public function get_songs() {
        $album_songs_table = 'simuze_album_songs';
        $songs_table = 'simuze_songs';

        $songs = md_db_query(
            "SELECT s.*, aso.track_number FROM $songs_table s
             INNER JOIN $album_songs_table aso ON s.id = aso.song_id
             WHERE aso.album_id = ? AND s.deleted_at IS NULL
             ORDER BY aso.track_number ASC",
            [$this->id]
        );

        return array_map(function($data) {
            return new Simuze_Song($data);
        }, $songs ?: []);
    }

    /**
     * Add song to album
     */
    public function add_song($song_id, $track_number = null) {
        $table = 'simuze_album_songs';

        // Get next track number if not provided
        if ($track_number === null) {
            $max = md_db_var(
                "SELECT MAX(track_number) FROM $table WHERE album_id = ?",
                [$this->id]
            );
            $track_number = ($max ? $max + 1 : 1);
        }

        return md_db_exec(
            "REPLACE INTO $table (album_id, song_id, track_number) VALUES (?, ?, ?)",
            [$this->id, $song_id, $track_number]
        );
    }

    /**
     * Remove song from album
     */
    public function remove_song($song_id) {
        $table = 'simuze_album_songs';

        return md_db_delete($table, array(
            'album_id' => $this->id,
            'song_id' => $song_id,
        ));
    }

    /**
     * Reorder songs in album
     */
    public function reorder_songs($song_ids) {
        $table = 'simuze_album_songs';

        foreach ($song_ids as $position => $song_id) {
            md_db_update(
                $table,
                array('track_number' => $position + 1),
                array('album_id' => $this->id, 'song_id' => $song_id)
            );
        }

        return true;
    }

    /**
     * Get total songs price (sum of individual song prices)
     */
    public function get_songs_total_price() {
        $songs = $this->get_songs();
        $total = 0;

        foreach ($songs as $song) {
            $pricing = $song->get_final_price();
            $total += $pricing['final_price'];
        }

        return $total;
    }

    /**
     * Get final price (no local discounts; pricing via SIMEZU)
     */
    public function get_final_price($buyer_id = null) {
        if ($this->price !== null && $this->price > 0) {
            $base_price = floatval($this->price);
        } else {
            $base_price = $this->get_songs_total_price();
        }
        if ($base_price <= 0) {
            return array(
                'base_price' => 0,
                'final_price' => 0,
                'discount_amount' => 0,
                'discount_reasons' => array('Free download'),
                'songs_included' => count($this->get_songs()),
            );
        }
        return array(
            'base_price' => $base_price,
            'final_price' => round($base_price, 2),
            'discount_amount' => 0,
            'discount_reasons' => array(),
            'songs_included' => count($this->get_songs()),
        );
    }

    /**
     * Get group (seller)
     */
    public function get_group() {
        return Simuze_Group::get($this->group_id);
    }

    /**
     * Increment download count
     */
    public function increment_downloads() {
        $table = 'md_music_albums';

        md_db_exec(
            "UPDATE $table SET download_count = download_count + 1 WHERE id = ?",
            [$this->id]
        );

        $this->download_count++;
    }

    /**
     * Get URL for album page
     */
    public function get_url() {
        $group = $this->get_group();
        if ($group) {
            return md_home_url('/album/' . $this->slug . '/');
        }
        return md_home_url('/album/' . $this->slug . '/');
    }

    /**
     * Convert to array
     */
    public function to_array() {
        $pricing = $this->get_final_price();
        $songs = $this->get_songs();

        return array(
            'id' => $this->id,
            'group_id' => $this->group_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'final_price' => $pricing['final_price'],
            'discount_amount' => $pricing['discount_amount'],
            'cover_image' => $this->cover_image,
            'release_date' => $this->release_date,
            'is_active' => $this->is_active,
            'is_purchasable' => $this->is_purchasable,
            'download_count' => $this->download_count,
            'songs_count' => count($songs),
            'songs' => array_map(function($song) { return $song->to_array(); }, $songs),
            'url' => $this->get_url(),
            'created_at' => $this->created_at,
        );
    }
}
