<?php

namespace Modular\Fields;

/**
 * Layout fields define structure: Group, Repeater, Flexible Content.
 * Stored as nested schemas; normalized recursively by core.
 * Never serialized blobs — predictable JSON shape.
 */
interface LayoutFieldInterface extends FieldInterface
{
    /**
     * Whether this field stores nested content (repeater, flexible, group).
     */
    public function isLayout(): bool;

    /**
     * Return the child field schema(s) for this layout.
     * Group: single set of sub-fields.
     * Repeater: single set repeated.
     * Flexible: array of layouts, each with name + sub_fields.
     *
     * @param array $schema This field's full schema
     * @return array For Group/Repeater: ['fields' => [...]]. For Flexible: ['layouts' => [['name','label','fields'=>[...]], ...]]
     */
    public function getChildSchema(array $schema): array;
}
