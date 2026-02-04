<?php

namespace Modular\Fields;

/**
 * Definitieve field contract per MODULAR_CUSTOM_FIELDS_SPEC.
 * Elk field type moet dit implementeren.
 * Bestaande FieldInterface blijft; types migreren naar dit contract.
 */
interface FieldContractInterface
{
    /**
     * Render admin edit UI voor dit veld.
     *
     * @param array $field Field schema (label, name, type, required, instructions, + type-specific)
     * @param mixed $value Huidige waarde
     * @return string HTML of output voor de form
     */
    public function renderAdmin(array $field, $value): string;

    /**
     * Render frontend/theming output (type-aware, geen HTML-blobs in data).
     *
     * @param array $field Field schema
     * @param mixed $value Opgeslagen waarde
     * @return array Gestructureerde data voor template (geen conditionals in template)
     */
    public function renderPublic(array $field, $value): array;

    /**
     * Sanitize raw input voor opslag.
     *
     * @param mixed $value Raw form value
     * @return mixed Sanitized value voor storage
     */
    public function sanitize($value);

    /**
     * Valideer waarde (required, type, min/max, etc.).
     *
     * @param mixed $value Waarde om te valideren
     * @param array $field Field schema (voor required, min, max, etc.)
     * @return bool|array True bij geldig, of array met error keys (bijv. ['required' => true])
     */
    public function validate($value, array $field = []);
}
