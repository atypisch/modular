<?php

namespace Modular\Fields;

/**
 * Image: single or multiple. Metadata normalization, responsive variants, lazy-loading hints.
 */
class ImageField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_MEDIA,
            'label' => 'Image',
            'icon' => 'fa-image',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'image',
            'label' => $schema['label'] ?? 'Image',
            'icon' => 'fa-image',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Image'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'return_format' => ['type' => 'string', 'default' => 'id'],
                'preview_size' => ['type' => 'string', 'default' => 'medium'],
                'library' => ['type' => 'string', 'default' => 'all'],
                'min_width' => ['type' => 'integer', 'required' => false],
                'min_height' => ['type' => 'integer', 'required' => false],
                'max_width' => ['type' => 'integer', 'required' => false],
                'max_height' => ['type' => 'integer', 'required' => false],
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
