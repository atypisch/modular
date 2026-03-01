<?php

namespace Modular\Fields;

/**
 * Latest posts: fetches recent posts by post type(s) and limit.
 * - Field Builder: define which post types are allowed (checkboxes) and return_format (array|id|json).
 * - Post edit: choose which post type(s) to use (from allowed) and the amount (limit). Stored per post.
 */
class LatestPostsField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_EVENT,
            'label' => 'Latest posts',
            'icon' => 'fa-list-alt',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'latest_posts',
            'label' => $schema['label'] ?? 'Latest posts',
            'icon' => 'fa-list-alt',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Latest posts'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'post_type' => ['type' => 'array', 'default' => []],
                'return_format' => ['type' => 'string', 'default' => 'array'],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if (!is_array($value)) {
            return ['post_type' => [], 'limit' => 5];
        }
        $allowed = $schema['post_type'] ?? [];
        if (!is_array($allowed)) {
            $allowed = $allowed ? [$allowed] : [];
        }
        $selected = $value['post_type'] ?? [];
        if (!is_array($selected)) {
            $selected = $selected !== '' && $selected !== null ? [$selected] : [];
        }
        $selected = array_values(array_intersect($selected, $allowed));
        if (empty($selected) && count($allowed) === 1) {
            $selected = $allowed;
        }
        $limit = isset($value['limit']) ? (int) $value['limit'] : 5;
        $limit = max(1, min(100, $limit));
        return ['post_type' => $selected, 'limit' => $limit];
    }

    public function normalize($value, array $schema = []): array
    {
        if (!is_array($value)) {
            return ['post_type' => [], 'limit' => 5];
        }
        return [
            'post_type' => $value['post_type'] ?? [],
            'limit' => isset($value['limit']) ? (int) $value['limit'] : 5,
        ];
    }

    public function frontend(array $data): array
    {
        return $data;
    }
}
