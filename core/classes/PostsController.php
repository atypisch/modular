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

    /**
     * Resolve the featured-image media id from a post's data payload so the
     * posts.featured_image column stays in sync with data.featured_image (front-end
     * helpers read the column first). Returns null when the key is absent (leave the
     * column untouched), 0 when explicitly cleared, or the media id.
     */
    private function featuredImageId($data): ?int {
        if (!is_array($data) || !array_key_exists('featured_image', $data)) return null;
        $fi = $data['featured_image'];
        if ($fi === null || $fi === '' || $fi === 0 || $fi === '0') return 0;
        if (is_array($fi)) return (int) ($fi['id'] ?? 0);
        if (is_numeric($fi)) return (int) $fi;
        return null;
    }

    /** Drop cached post-list queries after a write so overviews/archives reflect the change. */
    private function invalidatePostCache(): void {
        if (function_exists('cache_delete_prefix')) {
            cache_delete_prefix('mdposts');
        }
    }

    /** Author-name expression (username → email). Needs the users join. */
    private function authorNameExpr(bool $alias = true): string {
        $expr = "COALESCE(u.username, u.email, 'Unknown')";
        return $alias ? $expr . " as author_name" : $expr;
    }

    /**
     * ORDER BY clause for the overview, from whitelisted ?orderby / ?order params.
     *
     * Default is publication date, not `posts`.`order`: manual ordering is only
     * meaningful for post types someone has actually dragged into shape, and
     * everywhere else every row shares order=0, which silently degraded the list
     * to created_at while the column on screen showed published_at.
     */
    private function orderClause(bool $hasAuthorJoin = true, string $default = 'date'): string {
        $dir = strtolower((string) ($_GET['order'] ?? '')) === 'asc' ? 'ASC' : 'DESC';
        $flip = $dir === 'ASC' ? 'DESC' : 'ASC';
        $by = (string) ($_GET['orderby'] ?? $default);
        if ($by === 'author' && !$hasAuthorJoin) { $by = 'date'; }
        switch ($by) {
            case 'title':    return " ORDER BY p.title $dir, p.id DESC";
            case 'status':   return " ORDER BY p.status $dir, COALESCE(p.published_at, p.created_at) DESC, p.id DESC";
            case 'author':   return " ORDER BY author_name $dir, COALESCE(p.published_at, p.created_at) DESC, p.id DESC";
            case 'modified': return " ORDER BY p.updated_at $dir, p.id DESC";
            case 'order':    return " ORDER BY p.`order` $flip, COALESCE(p.published_at, p.created_at) DESC, p.id DESC";
            default:         return " ORDER BY COALESCE(p.published_at, p.created_at) $dir, p.id DESC";
        }
    }

    /** Distinct author names within a post type, to populate the overview's Filter popover. */
    private function authorList($postTypeId): array {
        $sql = "SELECT DISTINCT " . $this->authorNameExpr() . "
                FROM posts p LEFT JOIN users u ON p.author_id = u.id
                WHERE p.deleted_at IS NULL";
        $params = [];
        if ($postTypeId) { $sql .= " AND p.post_type_id = ?"; $params[] = $postTypeId; }
        $sql .= " ORDER BY author_name ASC LIMIT 200";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return array_values(array_filter($stmt->fetchAll(\PDO::FETCH_COLUMN)));
        } catch (\Throwable $e) {
            return [];
        }
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
        // Auto-publish scheduled posts that are now due so the overview/counts are accurate.
        // The API entrypoint doesn't load these helpers, so pull them in on demand (idempotent).
        if (!function_exists('md_publish_due_posts')) {
            $core = defined('CORE_PATH') ? CORE_PATH : dirname(__DIR__);
            foreach (['/functions/cache.php', '/functions/template-helpers.php'] as $f) {
                if (is_file($core . $f)) { require_once $core . $f; }
            }
        }
        if (function_exists('md_publish_due_posts')) { md_publish_due_posts(); }
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
            $cols = "p.id, p.title, p.slug, p.status, p.`order`, p.published_at, p.created_at, p.deleted_at, pt.name as post_type_name, pt.slug as post_type_slug, " . $this->authorNameExpr();
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
        // Overview filters (the Filter popover). Dates bound on the effective
        // publication date so they line up with what the Published column shows.
        $author = trim((string) ($_GET['author'] ?? ''));
        if ($author !== '' && !$light) {
            $where .= " AND " . $this->authorNameExpr(false) . " = ?";
            $params[] = $author;
        }
        $from = trim((string) ($_GET['date_from'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $where .= " AND DATE(COALESCE(p.published_at, p.created_at)) >= ?";
            $params[] = $from;
        }
        $to = trim((string) ($_GET['date_to'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $where .= " AND DATE(COALESCE(p.published_at, p.created_at)) <= ?";
            $params[] = $to;
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
            $sql .= $this->orderClause(!$light) . " LIMIT " . $perPage . " OFFSET " . $offset;
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
                'authors' => $this->authorList($postTypeId),
            ];
        }

        // Legacy non-paginated shape (relationship picker, exports): a bare array.
        // Legacy non-paginated shape keeps manual order as its default so the
        // relationship picker and exports are unaffected; ?orderby still works.
        $sql .= $this->orderClause(!$light, 'order') . " LIMIT " . (int) $limit;
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
        
        $publishedAt = array_key_exists('published_at', $input)
            ? $this->normalizePublishedAt($input['published_at'])
            : null;
        $publishErr = $this->applyPublishState($status, $publishedAt);
        if ($publishErr) {
            return $publishErr;
        }

        $fiId = $this->featuredImageId($input['data'] ?? null);
        $featuredCol = ($fiId !== null && $fiId > 0) ? $fiId : null;

        $stmt = $this->db->prepare("
            INSERT INTO posts
            (post_type_id, slug, title, status, `data`, `layout`, component_data, meta_data, featured_image,
             author_id, created_by, updated_by, published_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $postTypeId, $slug, $title, $status, $dataJson, $layoutJson, $componentData, $metaData, $featuredCol,
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
        // Manual sort position. Editable as a plain number in the editor; drag &
        // drop in the overview writes the same column through reorder().
        $order = array_key_exists('order', $input) ? (int) $input['order'] : (int) ($existing['order'] ?? 0);
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
        
        $publishedAt = array_key_exists('published_at', $input)
            ? $this->normalizePublishedAt($input['published_at'])
            : ($existing['published_at'] ?: null);
        $publishErr = $this->applyPublishState($status, $publishedAt);
        if ($publishErr) {
            return $publishErr;
        }
        
        // Keep the featured_image column in sync with data.featured_image (front-end reads
        // the column first). Untouched when the payload doesn't carry a featured_image key.
        $effectiveData = array_key_exists('data', $input) ? $input['data'] : json_decode($existing['data'] ?? 'null', true);
        $fiId = $this->featuredImageId(is_array($effectiveData) ? $effectiveData : null);
        $featuredCol = $fiId === null ? ($existing['featured_image'] ?? null) : ($fiId > 0 ? $fiId : null);

        $stmt = $this->db->prepare("
            UPDATE posts
            SET slug = ?, title = ?, status = ?, `order` = ?, `data` = ?, `layout` = ?, component_data = ?, meta_data = ?,
                featured_image = ?, published_at = ?, updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $slug, $title, $status, $order, $dataJson, $layoutJson, $componentData, $metaData, $featuredCol, $publishedAt, $userId, $id
        ]);
        
        // Create revision
        $this->createRevision($id, $userId, $title, $componentData, $metaData);

        $this->invalidatePostCache();
        return $this->show($id);
    }

    /**
     * POST /posts/{id}/preview — stash the editor's *unsaved* values in the session
     * and hand back the front-end URL that will render them.
     *
     * Nothing is written to the posts table: previewing a draft must never change
     * what is published. The front controller reads this back and overlays it.
     */
    public function preview($id) {
        $id = (int) $id;
        if ($id <= 0) return ['error' => 'Unknown post', 'code' => 400];

        $stmt = $this->db->prepare("
            SELECT p.slug, pt.slug AS post_type_slug
            FROM posts p INNER JOIN post_types pt ON p.post_type_id = pt.id
            WHERE p.id = ? AND p.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return ['error' => 'Post not found', 'code' => 404];

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $draft = [];
        foreach (['title', 'slug', 'excerpt', 'status', 'template'] as $k) {
            if (array_key_exists($k, $input)) $draft[$k] = $input[$k];
        }
        if (isset($input['data']) && is_array($input['data'])) $draft['data'] = $input['data'];
        if (isset($input['layout']) && is_array($input['layout'])) $draft['layout'] = $input['layout'];

        if (session_status() === PHP_SESSION_NONE) session_start();
        // One slot per post, replaced on each preview — a scratch buffer, not a
        // revision store, and it must not grow without bound.
        $_SESSION['md_preview'] = ['post_id' => $id, 'draft' => $draft, 'time' => time()];

        $this->loadPermalinks();
        // Built from the stored slug on purpose: an unsaved slug rename has no row
        // to resolve against, and the draft title/body is what the preview is for.
        $url = $this->permalinkFor(['post_type_slug' => $row['post_type_slug'], 'slug' => $row['slug']]);
        $sep = strpos($url, '?') === false ? '?' : '&';
        return ['url' => $url . $sep . 'preview=1'];
    }

    /**
     * POST /posts/reorder — body { ids: [postId, ...] } in the order they should
     * appear. Renumbers `order` to 0..n-1 in one transaction, so a drag never
     * leaves the list half-sorted. Ids that are not real posts are skipped.
     */
    public function reorder() {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $ids = array_values(array_filter(array_map('intval', (array) ($input['ids'] ?? []))));
        if (!$ids) return ['error' => 'Send the post ids in their new order', 'code' => 400];

        $place = implode(',', array_fill(0, count($ids), '?'));
        $known = $this->db->prepare("SELECT id FROM posts WHERE id IN ($place) AND deleted_at IS NULL");
        $known->execute($ids);
        $valid = array_map('intval', $known->fetchAll(\PDO::FETCH_COLUMN, 0));
        if (!$valid) return ['error' => 'None of those posts exist', 'code' => 404];

        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE posts SET `order` = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $position = 0;
            foreach ($ids as $id) {
                if (!in_array($id, $valid, true)) continue;
                $stmt->execute([$position++, $userId, $id]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['error' => 'Could not save the new order', 'code' => 500];
        }

        $this->invalidatePostCache();
        return ['ok' => true, 'count' => $position];
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

    /** Accept datetime-local or SQL datetime; empty becomes null. */
    private function normalizePublishedAt($raw): ?string {
        if ($raw === null) return null;
        $raw = trim((string) $raw);
        if ($raw === '') return null;
        $raw = str_replace('T', ' ', $raw);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $raw .= ' 00:00:00';
        }
        $ts = strtotime($raw);
        if ($ts === false) return null;
        return date('Y-m-d H:i:s', $ts);
    }

    /** Keep scheduled dates; fill published_at when going live now. Returns an error payload or null. */
    private function applyPublishState(string &$status, ?string &$publishedAt): ?array {
        $allowed = ['draft', 'published', 'scheduled', 'private'];
        if (!in_array($status, $allowed, true)) {
            $status = 'draft';
        }
        if ($status === 'scheduled') {
            if (!$publishedAt) {
                return ['error' => 'A publication date is required to schedule', 'code' => 400];
            }
            if (strtotime($publishedAt) <= time()) {
                $status = 'published';
            }
        } elseif ($status === 'published' && !$publishedAt) {
            $publishedAt = date('Y-m-d H:i:s');
        }
        return null;
    }
}


