<?php

namespace Modular\Fields;

/**
 * Date Time Picker. Timezone normalization, ISO output.
 */
class DateTimePickerField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_DATE_TIME,
            'label' => 'Date Time Picker',
            'icon' => 'fa-calendar-alt',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'date_time_picker',
            'label' => $schema['label'] ?? 'Date Time',
            'icon' => 'fa-calendar-alt',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Date Time'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'display_format' => ['type' => 'string', 'default' => 'Y-m-d H:i'],
                'return_format' => ['type' => 'string', 'default' => 'Y-m-d H:i:s'],
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
        return trim((string) $value);
    }

    public function normalize($value, array $schema = []): array
    {
        if ($value === null || $value === '') {
            return ['value' => null, 'iso' => null];
        }
        $value = (string) $value;
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $value)
            ?: \DateTime::createFromFormat('Y-m-d\TH:i:s', $value)
            ?: \DateTime::createFromFormat(\DateTime::ATOM, $value);
        $iso = $date ? $date->format('c') : $value;
        return ['value' => $value, 'iso' => $iso];
    }

    public function frontend(array $data): array
    {
        return [
            'value' => $data['value'] ?? null,
            'iso' => $data['iso'] ?? null,
        ];
    }
}
