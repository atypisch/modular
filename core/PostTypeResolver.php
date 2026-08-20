<?php
/**
 * Post Type Resolver
 *
 * Loads and validates post type schemas, merges schema/layout, normalizes
 * field values via FieldRegistry, and resolves relations. Outputs predictable
 * blocks for public rendering.
 */

namespace Modular;

use Modular\Fields\FieldInterface;
use Modular\Fields\FieldRegistry;
use Modular\Fields\LayoutFieldInterface;

class PostTypeResolver
{
    /** @var \PDO */
    private $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
        FieldRegistry::bootDefaultFields();
    }

    public function getPostType($idOrSlug): ?array
    {
        $isId = is_numeric($idOrSlug);
        $col = $isId ? 'id' : 'slug';
        $stmt = $this->db->prepare("
            SELECT * FROM post_types
            WHERE {$col} = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$idOrSlug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->decodePostTypeRow($row);
        return $row;
    }

    public function getPost(int $postId, $postTypeIdOrSlug = null): ?array
    {
        $sql = "
            SELECT p.*,
                   pt.slug as post_type_slug, pt.name as post_type_name,
                   pt.mode as post_type_mode, pt.`schema` as post_type_schema,
                   COALESCE(u.username, u.email, 'Unknown') as author_name
            FROM posts p
            INNER JOIN post_types pt ON p.post_type_id = pt.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.id = ? AND p.deleted_at IS NULL
        ";
        $params = [$postId];
        if ($postTypeIdOrSlug !== null) {
            $isId = is_numeric($postTypeIdOrSlug);
            $sql .= $isId ? " AND pt.id = ?" : " AND pt.slug = ?";
            $params[] = $postTypeIdOrSlug;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->decodePostRow($row);
        return $row;
    }

    /**
     * Resolve a post for frontend/API.
     * Returns: post, blocks (array of { type, name, data } or { type, name, children }), content (keyed for compat).
     */
    public function resolvePost(int $postId, array $options = []): array
    {
        $resolveRelations = $options['resolve_relations'] ?? true;
        $includeAuthor = $options['include_author'] ?? false;

        $post = $this->getPost($postId);
        if (!$post) {
            return ['error' => 'Post not found', 'code' => 404];
        }

        // Admin preview: overlay the editor's unsaved values onto the stored row
        // before anything is resolved, so blocks, content and the base output all
        // reflect the draft rather than what is in the database.
        if (!empty($options['overrides']) && is_array($options['overrides'])) {
            foreach (['title', 'slug', 'excerpt', 'status', 'template', 'layout'] as $k) {
                if (array_key_exists($k, $options['overrides'])) {
                    $post[$k] = $options['overrides'][$k];
                }
            }
            if (isset($options['overrides']['data']) && is_array($options['overrides']['data'])) {
                $post['data'] = $options['overrides']['data'];
            }
        }

        $schema = $this->getEffectiveSchema($post);
        if (empty($schema['fields'])) {
            $base = $this->basePostOutput($post, $includeAuthor);
            return $base + ['blocks' => [], 'content' => (object) []];
        }

        $data = $this->getEffectiveData($post);
        $layoutOrder = $this->getLayoutOrder($post, $schema);
        $blocks = [];
        $content = [];
        $relationIds = [];

        foreach ($layoutOrder as $fieldDef) {
            $name = $fieldDef['name'] ?? $fieldDef['slug'] ?? null;
            $type = $fieldDef['type'] ?? 'text';
            if ($name === null) {
                continue;
            }
            if (FieldRegistry::isUtilityType($type)) {
                continue;
            }
            $raw = $data[$name] ?? null;
            $block = $this->normalizeFieldToBlock($fieldDef, $raw, $data);
            if ($block === null) {
                continue;
            }
            $block['name'] = $name;
            $blocks[] = $block;
            $content[$name] = $block['data'] ?? $block['children'] ?? [];

            $this->collectRelationIds($type, $block, $name, $relationIds);
        }

        if ($resolveRelations && !empty($relationIds)) {
            $resolved = $this->resolveRelationIds($relationIds, $options);
            $this->injectResolvedRelations($blocks, $resolved);
            foreach ($resolved as $fieldName => $posts) {
                if (isset($content[$fieldName])) {
                    $content[$fieldName] = array_merge(is_array($content[$fieldName]) ? $content[$fieldName] : [], ['posts' => $posts]);
                }
            }
        }

        return $this->basePostOutput($post, $includeAuthor) + [
            'blocks' => $blocks,
            'content' => (object) $content,
        ];
    }

    /**
     * Turn one field definition + raw value into a block. Recurses for layout fields.
     */
    private function normalizeFieldToBlock(array $fieldDef, $raw, array $dataContext): ?array
    {
        $type = $fieldDef['type'] ?? 'text';
        $field = FieldRegistry::get($type);
        if (!$field) {
            return ['type' => $type, 'data' => ['raw' => $raw]];
        }

        if ($field instanceof LayoutFieldInterface) {
            return $this->normalizeLayoutBlock($field, $fieldDef, $raw);
        }

        if ($type === 'latest_posts') {
            $resolved = $this->resolveLatestPostsBlock($fieldDef, $raw, $dataContext);
            $frontend = $field->frontend($resolved);
            return ['type' => $type, 'data' => $frontend];
        }

        $normalized = $field->normalize($raw, $fieldDef);
        $frontend = $field->frontend($normalized);
        return ['type' => $type, 'data' => $frontend];
    }

    /**
     * Fetch latest posts. Allowed post types from fieldDef (schema); selected type(s) and limit from raw (stored per post).
     */
    private function resolveLatestPostsBlock(array $fieldDef, $raw, array $dataContext): array
    {
        $allowed = $fieldDef['post_type'] ?? [];
        if (!is_array($allowed)) {
            $allowed = $allowed ? [$allowed] : [];
        }
        $stored = is_array($raw) ? $raw : [];
        $selected = $stored['post_type'] ?? [];
        if (!is_array($selected)) {
            $selected = $selected !== '' && $selected !== null ? [$selected] : [];
        }
        $selected = array_values(array_intersect($selected, $allowed));
        if (empty($selected) && count($allowed) === 1) {
            $selected = $allowed;
        }
        $limit = isset($stored['limit']) ? (int) $stored['limit'] : 5;
        $limit = max(1, min(100, $limit));
        $returnFormat = $fieldDef['return_format'] ?? 'array';

        $postTypes = $selected;
        $posts = [];
        $ids = [];
        if (empty($postTypes)) {
            $stmt = $this->db->prepare("
                SELECT p.id, p.title, p.slug, p.excerpt, p.published_at, p.created_at, pt.slug as post_type_slug
                FROM posts p
                INNER JOIN post_types pt ON p.post_type_id = pt.id
                WHERE p.deleted_at IS NULL
                AND (p.status = 'published' OR (p.status = 'scheduled' AND p.published_at IS NOT NULL AND p.published_at <= NOW()))
                ORDER BY p.published_at DESC, p.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
        } else {
            $placeholders = implode(',', array_fill(0, count($postTypes), '?'));
            $stmt = $this->db->prepare("
                SELECT p.id, p.title, p.slug, p.excerpt, p.published_at, p.created_at, pt.slug as post_type_slug
                FROM posts p
                INNER JOIN post_types pt ON p.post_type_id = pt.id
                WHERE pt.slug IN ({$placeholders}) AND p.deleted_at IS NULL
                AND (p.status = 'published' OR (p.status = 'scheduled' AND p.published_at IS NOT NULL AND p.published_at <= NOW()))
                ORDER BY p.published_at DESC, p.created_at DESC
                LIMIT ?
            ");
            $stmt->execute(array_merge($postTypes, [$limit]));
        }
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $posts[] = $row;
            $ids[] = (int) $row['id'];
        }

        if ($returnFormat === 'id') {
            return ['ids' => $ids, 'posts' => [], 'json' => ''];
        }
        if ($returnFormat === 'json') {
            return ['posts' => $posts, 'ids' => $ids, 'json' => json_encode($posts)];
        }
        return ['posts' => $posts, 'ids' => $ids, 'json' => ''];
    }

    private function normalizeLayoutBlock(LayoutFieldInterface $field, array $fieldDef, $raw): array
    {
        $type = $fieldDef['type'] ?? 'group';
        $childSchema = $field->getChildSchema($fieldDef);
        $normalized = $field->normalize($raw, $fieldDef);

        if ($type === 'group') {
            $children = [];
            $subFields = $childSchema['fields'] ?? [];
            $childData = $normalized['children'] ?? [];
            foreach ($subFields as $sub) {
                $subName = $sub['name'] ?? $sub['slug'] ?? null;
                if ($subName === null) {
                    continue;
                }
                if (FieldRegistry::isUtilityType($sub['type'] ?? '')) {
                    continue;
                }
                $subRaw = $childData[$subName] ?? null;
                $subBlock = $this->normalizeFieldToBlock($sub, $subRaw, $childData);
                if ($subBlock !== null) {
                    $subBlock['name'] = $subName;
                    $children[] = $subBlock;
                }
            }
            return ['type' => 'group', 'children' => $children];
        }

        if ($type === 'repeater') {
            $rows = $normalized['rows'] ?? [];
            $subFields = $childSchema['fields'] ?? [];
            $rowBlocks = [];
            foreach ($rows as $row) {
                $rowChildren = [];
                foreach ($subFields as $sub) {
                    $subName = $sub['name'] ?? $sub['slug'] ?? null;
                    if ($subName === null || FieldRegistry::isUtilityType($sub['type'] ?? '')) {
                        continue;
                    }
                    $subRaw = is_array($row) ? ($row[$subName] ?? null) : null;
                    $subBlock = $this->normalizeFieldToBlock($sub, $subRaw, is_array($row) ? $row : []);
                    if ($subBlock !== null) {
                        $subBlock['name'] = $subName;
                        $rowChildren[] = $subBlock;
                    }
                }
                $rowBlocks[] = ['type' => 'repeater_row', 'children' => $rowChildren];
            }
            return ['type' => 'repeater', 'children' => $rowBlocks];
        }

        if ($type === 'flexible_content') {
            $layouts = $normalized['layouts'] ?? [];
            $layoutDefs = $childSchema['layouts'] ?? [];
            $byName = [];
            foreach ($layoutDefs as $ld) {
                $byName[$ld['name'] ?? $ld['key'] ?? ''] = $ld;
            }
            $layoutBlocks = [];
            foreach ($layouts as $item) {
                $layoutName = $item['acf_fc_layout'] ?? $item['layout'] ?? $item['name'] ?? null;
                if ($layoutName === null) {
                    continue;
                }
                $layoutDef = $byName[$layoutName] ?? null;
                $subFields = $layoutDef['sub_fields'] ?? [];
                $rowChildren = [];
                foreach ($subFields as $sub) {
                    $subName = $sub['name'] ?? $sub['slug'] ?? null;
                    if ($subName === null || FieldRegistry::isUtilityType($sub['type'] ?? '')) {
                        continue;
                    }
                    $subRaw = $item[$subName] ?? null;
                    $subBlock = $this->normalizeFieldToBlock($sub, $subRaw, $item);
                    if ($subBlock !== null) {
                        $subBlock['name'] = $subName;
                        $rowChildren[] = $subBlock;
                    }
                }
                $layoutBlocks[] = ['type' => 'flexible_layout', 'layout' => $layoutName, 'children' => $rowChildren];
            }
            return ['type' => 'flexible_content', 'children' => $layoutBlocks];
        }

        return ['type' => $type, 'data' => $field->frontend($normalized)];
    }

    private function collectRelationIds(string $type, array $block, string $fieldName, array &$relationIds): void
    {
        $data = $block['data'] ?? [];
        if ($type === 'relationship' && !empty($data['ids'])) {
            $relationIds[$fieldName] = $data['ids'];
        } elseif ($type === 'post_object') {
            if (isset($data['id'])) {
                $relationIds[$fieldName] = [$data['id']];
            } elseif (!empty($data['ids'])) {
                $relationIds[$fieldName] = $data['ids'];
            }
        } elseif ($type === 'relation' && !empty($data['ids'])) {
            $relationIds[$fieldName] = $data['ids'];
        }
    }

    private function injectResolvedRelations(array &$blocks, array $resolved): void
    {
        foreach ($blocks as &$block) {
            $name = $block['name'] ?? null;
            if ($name !== null && isset($resolved[$name]) && isset($block['data'])) {
                $block['data']['posts'] = $resolved[$name];
            }
            if (!empty($block['children'])) {
                $this->injectResolvedRelations($block['children'], $resolved);
            }
        }
    }

    public function validateSchema(array $schema): array
    {
        $errors = [];
        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            $errors[] = 'Schema must have a "fields" array';
            return ['valid' => false, 'errors' => $errors];
        }
        foreach ($schema['fields'] as $i => $field) {
            if (empty($field['type'])) {
                $errors[] = "Field at index {$i} missing 'type'";
            }
            $type = $field['type'] ?? '';
            if ($type && !FieldRegistry::isUtilityType($type)) {
                if (!FieldRegistry::get($type)) {
                    $errors[] = "Unknown field type: {$type}";
                }
            }
            if (!FieldRegistry::isUtilityType($type) && empty($field['name']) && empty($field['slug'])) {
                $errors[] = "Field at index {$i} missing 'name' or 'slug'";
            }
        }
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    private function decodePostTypeRow(array &$row): void
    {
        if (isset($row['schema']) && is_string($row['schema'])) {
            $row['schema'] = json_decode($row['schema'], true);
        }
        if (isset($row['settings']) && is_string($row['settings'])) {
            $row['settings'] = json_decode($row['settings'], true);
        }
        if (isset($row['supports']) && is_string($row['supports'])) {
            $row['supports'] = json_decode($row['supports'], true);
        }
        $row['mode'] = $row['mode'] ?? 'fixed';
    }

    private function decodePostRow(array &$row): void
    {
        foreach (['data', 'layout', 'component_data', 'meta_data'] as $key) {
            if (isset($row[$key]) && is_string($row[$key])) {
                $row[$key] = json_decode($row[$key], true);
            }
        }
        if (isset($row['post_type_schema']) && is_string($row['post_type_schema'])) {
            $row['post_type_schema'] = json_decode($row['post_type_schema'], true);
        }
    }

    private function getEffectiveSchema(array $post): array
    {
        $schema = $post['post_type_schema'] ?? null;
        if (is_array($schema) && !empty($schema['fields'])) {
            return $schema;
        }
        $postTypeId = $post['post_type_id'];
        $stmt = $this->db->prepare("
            SELECT slug, name, type, `schema` FROM component_definitions
            WHERE post_type_id = ? ORDER BY `order`
        ");
        $stmt->execute([$postTypeId]);
        $defs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $fields = [];
        foreach ($defs as $def) {
            $s = is_string($def['schema']) ? json_decode($def['schema'], true) : ($def['schema'] ?? []);
            foreach ($s['fields'] ?? [] as $f) {
                $f['name'] = $f['name'] ?? $f['slug'] ?? $def['slug'];
                $fields[] = $f;
            }
        }
        return ['fields' => $fields];
    }

    private function getEffectiveData(array $post): array
    {
        $data = $post['data'] ?? null;
        if (is_array($data) && !empty($data)) {
            return $data;
        }
        $legacy = $post['component_data'] ?? [];
        if (is_array($legacy)) {
            if (isset($legacy['data']) && is_array($legacy['data'])) {
                return $legacy['data'];
            }
            return $legacy;
        }
        return $post['meta_data'] ?? [];
    }

    private function getLayoutOrder(array $post, array $schema): array
    {
        $mode = $post['post_type_mode'] ?? 'fixed';
        $layout = $post['layout'] ?? null;
        $fields = $schema['fields'] ?? [];
        $byName = [];
        foreach ($fields as $f) {
            $name = $f['name'] ?? $f['slug'] ?? null;
            if ($name !== null) {
                $byName[$name] = $f;
            }
        }
        if ($mode === 'modular' && is_array($layout) && !empty($layout)) {
            $ordered = [];
            foreach ($layout as $block) {
                $name = is_array($block) ? ($block['name'] ?? $block['slug'] ?? null) : $block;
                if ($name !== null && isset($byName[$name])) {
                    $ordered[] = $byName[$name];
                }
            }
            return $ordered ?: $fields;
        }
        return $fields;
    }

    private function basePostOutput(array $post, bool $includeAuthor): array
    {
        $out = [
            'id' => (int) $post['id'],
            'slug' => $post['slug'],
            'title' => $post['title'],
            'excerpt' => $post['excerpt'] ?? null,
            'status' => $post['status'],
            'published_at' => $post['published_at'] ?? null,
            'created_at' => $post['created_at'] ?? null,
            'updated_at' => $post['updated_at'] ?? null,
            'post_type' => $post['post_type_slug'] ?? null,
            'template' => isset($post['template']) ? trim((string) $post['template']) : '',
            'visibility' => $post['visibility'] ?? 'public',
            'password' => $post['password'] ?? '',
        ];
        if ($includeAuthor) {
            $out['author_id'] = (int) $post['author_id'];
            $out['author_name'] = $post['author_name'] ?? null;
        }
        return $out;
    }

    private function resolveRelationIds(array $relationIds, array $options): array
    {
        $allIds = [];
        foreach ($relationIds as $ids) {
            foreach ($ids as $id) {
                $allIds[(int) $id] = true;
            }
        }
        $allIds = array_keys($allIds);
        if (empty($allIds)) {
            return array_fill_keys(array_keys($relationIds), []);
        }
        $placeholders = implode(',', array_fill(0, count($allIds), '?'));
        $stmt = $this->db->prepare("
            SELECT p.id, p.slug, p.title, p.excerpt, p.status, p.published_at,
                   p.post_type_id, pt.slug as post_type_slug
            FROM posts p
            INNER JOIN post_types pt ON p.post_type_id = pt.id
            WHERE p.id IN ({$placeholders}) AND p.deleted_at IS NULL
        ");
        $stmt->execute($allIds);
        $postsById = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $postsById[(int) $row['id']] = [
                'id' => (int) $row['id'],
                'slug' => $row['slug'],
                'title' => $row['title'],
                'excerpt' => $row['excerpt'] ?? null,
                'status' => $row['status'],
                'published_at' => $row['published_at'] ?? null,
                'post_type' => $row['post_type_slug'],
            ];
        }
        $result = [];
        foreach ($relationIds as $fieldName => $ids) {
            $result[$fieldName] = [];
            foreach ($ids as $id) {
                if (isset($postsById[(int) $id])) {
                    $result[$fieldName][] = $postsById[(int) $id];
                }
            }
        }
        return $result;
    }
}
