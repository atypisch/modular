<?php

namespace Modular\Fields;

/**
 * Video file (media ID). Metadata normalization.
 */
class VideoField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_MEDIA,
            'label' => 'Video',
            'icon' => 'fa-video',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'video',
            'label' => $schema['label'] ?? 'Video',
            'icon' => 'fa-video',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Video'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'return_format' => ['type' => 'string', 'default' => 'id'],
                'preview_size' => ['type' => 'string', 'default' => 'medium'],
                'library' => ['type' => 'string', 'default' => 'all'],
                'mime_types' => ['type' => 'string', 'default' => 'mp4,webm,ogg'],
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
