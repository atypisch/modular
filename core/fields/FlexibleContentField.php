<?php

namespace Modular\Fields;

/**
 * Flexible Content: multiple layouts, each with its own sub_fields. Stored as array of { layout, ...fields }.
 * Normalized recursively by core; always expanded, ordered, predictable.
 */
class FlexibleContentField implements FieldInterface, LayoutFieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_LAYOUT,
            'label' => 'Flexible Content',
            'icon' => 'fa-th-large',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'flexible_content',
            'label' => $schema['label'] ?? 'Flexible Content',
            'icon' => 'fa-th-large',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Flexible Content'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'layouts' => ['type' => 'array', 'default' => []],
                'min' => ['type' => 'integer', 'required' => false],
                'max' => ['type' => 'integer', 'required' => false],
                'button_label' => ['type' => 'string', 'default' => 'Add Layout'],
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
        return ['layouts' => $schema['layouts'] ?? []];
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
            return ['layouts' => []];
        }
        return ['layouts' => array_values($value)];
    }

    public function frontend(array $data): array
    {
        return [
            'type' => 'flexible_content',
            'layouts' => $data['layouts'] ?? [],
        ];
    }
}
