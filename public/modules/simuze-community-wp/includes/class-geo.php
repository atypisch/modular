<?php
/**
 * GEO location helper with Geonames API integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Geo {
    
    /**
     * Geonames API username
     */
    private static $geonames_username = 'atypisch';
    
    /**
     * Search cities using Geonames API
     * 
     * @param string $query Search query
     * @param int $max_results Maximum results to return
     * @return array Array of city results
     */
    public static function search_cities($query, $max_results = 10) {
        if (strlen($query) < 2) {
            return array();
        }
        
        $cache_key = 'simuze_geo_' . md5($query);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // First, check existing groups with type 'city'
        $existing_cities = self::search_existing_city_groups($query);
        
        // Then search Geonames API
        $geonames_cities = self::search_geonames($query, $max_results);
        
        // Merge results, prioritizing existing groups
        $results = array_merge($existing_cities, $geonames_cities);
        
        // Remove duplicates based on name + country
        $unique = array();
        $seen = array();
        foreach ($results as $city) {
            $key = strtolower($city['name'] . '-' . ($city['countryName'] ?? ''));
            if (!isset($seen[$key])) {
                $unique[] = $city;
                $seen[$key] = true;
            }
        }
        
        $results = array_slice($unique, 0, $max_results);
        
        // Cache for 1 hour
        set_transient($cache_key, $results, HOUR_IN_SECONDS);
        
        return $results;
    }
    
    /**
     * Search existing city groups in database
     */
    private static function search_existing_city_groups($query) {
        global $wpdb;
        $groups_table = $wpdb->prefix . (defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_') . 'groups';
        $types_table  = $wpdb->prefix . (defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_') . 'group_types';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT g.name, g.city, g.province, g.country, g.geo_id as geonameId, g.lat, g.lng
            FROM $groups_table g
            LEFT JOIN $types_table t ON g.group_type_id = t.id
            WHERE t.slug = 'city' 
            AND g.name LIKE %s
            AND g.deleted_at IS NULL
            LIMIT 5
        ", '%' . $wpdb->esc_like($query) . '%'), ARRAY_A);
        
        $cities = array();
        foreach ($results as $row) {
            $cities[] = array(
                'name' => $row['name'],
                'adminName1' => $row['province'],
                'countryName' => $row['country'],
                'geonameId' => $row['geonameId'],
                'lat' => $row['lat'],
                'lng' => $row['lng'],
                'existing' => true
            );
        }
        
        return $cities;
    }
    
    /**
     * Search Geonames API
     */
    private static function search_geonames($query, $max_results = 10) {
        $url = 'http://api.geonames.org/searchJSON?' . http_build_query(array(
            'name_startsWith' => $query,
            'featureClass' => 'P', // Populated places
            'maxRows' => $max_results,
            'lang' => 'en',
            'username' => self::$geonames_username
        ));
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            error_log('Geonames API error: ' . $response->get_error_message());
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['geonames'])) {
            return array();
        }
        
        $cities = array();
        foreach ($data['geonames'] as $place) {
            $cities[] = array(
                'name' => $place['name'],
                'adminName1' => $place['adminName1'] ?? '',
                'countryName' => $place['countryName'] ?? '',
                'geonameId' => $place['geonameId'] ?? '',
                'lat' => $place['lat'] ?? '',
                'lng' => $place['lng'] ?? '',
                'population' => $place['population'] ?? 0,
                'existing' => false
            );
        }
        
        // Sort by population (larger cities first)
        usort($cities, function($a, $b) {
            return ($b['population'] ?? 0) - ($a['population'] ?? 0);
        });
        
        return $cities;
    }
    
    /**
     * Get location data from Geonames by ID
     */
    public static function get_location_by_id($geoname_id) {
        $url = 'http://api.geonames.org/getJSON?' . http_build_query(array(
            'geonameId' => $geoname_id,
            'username' => self::$geonames_username
        ));
        
        $response = wp_remote_get($url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Auto-create groups based on location (city, province, country)
     * 
     * @param array $location_data Location data from search
     * @param int $creator_id User ID of the creator
     * @return array Array with created/existing group IDs
     */
    public static function auto_create_location_groups($location_data, $creator_id) {
        $results = array(
            'city' => null,
            'province' => null,
            'country' => null
        );

        // NOTE: We treat the coordinates coming from the city as the canonical
        // center point for all three related groups. This guarantees that
        // countries and provinces also get a lat/lng so we can render maps.
        $base_lat = isset($location_data['lat']) ? $location_data['lat'] : null;
        $base_lng = isset($location_data['lng']) ? $location_data['lng'] : null;
        
        // Get or create country group (admin by super admin)
        if (!empty($location_data['country'])) {
            $country_settings = array(
                'map_zoom' => 7, // Country-level zoom
            );
            
            $results['country'] = self::get_or_create_location_group(
                $location_data['country'],
                'country',
                array(
                    'country'  => $location_data['country'],
                    'lat'      => $base_lat,
                    'lng'      => $base_lng,
                    'settings' => $country_settings,
                ),
                1 // Super admin ID
            );
        }
        
        // Get or create province group (admin by creator)
        if (!empty($location_data['province'])) {
            $province_settings = array(
                'map_zoom' => 8, // Province-level zoom
            );
            
            $results['province'] = self::get_or_create_location_group(
                $location_data['province'],
                'province',
                array(
                    'province' => $location_data['province'],
                    'country'  => $location_data['country'],
                    'lat'      => $base_lat,
                    'lng'      => $base_lng,
                    'settings' => $province_settings,
                ),
                $creator_id
            );
        }
        
        // Get or create city group (admin by creator)
        if (!empty($location_data['city'])) {
            $city_settings = array(
                'map_zoom' => 12, // City-level zoom
            );
            
            $results['city'] = self::get_or_create_location_group(
                $location_data['city'],
                'city',
                array(
                    'city'     => $location_data['city'],
                    'province' => $location_data['province'],
                    'country'  => $location_data['country'],
                    'geo_id'   => $location_data['geo_id'] ?? null,
                    'lat'      => $base_lat,
                    'lng'      => $base_lng,
                    'settings' => $city_settings,
                ),
                $creator_id
            );
        }
        
        return $results;
    }
    
    /**
     * Get existing location group or create new one
     */
    private static function get_or_create_location_group($name, $type, $location_data, $admin_id) {
        global $wpdb;
        $groups_table = $wpdb->prefix . (defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_') . 'groups';
        $types_table  = $wpdb->prefix . (defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_') . 'group_types';
        
        // Get group type ID
        $type_id = self::get_group_type_id($type);
        if (!$type_id) {
            // Create the group type if it doesn't exist
            $type_id = self::create_group_type($type);
        }
        
        // Check if group already exists
        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $groups_table 
            WHERE name = %s 
            AND group_type_id = %d 
            AND deleted_at IS NULL
        ", $name, $type_id), ARRAY_A);
        
        if ($existing) {
            // Backfill missing lat/lng and map_zoom for existing groups when we have data
            $group = new Simuze_Group($existing);
            $update = array();
            
            // Only update lat/lng if group doesn't have them yet and we have values
            if ((empty($group->lat) || empty($group->lng)) && !empty($location_data['lat']) && !empty($location_data['lng'])) {
                $update['lat'] = (float) $location_data['lat'];
                $update['lng'] = (float) $location_data['lng'];
            }
            
            // Ensure settings map_zoom is present
            $desired_zoom = ($type === 'city') ? 12 : ($type === 'province' ? 8 : 7);
            $settings = array();
            if (isset($group->settings)) {
                if (is_array($group->settings)) {
                    $settings = $group->settings;
                } elseif (is_string($group->settings)) {
                    $decoded = json_decode($group->settings, true);
                    if (is_array($decoded)) {
                        $settings = $decoded;
                    }
                }
            }
            
            if (!isset($settings['map_zoom'])) {
                $settings['map_zoom'] = $desired_zoom;
                $update['settings'] = $settings;
            }
            
            if (!empty($update)) {
                $group->update($update);
            }
            
            return array(
                'id'      => $group->id,
                'created' => false
            );
        }
        
        // Create new group
        $group_data = array_merge(array(
            'name' => $name,
            'slug' => sanitize_title($name),
            'group_type_id' => $type_id,
            'description' => sprintf('%s - %s', ucfirst($type), $name),
            'status' => 'public',
            'creator_id' => $admin_id
        ), $location_data);
        
        $group = Simuze_Group::create($group_data);
        
        if ($group) {
            // Creator is already added as admin in Simuze_Group::create()
            return array(
                'id' => $group->id,
                'created' => true
            );
        }
        
        return null;
    }
    
    /**
     * Get group type ID by slug
     */
    public static function get_group_type_id($slug) {
        global $wpdb;
        $table = $wpdb->prefix . (defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_') . 'group_types';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE slug = %s AND deleted_at IS NULL",
            $slug
        ));
    }
    
    /**
     * Create a group type
     */
    private static function create_group_type($slug) {
        global $wpdb;
        $table = $wpdb->prefix . (defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_') . 'group_types';
        
        $wpdb->insert($table, array(
            'name' => ucfirst($slug),
            'slug' => $slug,
            'description' => ucfirst($slug) . ' group type',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));
        
        return $wpdb->insert_id;
    }
}

/**
 * One-time backfill for province locations.
 *
 * Many province groups were created before we started storing lat/lng for them.
 * This routine infers a province's center from its cities (average lat/lng) and
 * stores a sensible map_zoom so province pages can always render a map.
 */
function simuze_backfill_province_locations_if_needed() {
    if (get_option('simuze_province_geo_backfilled')) {
        return;
    }
    
    global $wpdb;
    $groups_table = $wpdb->prefix . (defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_') . 'groups';
    $types_table  = $wpdb->prefix . (defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_') . 'group_types';
    
    // Get type IDs
    $province_type_id = $wpdb->get_var("SELECT id FROM $types_table WHERE slug = 'province' AND deleted_at IS NULL");
    $city_type_id     = $wpdb->get_var("SELECT id FROM $types_table WHERE slug = 'city' AND deleted_at IS NULL");
    
    if (!$province_type_id || !$city_type_id) {
        update_option('simuze_province_geo_backfilled', 1);
        return;
    }
    
    // Provinces without lat/lng
    $provinces = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, province, country, lat, lng, settings
         FROM $groups_table
         WHERE group_type_id = %d
           AND deleted_at IS NULL
           AND (lat IS NULL OR lat = 0 OR lng IS NULL OR lng = 0)",
        $province_type_id
    ), ARRAY_A);
    
    foreach ($provinces as $row) {
        $province_name = $row['province'] ?: $row['name'];
        $country_name  = $row['country'];
        
        // Compute average lat/lng from all cities in this province
        $city_where = "group_type_id = %d AND province = %s AND deleted_at IS NULL AND lat IS NOT NULL AND lng IS NOT NULL";
        $city_vals  = array($city_type_id, $province_name);
        if (!empty($country_name)) {
            $city_where .= " AND country = %s";
            $city_vals[] = $country_name;
        }
        
        $city_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(lat) AS avg_lat, AVG(lng) AS avg_lng
             FROM $groups_table
             WHERE $city_where",
            ...$city_vals
        ), ARRAY_A);
        
        if (empty($city_stats['avg_lat']) || empty($city_stats['avg_lng'])) {
            // If we can't infer coordinates from cities, skip this province
            continue;
        }
        
        $group = Simuze_Group::get($row['id']);
        if (!$group) {
            continue;
        }
        
        $settings = array();
        if (isset($group->settings)) {
            if (is_array($group->settings)) {
                $settings = $group->settings;
            } elseif (is_string($group->settings)) {
                $decoded = json_decode($group->settings, true);
                if (is_array($decoded)) {
                    $settings = $decoded;
                }
            }
        }
        
        if (!isset($settings['map_zoom'])) {
            $settings['map_zoom'] = 8; // Province zoom
        }
        
        $group->update(array(
            'lat'      => (float) $city_stats['avg_lat'],
            'lng'      => (float) $city_stats['avg_lng'],
            'settings' => $settings,
        ));
    }
    
    update_option('simuze_province_geo_backfilled', 1);
}
add_action('init', 'simuze_backfill_province_locations_if_needed');

/**
 * One-time backfill for country locations.
 *
 * Similar to provinces, early country groups may miss lat/lng. We infer a
 * country's center from all groups in that country that already have
 * coordinates (typically cities) and store a map_zoom suitable for country maps.
 */
function simuze_backfill_country_locations_if_needed() {
    if (get_option('simuze_country_geo_backfilled')) {
        return;
    }
    
    global $wpdb;
    $groups_table = $wpdb->prefix . (defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_') . 'groups';
    $types_table  = $wpdb->prefix . (defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_') . 'group_types';
    
    $country_type_id = $wpdb->get_var("SELECT id FROM $types_table WHERE slug = 'country' AND deleted_at IS NULL");
    
    if (!$country_type_id) {
        update_option('simuze_country_geo_backfilled', 1);
        return;
    }
    
    // Countries without lat/lng
    $countries = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, country, lat, lng, settings
         FROM $groups_table
         WHERE group_type_id = %d
           AND deleted_at IS NULL
           AND (lat IS NULL OR lat = 0 OR lng IS NULL OR lng = 0)",
        $country_type_id
    ), ARRAY_A);
    
    foreach ($countries as $row) {
        $country_name = $row['country'] ?: $row['name'];
        
        // Compute average lat/lng from all groups in this country (cities, provinces, artists, etc.)
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(lat) AS avg_lat, AVG(lng) AS avg_lng
             FROM $groups_table
             WHERE country = %s
               AND deleted_at IS NULL
               AND lat IS NOT NULL
               AND lng IS NOT NULL",
            $country_name
        ), ARRAY_A);
        
        if (empty($stats['avg_lat']) || empty($stats['avg_lng'])) {
            continue;
        }
        
        $group = Simuze_Group::get($row['id']);
        if (!$group) {
            continue;
        }
        
        $settings = array();
        if (isset($group->settings)) {
            if (is_array($group->settings)) {
                $settings = $group->settings;
            } elseif (is_string($group->settings)) {
                $decoded = json_decode($group->settings, true);
                if (is_array($decoded)) {
                    $settings = $decoded;
                }
            }
        }
        
        if (!isset($settings['map_zoom'])) {
            $settings['map_zoom'] = 7; // Country zoom
        }
        
        $group->update(array(
            'lat'      => (float) $stats['avg_lat'],
            'lng'      => (float) $stats['avg_lng'],
            'settings' => $settings,
        ));
    }
    
    update_option('simuze_country_geo_backfilled', 1);
}
add_action('init', 'simuze_backfill_country_locations_if_needed');

/**
 * One-time backfill for city locations.
 *
 * City groups that were created before lat/lng was stored, or that lack
 * coordinates, get them from geo_id (Geonames getJSON) or from a Geonames
 * search by name + country. Process up to 5 cities per request to avoid API limits.
 */
function simuze_backfill_city_locations_if_needed() {
    if (get_option('simuze_city_geo_backfilled')) {
        return;
    }
    
    global $wpdb;
    $groups_table = $wpdb->prefix . (defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_') . 'groups';
    $types_table  = $wpdb->prefix . (defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_') . 'group_types';
    
    $city_type_id = $wpdb->get_var("SELECT id FROM $types_table WHERE slug = 'city' AND deleted_at IS NULL");
    
    if (!$city_type_id) {
        update_option('simuze_city_geo_backfilled', 1);
        return;
    }
    
    $cities = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, city, province, country, geo_id, lat, lng, settings
         FROM $groups_table
         WHERE group_type_id = %d
           AND deleted_at IS NULL
           AND (lat IS NULL OR lat = 0 OR lng IS NULL OR lng = 0)
         LIMIT 5",
        $city_type_id
    ), ARRAY_A);
    
    if (empty($cities)) {
        update_option('simuze_city_geo_backfilled', 1);
        return;
    }
    
    foreach ($cities as $row) {
        $lat = null;
        $lng = null;
        $geo_id = null;
        
        if (!empty($row['geo_id'])) {
            $loc = Simuze_Geo::get_location_by_id($row['geo_id']);
            if (!empty($loc['lat']) && !empty($loc['lng'])) {
                $lat = (float) $loc['lat'];
                $lng = (float) $loc['lng'];
                $geo_id = $row['geo_id'];
            }
        }
        
        if ($lat === null && ($row['name'] || $row['city'])) {
            $query = $row['name'] ?: $row['city'];
            $results = Simuze_Geo::search_cities($query, 3);
            foreach ($results as $hit) {
                $match_country = empty($row['country']) || (isset($hit['countryName']) && strcasecmp(trim($hit['countryName']), trim($row['country'])) === 0);
                if ($match_country && !empty($hit['lat']) && !empty($hit['lng'])) {
                    $lat = (float) $hit['lat'];
                    $lng = (float) $hit['lng'];
                    if (!empty($hit['geonameId'])) {
                        $geo_id = $hit['geonameId'];
                    }
                    break;
                }
            }
        }
        
        if ($lat === null || $lng === null) {
            continue;
        }
        
        $group = Simuze_Group::get($row['id']);
        if (!$group) {
            continue;
        }
        
        $settings = array();
        if (isset($group->settings)) {
            if (is_array($group->settings)) {
                $settings = $group->settings;
            } elseif (is_string($group->settings)) {
                $decoded = json_decode($group->settings, true);
                if (is_array($decoded)) {
                    $settings = $decoded;
                }
            }
        }
        if (!isset($settings['map_zoom'])) {
            $settings['map_zoom'] = 12;
        }
        
        $update = array(
            'lat'      => $lat,
            'lng'      => $lng,
            'settings' => $settings,
        );
        if ($geo_id !== null) {
            $update['geo_id'] = $geo_id;
        }
        $group->update($update);
    }
}
add_action('init', 'simuze_backfill_city_locations_if_needed', 20);

// Register AJAX handlers for city search
add_action('wp_ajax_simuze_search_cities', 'simuze_ajax_search_cities');
add_action('wp_ajax_nopriv_simuze_search_cities', 'simuze_ajax_search_cities');

function simuze_ajax_search_cities() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wp_rest')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $query = sanitize_text_field($_POST['query'] ?? '');
    
    if (strlen($query) < 2) {
        wp_send_json_success(array());
    }
    
    $results = Simuze_Geo::search_cities($query, 10);
    
    wp_send_json_success($results);
}
