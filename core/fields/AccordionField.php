<?php

namespace Modular\Fields;

/**
 * Accordion: admin-only layout. Never stored; editor UX only.
 */
class AccordionField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_UTILITY,
            'label' => 'Accordion',
            'icon' => 'fa-chevron-down',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'accordion',
            'label' => $schema['label'] ?? 'Accordion',
            'icon' => 'fa-chevron-down',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true],
                'open' => ['type' => 'boolean', 'default' => false],
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
