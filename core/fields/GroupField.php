<?php

namespace Modular\Fields;

/**
 * Group: nested set of sub-fields. Stored as keyed object. Normalized recursively by core.
 */
class GroupField implements FieldInterface, LayoutFieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_LAYOUT,
            'label' => 'Group',
            'icon' => 'fa-object-group',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'group',
            'label' => $schema['label'] ?? 'Group',
            'icon' => 'fa-object-group',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Group'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'sub_fields' => ['type' => 'array', 'default' => []],
                'layout' => ['type' => 'string', 'default' => 'block'],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function isLayout(): bool
    {
        return true;
    }

    public function getChildSchema(array $schema): array
    {
        return ['fields' => $schema['sub_fields'] ?? []];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || !is_array($value)) {
            return [];
        }
        return $value;
    }

    public function normalize($value, array $schema = []): array
    {
        if ($value === null || !is_array($value)) {
            return ['children' => []];
        }
        return ['children' => $value];
    }

    public function frontend(array $data): array
    {
        return [
            'type' => 'group',
            'children' => $data['children'] ?? [],
        ];
    }
}
