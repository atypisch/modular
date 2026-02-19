/**
 * Vue 3 + PrimeVue field renderer app for Modular CMS.
 * Renders schema-driven forms using resolveFieldComponent(); syncs to field_data_json on submit.
 * Requires: Vue 3, PrimeVue (UMD), resolveFieldComponent.js, TinyMCE + WysiwygField (for WYSIWYG).
 */
(function () {
    'use strict';

    if (!window.Vue || !window.PrimeVue || !window.ModularFieldRenderer) {
        if (typeof console !== 'undefined') console.warn('Modular field renderer: Vue, PrimeVue or ModularFieldRenderer not loaded. Check script order.');
        return;
    }

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
    if (typeof window.ModularWysiwygField !== 'undefined') {
        COMPONENT_REGISTRY['wysiwyg-field'] = window.ModularWysiwygField;
    }

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
            instanceId: { type: String, default: '' },
            onRemove: { type: Function, default: null },
            onDuplicate: { type: Function, default: null },
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

            var relationshipSearch = ref('');
            var relationshipPostType = ref('');

            var isCollapsed = ref(false);
            var isEditingLabel = ref(false);
            var labelDraft = ref('');
            watch(resolved, function (r) {
                var baseLabel = (r && r.label) || '';
                if (!isEditingLabel.value) {
                    labelDraft.value = baseLabel;
                }
            }, { immediate: true });

            return {
                resolved: resolved,
                localValue: localValue,
                pickListModel: pickListModel,
                relationshipSearch: relationshipSearch,
                relationshipPostType: relationshipPostType,
                isCollapsed: isCollapsed,
                isEditingLabel: isEditingLabel,
                labelDraft: labelDraft
            };
        },
        render: function (ctx) {
            var field = ctx.field;
            var resolved = ctx.resolved;
            var type = (field.type || 'text').toLowerCase();
            var isRelationship = type === 'relationship' || type === 'relation';

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

            var displayLabel = ctx.labelDraft || resolved.label || '';

            var inputClass = (resolved.props.class || '') + ' w-full ' + (resolved.styledOptions.class || '');

            var picklistModelValue = ctx.pickListModel;
            if (tag === 'p-picklist') {
                var src = (picklistModelValue && picklistModelValue[0]) || [];
                var tgt = (picklistModelValue && picklistModelValue[1]) || [];
                var q = (ctx.relationshipSearch || '').toLowerCase();
                var activePt = ctx.relationshipPostType || '';
                var filteredSource = src.filter(function (opt) {
                    if (!opt) return false;
                    var label = String(opt.label || '').toLowerCase();
                    if (q && label.indexOf(q) === -1) return false;
                    if (activePt) {
                        return String(opt.label || '').indexOf('(' + activePt + ')') !== -1;
                    }
                    return true;
                });
                picklistModelValue = [filteredSource, tgt];
            }

            var bindProps = Object.assign({}, resolved.props, {
                modelValue: tag === 'p-picklist' ? picklistModelValue : (tag === 'wysiwyg-field' ? String(ctx.localValue || '') : ctx.localValue),
                'onUpdate:modelValue': tag === 'p-picklist'
                    ? function (v) {
                        ctx.pickListModel = v;
                        ctx.localValue = (v && v[1]) ? v[1].map(function (x) { return x.value; }) : [];
                    }
                    : function (v) { 
                        ctx.localValue = v;
                    },
                id: id,
                class: inputClass,
            });
            if (resolved.props.invalid) bindProps['aria-invalid'] = 'true';

            if (tag === 'wysiwyg-field') {
                bindProps.field = field;
            }

            if (tag === 'p-calendar' && typeof bindProps.modelValue === 'string' && bindProps.modelValue) {
                bindProps.modelValue = bindProps.modelValue;
            }

            // FileUpload with basic mode - handle auto-upload response
            if (tag === 'p-fileupload') {
                bindProps.onUpload = function(event) {
                    var xhr = event.xhr;
                    if (xhr && xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                var currentValue = ctx.localValue;
                                if (resolved.props.multiple) {
                                    if (!Array.isArray(currentValue)) currentValue = [];
                                    currentValue.push(response.media_id);
                                } else {
                                    currentValue = response.media_id;
                                }
                                ctx.localValue = currentValue;
                            } else {
                                console.error('Upload failed:', response.error);
                            }
                        } catch (e) {
                            console.error('Upload response parse error:', e);
                        }
                    }
                };
            }

            // FileUpload with customUpload needs custom handler
            if (tag === 'p-fileupload' && resolved.props.customUpload) {
                bindProps.uploadHandler = function(options) {
                    var files = options.files;
                    if (!files || files.length === 0) return;
                    var file = files[0];
                    var formData = new FormData();
                    formData.append('file', file);
                    formData.append('title', file.name);
                    var csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
                    if (csrfToken) formData.append('csrf_token', csrfToken);
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', window.MODULAR_BASE_URL + '/admin/media/upload-handler.php', true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    var currentValue = ctx.localValue;
                                    if (resolved.props.multiple) {
                                        if (!Array.isArray(currentValue)) currentValue = [];
                                        currentValue.push(response.media_id);
                                    } else {
                                        currentValue = response.media_id;
                                    }
                                    ctx.localValue = currentValue;
                                } else {
                                    alert('Upload failed: ' + (response.error || 'Unknown error'));
                                }
                            } catch (e) {
                                alert('Upload failed: Invalid response');
                            }
                        } else {
                            alert('Upload failed: Server error');
                        }
                    };
                    xhr.onerror = function() {
                        alert('Upload failed: Network error');
                    };
                    xhr.send(formData);
                };
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

            var extras = [];
            if (isRelationship) {
                var showSearch = field.show_search !== false;
                var showPostTypeFilter = field.show_post_type_filter !== false && Array.isArray(field.post_type) && field.post_type.length > 1;
                if (showSearch || showPostTypeFilter) {
                    var toolbarChildren = [];
                    if (showSearch) {
                        toolbarChildren.push(h('div', { class: 'relationship-search-wrap' }, [
                            h('i', { class: 'fas fa-search relationship-search-icon', 'aria-hidden': 'true' }),
                            h('input', {
                                type: 'search',
                                class: 'relationship-search-input',
                                placeholder: 'Search related content…',
                                value: ctx.relationshipSearch,
                                onInput: function (e) { ctx.relationshipSearch = e.target.value || ''; }
                            })
                        ]));
                    }
                    if (showPostTypeFilter) {
                        var opts = (field.post_type || []).slice();
                        toolbarChildren.push(h('div', { class: 'relationship-type-filter-wrap' }, [
                            h('select', {
                                class: 'relationship-type-select',
                                value: ctx.relationshipPostType,
                                onChange: function (e) { ctx.relationshipPostType = e.target.value || ''; }
                            }, [
                                h('option', { value: '' }, 'All types')
                            ].concat(opts.map(function (slug) {
                                return h('option', { value: slug }, slug);
                            })))
                        ]));
                    }
                    extras.push(h('div', { class: 'relationship-toolbar' }, toolbarChildren));
                }
            }

            var titleNode;
            if (ctx.isEditingLabel) {
                titleNode = h('input', {
                    type: 'text',
                    id: (ctx.instanceId || id) + '-label-input',
                    class: 'field-toolbar-title-input',
                    value: displayLabel,
                    onInput: function (e) { ctx.labelDraft = e.target.value || ''; },
                    onBlur: function () { ctx.isEditingLabel = false; },
                    onKeydown: function (e) {
                        if (e.key === 'Enter') ctx.isEditingLabel = false;
                        if (e.key === 'Escape') { ctx.labelDraft = resolved.value.label || ''; ctx.isEditingLabel = false; }
                    }
                });
            } else {
                titleNode = h('span', { class: 'field-toolbar-title-text' }, displayLabel);
            }

            var editBtn = h('button', {
                type: 'button',
                class: 'field-toolbar-btn field-toolbar-edit',
                title: 'Rename field',
                'aria-label': 'Rename field',
                onClick: function (e) {
                    e.stopPropagation();
                    ctx.isEditingLabel = !ctx.isEditingLabel;
                    if (ctx.isEditingLabel) {
                        setTimeout(function () {
                            var el = document.getElementById((ctx.instanceId || id) + '-label-input');
                            if (el) el.focus();
                        }, 50);
                    }
                }
            }, [ h('i', { class: 'fas fa-pen' }) ]);

            var rightButtons = [];
            if (ctx.onDuplicate && typeof ctx.onDuplicate === 'function') {
                rightButtons.push(h('button', {
                    type: 'button',
                    class: 'field-toolbar-btn field-toolbar-duplicate',
                    title: 'Duplicate',
                    'aria-label': 'Duplicate',
                    onClick: function (e) { e.stopPropagation(); ctx.onDuplicate(); },
                }, [ h('i', { class: 'fas fa-copy' }) ]));
            }
            if (ctx.onRemove && typeof ctx.onRemove === 'function') {
                rightButtons.push(h('button', {
                    type: 'button',
                    class: 'field-toolbar-btn field-toolbar-remove',
                    title: 'Remove',
                    'aria-label': 'Remove',
                    onClick: function (e) { e.stopPropagation(); ctx.onRemove(); },
                }, [ h('i', { class: 'fas fa-trash-alt' }) ]));
            }
            rightButtons.push(h('button', {
                type: 'button',
                class: 'field-toolbar-btn field-toolbar-minimize',
                title: ctx.isCollapsed ? 'Expand field' : 'Collapse field',
                'aria-label': ctx.isCollapsed ? 'Expand field' : 'Collapse field',
                onClick: function (e) {
                    e.stopPropagation();
                    ctx.isCollapsed = !ctx.isCollapsed;
                }
            }, [ h('i', { class: ctx.isCollapsed ? 'fas fa-chevron-down' : 'fas fa-chevron-up' }) ]));

            var toolbar = h('div', {
                class: 'field-toolbar',
                onDblclick: function (e) {
                    e.stopPropagation();
                    ctx.isCollapsed = !ctx.isCollapsed;
                },
                'aria-expanded': ctx.isCollapsed ? 'false' : 'true'
            }, [
                h('div', { class: 'field-toolbar-left field-toolbar-handle' }, [ titleNode, editBtn ]),
                h('div', { class: 'field-toolbar-right' }, rightButtons )
            ]);

            var contentChildren = [].concat(extras, [inputEl, helpEl, validationEl ]);
            var content = h('div', {
                class: 'field-toolbar-content',
                style: ctx.isCollapsed ? { display: 'none' } : {},
                'data-collapsed': ctx.isCollapsed ? '1' : '0'
            }, contentChildren);

            var inner = h('div', { class: 'field-inner-wrap' }, [ toolbar, content ]);

            var rootAttrs = {
                class: wrapperClass + (ctx.isCollapsed ? ' field-collapsed' : ''),
                style: wrapperStyle,
                'data-field-type': type,
                'data-field-name': field.name || field.key || '',
            };
            if (ctx.instanceId) rootAttrs['data-field-id'] = ctx.instanceId;
            return h('div', rootAttrs, [ inner ]);
        },
    };

    /**
     * Mount the field renderer app on the given element. Expects window.MODULAR_FIELD_SCHEMA and MODULAR_FIELD_DATA.
     */
    function mountFieldRendererApp(rootId, formId, fieldDataJsonInputId) {
        var root = document.getElementById(rootId);
        if (!root) {
            if (typeof console !== 'undefined') console.warn('Modular field renderer: root element not found:', rootId);
            return null;
        }
        var area = root.closest('#post-edit-fields-area');
        var isFixedMode = !!(area && area.getAttribute('data-fixed') === '1');
        if (isFixedMode) {
            root.classList.add('modular-renderer-is-fixed');
        }

        function getJsonFromScript(id, fallback) {
            var el = document.getElementById(id);
            if (!el || !el.textContent) return fallback;
            try { return JSON.parse(el.textContent.trim()); } catch (e) { return fallback; }
        }
        var schema = window.MODULAR_FIELD_SCHEMA || getJsonFromScript('modular-field-schema-json', { fields: [] });
        var initialData = window.MODULAR_FIELD_DATA !== undefined ? window.MODULAR_FIELD_DATA : getJsonFromScript('modular-field-data-json', {});
        var initialLayout = window.MODULAR_FIELD_LAYOUT !== undefined ? window.MODULAR_FIELD_LAYOUT : getJsonFromScript('modular-field-layout-json', null);
        if (!schema || typeof schema !== 'object') schema = { fields: [] };
        var fields = schema.fields && Array.isArray(schema.fields) ? schema.fields : [];
        var optionsMap = window.MODULAR_FIELD_OPTIONS || {};

        // Map for quick lookup by field name
        var fieldsByName = {};
        fields.forEach(function (f) {
            var name = f.name || f.key;
            if (name) fieldsByName[name] = f;
        });

        // Simpel model: topSection + layoutBlocks; layoutOrder = ['top', blockId, ...] bepaalt volgorde (voor slepen).
        var topSection = ref([]);
        var layoutBlocks = ref([]);
        var layoutOrder = ref([]);
        var instanceCounter = 0;
        function makeInstanceId(name) {
            instanceCounter += 1;
            return 'fi-' + Date.now() + '-' + instanceCounter + '-' + (name || 'field');
        }
        function makeBlockId() {
            return 'block-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
        }
        function normalizeInst(item) {
            if (item && typeof item === 'object' && item.id && item.name) {
                return { id: String(item.id), name: item.name };
            }
            var name = String(item);
            return { id: makeInstanceId(name), name: name };
        }
        function filterValidInst(inst) {
            return !!(inst && fieldsByName[inst.name]);
        }

        if (initialLayout && typeof initialLayout === 'object' && !Array.isArray(initialLayout)) {
            if (Array.isArray(initialLayout.top)) {
                topSection.value = (initialLayout.top || []).map(normalizeInst).filter(filterValidInst);
                var rawBlocks = initialLayout.blocks;
                if (!Array.isArray(rawBlocks)) rawBlocks = [];
                layoutBlocks.value = rawBlocks.map(function (b) {
                    var numCols = b.numCols === 2 || b.numCols === 3 ? b.numCols : 1;
                    var cols = b.columns || [ [], [], [] ];
                    var defaultLabel = numCols === 1 ? 'Layout 1 Column' : (numCols === 2 ? 'Layout 2 Columns' : 'Layout 3 Columns');
                    return {
                        id: b.id || makeBlockId(),
                        numCols: numCols,
                        label: typeof b.label === 'string' && b.label.trim() ? b.label.trim() : defaultLabel,
                        collapsed: !!b.collapsed,
                        columns: [
                            (cols[0] || []).map(normalizeInst).filter(filterValidInst),
                            (cols[1] || []).map(normalizeInst).filter(filterValidInst),
                            (cols[2] || []).map(normalizeInst).filter(filterValidInst)
                        ]
                    };
                });
            } else if (initialLayout.type && Array.isArray(initialLayout.columns)) {
                topSection.value = (initialLayout.columns[0] || []).map(normalizeInst).filter(filterValidInst);
                layoutBlocks.value = [];
            }
        }
        if (Array.isArray(initialLayout) && initialLayout.length) {
            var list = initialLayout.map(normalizeInst).filter(filterValidInst);
            topSection.value = [];
            layoutBlocks.value = [{ id: makeBlockId(), numCols: 1, columns: [ list, [], [] ] }];
        }

        // Ensure we always start with at least one 1-column layout block.
        // If we received legacy data in topSection, migrate it into the first block.
        if (layoutBlocks.value.length === 0 && topSection.value.length > 0) {
            layoutBlocks.value = [{
                id: makeBlockId(),
                numCols: 1,
                label: 'Layout 1 Column',
                collapsed: false,
                columns: [ (topSection.value || []).slice(), [], [] ]
            }];
            topSection.value = [];
        } else if (layoutBlocks.value.length === 0) {
            // No blocks at all: build a default 1-column block with all fields.
            var defaultInstances = fields
                .map(function (f) { return f.name || f.key; })
                .filter(function (n) { return !!n; })
                .map(function (name) { return { id: makeInstanceId(name), name: name }; });
            layoutBlocks.value = [{
                id: makeBlockId(),
                numCols: 1,
                label: 'Layout 1 Column',
                collapsed: false,
                columns: [ defaultInstances.length ? defaultInstances : [], [], [] ]
            }];
        } else {
            // Migration guard: if we have blocks from DB but *all* columns are empty,
            // assume a legacy state and seed the first block with all fields so that
            // Layout 1 Column shows its components instead of staying visually empty.
            var hasAnyInstancesInBlocks = (layoutBlocks.value || []).some(function (b) {
                return (b.columns || []).some(function (col) { return (col || []).length > 0; });
            });
            if (!hasAnyInstancesInBlocks) {
                var defaultInstances2 = fields
                    .map(function (f) { return f.name || f.key; })
                    .filter(function (n) { return !!n; })
                    .map(function (name) { return { id: makeInstanceId(name), name: name }; });
                if (layoutBlocks.value.length > 0) {
                    layoutBlocks.value[0] = Object.assign({}, layoutBlocks.value[0], {
                        numCols: 1,
                        columns: [ defaultInstances2.length ? defaultInstances2 : [], [], [] ]
                    });
                }
            }
        }
        // Build initial layout order; if server provided an explicit order (including 'top'),
        // respect that, otherwise fall back to ['top', ...blockIds].
        var blockIds = (layoutBlocks.value || []).map(function (b) { return b.id; });
        var initialOrder = null;
        if (initialLayout && typeof initialLayout === 'object' && !Array.isArray(initialLayout) && Array.isArray(initialLayout.order)) {
            var seen = {};
            initialOrder = initialLayout.order.filter(function (id) {
                if (!id) return false;
                if (id !== 'top' && blockIds.indexOf(id) === -1) return false;
                if (seen[id]) return false;
                seen[id] = true;
                return true;
            });
            // If there is content in the top section but 'top' is missing, ensure it is present.
            if ((topSection.value || []).length && initialOrder.indexOf('top') === -1) {
                initialOrder.unshift('top');
            }
        }
        if (!initialOrder || !initialOrder.length) {
            initialOrder = ['top'].concat(blockIds);
        } else {
            // Append any blocks that were not in the saved order.
            blockIds.forEach(function (id) {
                if (initialOrder.indexOf(id) === -1) initialOrder.push(id);
            });
        }
        layoutOrder.value = initialOrder;

        var instanceValues = reactive({});
        function seedInstanceValue(inst, valueFromData) {
            var fieldDef = fieldsByName[inst.name];
            if (!fieldDef || isUtilityType(fieldDef.type)) return;
            var resolved = resolveFieldComponent(fieldDef.type, Object.assign({}, fieldDef, { options: optionsMap[inst.name] || fieldDef.options }));
            var def = defaultValueFor(fieldDef, resolved);
            instanceValues[inst.id] = valueFromData !== undefined && valueFromData !== null ? valueFromData : def;
        }
        var perFieldIndex = {};
        function seedAllFromTopAndBlocks() {
            function valueFromInitialData(name, idx) {
                var existing = initialData[name];
                return Array.isArray(existing) ? existing[idx] : (idx === 0 ? existing : undefined);
            }
            (topSection.value || []).forEach(function (inst) {
                var name = inst.name;
                var idx = perFieldIndex[name] || 0;
                perFieldIndex[name] = idx + 1;
                seedInstanceValue(inst, valueFromInitialData(name, idx));
            });
            (layoutBlocks.value || []).forEach(function (block) {
                (block.columns || []).forEach(function (list) {
                    (list || []).forEach(function (inst) {
                        if (!inst || !inst.id || Object.prototype.hasOwnProperty.call(instanceValues, inst.id)) return;
                        var name = inst.name;
                        var idx = perFieldIndex[name] || 0;
                        perFieldIndex[name] = idx + 1;
                        seedInstanceValue(inst, valueFromInitialData(name, idx));
                    });
                });
            });
        }
        seedAllFromTopAndBlocks();

        var topSectionCollapsed = ref(false);
        var topSectionLabel = ref('Content');
        var topSectionEditingLabel = ref(false);
        var topSectionLabelDraft = ref('');
        function updateTopSectionCollapsed(collapsed) {
            topSectionCollapsed.value = !!collapsed;
        }
        function updateTopSectionLabel(label) {
            var s = typeof label === 'string' ? label.trim() : '';
            topSectionLabel.value = s || 'Content';
        }
        function setTopSectionLabelDraft(v) {
            topSectionLabelDraft.value = typeof v === 'string' ? v : '';
        }
        function setTopSectionEditingLabel(v) {
            topSectionEditingLabel.value = !!v;
        }
        var blockEditingId = ref(null);
        var blockLabelDraft = ref('');
        function updateBlockLabel(blockId, label) {
            var s = typeof label === 'string' ? label.trim() : '';
            layoutBlocks.value = layoutBlocks.value.map(function (b) {
                if (b.id === blockId) return Object.assign({}, b, { label: s || b.label });
                return b;
            });
        }
        function updateBlockCollapsed(blockId, collapsed) {
            layoutBlocks.value = layoutBlocks.value.map(function (b) {
                if (b.id === blockId) return Object.assign({}, b, { collapsed: !!collapsed });
                return b;
            });
        }
        function setBlockEditingId(id) {
            blockEditingId.value = id;
        }
        function setBlockLabelDraft(val) {
            blockLabelDraft.value = typeof val === 'string' ? val : '';
        }

        var app = createApp({
            setup: function () {
                return {
                    fields: fields,
                    optionsMap: optionsMap,
                    topSection: topSection,
                    topSectionCollapsed: topSectionCollapsed,
                    topSectionLabel: topSectionLabel,
                    topSectionEditingLabel: topSectionEditingLabel,
                    topSectionLabelDraft: topSectionLabelDraft,
                    updateTopSectionCollapsed: updateTopSectionCollapsed,
                    updateTopSectionLabel: updateTopSectionLabel,
                    setTopSectionLabelDraft: setTopSectionLabelDraft,
                    setTopSectionEditingLabel: setTopSectionEditingLabel,
                    layoutBlocks: layoutBlocks,
                    layoutOrder: layoutOrder,
                    instanceValues: instanceValues,
                    fieldsByName: fieldsByName,
                    blockEditingId: blockEditingId,
                    blockLabelDraft: blockLabelDraft,
                    updateBlockLabel: updateBlockLabel,
                    updateBlockCollapsed: updateBlockCollapsed,
                    setBlockEditingId: setBlockEditingId,
                    setBlockLabelDraft: setBlockLabelDraft,
                };
            },
            render: function (ctx) {
                var api = window.ModularFieldRendererApp;
                function unwrapRef(v) {
                    if (Array.isArray(v)) return v;
                    if (v && typeof v === 'object' && 'value' in v) {
                        var val = v.value;
                        return Array.isArray(val) ? val : [];
                    }
                    return [];
                }
                function unwrapRefBlocks(v) {
                    if (Array.isArray(v)) return v;
                    if (v && typeof v === 'object' && 'value' in v) return Array.isArray(v.value) ? v.value : [];
                    return [];
                }
                function renderFieldList(list) {
                    var arr = unwrapRef(list);
                    return arr.map(function (inst) {
                        if (!inst || !inst.name || !inst.id) return null;
                        var field = ctx.fieldsByName[inst.name];
                        if (!field) return null;
                        var val = ctx.instanceValues[inst.id];
                        return h(FieldRenderer, {
                            key: inst.id,
                            field: field,
                            modelValue: val,
                            'onUpdate:modelValue': function (v) { ctx.instanceValues[inst.id] = v; },
                            optionsMap: ctx.optionsMap,
                            instanceId: inst.id,
                            onRemove: api && api.requestRemoveInstance ? function () { api.requestRemoveInstance(inst.id); } : null,
                            onDuplicate: api && api.duplicateInstance ? function () { api.duplicateInstance(inst.id); } : null,
                        });
                    }).filter(Boolean);
                }

                var nodes = [];
                var order = unwrapRef(ctx.layoutOrder);
                var blocksForOrder = unwrapRefBlocks(ctx.layoutBlocks);
                if (!order || !order.length) order = ['top'].concat((blocksForOrder || []).map(function (b) { return b && b.id ? b.id : ''; })).filter(Boolean);

                var topList = unwrapRef(ctx.topSection);
                var topCollapsedVal = (ctx.topSectionCollapsed && typeof ctx.topSectionCollapsed.value !== 'undefined'
                    ? ctx.topSectionCollapsed.value
                    : ctx.topSectionCollapsed);
                var topCollapsed = !!topCollapsedVal;
                var topLabelVal = (ctx.topSectionLabel && typeof ctx.topSectionLabel.value !== 'undefined'
                    ? ctx.topSectionLabel.value
                    : ctx.topSectionLabel);
                var topLabel = (topLabelVal || 'Content');
                var topEditingVal = (ctx.topSectionEditingLabel && typeof ctx.topSectionEditingLabel.value !== 'undefined'
                    ? ctx.topSectionEditingLabel.value
                    : ctx.topSectionEditingLabel);
                var topEditingLabel = !!topEditingVal;
                var topDraftVal = (ctx.topSectionLabelDraft && typeof ctx.topSectionLabelDraft.value !== 'undefined'
                    ? ctx.topSectionLabelDraft.value
                    : ctx.topSectionLabelDraft);
                var topLabelDraft = (topDraftVal || topLabel);
                var topInputId = 'layout-top-edit-label';
                var topHeaderLeft = topEditingLabel
                    ? h('input', {
                        type: 'text',
                        id: topInputId,
                        class: 'layout-block-title-input',
                        value: topLabelDraft,
                        onInput: function (e) {
                            if (ctx.setTopSectionLabelDraft) ctx.setTopSectionLabelDraft(e.target.value || '');
                        },
                        onKeydown: function (e) {
                            if (e.key === 'Enter') {
                                if (ctx.updateTopSectionLabel) ctx.updateTopSectionLabel(topLabelDraft);
                                if (ctx.setTopSectionEditingLabel) ctx.setTopSectionEditingLabel(false);
                            }
                            if (e.key === 'Escape') {
                                if (ctx.setTopSectionEditingLabel) ctx.setTopSectionEditingLabel(false);
                                if (ctx.setTopSectionLabelDraft) ctx.setTopSectionLabelDraft(topLabel);
                            }
                        },
                        onBlur: function () {
                            if (ctx.updateTopSectionLabel) ctx.updateTopSectionLabel(topLabelDraft);
                            if (ctx.setTopSectionEditingLabel) ctx.setTopSectionEditingLabel(false);
                        }
                    })
                    : h('span', { class: 'layout-block-title-text' }, topLabel);
                var topSectionNode = topList.length ? h('div', { class: 'modular-top-section modular-top-drop' + (topCollapsed ? ' modular-top-section-collapsed' : ''), 'data-section': 'top', 'data-block-id': 'top' }, [
                    h('div', { class: 'modular-block-header layout-block-toolbar modular-top-block-header' }, [
                        h('div', { class: 'layout-block-toolbar-left layout-block-toolbar-handle' }, [
                            topHeaderLeft,
                            h('button', {
                                type: 'button',
                                class: 'layout-block-toolbar-btn',
                                title: 'Rename section',
                                'aria-label': 'Rename section',
                                onClick: function (e) {
                                    e.stopPropagation();
                                    if (topEditingLabel) {
                                        if (ctx.updateTopSectionLabel) ctx.updateTopSectionLabel(topLabelDraft);
                                        if (ctx.setTopSectionEditingLabel) ctx.setTopSectionEditingLabel(false);
                                    } else {
                                        if (ctx.setTopSectionLabelDraft) ctx.setTopSectionLabelDraft(topLabel);
                                        if (ctx.setTopSectionEditingLabel) ctx.setTopSectionEditingLabel(true);
                                        setTimeout(function () {
                                            var el = document.getElementById(topInputId);
                                            if (el) el.focus();
                                        }, 50);
                                    }
                                }
                            }, [ h('i', { class: 'fas fa-pen' }) ])
                        ]),
                        h('div', { class: 'layout-block-toolbar-right' }, [
                            api && api.duplicateTopSection ? h('button', {
                                type: 'button',
                                class: 'layout-block-toolbar-btn',
                                title: 'Duplicate section',
                                'aria-label': 'Duplicate section',
                                onClick: function (e) {
                                    e.stopPropagation();
                                    api.duplicateTopSection();
                                }
                            }, [ h('i', { class: 'fas fa-copy' }) ]) : null,
                            api && api.requestRemoveTopSection ? h('button', {
                                type: 'button',
                                class: 'layout-block-toolbar-btn layout-block-toolbar-btn-remove',
                                title: 'Remove section',
                                'aria-label': 'Remove section',
                                onClick: function (e) {
                                    e.stopPropagation();
                                    api.requestRemoveTopSection();
                                }
                            }, [ h('i', { class: 'fas fa-trash-alt' }) ]) : null,
                            h('button', {
                                type: 'button',
                                class: 'layout-block-toolbar-btn',
                                title: topCollapsed ? 'Expand' : 'Collapse',
                                'aria-label': topCollapsed ? 'Expand' : 'Collapse',
                                onClick: function (e) {
                                    e.stopPropagation();
                                    if (ctx.updateTopSectionCollapsed) ctx.updateTopSectionCollapsed(!topCollapsed);
                                }
                            }, [ h('i', { class: topCollapsed ? 'fas fa-chevron-down' : 'fas fa-chevron-up' }) ])
                        ])
                    ]),
                    h('div', { class: 'modular-column-inner modular-top-inner', style: topCollapsed ? { display: 'none' } : {} }, renderFieldList(topList))
                ]) : null;

                var blocks = unwrapRefBlocks(ctx.layoutBlocks);
                var idToBlock = {};
                (blocks || []).forEach(function (b) { if (b && b.id) idToBlock[b.id] = b; });
                var blockOrderIds = (order || []).filter(function (id) { return id !== 'top'; });

                for (var oi = 0; oi < order.length; oi++) {
                    var id = order[oi];
                    if (id === 'top') {
                        if (topSectionNode) nodes.push(topSectionNode);
                        continue;
                    }
                    var block = idToBlock[id];
                    if (!block) continue;
                    var bi = blockOrderIds.indexOf(id);
                    if (bi === -1) bi = 0;
                    var numCols = block.numCols === 2 || block.numCols === 3 ? block.numCols : 1;
                    var cols = block.columns || [ [], [], [] ];
                    var blockLabel = block.label || (numCols === 1 ? 'Layout 1 Column' : (numCols === 2 ? 'Layout 2 Columns' : 'Layout 3 Columns'));
                    var currentEditingId = (ctx.blockEditingId && typeof ctx.blockEditingId.value !== 'undefined' ? ctx.blockEditingId.value : ctx.blockEditingId);
                    var isEditingBlock = currentEditingId === block.id;
                    var blockCollapsed = !!block.collapsed;
                    var blockInputId = 'layout-block-edit-' + (block.id || bi);
                    var currentDraft = (ctx.blockLabelDraft && typeof ctx.blockLabelDraft.value !== 'undefined' ? ctx.blockLabelDraft.value : ctx.blockLabelDraft) || '';
                    /* IIFE: capture this block's values so rename/collapse handlers always target the correct block */
                    var headerEl = (function (capturedId, capturedLabel, capturedInputId, capturedCollapsed, capturedIsEditing) {
                        var headerLeft = capturedIsEditing
                            ? h('input', {
                                type: 'text',
                                id: capturedInputId,
                                class: 'layout-block-title-input',
                                value: currentDraft,
                                onInput: function (e) {
                                    if (ctx.setBlockLabelDraft) ctx.setBlockLabelDraft(e.target.value || '');
                                },
                                onKeydown: function (e) {
                                    if (e.key === 'Enter') {
                                        var d = (ctx.blockLabelDraft && typeof ctx.blockLabelDraft.value !== 'undefined' ? ctx.blockLabelDraft.value : ctx.blockLabelDraft) || '';
                                        if (ctx.updateBlockLabel) ctx.updateBlockLabel(capturedId, d);
                                        if (ctx.setBlockEditingId) ctx.setBlockEditingId(null);
                                    }
                                    if (e.key === 'Escape') {
                                        if (ctx.setBlockEditingId) ctx.setBlockEditingId(null);
                                        if (ctx.setBlockLabelDraft) ctx.setBlockLabelDraft(capturedLabel);
                                    }
                                },
                                onBlur: function () {
                                    var d = (ctx.blockLabelDraft && typeof ctx.blockLabelDraft.value !== 'undefined' ? ctx.blockLabelDraft.value : ctx.blockLabelDraft) || '';
                                    if (ctx.updateBlockLabel) ctx.updateBlockLabel(capturedId, d);
                                    if (ctx.setBlockEditingId) ctx.setBlockEditingId(null);
                                }
                            })
                            : h('span', { class: 'layout-block-title-text' }, capturedLabel);
                        return h('div', {
                            class: 'modular-block-header layout-block-toolbar' + (capturedCollapsed ? ' layout-block-collapsed' : ''),
                            onDblclick: function (e) {
                                e.stopPropagation();
                                ctx.updateBlockCollapsed(capturedId, !capturedCollapsed);
                            },
                            'aria-expanded': capturedCollapsed ? 'false' : 'true'
                        }, [
                            h('div', { class: 'layout-block-toolbar-left layout-block-toolbar-handle' }, [
                                headerLeft,
                                h('button', {
                                    type: 'button',
                                    class: 'layout-block-toolbar-btn',
                                    title: 'Rename layout',
                                    'aria-label': 'Rename layout',
                                    onClick: function (e) {
                                        e.stopPropagation();
                                        if (capturedIsEditing) {
                                            var draftNow = (ctx.blockLabelDraft && typeof ctx.blockLabelDraft.value !== 'undefined' ? ctx.blockLabelDraft.value : ctx.blockLabelDraft) || '';
                                            if (ctx.updateBlockLabel) ctx.updateBlockLabel(capturedId, draftNow);
                                            if (ctx.setBlockEditingId) ctx.setBlockEditingId(null);
                                        } else {
                                            if (ctx.setBlockLabelDraft) ctx.setBlockLabelDraft(capturedLabel);
                                            if (ctx.setBlockEditingId) ctx.setBlockEditingId(capturedId);
                                            setTimeout(function () {
                                                var el = document.getElementById(capturedInputId);
                                                if (el) el.focus();
                                            }, 50);
                                        }
                                    }
                                }, [ h('i', { class: 'fas fa-pen' }) ])
                            ]),
                            h('div', { class: 'layout-block-toolbar-right' }, [
                                api && api.duplicateBlock ? h('button', {
                                    type: 'button',
                                    class: 'layout-block-toolbar-btn',
                                    title: 'Duplicate layout',
                                    'aria-label': 'Duplicate layout',
                                    onClick: function (e) {
                                        e.stopPropagation();
                                        api.duplicateBlock(capturedId);
                                    }
                                }, [ h('i', { class: 'fas fa-copy' }) ]) : null,
                                api && api.requestRemoveBlock ? h('button', {
                                    type: 'button',
                                    class: 'layout-block-toolbar-btn layout-block-toolbar-btn-remove',
                                    title: 'Remove layout',
                                    'aria-label': 'Remove layout',
                                    onClick: function (e) {
                                        e.stopPropagation();
                                        api.requestRemoveBlock(capturedId);
                                    }
                                }, [ h('i', { class: 'fas fa-trash-alt' }) ]) : null,
                                h('button', {
                                    type: 'button',
                                    class: 'layout-block-toolbar-btn',
                                    title: capturedCollapsed ? 'Expand' : 'Collapse',
                                    'aria-label': capturedCollapsed ? 'Expand' : 'Collapse',
                                    onClick: function (e) {
                                        e.stopPropagation();
                                        ctx.updateBlockCollapsed(capturedId, !capturedCollapsed);
                                    }
                                }, [ h('i', { class: capturedCollapsed ? 'fas fa-chevron-down' : 'fas fa-chevron-up' }) ])
                            ])
                        ]);
                    })(block.id, blockLabel, blockInputId, blockCollapsed, isEditingBlock);
                    /* Header first (full width), then columns row so title/collapse sit flush top */
                    var colNodes = [];
                    for (var c = 0; c < numCols; c++) {
                        var list = cols[c] || [];
                        var colLabel = 'Column ' + (c + 1);
                        var columnContent = blockCollapsed
                            ? h('div', { class: 'modular-collapsed-col-label' }, colLabel + (list.length ? ' (' + list.length + ')' : ''))
                            : h('div', { class: 'modular-column-inner' }, renderFieldList(list));
                        colNodes.push(h('div', {
                            key: 'b' + bi + '-c' + c,
                            class: 'modular-column modular-column-drop' + (blockCollapsed ? ' modular-column-collapsed' : ''),
                            'data-block-index': String(bi),
                            'data-column-index': String(c)
                        }, [ columnContent ]));
                    }
                    nodes.push(h('div', {
                        key: 'block-' + (block.id || bi),
                        class: 'modular-layout-block modular-layout-' + (numCols === 3 ? '3col' : numCols === 2 ? '2col' : '1col') + (blockCollapsed ? ' modular-layout-block-collapsed' : ''),
                        'data-block-index': String(bi),
                        'data-block-id': block.id || ('block-' + bi)
                    }, [ headerEl, h('div', { class: 'modular-layout-block-columns' }, colNodes) ]));
                }

                return h('div', { class: 'modular-field-renderer-root' }, nodes);
            },
        });

        app.component('FieldRenderer', FieldRenderer);
        registerAllComponents(app);
        if (typeof window.ModularWysiwygField !== 'undefined') {
            app.component('wysiwyg-field', window.ModularWysiwygField);
        }
        app.mount(root);
        // JS-editor is actief: schakel PHP-fallback velden uit zodat ze geen HTML5-validatie triggeren.
        try {
            var phpFallback = document.getElementById('post-edit-fields-php-fallback');
            if (phpFallback) {
                phpFallback.querySelectorAll('input, select, textarea').forEach(function (el) {
                    el.disabled = true;
                    el.removeAttribute('required');
                });
            }
        } catch (e) {}
        /* Ensure Sortables init after first paint so Content section and layout blocks are in DOM */
        setTimeout(function () {
            try {
                document.dispatchEvent(new CustomEvent('modular-layout-changed'));
            } catch (e) {}
        }, 150);

        function seedNewInstances() {
            (topSection.value || []).forEach(function (inst) {
                if (!inst || !inst.id || Object.prototype.hasOwnProperty.call(instanceValues, inst.id)) return;
                seedInstanceValue(inst, undefined);
            });
            (layoutBlocks.value || []).forEach(function (block) {
                (block.columns || []).forEach(function (list) {
                    (list || []).forEach(function (inst) {
                        if (!inst || !inst.id || Object.prototype.hasOwnProperty.call(instanceValues, inst.id)) return;
                        seedInstanceValue(inst, undefined);
                    });
                });
            });
        }
        watch(topSection, seedNewInstances, { deep: true });
        watch(layoutBlocks, function () {
            seedNewInstances();
            var blocks = layoutBlocks.value || [];
            var blockIds = blocks.map(function (b) { return b.id; });
            var currentOrder = layoutOrder.value || [];
            // Preserve existing order (including 'top') and only
            // add/remove block ids as needed so the first item is not forced sticky.
            if (!currentOrder || !currentOrder.length) {
                layoutOrder.value = ['top'].concat(blockIds);
            } else {
                var existing = currentOrder.slice();
                // Remove ids that no longer exist
                existing = existing.filter(function (id) {
                    return id === 'top' || blockIds.indexOf(id) !== -1;
                });
                // Append any new block ids in schema order
                blockIds.forEach(function (id) {
                    if (existing.indexOf(id) === -1) existing.push(id);
                });
                layoutOrder.value = existing;
            }
            setTimeout(function () { document.dispatchEvent(new CustomEvent('modular-layout-changed')); }, 0);
        }, { deep: true });

        var form = formId ? document.getElementById(formId) : root.closest('form');
        var jsonInput = fieldDataJsonInputId ? document.getElementById(fieldDataJsonInputId) : null;
        if (form && jsonInput) {
            function serializeToJsonInput() {
                try {
                    /* Sync layout and block order from DOM so dragged order and field positions are persisted. */
                    var api = window.ModularFieldRendererApp;
                    if (api) {
                        if (typeof api.syncLayoutFromDOM === 'function') api.syncLayoutFromDOM();
                        if (typeof api.syncBlocksFromDOM === 'function') api.syncBlocksFromDOM();
                    }
                    var payload = {
                        __layout_top: (topSection.value || []).slice(),
                        __layout_blocks: (layoutBlocks.value || []).map(function (b) {
                            return {
                                id: b.id,
                                numCols: b.numCols,
                                label: b.label || (b.numCols === 1 ? 'Layout 1 Column' : (b.numCols === 2 ? 'Layout 2 Columns' : 'Layout 3 Columns')),
                                collapsed: !!b.collapsed,
                                columns: [ (b.columns[0] || []).slice(), (b.columns[1] || []).slice(), (b.columns[2] || []).slice() ]
                            };
                        }),
                        __values_by_instance: {},
                        __layout_order: (layoutOrder.value || []).slice()
                    };
                    function collectIds(list) {
                        (list || []).forEach(function (inst) {
                            if (inst && inst.id) {
                                var v = instanceValues[inst.id];
                                payload.__values_by_instance[inst.id] = v !== undefined ? v : null;
                            }
                        });
                    }
                    collectIds(topSection.value);
                    (layoutBlocks.value || []).forEach(function (b) {
                        (b.columns || []).forEach(collectIds);
                    });
                    jsonInput.value = JSON.stringify(payload);
                } catch (e) {
                    console.error('Field renderer: serialize error', e);
                }
            }
            form.addEventListener('submit', serializeToJsonInput, true);
            if (window.ModularFieldRendererApp) {
                window.ModularFieldRendererApp._serialize = serializeToJsonInput;
            }
        }

        return { app: app, topSection: topSection, layoutBlocks: layoutBlocks, layoutOrder: layoutOrder, instanceValues: instanceValues };
    }

    var mountRetries = 0;
    var maxMountRetries = 25;

    function init() {
        var rootId = 'post-edit-fields-vue-app';
        var formId = 'post-edit-form';
        var jsonId = 'field_data_json';
        function doMount() {
            var root = document.getElementById(rootId);
            if (root) {
                try {
                    window.ModularFieldRendererApp._instance = mountFieldRendererApp(rootId, formId, jsonId);
                } catch (err) {
                    console.error('Modular field renderer mount error:', err);
                }
                return;
            }
            if (mountRetries < maxMountRetries) {
                mountRetries += 1;
                setTimeout(doMount, 50);
            }
        }
        function runInit() {
            mountRetries = 0;
            doMount();
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runInit);
        } else {
            runInit();
        }
    }

    window.ModularFieldRendererApp = {
        mount: mountFieldRendererApp,
        _instance: null,
        FieldRenderer: FieldRenderer,
        registerAllComponents: registerAllComponents,
        COMPONENT_REGISTRY: COMPONENT_REGISTRY,
        getLayout: function () {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst) return { topSection: [], layoutBlocks: [] };
            return {
                topSection: (inst.topSection && inst.topSection.value || []).slice(),
                layoutBlocks: (inst.layoutBlocks && inst.layoutBlocks.value || []).map(function (b) {
                    return {
                        id: b.id,
                        numCols: b.numCols,
                        label: b.label || (b.numCols === 1 ? 'Layout 1 Column' : (b.numCols === 2 ? 'Layout 2 Columns' : 'Layout 3 Columns')),
                        collapsed: !!b.collapsed,
                        columns: [ (b.columns[0] || []).slice(), (b.columns[1] || []).slice(), (b.columns[2] || []).slice() ]
                    };
                })
            };
        },
        /** Persist current layout (top + blocks + order) to server via AJAX (no full Save). */
        persistLayout: function () {
            var inst = window.ModularFieldRendererApp._instance;
            var postId = (typeof window.MODULAR_POST_ID !== 'undefined' && window.MODULAR_POST_ID) ? window.MODULAR_POST_ID : 0;
            if (!inst || !postId) return;
            var layout = window.ModularFieldRendererApp.getLayout();
            var order = (inst.layoutOrder && inst.layoutOrder.value) || [];
            var payload = {
                top: layout.topSection || [],
                blocks: layout.layoutBlocks || [],
                order: order
            };
            var base = (typeof window.MODULAR_BASE_URL !== 'undefined' && window.MODULAR_BASE_URL) ? window.MODULAR_BASE_URL : '/modular';
            var body = new URLSearchParams();
            body.append('id', String(postId));
            body.append('layout_json', JSON.stringify(payload));
            if (typeof window.MODULAR_CSRF_TOKEN !== 'undefined' && window.MODULAR_CSRF_TOKEN) {
                body.append('csrf_token', window.MODULAR_CSRF_TOKEN);
            }
            try {
                fetch(base.replace(/\/$/, '') + '/admin/posts/update-layout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString(),
                    credentials: 'same-origin'
                }).catch(function () { /* ignore network errors for now */ });
            } catch (e) {
                // Silent fail; full Save still works as fallback.
            }
        },
        addLayoutBlock: function (numCols) {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !inst.layoutBlocks) return;
            var n = numCols === 2 || numCols === 3 ? numCols : 1;
            var cols = [ [], [], [] ];
            if (n === 1) cols = [ [] ];
            else if (n === 2) cols = [ [], [] ];
            var defaultLabel = n === 1 ? 'Layout 1 Column' : (n === 2 ? 'Layout 2 Columns' : 'Layout 3 Columns');
            var next = (inst.layoutBlocks.value || []).slice();
            next.push({
                id: 'block-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8),
                numCols: n,
                label: defaultLabel,
                collapsed: false,
                columns: cols
            });
            inst.layoutBlocks.value = next;
            var newBlockIds = (inst.layoutOrder.value || []).filter(function (id) { return id !== 'top'; });
            var blockIds = next.map(function (b) { return b.id; });
            if (newBlockIds.length !== blockIds.length || newBlockIds.some(function (id, i) { return id !== blockIds[i]; })) {
                inst.layoutOrder.value = ['top'].concat(blockIds);
            }
            try {
                document.dispatchEvent(new CustomEvent('modular-layout-changed'));
            } catch (e) {}
        },
        addInstanceToTop: function (fieldName) {
            return window.ModularFieldRendererApp.addInstanceToTopAt(-1, fieldName);
        },
        addInstanceToBlock: function (blockIndex, columnIndex, fieldName) {
            return window.ModularFieldRendererApp.addInstanceToBlockAt(blockIndex, columnIndex, -1, fieldName);
        },
        addInstanceToBlockAt: function (blockIndex, columnIndex, insertIndex, fieldName) {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !inst.layoutBlocks) return null;
            var blocks = inst.layoutBlocks.value || [];
            if (blockIndex < 0 || blockIndex >= blocks.length) return null;
            var block = blocks[blockIndex];
            var numCols = block.numCols === 2 || block.numCols === 3 ? block.numCols : 1;
            if (columnIndex < 0 || columnIndex >= numCols) return null;
            var newId = 'fi-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
            var newInst = { id: newId, name: fieldName };
            inst.layoutBlocks.value = blocks.map(function (b, i) {
                if (i !== blockIndex) return b;
                var cols = (b.columns || [ [], [], [] ]).map(function (c) { return c.slice(); });
                if (!cols[columnIndex]) cols[columnIndex] = [];
                if (insertIndex >= 0 && insertIndex <= cols[columnIndex].length) {
                    cols[columnIndex].splice(insertIndex, 0, newInst);
                } else {
                    cols[columnIndex].push(newInst);
                }
                return { id: b.id, numCols: b.numCols, columns: cols };
            });
            return newId;
        },
        addInstanceToTopAt: function (insertIndex, fieldName) {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !inst.topSection) return null;
            var list = (inst.topSection.value || []).slice();
            var newId = 'fi-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
            var newInst = { id: newId, name: fieldName };
            if (insertIndex >= 0 && insertIndex <= list.length) {
                list.splice(insertIndex, 0, newInst);
            } else {
                list.push(newInst);
            }
            inst.topSection.value = list;
            return newId;
        },
        hasInstanceContent: function (instanceId) {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !inst.instanceValues || !instanceId) return false;
            var v = inst.instanceValues[instanceId];
            if (v === undefined || v === null) return false;
            if (typeof v === 'string') return v.trim() !== '';
            if (Array.isArray(v)) return v.length > 0;
            if (typeof v === 'object') return Object.keys(v).length > 0;
            return true;
        },
        requestRemoveInstance: function (instanceId) {
            var api = window.ModularFieldRendererApp;
            if (!api.hasInstanceContent(instanceId)) {
                api.removeInstance(instanceId);
                return;
            }
            if (typeof api.onRemoveConfirm === 'function') {
                api.onRemoveConfirm(instanceId, function () { api.removeInstance(instanceId); });
            } else {
                api.removeInstance(instanceId);
            }
        },
        removeInstance: function (instanceId) {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !instanceId) return;
            var removeFromList = function (list) {
                var idx = list.findIndex(function (o) { return o && o.id === instanceId; });
                if (idx !== -1) {
                    list.splice(idx, 1);
                    return true;
                }
                return false;
            };
            var top = (inst.topSection && inst.topSection.value) || [];
            if (removeFromList(top)) {
                inst.topSection.value = top.slice();
                if (inst.instanceValues && inst.instanceValues[instanceId] !== undefined) delete inst.instanceValues[instanceId];
                return;
            }
            var blocks = (inst.layoutBlocks && inst.layoutBlocks.value) || [];
            inst.layoutBlocks.value = blocks.map(function (b) {
                var cols = (b.columns || [ [], [], [] ]).map(function (col) {
                    var c = col.slice();
                    removeFromList(c);
                    return c;
                });
                return { id: b.id, numCols: b.numCols, columns: cols };
            });
            if (inst.instanceValues && inst.instanceValues[instanceId] !== undefined) {
                delete inst.instanceValues[instanceId];
            }
        },
        requestRemoveBlock: function (blockId) {
            var api = window.ModularFieldRendererApp;
            if (typeof api.onRemoveConfirm === 'function') {
                api.onRemoveConfirm(blockId, function () { api.removeBlock(blockId); });
            } else {
                api.removeBlock(blockId);
            }
        },
        removeBlock: function (blockId) {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !blockId) return;
            var blocks = (inst.layoutBlocks && inst.layoutBlocks.value) || [];
            var blockIndex = blocks.findIndex(function (b) { return b && b.id === blockId; });
            if (blockIndex === -1) return;
            var block = blocks[blockIndex];
            var idsToRemove = [];
            (block.columns || []).forEach(function (col) {
                (col || []).forEach(function (o) { if (o && o.id) idsToRemove.push(o.id); });
            });
            inst.layoutBlocks.value = blocks.filter(function (b) { return b.id !== blockId; });
            var order = (inst.layoutOrder && inst.layoutOrder.value) || [];
            inst.layoutOrder.value = order.filter(function (id) { return id !== blockId; });
            if (inst.instanceValues) {
                idsToRemove.forEach(function (id) { delete inst.instanceValues[id]; });
            }
            try {
                document.dispatchEvent(new CustomEvent('modular-layout-changed'));
            } catch (e) {}
            try {
                if (typeof window.ModularFieldRendererApp.persistLayout === 'function') {
                    window.ModularFieldRendererApp.persistLayout();
                }
            } catch (e) {}
        },
        requestRemoveTopSection: function () {
            var api = window.ModularFieldRendererApp;
            var inst = api && api._instance;
            var top = (inst && inst.topSection && inst.topSection.value) || [];
            var hasContent = top.some(function (inst) { return api.hasInstanceContent(inst.id); });
            if (hasContent && typeof api.onRemoveConfirm === 'function') {
                api.onRemoveConfirm('top', function () { api.removeTopSection(); });
            } else {
                api.removeTopSection();
            }
        },
        removeTopSection: function () {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !inst.topSection) return;
            var top = (inst.topSection.value || []).slice();
            top.forEach(function (o) {
                if (o && o.id && inst.instanceValues) delete inst.instanceValues[o.id];
            });
            inst.topSection.value = [];
            try {
                if (typeof window.ModularFieldRendererApp.persistLayout === 'function') {
                    window.ModularFieldRendererApp.persistLayout();
                }
            } catch (e) {}
        },
        duplicateTopSection: function () {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !inst.topSection || !inst.layoutBlocks) return null;
            var top = (inst.topSection.value || []).slice();
            var newBlockId = 'block-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
            var newCol0 = top.map(function (oldInst) {
                var newId = 'fi-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
                var val = (inst.instanceValues && inst.instanceValues[oldInst.id]) !== undefined ? inst.instanceValues[oldInst.id] : undefined;
                var copyVal = val !== undefined && val !== null
                    ? (typeof val === 'object' && val !== null && !Array.isArray(val) ? Object.assign({}, val) : Array.isArray(val) ? val.slice() : val)
                    : undefined;
                if (inst.instanceValues && copyVal !== undefined) inst.instanceValues[newId] = copyVal;
                return { id: newId, name: oldInst.name };
            });
            var newBlock = {
                id: newBlockId,
                numCols: 1,
                label: 'Content (copy)',
                collapsed: false,
                columns: [ newCol0, [], [] ]
            };
            var blocks = (inst.layoutBlocks.value || []).slice();
            blocks.push(newBlock);
            inst.layoutBlocks.value = blocks;
            var order = (inst.layoutOrder && inst.layoutOrder.value) || [];
            var blockIds = order.filter(function (id) { return id !== 'top'; });
            blockIds.push(newBlockId);
            inst.layoutOrder.value = ['top'].concat(blockIds);
            try {
                document.dispatchEvent(new CustomEvent('modular-layout-changed'));
            } catch (e) {}
            return newBlockId;
        },
        duplicateBlock: function (blockId) {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !blockId) return null;
            var blocks = (inst.layoutBlocks && inst.layoutBlocks.value) || [];
            var blockIndex = blocks.findIndex(function (b) { return b && b.id === blockId; });
            if (blockIndex === -1) return null;
            var block = blocks[blockIndex];
            var newBlockId = 'block-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
            var newColumns = (block.columns || [ [], [], [] ]).map(function (col) {
                return (col || []).map(function (oldInst) {
                    var newId = 'fi-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
                    var val = (inst.instanceValues && inst.instanceValues[oldInst.id]) !== undefined ? inst.instanceValues[oldInst.id] : undefined;
                    var copyVal = val !== undefined && val !== null
                        ? (typeof val === 'object' && val !== null && !Array.isArray(val) ? Object.assign({}, val) : Array.isArray(val) ? val.slice() : val)
                        : undefined;
                    if (inst.instanceValues && copyVal !== undefined) {
                        inst.instanceValues[newId] = copyVal;
                    }
                    return { id: newId, name: oldInst.name };
                });
            });
            var newBlock = {
                id: newBlockId,
                numCols: block.numCols === 2 || block.numCols === 3 ? block.numCols : 1,
                label: (block.label || '').trim() ? (block.label + ' (copy)') : (block.numCols === 2 ? 'Layout 2 Columns (copy)' : block.numCols === 3 ? 'Layout 3 Columns (copy)' : 'Layout 1 Column (copy)'),
                collapsed: !!block.collapsed,
                columns: newColumns
            };
            var next = blocks.slice();
            next.splice(blockIndex + 1, 0, newBlock);
            inst.layoutBlocks.value = next;
            var order = (inst.layoutOrder && inst.layoutOrder.value) || [];
            var orderFiltered = order.filter(function (id) { return id !== 'top'; });
            var newOrderIds = [];
            for (var i = 0; i < orderFiltered.length; i++) {
                newOrderIds.push(orderFiltered[i]);
                if (orderFiltered[i] === blockId) {
                    newOrderIds.push(newBlockId);
                }
            }
            inst.layoutOrder.value = ['top'].concat(newOrderIds);
            try {
                document.dispatchEvent(new CustomEvent('modular-layout-changed'));
            } catch (e) {}
            return newBlockId;
        },
        duplicateInstance: function (instanceId) {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !instanceId) return null;
            var findInList = function (list) {
                var idx = list.findIndex(function (o) { return o && o.id === instanceId; });
                return idx >= 0 ? { list: list, index: idx } : null;
            };
            var top = (inst.topSection && inst.topSection.value) || [];
            var found = findInList(top);
            if (found) {
                var oldInst = top[found.index];
                var newId = 'fi-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
                var newInst = { id: newId, name: oldInst.name };
                var newList = top.slice();
                newList.splice(found.index + 1, 0, newInst);
                inst.topSection.value = newList;
                var val = (inst.instanceValues && inst.instanceValues[instanceId]) !== undefined ? inst.instanceValues[instanceId] : undefined;
                var copyVal = val !== undefined && val !== null ? (typeof val === 'object' && val !== null && !Array.isArray(val) ? Object.assign({}, val) : Array.isArray(val) ? val.slice() : val) : undefined;
                if (inst.instanceValues && copyVal !== undefined) {
                    setTimeout(function () { inst.instanceValues[newId] = copyVal; }, 0);
                }
                return newId;
            }
            var blocks = (inst.layoutBlocks && inst.layoutBlocks.value) || [];
            for (var bi = 0; bi < blocks.length; bi++) {
                var cols = (blocks[bi].columns || [ [], [], [] ]);
                for (var ci = 0; ci < cols.length; ci++) {
                    found = findInList(cols[ci]);
                    if (found) {
                        var oldInst = cols[ci][found.index];
                        var newId = 'fi-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
                        var newInst = { id: newId, name: oldInst.name };
                        inst.layoutBlocks.value = blocks.map(function (b, i) {
                            if (i !== bi) return b;
                            var newCols = (b.columns || [ [], [], [] ]).map(function (c, j) {
                                if (j !== ci) return c.slice();
                                var c2 = c.slice();
                                c2.splice(found.index + 1, 0, newInst);
                                return c2;
                            });
                            return { id: b.id, numCols: b.numCols, label: b.label, collapsed: !!b.collapsed, columns: newCols };
                        });
                        var val = (inst.instanceValues && inst.instanceValues[instanceId]) !== undefined ? inst.instanceValues[instanceId] : undefined;
                        var copyVal = val !== undefined && val !== null ? (typeof val === 'object' && val !== null && !Array.isArray(val) ? Object.assign({}, val) : Array.isArray(val) ? val.slice() : val) : undefined;
                        if (inst.instanceValues && copyVal !== undefined) {
                            setTimeout(function () { inst.instanceValues[newId] = copyVal; }, 0);
                        }
                        return newId;
                    }
                }
            }
            return null;
        },
        setTopSection: function (instances) {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !inst.topSection) return;
            inst.topSection.value = Array.isArray(instances) ? instances.slice() : [];
        },
        setBlockColumn: function (blockIndex, columnIndex, instances) {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !inst.layoutBlocks) return;
            var blocks = inst.layoutBlocks.value || [];
            if (blockIndex < 0 || blockIndex >= blocks.length) return;
            inst.layoutBlocks.value = blocks.map(function (b, i) {
                if (i !== blockIndex) return b;
                var cols = (b.columns || [ [], [], [] ]).map(function (c) { return c.slice(); });
                cols[columnIndex] = Array.isArray(instances) ? instances.slice() : [];
                return { id: b.id, numCols: b.numCols, label: b.label, collapsed: !!b.collapsed, columns: cols };
            });
        },
        /** Reorder based on DOM: both top section and layout blocks (drag whole dropzones). */
        syncBlocksFromDOM: function () {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst || !inst.layoutBlocks) return;
            var root = document.querySelector('#post-edit-fields-area .modular-field-renderer-root');
            if (!root) return;
            var blocks = (inst.layoutBlocks.value || []).slice();
            var idToBlock = {};
            blocks.forEach(function (b) {
                if (b && b.id) idToBlock[b.id] = b;
            });
            var newOrderIds = [];
            var newBlocks = [];
            [].slice.call(root.children).forEach(function (el) {
                var id = el.getAttribute && el.getAttribute('data-block-id');
                if (!id) return;
                newOrderIds.push(id);
                if (id !== 'top' && idToBlock[id]) {
                    newBlocks.push(idToBlock[id]);
                }
            });
            if (newOrderIds.length) inst.layoutOrder.value = newOrderIds;
            if (newBlocks.length || blocks.length) inst.layoutBlocks.value = newBlocks.length ? newBlocks : blocks;
        },
        /** Build id -> instance map from current layout, then sync from DOM order (for drag-between-zones). */
        syncLayoutFromDOM: function () {
            var inst = window.ModularFieldRendererApp._instance;
            if (!inst) return;
            var root = document.querySelector('#post-edit-fields-area .modular-field-renderer-root');
            if (!root) return;
            var layout = window.ModularFieldRendererApp.getLayout();
            var idToInst = {};
            (layout.topSection || []).forEach(function (o) { if (o && o.id) idToInst[o.id] = o; });
            (layout.layoutBlocks || []).forEach(function (b) {
                (b.columns || []).forEach(function (col) {
                    (col || []).forEach(function (o) { if (o && o.id) idToInst[o.id] = o; });
                });
            });
            var topInner = root.querySelector('.modular-top-inner');
            var newTop = [];
            if (topInner) {
                topInner.querySelectorAll('[data-field-id]').forEach(function (el) {
                    var id = el.getAttribute('data-field-id');
                    if (id && idToInst[id]) newTop.push(idToInst[id]);
                });
            }
            inst.topSection.value = newTop;
            var blocks = (inst.layoutBlocks && inst.layoutBlocks.value) || [];
            var newBlocks = blocks.map(function (block, bi) {
                var numCols = block.numCols === 2 || block.numCols === 3 ? block.numCols : 1;
                var cols = [];
                for (var ci = 0; ci < 3; ci++) {
                    if (ci < numCols) {
                        var colEl = root.querySelector('.modular-column-drop[data-block-index="' + bi + '"][data-column-index="' + ci + '"] .modular-column-inner');
                        var newCol = [];
                        if (colEl) {
                            colEl.querySelectorAll('[data-field-id]').forEach(function (el) {
                                var id = el.getAttribute('data-field-id');
                                if (id && idToInst[id]) newCol.push(idToInst[id]);
                            });
                        }
                        cols.push(newCol);
                    } else {
                        cols.push([]);
                    }
                }
                return {
                    id: block.id,
                    numCols: block.numCols,
                    label: block.label || (block.numCols === 1 ? 'Layout 1 Column' : (block.numCols === 2 ? 'Layout 2 Columns' : 'Layout 3 Columns')),
                    collapsed: !!block.collapsed,
                    columns: cols
                };
            });
            inst.layoutBlocks.value = newBlocks;
        },
    };

    function start() {
        var root = document.getElementById('post-edit-fields-vue-app');
        var schemaScript = document.getElementById('modular-field-schema-json');
        var hasSchema = (root && root.dataset && root.dataset.schema) || window.MODULAR_FIELD_SCHEMA || (schemaScript && schemaScript.textContent);
        if (root && hasSchema) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        } else {
            document.addEventListener('modular-field-renderer-mount', function () { init(); });
        }
    }
    start();
})();
