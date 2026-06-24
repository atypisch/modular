<?php
/**
 * Post Types API Controller
 * Handles CRUD operations for post types (including mode and schema)
 */

namespace Modular\API;

class PostTypesController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function index() {
        $stmt = $this->db->query("
            SELECT pt.*, 
                   COALESCE(u1.username, u1.email, 'Unknown') as created_by_name,
                   COALESCE(u2.username, u2.email, 'Unknown') as updated_by_name
            FROM post_types pt
            LEFT JOIN users u1 ON pt.created_by = u1.id
            LEFT JOIN users u2 ON pt.updated_by = u2.id
            WHERE pt.deleted_at IS NULL
            ORDER BY pt.name
        ");
        $postTypes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($postTypes as &$pt) {
            $this->decodePostTypeRow($pt);
        }
        
        return $postTypes;
    }
    
    /**
     * Get post type by id or slug.
     */
    public function show($idOrSlug) {
        $byId = is_numeric($idOrSlug);
        $col = $byId ? 'pt.id' : 'pt.slug';
        $stmt = $this->db->prepare("
            SELECT pt.*, 
                   COALESCE(u1.username, u1.email, 'Unknown') as created_by_name,
                   COALESCE(u2.username, u2.email, 'Unknown') as updated_by_name
            FROM post_types pt
            LEFT JOIN users u1 ON pt.created_by = u1.id
            LEFT JOIN users u2 ON pt.updated_by = u2.id
            WHERE {$col} = ? AND pt.deleted_at IS NULL
        ");
        $stmt->execute([$idOrSlug]);
        $postType = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$postType) {
            return ['error' => 'Post type not found', 'code' => 404];
        }
        
        $this->decodePostTypeRow($postType);
        return $postType;
    }
    
    private function decodePostTypeRow(array &$row): void {
        if (isset($row['supports']) && is_string($row['supports'])) {
            $row['supports'] = json_decode($row['supports'], true);
        }
        if (isset($row['settings']) && is_string($row['settings'])) {
            $row['settings'] = json_decode($row['settings'], true);
        }
        if (isset($row['schema']) && is_string($row['schema'])) {
            $row['schema'] = json_decode($row['schema'], true);
        }
        $row['mode'] = $row['mode'] ?? 'fixed';
    }
    
    public function create() {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        
        $name = $input['name'] ?? '';
        $slug = $input['slug'] ?? $this->slugify($name);
        $description = $input['description'] ?? '';
        $public = $input['public'] ?? 1;
        $showInMenu = $input['show_in_menu'] ?? 1;
        $supports = json_encode($input['supports'] ?? ['title', 'editor', 'thumbnail']);
        $settings = json_encode($input['settings'] ?? []);
        $mode = isset($input['mode']) ? (string) $input['mode'] : 'fixed';
        if (!in_array($mode, ['fixed', 'modular'], true)) {
            $mode = 'fixed';
        }
        $icon = $input['icon'] ?? 'file';
        $hierarchical = !empty($input['hierarchical']) ? 1 : 0;
        $schema = isset($input['schema']) ? json_encode($this->normalizeSchema($input['schema'])) : null;

        if (empty($name) || empty($slug)) {
            return ['error' => 'Name and slug are required', 'code' => 400];
        }

        // Check if slug exists
        $stmt = $this->db->prepare("SELECT id FROM post_types WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            return ['error' => 'Slug already exists', 'code' => 409];
        }

        $stmt = $this->db->prepare("
            INSERT INTO post_types
            (name, slug, description, icon, hierarchical, public, show_in_menu, supports, settings, mode, `schema`,
             created_by, updated_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $name, $slug, $description, $icon, $hierarchical, $public, $showInMenu, $supports, $settings, $mode, $schema,
            $userId, $userId
        ]);
        
        $id = $this->db->lastInsertId();
        return $this->show((string) $id);
    }
    
    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        
        $existing = $this->show($id);
        if (isset($existing['error'])) {
            return $existing;
        }
        
        $name = $input['name'] ?? $existing['name'];
        $slug = $input['slug'] ?? $existing['slug'];
        $description = $input['description'] ?? $existing['description'];
        $public = $input['public'] ?? $existing['public'];
        $showInMenu = $input['show_in_menu'] ?? $existing['show_in_menu'];
        $supports = json_encode($input['supports'] ?? $existing['supports']);
        $settings = json_encode($input['settings'] ?? $existing['settings']);
        $mode = isset($input['mode']) ? (string) $input['mode'] : ($existing['mode'] ?? 'fixed');
        if (!in_array($mode, ['fixed', 'modular'], true)) {
            $mode = 'fixed';
        }
        $icon = $input['icon'] ?? ($existing['icon'] ?? 'file');
        $hierarchical = array_key_exists('hierarchical', $input) ? (!empty($input['hierarchical']) ? 1 : 0) : ($existing['hierarchical'] ?? 0);
        if (array_key_exists('schema', $input)) {
            $existingSchema = is_array($existing['schema']) ? $existing['schema'] : [];
            $incomingSchema = is_array($input['schema']) ? $input['schema'] : [];
            $schema = json_encode($this->normalizeSchema(array_merge($existingSchema, $incomingSchema)));
        } elseif (isset($existing['schema']) && is_array($existing['schema'])) {
            $schema = json_encode($existing['schema']);
        } else {
            $schema = $existing['schema'] ?? null;
        }

        if ($slug !== $existing['slug']) {
            $stmt = $this->db->prepare("SELECT id FROM post_types WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $existing['id']]);
            if ($stmt->fetch()) {
                return ['error' => 'Slug already exists', 'code' => 409];
            }
        }

        $stmt = $this->db->prepare("
            UPDATE post_types
            SET name = ?, slug = ?, description = ?, icon = ?, hierarchical = ?, public = ?, show_in_menu = ?,
                supports = ?, settings = ?, mode = ?, `schema` = ?, updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $name, $slug, $description, $icon, $hierarchical, $public, $showInMenu, $supports, $settings, $mode, $schema,
            $userId, $existing['id']
        ]);
        
        return $this->show((string) $existing['id']);
    }
    
    public function delete($id) {
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        $existing = $this->show($id);
        if (isset($existing['error'])) {
            return $existing;
        }
        
        $stmt = $this->db->prepare("
            UPDATE post_types 
            SET deleted_at = NOW(), updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$userId, $existing['id']]);
        
        return ['message' => 'Post type deleted'];
    }
    
    /**
     * Normalize a schema payload to canonical form (ui/storage/output per field)
     * before persisting, so the editor renderer and PHP/JSON output keep working
     * after a field-builder save. Preserves extra top-level keys (e.g. field_groups).
     */
    private function normalizeSchema($schema) {
        if (!is_array($schema)) {
            return $schema;
        }
        if (!class_exists(\Modular\Fields\FieldSchemaMap::class, false)) {
            require_once dirname(__DIR__) . '/fields/FieldSchemaMap.php';
        }
        return array_merge($schema, \Modular\Fields\FieldSchemaMap::normalizeSchema($schema));
    }

    private function slugify($text) {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
}


