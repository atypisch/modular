/**
 * Modular CMS → PrimeVue field renderer factory.
 * resolveFieldComponent(fieldType, fieldOptions) returns component config with props, events, styledOptions.
 * Use with Vue 3 + PrimeVue; all styling controllable via styledOptions.
 */
(function (global) {
    'use strict';

    var UTILITY_TYPES = ['message', 'accordion', 'tab', 'divider'];
    var LAYOUT_TYPES = ['group', 'repeater', 'flexible_content'];
    var NO_STORAGE_TYPES = UTILITY_TYPES.slice();

    /**
     * Normalize choices from schema (array of {value, label} or object {value: label} or "value : Label" lines) to [{label, value}].
     * @param {*} choices
     * @returns {Array<{label: string, value: string}>}
     */
    function normalizeChoices(choices) {
        if (!choices || (Array.isArray(choices) && choices.length === 0)) return [];
        if (Array.isArray(choices)) {
            return choices.map(function (c) {
                if (c && typeof c === 'object' && ('label' in c || 'value' in c)) {
                    return { label: String(c.label ?? c.value ?? ''), value: String(c.value ?? c.label ?? '') };
                }
                return { label: String(c), value: String(c) };
            });
        }
        if (typeof choices === 'object') {
            return Object.keys(choices).map(function (k) {
                return { label: String(choices[k]), value: String(k) };
            });
        }
        if (typeof choices === 'string') {
            var lines = choices.split(/\n/).filter(Boolean);
            return lines.map(function (line) {
                var idx = line.indexOf(':');
                if (idx !== -1) {
                    return { label: line.slice(idx + 1).trim(), value: line.slice(0, idx).trim() };
                }
                return { label: line.trim(), value: line.trim() };
            });
        }
        return [];
    }

    /**
     * Build base props from field options (label, instructions, required, placeholder, default, etc.).
     * @param {object} fieldOptions - Field definition from schema
     * @returns {object} Base props for any component
     */
    function basePropsFromOptions(fieldOptions) {
        var o = fieldOptions || {};
        var props = {
            placeholder: o.placeholder ?? '',
            disabled: !!o.disabled,
            readonly: !!o.readonly,
            required: !!o.required,
            invalid: !!o.invalid,
        };
        if (o.inputClass) props.class = o.inputClass;
        if (o.wrapper_class) props.wrapperClass = o.wrapper_class;
        return props;
    }

    /**
     * Resolve PrimeVue component and full config for a given Modular CMS field type and options.
     * @param {string} fieldType - e.g. 'text', 'wysiwyg', 'select', 'relationship'
     * @param {object} fieldOptions - Full field definition: label, name, key, instructions, required, default_value, placeholder, choices, min, max, step, post_type, taxonomy, toolbar, tabs, etc.
     * @param {object} styledOptions - Optional overrides: { class, style, theme, cssClass }
     * @returns {object} { component, props, events, slots, styledOptions, isUtility, isLayout, options (for selects) }
     */
    function resolveFieldComponent(fieldType, fieldOptions, styledOptions) {
        var type = (fieldType || 'text').toLowerCase();
        var o = fieldOptions || {};
        var styled = styledOptions || {};
        var base = basePropsFromOptions(o);
        var label = o.label ?? o.name ?? '';
        var name = o.name ?? o.key ?? '';
        var instructions = o.instructions ?? '';
        var defaultVal = o.default_value ?? o.default;
        var required = !!o.required;

        var result = {
            component: null,
            props: {},
            events: { 'update:modelValue': true, change: true, focus: true, blur: true },
            slots: {},
            styledOptions: Object.assign({}, styled),
            isUtility: UTILITY_TYPES.indexOf(type) !== -1,
            isLayout: LAYOUT_TYPES.indexOf(type) !== -1,
            options: [],
            label: label,
            name: name,
            instructions: instructions,
            required: required,
            default: defaultVal,
        };

        result.styledOptions.class = result.styledOptions.class || styled.class || '';
        result.styledOptions.style = result.styledOptions.style || styled.style || {};
        result.styledOptions.wrapperClass = result.styledOptions.wrapperClass || styled.wrapperClass || '';

        switch (type) {
            case 'text':
            case 'search':
            case 'embed':
                result.component = 'InputText';
                result.props = Object.assign({}, base, {
                    placeholder: o.placeholder || (type === 'search' ? 'Search' : type === 'embed' ? 'URL' : ''),
                    maxlength: o.maxlength ? parseInt(o.maxlength, 10) : undefined,
                });
                break;

            case 'email':
                result.component = 'InputText';
                result.props = Object.assign({}, base, { placeholder: o.placeholder || 'email@example.com' });
                break;

            case 'url':
                result.component = 'InputText';
                result.props = Object.assign({}, base, { placeholder: o.placeholder || 'https://' });
                break;

            case 'textarea':
                result.component = 'Textarea';
                result.props = Object.assign({}, base, {
                    rows: o.rows ? parseInt(o.rows, 10) : 4,
                    placeholder: o.placeholder || '',
                    maxlength: o.maxlength ? parseInt(o.maxlength, 10) : undefined,
                });
                break;

            case 'wysiwyg':
                result.component = 'WysiwygField';
                result.props = Object.assign({}, base, {
                    placeholder: o.placeholder || 'Write content...',
                    editorMode: (o.toolbar === 'basic' || o.editor_type === 'basic') ? 'basic' : 'full',
                });
                if (o.readonly != null) result.props.readonly = !!o.readonly;
                if (o.disabled != null) result.props.disabled = !!o.disabled;
                break;

            case 'code':
            case 'markdown':
                result.component = 'Textarea';
                result.props = Object.assign({}, base, {
                    rows: o.rows ? parseInt(o.rows, 10) : 10,
                    placeholder: o.placeholder || '',
                    class: (base.class || '') + ' p-monospace',
                });
                break;

            case 'number':
                result.component = 'InputNumber';
                result.props = Object.assign({}, base, {
                    min: o.min != null ? parseFloat(o.min) : undefined,
                    max: o.max != null ? parseFloat(o.max) : undefined,
                    step: o.step != null ? parseFloat(o.step) : 1,
                    minFractionDigits: o.min_fraction_digits,
                    maxFractionDigits: o.max_fraction_digits,
                    showButtons: o.show_buttons !== false,
                });
                break;

            case 'range':
                result.component = 'Slider';
                result.props = Object.assign({}, base, {
                    min: o.min != null ? parseFloat(o.min) : 0,
                    max: o.max != null ? parseFloat(o.max) : 100,
                    step: o.step != null ? parseFloat(o.step) : 1,
                });
                break;

            case 'password':
                result.component = 'Password';
                result.props = Object.assign({}, base, {
                    placeholder: o.placeholder || '',
                    feedback: o.feedback !== false,
                    toggleMask: true,
                });
                break;

            case 'hidden':
                result.component = 'Hidden';
                result.props = { value: defaultVal };
                break;

            case 'select':
                result.options = normalizeChoices(o.choices);
                result.component = 'Dropdown';
                result.props = Object.assign({}, base, {
                    options: result.options,
                    optionLabel: 'label',
                    optionValue: 'value',
                    placeholder: o.placeholder || 'Select',
                    showClear: !!o.allow_null,
                    filter: !!o.searchable,
                    filterPlaceholder: o.searchable ? 'Search' : undefined,
                });
                if (o.multiple) {
                    result.component = 'MultiSelect';
                    result.props = Object.assign(result.props, {
                        display: o.display || 'chip',
                        showClear: true,
                        maxSelectedLabels: o.max_selected_labels || 3,
                    });
                }
                break;

            case 'radio':
                result.options = normalizeChoices(o.choices);
                result.component = 'SelectButton';
                result.props = Object.assign({}, base, {
                    options: result.options,
                    optionLabel: 'label',
                    optionValue: 'value',
                    allowEmpty: !!o.allow_null,
                });
                break;

            case 'button_group':
                result.options = normalizeChoices(o.choices);
                result.component = 'SelectButton';
                result.props = Object.assign({}, base, {
                    options: result.options,
                    optionLabel: 'label',
                    optionValue: 'value',
                    allowEmpty: !!o.allow_null,
                });
                break;

            case 'checkbox':
                if (o.choices && (Array.isArray(o.choices) ? o.choices.length > 0 : Object.keys(o.choices || {}).length > 0)) {
                    result.options = normalizeChoices(o.choices);
                    result.component = 'MultiSelect';
                    result.props = Object.assign({}, base, {
                        options: result.options,
                        optionLabel: 'label',
                        optionValue: 'value',
                        display: 'checkbox',
                    });
                } else {
                    result.component = 'Checkbox';
                    result.props = Object.assign({}, base, { binary: true, inputId: 'field-' + (name || 'cb').replace(/[^a-z0-9_-]/gi, '-') });
                }
                break;

            case 'true_false':
                result.component = (o.ui && o.ui.component === 'ToggleSwitch') ? 'ToggleSwitch' : 'Checkbox';
                result.props = Object.assign({}, base, {
                    binary: true,
                    inputId: 'field-' + (name || 'tf').replace(/[^a-z0-9_-]/gi, '-'),
                });
                if (result.component === 'ToggleSwitch') {
                    result.props.inputId = result.props.inputId || 'field-' + (name || 'tf').replace(/[^a-z0-9_-]/gi, '-');
                }
                break;

            case 'date_picker':
                result.component = 'Calendar';
                result.props = Object.assign({}, base, {
                    dateFormat: 'yy-mm-dd',
                    showTime: false,
                    timeOnly: false,
                    showButtonBar: true,
                    hourFormat: o.hour_format || '24',
                });
                break;

            case 'time_picker':
                result.component = 'Calendar';
                result.props = Object.assign({}, base, {
                    timeOnly: true,
                    dateFormat: 'HH:mm',
                    hourFormat: o.hour_format || '24',
                    showButtonBar: true,
                });
                break;

            case 'date_time_picker':
                result.component = 'Calendar';
                result.props = Object.assign({}, base, {
                    showTime: true,
                    dateFormat: 'yy-mm-dd HH:mm',
                    hourFormat: o.hour_format || '24',
                    showButtonBar: true,
                });
                break;

            case 'post_object':
                result.component = 'Dropdown';
                result.props = Object.assign({}, base, {
                    options: o.options || [], // API-driven; filled by app
                    optionLabel: 'label',
                    optionValue: 'value',
                    placeholder: o.placeholder || 'Select a post',
                    showClear: !!o.allow_null,
                    filter: true,
                    filterPlaceholder: 'Search',
                });
                result.options = o.options || [];
                break;

            case 'relationship':
            case 'relation':
                result.component = 'PickList';
                result.props = Object.assign({}, base, {
                    dataKey: 'value',
                    breakpoint: '1400px',
                    listStyle: { maxHeight: '16rem' },
                });
                result.options = o.options || [];
                result.pickListValue = true; // app converts modelValue (ids) <-> [source, target]
                break;

            case 'user':
                result.component = 'Dropdown';
                result.props = Object.assign({}, base, {
                    options: o.options || [],
                    optionLabel: 'label',
                    optionValue: 'value',
                    placeholder: o.placeholder || 'Select user',
                    showClear: true,
                    filter: true,
                });
                result.options = o.options || [];
                break;

            case 'taxonomy':
                result.component = 'TreeSelect';
                result.props = Object.assign({}, base, {
                    options: o.options || [],
                    placeholder: o.placeholder || 'Select',
                    selectionMode: o.multiple !== false ? 'multiple' : 'single',
                    filter: true,
                    filterPlaceholder: 'Search',
                });
                result.options = o.options || [];
                break;

            case 'image':
            case 'file':
                result.component = 'FileUpload';
                result.props = Object.assign({}, base, {
                    mode: 'basic',
                    url: (window.MODULAR_BASE_URL || '') + '/admin/media/upload-handler.php?_csrf_token=' + (window.MODULAR_CSRF_TOKEN || ''),
                    accept: type === 'image' ? 'image/*' : (o.accept || '*/*'),
                    multiple: o.multiple || false,
                    chooseLabel: o.choose_label || 'Choose',
                    uploadLabel: o.upload_label || 'Upload',
                    cancelLabel: o.cancel_label || 'Cancel',
                    auto: true,
                });
                break;

            case 'gallery':
                result.component = 'FileUpload';
                result.props = Object.assign({}, base, {
                    mode: 'basic',
                    url: (window.MODULAR_BASE_URL || '') + '/admin/media/upload-handler.php?_csrf_token=' + (window.MODULAR_CSRF_TOKEN || ''),
                    accept: 'image/*',
                    multiple: true,
                    chooseLabel: o.choose_label || 'Add Images',
                    uploadLabel: o.upload_label || 'Upload',
                    cancelLabel: o.cancel_label || 'Cancel',
                    auto: true,
                });
                break;

            case 'carousel':
                result.component = 'Carousel';
                result.props = Object.assign({}, base, {
                    numVisible: o.numVisible != null ? parseInt(o.numVisible, 10) : 3,
                    numScroll: o.numScroll != null ? parseInt(o.numScroll, 10) : 1,
                });
                break;

            case 'image_compare':
                result.component = 'ImageCompare';
                result.props = Object.assign({}, base, {
                    leftImageSrc: o.leftImageSrc || o.left || '',
                    rightImageSrc: o.rightImageSrc || o.right || '',
                });
                break;

            case 'audio':
                result.component = 'FileUpload';
                result.props = Object.assign({}, base, {
                    mode: 'basic',
                    url: (window.MODULAR_BASE_URL || '') + '/admin/media/upload-handler.php?_csrf_token=' + (window.MODULAR_CSRF_TOKEN || ''),
                    accept: 'audio/*',
                    multiple: false,
                    chooseLabel: o.choose_label || 'Choose Audio',
                    auto: true,
                });
                break;

            case 'video':
                result.component = 'FileUpload';
                result.props = Object.assign({}, base, {
                    mode: 'basic',
                    url: (window.MODULAR_BASE_URL || '') + '/admin/media/upload-handler.php?_csrf_token=' + (window.MODULAR_CSRF_TOKEN || ''),
                    accept: 'video/*',
                    multiple: false,
                    chooseLabel: o.choose_label || 'Choose Video',
                    auto: true,
                });
                break;

            case 'message':
                result.component = 'Message';
                result.props = { severity: o.severity || 'info' };
                result.slots = { default: label || instructions };
                break;

            case 'accordion':
                result.component = 'Accordion';
                result.props = { multiple: !!o.multiple };
                break;

            case 'tab':
                result.component = 'Tab';
                result.props = { header: label, value: o.value || name };
                break;

            case 'card':
                result.component = 'Card';
                result.props = { title: label, subtitle: o.subtitle || '' };
                break;

            case 'chart':
                result.component = 'Chart';
                result.props = Object.assign({}, base, {
                    type: o.chartType || 'bar',
                    data: o.data || {},
                    options: o.options || {},
                });
                break;

            case 'divider':
                result.component = 'Divider';
                result.props = { type: o.type || 'solid' };
                break;

            case 'rating':
                result.component = 'Rating';
                result.props = Object.assign({}, base, {
                    stars: o.stars ? parseInt(o.stars, 10) : 5,
                    cancel: o.cancel !== false,
                    readonly: !!o.readonly,
                });
                break;

            case 'color':
            case 'color_picker':
                result.component = 'ColorPicker';
                result.props = Object.assign({}, base, {
                    format: (o.format || 'hex').toLowerCase(),
                    inline: !!o.inline,
                });
                break;

            case 'listbox':
                result.options = normalizeChoices(o.choices);
                result.component = 'Listbox';
                result.props = Object.assign({}, base, {
                    options: result.options,
                    optionLabel: 'label',
                    optionValue: 'value',
                    filter: !!o.filter,
                    multiple: !!o.multiple,
                });
                break;

            case 'autocomplete':
                result.component = 'AutoComplete';
                result.props = Object.assign({}, base, {
                    suggestions: o.suggestions || o.options || [],
                    dropdown: !!o.dropdown,
                    minLength: o.minLength != null ? parseInt(o.minLength, 10) : 1,
                    placeholder: o.placeholder || 'Type to search',
                    forceSelection: !!o.forceSelection,
                });
                result.options = o.suggestions || o.options || [];
                break;

            case 'chips':
                result.component = 'Chips';
                result.props = Object.assign({}, base, {
                    placeholder: o.placeholder || 'Add item',
                    separator: o.separator || ',',
                    max: o.max ? parseInt(o.max, 10) : undefined,
                });
                break;

            case 'group':
            case 'repeater':
            case 'flexible_content':
                result.component = type === 'group' ? 'Group' : type === 'repeater' ? 'Repeater' : 'FlexibleContent';
                result.props = { subFields: o.sub_fields || [], layouts: o.layouts || [] };
                break;

            default:
                result.component = 'InputText';
                result.props = Object.assign({}, base, { placeholder: o.placeholder || '' });
        }

        result.tag = getComponentTag(result.component);
        return result;
    }

    function isUtilityType(type) {
        return UTILITY_TYPES.indexOf((type || '').toLowerCase()) !== -1;
    }

    function isLayoutType(type) {
        return LAYOUT_TYPES.indexOf((type || '').toLowerCase()) !== -1;
    }

    /** PrimeVue component name to tag name (kebab-case for template). */
    var COMPONENT_TAG_MAP = {
        InputText: 'p-inputtext', Textarea: 'p-textarea', WysiwygField: 'wysiwyg-field', InputNumber: 'p-inputnumber',
        Slider: 'p-slider', Password: 'p-password', Dropdown: 'p-dropdown', MultiSelect: 'p-multiselect',
        Calendar: 'p-calendar', Checkbox: 'p-checkbox', RadioButton: 'p-radiobutton', SelectButton: 'p-selectbutton',
        FileUpload: 'p-fileupload', TreeSelect: 'p-treeselect', Message: 'p-message', Accordion: 'p-accordion',
        Divider: 'p-divider', Hidden: 'input', Group: 'div', Repeater: 'div', FlexibleContent: 'div',
        Tab: 'div', ToggleSwitch: 'p-toggleswitch', Listbox: 'p-listbox', AutoComplete: 'p-autocomplete',
        Rating: 'p-rating', ColorPicker: 'p-colorpicker', Chips: 'p-chips',
        PickList: 'p-picklist', Galleria: 'p-galleria', Carousel: 'p-carousel', ImageCompare: 'p-imagecompare',
        Card: 'p-card', Chart: 'p-chart',
    };

    function getComponentTag(componentName) {
        return COMPONENT_TAG_MAP[componentName] || 'p-inputtext';
    }

    global.ModularFieldRenderer = {
        resolveFieldComponent: resolveFieldComponent,
        normalizeChoices: normalizeChoices,
        isUtilityType: isUtilityType,
        isLayoutType: isLayoutType,
        UTILITY_TYPES: UTILITY_TYPES,
        LAYOUT_TYPES: LAYOUT_TYPES,
        getComponentTag: getComponentTag,
        COMPONENT_TAG_MAP: COMPONENT_TAG_MAP,
    };
})(typeof window !== 'undefined' ? window : this);
