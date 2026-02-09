<?php
/**
 * Sanitize raw form data (e.g. $_POST['field_data']) against a post type schema.
 * Used by post save to build posts.data and posts.layout.
 */

use Modular\Fields\FieldRegistry;
use Modular\Fields\LayoutFieldInterface;

if (!function_exists('sanitize_field_data_from_schema')) {
    /**
     * Recursively sanitize raw field data using schema and FieldRegistry.
     *
     * @param array $schemaFields Array of field definitions (each with type, name, sub_fields, etc.)
     * @param array $raw          Raw keyed data (e.g. $_POST['field_data'])
     * @return array Sanitized data keyed by field name
     */
    function sanitize_field_data_from_schema(array $schemaFields, array $raw): array
    {
        if (!class_exists(FieldRegistry::class)) {
            return [];
        }
        FieldRegistry::bootDefaultFields();

        $out = [];
        foreach ($schemaFields as $fieldDef) {
            $type = $fieldDef['type'] ?? 'text';
            $name = $fieldDef['name'] ?? $fieldDef['slug'] ?? null;
            if ($name === null) {
                continue;
            }
            if (FieldRegistry::isUtilityType($type)) {
                continue;
            }

            $field = FieldRegistry::get($type);
            if (!$field) {
                $out[$name] = $raw[$name] ?? null;
                continue;
            }

            if ($field instanceof LayoutFieldInterface) {
                $childSchema = $field->getChildSchema($fieldDef);
                $subFields = $childSchema['fields'] ?? [];
                $rawValue = $raw[$name] ?? null;
                if (is_string($rawValue) && in_array($type, ['group', 'repeater', 'flexible_content'], true)) {
                    $dec = json_decode($rawValue, true);
                    if (is_array($dec)) {
                        $rawValue = $dec;
                    }
                }

                if ($type === 'group') {
                    $out[$name] = is_array($rawValue) ? sanitize_field_data_from_schema($subFields, $rawValue) : [];
                } elseif ($type === 'repeater') {
                    $rows = [];
                    if (is_array($rawValue) && !empty($subFields)) {
                        foreach (array_values($rawValue) as $row) {
                            if (is_array($row)) {
                                $rows[] = sanitize_field_data_from_schema($subFields, $row);
                            }
                        }
                    }
                    $out[$name] = $rows;
                } elseif ($type === 'flexible_content') {
                    $out[$name] = is_array($rawValue) ? $rawValue : ['blocks' => []];
                } else {
                    $out[$name] = $field->sanitize($rawValue, $fieldDef);
                }
                continue;
            }

            $value = $raw[$name] ?? null;
            $out[$name] = $field->sanitize($value, $fieldDef);
        }
        return $out;
    }
}

if (!function_exists('build_layout_order_from_schema')) {
    /**
     * Build layout array (ordered field names) for modular mode. Same as schema order.
     *
     * @param array $schemaFields
     * @return array List of field names (excluding utility)
     */
    function build_layout_order_from_schema(array $schemaFields): array
    {
        if (!class_exists(FieldRegistry::class)) {
            return [];
        }
        FieldRegistry::bootDefaultFields();
        $order = [];
        foreach ($schemaFields as $fieldDef) {
            $type = $fieldDef['type'] ?? 'text';
            if (FieldRegistry::isUtilityType($type)) {
                continue;
            }
            $name = $fieldDef['name'] ?? $fieldDef['slug'] ?? null;
            if ($name !== null) {
                $order[] = $name;
            }
        }
        return $order;
    }
}
