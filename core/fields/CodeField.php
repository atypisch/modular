<?php

namespace Modular\Fields;

/**
 * Code editor field — language-aware (html, css, js, json, php, etc.).
 */
class CodeField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_CONTENT,
            'label' => 'Code Editor',
            'icon' => 'fa-code',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'code',
            'label' => $schema['label'] ?? 'Code',
            'icon' => 'fa-code',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Code'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'default_value' => ['type' => 'string', 'required' => false],
                'language' => ['type' => 'string', 'default' => 'plain'],
                'theme' => ['type' => 'string', 'default' => 'default'],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return $schema['default_value'] ?? null;
        }
        return (string) $value;
    }

    public function normalize($value, array $schema = []): array
    {
        return [
            'code' => $value === null ? ($schema['default_value'] ?? '') : (string) $value,
            'language' => $schema['language'] ?? 'plain',
        ];
    }

    public function frontend(array $data): array
    {
        return [
            'code' => isset($data['code']) ? (string) $data['code'] : '',
            'language' => $data['language'] ?? 'plain',
        ];
    }
}
