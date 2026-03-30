<?php
/**
 * ActivityPostRepository — CRUD for simuze_activity_posts.
 * Follows the same static-class pattern as EventRepository.
 */
class ActivityPostRepository {

    // -------------------------------------------------------------------------
    // Type labels
    // -------------------------------------------------------------------------
    const TYPE_LABELS = [
        'open_slot'        => 'Open slot',
        'looking_for_band' => 'Looking for band',
        'musician_wanted'  => 'Musician wanted',
        'rehearsal_space'  => 'Rehearsal space',
        'general_request'  => 'General',
    ];

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * Fetch recent active posts (not expired, not deleted).
     *
     * @param int         $limit
     * @param string|null $type   Filter by type slug
     * @param int|null    $city_id
     * @return array
     */
    public static function findRecent(int $limit = 20, ?string $type = null, ?int $city_id = null): array {
        $db = md_db();
        $where = ["p.status = 'active'", "(p.expires_at IS NULL OR p.expires_at > NOW())", "p.deleted_at IS NULL"];
        $params = [];

        if ($type && array_key_exists($type, self::TYPE_LABELS)) {
            $where[] = 'p.type = ?';
            $params[] = $type;
        }
        if ($city_id) {
            $where[] = 'p.city_id = ?';
            $params[] = $city_id;
        }

        // Join only columns that exist on all supported schemas (avoid unknown-column SQL errors).
        $sql = 'SELECT p.*,
                    u.username   AS user_username,
                    u.avatar_url AS user_avatar,
                    g.name       AS group_name
                FROM simuze_activity_posts p
                LEFT JOIN simuze_users  u ON p.author_type = \'user\'  AND p.author_id = u.id
                LEFT JOIN simuze_groups g ON p.author_type = \'group\' AND p.author_id = g.id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.created_at DESC
                LIMIT ' . (int) $limit;

        $rows = md_db_query($sql, $params) ?: [];
        self::hydrateUserUniqueIdentifiersForRows($rows);

        return $rows;
    }

    /**
     * Find a single post by ID.
     */
    public static function findById(int $id): ?array {
        $row = md_db_row(
            'SELECT * FROM simuze_activity_posts WHERE id = ? AND deleted_at IS NULL',
            [$id]
        );
        if (!$row) {
            return null;
        }
        self::hydrateAuthorJoinFields($row);

        return $row;
    }

    /**
     * Attach user/group display fields for a post row loaded from simuze_activity_posts only.
     */
    private static function hydrateAuthorJoinFields(array &$row): void {
        $row['user_unique_identifier'] = $row['user_unique_identifier'] ?? '';
        $row['user_username']          = $row['user_username'] ?? '';
        $row['user_avatar']            = $row['user_avatar'] ?? null;
        $row['group_name']             = $row['group_name'] ?? '';
        $row['group_slug']             = $row['group_slug'] ?? null;
        $row['group_avatar']           = $row['group_avatar'] ?? null;

        if (($row['author_type'] ?? '') === 'user') {
            $aid = (int) ($row['author_id'] ?? 0);
            if ($aid < 1) {
                return;
            }
            $u = md_db_row(
                'SELECT username, avatar_url FROM simuze_users WHERE id = ? AND deleted_at IS NULL',
                [$aid]
            );
            if ($u) {
                $row['user_username'] = (string) ($u['username'] ?? '');
                $row['user_avatar']   = $u['avatar_url'] ?? null;
            }
            self::hydrateUserUniqueIdentifier($row);
            return;
        }

        if (($row['author_type'] ?? '') === 'group') {
            $gid = (int) ($row['author_id'] ?? 0);
            if ($gid < 1) {
                return;
            }
            $g = md_db_row(
                'SELECT name FROM simuze_groups WHERE id = ? AND deleted_at IS NULL',
                [$gid]
            );
            if ($g) {
                $row['group_name'] = (string) ($g['name'] ?? '');
            }
        }
    }

    /**
     * Set user_unique_identifier on list rows (one batched query when column exists).
     *
     * @param array<int,array<string,mixed>> $rows
     */
    private static function hydrateUserUniqueIdentifiersForRows(array &$rows): void {
        if ($rows === [] || !self::simuzeUsersHasUniqueIdentifierColumn()) {
            return;
        }
        $ids = [];
        foreach ($rows as $r) {
            if (($r['author_type'] ?? '') !== 'user') {
                continue;
            }
            $aid = (int) ($r['author_id'] ?? 0);
            if ($aid > 0) {
                $ids[$aid] = true;
            }
        }
        if ($ids === []) {
            return;
        }
        $idList = implode(',', array_map('intval', array_keys($ids)));
        $mapRows = md_db_query(
            "SELECT id, unique_identifier FROM simuze_users WHERE id IN ({$idList}) AND deleted_at IS NULL"
        ) ?: [];
        $map = [];
        foreach ($mapRows as $m) {
            $uid = (int) ($m['id'] ?? 0);
            $h   = trim((string) ($m['unique_identifier'] ?? ''));
            if ($uid > 0 && $h !== '') {
                $map[$uid] = $h;
            }
        }
        foreach ($rows as &$r) {
            if (($r['author_type'] ?? '') !== 'user') {
                continue;
            }
            $aid = (int) ($r['author_id'] ?? 0);
            if ($aid > 0 && isset($map[$aid])) {
                $r['user_unique_identifier'] = $map[$aid];
            }
        }
        unset($r);
    }

    /**
     * Set user_unique_identifier when the column exists (optional on older DBs).
     */
    private static function hydrateUserUniqueIdentifier(array &$row): void {
        if (($row['author_type'] ?? '') !== 'user') {
            return;
        }
        $aid = (int) ($row['author_id'] ?? 0);
        if ($aid < 1 || !self::simuzeUsersHasUniqueIdentifierColumn()) {
            return;
        }
        $h = md_db_var('SELECT unique_identifier FROM simuze_users WHERE id = ? AND deleted_at IS NULL', [$aid]);
        if ($h !== null && trim((string) $h) !== '') {
            $row['user_unique_identifier'] = trim((string) $h);
        }
    }

    private static function simuzeUsersHasUniqueIdentifierColumn(): bool {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $stmt = md_db()->query("SHOW COLUMNS FROM simuze_users LIKE 'unique_identifier'");
            $cached = $stmt && $stmt->fetch() !== false;
        } catch (\Throwable $e) {
            $cached = false;
        }
        return $cached;
    }

    /**
     * Find posts by a specific author.
     *
     * @param string $author_type 'user' or 'group'
     * @param int    $author_id
     * @param int    $limit
     * @return array
     */
    public static function findByAuthor(string $author_type, int $author_id, int $limit = 20): array {
        return md_db_results(
            'SELECT * FROM simuze_activity_posts
             WHERE author_type = ? AND author_id = ? AND deleted_at IS NULL
             ORDER BY created_at DESC
             LIMIT ' . (int) $limit,
            [$author_type, $author_id]
        ) ?: [];
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    /**
     * Create a new activity post.
     *
     * Required keys: type, author_type, author_id, title
     * Optional: city_id, city_name, body, expires_in_days (7/14/30)
     *
     * @param array $data
     * @return int|null  Inserted ID, or null on failure
     */
    public static function create(array $data): ?int {
        if (empty($data['type']) || !array_key_exists($data['type'], self::TYPE_LABELS)) {
            return null;
        }
        if (empty($data['author_type']) || empty($data['author_id']) || empty($data['title'])) {
            return null;
        }

        $expires_at = null;
        if (!empty($data['expires_in_days'])) {
            $days = (int) $data['expires_in_days'];
            if ($days > 0) {
                $expires_at = date('Y-m-d H:i:s', strtotime("+{$days} days"));
            }
        }

        $id = md_db_insert('simuze_activity_posts', [
            'type'        => $data['type'],
            'author_type' => $data['author_type'],
            'author_id'   => (int) $data['author_id'],
            'title'       => substr(trim($data['title']), 0, 255),
            'city_id'     => !empty($data['city_id'])   ? (int) $data['city_id']  : null,
            'city_name'   => !empty($data['city_name']) ? substr(trim($data['city_name']), 0, 100) : null,
            'body'        => !empty($data['body'])       ? trim($data['body'])     : null,
            'status'      => 'active',
            'expires_at'  => $expires_at,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return $id > 0 ? $id : null;
    }

    /**
     * Soft-delete a post (sets deleted_at = NOW()).
     * Only the author or an admin should call this.
     */
    public static function delete(int $id): bool {
        $db = md_db();
        $stmt = $db->prepare('UPDATE simuze_activity_posts SET deleted_at = NOW(), status = \'deleted\' WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Close a post (mark as closed without deleting).
     */
    public static function close(int $id): bool {
        $db = md_db();
        $stmt = $db->prepare("UPDATE simuze_activity_posts SET status = 'closed' WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return the human label for a post type slug.
     */
    public static function typeLabel(string $type): string {
        return self::TYPE_LABELS[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Public author line for board UI: groups use name; users use @unique_identifier (never email).
     */
    public static function formatAuthorLabel(array $row): string {
        if (($row['author_type'] ?? '') === 'group') {
            return (string) ($row['group_name'] ?? 'Group');
        }
        $handle = trim((string) ($row['user_unique_identifier'] ?? ''));
        if ($handle === '') {
            $handle = trim((string) ($row['user_username'] ?? ''));
        }
        if ($handle === '') {
            return 'User';
        }
        return '@' . $handle;
    }
}
