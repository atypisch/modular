<?php
/**
 * Field Groups API Controller
 * The WHERE/binding layer: reusable field groups with ACF-style location
 * rules (OR-of-AND), placement and a fields schema tree.
 */

namespace Modular\API;

class FieldGroupsController {
    private $db;
    private const JSON_COLS = ['location', 'bound_to', 'fields'];

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        $rows = $this->db->query("
            SELECT * FROM field_groups WHERE deleted_at IS NULL ORDER BY `order`, name
        ")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$r) { $this->decode($r); $r['field_count'] = is_array($r['fields']) ? count($r['fields']) : 0; }
        return $rows;
    }

    public function show($id) {
        $stmt = $this->db->prepare("SELECT * FROM field_groups WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return ['error' => 'Field group not found', 'code' => 404];
        $this->decode($row);
        return $row;
    }

    public function create() {
        $in = $this->body();
        $uid = $this->uid();
        $name = trim($in['name'] ?? '');
        if ($name === '') return ['error' => 'Name is required', 'code' => 400];
        $slug = $in['slug'] ?? $this->slugify($name);
        $stmt = $this->db->prepare("SELECT id FROM field_groups WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) return ['error' => 'Slug already exists', 'code' => 409];
        $stmt = $this->db->prepare("
            INSERT INTO field_groups
              (name, slug, `key`, icon, description, location, position, style, label_placement, bound_to, fields, active, `order`, created_by, updated_by, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())
        ");
        $stmt->execute([
            $name, $slug, $in['key'] ?? $slug, $in['icon'] ?? 'box', $in['description'] ?? '',
            $this->enc($in['location'] ?? []), $in['position'] ?? 'normal', $in['style'] ?? 'default', $in['label_placement'] ?? 'top',
            $this->enc($in['bound_to'] ?? []), $this->enc($in['fields'] ?? []),
            (int)($in['active'] ?? 1), (int)($in['order'] ?? 0), $uid, $uid,
        ]);
        return $this->show((string)$this->db->lastInsertId());
    }

    public function update($id) {
        $cur = $this->show($id);
        if (isset($cur['error'])) return $cur;
        $in = $this->body();
        $stmt = $this->db->prepare("
            UPDATE field_groups SET name=?, slug=?, `key`=?, icon=?, description=?, location=?, position=?, style=?, label_placement=?, bound_to=?, fields=?, active=?, `order`=?, updated_by=?, updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([
            $in['name'] ?? $cur['name'], $in['slug'] ?? $cur['slug'], $in['key'] ?? $cur['key'], $in['icon'] ?? $cur['icon'] ?? 'box', $in['description'] ?? $cur['description'],
            $this->enc($in['location'] ?? $cur['location']), $in['position'] ?? $cur['position'], $in['style'] ?? $cur['style'], $in['label_placement'] ?? $cur['label_placement'],
            $this->enc($in['bound_to'] ?? $cur['bound_to']), $this->enc(array_key_exists('fields', $in) ? $in['fields'] : $cur['fields']),
            (int)($in['active'] ?? $cur['active']), (int)($in['order'] ?? $cur['order']), $this->uid(), $cur['id'],
        ]);
        return $this->show((string)$cur['id']);
    }

    /** PUT /field-groups/{id}/location — body { location: [...], bound_to: [...] } */
    public function updateLocation($id) {
        $cur = $this->show($id);
        if (isset($cur['error'])) return $cur;
        $in = $this->body();
        $stmt = $this->db->prepare("UPDATE field_groups SET location=?, bound_to=?, updated_by=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$this->enc($in['location'] ?? $cur['location']), $this->enc($in['bound_to'] ?? $cur['bound_to']), $this->uid(), $cur['id']]);
        return $this->show((string)$cur['id']);
    }

    public function delete($id) {
        $cur = $this->show($id);
        if (isset($cur['error'])) return $cur;
        $stmt = $this->db->prepare("UPDATE field_groups SET deleted_at=NOW(), updated_by=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$this->uid(), $cur['id']]);
        return ['message' => 'Field group deleted'];
    }

    /* ---- helpers ---- */
    private function decode(array &$r): void {
        foreach (self::JSON_COLS as $c) { if (isset($r[$c]) && is_string($r[$c])) $r[$c] = json_decode($r[$c], true); }
    }
    private function enc($v): string { return json_encode($v === null ? [] : $v); }
    private function body(): array { return json_decode(file_get_contents('php://input'), true) ?: []; }
    private function uid(): int { return (int)($GLOBALS['current_user']['user_id'] ?? $_SESSION['user_id'] ?? 1); }
    private function slugify($t) { $t = strtolower(trim($t)); $t = preg_replace('/[^a-z0-9-]/', '-', $t); return trim(preg_replace('/-+/', '-', $t), '-'); }
}
