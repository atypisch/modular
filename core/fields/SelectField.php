<?php

namespace Modular\Fields;

/**
 * Select: single or multiple. Options: static list or dynamic (taxonomies, post types).
 */
class SelectField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_CHOICE,
            'label' => 'Select',
            'icon' => 'fa-list',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'select',
            'label' => $schema['label'] ?? 'Select',
            'icon' => 'fa-list',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Select'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'choices' => ['type' => 'object', 'default' => []],
                'default_value' => ['type' => 'mixed', 'required' => false],
                'allow_null' => ['type' => 'boolean', 'default' => false],
                'multiple' => ['type' => 'boolean', 'default' => false],
                'ui' => ['type' => 'boolean', 'default' => true],
                'ajax' => ['type' => 'boolean', 'default' => false],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        $multiple = !empty($schema['multiple']);
        $choices = $schema['choices'] ?? [];
        if (is_array($choices)) {
            $valid = array_keys($choices);
        } else {
            $valid = [];
        }
        if ($value === null || $value === '') {
            return $multiple ? [] : ($schema['default_value'] ?? ($schema['allow_null'] ? null : ''));
        }
        if ($multiple) {
            $arr = is_array($value) ? $value : [$value];
            $out = array_values(array_filter(array_map(function ($v) use ($valid) {
                $v = (string) $v;
                return $v === '' ? null : $v;
            }, $arr)));
            return empty($valid) ? $out : array_values(array_intersect($out, $valid));
        }
        $v = (string) $value;
        if (empty($valid) || in_array($v, $valid, true)) {
            return $v;
        }
        return $schema['default_value'] ?? ($schema['allow_null'] ? null : '');
    }

    public function normalize($value, array $schema = []): array
    {
        $multiple = !empty($schema['multiple']);
        if ($value === null || $value === '') {
            return ['value' => $multiple ? [] : ($schema['default_value'] ?? null)];
        }
        if ($multiple) {
            $arr = is_array($value) ? $value : [$value];
            return ['value' => array_values(array_map('strval', $arr))];
        }
        return ['value' => (string) $value];
    }

    public function frontend(array $data): array
    {
        $v = $data['value'] ?? null;
        if (is_array($v)) {
            return ['value' => $v];
        }
        return ['value' => $v === null ? null : (string) $v];
    }
}
