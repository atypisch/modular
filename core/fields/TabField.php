<?php

namespace Modular\Fields;

/**
 * Tab: admin-only grouping. Never stored; editor UX only.
 */
class TabField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_UTILITY,
            'label' => 'Field Group',
            'icon' => 'fa-folder',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'tab',
            'label' => $schema['label'] ?? 'Field Group',
            'icon' => 'fa-folder',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true],
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
