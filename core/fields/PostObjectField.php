<?php

namespace Modular\Fields;

/**
 * Post Object: single post reference. Filter by post type, taxonomy, status; async search.
 */
class PostObjectField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_RELATIONAL,
            'label' => 'Post Object',
            'icon' => 'fa-file',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'post_object',
            'label' => $schema['label'] ?? 'Post',
            'icon' => 'fa-file',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Post'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'post_type' => ['type' => 'array', 'default' => []],
                'taxonomy' => ['type' => 'array', 'default' => []],
                'allow_null' => ['type' => 'boolean', 'default' => false],
                'multiple' => ['type' => 'boolean', 'default' => false],
                'return_format' => ['type' => 'string', 'default' => 'id'],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return $schema['multiple'] ? [] : null;
        }
        if (is_array($value)) {
            return array_values(array_map('intval', array_filter($value)));
        }
        return (int) $value;
    }

    public function normalize($value, array $schema = []): array
    {
        if ($value === null || $value === '') {
            return ['id' => null];
        }
        if (is_array($value)) {
            return ['ids' => array_values(array_map('intval', $value))];
        }
        return ['id' => (int) $value];
    }

    public function frontend(array $data): array
    {
        if (isset($data['ids'])) {
            return ['ids' => array_values(array_map('intval', $data['ids']))];
        }
        return [
            'id' => isset($data['id']) ? (int) $data['id'] : null,
        ];
    }
}
