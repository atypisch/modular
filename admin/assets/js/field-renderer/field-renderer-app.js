/**
 * Vue 3 + PrimeVue field renderer app for Modular CMS.
 * Renders schema-driven forms using resolveFieldComponent(); syncs to field_data_json on submit.
 * Requires: Vue 3, PrimeVue (UMD), resolveFieldComponent.js, Quill (for Editor).
 */
(function () {
    'use strict';

    if (!window.Vue || !window.PrimeVue || !window.ModularFieldRenderer) return;

    var Vue = window.Vue;
    var PV = window.PrimeVue;
    var resolveFieldComponent = window.ModularFieldRenderer.resolveFieldComponent;
    var isUtilityType = window.ModularFieldRenderer.isUtilityType;
    var isLayoutType = window.ModularFieldRenderer.isLayoutType;

    var createApp = Vue.createApp;
    var ref = Vue.ref;
    var reactive = Vue.reactive;
    var computed = Vue.computed;
    var watch = Vue.watch;
    var h = Vue.h;

    var PrimeUIX = window.PrimeUIX;
    var pvConfig = { ripple: true };
    if (PrimeUIX && PrimeUIX.Themes && PrimeUIX.Themes.Aura) {
        pvConfig.theme = { preset: PrimeUIX.Themes.Aura };
    }

    var COMPONENT_REGISTRY = {
        'p-inputtext': PV.InputText,
        'p-textarea': PV.Textarea,
        'p-editor': PV.Editor,
        'p-inputnumber': PV.InputNumber,
        'p-slider': PV.Slider,
        'p-password': PV.Password,
        'p-dropdown': PV.Dropdown,
        'p-multiselect': PV.MultiSelect,
        'p-calendar': PV.Calendar,
        'p-checkbox': PV.Checkbox,
        'p-radiobutton': PV.RadioButton,
        'p-selectbutton': PV.SelectButton,
        'p-fileupload': PV.FileUpload,
        'p-treeselect': PV.TreeSelect,
        'p-message': PV.Message,
        'p-accordion': PV.Accordion,
        'p-divider': PV.Divider,
        'p-toggleswitch': PV.ToggleSwitch,
        'p-listbox': PV.Listbox,
        'p-autocomplete': PV.AutoComplete,
        'p-rating': PV.Rating,
        'p-colorpicker': PV.ColorPicker,
        'p-chips': PV.Chips,
        'p-picklist': PV.PickList,
        'p-galleria': PV.Galleria,
        'p-carousel': PV.Carousel,
        'p-imagecompare': PV.ImageCompare,
        'p-card': PV.Card,
        'p-chart': PV.Chart,
    };

    function registerAllComponents(app) {
        app.use(PV.Config, pvConfig);
        Object.keys(COMPONENT_REGISTRY).forEach(function (tag) {
            if (COMPONENT_REGISTRY[tag]) {
                app.component(tag, COMPONENT_REGISTRY[tag]);
            }
        });
    }

    /**
     * Normalize initial value for a field type (e.g. Calendar expects Date or string in format).
     */
    function defaultValueFor(field, resolved) {
        var type = (field.type || 'text').toLowerCase();
        var def = field.default_value ?? field.default;
        if (def !== undefined && def !== null) return def;
        if (resolved.props.multiple || type === 'relationship' || type === 'relation' || type === 'chips') return [];
        if (type === 'checkbox' || type === 'true_false') return false;
        if (type === 'number' || type === 'range' || type === 'rating') return null;
        if (type === 'gallery' || type === 'carousel' || type === 'chart') return type === 'chart' ? {} : [];
        return '';
    }

    /**
     * Single field renderer (Vue component via render function).
     */
    var FieldRenderer = {
        name: 'FieldRenderer',
        props: {
            field: { type: Object, required: true },
            modelValue: { type: [String, Number, Boolean, Object, Array, Date], default: undefined },
            optionsMap: { type: Object, default: function () { return {}; } },
        },
        emits: ['update:modelValue'],
        setup: function (props, context) {
            var resolved = computed(function () {
                var o = Object.assign({}, props.field);
                var name = o.name || o.key;
                if (props.optionsMap[name]) {
                    o.options = props.optionsMap[name];
                }
                return resolveFieldComponent(o.type, o);
            });

            var localValue = ref(props.modelValue);
            watch(function () { return props.modelValue; }, function (v) { localValue.value = v; }, { immediate: true });
            watch(localValue, function (v) { context.emit('update:modelValue', v); }, { deep: true });

            // PickList (relationship) uses [[source], [target]]; we store selected IDs
            var pickListModel = ref([[], []]);
            watch([localValue, resolved], function () {
                var r = resolved.value;
                if (r.component !== 'PickList' || !r.options) return;
                var opts = r.options || [];
                var ids = Array.isArray(localValue.value) ? localValue.value : [];
                var source = opts.filter(function (o) { return ids.indexOf(o.value) === -1; });
                var target = opts.filter(function (o) { return ids.indexOf(o.value) !== -1; });
                pickListModel.value = [source, target];
            }, { immediate: true, deep: true });

            return { resolved: resolved, localValue: localValue, pickListModel: pickListModel };
        },
        render: function (ctx) {
            var field = ctx.field;
            var resolved = ctx.resolved;
            var type = (field.type || 'text').toLowerCase();

            if (resolved.isUtility) {
                if (type === 'message') {
                    return h('div', { class: 'form-group form-group-utility form-group-message' }, [
                        h(PV.Message, { severity: resolved.props.severity || 'info' }, { default: function () { return resolved.label || resolved.instructions; } }),
                    ]);
                }
                if (type === 'divider') {
                    return h(PV.Divider, resolved.props);
                }
                if (type === 'accordion') {
                    return h('div', { class: 'form-group form-group-utility form-group-accordion' }, [
                        h('div', { class: 'field-accordion-header' }, [
                            h('span', { class: 'field-accordion-icon', 'aria-hidden': 'true' }, [ h('i', { class: 'fas fa-chevron-down' }) ]),
                            h('strong', { class: 'field-accordion-title' }, resolved.label),
                        ]),
                    ]);
                }
                if (type === 'tab') {
                    return h('div', { class: 'form-group form-group-utility form-group-tab' }, [
                        h('div', { class: 'field-tab-label' }, resolved.label),
                    ]);
                }
            }

            if (resolved.isLayout) {
                return h('div', {
                    class: 'form-group form-group-layout field-type-' + type,
                    'data-field-type': type,
                }, [
                    h('strong', { class: 'block margin-bottom' }, resolved.label || type),
                    h('p', { class: 'help-text' }, 'Nested and repeating fields (group/repeater/flexible) use the same schema; full nested Vue rendering can be added here.'),
                ]);
            }

            if (type === 'hidden') {
                return h('input', {
                    type: 'hidden',
                    name: 'field_data[' + (field.name || field.key) + ']',
                    value: ctx.localValue,
                });
            }

            var tag = resolved.tag;
            var component = COMPONENT_REGISTRY[tag];
            if (!component && tag !== 'input') {
                return h('div', { class: 'form-group' }, [
                    h('label', resolved.label),
                    h('p', { class: 'help-text' }, 'Unknown component: ' + resolved.component),
                ]);
            }

            var id = 'field-' + (field.name || field.key || '').replace(/[^a-z0-9_-]/gi, '-');
            var wrapperClass = 'form-group field-type-' + type + ' ' + (resolved.styledOptions.wrapperClass || '');
            if (resolved.props.invalid) wrapperClass += ' is-invalid';
            var wrapperStyle = resolved.styledOptions.style && typeof resolved.styledOptions.style === 'object' ? resolved.styledOptions.style : {};
            var labelAttrs = { for: id, class: resolved.required ? 'required' : '' };
            if (resolved.tooltip) {
                labelAttrs.title = resolved.tooltip;
                labelAttrs['aria-label'] = resolved.label + (resolved.tooltip ? ': ' + resolved.tooltip : '');
            }
            var labelEl = h('label', labelAttrs, [
                resolved.label,
                resolved.required ? h('span', { class: 'required-asterisk', 'aria-label': 'Required' }, '*') : null,
            ]);

            var inputClass = (resolved.props.class || '') + ' w-full ' + (resolved.styledOptions.class || '');
            var bindProps = Object.assign({}, resolved.props, {
                modelValue: tag === 'p-picklist' ? ctx.pickListModel : ctx.localValue,
                'onUpdate:modelValue': tag === 'p-picklist'
                    ? function (v) {
                        ctx.pickListModel.value = v;
                        ctx.localValue = (v && v[1]) ? v[1].map(function (x) { return x.value; }) : [];
                    }
                    : function (v) { ctx.localValue = v; },
                id: id,
                class: inputClass,
            });
            if (resolved.props.invalid) bindProps['aria-invalid'] = 'true';

            if (tag === 'p-calendar' && typeof bindProps.modelValue === 'string' && bindProps.modelValue) {
                bindProps.modelValue = bindProps.modelValue;
            }

            // PickList needs option slot for item label
            var inputEl;
            if (tag === 'p-picklist') {
                inputEl = h(component, bindProps, {
                    option: function (slotProps) {
                        return slotProps.option ? slotProps.option.label : '';
                    },
                });
            } else if (tag === 'input') {
                inputEl = h('input', { type: 'hidden', name: 'field_data[' + (field.name || field.key) + ']', value: ctx.localValue });
            } else {
                inputEl = h(component, bindProps);
            }

            var helpEl = resolved.instructions ? h('div', { class: 'help-text' }, resolved.instructions) : null;
            var validationEl = (resolved.validationMessage || field.validationMessage) ? h('div', { class: 'help-text text-danger', role: 'alert' }, resolved.validationMessage || field.validationMessage) : null;

            return h('div', {
                class: wrapperClass,
                style: wrapperStyle,
                'data-field-type': type,
            }, [ labelEl, inputEl, helpEl, validationEl ]);
        },
    };

    /**
     * Mount the field renderer app on the given element. Expects window.MODULAR_FIELD_SCHEMA and MODULAR_FIELD_DATA.
     */
    function mountFieldRendererApp(rootId, formId, fieldDataJsonInputId) {
        var root = document.getElementById(rootId);
        if (!root) return null;

        var schema = window.MODULAR_FIELD_SCHEMA || { fields: [] };
        var fields = schema.fields || [];
        var initialData = window.MODULAR_FIELD_DATA || {};
        var optionsMap = window.MODULAR_FIELD_OPTIONS || {};

        var fieldData = reactive({});
        fields.forEach(function (f) {
            var name = f.name || f.key;
            if (!name) return;
            if (isUtilityType(f.type)) return;
            if (isLayoutType(f.type)) {
                fieldData[name] = initialData[name] != null ? (Array.isArray(initialData[name]) ? initialData[name] : initialData[name]) : (f.type === 'repeater' ? [] : {});
                return;
            }
            var resolved = resolveFieldComponent(f.type, Object.assign({}, f, { options: optionsMap[name] || f.options }));
            var def = defaultValueFor(f, resolved);
            fieldData[name] = initialData[name] !== undefined && initialData[name] !== null ? initialData[name] : def;
        });

        var app = createApp({
            setup: function () {
                return {
                    fields: fields,
                    fieldData: fieldData,
                    optionsMap: optionsMap,
                };
            },
            render: function (ctx) {
                var nodes = [];
                ctx.fields.forEach(function (field, idx) {
                    var name = field.name || field.key;
                    if (!name && !isUtilityType(field.type)) return;
                    nodes.push(h(FieldRenderer, {
                        key: name || 'utility-' + idx,
                        field: field,
                        modelValue: name ? ctx.fieldData[name] : undefined,
                        'onUpdate:modelValue': name ? function (v) { ctx.fieldData[name] = v; } : undefined,
                        optionsMap: ctx.optionsMap,
                    }));
                });
                return h('div', { class: 'modular-field-renderer-root' }, nodes);
            },
        });

        app.component('FieldRenderer', FieldRenderer);
        registerAllComponents(app);
        app.mount(root);

        var form = formId ? document.getElementById(formId) : root.closest('form');
        var jsonInput = fieldDataJsonInputId ? document.getElementById(fieldDataJsonInputId) : null;
        if (form && jsonInput) {
            form.addEventListener('submit', function () {
                try {
                    var data = {};
                    for (var k in fieldData) {
                        if (Object.prototype.hasOwnProperty.call(fieldData, k)) {
                            data[k] = fieldData[k];
                        }
                    }
                    jsonInput.value = JSON.stringify(data);
                } catch (e) {
                    console.error('Field renderer: serialize error', e);
                }
            });
        }

        return { app: app, fieldData: fieldData };
    }

    function init() {
        var rootId = 'post-edit-fields-vue-app';
        var formId = 'post-edit-form';
        var jsonId = 'field_data_json';
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                window.ModularFieldRendererApp._instance = mountFieldRendererApp(rootId, formId, jsonId);
            });
        } else {
            window.ModularFieldRendererApp._instance = mountFieldRendererApp(rootId, formId, jsonId);
        }
    }

    window.ModularFieldRendererApp = {
        mount: mountFieldRendererApp,
        _instance: null,
        FieldRenderer: FieldRenderer,
        registerAllComponents: registerAllComponents,
        COMPONENT_REGISTRY: COMPONENT_REGISTRY,
    };

    if (window.MODULAR_FIELD_SCHEMA) {
        init();
    } else {
        document.addEventListener('modular-field-renderer-mount', function () {
            init();
        });
    }
})();
