<?php

namespace Modular\Fields;

class RangeField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_BASIC,
            'label' => 'Range',
            'icon' => 'fa-sliders-h',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'range',
            'label' => $schema['label'] ?? 'Range',
            'icon' => 'fa-sliders-h',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Range'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'default_value' => ['type' => 'number', 'required' => false],
                'min' => ['type' => 'number', 'required' => false, 'default' => 0],
                'max' => ['type' => 'number', 'required' => false, 'default' => 100],
                'step' => ['type' => 'number', 'required' => false],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return isset($schema['default_value']) ? (float) $schema['default_value'] : null;
        }
        $num = is_numeric($value) ? (float) $value : null;
        if ($num === null) {
            return $schema['default_value'] ?? null;
        }
        $min = isset($schema['min']) ? (float) $schema['min'] : 0;
        $max = isset($schema['max']) ? (float) $schema['max'] : 100;
        if ($num < $min) {
            $num = $min;
        }
        if ($num > $max) {
            $num = $max;
        }
        return $num;
    }

    public function normalize($value, array $schema = []): array
    {
        return [
            'value' => $value === null ? ($schema['default_value'] ?? null) : (float) $value,
        ];
    }

    public function frontend(array $data): array
    {
        return [
            'value' => isset($data['value']) ? (float) $data['value'] : null,
        ];
    }
}
