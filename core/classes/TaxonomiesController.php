<?php
/**
 * Taxonomies API Controller
 * CRUD for taxonomies and their terms (incl. term reorder).
 */

namespace Modular\API;

class TaxonomiesController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        $stmt = $this->db->query("
            SELECT t.*, (SELECT COUNT(*) FROM terms te WHERE te.taxonomy_id = t.id AND te.deleted_at IS NULL) AS term_count
            FROM taxonomies t WHERE t.deleted_at IS NULL ORDER BY t.name
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function show($idOrSlug) {
        $byId = is_numeric($idOrSlug);
        $col = $byId ? 'id' : 'slug';
        $stmt = $this->db->prepare("SELECT * FROM taxonomies WHERE {$col} = ? AND deleted_at IS NULL");
        $stmt->execute([$idOrSlug]);
        $tax = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$tax) return ['error' => 'Taxonomy not found', 'code' => 404];
        return $tax;
    }

    public function create() {
        $in = $this->body();
        $uid = $this->uid();
        $name = trim($in['name'] ?? '');
        if ($name === '') return ['error' => 'Name is required', 'code' => 400];
        $slug = $in['slug'] ?? $this->slugify($name);
        $stmt = $this->db->prepare("SELECT id FROM taxonomies WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) return ['error' => 'Slug already exists', 'code' => 409];
        $stmt = $this->db->prepare("
            INSERT INTO taxonomies (name, slug, description, hierarchical, public, show_in_menu, show_in_rest, rest_base, created_by, updated_by, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())
        ");
        $stmt->execute([
            $name, $slug, $in['description'] ?? '', (int)($in['hierarchical'] ?? 0), (int)($in['public'] ?? 1),
            (int)($in['show_in_menu'] ?? 1), (int)($in['show_in_rest'] ?? 1), $in['rest_base'] ?? null, $uid, $uid,
        ]);
        return $this->show((string)$this->db->lastInsertId());
    }

    public function update($id) {
        $cur = $this->show($id);
        if (isset($cur['error'])) return $cur;
        $in = $this->body();
        $uid = $this->uid();
        $stmt = $this->db->prepare("
            UPDATE taxonomies SET name=?, slug=?, description=?, hierarchical=?, public=?, show_in_menu=?, show_in_rest=?, rest_base=?, updated_by=?, updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([
            $in['name'] ?? $cur['name'], $in['slug'] ?? $cur['slug'], $in['description'] ?? $cur['description'],
            (int)($in['hierarchical'] ?? $cur['hierarchical']), (int)($in['public'] ?? $cur['public']),
            (int)($in['show_in_menu'] ?? $cur['show_in_menu']), (int)($in['show_in_rest'] ?? $cur['show_in_rest']),
            $in['rest_base'] ?? $cur['rest_base'], $uid, $cur['id'],
        ]);
        return $this->show((string)$cur['id']);
    }

    public function delete($id) {
        $cur = $this->show($id);
        if (isset($cur['error'])) return $cur;
        $stmt = $this->db->prepare("UPDATE taxonomies SET deleted_at=NOW(), updated_by=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$this->uid(), $cur['id']]);
        return ['message' => 'Taxonomy deleted'];
    }

    /* ---- terms ---- */
    public function terms($taxId) {
        $stmt = $this->db->prepare("SELECT * FROM terms WHERE taxonomy_id = ? AND deleted_at IS NULL ORDER BY `order`, name");
        $stmt->execute([$taxId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createTerm($taxId) {
        $in = $this->body();
        $uid = $this->uid();
        $name = trim($in['name'] ?? '');
        if ($name === '') return ['error' => 'Name is required', 'code' => 400];
        $slug = $in['slug'] ?? $this->slugify($name);
        $stmt = $this->db->prepare("
            INSERT INTO terms (taxonomy_id, name, slug, description, parent_id, `order`, created_by, updated_by, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())
        ");
        $stmt->execute([$taxId, $name, $slug, $in['description'] ?? '', $in['parent_id'] ?? null, (int)($in['order'] ?? 0), $uid, $uid]);
        $id = $this->db->lastInsertId();
        $stmt = $this->db->prepare("SELECT * FROM terms WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateTerm($taxId, $termId) {
        $stmt = $this->db->prepare("SELECT * FROM terms WHERE id=? AND taxonomy_id=? AND deleted_at IS NULL");
        $stmt->execute([$termId, $taxId]);
        $cur = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$cur) return ['error' => 'Term not found', 'code' => 404];
        $in = $this->body();
        $stmt = $this->db->prepare("UPDATE terms SET name=?, slug=?, description=?, parent_id=?, `order`=?, updated_by=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([
            $in['name'] ?? $cur['name'], $in['slug'] ?? $cur['slug'], $in['description'] ?? $cur['description'],
            array_key_exists('parent_id', $in) ? $in['parent_id'] : $cur['parent_id'], (int)($in['order'] ?? $cur['order']), $this->uid(), $termId,
        ]);
        $stmt = $this->db->prepare("SELECT * FROM terms WHERE id=?");
        $stmt->execute([$termId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function deleteTerm($taxId, $termId) {
        $stmt = $this->db->prepare("UPDATE terms SET deleted_at=NOW(), updated_by=?, updated_at=NOW() WHERE id=? AND taxonomy_id=?");
        $stmt->execute([$this->uid(), $termId, $taxId]);
        return ['message' => 'Term deleted'];
    }

    /** Reorder terms: body { order: [termId, ...] } */
    public function reorderTerms($taxId) {
        $in = $this->body();
        $order = $in['order'] ?? [];
        $stmt = $this->db->prepare("UPDATE terms SET `order`=?, updated_at=NOW() WHERE id=? AND taxonomy_id=?");
        foreach (array_values($order) as $i => $termId) {
            $stmt->execute([$i, $termId, $taxId]);
        }
        return ['message' => 'Reordered', 'count' => count($order)];
    }

    /* ---- helpers ---- */
    private function body(): array { return json_decode(file_get_contents('php://input'), true) ?: []; }
    private function uid(): int { return (int)($GLOBALS['current_user']['user_id'] ?? $_SESSION['user_id'] ?? 1); }
    private function slugify($t) { $t = strtolower(trim($t)); $t = preg_replace('/[^a-z0-9-]/', '-', $t); return trim(preg_replace('/-+/', '-', $t), '-'); }
}
