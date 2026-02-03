<?php

namespace Modular\Fields;

/**
 * Embed (oEmbed URL: YouTube, Vimeo, etc.).
 */
class EmbedField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_MEDIA,
            'label' => 'Embed',
            'icon' => 'fa-play-circle',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'embed',
            'label' => $schema['label'] ?? 'Embed',
            'icon' => 'fa-play-circle',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Embed'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return null;
        }
        return trim((string) $value);
    }

    public function normalize($value, array $schema = []): array
    {
        return [
            'url' => $value === null ? null : (string) $value,
        ];
    }

    public function frontend(array $data): array
    {
        return [
            'url' => isset($data['url']) ? (string) $data['url'] : null,
        ];
    }
}
