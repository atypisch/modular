<?php
/**
 * Posts API Controller
 * Handles CRUD operations for posts
 */

namespace Modular\API;

class PostsController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }

    /** Drop cached post-list queries after a write so overviews/archives reflect the change. */
    private function invalidatePostCache(): void {
        if (function_exists('cache_delete_prefix')) {
            cache_delete_prefix('mdposts');
        }
    }

    /** Author-name expression (username → email). Needs the users join. */
    private function authorNameExpr(): string {
        return "COALESCE(u.username, u.email, 'Unknown') as author_name";
    }

    /**
     * Search clause covering title + custom fields so the admin overview keeps finding posts
     * by values stored in component_data/meta_data/data (not just the title). Adds 5 params.
     */
    private function searchClause(string $q, array &$params): string {
        $like = '%' . $q . '%';
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        return " AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.component_data LIKE ? OR p.meta_data LIKE ? OR p.data LIKE ?)";
    }

    /** Ensure get_post_permalink() (permalink structure builder) is available. */
    private function loadPermalinks() {
        if (function_exists('get_post_permalink')) { return; }
        $core = defined('CORE_PATH') ? CORE_PATH : dirname(__DIR__);
        foreach (['/functions/permalinks.php', '/functions/template-helpers.php'] as $f) {
            if (is_file($core . $f)) { require_once $core . $f; }
        }
    }

    /** Public front-end URL for a post row (joined with post_type_slug). */
    private function permalinkFor(array $row): string {
        return function_exists('get_post_permalink')
            ? get_post_permalink($row['post_type_slug'] ?? '', $row['slug'] ?? '')
            : '';
    }

    public function index($postTypeId = null) {
        // Lightweight mode (?fields=light): id/title/slug/status only — relationship field picker.
        // List mode (?fields=list): lean admin-overview columns + author/url.
        $light = (($_GET['fields'] ?? '') === 'light');
        $listMode = (($_GET['fields'] ?? '') === 'list');
        // Pagination kicks in when ?page or ?per_page is present; returns {items,total,...}.
        $paginate = isset($_GET['page']) || isset($_GET['per_page']);
        $limit = isset($_GET['limit']) ? max(1, min(2000, (int) $_GET['limit'])) : 100;
        $q = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $includeTrashed = in_array((string) ($_GET['include_trashed'] ?? ''), ['1', 'true', 'yes'], true);

        if ($light) {
            $cols = "p.id, p.title, p.slug, p.status, p.deleted_at, pt.name as post_type_name, pt.slug as post_type_slug";
            $join = "";
        } elseif ($listMode) {
            $cols = "p.id, p.title, p.slug, p.status, p.published_at, p.created_at, p.deleted_at, pt.name as post_type_name, pt.slug as post_type_slug, " . $this->authorNameExpr();
            $join = "LEFT JOIN users u ON p.author_id = u.id";
        } else {
            $cols = "p.*, pt.name as post_type_name, pt.slug as post_type_slug, " . $this->authorNameExpr();
            $join = "LEFT JOIN users u ON p.author_id = u.id";
        }

        // Shared WHERE so the data query and the COUNT use identical filters.
        $where = " WHERE 1=1";
        $params = [];
        if ($postTypeId) { $where .= " AND p.post_type_id = ?"; $params[] = $postTypeId; }
        if ($status === 'trash') {
            $where .= " AND p.deleted_at IS NOT NULL";
        } elseif ($status !== '' && $status !== 'all') {
            $where .= " AND p.status = ? AND p.deleted_at IS NULL";
            $params[] = $status;
        } elseif (!$includeTrashed) {
            $where .= " AND p.deleted_at IS NULL";
        }
        // The relationship picker (light) keeps its title-only search; the admin overview
        // searches custom fields too so posts/tracks are findable by any stored value.
        if ($q !== '') {
            if ($light) { $where .= " AND p.title LIKE ?"; $params[] = '%' . $q . '%'; }
            else { $where .= $this->searchClause($q, $params); }
        }

        $sql = "SELECT $cols FROM posts p INNER JOIN post_types pt ON p.post_type_id = pt.id $join" . $where;

        if ($paginate) {
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $perPage = max(1, min(200, (int) ($_GET['per_page'] ?? 50)));
            $offset = ($page - 1) * $perPage;
            $sql .= " ORDER BY p.created_at DESC LIMIT " . $perPage . " OFFSET " . $offset;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM posts p INNER JOIN post_types pt ON p.post_type_id = pt.id $join" . $where);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            if (!$light) {
                $this->loadPermalinks();
                foreach ($rows as &$row) {
                    if (!$listMode) {
                        $row['component_data'] = json_decode($row['component_data'], true);
                        $row['meta_data'] = json_decode($row['meta_data'], true);
                    }
                    $row['url'] = $this->permalinkFor($row);
                }
                unset($row);
            }

            return [
                'items' => $rows,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'counts' => $this->statusCounts($postTypeId, $q),
            ];
        }

        // Legacy non-paginated shape (relationship picker, exports): a bare array.
        $sql .= " ORDER BY p.created_at DESC LIMIT " . (int) $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($light) {
            return $posts; // no JSON decode / permalink work — keep it cheap
        }

        $this->loadPermalinks();
        foreach ($posts as &$post) {
            if (!$listMode) {
                $post['component_data'] = json_decode($post['component_data'], true);
                $post['meta_data'] = json_decode($post['meta_data'], true);
            }
            $post['url'] = $this->permalinkFor($post);
        }

        return $posts;
    }

    /** Tab-badge counts (live vs trashed) for a post type, honouring the active search. */
    private function statusCounts($postTypeId, string $q = ''): array {
        $where = " WHERE 1=1";
        $params = [];
        if ($postTypeId) { $where .= " AND p.post_type_id = ?"; $params[] = $postTypeId; }
        if ($q !== '') { $where .= $this->searchClause($q, $params); }
        $stmt = $this->db->prepare(
            "SELECT SUM(p.deleted_at IS NULL) AS live, SUM(p.deleted_at IS NOT NULL) AS trash FROM posts p" . $where
        );
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        return ['all' => (int) ($row['live'] ?? 0), 'trash' => (int) ($row['trash'] ?? 0)];
    }
    
    public function show($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   pt.name as post_type_name, pt.slug as post_type_slug,
                   COALESCE(u.username, u.email, 'Unknown') as author_name
            FROM posts p
            INNER JOIN post_types pt ON p.post_type_id = pt.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.id = ? AND p.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        $post = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$post) {
            return ['error' => 'Post not found', 'code' => 404];
        }
        
        $post['component_data'] = json_decode($post['component_data'], true);
        $post['meta_data'] = json_decode($post['meta_data'], true);
        $this->loadPermalinks();
        $post['url'] = $this->permalinkFor($post);

        return $post;
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        
        $postTypeId = $input['post_type_id'] ?? null;
        $title = $input['title'] ?? '';
        $slug = $input['slug'] ?? $this->slugify($title);
        $status = $input['status'] ?? 'draft';
        $componentData = json_encode($input['component_data'] ?? []);
        $metaData = json_encode($input['meta_data'] ?? []);
        $dataJson = json_encode($input['data'] ?? new \stdClass());
        $layoutJson = json_encode($input['layout'] ?? new \stdClass());

        if (empty($postTypeId) || empty($title)) {
            return ['error' => 'Post type ID and title are required', 'code' => 400];
        }
        
        // Verify post type exists
        $stmt = $this->db->prepare("SELECT id FROM post_types WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$postTypeId]);
        if (!$stmt->fetch()) {
            return ['error' => 'Post type not found', 'code' => 404];
        }
        
        // Check slug uniqueness
        $stmt = $this->db->prepare("SELECT id FROM posts WHERE slug = ? AND post_type_id = ?");
        $stmt->execute([$slug, $postTypeId]);
        if ($stmt->fetch()) {
            return ['error' => 'Slug already exists for this post type', 'code' => 409];
        }
        
        $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;
        
        $stmt = $this->db->prepare("
            INSERT INTO posts
            (post_type_id, slug, title, status, `data`, `layout`, component_data, meta_data,
             author_id, created_by, updated_by, published_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $postTypeId, $slug, $title, $status, $dataJson, $layoutJson, $componentData, $metaData,
            $userId, $userId, $userId, $publishedAt
        ]);
        
        $id = $this->db->lastInsertId();
        $this->invalidatePostCache();
        return $this->show($id);
    }

    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        
        $existing = $this->show($id);
        if (isset($existing['error'])) {
            return $existing;
        }
        
        $title = $input['title'] ?? $existing['title'];
        $slug = $input['slug'] ?? $existing['slug'];
        $status = $input['status'] ?? $existing['status'];
        $componentData = json_encode($input['component_data'] ?? $existing['component_data']);
        $metaData = json_encode($input['meta_data'] ?? $existing['meta_data']);
        // `data` is the canonical field-value store; keep existing raw JSON when not supplied.
        $dataJson = array_key_exists('data', $input) ? json_encode($input['data']) : ($existing['data'] ?? json_encode(new \stdClass()));
        $layoutJson = array_key_exists('layout', $input) ? json_encode($input['layout']) : ($existing['layout'] ?? json_encode(new \stdClass()));

        // Check slug uniqueness if changed
        if ($slug !== $existing['slug']) {
            $stmt = $this->db->prepare("SELECT id FROM posts WHERE slug = ? AND post_type_id = ? AND id != ?");
            $stmt->execute([$slug, $existing['post_type_id'], $id]);
            if ($stmt->fetch()) {
                return ['error' => 'Slug already exists', 'code' => 409];
            }
        }
        
        $publishedAt = $existing['published_at'];
        if ($status === 'published' && !$publishedAt) {
            $publishedAt = date('Y-m-d H:i:s');
        } elseif ($status !== 'published') {
            $publishedAt = null;
        }
        
        $stmt = $this->db->prepare("
            UPDATE posts
            SET slug = ?, title = ?, status = ?, `data` = ?, `layout` = ?, component_data = ?, meta_data = ?,
                published_at = ?, updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $slug, $title, $status, $dataJson, $layoutJson, $componentData, $metaData, $publishedAt, $userId, $id
        ]);
        
        // Create revision
        $this->createRevision($id, $userId, $title, $componentData, $metaData);

        $this->invalidatePostCache();
        return $this->show($id);
    }

    public function delete($id) {
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;

        $stmt = $this->db->prepare("
            UPDATE posts
            SET deleted_at = NOW(), updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$userId, $id]);

        $this->invalidatePostCache();
        return ['message' => 'Post deleted'];
    }
    
    private function createRevision($postId, $userId, $title, $componentData, $metaData) {
        $stmt = $this->db->prepare("
            INSERT INTO post_revisions (post_id, title, component_data, meta_data, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$postId, $title, $componentData, $metaData, $userId]);
    }
    
    private function slugify($text) {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
}


