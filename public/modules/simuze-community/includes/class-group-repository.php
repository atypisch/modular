<?php
/**
 * GroupRepository
 *
 * All data access for simuze_groups and simuze_group_members.
 * Replaces groups_get_group(), bp_get_group_url(), and direct $wpdb calls.
 * Uses md_db() / md_db_*() — no $wpdb dependency.
 */
class GroupRepository {

    /**
     * Find a group by primary key. Returns null if not found.
     */
    public static function find(int $id): ?object {
        $row = md_db_row('SELECT * FROM simuze_groups WHERE id = ?', [$id]);
        return $row ? self::toObject($row) : null;
    }

    /**
     * Find a group by its unique slug.
     */
    public static function findBySlug(string $slug): ?object {
        $row = md_db_row('SELECT * FROM simuze_groups WHERE unique_name = ?', [$slug]);
        return $row ? self::toObject($row) : null;
    }

    /**
     * Return total confirmed member count for a group.
     */
    public static function getMemberCount(int $group_id): int {
        return (int) md_db_var(
            "SELECT COUNT(*) FROM simuze_group_members WHERE group_id = ? AND status = 'active'",
            [$group_id]
        );
    }

    /**
     * Return a list of members for a group.
     * Each row has user fields joined from simuze_users.
     */
    public static function getMembers(int $group_id, int $limit = 20): array {
        return md_db_query(
            "SELECT u.id, u.username, u.display_name, u.avatar_url, gm.role, gm.joined_at
             FROM simuze_group_members gm
             INNER JOIN simuze_users u ON u.id = gm.user_id
             WHERE gm.group_id = ? AND gm.status = 'active'
             ORDER BY gm.joined_at ASC
             LIMIT ?",
            [$group_id, $limit]
        );
    }

    /**
     * Return the membership role of a user in a group, or null if not a member.
     */
    public static function getUserRole(int $group_id, int $user_id): ?string {
        $role = md_db_var(
            "SELECT role FROM simuze_group_members WHERE group_id = ? AND user_id = ? AND status = 'active'",
            [$group_id, $user_id]
        );
        return $role !== null ? (string) $role : null;
    }

    /**
     * Check whether a user is a member (any active role) of a group.
     */
    public static function isMember(int $group_id, int $user_id): bool {
        return self::getUserRole($group_id, $user_id) !== null;
    }

    /**
     * Return the public-facing URL for a group.
     */
    public static function getUrl(object $group): string {
        $slug = $group->slug ?? $group->unique_name ?? '';
        return md_home_url('/group/' . $slug . '/');
    }

    /**
     * Return groups the current user is a member of.
     */
    public static function findByUser(int $user_id): array {
        $rows = md_db_query(
            "SELECT g.* FROM simuze_groups g
             INNER JOIN simuze_group_members gm ON gm.group_id = g.id
             WHERE gm.user_id = ? AND gm.status = 'active'
             ORDER BY g.name ASC",
            [$user_id]
        );
        return array_map([self::class, 'toObject'], $rows);
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    public static function toObject(array $row): object {
        $o = new stdClass();
        $o->id          = (int) ($row['id'] ?? 0);
        $o->name        = $row['name'] ?? '';
        $o->slug        = $row['unique_name'] ?? '';
        $o->unique_name = $o->slug;
        $o->description = $row['description'] ?? '';
        $o->avatar_url  = $row['avatar_url'] ?? '';
        $o->header_url  = $row['header_url'] ?? '';
        $o->status      = $row['status'] ?? 'active';
        $o->creator_id  = (int) ($row['creator_id'] ?? 0);
        $o->group_type_id = (int) ($row['group_type_id'] ?? 0);
        $o->created_at  = $row['created_at'] ?? '';
        // Convenience method for URL building
        $o->get_url = function() use ($o) {
            return GroupRepository::getUrl($o);
        };
        return $o;
    }
}
