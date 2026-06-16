<?php
/**
 * Components API Controller
 * Versioned, reusable blocks. Each save snapshots the schema into
 * component_versions and bumps current_version; usage drives "used in N".
 */

namespace Modular\API;

class ComponentsController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        $rows = $this->db->query("
            SELECT c.*, (SELECT COUNT(*) FROM component_usage cu WHERE cu.component_id = c.id) AS used_in
            FROM components c WHERE c.deleted_at IS NULL ORDER BY c.name
        ")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$r) { $this->decode($r); }
        return $rows;
    }

    public function show($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, (SELECT COUNT(*) FROM component_usage cu WHERE cu.component_id = c.id) AS used_in
            FROM components c WHERE c.id = ? AND c.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return ['error' => 'Component not found', 'code' => 404];
        $this->decode($row);
        return $row;
    }

    public function create() {
        $in = $this->body();
        $uid = $this->uid();
        $name = trim($in['name'] ?? '');
        if ($name === '') return ['error' => 'Name is required', 'code' => 400];
        $slug = $in['slug'] ?? $this->slugify($name);
        $stmt = $this->db->prepare("SELECT id FROM components WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) return ['error' => 'Slug already exists', 'code' => 409];
        $schema = $in['schema'] ?? [];
        $stmt = $this->db->prepare("
            INSERT INTO components (name, slug, icon, description, current_version, `schema`, created_by, updated_by, created_at, updated_at)
            VALUES (?,?,?,?,1,?,?,?,NOW(),NOW())
        ");
        $stmt->execute([$name, $slug, $in['icon'] ?? 'layers', $in['description'] ?? '', json_encode($schema), $uid, $uid]);
        $id = (int)$this->db->lastInsertId();
        $this->snapshot($id, 1, $schema, $in['note'] ?? 'Initial version', $uid);
        return $this->show((string)$id);
    }

    public function update($id) {
        $cur = $this->show($id);
        if (isset($cur['error'])) return $cur;
        $in = $this->body();
        $uid = $this->uid();
        $schemaChanged = array_key_exists('schema', $in) && json_encode($in['schema']) !== json_encode($cur['schema']);
        $version = (int)$cur['current_version'];
        $schema = array_key_exists('schema', $in) ? $in['schema'] : $cur['schema'];
        if ($schemaChanged) { $version++; }
        $stmt = $this->db->prepare("
            UPDATE components SET name=?, slug=?, icon=?, description=?, current_version=?, `schema`=?, updated_by=?, updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([
            $in['name'] ?? $cur['name'], $in['slug'] ?? $cur['slug'], $in['icon'] ?? $cur['icon'], $in['description'] ?? $cur['description'],
            $version, json_encode($schema), $uid, $cur['id'],
        ]);
        if ($schemaChanged) { $this->snapshot((int)$cur['id'], $version, $schema, $in['note'] ?? null, $uid); }
        return $this->show((string)$cur['id']);
    }

    public function delete($id) {
        $cur = $this->show($id);
        if (isset($cur['error'])) return $cur;
        $stmt = $this->db->prepare("UPDATE components SET deleted_at=NOW(), updated_by=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$this->uid(), $cur['id']]);
        return ['message' => 'Component deleted'];
    }

    /** GET /components/{id}/versions */
    public function versions($id) {
        $stmt = $this->db->prepare("
            SELECT v.id, v.version, v.note, v.created_at, COALESCE(u.username, u.email, 'Unknown') AS created_by_name
            FROM component_versions v LEFT JOIN users u ON v.created_by = u.id
            WHERE v.component_id = ? ORDER BY v.version DESC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** POST /components/{id}/versions — snapshot the current (or supplied) schema as a new version. */
    public function createVersion($id) {
        $cur = $this->show($id);
        if (isset($cur['error'])) return $cur;
        $in = $this->body();
        $schema = array_key_exists('schema', $in) ? $in['schema'] : $cur['schema'];
        $version = (int)$cur['current_version'] + 1;
        $stmt = $this->db->prepare("UPDATE components SET current_version=?, `schema`=?, updated_by=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$version, json_encode($schema), $this->uid(), $cur['id']]);
        $this->snapshot((int)$cur['id'], $version, $schema, $in['note'] ?? null, $this->uid());
        return $this->show((string)$cur['id']);
    }

    /** GET /components/{id}/usage — posts using this component. */
    public function usage($id) {
        $stmt = $this->db->prepare("
            SELECT cu.post_id, cu.version, p.title, p.slug, pt.slug AS post_type
            FROM component_usage cu
            LEFT JOIN posts p ON p.id = cu.post_id
            LEFT JOIN post_types pt ON pt.id = p.post_type_id
            WHERE cu.component_id = ? ORDER BY p.title
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* ---- helpers ---- */
    private function snapshot(int $componentId, int $version, $schema, ?string $note, int $uid): void {
        $stmt = $this->db->prepare("
            INSERT INTO component_versions (component_id, version, `schema`, note, created_by, created_at)
            VALUES (?,?,?,?,?,NOW())
            ON DUPLICATE KEY UPDATE `schema` = VALUES(`schema`), note = VALUES(note)
        ");
        $stmt->execute([$componentId, $version, json_encode($schema), $note, $uid]);
    }
    private function decode(array &$r): void {
        if (isset($r['schema']) && is_string($r['schema'])) $r['schema'] = json_decode($r['schema'], true);
        if (isset($r['used_in'])) $r['used_in'] = (int)$r['used_in'];
        if (isset($r['current_version'])) $r['current_version'] = (int)$r['current_version'];
    }
    private function body(): array { return json_decode(file_get_contents('php://input'), true) ?: []; }
    private function uid(): int { return (int)($GLOBALS['current_user']['user_id'] ?? $_SESSION['user_id'] ?? 1); }
    private function slugify($t) { $t = strtolower(trim($t)); $t = preg_replace('/[^a-z0-9-]/', '-', $t); return trim(preg_replace('/-+/', '-', $t), '-'); }
}
