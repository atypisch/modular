<?php

namespace Modular\Fields;

/**
 * Repeater: repeated set of sub-fields. Stored as array of rows. Normalized recursively by core.
 */
class RepeaterField implements FieldInterface, LayoutFieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_LAYOUT,
            'label' => 'Repeater',
            'icon' => 'fa-list-ol',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'repeater',
            'label' => $schema['label'] ?? 'Repeater',
            'icon' => 'fa-list-ol',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Repeater'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'sub_fields' => ['type' => 'array', 'default' => []],
                'min' => ['type' => 'integer', 'required' => false],
                'max' => ['type' => 'integer', 'required' => false],
                'layout' => ['type' => 'string', 'default' => 'table'],
                'button_label' => ['type' => 'string', 'default' => 'Add Row'],
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
        return array_values($value);
    }

    public function normalize($value, array $schema = []): array
    {
        if ($value === null || !is_array($value)) {
            return ['rows' => []];
        }
        return ['rows' => array_values($value)];
    }

    public function frontend(array $data): array
    {
        return [
            'type' => 'repeater',
            'rows' => $data['rows'] ?? [],
        ];
    }
}
