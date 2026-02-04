<?php

namespace Modular\Fields;

/**
 * Adapter that wraps a FieldInterface and implements FieldContractInterface.
 * Allows using the definitive contract (renderAdmin, renderPublic, sanitize, validate)
 * with existing field types until they are migrated to implement the contract directly.
 */
class FieldContractAdapter implements FieldContractInterface
{
    /** @var FieldInterface */
    private $wrapped;

    /** @var array Field schema (label, name, type, required, + type-specific) */
    private $fieldSchema;

    public function __construct(FieldInterface $wrapped, array $fieldSchema = [])
    {
        $this->wrapped = $wrapped;
        $this->fieldSchema = $fieldSchema;
    }

    /**
     * Update the field schema (e.g. when rendering another field with same type).
     */
    public function setFieldSchema(array $fieldSchema): void
    {
        $this->fieldSchema = $fieldSchema;
    }

    public function renderAdmin(array $field, $value): string
    {
        $schema = array_merge($this->fieldSchema, $field);
        if (!function_exists('render_field_form')) {
            $path = defined('CORE_PATH') ? CORE_PATH : dirname(__DIR__);
            $formFile = $path . '/functions/field-form.php';
            if (is_file($formFile)) {
                require_once $formFile;
            }
        }
        if (!function_exists('render_field_form')) {
            return '<!-- field-form not loaded -->';
        }
        ob_start();
        render_field_form($schema, $value, 'field_data');
        return ob_get_clean();
    }

    public function renderPublic(array $field, $value): array
    {
        $schema = array_merge($this->fieldSchema, $field);
        $normalized = $this->wrapped->normalize($value, $schema);
        return $this->wrapped->frontend($normalized);
    }

    public function sanitize($value)
    {
        return $this->wrapped->sanitize($value, $this->fieldSchema);
    }

    /**
     * @param mixed $value
     * @param array $field Field schema (optional; falls back to adapter's schema)
     * @return bool|array True if valid, or array of error keys e.g. ['required' => true]
     */
    public function validate($value, array $field = [])
    {
        $schema = !empty($field) ? $field : $this->fieldSchema;
        if (!empty($schema['required'])) {
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                return ['required' => true];
            }
        }
        return true;
    }
}
