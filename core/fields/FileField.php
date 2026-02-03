<?php

namespace Modular\Fields;

/**
 * File upload. Metadata normalization.
 */
class FileField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_MEDIA,
            'label' => 'File',
            'icon' => 'fa-file',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'file',
            'label' => $schema['label'] ?? 'File',
            'icon' => 'fa-file',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'File'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'return_format' => ['type' => 'string', 'default' => 'id'],
                'library' => ['type' => 'string', 'default' => 'all'],
                'mime_types' => ['type' => 'string', 'default' => ''],
                'min_size' => ['type' => 'integer', 'required' => false],
                'max_size' => ['type' => 'integer', 'required' => false],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    public function normalize($value, array $schema = []): array
    {
        if ($value === null || $value === '') {
            return ['id' => null];
        }
        return ['id' => (int) $value];
    }

    public function frontend(array $data): array
    {
        return [
            'id' => isset($data['id']) ? (int) $data['id'] : null,
        ];
    }
}
