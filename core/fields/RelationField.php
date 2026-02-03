<?php

namespace Modular\Fields;

/**
 * Relationship: multiple post references. Filter by post type, taxonomy, status; async search, pagination.
 */
class RelationField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_RELATIONAL,
            'label' => 'Relationship',
            'icon' => 'fa-bars',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'relationship',
            'label' => $schema['label'] ?? 'Relationship',
            'icon' => 'fa-bars',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Relationship'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'post_type' => ['type' => 'array', 'default' => []],
                'taxonomy' => ['type' => 'array', 'default' => []],
                'filters' => ['type' => 'array', 'default' => ['search', 'post_type', 'taxonomy']],
                'return_format' => ['type' => 'string', 'default' => 'id'],
                'min' => ['type' => 'integer', 'required' => false],
                'max' => ['type' => 'integer', 'required' => false],
                'elements' => ['type' => 'array', 'default' => []],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return array_values(array_map('intval', array_filter($value)));
        }
        $str = (string) $value;
        if (strpos($str, ',') !== false) {
            $ids = array_map('intval', array_filter(array_map('trim', explode(',', $str))));
            return array_values($ids);
        }
        return [(int) $value];
    }

    public function normalize($value, array $schema = []): array
    {
        if ($value === null || $value === '') {
            return ['ids' => []];
        }
        if (is_array($value)) {
            return ['ids' => array_values(array_map('intval', $value))];
        }
        return ['ids' => [(int) $value]];
    }

    public function frontend(array $data): array
    {
        return [
            'ids' => isset($data['ids']) && is_array($data['ids'])
                ? array_values(array_map('intval', $data['ids']))
                : [],
        ];
    }
}
