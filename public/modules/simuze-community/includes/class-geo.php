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
        // Guard against recursive calls (Simuze_Group::create() also calls this)
        static $running = false;
        if ($running) return ['city' => null, 'province' => null, 'country' => null];
        $running = true;

        $results  = ['city' => null, 'province' => null, 'country' => null];
        $base_lat = $location_data['lat'] ?? null;
        $base_lng = $location_data['lng'] ?? null;

        // Canonical names (strings) for linkage + display
        $country_name  = !empty($location_data['country']) ? (string) $location_data['country'] : '';
        $province_name = !empty($location_data['province']) ? (string) $location_data['province'] : '';
        $city_name     = !empty($location_data['city']) ? (string) $location_data['city'] : '';

        if (!empty($location_data['country'])) {
            $results['country'] = self::get_or_create_location_group(
                $location_data['country'], 'country',
                ['country' => $location_data['country'], 'lat' => $base_lat, 'lng' => $base_lng, 'settings' => ['map_zoom' => 7]],
                $creator_id
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

        // Persist parent links in settings JSON for location groups (custom fields).
        // This makes it possible to traverse country -> provinces -> cities and back reliably.
        try {
            $country_group_id  = !empty($results['country']['id'])  ? (int) $results['country']['id']  : 0;
            $province_group_id = !empty($results['province']['id']) ? (int) $results['province']['id'] : 0;
            $city_group_id     = !empty($results['city']['id'])     ? (int) $results['city']['id']     : 0;

            if ($province_group_id > 0 && $country_group_id > 0 && class_exists('Simuze_Group')) {
                $prov = Simuze_Group::get($province_group_id);
                if ($prov) {
                    $ps = is_array($prov->settings) ? $prov->settings : (is_string($prov->settings) ? json_decode((string) $prov->settings, true) : []);
                    if (!is_array($ps)) $ps = [];
                    if (!isset($ps['location']) || !is_array($ps['location'])) $ps['location'] = [];
                    $ps['location']['country_group_id'] = $country_group_id;
                    if ($country_name !== '') $ps['location']['country_name'] = $country_name;
                    if ($province_name !== '') $ps['location']['province_name'] = $province_name;
                    $prov->update(['settings' => $ps], ['skip_location_groups' => true]);
                }
            }

            if ($city_group_id > 0 && class_exists('Simuze_Group')) {
                $cityg = Simuze_Group::get($city_group_id);
                if ($cityg) {
                    $cs = is_array($cityg->settings) ? $cityg->settings : (is_string($cityg->settings) ? json_decode((string) $cityg->settings, true) : []);
                    if (!is_array($cs)) $cs = [];
                    if (!isset($cs['location']) || !is_array($cs['location'])) $cs['location'] = [];
                    if ($country_group_id > 0) $cs['location']['country_group_id'] = $country_group_id;
                    if ($province_group_id > 0) $cs['location']['province_group_id'] = $province_group_id;
                    if ($country_name !== '') $cs['location']['country_name'] = $country_name;
                    if ($province_name !== '') $cs['location']['province_name'] = $province_name;
                    if ($city_name !== '') $cs['location']['city_name'] = $city_name;
                    $cityg->update(['settings' => $cs], ['skip_location_groups' => true]);
                }
            }
        } catch (\Throwable $e) {
            // Non-critical; ignore
        }

        $running = false;
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
            'owner_id'      => $admin_id, // owner_id is NOT NULL in DB schema
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

/**
 * One-time backfill: persist linked location IDs into simuze_groups.settings.location
 * and normalize simuze_cities.country to the canonical (local/native) country group name.
 */
function simuze_backfill_location_links_if_needed() {
    if (simuze_get_backfill_flag('simuze_location_links_backfilled')) return;
    if (!class_exists('Simuze_Group') || !class_exists('Simuze_Geo')) {
        simuze_set_backfill_flag('simuze_location_links_backfilled');
        return;
    }

    // ---------------------------
    // 1) Backfill group.settings.location.*_group_id for existing groups
    // ---------------------------
    $rows = md_db_query(
        "SELECT id, city, province, country, geo_id, lat, lng, settings
         FROM simuze_groups
         WHERE deleted_at IS NULL
           AND (country <> '' OR province <> '' OR city <> '')
         LIMIT 800"
    ) ?: [];

    foreach ($rows as $r) {
        $gid = (int) ($r['id'] ?? 0);
        if ($gid <= 0) continue;

        $settings = self_geo_parse_settings($r['settings'] ?? null);
        if (!isset($settings['location']) || !is_array($settings['location'])) {
            $settings['location'] = [];
        }
        $loc = $settings['location'];

        // Skip if already linked.
        if (!empty($loc['country_group_id']) || !empty($loc['province_group_id']) || !empty($loc['city_group_id'])) {
            continue;
        }

        $country = trim((string) ($r['country'] ?? ''));
        $province = trim((string) ($r['province'] ?? ''));
        $city = trim((string) ($r['city'] ?? ''));

        $location_data = [
            'country' => $country,
            'province' => $province,
            'city' => $city,
            'geo_id' => $r['geo_id'] ?? null,
            'lat' => $r['lat'] ?? null,
            'lng' => $r['lng'] ?? null,
        ];

        $created = null;
        try {
            $created = Simuze_Geo::auto_create_location_groups($location_data, 1);
        } catch (\Throwable $e) {
            $created = null;
        }

        if (is_array($created)) {
            if (!empty($created['country']['id']))  $loc['country_group_id']  = (int) $created['country']['id'];
            if (!empty($created['province']['id'])) $loc['province_group_id'] = (int) $created['province']['id'];
            if (!empty($created['city']['id']))     $loc['city_group_id']     = (int) $created['city']['id'];
        }
        if ($country !== '')  $loc['country_name']  = $country;
        if ($province !== '') $loc['province_name'] = $province;
        if ($city !== '')     $loc['city_name']     = $city;

        if (!empty($loc['country_group_id']) || !empty($loc['province_group_id']) || !empty($loc['city_group_id'])) {
            $settings['location'] = $loc;
            $g = Simuze_Group::get($gid);
            if ($g) {
                $g->update(['settings' => $settings], ['skip_location_groups' => true]);
            }
        }
    }

    // ---------------------------
    // 2) Normalize simuze_cities.country to canonical local/native country group name
    // ---------------------------
    $country_type_id = Simuze_Geo::get_group_type_id('country');
    if ($country_type_id) {
        // Build lookup table: lowercase(country_group.name) => canonical name
        $country_groups = md_db_query(
            "SELECT id, name, settings
             FROM simuze_groups
             WHERE group_type_id = ?
               AND deleted_at IS NULL",
            [(int) $country_type_id]
        ) ?: [];

        $canon_by_lower = [];
        foreach ($country_groups as $cg) {
            $nm = trim((string) ($cg['name'] ?? ''));
            if ($nm === '') continue;
            $canon_by_lower[mb_strtolower($nm)] = $nm;

            // Optional aliases in settings.location.aliases: ["Netherlands","The Netherlands"]
            $st = self_geo_parse_settings($cg['settings'] ?? null);
            $aliases = $st['location']['aliases'] ?? [];
            if (is_array($aliases)) {
                foreach ($aliases as $a) {
                    $a = trim((string) $a);
                    if ($a !== '') $canon_by_lower[mb_strtolower($a)] = $nm;
                }
            }
        }

        // Hardcoded common alias (requested case: Netherlands -> Nederland) when such group exists.
        if (isset($canon_by_lower['nederland'])) {
            $canon_by_lower['netherlands'] = $canon_by_lower['nederland'];
            $canon_by_lower['the netherlands'] = $canon_by_lower['nederland'];
        }

        $cities = md_db_query(
            "SELECT id, country FROM simuze_cities
             WHERE country <> ''
             LIMIT 5000"
        ) ?: [];

        foreach ($cities as $c) {
            $cid = (int) ($c['id'] ?? 0);
            $cn = trim((string) ($c['country'] ?? ''));
            if ($cid <= 0 || $cn === '') continue;
            $key = mb_strtolower($cn);
            if (!isset($canon_by_lower[$key])) continue;
            $canonical = $canon_by_lower[$key];
            if ($canonical !== '' && $canonical !== $cn) {
                md_db_update('simuze_cities', ['country' => $canonical], ['id' => $cid]);
            }
        }
    }

    simuze_set_backfill_flag('simuze_location_links_backfilled');
}

// Register backfills on init
md_hook_action('init', 'simuze_backfill_province_locations_if_needed');
md_hook_action('init', 'simuze_backfill_country_locations_if_needed');
md_hook_action('init', 'simuze_backfill_city_locations_if_needed', 20);
md_hook_action('init', 'simuze_backfill_location_links_if_needed', 30);

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
