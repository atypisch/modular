<?php

namespace Modular\Fields;

class SearchField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_BASIC,
            'label' => 'Search',
            'icon' => 'fa-search',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'search',
            'label' => $schema['label'] ?? 'Search',
            'icon' => 'fa-search',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Search'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'placeholder' => ['type' => 'string', 'required' => false],
                'default_value' => ['type' => 'string', 'required' => false],
                'maxlength' => ['type' => 'integer', 'required' => false],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return $schema['default_value'] ?? null;
        }
        $value = trim((string) $value);
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
            'value' => isset($data['value']) ? (string) $data['value'] : null,
        ];
    }
}
