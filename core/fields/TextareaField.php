<?php

namespace Modular\Fields;

class TextareaField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_BASIC,
            'label' => 'Textarea',
            'icon' => 'fa-align-left',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'textarea',
            'label' => $schema['label'] ?? 'Textarea',
            'icon' => 'fa-align-left',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Textarea'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'placeholder' => ['type' => 'string', 'required' => false],
                'default_value' => ['type' => 'string', 'required' => false],
                'maxlength' => ['type' => 'integer', 'required' => false],
                'rows' => ['type' => 'integer', 'default' => 4],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return $schema['default_value'] ?? null;
        }
        $value = (string) $value;
        $max = isset($schema['maxlength']) ? (int) $schema['maxlength'] : null;
        if ($max !== null && $max > 0 && mb_strlen($value) > $max) {
            $value = mb_substr($value, 0, $max);
        }
        return $value;
    }

    public function normalize($value, array $schema = []): array
    {
        return [
            'value' => $value === null ? ($schema['default_value'] ?? null) : (string) $value,
        ];
    }

    public function frontend(array $data): array
    {
        return [
            'text' => isset($data['value']) ? (string) $data['value'] : null,
        ];
    }
}
