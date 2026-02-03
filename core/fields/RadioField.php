<?php

namespace Modular\Fields;

class RadioField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_CHOICE,
            'label' => 'Radio Button',
            'icon' => 'fa-dot-circle',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'radio',
            'label' => $schema['label'] ?? 'Radio',
            'icon' => 'fa-dot-circle',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Radio'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'choices' => ['type' => 'object', 'default' => []],
                'default_value' => ['type' => 'string', 'required' => false],
                'allow_null' => ['type' => 'boolean', 'default' => false],
                'layout' => ['type' => 'string', 'default' => 'vertical'],
                'other_choice' => ['type' => 'boolean', 'default' => false],
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
        if (empty($valid) || in_array($v, $valid, true) || !empty($schema['other_choice'])) {
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
