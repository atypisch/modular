<?php

namespace Modular\Fields;

/**
 * Single source of truth: CMS field type → PrimeVue component, storage format, output.
 * Used to normalize field definitions and to drive the editor renderer.
 * See docs/FIELD_MAPPING.md and docs/FIELD_DEFINITION_SCHEMA.md.
 */
final class FieldSchemaMap
{
    /** @var array<string, array{ui: array, storage: array, output: array}> */
    private static $map;

    private static function init(): void
    {
        if (self::$map !== null) {
            return;
        }
        self::$map = [
            'text' => [
                'ui' => ['component' => 'InputText', 'props' => ['placeholder' => '']],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'textarea' => [
                'ui' => ['component' => 'Textarea', 'props' => ['rows' => 4, 'placeholder' => '']],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'wysiwyg' => [
                'ui' => ['component' => 'WysiwygField', 'props' => ['editorMode' => 'full', 'placeholder' => 'Write content...']],
                'storage' => ['format' => 'html', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'code' => [
                'ui' => ['component' => 'Textarea', 'props' => ['rows' => 10, 'placeholder' => '', 'class' => 'p-monospace']],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'markdown' => [
                'ui' => ['component' => 'Textarea', 'props' => ['rows' => 8, 'placeholder' => '']],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'embed' => [
                'ui' => ['component' => 'InputText', 'props' => ['placeholder' => 'URL']],
                'storage' => ['format' => 'url', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'image' => [
                'ui' => ['component' => 'FileUpload', 'props' => ['mode' => 'advanced', 'accept' => 'image/*']],
                'storage' => ['format' => 'media_id', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'file' => [
                'ui' => ['component' => 'FileUpload', 'props' => ['mode' => 'advanced']],
                'storage' => ['format' => 'media_id', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'gallery' => [
                'ui' => ['component' => 'FileUpload', 'props' => ['mode' => 'advanced', 'multiple' => true, 'accept' => 'image/*']],
                'storage' => ['format' => 'array', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'audio' => [
                'ui' => ['component' => 'FileUpload', 'props' => ['mode' => 'advanced', 'accept' => 'audio/*']],
                'storage' => ['format' => 'media_id', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'video' => [
                'ui' => ['component' => 'FileUpload', 'props' => ['mode' => 'advanced', 'accept' => 'video/*']],
                'storage' => ['format' => 'media_id', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'select' => [
                'ui' => ['component' => 'Dropdown', 'props' => ['optionLabel' => 'label', 'optionValue' => 'value', 'placeholder' => 'Select', 'showClear' => true]],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'checkbox' => [
                'ui' => ['component' => 'Checkbox', 'props' => []],
                'storage' => ['format' => 'bool', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'radio' => [
                'ui' => ['component' => 'RadioButton', 'props' => ['optionLabel' => 'label', 'optionValue' => 'value']],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'button_group' => [
                'ui' => ['component' => 'SelectButton', 'props' => ['optionLabel' => 'label', 'optionValue' => 'value']],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'true_false' => [
                'ui' => ['component' => 'Checkbox', 'props' => ['binary' => true]],
                'storage' => ['format' => 'bool', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'post_object' => [
                'ui' => ['component' => 'AutoComplete', 'props' => ['dropdown' => true, 'minLength' => 1]],
                'storage' => ['format' => 'post_id', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'relationship' => [
                'ui' => ['component' => 'AutoComplete', 'props' => ['dropdown' => true, 'multiple' => true, 'minLength' => 1]],
                'storage' => ['format' => 'array', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'relation' => [
                'ui' => ['component' => 'AutoComplete', 'props' => ['dropdown' => true, 'multiple' => true, 'minLength' => 1]],
                'storage' => ['format' => 'array', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'taxonomy' => [
                'ui' => ['component' => 'TreeSelect', 'props' => ['placeholder' => 'Select']],
                'storage' => ['format' => 'term_ids', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'user' => [
                'ui' => ['component' => 'Dropdown', 'props' => ['optionLabel' => 'label', 'optionValue' => 'value', 'placeholder' => 'Select user', 'showClear' => true]],
                'storage' => ['format' => 'user_id', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'email' => [
                'ui' => ['component' => 'InputText', 'props' => ['placeholder' => '']],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'url' => [
                'ui' => ['component' => 'InputText', 'props' => ['placeholder' => 'https://']],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'number' => [
                'ui' => ['component' => 'InputNumber', 'props' => []],
                'storage' => ['format' => 'number', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'range' => [
                'ui' => ['component' => 'Slider', 'props' => []],
                'storage' => ['format' => 'number', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'password' => [
                'ui' => ['component' => 'Password', 'props' => []],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'search' => [
                'ui' => ['component' => 'InputText', 'props' => ['placeholder' => 'Search']],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'hidden' => [
                'ui' => ['component' => null, 'props' => []],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'date_picker' => [
                'ui' => ['component' => 'Calendar', 'props' => ['dateFormat' => 'yy-mm-dd']],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'time_picker' => [
                'ui' => ['component' => 'Calendar', 'props' => ['timeOnly' => true, 'hourFormat' => '24']],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'date_time_picker' => [
                'ui' => ['component' => 'Calendar', 'props' => ['showTime' => true, 'hourFormat' => '24']],
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'group' => [
                'ui' => ['component' => 'Group', 'props' => []],
                'storage' => ['format' => 'object', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'repeater' => [
                'ui' => ['component' => 'Repeater', 'props' => []],
                'storage' => ['format' => 'array', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'flexible_content' => [
                'ui' => ['component' => 'FlexibleContent', 'props' => []],
                'storage' => ['format' => 'array', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'message' => [
                'ui' => ['component' => 'Message', 'props' => []],
                'storage' => ['format' => null, 'column' => null],
                'output' => ['php' => null, 'json' => null],
            ],
            'accordion' => [
                // FAQ accordion is stored in post.data as an array of items.
                'ui' => [
                    'component' => 'Textarea',
                    'props' => [
                        'rows' => 12,
                        'placeholder' => '[{"label":"Question?","content":"Answer text","open":false}]',
                    ],
                ],
                // Admin editor uses a JSON textarea (single string), so we keep schema storage.format as string.
                // AccordionField::sanitize() parses JSON into the final items array for storage/rendering.
                'storage' => ['format' => 'string', 'column' => ''],
                'output' => ['php' => '', 'json' => ''],
            ],
            'tab' => [
                'ui' => ['component' => 'Tabs', 'props' => []],
                'storage' => ['format' => null, 'column' => null],
                'output' => ['php' => null, 'json' => null],
            ],
            'divider' => [
                'ui' => ['component' => 'Divider', 'props' => []],
                'storage' => ['format' => null, 'column' => null],
                'output' => ['php' => null, 'json' => null],
            ],
        ];
    }

    /**
     * Return default ui/storage/output for a field type. Key is filled from $key when provided.
     */
    public static function defaultsForType(string $type, string $key = ''): array
    {
        self::init();
        $type = strtolower($type);
        $out = self::$map[$type] ?? [
            'ui' => ['component' => 'InputText', 'props' => []],
            'storage' => ['format' => 'string', 'column' => $key],
            'output' => ['php' => '$post->' . $key, 'json' => $key],
        ];
        $column = $key !== '' ? $key : ($out['storage']['column'] ?? '');
        $out['storage']['column'] = $column;
        $out['output']['php'] = $out['output']['php'] ?: ('$post->' . $column);
        $out['output']['json'] = $out['output']['json'] ?: $column;
        return $out;
    }

    /**
     * Normalize a single field definition to canonical schema (key, label, instructions, required, default, ui, storage, output).
     * Merges existing keys with defaults from map; ensures ui.component and ui.props exist.
     */
    public static function normalizeField(array $field): array
    {
        $type = $field['type'] ?? 'text';
        $key = $field['key'] ?? $field['name'] ?? '';
        if ($key === '' && isset($field['label'])) {
            $key = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($field['label']))) ?: 'field';
        }
        if ($key === '') {
            $key = 'field';
        }

        $defaults = self::defaultsForType($type, $key);
        $ui = array_merge($defaults['ui'], $field['ui'] ?? []);
        $ui['props'] = array_merge($defaults['ui']['props'] ?? [], $field['ui']['props'] ?? []);
        $storage = array_merge($defaults['storage'], $field['storage'] ?? []);
        $output = array_merge($defaults['output'], $field['output'] ?? []);

        return array_merge($field, [
            'type' => $type,
            'key' => $key,
            'name' => $key,
            'label' => $field['label'] ?? ucfirst($key),
            'instructions' => $field['instructions'] ?? '',
            'required' => !empty($field['required']),
            'default' => $field['default'] ?? null,
            'ui' => $ui,
            'storage' => $storage,
            'output' => $output,
        ]);
    }

    /**
     * Normalize a full schema (fields array).
     *
     * @param array $schema ['fields' => [...]]
     * @return array Normalized schema
     */
    public static function normalizeSchema(array $schema): array
    {
        $fields = $schema['fields'] ?? [];
        $normalized = [];
        foreach ($fields as $f) {
            if (empty($f['type'])) {
                continue;
            }
            $normalized[] = self::normalizeField($f);
        }
        return ['fields' => $normalized];
    }

    /**
     * Return mapping for JS (type → ui.component, default props).
     */
    public static function getMapForFrontend(): array
    {
        self::init();
        $out = [];
        foreach (self::$map as $type => $def) {
            $out[$type] = [
                'component' => $def['ui']['component'] ?? 'InputText',
                'props' => $def['ui']['props'] ?? [],
            ];
        }
        return $out;
    }
}
