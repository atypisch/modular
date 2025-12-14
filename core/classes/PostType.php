<?php
/**
 * PostType Class
 * Handles creation and management of custom post types
 */
class PostType {
    private $db;
    private $table = 'post_types';

    public function __construct($db = null) {
        if ($db === null) {
            // Use global database connection if available
            if (isset($GLOBALS['db'])) {
                $this->db = $GLOBALS['db'];
            } else {
                // Fallback: try to get from config
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
     * Create a new post type
     * Supports both old format (nicename, single_name, plural_name) and new format (slug)
     */
    public function create($data) {
        try {
            // Validate required fields - support both old and new formats
            if (empty($data['name'])) {
                throw new Exception("name is required");
            }
            
            // Determine slug - use slug if provided, otherwise use nicename or generate from name
            $slug = $data['slug'] ?? $data['nicename'] ?? $this->sanitizeSlug($data['name']);
            $slug = $this->sanitizeSlug($slug);

            // Check if post type already exists
            if ($this->getBySlug($slug)) {
                throw new Exception("A post type with this slug already exists");
            }

            // Get current user ID for created_by/updated_by
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
            
            // Build supports JSON from individual support flags or use provided supports
            $supports = [];
            if (isset($data['supports']) && is_array($data['supports'])) {
                $supports = $data['supports'];
            } else {
                if (!empty($data['supports_title'])) $supports[] = 'title';
                if (!empty($data['supports_editor'])) $supports[] = 'editor';
                if (!empty($data['supports_thumbnail'])) $supports[] = 'thumbnail';
                if (empty($supports)) $supports = ['title', 'editor']; // Default
            }

            $sql = "INSERT INTO {$this->table} (
                name, slug, description, public, show_in_menu, supports,
                created_by, updated_by, created_at, updated_at
            ) VALUES (
                :name, :slug, :description, :public, :show_in_menu, :supports,
                :created_by, :updated_by, NOW(), NOW()
            )";

            $stmt = $this->db->prepare($sql);
            
            $stmt->execute([
                ':name' => $data['name'],
                ':slug' => $slug,
                ':description' => $data['description'] ?? '',
                ':public' => isset($data['public']) ? (int)$data['public'] : (isset($data['is_public']) ? (int)$data['is_public'] : 1),
                ':show_in_menu' => isset($data['show_in_menu']) ? (int)$data['show_in_menu'] : 1,
                ':supports' => json_encode($supports),
                ':created_by' => $user_id,
                ':updated_by' => $user_id
            ]);

            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Database error in PostType::create: " . $e->getMessage());
            throw new Exception("Failed to create post type: " . $e->getMessage());
        }
    }

    /**
     * Get all post types
     */
    public function getAll() {
        try {
            $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY name");
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Parse supports JSON for backward compatibility
            foreach ($results as &$result) {
                if (isset($result['supports']) && is_string($result['supports'])) {
                    $supports = json_decode($result['supports'], true);
                    if (is_array($supports)) {
                        $result['supports_title'] = in_array('title', $supports);
                        $result['supports_editor'] = in_array('editor', $supports);
                        $result['supports_thumbnail'] = in_array('thumbnail', $supports);
                    }
                }
                // For backward compatibility with old code expecting nicename
                if (!isset($result['nicename']) && isset($result['slug'])) {
                    $result['nicename'] = $result['slug'];
                }
                // For backward compatibility with old code expecting is_public
                if (!isset($result['is_public']) && isset($result['public'])) {
                    $result['is_public'] = (bool)$result['public'];
                }
            }
            
            return $results;
        } catch (\PDOException $e) {
            error_log("Database error in PostType::getAll: " . $e->getMessage());
            throw new Exception("Failed to get post types: " . $e->getMessage());
        }
    }

    /**
     * Get a post type by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result) {
                // Parse supports JSON for backward compatibility
                if (isset($result['supports']) && is_string($result['supports'])) {
                    $supports = json_decode($result['supports'], true);
                    if (is_array($supports)) {
                        $result['supports_title'] = in_array('title', $supports);
                        $result['supports_editor'] = in_array('editor', $supports);
                        $result['supports_thumbnail'] = in_array('thumbnail', $supports);
                    }
                }
                // For backward compatibility
                if (!isset($result['nicename']) && isset($result['slug'])) {
                    $result['nicename'] = $result['slug'];
                }
                if (!isset($result['is_public']) && isset($result['public'])) {
                    $result['is_public'] = (bool)$result['public'];
                }
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Database error in PostType::getById: " . $e->getMessage());
            throw new Exception("Failed to get post type: " . $e->getMessage());
        }
    }

    /**
     * Get a post type by slug
     */
    public function getBySlug($slug) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = ? AND deleted_at IS NULL");
            $stmt->execute([$slug]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database error in PostType::getBySlug: " . $e->getMessage());
            throw new Exception("Failed to get post type");
        }
    }

    /**
     * Get a post type by nicename (for backward compatibility)
     */
    public function getByNicename($nicename) {
        return $this->getBySlug($nicename);
    }

    /**
     * Update a post type
     */
    public function update($id, $data) {
        try {
            // Get existing post type
            $existing = $this->getById($id);
            if (!$existing) {
                throw new Exception("Post type not found");
            }

            // Determine slug - support both slug and nicename
            $slug = $existing['slug'];
            if (isset($data['slug'])) {
                $slug = $this->sanitizeSlug($data['slug']);
            } elseif (isset($data['nicename'])) {
                $slug = $this->sanitizeSlug($data['nicename']);
            }

            // Check if slug is being changed and if new slug already exists
            if ($slug !== $existing['slug']) {
                if ($this->getBySlug($slug)) {
                    throw new Exception("A post type with this slug already exists");
                }
            }

            // Get current user ID for updated_by
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
            
            // Build supports JSON
            $supports = [];
            if (isset($data['supports']) && is_array($data['supports'])) {
                $supports = $data['supports'];
            } else {
                if (!empty($data['supports_title'])) $supports[] = 'title';
                if (!empty($data['supports_editor'])) $supports[] = 'editor';
                if (!empty($data['supports_thumbnail'])) $supports[] = 'thumbnail';
                // If no supports specified, keep existing
                if (empty($supports) && isset($existing['supports'])) {
                    $existing_supports = json_decode($existing['supports'], true);
                    if (is_array($existing_supports)) {
                        $supports = $existing_supports;
                    }
                }
                if (empty($supports)) $supports = ['title', 'editor']; // Default
            }

            $sql = "UPDATE {$this->table} SET
                name = :name,
                slug = :slug,
                description = :description,
                public = :public,
                show_in_menu = :show_in_menu,
                supports = :supports,
                updated_by = :updated_by,
                updated_at = NOW()
                WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'] ?? $existing['name'],
                ':slug' => $slug,
                ':description' => $data['description'] ?? $existing['description'] ?? '',
                ':public' => isset($data['public']) ? (int)$data['public'] : (isset($data['is_public']) ? (int)$data['is_public'] : (int)$existing['public']),
                ':show_in_menu' => isset($data['show_in_menu']) ? (int)$data['show_in_menu'] : (int)($existing['show_in_menu'] ?? 1),
                ':supports' => json_encode($supports),
                ':updated_by' => $user_id
            ]);
        } catch (\PDOException $e) {
            error_log("Database error in PostType::update: " . $e->getMessage());
            throw new Exception("Failed to update post type: " . $e->getMessage());
        }
    }

    /**
     * Delete a post type (soft delete)
     */
    public function delete($id) {
        try {
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
            $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted_at = NOW(), updated_by = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$user_id, $id]);
        } catch (\PDOException $e) {
            error_log("Database error in PostType::delete: " . $e->getMessage());
            throw new Exception("Failed to delete post type: " . $e->getMessage());
        }
    }

    /**
     * Sanitize a string to create a valid slug
     */
    private function sanitizeSlug($string) {
        // Convert to lowercase
        $string = strtolower($string);
        
        // Replace spaces and other characters with hyphens
        $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
        
        // Remove multiple consecutive hyphens
        $string = preg_replace('/-+/', '-', $string);
        
        // Remove leading and trailing hyphens
        $string = trim($string, '-');
        
        return $string;
    }
}
