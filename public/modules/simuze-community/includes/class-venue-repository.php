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
            'name'       => $data['name'] ?? '',
            'type'       => $data['type'] ?? 'diy_space',
            'city_id'    => $data['city_id'] ?? null,
            'address'    => $data['address'] ?? null,
            'capacity'   => $data['capacity'] ?? null,
            'contact'    => $data['contact'] ?? null,
            'notes'      => $data['notes'] ?? null,
            'is_public'  => (int) ($data['is_public'] ?? 1),
            'group_id'   => $data['group_id'] ?? null,
            'created_by' => $data['created_by'],
            'created_at' => $now,
        ]);
        return (int) md_db_var('SELECT id FROM simuze_venues WHERE created_by = ? AND name = ? ORDER BY id DESC LIMIT 1', [$data['created_by'], $data['name']]) ?: null;
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
}
