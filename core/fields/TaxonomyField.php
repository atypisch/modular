<?php

namespace Modular\Fields;

/**
 * Taxonomy selector. Single or multiple terms from one or more taxonomies.
 */
class TaxonomyField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_RELATIONAL,
            'label' => 'Taxonomy',
            'icon' => 'fa-tags',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'taxonomy',
            'label' => $schema['label'] ?? 'Taxonomy',
            'icon' => 'fa-tags',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Taxonomy'],
                'name' => ['type' => 'string', 'required' => true],
                'key' => ['type' => 'string', 'required' => false],
                'required' => ['type' => 'boolean', 'default' => false],
                'instructions' => ['type' => 'string', 'required' => false],
                'taxonomy' => ['type' => 'string', 'default' => ''],
                'field_type' => ['type' => 'string', 'default' => 'select'],
                'allow_null' => ['type' => 'boolean', 'default' => false],
                'multiple' => ['type' => 'boolean', 'default' => false],
                'add_term' => ['type' => 'boolean', 'default' => true],
                'save_terms' => ['type' => 'boolean', 'default' => true],
                'load_terms' => ['type' => 'boolean', 'default' => true],
                'return_format' => ['type' => 'string', 'default' => 'id'],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        if ($value === null || $value === '') {
            return $schema['multiple'] ? [] : null;
        }
        if (is_array($value)) {
            $flat = [];
            foreach ($value as $v) {
                if (is_string($v) && strpos($v, ',') !== false) {
                    foreach (array_map('trim', explode(',', $v)) as $part) {
                        if ($part !== '') {
                            $flat[] = (int) $part;
                        }
                    }
                } else {
                    $flat[] = (int) $v;
                }
            }
            $out = array_values(array_filter($flat));
            return $schema['multiple'] ? $out : ($out[0] ?? null);
        }
        $str = (string) $value;
        if (strpos($str, ',') !== false) {
            $ids = array_map('intval', array_filter(array_map('trim', explode(',', $str))));
            return $schema['multiple'] ? array_values($ids) : ($ids[0] ?? null);
        }
        return (int) $value;
    }

    public function normalize($value, array $schema = []): array
    {
        if ($value === null || $value === '') {
            return ['term_ids' => []];
        }
        if (is_array($value)) {
            return ['term_ids' => array_values(array_map('intval', $value))];
        }
        return ['term_ids' => [(int) $value]];
    }

    public function frontend(array $data): array
    {
        return [
            'term_ids' => isset($data['term_ids']) && is_array($data['term_ids'])
                ? array_values(array_map('intval', $data['term_ids']))
                : [],
        ];
    }
}
