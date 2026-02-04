<?php

namespace Modular\Fields;

/**
 * Hidden field: stores a value with no visible UI in the form.
 * Renders as type="hidden" in admin; frontend returns the value in data.
 */
class HiddenField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_BASIC,
            'label' => 'Hidden',
            'icon' => 'fa-eye-slash',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'hidden',
            'label' => $schema['label'] ?? 'Hidden',
            'icon' => 'fa-eye-slash',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => false, 'default' => 'Hidden'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'default_value' => ['type' => 'string', 'required' => false],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return $schema['default_value'] ?? null;
        }
        return trim((string) $value);
    }

    public function normalize($value, array $schema = []): array
    {
        return [
            'value' => $value === null ? ($schema['default_value'] ?? null) : (string) $value,
        ];
    }

    public function frontend(array $data): array
    {
        return [
            'value' => isset($data['value']) ? (string) $data['value'] : null,
        ];
    }
}
