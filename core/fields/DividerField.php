<?php

namespace Modular\Fields;

/**
 * Divider: admin-only visual separator. Never stored.
 */
class DividerField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_UTILITY,
            'label' => 'Divider',
            'icon' => 'fa-minus',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'divider',
            'label' => $schema['label'] ?? 'Divider',
            'icon' => 'fa-minus',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => false],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        return null;
    }

    public function normalize($value, array $schema = []): array
    {
        return [];
    }

    public function frontend(array $data): array
    {
        return [];
    }
}
