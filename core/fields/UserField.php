<?php

namespace Modular\Fields;

/**
 * User selector. Single or multiple users.
 */
class UserField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_RELATIONAL,
            'label' => 'User',
            'icon' => 'fa-user',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'user',
            'label' => $schema['label'] ?? 'User',
            'icon' => 'fa-user',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'User'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'role' => ['type' => 'array', 'default' => []],
                'allow_null' => ['type' => 'boolean', 'default' => false],
                'multiple' => ['type' => 'boolean', 'default' => false],
                'return_format' => ['type' => 'string', 'default' => 'id'],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return $schema['multiple'] ? [] : null;
        }
        if (is_array($value)) {
            return array_values(array_map('intval', array_filter($value)));
        }
        return (int) $value;
    }

    public function normalize($value, array $schema = []): array
    {
        if ($value === null || $value === '') {
            return ['user_ids' => []];
        }
        if (is_array($value)) {
            return ['user_ids' => array_values(array_map('intval', $value))];
        }
        return ['user_ids' => [(int) $value]];
    }

    public function frontend(array $data): array
    {
        return [
            'user_ids' => isset($data['user_ids']) && is_array($data['user_ids'])
                ? array_values(array_map('intval', $data['user_ids']))
                : [],
        ];
    }
}
