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
