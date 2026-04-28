<?php
/**
 * EventRepository
 *
 * All data access for simuze_events and simuze_event_slots.
 * Uses md_db_*() helpers — no $wpdb dependency.
 */
class EventRepository {
    private static array $columnCache = [];

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
        $cityCols = self::citySelectColumns();
        if ($city_id) {
            return md_db_query(
                "SELECT e.*, {$cityCols}
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
            "SELECT e.*, {$cityCols}
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
        $cityCols = self::citySelectColumns();
        if ($city_id) {
            return md_db_query(
                "SELECT e.*, {$cityCols}
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
            "SELECT e.*, {$cityCols}
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
        $cityCols = self::citySelectColumns();
        return md_db_query(
            "SELECT e.*, {$cityCols}
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
        $cityCols = self::citySelectColumns();
        return md_db_query(
            "SELECT e.*, {$cityCols}
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

        $row = [
            'title'          => $data['title'] ?? '',
            'slug'           => $slug,
            'type'           => $data['type'] ?? 'show',
            'organiser_id'   => isset($data['organiser_id']) ? ($data['organiser_id'] !== null ? (int) $data['organiser_id'] : null) : null,
            // DB schema requires organiser_type NOT NULL (default 'group').
            // Allow organiser_id to be NULL (anonymous) but keep organiser_type valid.
            'organiser_type' => !empty($data['organiser_type']) ? $data['organiser_type'] : (!empty($data['organiser_id']) ? 'user' : 'group'),
            'city_id'        => $data['city_id'] ?? null,
            'venue_id'       => $data['venue_id'] ?? null,
            'venue_label'    => $data['venue_label'] ?? null,
            'address_hidden' => (int) ($data['address_hidden'] ?? 0),
            'event_date'     => $data['event_date'],
            'doors_time'     => $data['doors_time'] ?? null,
            'capacity'       => $data['capacity'] ?? null,
            'genres'         => $genres,
            'mood'           => $data['mood'] ?? null,
            'open_slots'     => (int) ($data['open_slots'] ?? 0),
            'description'    => $data['description'] ?? null,
            'diy_notes'      => $data['diy_notes'] ?? null,
            'contact_name'   => isset($data['contact_name']) && $data['contact_name'] !== '' ? $data['contact_name'] : null,
            'contact_email'  => isset($data['contact_email']) && $data['contact_email'] !== '' ? $data['contact_email'] : null,
            'status'         => $data['status'] ?? 'open',
            'created_at'     => $now,
            'updated_at'     => $now,
        ];
        if (self::hasColumn('simuze_events', 'ticket_price')) {
            $row['ticket_price'] = isset($data['ticket_price']) ? (float) $data['ticket_price'] : 0;
        }
        if (self::hasColumn('simuze_events', 'ticket_currency')) {
            $row['ticket_currency'] = !empty($data['ticket_currency']) ? strtoupper((string) $data['ticket_currency']) : 'EUR';
        }
        if (self::hasColumn('simuze_events', 'ticket_sales_enabled')) {
            $row['ticket_sales_enabled'] = isset($data['ticket_sales_enabled']) ? (int) $data['ticket_sales_enabled'] : ((float) ($data['ticket_price'] ?? 0) > 0 ? 1 : 0);
        }
        if (self::hasColumn('simuze_events', 'ticket_type')) {
            $tt = $data['ticket_type'] ?? 'none';
            $row['ticket_type'] = in_array($tt, ['none','simuze','external'], true) ? $tt : 'none';
        }
        if (self::hasColumn('simuze_events', 'ticket_url')) {
            $row['ticket_url'] = !empty($data['ticket_url']) ? $data['ticket_url'] : null;
        }
        if (self::hasColumn('simuze_events', 'header_image_url')) {
            $row['header_image_url'] = !empty($data['header_image_url']) ? $data['header_image_url'] : null;
        }
        if (self::hasColumn('simuze_events', 'genres') && isset($data['genres'])) {
            // Accept already-encoded JSON string or array
            $row['genres'] = is_array($data['genres']) ? json_encode($data['genres']) : $data['genres'];
        }
        md_db_insert('simuze_events', $row);
        // Use LAST_INSERT_ID() first (reliable), fall back to slug lookup
        $last_id = md_db_var('SELECT LAST_INSERT_ID()');
        $id = $last_id ? $last_id : md_db_var('SELECT id FROM simuze_events WHERE slug = ? LIMIT 1', [$slug]);
        if ($id) {
            $event_id = (int) $id;
            // Log to simuze_activity
            try {
                $act_author_type = !empty($row['organiser_type']) ? $row['organiser_type'] : 'user';
                $act_author_id   = !empty($row['organiser_id'])   ? (int) $row['organiser_id'] : 0;
                if ($act_author_id > 0) {
                    md_db_insert('simuze_activity', [
                        'author_type' => $act_author_type,
                        'author_id'   => $act_author_id,
                        'content'     => 'show_created',
                        'extra'       => json_encode(['entity_type' => 'event', 'entity_id' => $event_id, 'title' => $row['title'], 'slug' => $slug]),
                        'created_at'  => $now,
                    ]);
                }
            } catch (\Throwable $e) {
                // Activity logging is non-critical — fail silently
            }
        }
        return isset($event_id) ? $event_id : null;
    }

    /**
     * Update a field set on an event.
     */
    public static function update(int $id, array $data): void {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $filtered = [];
        foreach ($data as $k => $v) {
            if ($k === 'updated_at' || self::hasColumn('simuze_events', (string) $k)) {
                $filtered[$k] = $v;
            }
        }
        md_db_update('simuze_events', $filtered, ['id' => $id]);
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
            "SELECT s.*,
                    g.name AS group_name, g.unique_name AS group_slug, g.avatar_url AS group_avatar,
                    u.display_name AS user_display_name, u.username AS user_username, u.avatar_url AS user_avatar
             FROM simuze_event_slots s
             LEFT JOIN simuze_groups g ON g.id = s.group_id
             LEFT JOIN simuze_users  u ON u.id = s.user_id
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
    // Show slot pool (band availability) + invitations
    // ------------------------------------------------------------------

    /**
     * Create one or more pool rows for a band group.
     *
     * Each location entry results in a separate row, so organizers can
     * choose from a clean pool with a specific location filter.
     *
     * Expected location entry formats:
     * - ['location_level'=>'country','country'=>string]
     * - ['location_level'=>'province','province'=>string]
     * - ['location_level'=>'city','city_id'=>int]
     *
     * Returns inserted pool row ids (empty array on failure).
     */
    public static function createShowSlotPool(
        int $band_group_id,
        int $creator_user_id,
        array $locations,
        ?string $from_date,
        ?string $to_date
    ): array {
        if ($band_group_id <= 0 || $creator_user_id <= 0) return [];
        $now = date('Y-m-d H:i:s');

        $from = $from_date ? trim((string) $from_date) : null;
        $to   = $to_date ? trim((string) $to_date) : null;

        $from = ($from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) ? $from : null;
        $to   = ($to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) ? $to : null;

        if ($from && $to && $from > $to) {
            // Swap to keep a valid range instead of failing the entire request.
            [$from, $to] = [$to, $from];
        }

        $inserted = [];
        foreach ($locations as $loc) {
            if (!is_array($loc) || empty($loc['location_level'])) continue;

            $level = (string) $loc['location_level'];
            $row = [
                'band_group_id'      => $band_group_id,
                'location_level'     => in_array($level, ['country', 'province', 'city'], true) ? $level : 'city',
                'country'            => '',
                'province'           => '',
                'city_id'            => null,
                'from_date'          => $from,
                'to_date'            => $to,
                'created_by_user_id' => $creator_user_id,
                'status'             => 'active',
                'created_at'         => $now,
                'updated_at'         => $now,
            ];

            if ($row['location_level'] === 'country') {
                $country = trim((string) ($loc['country'] ?? ''));
                if ($country === '') continue;
                $row['country'] = $country;
            } elseif ($row['location_level'] === 'province') {
                $province = trim((string) ($loc['province'] ?? ''));
                if ($province === '') continue;
                $row['province'] = $province;
            } else {
                $city_id = (int) ($loc['city_id'] ?? 0);
                if ($city_id <= 0) continue;
                $row['city_id'] = $city_id;
                // Store city metadata for easy display and (optionally) matching.
                $city = md_db_row('SELECT country, province FROM simuze_cities WHERE id = ?', [$city_id]);
                if ($city) {
                    $row['country'] = (string) ($city['country'] ?? '');
                    $row['province'] = (string) ($city['province'] ?? '');
                }
            }

            $id = md_db_insert('simuze_show_slot_pool', $row);
            if ($id) {
                $inserted[] = (int) $id;
            }
        }

        return $inserted;
    }

    /**
     * Get pool entries matching an event (show city + show date range).
     *
     * Matching rules:
     * - date-only match against from_date/to_date if provided
     * - location match based on location_level (city/province/country)
     * - hides pool rows when the band already has a slot (pending or confirmed)
     *   for this event (so organizers don't duplicate invitations).
     */
    public static function getShowSlotPoolForEvent(int $event_id): array {
        $event = self::findById($event_id);
        if (!$event || empty($event['city_id']) || empty($event['event_date'])) return [];

        $city_id = (int) $event['city_id'];
        $city = md_db_row(
            'SELECT id, name, country, province FROM simuze_cities WHERE id = ?',
            [$city_id]
        );
        if (!$city) return [];

        $show_date = date('Y-m-d', strtotime((string) $event['event_date']));

        $pool = md_db_query(
            "SELECT
                p.*,
                g.name AS band_name,
                g.unique_name AS band_unique_name,
                g.unique_handle AS band_unique_handle,
                g.avatar_url AS band_avatar
             FROM simuze_show_slot_pool p
             INNER JOIN simuze_groups g ON g.id = p.band_group_id AND g.deleted_at IS NULL
             WHERE p.status = 'active'
               AND (
                    (p.location_level = 'city' AND p.city_id = ?)
                 OR (p.location_level = 'country' AND p.country <> '' AND p.country = ?)
                 OR (p.location_level = 'province' AND p.province <> '' AND p.province = ?)
               )
               AND (p.from_date IS NULL OR p.from_date <= ?)
               AND (p.to_date IS NULL OR p.to_date >= ?)
               AND NOT EXISTS (
                    SELECT 1 FROM simuze_event_slots s
                    WHERE s.event_id = ? AND s.group_id = p.band_group_id
               )
             ORDER BY p.created_at DESC
             LIMIT 50",
            [
                $city_id,
                (string) ($city['country'] ?? ''),
                (string) ($city['province'] ?? ''),
                $show_date,
                $show_date,
                $event_id,
            ]
        );

        return $pool ?: [];
    }

    /**
     * Assign a pool entry to a show slot: create a pending slot + invitation,
     * then delete the pool row so the request disappears from the pool.
     */
    public static function assignShowSlotFromPool(
        int $event_id,
        int $pool_entry_id,
        int $created_by_user_id
    ): ?array {
        $event = self::findById($event_id);
        if (!$event) return null;

        $pool = md_db_row(
            "SELECT * FROM simuze_show_slot_pool WHERE id = ? AND status = 'active' LIMIT 1",
            [$pool_entry_id]
        );
        if (!$pool) return null;

        if (empty($event['city_id']) || empty($event['event_date'])) return null;
        $city_id = (int) $event['city_id'];
        $show_date = date('Y-m-d', strtotime((string) $event['event_date']));

        $city = md_db_row(
            'SELECT id, country, province FROM simuze_cities WHERE id = ?',
            [$city_id]
        );
        if (!$city) return null;

        // Enforce match server-side (do not trust organizer UI).
        $loc_ok = false;
        if (($pool['location_level'] ?? '') === 'city') {
            $loc_ok = ((int) ($pool['city_id'] ?? 0)) === $city_id;
        } elseif (($pool['location_level'] ?? '') === 'country') {
            $loc_ok = !empty($pool['country']) && (string) $pool['country'] === (string) ($city['country'] ?? '');
        } elseif (($pool['location_level'] ?? '') === 'province') {
            $loc_ok = !empty($pool['province']) && (string) $pool['province'] === (string) ($city['province'] ?? '');
        }

        $time_ok = true;
        if (!empty($pool['from_date']) && $show_date < (string) $pool['from_date']) {
            $time_ok = false;
        }
        if (!empty($pool['to_date']) && $show_date > (string) $pool['to_date']) {
            $time_ok = false;
        }

        if (!$loc_ok || !$time_ok) return null;

        $band_group_id = (int) ($pool['band_group_id'] ?? 0);
        if ($band_group_id <= 0) return null;

        // Prevent duplicates (pending or confirmed) for this event+band.
        $exists_slot = md_db_var(
            "SELECT 1 FROM simuze_event_slots WHERE event_id = ? AND group_id = ? LIMIT 1",
            [$event_id, $band_group_id]
        );
        if ($exists_slot) return null;

        $now = date('Y-m-d H:i:s');
        $slot_order = (int) (md_db_var(
            "SELECT COALESCE(MAX(slot_order),0) + 1 FROM simuze_event_slots WHERE event_id = ?",
            [$event_id]
        ) ?: 0);

        $slot_id = md_db_insert('simuze_event_slots', [
            'event_id'   => $event_id,
            'group_id'   => $band_group_id,
            'user_id'    => null,
            'slot_type'  => 'tbc',
            'confirmed'  => 0,
            'set_time'   => null,
            'slot_order' => $slot_order,
            'notes'      => null,
            'created_at' => $now,
        ]);

        if (!$slot_id) return null;

        $invitation_id = md_db_insert('simuze_event_slot_invitations', [
            'event_id'           => $event_id,
            'slot_id'            => (int) $slot_id,
            'band_group_id'      => $band_group_id,
            'pool_entry_id'     => (int) $pool_entry_id,
            'status'             => 'pending',
            'created_by_user_id' => $created_by_user_id,
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);

        if (!$invitation_id) {
            // Best-effort cleanup: avoid orphan pending slots.
            self::removeSlot((int) $slot_id);
            return null;
        }

        // Cleanup: pool request disappears once consumed by the organizer.
        md_db_delete('simuze_show_slot_pool', ['id' => (int) $pool_entry_id]);

        return [
            'slot_id' => (int) $slot_id,
            'invitation_id' => (int) $invitation_id,
            'band_group_id' => $band_group_id,
        ];
    }

    public static function acceptShowSlotInvitation(int $invitation_id, int $actor_user_id): bool {
        if ($invitation_id <= 0 || $actor_user_id <= 0) return false;

        $inv = md_db_row(
            "SELECT * FROM simuze_event_slot_invitations WHERE id = ? AND status = 'pending' LIMIT 1",
            [$invitation_id]
        );
        if (!$inv) return false;

        $band_group_id = (int) ($inv['band_group_id'] ?? 0);
        $slot_id = (int) ($inv['slot_id'] ?? 0);
        $event_id = (int) ($inv['event_id'] ?? 0);
        if ($band_group_id <= 0 || $slot_id <= 0 || $event_id <= 0) return false;

        $group = class_exists('Simuze_Group') ? Simuze_Group::get($band_group_id) : null;
        if (!$group || !$group->can_manage($actor_user_id)) return false;

        $now = date('Y-m-d H:i:s');
        md_db_update(
            'simuze_event_slot_invitations',
            ['status' => 'accepted', 'updated_at' => $now],
            ['id' => $invitation_id]
        );

        self::confirmSlot($slot_id);

        $event = self::findById($event_id);
        if ($event) {
            $open_slots = (int) ($event['open_slots'] ?? 0);
            $new_open_slots = max(0, $open_slots - 1);
            $new_status = $new_open_slots === 0 ? 'confirmed' : 'lineup_set';
            self::update($event_id, [
                'open_slots' => $new_open_slots,
                'status' => $new_status,
            ]);
        }

        return true;
    }

    public static function declineShowSlotInvitation(int $invitation_id, int $actor_user_id): bool {
        if ($invitation_id <= 0 || $actor_user_id <= 0) return false;

        $inv = md_db_row(
            "SELECT * FROM simuze_event_slot_invitations WHERE id = ? AND status = 'pending' LIMIT 1",
            [$invitation_id]
        );
        if (!$inv) return false;

        $band_group_id = (int) ($inv['band_group_id'] ?? 0);
        $slot_id = (int) ($inv['slot_id'] ?? 0);
        if ($band_group_id <= 0 || $slot_id <= 0) return false;

        $group = class_exists('Simuze_Group') ? Simuze_Group::get($band_group_id) : null;
        if (!$group || !$group->can_manage($actor_user_id)) return false;

        $now = date('Y-m-d H:i:s');
        md_db_update(
            'simuze_event_slot_invitations',
            ['status' => 'declined', 'updated_at' => $now],
            ['id' => $invitation_id]
        );

        // Decline removes the pending lineup entry; open_slots stay unchanged.
        self::removeSlot($slot_id);

        return true;
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
    public static function findOrCreateCity(string $name, string $country = '', string $province = ''): ?int {
        if ($name === '') return null;
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
        $id = md_db_var('SELECT id FROM simuze_cities WHERE slug = ? LIMIT 1', [$slug]);
        if ($id) return (int) $id;
        $cityRow = [
            'name'    => $name,
            'slug'    => $slug,
            'country' => $country,
        ];
        if (self::hasColumn('simuze_cities', 'province')) {
            $cityRow['province'] = $province;
        }
        md_db_insert('simuze_cities', $cityRow);
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

    public static function getUpcomingFilterOptions(): array {
        $countries = md_db_query(
            "SELECT c.country, COUNT(e.id) AS count
             FROM simuze_cities c
             INNER JOIN simuze_events e ON e.city_id = c.id
             WHERE e.status IN ('open','lineup_set','confirmed')
               AND e.event_date >= NOW()
               AND e.deleted_at IS NULL
               AND c.country <> ''
             GROUP BY c.country
             ORDER BY c.country ASC"
        ) ?: [];
        $provinces = md_db_query(
            "SELECT c.province, COUNT(e.id) AS count
             FROM simuze_cities c
             INNER JOIN simuze_events e ON e.city_id = c.id
             WHERE e.status IN ('open','lineup_set','confirmed')
               AND e.event_date >= NOW()
               AND e.deleted_at IS NULL
               AND c.province <> ''
             GROUP BY c.province
             ORDER BY c.province ASC"
        ) ?: [];
        $cities = md_db_query(
            "SELECT c.id, c.name, COUNT(e.id) AS count
             FROM simuze_cities c
             INNER JOIN simuze_events e ON e.city_id = c.id
             WHERE e.status IN ('open','lineup_set','confirmed')
               AND e.event_date >= NOW()
               AND e.deleted_at IS NULL
             GROUP BY c.id, c.name
             ORDER BY c.name ASC"
        ) ?: [];
        return ['countries' => $countries, 'provinces' => $provinces, 'cities' => $cities];
    }

    public static function searchUpcoming(array $args = []): array {
        $limit = isset($args['limit']) ? max(1, min(100, (int) $args['limit'])) : 20;
        $offset = isset($args['offset']) ? max(0, (int) $args['offset']) : 0;
        $country = trim((string) ($args['country'] ?? ''));
        $province = trim((string) ($args['province'] ?? ''));
        $city_id = (int) ($args['city_id'] ?? 0);
        $genre = trim((string) ($args['genre'] ?? ''));
        $q = trim((string) ($args['q'] ?? ''));
        $sort = trim((string) ($args['sort'] ?? 'date_asc'));

        $where = [
            "e.status IN ('open','lineup_set','confirmed')",
            "e.event_date >= NOW()",
            "e.deleted_at IS NULL",
        ];
        $params = [];
        if ($country !== '') {
            $where[] = 'c.country = ?';
            $params[] = $country;
        }
        if ($province !== '' && self::hasColumn('simuze_cities', 'province')) {
            $where[] = 'c.province = ?';
            $params[] = $province;
        }
        if ($city_id > 0) {
            $where[] = 'e.city_id = ?';
            $params[] = $city_id;
        }
        if ($genre !== '') {
            $where[] = 'JSON_SEARCH(e.genres, "one", ?) IS NOT NULL';
            $params[] = $genre;
        }
        if ($q !== '') {
            $where[] = "(e.title LIKE ? OR e.venue_label LIKE ? OR c.name LIKE ?
                OR EXISTS (
                    SELECT 1 FROM simuze_groups og
                    WHERE og.id = e.organiser_id
                      AND e.organiser_type = 'group'
                      AND og.name LIKE ?
                )
                OR EXISTS (
                    SELECT 1 FROM simuze_event_slots es
                    INNER JOIN simuze_groups sg ON sg.id = es.group_id
                    WHERE es.event_id = e.id
                      AND sg.name LIKE ?
                )
            )";
            $like = '%' . md_db_esc_like($q) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $orderBy = $sort === 'latest_added' ? 'e.created_at DESC' : 'e.event_date ASC';
        $cityCols = self::citySelectColumns();
        return md_db_query(
            "SELECT e.*, {$cityCols}
             FROM simuze_events e
             LEFT JOIN simuze_cities c ON c.id = e.city_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY {$orderBy}
             LIMIT {$limit} OFFSET {$offset}",
            $params
        ) ?: [];
    }

    private static function citySelectColumns(): string {
        if (self::hasColumn('simuze_cities', 'province')) {
            return "c.name AS city_name, c.slug AS city_slug, c.country AS city_country, c.province AS city_province";
        }
        return "c.name AS city_name, c.slug AS city_slug, c.country AS city_country, '' AS city_province";
    }

    private static function hasColumn(string $table, string $column): bool {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }
        $exists = (int) md_db_var(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?",
            [$table, $column]
        ) > 0;
        self::$columnCache[$key] = $exists;
        return $exists;
    }
}

function simuze_ajax_filter_shows() {
    $nonce = $_POST['nonce'] ?? $_POST['_csrf_token'] ?? '';

    $valid = false;
    if (function_exists('md_verify_csrf')) {
        try {
            if (md_verify_csrf()) $valid = true;
        } catch (\Throwable $e) {
            // ignore
        }
    }

    if (!$valid && !empty($nonce) && function_exists('md_csrf_token')) {
        $valid = hash_equals(md_csrf_token(), (string) $nonce);
    }

    if (!$valid) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'data' => 'Invalid token']);
        exit;
    }
    $events = EventRepository::searchUpcoming([
        'limit' => (int) ($_POST['limit'] ?? 30),
        'offset' => (int) ($_POST['offset'] ?? 0),
        'country' => trim((string) ($_POST['country'] ?? '')),
        'province' => trim((string) ($_POST['province'] ?? '')),
        'city_id' => (int) ($_POST['city_id'] ?? 0),
        'genre' => trim((string) ($_POST['genre'] ?? '')),
        'sort' => trim((string) ($_POST['sort'] ?? 'date_asc')),
        'q' => trim((string) ($_POST['q'] ?? '')),
    ]);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $events]);
    exit;
}

if (function_exists('add_action')) {
    add_action('wp_ajax_simuze_filter_shows', 'simuze_ajax_filter_shows');
    add_action('wp_ajax_nopriv_simuze_filter_shows', 'simuze_ajax_filter_shows');
}

/**
 * AJAX handler — organizer can update their show fields.
 */
function simuze_ajax_update_event() {
    header('Content-Type: application/json');

    $nonce = $_POST['nonce'] ?? $_POST['_csrf_token'] ?? '';
    $valid = false;

    if (function_exists('md_verify_csrf')) {
        try {
            // Some implementations validate against $_POST automatically.
            if (md_verify_csrf()) $valid = true;
        } catch (\Throwable $e) {
            // ignore
        }
        if (!$valid && !empty($nonce) && function_exists('md_csrf_token')) {
            $valid = hash_equals(md_csrf_token(), (string) $nonce);
        }
        if (!$valid && !empty($nonce)) {
            try {
                if (md_verify_csrf($nonce)) $valid = true;
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    if (!$valid) {
        echo json_encode(['success' => false, 'data' => 'Invalid token']);
        exit;
    }

    $event_id = (int) ($_POST['event_id'] ?? 0);
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'data' => 'Missing event_id']);
        exit;
    }

    $show = EventRepository::findById($event_id);
    if (!$show) {
        echo json_encode(['success' => false, 'data' => 'Show not found']);
        exit;
    }

    $current_uid = 0;
    if (class_exists('Simuze_Auth')) {
        try {
            $auth = Simuze_Auth::get_instance();
            $u = $auth->get_current_user();
            if ($u && !empty($u->id)) {
                $current_uid = (int) $u->id;
            }
        } catch (\Throwable $e) {
            $current_uid = 0;
        }
    } elseif (!empty($GLOBALS['current_user']) && !empty($GLOBALS['current_user']->id)) {
        $current_uid = (int) $GLOBALS['current_user']->id;
    }

    if ($current_uid <= 0) {
        echo json_encode(['success' => false, 'data' => 'Not authenticated']);
        exit;
    }

    $can_manage = false;
    if (($show['organiser_type'] ?? 'group') === 'user') {
        $can_manage = ((int) ($show['organiser_id'] ?? 0) === $current_uid);
    } else {
        if (class_exists('Simuze_Group')) {
            $group = Simuze_Group::get((int) ($show['organiser_id'] ?? 0));
            if ($group && method_exists($group, 'can_manage') && $group->can_manage($current_uid)) {
                $can_manage = true;
            }
        }
    }

    if (!$can_manage) {
        echo json_encode(['success' => false, 'data' => 'Forbidden']);
        exit;
    }

    $data = [];

    if (isset($_POST['open_slots'])) {
        $data['open_slots'] = max(0, min(20, (int) $_POST['open_slots']));
    }

    if (array_key_exists('capacity', $_POST)) {
        $cap = max(0, (int) $_POST['capacity']);
        $data['capacity'] = $cap > 0 ? $cap : null;
    }

    if (isset($_POST['ticket_price'])) {
        $tp = (float) $_POST['ticket_price'];
        if ($tp < 0) $tp = 0.0;
        if ($tp > 99999.99) $tp = 99999.99;
        $data['ticket_price'] = $tp;
        $data['ticket_currency'] = 'EUR';
        $data['ticket_sales_enabled'] = $tp > 0 ? 1 : 0;
    }

    if (isset($_POST['description'])) {
        $desc = trim((string) ($_POST['description'] ?? ''));
        $data['description'] = $desc !== '' ? $desc : null;
    }

    if (isset($_POST['diy_notes'])) {
        $notes = trim((string) ($_POST['diy_notes'] ?? ''));
        $data['diy_notes'] = $notes !== '' ? $notes : null;
    }

    if (isset($_POST['venue_label'])) {
        $vlabel = trim((string) ($_POST['venue_label'] ?? ''));
        $data['venue_label'] = $vlabel !== '' ? $vlabel : null;
    }

    if (!empty($data)) {
        EventRepository::update($event_id, $data);
    }

    echo json_encode(['success' => true, 'data' => ['updated' => true]]);
    exit;
}

if (function_exists('add_action')) {
    add_action('wp_ajax_simuze_update_event', 'simuze_ajax_update_event');
    add_action('wp_ajax_nopriv_simuze_update_event', 'simuze_ajax_update_event');
}

// ------------------------------------------------------------------
// Show slot pool AJAX handlers
// ------------------------------------------------------------------

function simuze_community_verify_csrf_or_nonce(): bool {
    $nonce = $_POST['nonce'] ?? $_POST['_csrf_token'] ?? '';
    $valid = false;

    if (function_exists('md_verify_csrf')) {
        try {
            if (md_verify_csrf()) {
                $valid = true;
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    if (!$valid && !empty($nonce) && function_exists('md_csrf_token')) {
        $valid = hash_equals(md_csrf_token(), (string) $nonce);
    }

    return (bool) $valid;
}

function simuze_community_current_user_id(): int {
    if (class_exists('Simuze_Auth')) {
        try {
            $auth = Simuze_Auth::get_instance();
            $u = $auth->get_current_user();
            if ($u && !empty($u->id)) return (int) $u->id;
        } catch (\Throwable $e) {
            return 0;
        }
    }
    if (!empty($GLOBALS['current_user']) && !empty($GLOBALS['current_user']->id)) {
        return (int) $GLOBALS['current_user']->id;
    }
    return 0;
}

function simuze_community_can_manage_event(array $show, int $current_uid): bool {
    if (empty($show['id'])) return false;
    if ($current_uid <= 0) return false;

    $organiser_type = $show['organiser_type'] ?? 'group';
    if ($organiser_type === 'user') {
        return ((int) ($show['organiser_id'] ?? 0) === $current_uid);
    }

    if (class_exists('Simuze_Group')) {
        $group = Simuze_Group::get((int) ($show['organiser_id'] ?? 0));
        return (bool) ($group && method_exists($group, 'can_manage') && $group->can_manage($current_uid));
    }

    return false;
}

/**
 * Band creates/updates availability pool rows (location(s) + optional date range).
 */
function simuze_ajax_create_show_slot_pool() {
    header('Content-Type: application/json; charset=utf-8');

    if (!simuze_community_verify_csrf_or_nonce()) {
        echo json_encode(['success' => false, 'data' => 'Invalid token']);
        exit;
    }

    $current_uid = simuze_community_current_user_id();
    if ($current_uid <= 0) {
        echo json_encode(['success' => false, 'data' => 'Not authenticated']);
        exit;
    }

    $band_unique_name = trim((string) ($_POST['band_unique_name'] ?? ''));
    $from_date = isset($_POST['from_date']) ? trim((string) $_POST['from_date']) : null;
    $to_date   = isset($_POST['to_date']) ? trim((string) $_POST['to_date']) : null;

    $locations_json = $_POST['locations_json'] ?? [];
    $locations = [];
    if (is_string($locations_json)) {
        $decoded = json_decode($locations_json, true);
        $locations = is_array($decoded) ? $decoded : [];
    } elseif (is_array($locations_json)) {
        $locations = $locations_json;
    }

    if ($band_unique_name === '') {
        echo json_encode(['success' => false, 'data' => 'Missing band unique name']);
        exit;
    }
    if (empty($locations)) {
        echo json_encode(['success' => false, 'data' => 'Select at least one location']);
        exit;
    }

    $group = class_exists('Simuze_Group') ? Simuze_Group::get_by_unique_name($band_unique_name) : null;
    if (!$group) {
        echo json_encode(['success' => false, 'data' => 'Band group not found']);
        exit;
    }

    $type_slug = method_exists($group, 'get_type_slug') ? $group->get_type_slug() : null;
    if ($type_slug !== 'artist') {
        echo json_encode(['success' => false, 'data' => 'Only artist groups can create show availability']);
        exit;
    }

    if (!$group->can_manage($current_uid)) {
        echo json_encode(['success' => false, 'data' => 'Forbidden']);
        exit;
    }

    $inserted = EventRepository::createShowSlotPool(
        (int) $group->id,
        $current_uid,
        $locations,
        $from_date,
        $to_date
    );

    if (empty($inserted)) {
        echo json_encode(['success' => false, 'data' => 'Could not create availability']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => ['inserted_ids' => $inserted]]);
    exit;
}

/**
 * Organizer loads pool entries matching this event.
 */
function simuze_ajax_get_show_slot_pool_for_event() {
    header('Content-Type: application/json; charset=utf-8');

    if (!simuze_community_verify_csrf_or_nonce()) {
        echo json_encode(['success' => false, 'data' => 'Invalid token']);
        exit;
    }

    $current_uid = simuze_community_current_user_id();
    if ($current_uid <= 0) {
        echo json_encode(['success' => false, 'data' => 'Not authenticated']);
        exit;
    }

    $event_id = (int) ($_POST['event_id'] ?? 0);
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'data' => 'Missing event_id']);
        exit;
    }

    $show = EventRepository::findById($event_id);
    if (!$show) {
        echo json_encode(['success' => false, 'data' => 'Show not found']);
        exit;
    }

    if (!simuze_community_can_manage_event($show, $current_uid)) {
        echo json_encode(['success' => false, 'data' => 'Forbidden']);
        exit;
    }

    $matches = EventRepository::getShowSlotPoolForEvent($event_id);
    echo json_encode(['success' => true, 'data' => ['items' => $matches]]);
    exit;
}

/**
 * Organizer consumes a pool entry: creates pending slot + invitation,
 * deletes the pool request, and notifies band admins/mods.
 */
function simuze_ajax_assign_show_slot_from_pool() {
    header('Content-Type: application/json; charset=utf-8');

    if (!simuze_community_verify_csrf_or_nonce()) {
        echo json_encode(['success' => false, 'data' => 'Invalid token']);
        exit;
    }

    $current_uid = simuze_community_current_user_id();
    if ($current_uid <= 0) {
        echo json_encode(['success' => false, 'data' => 'Not authenticated']);
        exit;
    }

    $event_id = (int) ($_POST['event_id'] ?? 0);
    $pool_entry_id = (int) ($_POST['pool_entry_id'] ?? 0);
    if ($event_id <= 0 || $pool_entry_id <= 0) {
        echo json_encode(['success' => false, 'data' => 'Missing parameters']);
        exit;
    }

    $show = EventRepository::findById($event_id);
    if (!$show) {
        echo json_encode(['success' => false, 'data' => 'Show not found']);
        exit;
    }

    if (!simuze_community_can_manage_event($show, $current_uid)) {
        echo json_encode(['success' => false, 'data' => 'Forbidden']);
        exit;
    }

    $open_slots = (int) ($show['open_slots'] ?? 0);
    if ($open_slots <= 0) {
        echo json_encode(['success' => false, 'data' => 'No open slots remaining']);
        exit;
    }

    $assigned = EventRepository::assignShowSlotFromPool($event_id, $pool_entry_id, $current_uid);
    if (!$assigned) {
        echo json_encode(['success' => false, 'data' => 'Could not assign from pool']);
        exit;
    }

    // Notify band admins/mods via DM and email.
    $band_group_id = (int) ($assigned['band_group_id'] ?? 0);
    if ($band_group_id > 0 && class_exists('Simuze_Group')) {
        $band_group = Simuze_Group::get($band_group_id);
        $band_url = $band_group ? rtrim($band_group->get_url(), '/') . '/shows/' : md_home_url('/');

        $event_city = null;
        if (!empty($show['city_id'])) {
            $event_city = md_db_row(
                'SELECT name, country, province FROM simuze_cities WHERE id = ?',
                [(int) $show['city_id']]
            );
        }
        $city_label = '';
        if ($event_city) {
            $city_label = (string) ($event_city['name'] ?? '');
        }
        $event_date_fmt = !empty($show['event_date']) ? date('D j M Y', strtotime((string) $show['event_date'])) : '';
        $show_url = !empty($show['slug']) ? md_home_url('/show/' . $show['slug']) : md_home_url('/shows');

        $admins = md_db_query(
            "SELECT gm.user_id, u.email, u.display_name, u.username
             FROM simuze_group_members gm
             INNER JOIN simuze_users u ON u.id = gm.user_id
             WHERE gm.group_id = ?
               AND gm.status = 'active'
               AND gm.role IN ('admin','mod')
               AND u.deleted_at IS NULL",
            [$band_group_id]
        );

        $creator_name = $GLOBALS['current_user']->display_name ?? ($GLOBALS['current_user']->username ?? 'Someone');
        $creator_name = is_string($creator_name) ? $creator_name : 'Someone';

        $msg = sprintf(
            "Invitation: %s invited you to join a slot for \"%s\" on %s%s. Accept/decline in your group dashboard: %s",
            $creator_name,
            (string) ($show['title'] ?? 'a show'),
            $event_date_fmt,
            $city_label ? (' in ' . $city_label) : '',
            $band_url
        );

        if (!class_exists('Simuze_Message')) {
            $msgFile = __DIR__ . '/class-message.php';
            if (is_file($msgFile)) {
                require_once $msgFile;
            }
        }

        foreach ($admins as $admin) {
            $recipient_user_id = (int) ($admin['user_id'] ?? 0);
            if ($recipient_user_id <= 0) continue;

            // Internal DM (group context).
            try {
                if (class_exists('Simuze_Message')) {
                    Simuze_Message::send($current_uid, $recipient_user_id, $msg, null, $band_group_id);
                }
            } catch (\Throwable $e) {
                // Non-fatal
            }

            // Email notification.
            $email = trim((string) ($admin['email'] ?? ''));
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $admin_email = md_db_var("SELECT option_value FROM settings WHERE option_name = 'admin_email' LIMIT 1") ?? '';
                $site_name = md_db_var("SELECT option_value FROM settings WHERE option_name = 'site_name' LIMIT 1") ?? 'Simuze';

                $subject = sprintf('Show slot invitation: %s', (string) ($show['title'] ?? 'show'));
                $html = '<p>Hi ' . htmlspecialchars((string) ($admin['display_name'] ?? $admin['username'] ?? ''), ENT_QUOTES, 'UTF-8') . ',</p>'
                    . '<p><strong>' . htmlspecialchars($creator_name, ENT_QUOTES, 'UTF-8') . '</strong> invited you to join a slot for <strong>' . htmlspecialchars((string) $show['title'], ENT_QUOTES, 'UTF-8') . '</strong> ' .
                    ($event_date_fmt ? ('on ' . htmlspecialchars($event_date_fmt, ENT_QUOTES, 'UTF-8')) : '') .
                    ($city_label ? (' in ' . htmlspecialchars($city_label, ENT_QUOTES, 'UTF-8')) : '') .
                    '.</p>'
                    . '<p>Accept or decline here: <a href="' . htmlspecialchars($band_url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') . ' group dashboard</a></p>'
                    . '<p><small>If you decline, this specific availability request has already been consumed. You can submit new availability anytime.</small></p>';

                $headers = "Content-Type: text/html; charset=UTF-8\r\nFrom: Simuze <{$admin_email}>";
                try {
                    mail($email, $subject, $html, $headers);
                } catch (\Throwable $e) {
                    // Non-fatal
                }
            }
        }
    }

    echo json_encode(['success' => true, 'data' => ['assigned' => $assigned]]);
    exit;
}

function simuze_ajax_accept_show_slot_invitation() {
    header('Content-Type: application/json; charset=utf-8');

    if (!simuze_community_verify_csrf_or_nonce()) {
        echo json_encode(['success' => false, 'data' => 'Invalid token']);
        exit;
    }

    $current_uid = simuze_community_current_user_id();
    if ($current_uid <= 0) {
        echo json_encode(['success' => false, 'data' => 'Not authenticated']);
        exit;
    }

    $invitation_id = (int) ($_POST['invitation_id'] ?? 0);
    if ($invitation_id <= 0) {
        echo json_encode(['success' => false, 'data' => 'Missing invitation_id']);
        exit;
    }

    $ok = EventRepository::acceptShowSlotInvitation($invitation_id, $current_uid);
    if (!$ok) {
        echo json_encode(['success' => false, 'data' => 'Could not accept invitation']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => ['accepted' => true]]);
    exit;
}

function simuze_ajax_decline_show_slot_invitation() {
    header('Content-Type: application/json; charset=utf-8');

    if (!simuze_community_verify_csrf_or_nonce()) {
        echo json_encode(['success' => false, 'data' => 'Invalid token']);
        exit;
    }

    $current_uid = simuze_community_current_user_id();
    if ($current_uid <= 0) {
        echo json_encode(['success' => false, 'data' => 'Not authenticated']);
        exit;
    }

    $invitation_id = (int) ($_POST['invitation_id'] ?? 0);
    if ($invitation_id <= 0) {
        echo json_encode(['success' => false, 'data' => 'Missing invitation_id']);
        exit;
    }

    $ok = EventRepository::declineShowSlotInvitation($invitation_id, $current_uid);
    if (!$ok) {
        echo json_encode(['success' => false, 'data' => 'Could not decline invitation']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => ['declined' => true]]);
    exit;
}

if (function_exists('add_action')) {
    add_action('wp_ajax_simuze_create_show_slot_pool', 'simuze_ajax_create_show_slot_pool');
    add_action('wp_ajax_nopriv_simuze_create_show_slot_pool', 'simuze_ajax_create_show_slot_pool');

    add_action('wp_ajax_simuze_get_show_slot_pool_for_event', 'simuze_ajax_get_show_slot_pool_for_event');
    add_action('wp_ajax_nopriv_simuze_get_show_slot_pool_for_event', 'simuze_ajax_get_show_slot_pool_for_event');

    add_action('wp_ajax_simuze_assign_show_slot_from_pool', 'simuze_ajax_assign_show_slot_from_pool');
    add_action('wp_ajax_nopriv_simuze_assign_show_slot_from_pool', 'simuze_ajax_assign_show_slot_from_pool');

    add_action('wp_ajax_simuze_accept_show_slot_invitation', 'simuze_ajax_accept_show_slot_invitation');
    add_action('wp_ajax_nopriv_simuze_accept_show_slot_invitation', 'simuze_ajax_accept_show_slot_invitation');

    add_action('wp_ajax_simuze_decline_show_slot_invitation', 'simuze_ajax_decline_show_slot_invitation');
    add_action('wp_ajax_nopriv_simuze_decline_show_slot_invitation', 'simuze_ajax_decline_show_slot_invitation');
}
