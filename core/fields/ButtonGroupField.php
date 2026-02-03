<?php

namespace Modular\Fields;

/**
 * Button Group: single choice displayed as toggle buttons.
 */
class ButtonGroupField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_CHOICE,
            'label' => 'Button Group',
            'icon' => 'fa-th-large',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'button_group',
            'label' => $schema['label'] ?? 'Button Group',
            'icon' => 'fa-th-large',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Button Group'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'choices' => ['type' => 'object', 'default' => []],
                'default_value' => ['type' => 'string', 'required' => false],
                'allow_null' => ['type' => 'boolean', 'default' => false],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        $choices = $schema['choices'] ?? [];
        $valid = is_array($choices) ? array_keys($choices) : [];
        if ($value === null || $value === '') {
            return $schema['default_value'] ?? ($schema['allow_null'] ? null : '');
        }
        $v = (string) $value;
        if (empty($valid) || in_array($v, $valid, true)) {
            return $v;
        }
        return $schema['default_value'] ?? ($schema['allow_null'] ? null : '');
    }

    public function normalize($value, array $schema = []): array
    {
        return [
            'value' => $value === null || $value === ''
                ? ($schema['default_value'] ?? null)
                : (string) $value,
        ];
    }

    public function frontend(array $data): array
    {
        return [
            'value' => isset($data['value']) ? (string) $data['value'] : null,
        ];
    }
}
