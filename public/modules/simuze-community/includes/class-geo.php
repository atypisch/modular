<?php
/**
 * GEO location helper with Geonames API integration
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Geo {

    private static $geonames_username = 'atypisch';

    /** In-memory request cache for city search results */
    private static $cache = [];

    public static function search_cities($query, $max_results = 10) {
        if (strlen($query) < 2) return [];

        $cache_key = md5($query);
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        $existing_cities = self::search_existing_city_groups($query);
        $geonames_cities = self::search_geonames($query, $max_results);

        $results = array_merge($existing_cities, $geonames_cities);

        $unique = [];
        $seen   = [];
        foreach ($results as $city) {
            $key = strtolower($city['name'] . '-' . ($city['countryName'] ?? ''));
            if (!isset($seen[$key])) {
                $unique[]   = $city;
                $seen[$key] = true;
            }
        }

        $results = array_slice($unique, 0, $max_results);
        self::$cache[$cache_key] = $results;

        return $results;
    }

    private static function search_existing_city_groups($query) {
        $like    = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
        $results = md_db_query(
            "SELECT g.name, g.city, g.province, g.country, g.geo_id as geonameId, g.lat, g.lng
             FROM simuze_groups g
             LEFT JOIN simuze_group_types t ON g.group_type_id = t.id
             WHERE t.slug = 'city'
             AND g.name LIKE ?
             AND g.deleted_at IS NULL
             LIMIT 5",
            [$like]
        );

        $cities = [];
        foreach ($results as $row) {
            $cities[] = [
                'name'        => $row['name'],
                'adminName1'  => $row['province'],
                'countryName' => $row['country'],
                'geonameId'   => $row['geonameId'],
                'lat'         => $row['lat'],
                'lng'         => $row['lng'],
                'existing'    => true,
            ];
        }
        return $cities;
    }

    private static function search_geonames($query, $max_results = 10) {
        $url = 'http://api.geonames.org/searchJSON?' . http_build_query([
            'name_startsWith' => $query,
            'featureClass'    => 'P',
            'maxRows'         => $max_results,
            'lang'            => 'en',
            'username'        => self::$geonames_username,
        ]);

        $response = md_http_get($url, ['timeout' => 10]);

        if ($response === false || $response['code'] !== 200) return [];

        $data = json_decode($response['body'], true);
        if (empty($data['geonames'])) return [];

        $cities = [];
        foreach ($data['geonames'] as $place) {
            $cities[] = [
                'name'        => $place['name'],
                'adminName1'  => $place['adminName1']  ?? '',
                'countryName' => $place['countryName'] ?? '',
                'geonameId'   => $place['geonameId']   ?? '',
                'lat'         => $place['lat']         ?? '',
                'lng'         => $place['lng']         ?? '',
                'population'  => $place['population']  ?? 0,
                'existing'    => false,
            ];
        }

        usort($cities, fn($a, $b) => ($b['population'] ?? 0) - ($a['population'] ?? 0));

        return $cities;
    }

    public static function get_location_by_id($geoname_id) {
        $url      = 'http://api.geonames.org/getJSON?' . http_build_query(['geonameId' => $geoname_id, 'username' => self::$geonames_username]);
        $response = md_http_get($url, ['timeout' => 10]);
        if ($response === false || $response['code'] !== 200) return false;
        return json_decode($response['body'], true);
    }

    public static function auto_create_location_groups($location_data, $creator_id) {
        $results  = ['city' => null, 'province' => null, 'country' => null];
        $base_lat = $location_data['lat'] ?? null;
        $base_lng = $location_data['lng'] ?? null;

        if (!empty($location_data['country'])) {
            $results['country'] = self::get_or_create_location_group(
                $location_data['country'], 'country',
                ['country' => $location_data['country'], 'lat' => $base_lat, 'lng' => $base_lng, 'settings' => ['map_zoom' => 7]],
                1
            );
        }

        if (!empty($location_data['province'])) {
            $results['province'] = self::get_or_create_location_group(
                $location_data['province'], 'province',
                ['province' => $location_data['province'], 'country' => $location_data['country'], 'lat' => $base_lat, 'lng' => $base_lng, 'settings' => ['map_zoom' => 8]],
                $creator_id
            );
        }

        if (!empty($location_data['city'])) {
            $results['city'] = self::get_or_create_location_group(
                $location_data['city'], 'city',
                ['city' => $location_data['city'], 'province' => $location_data['province'], 'country' => $location_data['country'], 'geo_id' => $location_data['geo_id'] ?? null, 'lat' => $base_lat, 'lng' => $base_lng, 'settings' => ['map_zoom' => 12]],
                $creator_id
            );
        }

        return $results;
    }

    private static function get_or_create_location_group($name, $type, $location_data, $admin_id) {
        $type_id = self::get_group_type_id($type);
        if (!$type_id) {
            $type_id = self::create_group_type($type);
        }

        $existing = md_db_row(
            "SELECT * FROM simuze_groups WHERE name = ? AND group_type_id = ? AND deleted_at IS NULL",
            [$name, $type_id]
        );

        if ($existing) {
            $group  = new Simuze_Group($existing);
            $update = [];

            if ((empty($group->lat) || empty($group->lng)) && !empty($location_data['lat']) && !empty($location_data['lng'])) {
                $update['lat'] = (float) $location_data['lat'];
                $update['lng'] = (float) $location_data['lng'];
            }

            $desired_zoom = ($type === 'city') ? 12 : ($type === 'province' ? 8 : 7);
            $settings     = self::parse_settings($group->settings ?? null);

            if (!isset($settings['map_zoom'])) {
                $settings['map_zoom'] = $desired_zoom;
                $update['settings']   = $settings;
            }

            if (!empty($update)) $group->update($update);

            return ['id' => $group->id, 'created' => false];
        }

        $slug       = preg_replace('/[^a-z0-9-]+/', '-', strtolower($name));
        $group_data = array_merge([
            'name'          => $name,
            'slug'          => $slug,
            'group_type_id' => $type_id,
            'description'   => sprintf('%s - %s', ucfirst($type), $name),
            'status'        => 'public',
            'creator_id'    => $admin_id,
        ], $location_data);

        $group = Simuze_Group::create($group_data);

        return $group ? ['id' => $group->id, 'created' => true] : null;
    }

    public static function get_group_type_id($slug) {
        return md_db_var("SELECT id FROM simuze_group_types WHERE slug = ? AND deleted_at IS NULL", [$slug]);
    }

    private static function create_group_type($slug) {
        $now = date('Y-m-d H:i:s');
        return md_db_insert('simuze_group_types', [
            'name'        => ucfirst($slug),
            'slug'        => $slug,
            'description' => ucfirst($slug) . ' group type',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    private static function parse_settings($settings) {
        if (is_array($settings))  return $settings;
        if (is_string($settings)) {
            $decoded = json_decode($settings, true);
            if (is_array($decoded)) return $decoded;
        }
        return [];
    }
}

// ---------------------------------------------------------------------------
// One-time backfill utilities (modular-native version)
// ---------------------------------------------------------------------------

function simuze_get_backfill_flag($name) {
    try {
        return (bool) md_db_var("SELECT option_value FROM settings WHERE option_name = ? LIMIT 1", [$name]);
    } catch (\Throwable $e) {
        return false;
    }
}

function simuze_set_backfill_flag($name, $value = 1) {
    try {
        $exists = md_db_var("SELECT id FROM settings WHERE option_name = ?", [$name]);
        if ($exists) {
            md_db_update('settings', ['option_value' => $value], ['option_name' => $name]);
        } else {
            md_db_insert('settings', ['option_name' => $name, 'option_value' => $value]);
        }
    } catch (\Throwable $e) {
        // Table may not exist yet — ignore
    }
}

function simuze_backfill_province_locations_if_needed() {
    if (simuze_get_backfill_flag('simuze_province_geo_backfilled')) return;

    $province_type_id = md_db_var("SELECT id FROM simuze_group_types WHERE slug = 'province' AND deleted_at IS NULL");
    $city_type_id     = md_db_var("SELECT id FROM simuze_group_types WHERE slug = 'city' AND deleted_at IS NULL");

    if (!$province_type_id || !$city_type_id) {
        simuze_set_backfill_flag('simuze_province_geo_backfilled');
        return;
    }

    $provinces = md_db_query(
        "SELECT id, name, province, country, lat, lng, settings
         FROM simuze_groups
         WHERE group_type_id = ?
           AND deleted_at IS NULL
           AND (lat IS NULL OR lat = 0 OR lng IS NULL OR lng = 0)",
        [$province_type_id]
    );

    foreach ($provinces as $row) {
        $province_name = $row['province'] ?: $row['name'];
        $country_name  = $row['country'];

        $city_sql    = "SELECT AVG(lat) AS avg_lat, AVG(lng) AS avg_lng FROM simuze_groups WHERE group_type_id = ? AND province = ? AND deleted_at IS NULL AND lat IS NOT NULL AND lng IS NOT NULL";
        $city_params = [$city_type_id, $province_name];

        if (!empty($country_name)) {
            $city_sql    .= ' AND country = ?';
            $city_params[] = $country_name;
        }

        $city_stats = md_db_row($city_sql, $city_params);

        if (empty($city_stats['avg_lat']) || empty($city_stats['avg_lng'])) continue;

        $group = Simuze_Group::get($row['id']);
        if (!$group) continue;

        $settings = self_geo_parse_settings($group->settings ?? null);
        if (!isset($settings['map_zoom'])) $settings['map_zoom'] = 8;

        $group->update(['lat' => (float) $city_stats['avg_lat'], 'lng' => (float) $city_stats['avg_lng'], 'settings' => $settings]);
    }

    simuze_set_backfill_flag('simuze_province_geo_backfilled');
}

function simuze_backfill_country_locations_if_needed() {
    if (simuze_get_backfill_flag('simuze_country_geo_backfilled')) return;

    $country_type_id = md_db_var("SELECT id FROM simuze_group_types WHERE slug = 'country' AND deleted_at IS NULL");

    if (!$country_type_id) {
        simuze_set_backfill_flag('simuze_country_geo_backfilled');
        return;
    }

    $countries = md_db_query(
        "SELECT id, name, country, lat, lng, settings
         FROM simuze_groups
         WHERE group_type_id = ? AND deleted_at IS NULL AND (lat IS NULL OR lat = 0 OR lng IS NULL OR lng = 0)",
        [$country_type_id]
    );

    foreach ($countries as $row) {
        $country_name = $row['country'] ?: $row['name'];
        $stats        = md_db_row(
            "SELECT AVG(lat) AS avg_lat, AVG(lng) AS avg_lng FROM simuze_groups WHERE country = ? AND deleted_at IS NULL AND lat IS NOT NULL AND lng IS NOT NULL",
            [$country_name]
        );

        if (empty($stats['avg_lat']) || empty($stats['avg_lng'])) continue;

        $group = Simuze_Group::get($row['id']);
        if (!$group) continue;

        $settings = self_geo_parse_settings($group->settings ?? null);
        if (!isset($settings['map_zoom'])) $settings['map_zoom'] = 7;

        $group->update(['lat' => (float) $stats['avg_lat'], 'lng' => (float) $stats['avg_lng'], 'settings' => $settings]);
    }

    simuze_set_backfill_flag('simuze_country_geo_backfilled');
}

function simuze_backfill_city_locations_if_needed() {
    if (simuze_get_backfill_flag('simuze_city_geo_backfilled')) return;

    $city_type_id = md_db_var("SELECT id FROM simuze_group_types WHERE slug = 'city' AND deleted_at IS NULL");

    if (!$city_type_id) {
        simuze_set_backfill_flag('simuze_city_geo_backfilled');
        return;
    }

    $cities = md_db_query(
        "SELECT id, name, city, province, country, geo_id, lat, lng, settings
         FROM simuze_groups
         WHERE group_type_id = ? AND deleted_at IS NULL AND (lat IS NULL OR lat = 0 OR lng IS NULL OR lng = 0)
         LIMIT 5",
        [$city_type_id]
    );

    if (empty($cities)) {
        simuze_set_backfill_flag('simuze_city_geo_backfilled');
        return;
    }

    foreach ($cities as $row) {
        $lat = $lng = $geo_id = null;

        if (!empty($row['geo_id'])) {
            $loc = Simuze_Geo::get_location_by_id($row['geo_id']);
            if (!empty($loc['lat']) && !empty($loc['lng'])) {
                $lat = (float) $loc['lat'];
                $lng = (float) $loc['lng'];
                $geo_id = $row['geo_id'];
            }
        }

        if ($lat === null && ($row['name'] || $row['city'])) {
            $query   = $row['name'] ?: $row['city'];
            $results = Simuze_Geo::search_cities($query, 3);
            foreach ($results as $hit) {
                $match_country = empty($row['country']) || (isset($hit['countryName']) && strcasecmp(trim($hit['countryName']), trim($row['country'])) === 0);
                if ($match_country && !empty($hit['lat']) && !empty($hit['lng'])) {
                    $lat    = (float) $hit['lat'];
                    $lng    = (float) $hit['lng'];
                    $geo_id = $hit['geonameId'] ?? null;
                    break;
                }
            }
        }

        if ($lat === null || $lng === null) continue;

        $group = Simuze_Group::get($row['id']);
        if (!$group) continue;

        $settings = self_geo_parse_settings($group->settings ?? null);
        if (!isset($settings['map_zoom'])) $settings['map_zoom'] = 12;

        $update = ['lat' => $lat, 'lng' => $lng, 'settings' => $settings];
        if ($geo_id !== null) $update['geo_id'] = $geo_id;
        $group->update($update);
    }
}

function self_geo_parse_settings($settings) {
    if (is_array($settings))  return $settings;
    if (is_string($settings)) {
        $decoded = json_decode($settings, true);
        if (is_array($decoded)) return $decoded;
    }
    return [];
}

// Register backfills on init
md_hook_action('init', 'simuze_backfill_province_locations_if_needed');
md_hook_action('init', 'simuze_backfill_country_locations_if_needed');
md_hook_action('init', 'simuze_backfill_city_locations_if_needed', 20);

// Register city search AJAX (modular REST route handles this; keep as hook for WP compat)
if (function_exists('add_action')) {
    add_action('wp_ajax_simuze_search_cities', 'simuze_ajax_search_cities');
    add_action('wp_ajax_nopriv_simuze_search_cities', 'simuze_ajax_search_cities');
}

function simuze_ajax_search_cities() {
    // Accept either the standard Modular CSRF token (_csrf_token/X-CSRF-Token)
    // or the simuzeCommunity.nonce value passed by frontend JS (md_csrf_token()).
    $nonce = $_POST['nonce'] ?? $_POST['_csrf_token'] ?? '';
    $valid = false;
    if (function_exists('md_verify_csrf') && md_verify_csrf()) {
        $valid = true;
    } elseif ($nonce !== '' && function_exists('md_csrf_token')) {
        $valid = hash_equals(md_csrf_token(), $nonce);
    }

    if (!$valid) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'data' => 'Invalid token']);
        exit;
    }

    $query = md_sanitize_text($_POST['query'] ?? '');

    if (strlen($query) < 2) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $results = Simuze_Geo::search_cities($query, 10);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}
