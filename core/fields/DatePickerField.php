<?php

namespace Modular\Fields;

/**
 * Date Picker. ISO output, timezone normalization, frontend formatting hints.
 */
class DatePickerField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_DATE_TIME,
            'label' => 'Date Picker',
            'icon' => 'fa-calendar',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'date_picker',
            'label' => $schema['label'] ?? 'Date',
            'icon' => 'fa-calendar',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Date'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'display_format' => ['type' => 'string', 'default' => 'Y-m-d'],
                'return_format' => ['type' => 'string', 'default' => 'Y-m-d'],
                'first_day' => ['type' => 'integer', 'default' => 0],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim((string) $value);
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
        return $value;
    }

    public function normalize($value, array $schema = []): array
    {
        if ($value === null || $value === '') {
            return ['value' => null, 'iso' => null];
        }
        $value = (string) $value;
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        $iso = $date !== false ? $date->format('Y-m-d') : $value;
        return ['value' => $iso, 'iso' => $iso];
    }

    public function frontend(array $data): array
    {
        return [
            'value' => $data['value'] ?? null,
            'iso' => $data['iso'] ?? null,
        ];
    }
}
