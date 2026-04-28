<?php
/**
 * Admin form rendering for schema-based post fields.
 * Renders HTML inputs for each field type; name prefix e.g. field_data or field_data[group][sub].
 */

use Modular\Fields\FieldRegistry;
use Modular\Fields\LayoutFieldInterface;

if (!function_exists('get_posts_for_relational_field')) {
    /**
     * Load posts for post_object/relationship dropdowns. Filter by schema post_type (slugs). Limit 500.
     *
     * @param array $schema Field schema with optional 'post_type' => [slugs]
     * @return array<array{id: int, title: string, post_type_slug: string}>
     */
    function get_posts_for_relational_field(array $schema): array
    {
        $db = $GLOBALS['db'] ?? null;
        if (!$db) {
            return [];
        }
        $allowedSlugs = $schema['post_type'] ?? [];
        if (!is_array($allowedSlugs)) {
            $allowedSlugs = [];
        }
        try {
            $sql = "SELECT p.id, p.title, pt.slug AS post_type_slug
                    FROM posts p
                    INNER JOIN post_types pt ON p.post_type_id = pt.id
                    WHERE p.deleted_at IS NULL AND pt.deleted_at IS NULL";
            $params = [];
            if (!empty($allowedSlugs)) {
                $placeholders = implode(',', array_fill(0, count($allowedSlugs), '?'));
                $sql .= " AND pt.slug IN ($placeholders)";
                $params = array_values($allowedSlugs);
            }
            $sql .= " ORDER BY p.updated_at DESC, p.id DESC LIMIT 500";
            $stmt = $params ? $db->prepare($sql) : $db->query($sql);
            if ($stmt) {
                if ($params) {
                    $stmt->execute($params);
                }
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            // ignore
        }
        return [];
    }
}

if (!function_exists('render_field_instruction_hint')) {
    /**
     * Default instruction hint per field type when instructions are empty (ACF/PrimeVue-style).
     */
    function render_field_instruction_hint(string $type, array $fieldDef): string
    {
        switch ($type) {
            case 'wysiwyg':
                return 'Rich text editor (like ACF Visual/Text or PrimeVue Editor). Toolbar and tabs are set in Field Builder → Display.';
            case 'image':
                return 'Media ID from the Media library. Upload in Media first, then paste the ID here. (PrimeVue: FileUpload for picker UX later.)';
            case 'file':
                return 'Media ID of the file from the Media library. (PrimeVue: FileUpload.)';
            case 'gallery':
                return 'Comma-separated Media IDs (e.g. 12,14,18). Upload in Media, then list IDs. (PrimeVue: FileUpload multiple.)';
            case 'post_object':
                return 'Select one linked post. Allowed post types in Field Builder → General. (PrimeVue: AutoComplete.)';
            case 'relationship':
            case 'relation':
                return 'Select one or more posts. Allowed post types in Field Builder → General. (PrimeVue: AutoComplete multiple.)';
            case 'taxonomy':
                return 'Term IDs (comma-separated) or configure a term selector in Field Builder. (PrimeVue: TreeSelect.)';
            case 'user':
                return 'Select a user; stored as user ID. (PrimeVue: Dropdown or AutoComplete.)';
            case 'date_picker':
                return 'Pick a date. Stored as Y-m-d. (PrimeVue: Calendar.)';
            case 'time_picker':
                return 'Pick a time. Stored as H:i. (PrimeVue: Calendar timeOnly.)';
            case 'date_time_picker':
                return 'Pick date and time. Stored as Y-m-d H:i. (PrimeVue: Calendar with showTime.)';
            case 'select':
                return 'Choose one. Add choices in Field Builder → Display (one per line: value : Label). (PrimeVue: Dropdown.)';
            case 'radio':
            case 'button_group':
                return 'Choose one. Configure choices in Field Builder → Display. (PrimeVue: RadioButton / SelectButton.)';
            case 'checkbox':
                return 'Check to enable, or add multiple options in Field Builder → Display: Choices. (PrimeVue: Checkbox.)';
            default:
                return '';
        }
    }
}

if (!function_exists('render_field_form')) {
    /**
     * Ensure FieldRegistry is booted and render a single field's form control.
     *
     * @param array  $fieldDef   Field schema (type, name, label, required, instructions, + type-specific)
     * @param mixed  $value      Current value
     * @param string $namePrefix e.g. "field_data" or "field_data[group_name]"
     */
    function render_field_form(array $fieldDef, $value, string $namePrefix = 'field_data'): void
    {
        if (!class_exists(FieldRegistry::class)) {
            return;
        }
        FieldRegistry::bootDefaultFields();

        $type = $fieldDef['type'] ?? 'text';
        $name = $fieldDef['name'] ?? $fieldDef['slug'] ?? '';
        if ($name === '') {
            return;
        }

        if (FieldRegistry::isUtilityType($type)) {
            render_field_utility($fieldDef);
            return;
        }

        $field = FieldRegistry::get($type);
        if (!$field) {
            render_field_fallback($fieldDef, $value, $namePrefix);
            return;
        }

        if ($field instanceof LayoutFieldInterface) {
            render_field_layout($field, $fieldDef, $value, $namePrefix);
            return;
        }

        $inputName = $namePrefix === 'field_data' ? 'field_data[' . htmlspecialchars($name) . ']' : $namePrefix . '[' . htmlspecialchars($name) . ']';
        $id = 'field-' . preg_replace('/[^a-z0-9_-]/i', '-', $namePrefix . '-' . $name);

        if ($type === 'hidden') {
            render_field_input($type, $fieldDef, $value, $inputName, $id);
            return;
        }

        $label = $fieldDef['label'] ?? ucfirst($name);
        $required = !empty($fieldDef['required']);
        $instructions = $fieldDef['instructions'] ?? '';
        $typeHint = render_field_instruction_hint($type, $fieldDef);
        $instructions = $instructions !== '' ? $instructions : $typeHint;
        ?>
        <div class="form-group field-type-<?php echo htmlspecialchars($type); ?>" data-field-type="<?php echo htmlspecialchars($type); ?>">
            <label for="<?php echo $id; ?>">
                <?php echo htmlspecialchars($label); ?>
                <?php if ($required): ?><span class="required-asterisk" aria-label="Required">*</span><?php endif; ?>
            </label>
            <?php render_field_input($type, $fieldDef, $value, $inputName, $id); ?>
            <?php if ($instructions !== ''): ?>
                <div class="help-text"><?php echo htmlspecialchars($instructions); ?></div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the actual input element by type.
     */
    function render_field_input(string $type, array $schema, $value, string $inputName, string $id): void
    {
        $val = $value;
        if ($val === null && isset($schema['default_value'])) {
            $val = $schema['default_value'];
        }

        switch ($type) {
            case 'text':
            case 'email':
            case 'url':
            case 'password':
                $attr = $type === 'password' ? ' autocomplete="new-password"' : '';
                $max = isset($schema['maxlength']) ? (int) $schema['maxlength'] : '';
                $ph = isset($schema['placeholder']) ? ' placeholder="' . htmlspecialchars($schema['placeholder']) . '"' : '';
                echo '<input type="' . htmlspecialchars($type) . '" name="' . $inputName . '" id="' . $id . '" class="form-control" value="' . htmlspecialchars((string)$val) . '"' . $attr . ($max ? ' maxlength="' . $max . '"' : '') . $ph . '>';
                break;
            case 'number':
                $min = isset($schema['min']) ? (int) $schema['min'] : '';
                $max = isset($schema['max']) ? (int) $schema['max'] : '';
                $step = isset($schema['step']) ? (float) $schema['step'] : '';
                echo '<input type="number" name="' . $inputName . '" id="' . $id . '" class="form-control" value="' . htmlspecialchars((string)$val) . '"' .
                    ($min !== '' ? ' min="' . $min . '"' : '') . ($max !== '' ? ' max="' . $max . '"' : '') . ($step !== '' ? ' step="' . $step . '"' : '') . '>';
                break;
            case 'range':
                $min = isset($schema['min']) ? (float) $schema['min'] : 0;
                $max = isset($schema['max']) ? (float) $schema['max'] : 100;
                $step = isset($schema['step']) ? (float) $schema['step'] : 1;
                $rangeVal = $val !== null && $val !== '' ? (float) $val : $min;
                echo '<input type="range" name="' . $inputName . '" id="' . $id . '" class="form-control" min="' . $min . '" max="' . $max . '" step="' . $step . '" value="' . htmlspecialchars((string)$rangeVal) . '">';
                echo ' <span class="range-value">' . htmlspecialchars((string)$rangeVal) . '</span>';
                break;
            case 'search':
                $max = isset($schema['maxlength']) ? (int) $schema['maxlength'] : '';
                $ph = isset($schema['placeholder']) ? ' placeholder="' . htmlspecialchars($schema['placeholder']) . '"' : '';
                echo '<input type="search" name="' . $inputName . '" id="' . $id . '" class="form-control" value="' . htmlspecialchars((string)$val) . '"' . ($max ? ' maxlength="' . $max . '"' : '') . $ph . '>';
                break;
            case 'hidden':
                $hiddenVal = $val !== null && $val !== '' ? (string) $val : (isset($schema['default_value']) ? (string) $schema['default_value'] : '');
                echo '<input type="hidden" name="' . $inputName . '" id="' . $id . '" value="' . htmlspecialchars($hiddenVal) . '">';
                break;
            case 'textarea':
                $rows = isset($schema['rows']) ? (int) $schema['rows'] : 4;
                echo '<textarea name="' . $inputName . '" id="' . $id . '" class="form-control" rows="' . $rows . '">' . htmlspecialchars((string)$val) . '</textarea>';
                break;
            case 'wysiwyg':
                $rows = isset($schema['rows']) ? (int) $schema['rows'] : 8;
                $wysiwygClass = 'form-control wysiwyg-textarea';
                if (!empty($schema['toolbar']) && $schema['toolbar'] === 'basic') {
                    $wysiwygClass .= ' wysiwyg-toolbar-basic';
                }
                echo '<textarea name="' . $inputName . '" id="' . $id . '" class="' . $wysiwygClass . '" rows="' . $rows . '" data-rows="' . $rows . '" data-tabs="' . htmlspecialchars($schema['tabs'] ?? 'all') . '" data-toolbar="' . htmlspecialchars($schema['toolbar'] ?? 'full') . '">' . htmlspecialchars((string)$val) . '</textarea>';
                break;
            case 'code':
            case 'markdown':
                $rows = isset($schema['rows']) ? (int) $schema['rows'] : 10;
                echo '<textarea name="' . $inputName . '" id="' . $id . '" class="form-control" rows="' . $rows . '" style="font-family: monospace;">' . htmlspecialchars((string)$val) . '</textarea>';
                break;
            case 'select':
                $choices = $schema['choices'] ?? [];
                $multiple = !empty($schema['multiple']);
                $allowNull = !empty($schema['allow_null']);
                echo '<select name="' . $inputName . ($multiple ? '[]' : '') . '" id="' . $id . '" class="form-control"' . ($multiple ? ' multiple' : '') . '>';
                if (!$multiple && $allowNull) {
                    echo '<option value="">—</option>';
                }
                $arrVal = $multiple && is_array($val) ? $val : ($val !== null && $val !== '' ? [$val] : []);
                foreach ($choices as $k => $label) {
                    $sel = $multiple ? in_array((string)$k, array_map('strval', $arrVal), true) : ((string)$val === (string)$k);
                    echo '<option value="' . htmlspecialchars((string)$k) . '"' . ($sel ? ' selected' : '') . '>' . htmlspecialchars(is_int($k) ? (string)$label : $label) . '</option>';
                }
                echo '</select>';
                break;
            case 'radio':
                $choices = $schema['choices'] ?? [];
                foreach ($choices as $k => $label) {
                    $sel = (string)$val === (string)$k;
                    echo '<label class="radio-inline"><input type="radio" name="' . $inputName . '" value="' . htmlspecialchars((string)$k) . '"' . ($sel ? ' checked' : '') . '> ' . htmlspecialchars(is_int($k) ? (string)$label : $label) . '</label> ';
                }
                break;
            case 'checkbox':
                $choices = $schema['choices'] ?? [];
                if (empty($choices)) {
                    $checked = (bool) $val;
                    echo '<input type="hidden" name="' . $inputName . '" value="0"><input type="checkbox" name="' . $inputName . '" value="1" id="' . $id . '"' . ($checked ? ' checked' : '') . '>';
                } else {
                    $arrVal = is_array($val) ? $val : [];
                    foreach ($choices as $k => $label) {
                        $checked = in_array((string)$k, array_map('strval', $arrVal), true);
                        echo '<label class="checkbox-inline"><input type="checkbox" name="' . $inputName . '[]" value="' . htmlspecialchars((string)$k) . '"' . ($checked ? ' checked' : '') . '> ' . htmlspecialchars(is_int($k) ? (string)$label : $label) . '</label> ';
                    }
                }
                break;
            case 'button_group':
                $choices = $schema['choices'] ?? [];
                foreach ($choices as $k => $label) {
                    $sel = (string)$val === (string)$k;
                    echo '<label class="btn-group-inline"><input type="radio" name="' . $inputName . '" value="' . htmlspecialchars((string)$k) . '"' . ($sel ? ' checked' : '') . '> ' . htmlspecialchars(is_int($k) ? (string)$label : $label) . '</label> ';
                }
                break;
            case 'true_false':
                $checked = (bool) $val;
                echo '<input type="hidden" name="' . $inputName . '" value="0"><input type="checkbox" name="' . $inputName . '" value="1" id="' . $id . '"' . ($checked ? ' checked' : '') . '>';
                break;
            case 'image':
            case 'file':
                $idVal = $val;
                if (is_array($val) && isset($val['id'])) {
                    $idVal = $val['id'];
                }
                echo '<input type="number" name="' . $inputName . '" id="' . $id . '" class="form-control" value="' . ($idVal !== null && $idVal !== '' ? (int)$idVal : '') . '" placeholder="Media ID">';
                break;
            case 'post_object':
            case 'relationship':
            case 'relation':
            case 'user':
                $idVal = $val;
                if (is_array($val) && isset($val['id'])) {
                    $idVal = $val['id'];
                }
                if (in_array($type, ['relationship', 'relation'], true) && is_array($val) && isset($val['ids'])) {
                    $idVal = $val['ids'];
                }
                if (is_array($idVal)) {
                    $selectedIds = array_map('intval', array_filter($idVal));
                } else {
                    $selectedIds = $idVal !== null && $idVal !== '' ? [ (int) $idVal ] : [];
                }
                $posts = $type === 'user' ? [] : get_posts_for_relational_field($schema);
                $multiple = in_array($type, ['relationship', 'relation'], true);
                $allowNull = !empty($schema['allow_null']);
                if ($type === 'user') {
                    echo '<input type="text" name="' . $inputName . '" id="' . $id . '" class="form-control" value="' . htmlspecialchars(is_array($selectedIds) ? implode(',', $selectedIds) : (string)($selectedIds[0] ?? '')) . '" placeholder="User ID">';
                } elseif (empty($posts)) {
                    echo '<input type="text" name="' . $inputName . ($multiple ? '[]' : '') . '" id="' . $id . '" class="form-control" value="' . htmlspecialchars($multiple ? implode(',', $selectedIds) : (string)($selectedIds[0] ?? '')) . '" placeholder="' . ($multiple ? 'Post IDs (comma-separated)' : 'Post ID') . '">';
                } else {
                    echo '<select name="' . $inputName . ($multiple ? '[]' : '') . '" id="' . $id . '" class="form-control"' . ($multiple ? ' multiple size="10"' : '') . '>';
                    if (!$multiple && $allowNull) {
                        echo '<option value="">— Select —</option>';
                    }
                    foreach ($posts as $p) {
                        $pid = (int) $p['id'];
                        $title = $p['title'] ?? ('Post #' . $pid);
                        $slug = $p['post_type_slug'] ?? '';
                        $label = htmlspecialchars($title) . ($slug ? ' (' . htmlspecialchars($slug) . ')' : '');
                        $sel = $multiple ? in_array($pid, $selectedIds, true) : (($selectedIds[0] ?? null) === $pid);
                        echo '<option value="' . $pid . '"' . ($sel ? ' selected' : '') . '>' . $label . '</option>';
                    }
                    echo '</select>';
                }
                break;
            case 'taxonomy':
                $arrVal = is_array($val) ? $val : ($val !== null && $val !== '' ? [$val] : []);
                echo '<input type="text" name="' . $inputName . '[]" id="' . $id . '" class="form-control" value="' . htmlspecialchars(implode(', ', array_map('strval', $arrVal))) . '" placeholder="Term IDs comma-separated">';
                break;
            case 'gallery':
                $ids = is_array($val) && isset($val['ids']) ? $val['ids'] : (is_array($val) ? $val : []);
                echo '<input type="text" name="' . $inputName . '" id="' . $id . '" class="form-control" value="' . htmlspecialchars(implode(',', array_map('intval', $ids))) . '" placeholder="Comma-separated media IDs">';
                break;
            case 'date_picker':
            case 'time_picker':
            case 'date_time_picker':
                $fmt = $type === 'date_picker' ? 'Y-m-d' : ($type === 'time_picker' ? 'H:i' : 'Y-m-d H:i');
                if ($val instanceof \DateTimeInterface) {
                    $val = $val->format($fmt);
                } elseif (is_numeric($val)) {
                    $val = date($fmt, (int) $val);
                } elseif (is_array($val) && isset($val['value'])) {
                    $val = $val['value'];
                }
                $inputType = $type === 'date_picker' ? 'date' : ($type === 'time_picker' ? 'time' : 'datetime-local');
                echo '<input type="' . $inputType . '" name="' . $inputName . '" id="' . $id . '" class="form-control" value="' . htmlspecialchars((string)$val) . '">';
                break;
            case 'embed':
            case 'audio':
            case 'video':
                echo '<input type="text" name="' . $inputName . '" id="' . $id . '" class="form-control" value="' . htmlspecialchars(is_array($val) && isset($val['url']) ? $val['url'] : (string)$val) . '" placeholder="URL">';
                break;
            case 'accordion':
                $rows = isset($schema['rows']) ? (int) $schema['rows'] : 12;
                $placeholder = 'FAQ items JSON: [{"label":"Question?","content":"Answer text","open":false}]';
                $current = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $val;
                echo '<textarea name="' . $inputName . '" id="' . $id . '" class="form-control" rows="' . (int)$rows . '" placeholder="' . htmlspecialchars($placeholder) . '">' . htmlspecialchars($current) . '</textarea>';
                break;
            default:
                echo '<input type="text" name="' . $inputName . '" id="' . $id . '" class="form-control" value="' . htmlspecialchars((string)$val) . '">';
        }
    }

    function render_field_utility(array $fieldDef): void
    {
        $type = $fieldDef['type'] ?? '';
        $label = $fieldDef['label'] ?? ucfirst($type);
        $instructions = $fieldDef['instructions'] ?? '';
        if ($type === 'message') {
            echo '<div class="form-group form-group-utility form-group-message">';
            echo '<p class="field-message-text">' . nl2br(htmlspecialchars($label)) . '</p>';
            if ($instructions !== '') {
                echo '<p class="help-text">' . htmlspecialchars($instructions) . '</p>';
            }
            echo '</div>';
        } elseif ($type === 'divider') {
            echo '<hr class="section-divider field-divider" aria-hidden="true">';
        } elseif ($type === 'accordion') {
            echo '<div class="form-group form-group-utility form-group-accordion" role="region" aria-label="' . htmlspecialchars($label) . '">';
            echo '<div class="field-accordion-header">';
            echo '<span class="field-accordion-icon" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>';
            echo '<strong class="field-accordion-title">' . htmlspecialchars($label) . '</strong>';
            if ($instructions !== '') {
                echo '<p class="help-text field-accordion-hint">' . htmlspecialchars($instructions) . '</p>';
            }
            echo '</div></div>';
        } elseif ($type === 'tab') {
            echo '<div class="form-group form-group-utility form-group-tab" role="tablist" aria-label="Section">';
            echo '<div class="field-tab-label">' . htmlspecialchars($label) . '</div>';
            if ($instructions !== '') {
                echo '<p class="help-text">' . htmlspecialchars($instructions) . '</p>';
            }
            echo '</div>';
        } else {
            echo '<div class="form-group form-group-utility"><strong>' . htmlspecialchars($label) . '</strong></div>';
        }
    }

    function render_field_fallback(array $fieldDef, $value, string $namePrefix): void
    {
        $name = $fieldDef['name'] ?? $fieldDef['slug'] ?? 'unknown';
        $inputName = $namePrefix === 'field_data' ? 'field_data[' . htmlspecialchars($name) . ']' : $namePrefix . '[' . htmlspecialchars($name) . ']';
        $id = 'field-' . preg_replace('/[^a-z0-9_-]/i', '-', $namePrefix . '-' . $name);
        $label = $fieldDef['label'] ?? $name;
        echo '<div class="form-group"><label for="' . $id . '">' . htmlspecialchars($label) . '</label>';
        echo '<input type="text" name="' . $inputName . '" id="' . $id . '" class="form-control" value="' . htmlspecialchars((string)$value) . '"></div>';
    }

    function render_field_layout(LayoutFieldInterface $field, array $fieldDef, $value, string $namePrefix): void
    {
        $type = $fieldDef['type'] ?? 'group';
        $name = $fieldDef['name'] ?? $fieldDef['slug'] ?? '';
        $label = $fieldDef['label'] ?? ucfirst($name);
        $childSchema = $field->getChildSchema($fieldDef);
        $subFields = $childSchema['fields'] ?? [];

        if ($type === 'group') {
            $childData = (is_array($value) && isset($value['children'])) ? $value['children'] : (is_array($value) ? $value : []);
            echo '<div class="form-group form-group-layout" data-field-type="group">';
            echo '<strong class="block margin-bottom">' . htmlspecialchars($label) . '</strong>';
            $prefix = $namePrefix === 'field_data' ? 'field_data[' . $name . ']' : $namePrefix . '[' . $name . ']';
            foreach ($subFields as $sub) {
                $subName = $sub['name'] ?? $sub['slug'] ?? null;
                if ($subName === null) {
                    continue;
                }
                $subVal = $childData[$subName] ?? null;
                render_field_form($sub, $subVal, $prefix);
            }
            echo '</div>';
            return;
        }

        if ($type === 'repeater') {
            $rows = (is_array($value) && isset($value['rows'])) ? $value['rows'] : (is_array($value) ? $value : []);
            if (empty($rows)) {
                $rows = [[]];
            }
            echo '<div class="form-group form-group-repeater" data-field-type="repeater" data-field-name="' . htmlspecialchars($name) . '">';
            echo '<strong class="block margin-bottom">' . htmlspecialchars($label) . '</strong>';
            foreach ($rows as $i => $row) {
                echo '<div class="repeater-row" data-index="' . $i . '">';
                $prefix = 'field_data[' . $name . '][' . $i . ']';
                foreach ($subFields as $sub) {
                    $subName = $sub['name'] ?? $sub['slug'] ?? null;
                    if ($subName === null) {
                        continue;
                    }
                    $subVal = is_array($row) ? ($row[$subName] ?? null) : null;
                    render_field_form($sub, $subVal, $prefix);
                }
                echo '</div>';
            }
            echo '<button type="button" class="btn btn-secondary btn-sm repeater-add-row">Add row</button>';
            echo '</div>';
            return;
        }

        if ($type === 'flexible_content') {
            $layouts = $fieldDef['layouts'] ?? [];
            $blocks = is_array($value) && isset($value['blocks']) ? $value['blocks'] : [];
            echo '<div class="form-group form-group-flexible" data-field-type="flexible_content">';
            echo '<strong class="block margin-bottom">' . htmlspecialchars($label) . '</strong>';
            echo '<p class="help-text">Flexible content editing is simplified: use group/repeater for now.</p>';
            echo '</div>';
        }
    }
}

if (!function_exists('render_schema_fields_form')) {
    /**
     * Render all non-utility fields from a schema (flat list). Used for post edit.
     *
     * @param array $fields    Schema fields array
     * @param array $data     Current post data (keyed by field name)
     * @param string $prefix  Name prefix for form (e.g. field_data)
     */
    function render_schema_fields_form(array $fields, array $data = [], string $prefix = 'field_data'): void
    {
        if (!class_exists(FieldRegistry::class)) {
            return;
        }
        FieldRegistry::bootDefaultFields();
        foreach ($fields as $fieldDef) {
            $type = $fieldDef['type'] ?? 'text';
            if (FieldRegistry::isUtilityType($type)) {
                render_field_form($fieldDef, null, $prefix);
                continue;
            }
            $name = $fieldDef['name'] ?? $fieldDef['slug'] ?? null;
            if ($name === null) {
                continue;
            }
            $value = $data[$name] ?? null;
            render_field_form($fieldDef, $value, $prefix);
        }
    }
}
