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
        if (!function_exists('get_post_permalink')) {
            $core = defined('CORE_PATH') ? CORE_PATH : dirname(__DIR__);
            foreach (['/functions/permalinks.php', '/functions/template-helpers.php'] as $f) {
                if (is_file($core . $f)) { require_once $core . $f; }
            }
        }
        $url = function_exists('get_post_permalink') ? get_post_permalink($post['post_type_slug'], $post['slug']) : '';

        return [
            'post' => [
                'id' => (int)$post['id'],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'status' => $post['status'],
                'url' => $url,
                'post_type' => ['id' => (int)$post['post_type_id'], 'name' => $post['post_type_name'], 'slug' => $post['post_type_slug'], 'mode' => $post['post_type_mode'] ?? 'fixed'],
            ],
            'schema' => ['fields' => $fields],
            'values' => is_array($values) ? $values : [],
            'layout' => is_array($layout) ? $layout : null,
            'field_groups' => $matchedGroups,
        ];
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
