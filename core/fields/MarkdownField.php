<?php

namespace Modular\Fields;

/**
 * Markdown field. Optional toggle to render as HTML on frontend (core or theme).
 */
class MarkdownField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_CONTENT,
            'label' => 'Markdown',
            'icon' => 'fa-file-code',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'markdown',
            'label' => $schema['label'] ?? 'Markdown',
            'icon' => 'fa-file-code',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Markdown'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'default_value' => ['type' => 'string', 'required' => false],
                'rows' => ['type' => 'integer', 'default' => 10],
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
            'markdown' => $value === null ? ($schema['default_value'] ?? '') : (string) $value,
        ];
    }

    public function frontend(array $data): array
    {
        return [
            'markdown' => isset($data['markdown']) ? (string) $data['markdown'] : '',
        ];
    }
}
