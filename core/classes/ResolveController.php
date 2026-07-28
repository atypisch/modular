<?php
/**
 * Resolve API Controller
 * Produces the EFFECTIVE fieldset for a post (schema + current values +
 * provenance) for the SPA content editor, and exposes post revisions.
 * Provenance today: post-type schema fields are tagged 'type'. Shared
 * field groups ('shared') and per-post additions ('post') are layered in
 * once the field_groups table lands (Fase 3) and the editor in Fase 5.
 */

namespace Modular\API;

class ResolveController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /** GET /posts/{id}/resolved-fieldset */
    public function resolvedFieldset($postId) {
        $stmt = $this->db->prepare("
            SELECT p.*, pt.name AS post_type_name, pt.slug AS post_type_slug, pt.mode AS post_type_mode, pt.`schema` AS pt_schema
            FROM posts p INNER JOIN post_types pt ON p.post_type_id = pt.id
            WHERE p.id = ? AND p.deleted_at IS NULL
        ");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$post) return ['error' => 'Post not found', 'code' => 404];

        $schema = json_decode($post['pt_schema'] ?? 'null', true);
        $fields = is_array($schema) && isset($schema['fields']) ? $schema['fields'] : (is_array($schema) ? $schema : []);
        // tag provenance for fields that come from the post type definition
        $fields = array_map(function ($f) {
            if (is_array($f) && !isset($f['prov'])) $f['prov'] = 'type';
            return $f;
        }, $fields);

        // Merge active field groups whose location matches this post type (prov = shared).
        $matchedGroups = [];
        $ptSlug = $post['post_type_slug'];
        $fgStmt = $this->db->query("SELECT id, name, icon, location, position, fields FROM field_groups WHERE active = 1 AND deleted_at IS NULL ORDER BY `order`, name");
        foreach ($fgStmt->fetchAll(\PDO::FETCH_ASSOC) as $fg) {
            $location = json_decode($fg['location'] ?? 'null', true);
            if (!$this->locationMatches($location, $ptSlug)) continue;
            $fgFields = json_decode($fg['fields'] ?? 'null', true);
            if (is_array($fgFields)) {
                foreach ($fgFields as $f) {
                    if (is_array($f)) { $f['prov'] = 'shared'; $f['_group'] = (int)$fg['id']; $fields[] = $f; }
                }
            }
            $matchedGroups[] = ['id' => (int)$fg['id'], 'name' => $fg['name'], 'icon' => $fg['icon'], 'position' => $fg['position']];
        }

        $values = json_decode($post['data'] ?? 'null', true);
        if (!is_array($values)) $values = json_decode($post['component_data'] ?? 'null', true) ?: [];
        $layout = json_decode($post['layout'] ?? 'null', true);

        // Public front-end URL (so the editor's View / Preview links work).
        $core = defined('CORE_PATH') ? CORE_PATH : dirname(__DIR__);
        if (!function_exists('get_post_permalink')) {
            foreach (['/functions/permalinks.php', '/functions/template-helpers.php'] as $f) {
                if (is_file($core . $f)) { require_once $core . $f; }
            }
        }
        if (!function_exists('md_upload_url') && is_file($core . '/functions/http.php')) {
            require_once $core . '/functions/http.php';
        }
        $url = function_exists('get_post_permalink') ? get_post_permalink($post['post_type_slug'], $post['slug']) : '';

        // Hydrate image/file fields into the {id,name,url,w,h} object the editor expects.
        // Stored values may be a bare media id (legacy) or — for featured_image — live only
        // in the posts.featured_image column. Without this the image field renders empty even
        // though the front-end (which reads the column) shows the picture.
        $values = $this->hydrateImageFields($fields, is_array($values) ? $values : [], $post);

        return [
            'post' => [
                'id' => (int)$post['id'],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'status' => $post['status'],
                'order' => (int) ($post['order'] ?? 0),
                'url' => $url,
                'post_type' => ['id' => (int)$post['post_type_id'], 'name' => $post['post_type_name'], 'slug' => $post['post_type_slug'], 'mode' => $post['post_type_mode'] ?? 'fixed'],
            ],
            'schema' => ['fields' => $fields],
            'values' => is_array($values) ? $values : [],
            'layout' => is_array($layout) ? $layout : null,
            'field_groups' => $matchedGroups,
        ];
    }

    /** Extract a media id from a stored image value (bare id or {id:…} object). */
    private function mediaIdFromValue($v): int {
        if (is_array($v)) return (int) ($v['id'] ?? 0);
        if (is_numeric($v)) return (int) $v;
        return 0;
    }

    /**
     * Turn image/file field values into the {id,name,url,w,h} object the editor's
     * image control needs. Handles the legacy featured_image column fallback and
     * batches the media lookup into one query.
     */
    private function hydrateImageFields(array $fields, array $values, array $post): array {
        $imageFields = [];
        foreach ($fields as $f) {
            if (!is_array($f)) continue;
            if (in_array(($f['type'] ?? ''), ['image', 'file'], true)) {
                $fn = (string) ($f['name'] ?? ($f['key'] ?? ''));
                if ($fn !== '') $imageFields[$fn] = true;
            }
        }
        if (empty($imageFields)) return $values;

        // Legacy: featured_image may live only in the column, not in data.
        if (isset($imageFields['featured_image']) && empty($values['featured_image']) && !empty($post['featured_image'])) {
            $values['featured_image'] = (int) $post['featured_image'];
        }

        $needIds = [];
        foreach ($imageFields as $fn => $_) {
            $v = $values[$fn] ?? null;
            $mid = $this->mediaIdFromValue($v);
            if ($mid > 0 && (!is_array($v) || empty($v['url']))) $needIds[$mid] = true;
        }
        if (empty($needIds)) return $values;

        $ids = array_keys($needIds);
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id, file_path, title, filename, width, height FROM media WHERE id IN ($ph)");
        $stmt->execute($ids);
        $byId = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $m) { $byId[(int) $m['id']] = $m; }

        foreach ($imageFields as $fn => $_) {
            $v = $values[$fn] ?? null;
            if (is_array($v) && !empty($v['url'])) continue;
            $mid = $this->mediaIdFromValue($v);
            if ($mid <= 0 || !isset($byId[$mid])) continue;
            $m = $byId[$mid];
            $path = (string) ($m['file_path'] ?? '');
            $url = function_exists('md_upload_url')
                ? md_upload_url($path)
                : (defined('UPLOADS_WEB_PATH') ? rtrim(UPLOADS_WEB_PATH, '/') . '/' . ltrim($path, '/') : $path);
            $values[$fn] = [
                'id'   => $mid,
                'name' => (string) (($m['title'] ?? '') !== '' ? $m['title'] : (($m['filename'] ?? '') !== '' ? $m['filename'] : ('media-' . $mid))),
                'url'  => $url,
                'w'    => isset($m['width']) ? (int) $m['width'] : null,
                'h'    => isset($m['height']) ? (int) $m['height'] : null,
            ];
        }
        return $values;
    }

    /** True if any OR-group's AND-rules match the given post type slug. */
    private function locationMatches($location, string $ptSlug): bool {
        if (!is_array($location) || !count($location)) return false;
        foreach ($location as $orGroup) {
            if (!is_array($orGroup) || !count($orGroup)) continue;
            $all = true;
            foreach ($orGroup as $rule) {
                $param = $rule['param'] ?? '';
                $op = $rule['op'] ?? '==';
                $val = $rule['value'] ?? '';
                if ($param === 'post_type') {
                    $ok = $op === '!=' ? ($val !== $ptSlug) : ($val === $ptSlug || $val === '');
                    if (!$ok) { $all = false; break; }
                }
                // other params (post/template/taxonomy/role) are not constrained here yet
            }
            if ($all) return true;
        }
        return false;
    }

    /** GET /posts/{id}/revisions */
    public function revisions($postId) {
        $stmt = $this->db->prepare("
            SELECT r.id, r.post_id, r.title, r.created_at,
                   COALESCE(u.username, u.email, 'Unknown') AS created_by_name
            FROM post_revisions r LEFT JOIN users u ON r.created_by = u.id
            WHERE r.post_id = ? ORDER BY r.created_at DESC
        ");
        $stmt->execute([$postId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
