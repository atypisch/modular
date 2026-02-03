<?php

namespace Modular\Fields;

class CheckboxField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_CHOICE,
            'label' => 'Checkbox',
            'icon' => 'fa-check-square',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'checkbox',
            'label' => $schema['label'] ?? 'Checkbox',
            'icon' => 'fa-check-square',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Checkbox'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'choices' => ['type' => 'object', 'default' => []],
                'default_value' => ['type' => 'array', 'default' => []],
                'layout' => ['type' => 'string', 'default' => 'vertical'],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        $choices = $schema['choices'] ?? [];
        $valid = is_array($choices) ? array_keys($choices) : [];
        if ($value === null || $value === '') {
            return $schema['default_value'] ?? [];
        }
        $arr = is_array($value) ? $value : [$value];
        $out = array_values(array_unique(array_map('strval', $arr)));
        return empty($valid) ? $out : array_values(array_intersect($out, $valid));
    }

    public function normalize($value, array $schema = []): array
    {
        if (!is_array($value)) {
            $value = $value === null || $value === '' ? [] : [$value];
        }
        return ['value' => array_values(array_map('strval', $value))];
    }

    public function frontend(array $data): array
    {
        return [
            'value' => isset($data['value']) && is_array($data['value'])
                ? array_values($data['value'])
                : [],
        ];
    }
}
