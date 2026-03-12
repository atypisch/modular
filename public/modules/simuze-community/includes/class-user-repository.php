<?php
/**
 * UserRepository
 *
 * All data access for simuze_users and simuze_user_profiles.
 * Replaces get_userdata(), get_current_user_id(), current_user_can() WP calls.
 * Uses md_db() / md_db_*() — no $wpdb dependency.
 */
class UserRepository {

    /**
     * Find a user by primary key.
     * Returns a stdClass with properties: ID, user_login, user_email, display_name,
     * user_registered, user_status, role, avatar_url.
     */
    public static function find(int $id): ?object {
        $row = md_db_row('SELECT * FROM simuze_users WHERE id = ?', [$id]);
        return $row ? self::toObject($row) : null;
    }

    /**
     * Find a user by username.
     */
    public static function findByUsername(string $username): ?object {
        $row = md_db_row('SELECT * FROM simuze_users WHERE username = ?', [$username]);
        return $row ? self::toObject($row) : null;
    }

    /**
     * Find a user by email address.
     */
    public static function findByEmail(string $email): ?object {
        $row = md_db_row('SELECT * FROM simuze_users WHERE email = ?', [$email]);
        return $row ? self::toObject($row) : null;
    }

    /**
     * Return the currently authenticated user object, or null if not logged in.
     */
    public static function current(): ?object {
        $user = $GLOBALS['current_user'] ?? null;
        if ($user && isset($user->ID)) {
            return $user;
        }
        $id = (int) ($GLOBALS['current_user_id'] ?? $_SESSION['user_id'] ?? 0);
        return $id > 0 ? self::find($id) : null;
    }

    /**
     * Return the current user's ID (0 if not logged in).
     */
    public static function currentId(): int {
        $user = self::current();
        return $user ? (int) $user->ID : 0;
    }

    /**
     * Check whether the current user has admin role.
     * Replaces current_user_can('manage_options').
     */
    public static function currentIsAdmin(): bool {
        if (function_exists('is_admin')) {
            return is_admin();
        }
        $role = $_SESSION['user_role'] ?? ($GLOBALS['user_role'] ?? '');
        return $role === 'admin';
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    /**
     * Normalise a DB row into a WP-style stdClass for backwards compatibility
     * in template code that still reads ->ID, ->user_login, etc.
     */
    private static function toObject(array $row): object {
        $o = new stdClass();
        // WP-compat aliases
        $o->ID           = (int) ($row['id'] ?? 0);
        $o->user_login   = $row['username'] ?? '';
        $o->user_email   = $row['email'] ?? '';
        $o->display_name = $row['display_name'] ?? $row['username'] ?? '';
        $o->user_registered = $row['created_at'] ?? '';
        $o->user_status  = $row['status'] ?? 'active';
        // Modular-native fields
        $o->id           = $o->ID;
        $o->username     = $o->user_login;
        $o->email        = $o->user_email;
        $o->role         = $row['role'] ?? 'subscriber';
        $o->avatar_url   = $row['avatar_url'] ?? '';
        $o->bio          = $row['bio'] ?? '';
        return $o;
    }
}
