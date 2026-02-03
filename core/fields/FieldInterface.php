<?php

namespace Modular\Fields;

/**
 * Core field interface.
 *
 * Fields are atomic, self-describing, composable, serializable, schema-driven.
 * They are defined only by Admins, rendered only via core normalization, never render themselves.
 */
interface FieldInterface
{
    /**
     * Static admin metadata: category, icon, label for the Field Library.
     */
    public function adminConfig(): array;

    /**
     * Describe how this field is rendered in the admin UI and its config schema.
     *
     * @param array $schema Current field schema (label, name, type, required, instructions, conditional_logic, + type-specific)
     */
    public function adminInput(array $schema): array;

    /**
     * Sanitize raw form input before storage.
     *
     * @param mixed $value Raw value
     * @param array $schema Field schema (name, type, required, + type-specific config)
     * @return mixed Sanitized value for storage
     */
    public function sanitize($value, array $schema = []);

    /**
     * Normalize stored value into a predictable array structure.
     *
     * @param mixed $value Stored value
     * @param array $schema Field schema
     * @return array Normalized structure for frontend
     */
    public function normalize($value, array $schema = []): array;

    /**
     * Produce frontend-ready data block (public API / themes).
     * Receives normalized data; returns block shape for rendering.
     */
    public function frontend(array $data): array;
}
