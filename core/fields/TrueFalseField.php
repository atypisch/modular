<?php

namespace Modular\Fields;

/**
 * True / False (boolean toggle).
 */
class TrueFalseField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_CHOICE,
            'label' => 'True / False',
            'icon' => 'fa-toggle-on',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'true_false',
            'label' => $schema['label'] ?? 'True / False',
            'icon' => 'fa-toggle-on',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'True / False'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'message' => ['type' => 'string', 'default' => ''],
                'default_value' => ['type' => 'boolean', 'default' => false],
                'ui' => ['type' => 'boolean', 'default' => true],
                'ui_on_text' => ['type' => 'string', 'default' => 'Yes'],
                'ui_off_text' => ['type' => 'string', 'default' => 'No'],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return (bool) ($schema['default_value'] ?? false);
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) ($schema['default_value'] ?? false);
    }

    public function normalize($value, array $schema = []): array
    {
        return [
            'value' => (bool) ($value ?? $schema['default_value'] ?? false),
        ];
    }

    public function frontend(array $data): array
    {
        return [
            'value' => !empty($data['value']),
        ];
    }
}
