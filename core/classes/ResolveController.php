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
        return $this->buildResolved($post);
    }

    /** GET /resolved-fieldset?post_type={slug}&slug= optional — fieldset for a not-yet-saved post. */
    public function resolvedFieldsetPreview($ptSlug, $slug = '') {
        $ptSlug = trim((string) $ptSlug);
        if ($ptSlug === '') return ['error' => 'post_type is required', 'code' => 400];
        $stmt = $this->db->prepare("SELECT id, name, slug, mode, `schema` FROM post_types WHERE slug = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$ptSlug]);
        $pt = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$pt) return ['error' => 'Post type not found', 'code' => 404];
        return $this->buildResolved([
            'id' => 0,
            'title' => '',
            'slug' => (string) $slug,
            'status' => 'draft',
            'order' => 0,
            'data' => null,
            'component_data' => null,
            'layout' => null,
            'template' => '',
            'featured_image' => null,
            'post_type_id' => $pt['id'],
            'post_type_name' => $pt['name'],
            'post_type_slug' => $pt['slug'],
            'post_type_mode' => $pt['mode'] ?? 'fixed',
            'pt_schema' => $pt['schema'],
        ]);
    }

    private function buildResolved(array $post): array {
        $schema = is_array($post['pt_schema'] ?? null) ? $post['pt_schema'] : json_decode($post['pt_schema'] ?? 'null', true);
        $fields = is_array($schema) && isset($schema['fields']) ? $schema['fields'] : (is_array($schema) ? $schema : []);
        $fields = array_map(function ($f) {
            if (is_array($f) && !isset($f['prov'])) $f['prov'] = 'type';
            return $f;
        }, $fields);

        $matchedGroups = [];
        $locCtx = [
            'post_type_slug' => (string) ($post['post_type_slug'] ?? ''),
            'post_type_id' => (string) ((int) $post['post_type_id']),
            'id' => (string) ((int) ($post['id'] ?? 0)),
            'slug' => (string) ($post['slug'] ?? ''),
            'template' => trim((string) ($post['template'] ?? '')),
        ];
        $fgStmt = $this->db->query("SELECT id, name, icon, location, position, fields FROM field_groups WHERE active = 1 AND deleted_at IS NULL ORDER BY `order`, name");
        foreach ($fgStmt->fetchAll(\PDO::FETCH_ASSOC) as $fg) {
            $location = is_string($fg['location'] ?? null) ? json_decode($fg['location'], true) : ($fg['location'] ?? null);
            if (!$this->locationMatches($location, $locCtx)) continue;
            $fgFields = is_string($fg['fields'] ?? null) ? json_decode($fg['fields'], true) : ($fg['fields'] ?? null);
            foreach ($this->normalizeFieldList($fgFields) as $f) {
                $f['prov'] = 'shared';
                $f['_group'] = (int) $fg['id'];
                $fields[] = $f;
            }
            $matchedGroups[] = ['id' => (int)$fg['id'], 'name' => $fg['name'], 'icon' => $fg['icon'], 'position' => $fg['position']];
        }

        $values = is_array($post['data'] ?? null) ? $post['data'] : json_decode($post['data'] ?? 'null', true);
        if (!is_array($values)) $values = json_decode($post['component_data'] ?? 'null', true) ?: [];
        $layout = is_array($post['layout'] ?? null) ? $post['layout'] : json_decode($post['layout'] ?? 'null', true);

        $core = defined('CORE_PATH') ? CORE_PATH : dirname(__DIR__);
        if (!function_exists('get_post_permalink')) {
            foreach (['/functions/permalinks.php', '/functions/template-helpers.php'] as $f) {
                if (is_file($core . $f)) { require_once $core . $f; }
            }
        }
        if (!function_exists('md_upload_url') && is_file($core . '/functions/http.php')) {
            require_once $core . '/functions/http.php';
        }
        $url = ((int) ($post['id'] ?? 0) > 0 && function_exists('get_post_permalink'))
            ? get_post_permalink($post['post_type_slug'], $post['slug'])
            : '';

        $values = $this->hydrateImageFields($fields, is_array($values) ? $values : [], $post);

        return [
            'post' => [
                'id' => (int) ($post['id'] ?? 0),
                'title' => $post['title'] ?? '',
                'slug' => $post['slug'] ?? '',
                'status' => $post['status'] ?? 'draft',
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

    /** Flatten a field-group `fields` payload to a list of field defs. */
    private function normalizeFieldList($raw): array {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (!is_array($raw)) {
            return [];
        }
        if (isset($raw['fields']) && is_array($raw['fields']) && !isset($raw['type']) && !isset($raw['name'])) {
            $raw = $raw['fields'];
        }
        $out = [];
        foreach ($raw as $f) {
            if (is_array($f) && (isset($f['type']) || isset($f['name']) || isset($f['key']))) {
                $out[] = $f;
            }
        }
        return $out;
    }

    /** True if any OR-group's AND-rules match this post (type, id, slug, template). */
    private function locationMatches($location, array $ctx): bool {
        if (!is_array($location) || !count($location)) return true;
        $ptSlug = (string) ($ctx['post_type_slug'] ?? '');
        $ptId = (string) ($ctx['post_type_id'] ?? '');
        $postId = (string) ($ctx['id'] ?? '');
        $slug = (string) ($ctx['slug'] ?? '');
        $template = (string) ($ctx['template'] ?? '');
        foreach ($location as $orGroup) {
            if (!is_array($orGroup) || !count($orGroup)) continue;
            $all = true;
            foreach ($orGroup as $rule) {
                $param = (string) ($rule['param'] ?? '');
                $op = (string) ($rule['op'] ?? '==');
                $val = (string) ($rule['value'] ?? '');
                if ($param === 'post_type') {
                    $ptEqual = ($val === $ptSlug || ($val !== '' && $val === $ptId));
                    $ok = $op === '!=' ? ($val !== '' && !$ptEqual) : ($ptEqual || $val === '');
                } elseif ($param === 'post') {
                    $ok = $op === '!=' ? ($val !== $postId) : ($val === $postId);
                } elseif ($param === 'post_slug') {
                    $ok = $op === '!=' ? ($val !== $slug) : ($val === $slug);
                } elseif ($param === 'post_template') {
                    $ok = $op === '!=' ? ($val !== $template) : ($val === $template);
                } else {
                    continue;
                }
                if (!$ok) { $all = false; break; }
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
