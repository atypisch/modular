<?php

namespace Modular\Fields;

/**
 * Accordion field: stores FAQ items and renders them on the frontend.
 *
 * Stored value format (array of items):
 * [
 *   { "label": "Question?", "content": "Answer text", "open": true|false },
 *   ...
 * ]
 *
 * Admin MVP input accepts a JSON string representing either:
 * - an array of items
 * - or an object { "items": [ ... ] }
 */
class AccordionField implements FieldInterface
{
    public function adminConfig(): array
    {
        return [
            'category' => FieldRegistry::CATEGORY_UTILITY,
            'label' => 'Accordion',
            'icon' => 'fa-chevron-down',
        ];
    }

    public function adminInput(array $schema): array
    {
        return [
            'type' => 'accordion',
            'label' => $schema['label'] ?? 'Accordion',
            'icon' => 'fa-chevron-down',
            'config_schema' => [
                'label' => ['type' => 'string', 'required' => true, 'default' => 'Accordion'],
                // Editor-only config (kept for backward compatibility with older UI)
                'open' => ['type' => 'boolean', 'default' => false],
                'conditional_logic' => ['type' => 'array', 'required' => false],
            ],
        ];
    }

    public function sanitize($value, array $schema = [])
    {
        $items = $this->normalizeItemsInput($value);
        // Store as an array of normalized items (not JSON string).
        return $items;
    }

    public function normalize($value, array $schema = []): array
    {
        // Data is already stored sanitized, but keep it defensive.
        if ($value === null || $value === '') {
            return [];
        }
        return $this->normalizeItemsInput($value);
    }

    public function frontend(array $data): array
    {
        return [
            'items' => is_array($data) ? $data : [],
        ];
    }

    /**
     * Convert raw input into normalized accordion items.
     *
     * @param mixed $value
     * @return array<int, array{label:string, content:string, open:bool}>
     */
    private function normalizeItemsInput($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $trim = trim($value);
            if ($trim === '') return [];
            $decoded = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_array($decoded) && isset($decoded['items']) && is_array($decoded['items'])) {
                    $decoded = $decoded['items'];
                }
                if (is_array($decoded)) {
                    return $this->normalizeItemsArray($decoded);
                }
            }
            return [];
        }

        if (is_array($value)) {
            // Accept {items:[...]} and array-of-items interchangeably.
            if (isset($value['items']) && is_array($value['items'])) {
                $value = $value['items'];
            }
            return $this->normalizeItemsArray($value);
        }

        return [];
    }

    /**
     * @param array $items
     * @return array<int, array{label:string, content:string, open:bool}>
     */
    private function normalizeItemsArray(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;

            $label = '';
            if (array_key_exists('label', $item)) $label = (string) ($item['label'] ?? '');
            if ($label === '' && array_key_exists('question', $item)) $label = (string) ($item['question'] ?? '');

            $content = '';
            if (array_key_exists('content', $item)) $content = (string) ($item['content'] ?? '');
            if ($content === '' && array_key_exists('answer', $item)) $content = (string) ($item['answer'] ?? '');

            if ($label === '' || $content === '') continue;

            $open = !empty($item['open']);
            $out[] = [
                'label' => $label,
                'content' => $content,
                'open' => $open,
            ];
        }

        return array_values($out);
    }
}
