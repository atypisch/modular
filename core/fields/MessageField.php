<?php

namespace Modular\Fields;

/**
 * Message: admin-only helper text. Never stored in post data; ignored by frontend.
 */
class MessageField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_UTILITY,
            'label' => 'Message',
            'icon' => 'fa-info-circle',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'message',
            'label' => $schema['label'] ?? 'Message',
            'icon' => 'fa-info-circle',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => false],
                'name' => ['type' => 'string', 'required' => false],
                'message' => ['type' => 'string', 'default' => ''],
                'new_lines' => ['type' => 'string', 'default' => 'wpautop'],
                'esc_html' => ['type' => 'boolean', 'default' => true],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        return null;
    }

    public function normalize($value, array $schema = []): array
    {
        return [];
    }

    public function frontend(array $data): array
    {
        return [];
    }
}
