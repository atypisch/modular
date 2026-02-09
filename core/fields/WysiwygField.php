<?php

namespace Modular\Fields;

class WysiwygField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_CONTENT,
            'label' => 'WYSIWYG Editor',
            'icon' => 'fa-paragraph',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'wysiwyg',
            'label' => $schema['label'] ?? 'Content',
            'icon' => 'fa-paragraph',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Content'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'default_value' => ['type' => 'string', 'required' => false],
                'toolbar' => ['type' => 'string', 'default' => 'full'],
                'media_upload' => ['type' => 'boolean', 'default' => true],
                'tabs' => ['type' => 'string', 'default' => 'all'],
                'delay' => ['type' => 'boolean', 'default' => false],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null) {
            return $schema['default_value'] ?? null;
        }
        return (string) $value;
    }

    public function normalize($value, array $schema = []): array
    {
        return [
            'html' => $value === null ? ($schema['default_value'] ?? '') : (string) $value,
        ];
    }

    public function frontend(array $data): array
    {
        return [
            'html' => isset($data['html']) ? (string) $data['html'] : '',
        ];
    }
}
