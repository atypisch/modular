<?php

namespace Modular\Fields;

class EmailField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_BASIC,
            'label' => 'Email',
            'icon' => 'fa-envelope',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'email',
            'label' => $schema['label'] ?? 'Email',
            'icon' => 'fa-envelope',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Email'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'placeholder' => ['type' => 'string', 'required' => false],
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
        $value = trim((string) $value);
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? $value : ($schema['default_value'] ?? null);
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
            'email' => isset($data['value']) ? (string) $data['value'] : null,
        ];
    }
}
