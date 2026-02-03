<?php

namespace Modular\Fields;

/**
 * Audio file (media ID). Metadata normalization.
 */
class AudioField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_MEDIA,
            'label' => 'Audio',
            'icon' => 'fa-volume-up',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'audio',
            'label' => $schema['label'] ?? 'Audio',
            'icon' => 'fa-volume-up',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Audio'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'return_format' => ['type' => 'string', 'default' => 'id'],
                'library' => ['type' => 'string', 'default' => 'all'],
                'mime_types' => ['type' => 'string', 'default' => 'mp3,wav,ogg'],
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
