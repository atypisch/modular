<?php
/**
 * System API Controller
 * Read-mostly endpoints for the SPA's System screens: users, roles +
 * capabilities, and modules (list + activate/deactivate toggle).
 */

namespace Modular\API;

class SystemController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /** GET /users */
    public function users() {
        $stmt = $this->db->query("
            SELECT id, username, email,
                   NULLIF(TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))), '') AS name,
                   role, status, last_login, created_at
            FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Every column that records which CMS user owns or authored a record.
     * Deleting a user reassigns all of these, so no screen is left pointing at a
     * user id that no longer exists. Deliberately excludes the audit trails
     * (activity_log, import_log): reassigning those would attribute one person's
     * actions to another. user_roles / user_sessions are handled by their
     * ON DELETE CASCADE foreign keys.
     */
    private const USER_OWNERSHIP_COLUMNS = [
        'components'        => ['created_by', 'updated_by'],
        'component_versions'=> ['created_by'],
        'field_groups'      => ['created_by', 'updated_by'],
        'fields'            => ['created_by', 'updated_by'],
        'forms'             => ['created_by', 'updated_by'],
        'media'             => ['uploaded_by', 'created_by', 'updated_by'],
        'post_revisions'    => ['created_by'],
        'post_types'        => ['created_by', 'updated_by'],
        'posts'             => ['author_id', 'created_by', 'updated_by'],
        'shared_fields'     => ['created_by', 'updated_by'],
        'taxonomies'        => ['created_by', 'updated_by'],
        'terms'             => ['created_by', 'updated_by'],
    ];

    /** How many records the given user owns, per table — powers the delete dialog. */
    public function userContent($id) {
        $id = (int) $id;
        $exists = $this->db->prepare("SELECT 1 FROM users WHERE id = ? AND deleted_at IS NULL");
        $exists->execute([$id]);
        if (!$exists->fetchColumn()) return ['error' => 'User not found', 'code' => 404];

        $counts = [];
        $total = 0;
        foreach (self::USER_OWNERSHIP_COLUMNS as $table => $columns) {
            $where = implode(' OR ', array_map(fn($c) => "`$c` = ?", $columns));
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `$table` WHERE $where");
            $stmt->execute(array_fill(0, count($columns), $id));
            $n = (int) $stmt->fetchColumn();
            if ($n > 0) { $counts[$table] = $n; $total += $n; }
        }
        return ['id' => $id, 'total' => $total, 'by_table' => $counts];
    }

    /** POST /users — body { email, password, username?, first_name?, last_name?, role?, status? }. */
    public function createUser() {
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        $password = (string) ($in['password'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'A valid email address is required', 'code' => 400];
        }
        if (strlen($password) < 8) {
            return ['error' => 'Password must be at least 8 characters', 'code' => 400];
        }

        // Username defaults to the email local part, de-duplicated with a suffix.
        $username = trim((string) ($in['username'] ?? ''));
        if ($username === '') $username = explode('@', $email)[0];
        $username = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $username) ?: 'user';
        $base = $username; $n = 1;
        $taken = $this->db->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
        $taken->execute([$username]);
        while ($taken->fetchColumn()) {
            $username = $base . '-' . (++$n);
            $taken->execute([$username]);
        }

        $dupe = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $dupe->execute([$email]);
        if ($dupe->fetchColumn()) return ['error' => 'A user with that email already exists', 'code' => 409];

        $role = (string) ($in['role'] ?? 'user');
        $roleOk = $this->db->prepare("SELECT 1 FROM roles WHERE name = ? LIMIT 1");
        $roleOk->execute([$role]);
        if (!$roleOk->fetchColumn()) return ['error' => 'Unknown role', 'code' => 400];

        // Coalesce first: `$in['status'] ?? 'active'` passing the in_array check does
        // not mean the key exists, so reading it back would hand NULL to the insert.
        $status = (string) ($in['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'], true)) $status = 'active';

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password, first_name, last_name, role, status, email_verified, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())"
        );
        $stmt->execute([
            $username, $email, password_hash($password, PASSWORD_DEFAULT),
            trim((string) ($in['first_name'] ?? '')) ?: null,
            trim((string) ($in['last_name'] ?? '')) ?: null,
            $role, $status,
        ]);
        $id = (int) $this->db->lastInsertId();

        // Mirror the role into user_roles so capability checks see it too.
        $this->db->prepare(
            "INSERT IGNORE INTO user_roles (user_id, role_id) SELECT ?, id FROM roles WHERE name = ?"
        )->execute([$id, $role]);

        $row = $this->db->prepare(
            "SELECT id, username, email,
                    NULLIF(TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))), '') AS name,
                    role, status, last_login, created_at
             FROM users WHERE id = ?"
        );
        $row->execute([$id]);
        return $row->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * DELETE /users/{id} — body { reassign_to: userId }.
     * Removes the account for real and hands everything it owned to another
     * user, so nothing is orphaned. Refuses to delete the last administrator or
     * the account making the request.
     */
    public function deleteUser($id, $currentUserId = null) {
        $id = (int) $id;
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $reassignTo = (int) ($in['reassign_to'] ?? 0);

        $target = $this->db->prepare("SELECT id, role FROM users WHERE id = ? AND deleted_at IS NULL");
        $target->execute([$id]);
        $user = $target->fetch(\PDO::FETCH_ASSOC);
        if (!$user) return ['error' => 'User not found', 'code' => 404];

        if ($currentUserId !== null && (int) $currentUserId === $id) {
            return ['error' => 'You cannot delete the account you are signed in with', 'code' => 400];
        }
        if (strtolower((string) $user['role']) === 'admin') {
            $admins = (int) $this->db->query(
                "SELECT COUNT(*) FROM users WHERE role = 'admin' AND deleted_at IS NULL"
            )->fetchColumn();
            if ($admins <= 1) return ['error' => 'The last administrator cannot be deleted', 'code' => 400];
        }

        if ($reassignTo === $id) return ['error' => 'Pick a different user to hand the content to', 'code' => 400];
        $heir = $this->db->prepare("SELECT id FROM users WHERE id = ? AND deleted_at IS NULL");
        $heir->execute([$reassignTo]);
        if (!$heir->fetchColumn()) {
            return ['error' => 'Choose an existing user to hand this content to', 'code' => 400];
        }

        $moved = [];
        $this->db->beginTransaction();
        try {
            foreach (self::USER_OWNERSHIP_COLUMNS as $table => $columns) {
                foreach ($columns as $column) {
                    $stmt = $this->db->prepare("UPDATE `$table` SET `$column` = ? WHERE `$column` = ?");
                    $stmt->execute([$reassignTo, $id]);
                    if ($stmt->rowCount() > 0) {
                        $moved[$table] = ($moved[$table] ?? 0) + $stmt->rowCount();
                    }
                }
            }
            // user_roles + user_sessions drop out via ON DELETE CASCADE.
            $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['error' => 'Could not delete this user: ' . $e->getMessage(), 'code' => 500];
        }

        return ['ok' => true, 'id' => $id, 'reassigned_to' => $reassignTo, 'moved' => $moved];
    }

    /** PUT /users/{id} — body { role?, status? }. Assign a user's role / status. */
    public function updateUser($id) {
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $sets = []; $args = [];
        if (array_key_exists('role', $in)) {
            $role = (string) $in['role'];
            $ok = $this->db->prepare("SELECT 1 FROM roles WHERE name = ? LIMIT 1");
            $ok->execute([$role]);
            if (!$ok->fetchColumn()) return ['error' => 'Unknown role', 'code' => 400];
            $sets[] = 'role = ?'; $args[] = $role;
        }
        if (array_key_exists('status', $in)) { $sets[] = 'status = ?'; $args[] = (string) $in['status']; }
        if (!$sets) return ['error' => 'Nothing to update', 'code' => 400];
        $args[] = $id;
        $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?");
        $stmt->execute($args);
        $row = $this->db->prepare("SELECT id, username, email, role, status FROM users WHERE id = ?");
        $row->execute([$id]);
        $u = $row->fetch(\PDO::FETCH_ASSOC);
        if (!$u) return ['error' => 'User not found', 'code' => 404];
        return $u;
    }

    /** GET /roles — each role with its capability names + user count. */
    public function roles() {
        $roles = $this->db->query("
            SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role = r.name AND u.deleted_at IS NULL) AS user_count
            FROM roles r ORDER BY r.id
        ")->fetchAll(\PDO::FETCH_ASSOC);
        $caps = $this->db->query("
            SELECT rc.role_id, c.name FROM role_capabilities rc
            INNER JOIN capabilities c ON c.id = rc.capability_id
        ")->fetchAll(\PDO::FETCH_ASSOC);
        $byRole = [];
        foreach ($caps as $c) { $byRole[$c['role_id']][] = $c['name']; }
        foreach ($roles as &$r) { $r['capabilities'] = $byRole[$r['id']] ?? []; $r['user_count'] = (int) $r['user_count']; }
        return $roles;
    }

    /** POST /roles — body { name|display_name, description }. */
    public function createRole() {
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $display = trim((string) ($in['display_name'] ?? $in['name'] ?? ''));
        if ($display === '') return ['error' => 'Name is required', 'code' => 400];
        $name = preg_replace('/[^a-z0-9_]+/', '_', strtolower($display));
        $name = trim($name, '_') ?: ('role_' . substr(md5($display), 0, 6));
        $exists = $this->db->prepare("SELECT 1 FROM roles WHERE name = ? LIMIT 1");
        $exists->execute([$name]);
        if ($exists->fetchColumn()) return ['error' => 'A role with that name already exists', 'code' => 409];
        $stmt = $this->db->prepare("INSERT INTO roles (name, display_name, description, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$name, $display, (string) ($in['description'] ?? '')]);
        $id = (int) $this->db->lastInsertId();
        return ['id' => $id, 'name' => $name, 'display_name' => $display, 'description' => (string) ($in['description'] ?? ''), 'capabilities' => [], 'user_count' => 0];
    }

    /** PUT /roles/{id}/capabilities — body { capabilities: [names] }. Replaces the grant set. */
    public function updateRoleCapabilities($id) {
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $names = is_array($in['capabilities'] ?? null) ? $in['capabilities'] : [];
        $role = $this->db->prepare("SELECT name FROM roles WHERE id = ?");
        $role->execute([$id]);
        $roleName = $role->fetchColumn();
        if ($roleName === false) return ['error' => 'Role not found', 'code' => 404];
        // Administrators always hold every capability — not narrowable.
        if (strtolower($roleName) === 'admin') return ['error' => 'Administrator capabilities cannot be changed', 'code' => 403];
        $del = $this->db->prepare("DELETE FROM role_capabilities WHERE role_id = ?");
        $del->execute([$id]);
        if ($names) {
            $place = implode(',', array_fill(0, count($names), '?'));
            $capStmt = $this->db->prepare("SELECT id, name FROM capabilities WHERE name IN ($place)");
            $capStmt->execute(array_values($names));
            $ids = $capStmt->fetchAll(\PDO::FETCH_COLUMN, 0);
            if ($ids) {
                $ins = $this->db->prepare("INSERT INTO role_capabilities (role_id, capability_id) VALUES (?, ?)");
                foreach ($ids as $cid) { $ins->execute([$id, $cid]); }
            }
        }
        return ['id' => (int) $id, 'capabilities' => array_values($names)];
    }

    /** DELETE /roles/{id} — built-in roles are protected. */
    public function deleteRole($id) {
        $role = $this->db->prepare("SELECT name FROM roles WHERE id = ?");
        $role->execute([$id]);
        $roleName = $role->fetchColumn();
        if ($roleName === false) return ['error' => 'Role not found', 'code' => 404];
        if (in_array(strtolower($roleName), ['admin', 'editor', 'author', 'user'], true)) {
            return ['error' => 'Built-in roles cannot be deleted', 'code' => 403];
        }
        $this->db->prepare("DELETE FROM role_capabilities WHERE role_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);
        return ['ok' => true, 'id' => (int) $id];
    }

    /** GET /capabilities */
    public function capabilities() {
        return $this->db->query("SELECT * FROM capabilities ORDER BY name")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** GET /modules */
    public function modules() {
        $rows = $this->db->query("SELECT * FROM modules ORDER BY name")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$m) {
            if (isset($m['settings']) && is_string($m['settings'])) $m['settings'] = json_decode($m['settings'], true);
            $m['active'] = (int)$m['active'];
        }
        return $rows;
    }

    /** PUT /modules/{id} — body { active: bool } */
    public function toggleModule($id) {
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        if (!array_key_exists('active', $in)) return ['error' => 'active is required', 'code' => 400];
        $stmt = $this->db->prepare("UPDATE modules SET active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([(int)!!$in['active'], $id]);
        $stmt = $this->db->prepare("SELECT * FROM modules WHERE id = ?");
        $stmt->execute([$id]);
        $m = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$m) return ['error' => 'Module not found', 'code' => 404];
        if (function_exists('do_action')) {
            do_action(((int)$m['active']) ? 'module_activate' : 'module_deactivate', $m['slug']);
        }
        $m['active'] = (int)$m['active'];
        return $m;
    }
}
