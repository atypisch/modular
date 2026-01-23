<?php
/**
 * Post Types API Controller
 * Handles CRUD operations for post types
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
                   u1.username as created_by_name,
                   u2.username as updated_by_name
            FROM post_types pt
            LEFT JOIN users u1 ON pt.created_by = u1.id
            LEFT JOIN users u2 ON pt.updated_by = u2.id
            WHERE pt.deleted_at IS NULL
            ORDER BY pt.name
        ");
        $postTypes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($postTypes as &$pt) {
            $pt['supports'] = json_decode($pt['supports'], true);
            $pt['settings'] = json_decode($pt['settings'], true);
        }
        
        return $postTypes;
    }
    
    public function show($id) {
        $stmt = $this->db->prepare("
            SELECT pt.*, 
                   u1.username as created_by_name,
                   u2.username as updated_by_name
            FROM post_types pt
            LEFT JOIN users u1 ON pt.created_by = u1.id
            LEFT JOIN users u2 ON pt.updated_by = u2.id
            WHERE pt.id = ? AND pt.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        $postType = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$postType) {
            return ['error' => 'Post type not found', 'code' => 404];
        }
        
        $postType['supports'] = json_decode($postType['supports'], true);
        $postType['settings'] = json_decode($postType['settings'], true);
        
        return $postType;
    }
    
    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        
        $name = $input['name'] ?? '';
        $slug = $input['slug'] ?? $this->slugify($name);
        $description = $input['description'] ?? '';
        $public = $input['public'] ?? 1;
        $showInMenu = $input['show_in_menu'] ?? 1;
        $supports = json_encode($input['supports'] ?? ['title', 'editor', 'thumbnail']);
        $settings = json_encode($input['settings'] ?? []);
        
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
            (name, slug, description, public, show_in_menu, supports, settings, 
             created_by, updated_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $name, $slug, $description, $public, $showInMenu, $supports, $settings,
            $userId, $userId
        ]);
        
        $id = $this->db->lastInsertId();
        return $this->show($id);
    }
    
    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        
        // Get existing
        $existing = $this->show($id);
        if (isset($existing['error'])) {
            return $existing;
        }
        
        $name = $input['name'] ?? $existing['name'];
        $slug = $input['slug'] ?? $existing['slug'];
        $description = $input['description'] ?? $existing['description'];
        $public = $input['public'] ?? $existing['public'];
        $showInMenu = $input['show_in_menu'] ?? $existing['show_in_menu'];
        $supports = json_encode($input['supports'] ?? json_decode($existing['supports'], true));
        $settings = json_encode($input['settings'] ?? json_decode($existing['settings'], true));
        
        // Check slug uniqueness if changed
        if ($slug !== $existing['slug']) {
            $stmt = $this->db->prepare("SELECT id FROM post_types WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id]);
            if ($stmt->fetch()) {
                return ['error' => 'Slug already exists', 'code' => 409];
            }
        }
        
        $stmt = $this->db->prepare("
            UPDATE post_types 
            SET name = ?, slug = ?, description = ?, public = ?, show_in_menu = ?,
                supports = ?, settings = ?, updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $name, $slug, $description, $public, $showInMenu, $supports, $settings,
            $userId, $id
        ]);
        
        return $this->show($id);
    }
    
    public function delete($id) {
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        
        $stmt = $this->db->prepare("
            UPDATE post_types 
            SET deleted_at = NOW(), updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$userId, $id]);
        
        return ['message' => 'Post type deleted'];
    }
    
    private function slugify($text) {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
}


