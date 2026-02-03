<?php

namespace Modular\Fields;

/**
 * Time Picker. ISO output, timezone normalization.
 */
class TimePickerField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_DATE_TIME,
            'label' => 'Time Picker',
            'icon' => 'fa-clock',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'time_picker',
            'label' => $schema['label'] ?? 'Time',
            'icon' => 'fa-clock',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Time'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'display_format' => ['type' => 'string', 'default' => 'H:i'],
                'return_format' => ['type' => 'string', 'default' => 'H:i:s'],
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
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
            return $value;
        }
        return $value;
    }

    public function normalize($value, array $schema = []): array
    {
        if ($value === null || $value === '') {
            return ['value' => null, 'iso' => null];
        }
        $value = (string) $value;
        return ['value' => $value, 'iso' => $value];
    }

    public function frontend(array $data): array
    {
        return [
            'value' => $data['value'] ?? null,
            'iso' => $data['iso'] ?? null,
        ];
    }
}
