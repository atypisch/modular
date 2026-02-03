<?php

namespace Modular\Fields;

/**
 * Gallery: multiple images. Optional slideshow config, lazy-loading hints.
 */
class GalleryField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_MEDIA,
            'label' => 'Gallery',
            'icon' => 'fa-images',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'gallery',
            'label' => $schema['label'] ?? 'Gallery',
            'icon' => 'fa-images',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Gallery'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'min' => ['type' => 'integer', 'required' => false],
                'max' => ['type' => 'integer', 'required' => false],
                'preview_size' => ['type' => 'string', 'default' => 'medium'],
                'library' => ['type' => 'string', 'default' => 'all'],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if (is_array($value)) {
            return array_values(array_map('intval', array_filter($value)));
        }
        if ($value === null || $value === '') {
            return [];
        }
        $str = (string) $value;
        if (strpos($str, ',') !== false) {
            $ids = array_map('intval', array_filter(array_map('trim', explode(',', $str))));
            return array_values($ids);
        }
        return [(int) $str];
    }

    public function normalize($value, array $schema = []): array
    {
        if (!is_array($value)) {
            return ['ids' => []];
        }
        return ['ids' => array_values(array_map('intval', $value))];
    }

    public function frontend(array $data): array
    {
        return [
            'ids' => isset($data['ids']) && is_array($data['ids'])
                ? array_values(array_map('intval', $data['ids']))
                : [],
        ];
    }
}
