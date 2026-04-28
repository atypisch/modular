<?php
/**
 * Song (Product) class for Simuze Commerce
 */

if (!defined('ABSPATH')) {
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
    
    public function __construct($data = array()) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Get song by ID
     */
    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND deleted_at IS NULL",
            $id
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Get song by slug
     */
    public static function get_by_slug($slug) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE slug = %s AND deleted_at IS NULL",
            $slug
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Create new song
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
        
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
            'license' => 'cc-by',
            'download_count' => 0,
        );
        
        $data = array_merge($defaults, $data);
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return self::get($wpdb->insert_id);
        }
        
        return null;
    }
    
    /**
     * Update song
     */
    public function update($data) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
        
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $this->id)
        );
        
        if ($result !== false) {
            // Refresh data
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
     * Soft delete song
     */
    public function delete() {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
        
        return $wpdb->update(
            $table,
            array('deleted_at' => current_time('mysql')),
            array('id' => $this->id)
        );
    }
    
    /**
     * Get songs by group
     */
    public static function get_by_group($group_id, $args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
        
        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'active_only' => true,
            'purchasable_only' => false,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );
        
        $args = array_merge($defaults, $args);
        
        $where = "group_id = %d AND deleted_at IS NULL";
        $params = array($group_id);
        
        if ($args['active_only']) {
            $where .= " AND is_active = 1";
        }
        
        if ($args['purchasable_only']) {
            $where .= " AND is_purchasable = 1";
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $songs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY $orderby LIMIT %d OFFSET %d",
            array_merge($params, array($args['limit'], $args['offset']))
        ), ARRAY_A);
        
        return array_map(function($data) {
            return new self($data);
        }, $songs);
    }
    
    /**
     * Get final price with discounts applied
     */
    public function get_final_price($buyer_id = null) {
        $base_price = floatval($this->price);
        
        if ($base_price <= 0) {
            return array(
                'base_price' => 0,
                'final_price' => 0,
                'discount_amount' => 0,
                'discount_reasons' => array('Free download'),
            );
        }
        
        $discount_amount = 0;
        $discount_reasons = array();
        
        // Get group discount
        $group_discount = Simuze_Discount::get_active_for_group($this->group_id, 'song');
        if ($group_discount) {
            $group_discount_amount = $group_discount->calculate($base_price);
            if ($group_discount_amount > 0) {
                $discount_amount += $group_discount_amount;
                $discount_reasons[] = $group_discount->name . ' (-' . $group_discount->get_display_value() . ')';
            }
        }
        
        // Get global discount
        $global_discount = Simuze_Discount::get_active_global('song');
        if ($global_discount) {
            $remaining_price = $base_price - $discount_amount;
            $global_discount_amount = $global_discount->calculate($remaining_price);
            if ($global_discount_amount > 0) {
                $discount_amount += $global_discount_amount;
                $discount_reasons[] = $global_discount->name . ' (-' . $global_discount->get_display_value() . ')';
            }
        }
        
        $final_price = max(0, $base_price - $discount_amount);
        
        return array(
            'base_price' => $base_price,
            'final_price' => round($final_price, 2),
            'discount_amount' => round($discount_amount, 2),
            'discount_reasons' => $discount_reasons,
        );
    }
    
    /**
     * Get file URL for download
     */
    public function get_file_url() {
        if (empty($this->file_path)) {
            return null;
        }
        
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/' . ltrim($this->file_path, '/');
    }
    
    /**
     * Get file path for download
     */
    public function get_file_path() {
        if (empty($this->file_path)) {
            return null;
        }
        
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/' . ltrim($this->file_path, '/');
    }
    
    /**
     * Get group (seller)
     */
    public function get_group() {
        if (empty($this->group_id)) {
            return null;
        }
        return Simuze_Group::get($this->group_id);
    }
    
    /**
     * Get albums this song belongs to
     */
    public function get_albums() {
        global $wpdb;
        $album_songs_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'album_songs';
        $albums_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'albums';
        
        $albums = $wpdb->get_results($wpdb->prepare(
            "SELECT a.* FROM $albums_table a 
             INNER JOIN $album_songs_table aso ON a.id = aso.album_id 
             WHERE aso.song_id = %d AND a.deleted_at IS NULL",
            $this->id
        ), ARRAY_A);
        
        return array_map(function($data) {
            return new Simuze_Album($data);
        }, $albums);
    }
    
    /**
     * Increment download count
     */
    public function increment_downloads() {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET download_count = download_count + 1 WHERE id = %d",
            $this->id
        ));
        
        $this->download_count++;
    }
    
    /**
     * Get URL for song page
     */
    public function get_url() {
        $group = $this->get_group();
        if ($group) {
            return home_url('/track/' . $this->slug . '/');
        }
        return home_url('/track/' . $this->slug . '/');
    }
    
    /**
     * Convert to array
     */
    public function to_array() {
        $pricing = $this->get_final_price();
        
        return array(
            'id' => $this->id,
            'group_id' => $this->group_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'final_price' => $pricing['final_price'],
            'discount_amount' => $pricing['discount_amount'],
            'file_url' => $this->get_file_url(),
            'artwork_url' => $this->artwork_url,
            'genre' => $this->genre,
            'license' => $this->license,
            'duration' => $this->duration,
            'is_active' => $this->is_active,
            'is_purchasable' => $this->is_purchasable,
            'download_count' => $this->download_count,
            'url' => $this->get_url(),
            'created_at' => $this->created_at,
        );
    }
}











