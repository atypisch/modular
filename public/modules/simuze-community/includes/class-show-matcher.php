<?php
/**
 * ShowMatcher — rule-based artist ↔ show matching.
 *
 * Scoring:
 *   +3  genre match  (any show genre ∩ artist booking_preferences.genres)
 *   +2  mood match   (show mood == artist booking_preferences.mood)
 *   +2  same city    / +1 same province
 *   +1  show_type match (show type in artist booking_preferences.show_types)
 *
 * Only artists with is_active=true AND available_for_shows=true are considered.
 * Only artists with score >= 1 are returned.
 *
 * TODO: smarter matching — tag similarity, AI-based genre embeddings, distance radius
 */
class ShowMatcher {

    /**
     * Find artist groups that match the given show criteria.
     *
     * @param array $show_data {
     *     @type array       $genres    Array of genre strings from the show.
     *     @type string|null $mood      Mood string (e.g. 'energetic').
     *     @type int|null    $city_id   City ID of the show.
     *     @type string      $type      Show type ENUM value.
     * }
     * @param int $limit Max results to return (default 20).
     * @return array[] Each item: id, name, unique_name, avatar_url, city, genre, mood, description, score.
     */
    public static function findMatches(array $show_data, int $limit = 20): array {
        // Normalise show inputs
        $show_genres   = array_map('strtolower', (array) ($show_data['genres'] ?? []));
        $show_mood     = strtolower((string) ($show_data['mood'] ?? ''));
        $show_city_id  = isset($show_data['city_id']) ? (int) $show_data['city_id'] : null;
        $show_type     = strtolower((string) ($show_data['type'] ?? ''));

        // Resolve city → province for location scoring
        $show_province = null;
        if ($show_city_id) {
            $city_row = md_db_row(
                "SELECT province FROM simuze_cities WHERE id = ? LIMIT 1",
                [$show_city_id]
            );
            $show_province = $city_row ? strtolower((string) ($city_row['province'] ?? '')) : null;
        }

        // Query all available artist groups
        $rows = md_db_query(
            "SELECT g.id, g.name, g.unique_name, g.avatar_url, g.city, g.description, g.settings
             FROM simuze_groups g
             INNER JOIN simuze_group_types t ON t.id = g.group_type_id
             WHERE t.slug = 'artist'
               AND g.deleted_at IS NULL
               AND JSON_EXTRACT(g.settings, '$.is_active') = true
               AND JSON_EXTRACT(g.settings, '$.available_for_shows') = true",
            []
        ) ?: [];

        $results = [];

        foreach ($rows as $g) {
            $settings = is_array($g['settings'])
                ? $g['settings']
                : (is_string($g['settings']) ? json_decode($g['settings'], true) : []);
            $settings = is_array($settings) ? $settings : [];
            $bp       = is_array($settings['booking_preferences'] ?? null)
                          ? $settings['booking_preferences']
                          : [];

            $artist_genres     = array_map('strtolower', (array) ($bp['genres'] ?? []));
            $artist_mood       = strtolower((string) ($bp['mood'] ?? ''));
            $artist_show_types = array_map('strtolower', (array) ($bp['show_types'] ?? []));
            $artist_city       = strtolower((string) ($g['city'] ?? ''));

            $score = 0;

            // Genre match (+3)
            if (!empty($show_genres) && !empty($artist_genres)) {
                if (array_intersect($show_genres, $artist_genres)) {
                    $score += 3;
                }
            }

            // Mood match (+2)
            if ($show_mood !== '' && $artist_mood !== '' && $show_mood === $artist_mood) {
                $score += 2;
            }

            // Location match (+2 city, +1 province)
            if ($show_city_id) {
                $artist_city_row = $artist_city !== ''
                    ? md_db_row("SELECT id, province FROM simuze_cities WHERE LOWER(name) = ? LIMIT 1", [$artist_city])
                    : null;
                if ($artist_city_row) {
                    if ((int) $artist_city_row['id'] === $show_city_id) {
                        $score += 2;
                    } elseif ($show_province !== null && strtolower((string) ($artist_city_row['province'] ?? '')) === $show_province) {
                        $score += 1;
                    }
                }
            }

            // Show type match (+1)
            if ($show_type !== '' && !empty($artist_show_types) && in_array($show_type, $artist_show_types, true)) {
                $score += 1;
            }

            if ($score < 1) {
                continue;
            }

            $results[] = [
                'id'          => (int) $g['id'],
                'name'        => $g['name'],
                'unique_name' => $g['unique_name'],
                'avatar_url'  => $g['avatar_url'],
                'city'        => $g['city'],
                'genre'       => $settings['genre'] ?? '',
                'mood'        => $artist_mood ?: null,
                'description' => $g['description'],
                'score'       => $score,
            ];
        }

        // Sort by score descending
        usort($results, fn($a, $b) => $b['score'] - $a['score']);

        return array_slice($results, 0, $limit);
    }
}
