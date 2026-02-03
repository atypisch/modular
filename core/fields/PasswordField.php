<?php

namespace Modular\Fields;

/**
 * Password field. Value is never echoed in frontend; use for admin-only or internal.
 * Stored hashed if storage layer supports it; otherwise store as-is (caller responsibility).
 */
class PasswordField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_BASIC,
            'label' => 'Password',
            'icon' => 'fa-lock',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'password',
            'label' => $schema['label'] ?? 'Password',
            'icon' => 'fa-lock',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Password'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'placeholder' => ['type' => 'string', 'required' => false],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (string) $value;
    }

    public function normalize($value, array $schema = []): array
    {
        // Never expose raw value in normalized/frontend output.
        return [
            'set' => $value !== null && $value !== '',
        ];
    }

    public function frontend(array $data): array
    {
        return ['set' => !empty($data['set'])];
    }
}
