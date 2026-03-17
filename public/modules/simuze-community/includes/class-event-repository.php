<?php
/**
 * EventRepository
 *
 * All data access for simuze_events and simuze_event_slots.
 * Uses md_db_*() helpers — no $wpdb dependency.
 */
class EventRepository {

    // ------------------------------------------------------------------
    // Events
    // ------------------------------------------------------------------

    public static function findById(int $id): ?array {
        return md_db_row('SELECT * FROM simuze_events WHERE id = ? AND deleted_at IS NULL', [$id]) ?: null;
    }

    public static function findBySlug(string $slug): ?array {
        return md_db_row('SELECT * FROM simuze_events WHERE slug = ? AND deleted_at IS NULL', [$slug]) ?: null;
    }

    /**
     * Upcoming events, optionally filtered by city_id.
     * Returns events with status open|lineup_set|confirmed and event_date >= now.
     */
    public static function findUpcoming(int $limit = 12, ?int $city_id = null): array {
        if ($city_id) {
            return md_db_query(
                "SELECT e.*, c.name AS city_name, c.slug AS city_slug
                 FROM simuze_events e
                 LEFT JOIN simuze_cities c ON c.id = e.city_id
                 WHERE e.status IN ('open','lineup_set','confirmed')
                   AND e.event_date >= NOW()
                   AND e.deleted_at IS NULL
                   AND e.city_id = ?
                 ORDER BY e.event_date ASC
                 LIMIT " . intval($limit),
                [$city_id]
            ) ?: [];
        }
        return md_db_query(
            "SELECT e.*, c.name AS city_name, c.slug AS city_slug
             FROM simuze_events e
             LEFT JOIN simuze_cities c ON c.id = e.city_id
             WHERE e.status IN ('open','lineup_set','confirmed')
               AND e.event_date >= NOW()
               AND e.deleted_at IS NULL
             ORDER BY e.event_date ASC
             LIMIT " . intval($limit)
        ) ?: [];
    }

    /**
     * Past events, optionally filtered by city_id.
     */
    public static function findPast(int $limit = 10, ?int $city_id = null): array {
        if ($city_id) {
            return md_db_query(
                "SELECT e.*, c.name AS city_name, c.slug AS city_slug
                 FROM simuze_events e
                 LEFT JOIN simuze_cities c ON c.id = e.city_id
                 WHERE e.event_date < NOW()
                   AND e.deleted_at IS NULL
                   AND e.city_id = ?
                 ORDER BY e.event_date DESC
                 LIMIT " . intval($limit),
                [$city_id]
            ) ?: [];
        }
        return md_db_query(
            "SELECT e.*, c.name AS city_name, c.slug AS city_slug
             FROM simuze_events e
             LEFT JOIN simuze_cities c ON c.id = e.city_id
             WHERE e.event_date < NOW()
               AND e.deleted_at IS NULL
             ORDER BY e.event_date DESC
             LIMIT " . intval($limit)
        ) ?: [];
    }

    /**
     * Events organised by a group or user.
     */
    public static function findByOrganiser(int $organiser_id, string $organiser_type = 'group', int $limit = 20): array {
        return md_db_query(
            "SELECT e.*, c.name AS city_name, c.slug AS city_slug
             FROM simuze_events e
             LEFT JOIN simuze_cities c ON c.id = e.city_id
             WHERE e.organiser_id = ? AND e.organiser_type = ? AND e.deleted_at IS NULL
             ORDER BY e.event_date DESC
             LIMIT " . intval($limit),
            [$organiser_id, $organiser_type]
        ) ?: [];
    }

    /**
     * Events a group is participating in (as a slot).
     */
    public static function findByGroupSlot(int $group_id, int $limit = 20): array {
        return md_db_query(
            "SELECT e.*, c.name AS city_name, c.slug AS city_slug
             FROM simuze_events e
             LEFT JOIN simuze_cities c ON c.id = e.city_id
             INNER JOIN simuze_event_slots s ON s.event_id = e.id AND s.group_id = ? AND s.confirmed = 1
             WHERE e.deleted_at IS NULL
             ORDER BY e.event_date DESC
             LIMIT " . intval($limit),
            [$group_id]
        ) ?: [];
    }

    /**
     * Create a new event. Returns the new event id or null on failure.
     */
    public static function create(array $data): ?int {
        $now = date('Y-m-d H:i:s');
        $slug = self::generateSlug($data['title'] ?? 'show', $data['event_date'] ?? $now);
        $genres = isset($data['genres']) ? json_encode($data['genres']) : null;

        md_db_insert('simuze_events', [
            'title'          => $data['title'] ?? '',
            'slug'           => $slug,
            'type'           => $data['type'] ?? 'show',
            'organiser_id'   => $data['organiser_id'],
            'organiser_type' => $data['organiser_type'] ?? 'group',
            'city_id'        => $data['city_id'] ?? null,
            'venue_id'       => $data['venue_id'] ?? null,
            'venue_label'    => $data['venue_label'] ?? null,
            'address_hidden' => (int) ($data['address_hidden'] ?? 0),
            'event_date'     => $data['event_date'],
            'doors_time'     => $data['doors_time'] ?? null,
            'capacity'       => $data['capacity'] ?? null,
            'genres'         => $genres,
            'open_slots'     => (int) ($data['open_slots'] ?? 0),
            'description'    => $data['description'] ?? null,
            'diy_notes'      => $data['diy_notes'] ?? null,
            'status'         => $data['status'] ?? 'open',
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
        $id = md_db_var('SELECT id FROM simuze_events WHERE slug = ? LIMIT 1', [$slug]);
        return $id ? (int) $id : null;
    }

    /**
     * Update a field set on an event.
     */
    public static function update(int $id, array $data): void {
        $data['updated_at'] = date('Y-m-d H:i:s');
        md_db_update('simuze_events', $data, ['id' => $id]);
    }

    /**
     * Soft-delete an event.
     */
    public static function delete(int $id): void {
        self::update($id, ['deleted_at' => date('Y-m-d H:i:s'), 'status' => 'cancelled']);
    }

    // ------------------------------------------------------------------
    // Slots
    // ------------------------------------------------------------------

    public static function findSlotsByEvent(int $event_id): array {
        return md_db_query(
            "SELECT s.*, g.name AS group_name, g.unique_name AS group_slug, g.avatar_url AS group_avatar
             FROM simuze_event_slots s
             LEFT JOIN simuze_groups g ON g.id = s.group_id
             WHERE s.event_id = ?
             ORDER BY s.slot_order ASC, s.id ASC",
            [$event_id]
        ) ?: [];
    }

    public static function addSlot(int $event_id, array $data): void {
        md_db_insert('simuze_event_slots', [
            'event_id'   => $event_id,
            'group_id'   => $data['group_id'] ?? null,
            'user_id'    => $data['user_id'] ?? null,
            'slot_type'  => $data['slot_type'] ?? 'tbc',
            'confirmed'  => (int) ($data['confirmed'] ?? 0),
            'set_time'   => $data['set_time'] ?? null,
            'slot_order' => $data['slot_order'] ?? 0,
            'notes'      => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function confirmSlot(int $slot_id): void {
        md_db_update('simuze_event_slots', ['confirmed' => 1], ['id' => $slot_id]);
    }

    public static function removeSlot(int $slot_id): void {
        md_db_query('DELETE FROM simuze_event_slots WHERE id = ?', [$slot_id]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Generate a unique URL slug from title + date.
     */
    private static function generateSlug(string $title, string $date): string {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $base = trim($base, '-');
        $year = substr($date, 0, 4);
        $candidate = $base . '-' . $year;
        $n = 1;
        while (md_db_var('SELECT id FROM simuze_events WHERE slug = ? LIMIT 1', [$candidate])) {
            $candidate = $base . '-' . $year . '-' . $n;
            $n++;
        }
        return $candidate;
    }

    /**
     * Find or create a city by name, returning its id.
     */
    public static function findOrCreateCity(string $name, string $country = ''): ?int {
        if ($name === '') return null;
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
        $id = md_db_var('SELECT id FROM simuze_cities WHERE slug = ? LIMIT 1', [$slug]);
        if ($id) return (int) $id;
        md_db_insert('simuze_cities', [
            'name'    => $name,
            'slug'    => $slug,
            'country' => $country,
        ]);
        return (int) md_db_var('SELECT id FROM simuze_cities WHERE slug = ? LIMIT 1', [$slug]);
    }

    /**
     * All cities with upcoming show counts.
     */
    public static function findCitiesWithShows(int $limit = 50): array {
        return md_db_query(
            "SELECT c.*, COUNT(e.id) AS show_count
             FROM simuze_cities c
             LEFT JOIN simuze_events e ON e.city_id = c.id
               AND e.status IN ('open','lineup_set','confirmed')
               AND e.event_date >= NOW()
               AND e.deleted_at IS NULL
             GROUP BY c.id
             ORDER BY show_count DESC, c.name ASC
             LIMIT " . intval($limit)
        ) ?: [];
    }
}
