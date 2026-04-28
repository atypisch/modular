<?php
/**
 * VenueRepository
 *
 * All data access for simuze_venues and simuze_cities.
 * Uses md_db_*() helpers — no $wpdb dependency.
 */
class VenueRepository {

    public static function findById(int $id): ?array {
        return md_db_row('SELECT v.*, c.name AS city_name, c.slug AS city_slug FROM simuze_venues v LEFT JOIN simuze_cities c ON c.id = v.city_id WHERE v.id = ? AND v.deleted_at IS NULL', [$id]) ?: null;
    }

    /**
     * Public venues in a city (for show creation picker and city page).
     */
    public static function findPublicByCity(int $city_id): array {
        return md_db_query(
            'SELECT * FROM simuze_venues WHERE city_id = ? AND is_public = 1 AND deleted_at IS NULL ORDER BY name ASC',
            [$city_id]
        ) ?: [];
    }

    /**
     * All venues (admin).
     */
    public static function findAll(int $limit = 100): array {
        return md_db_query(
            "SELECT v.*, c.name AS city_name FROM simuze_venues v LEFT JOIN simuze_cities c ON c.id = v.city_id WHERE v.deleted_at IS NULL ORDER BY v.name ASC LIMIT " . intval($limit)
        ) ?: [];
    }

    public static function create(array $data): ?int {
        $now = date('Y-m-d H:i:s');
        md_db_insert('simuze_venues', [
            'name'        => $data['name'] ?? '',
            'type'        => $data['type'] ?? 'diy_space',
            'city_id'     => $data['city_id'] ?? null,
            'address'     => $data['address'] ?? null,
            'capacity'    => $data['capacity'] ?? null,
            'contact'     => $data['contact'] ?? null,
            'notes'       => $data['notes'] ?? null,
            'is_public'   => (int) ($data['is_public'] ?? 1),
            'group_id'    => $data['group_id'] ?? null,
            'created_by'  => isset($data['created_by']) && $data['created_by'] ? (int) $data['created_by'] : null,
            'owner_email' => isset($data['owner_email']) && $data['owner_email'] !== '' ? $data['owner_email'] : null,
            'created_at'  => $now,
        ]);
        return (int) md_db_var('SELECT id FROM simuze_venues WHERE name = ? AND created_at = ? ORDER BY id DESC LIMIT 1', [$data['name'], $now]) ?: null;
    }

    public static function update(int $id, array $data): void {
        md_db_update('simuze_venues', $data, ['id' => $id]);
    }

    public static function delete(int $id): void {
        md_db_update('simuze_venues', ['deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);
    }

    // ------------------------------------------------------------------
    // Cities
    // ------------------------------------------------------------------

    public static function findCityBySlug(string $slug): ?array {
        return md_db_row('SELECT * FROM simuze_cities WHERE slug = ?', [$slug]) ?: null;
    }

    public static function findCityById(int $id): ?array {
        return md_db_row('SELECT * FROM simuze_cities WHERE id = ?', [$id]) ?: null;
    }

    public static function allCities(): array {
        return md_db_query('SELECT * FROM simuze_cities ORDER BY name ASC') ?: [];
    }

    /**
     * Search venues by name within a city (AJAX live-search).
     */
    public static function searchByCity(int $city_id, string $query, int $limit = 10): array {
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
        return md_db_query(
            'SELECT id, name, type, address FROM simuze_venues
             WHERE city_id = ? AND name LIKE ? AND is_public = 1 AND deleted_at IS NULL
             ORDER BY name ASC LIMIT ' . intval($limit),
            [$city_id, $like]
        ) ?: [];
    }
}

// ------------------------------------------------------------------
// AJAX handler — venue live search
// ------------------------------------------------------------------
function simuze_ajax_search_venues() {
    header('Content-Type: application/json');

    $nonce = $_POST['nonce'] ?? $_POST['_csrf_token'] ?? '';
    $valid = false;
    if (function_exists('md_verify_csrf') && md_verify_csrf()) {
        $valid = true;
    } elseif ($nonce !== '' && function_exists('md_csrf_token')) {
        $valid = hash_equals(md_csrf_token(), $nonce);
    }
    if (!$valid) {
        echo json_encode(['success' => false, 'data' => 'Invalid token']);
        exit;
    }

    $city_id = (int) ($_POST['city_id'] ?? 0);
    $query   = function_exists('md_sanitize_text') ? md_sanitize_text($_POST['query'] ?? '') : trim($_POST['query'] ?? '');

    if (!$city_id || strlen($query) < 1) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $results = VenueRepository::searchByCity($city_id, $query);
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

if (function_exists('add_action')) {
    add_action('wp_ajax_simuze_search_venues',        'simuze_ajax_search_venues');
    add_action('wp_ajax_nopriv_simuze_search_venues', 'simuze_ajax_search_venues');
}

// ------------------------------------------------------------------
// AJAX handler — resolve/create city in simuze_cities, return its id
// Called after city autocomplete selection so venue search can use city_id
// ------------------------------------------------------------------
function simuze_ajax_resolve_city() {
    header('Content-Type: application/json');

    $nonce = $_POST['nonce'] ?? $_POST['_csrf_token'] ?? '';
    $valid = false;
    if (function_exists('md_verify_csrf') && md_verify_csrf()) {
        $valid = true;
    } elseif ($nonce !== '' && function_exists('md_csrf_token')) {
        $valid = hash_equals(md_csrf_token(), $nonce);
    }
    if (!$valid) {
        echo json_encode(['success' => false, 'data' => 'Invalid token']);
        exit;
    }

    $name    = function_exists('md_sanitize_text') ? md_sanitize_text($_POST['name']    ?? '') : trim($_POST['name']    ?? '');
    $country = function_exists('md_sanitize_text') ? md_sanitize_text($_POST['country'] ?? '') : trim($_POST['country'] ?? '');

    if ($name === '') {
        echo json_encode(['success' => false, 'data' => 'Missing city name']);
        exit;
    }

    if (!class_exists('EventRepository')) {
        echo json_encode(['success' => false, 'data' => 'EventRepository not available']);
        exit;
    }

    $city_id = EventRepository::findOrCreateCity($name, $country);
    echo json_encode(['success' => (bool) $city_id, 'data' => ['city_id' => $city_id]]);
    exit;
}

if (function_exists('add_action')) {
    add_action('wp_ajax_simuze_resolve_city',        'simuze_ajax_resolve_city');
    add_action('wp_ajax_nopriv_simuze_resolve_city', 'simuze_ajax_resolve_city');
}
