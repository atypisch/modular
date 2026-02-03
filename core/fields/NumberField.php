<?php

namespace Modular\Fields;

class NumberField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_BASIC,
            'label' => 'Number',
            'icon' => 'fa-hashtag',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'number',
            'label' => $schema['label'] ?? 'Number',
            'icon' => 'fa-hashtag',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Number'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'default_value' => ['type' => 'number', 'required' => false],
                'min' => ['type' => 'number', 'required' => false],
                'max' => ['type' => 'number', 'required' => false],
                'step' => ['type' => 'number', 'required' => false],
                'placeholder' => ['type' => 'string', 'required' => false],
                'prepend' => ['type' => 'string', 'required' => false],
                'append' => ['type' => 'string', 'required' => false],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return isset($schema['default_value']) ? (float) $schema['default_value'] : null;
        }
        $step = isset($schema['step']) ? (float) $schema['step'] : 1;
        $num = is_numeric($value) ? (float) $value : null;
        if ($num === null) {
            return $schema['default_value'] ?? null;
        }
        if (isset($schema['min']) && $num < (float) $schema['min']) {
            $num = (float) $schema['min'];
        }
        if (isset($schema['max']) && $num > (float) $schema['max']) {
            $num = (float) $schema['max'];
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
