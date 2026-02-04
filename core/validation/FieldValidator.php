<?php

namespace Modular\Validation;

use Modular\Fields\FieldRegistry;

/**
 * Valideert post field data tegen het schema.
 * Per docs/MODULAR_CUSTOM_FIELDS_SPEC: type-aware, geen fallback.
 */
class FieldValidator
{
    /**
     * Valideer alle velden volgens schema.
     *
     * @param array $schema Post type schema ['fields' => [...]]
     * @param array $data   field_data keyed by field name
     * @return array ['valid' => bool, 'errors' => [field_name => error key or message]]
     */
    public static function validateSchema(array $schema, array $data): array
    {
        $errors = [];
        $fields = $schema['fields'] ?? [];

        foreach ($fields as $field) {
            $name = $field['name'] ?? $field['slug'] ?? null;
            if ($name === null || $name === '') {
                continue;
            }
            $value = $data[$name] ?? null;
            $type = $field['type'] ?? 'text';
            $instance = FieldRegistry::get($type);
            if (!$instance) {
                continue;
            }
            if (method_exists($instance, 'validate')) {
                $result = $instance->validate($value, $field);
                if ($result !== true && $result !== []) {
                    $errors[$name] = is_array($result) ? $result : ['invalid' => true];
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
