<?php
/**
 * Taxonomy Class
 * Handles creation and management of taxonomies
 */
class Taxonomy {
    private $db;
    private $table = 'taxonomies';

    public function __construct($db = null) {
        if ($db === null) {
            if (isset($GLOBALS['db'])) {
                $this->db = $GLOBALS['db'];
            } else {
                require_once dirname(dirname(__DIR__)) . '/admin/config/config.php';
                if (isset($GLOBALS['db'])) {
                    $this->db = $GLOBALS['db'];
                } else {
                    throw new Exception("Database connection not available");
                }
            }
        } else {
            $this->db = $db;
        }
    }

    /**
     * Create a new taxonomy
     */
    public function create($data) {
        try {
            if (empty($data['name'])) {
                throw new Exception("name is required");
            }
            
            $slug = $data['slug'] ?? $this->sanitizeSlug($data['name']);
            $slug = $this->sanitizeSlug($slug);

            if ($this->getBySlug($slug)) {
                throw new Exception("A taxonomy with this slug already exists");
            }

            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

            $sql = "INSERT INTO {$this->table} (
                name, slug, description, hierarchical, public, show_in_menu, show_in_rest, rest_base,
                created_by, updated_by, created_at, updated_at
            ) VALUES (
                :name, :slug, :description, :hierarchical, :public, :show_in_menu, :show_in_rest, :rest_base,
                :created_by, :updated_by, NOW(), NOW()
            )";

            $stmt = $this->db->prepare($sql);
            
            $stmt->execute([
                ':name' => $data['name'],
                ':slug' => $slug,
                ':description' => $data['description'] ?? '',
                ':hierarchical' => isset($data['hierarchical']) ? (int)$data['hierarchical'] : 0,
                ':public' => isset($data['public']) ? (int)$data['public'] : 1,
                ':show_in_menu' => isset($data['show_in_menu']) ? (int)$data['show_in_menu'] : 1,
                ':show_in_rest' => isset($data['show_in_rest']) ? (int)$data['show_in_rest'] : 1,
                ':rest_base' => $data['rest_base'] ?? $slug,
                ':created_by' => $user_id,
                ':updated_by' => $user_id
            ]);

            $taxonomyId = $this->db->lastInsertId();
            
            // Link to post types if provided
            if (!empty($data['post_types']) && is_array($data['post_types'])) {
                $this->linkToPostTypes($taxonomyId, $data['post_types']);
            }
            
            return $taxonomyId;
        } catch (\PDOException $e) {
            error_log("Database error in Taxonomy::create: " . $e->getMessage());
            throw new Exception("Failed to create taxonomy: " . $e->getMessage());
        }
    }

    /**
     * Get all taxonomies
     */
    public function getAll() {
        try {
            $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY name");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database error in Taxonomy::getAll: " . $e->getMessage());
            throw new Exception("Failed to get taxonomies: " . $e->getMessage());
        }
    }

    /**
     * Get taxonomy by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database error in Taxonomy::getById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get taxonomy by slug
     */
    public function getBySlug($slug) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = ? AND deleted_at IS NULL");
            $stmt->execute([$slug]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database error in Taxonomy::getBySlug: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update taxonomy
     */
    public function update($id, $data) {
        try {
            $existing = $this->getById($id);
            if (!$existing) {
                throw new Exception("Taxonomy not found");
            }

            $slug = $data['slug'] ?? $existing['slug'];
            $slug = $this->sanitizeSlug($slug);

            // Check if slug is already taken by another taxonomy
            $existingBySlug = $this->getBySlug($slug);
            if ($existingBySlug && $existingBySlug['id'] != $id) {
                throw new Exception("A taxonomy with this slug already exists");
            }

            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

            $sql = "UPDATE {$this->table} SET
                name = :name,
                slug = :slug,
                description = :description,
                hierarchical = :hierarchical,
                public = :public,
                show_in_menu = :show_in_menu,
                show_in_rest = :show_in_rest,
                rest_base = :rest_base,
                updated_by = :updated_by,
                updated_at = NOW()
                WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'] ?? $existing['name'],
                ':slug' => $slug,
                ':description' => $data['description'] ?? $existing['description'],
                ':hierarchical' => isset($data['hierarchical']) ? (int)$data['hierarchical'] : (int)$existing['hierarchical'],
                ':public' => isset($data['public']) ? (int)$data['public'] : (int)$existing['public'],
                ':show_in_menu' => isset($data['show_in_menu']) ? (int)$data['show_in_menu'] : (int)$existing['show_in_menu'],
                ':show_in_rest' => isset($data['show_in_rest']) ? (int)$data['show_in_rest'] : (int)$existing['show_in_rest'],
                ':rest_base' => $data['rest_base'] ?? $existing['rest_base'] ?? $slug,
                ':updated_by' => $user_id
            ]);

            // Update post type links if provided
            if (isset($data['post_types']) && is_array($data['post_types'])) {
                $this->unlinkFromAllPostTypes($id);
                $this->linkToPostTypes($id, $data['post_types']);
            }
            
            return true;
        } catch (\PDOException $e) {
            error_log("Database error in Taxonomy::update: " . $e->getMessage());
            throw new Exception("Failed to update taxonomy: " . $e->getMessage());
        }
    }

    /**
     * Delete taxonomy (soft delete)
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            error_log("Database error in Taxonomy::delete: " . $e->getMessage());
            throw new Exception("Failed to delete taxonomy: " . $e->getMessage());
        }
    }

    /**
     * Link taxonomy to post types
     */
    public function linkToPostTypes($taxonomyId, $postTypeIds) {
        try {
            // Remove existing links
            $this->unlinkFromAllPostTypes($taxonomyId);
            
            // Add new links
            $stmt = $this->db->prepare("INSERT INTO post_type_taxonomies (post_type_id, taxonomy_id) VALUES (?, ?)");
            foreach ($postTypeIds as $postTypeId) {
                $stmt->execute([$postTypeId, $taxonomyId]);
            }
            return true;
        } catch (\PDOException $e) {
            error_log("Database error in Taxonomy::linkToPostTypes: " . $e->getMessage());
            throw new Exception("Failed to link taxonomy to post types: " . $e->getMessage());
        }
    }

    /**
     * Unlink taxonomy from all post types
     */
    public function unlinkFromAllPostTypes($taxonomyId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM post_type_taxonomies WHERE taxonomy_id = ?");
            return $stmt->execute([$taxonomyId]);
        } catch (\PDOException $e) {
            error_log("Database error in Taxonomy::unlinkFromAllPostTypes: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get post types linked to this taxonomy
     */
    public function getLinkedPostTypes($taxonomyId) {
        try {
            $stmt = $this->db->prepare("
                SELECT pt.*
                FROM post_types pt
                INNER JOIN post_type_taxonomies ptt ON pt.id = ptt.post_type_id
                WHERE ptt.taxonomy_id = ? AND pt.deleted_at IS NULL
            ");
            $stmt->execute([$taxonomyId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database error in Taxonomy::getLinkedPostTypes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Sanitize slug
     */
    private function sanitizeSlug($text) {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
}
