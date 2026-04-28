/**
 * Post Edit: schema-driven PrimeVue field renderer.
 * Config → renderer only. No hardcoded component choice; all from field.ui.
 * Values sync to hidden inputs for form submit.
 */
(function () {
    if (!window.Vue || !window.PrimeVue) return;

    var schema = window.MODULAR_FIELD_SCHEMA;
    var initialData = window.MODULAR_FIELD_DATA || {};
    if (!Array.isArray(schema) || schema.length === 0) return;

    var Vue = window.Vue;
    var createApp = Vue.createApp;
    var ref = Vue.ref;
    var watch = Vue.watch;
    var computed = Vue.computed;

    var fieldValues = {};
    schema.forEach(function (f) {
        var key = f.key || f.name;
        if (!key) return;
        var val = initialData[key];
        if (val === undefined) val = f.default !== undefined ? f.default : null;
        fieldValues[key] = val;
    });

    var app = createApp({
        setup: function () {
            var state = ref(fieldValues);

            function setValue(key, v) {
                state.value[key] = v;
            }

            function getValue(key) {
                return state.value[key];
            }

            function isUtility(type) {
                return ['message', 'tab', 'divider'].indexOf(type) >= 0;
            }

            function isLayout(type) {
                return ['group', 'repeater', 'flexible_content'].indexOf(type) >= 0;
            }

            function needsMultipleInputs(field) {
                var t = field.type;
                var format = (field.storage || {}).format;
                return format === 'array' || t === 'relationship' || t === 'relation' || t === 'gallery';
            }

            function optionsFor(field) {
                if (field.options && Array.isArray(field.options)) return field.options;
                var choices = field.choices;
                if (!choices || typeof choices !== 'object') return [];
                return Object.keys(choices).map(function (k) {
                    return { label: choices[k], value: k };
                });
            }

            function componentTag(field) {
                var ui = field.ui || {};
                var comp = (ui.component || 'InputText').toString();
                return 'p-' + comp.replace(/([A-Z])/g, function (m) { return m.toLowerCase(); });
            }

            function getSerializedValue(field) {
                var v = getValue(field.key || field.name);
                if (v == null) return '';
                if (Array.isArray(v)) return v.join(',');
                return String(v);
            }

            function setLayoutValue(field, e) {
                var k = field.key || field.name;
                try {
                    setValue(k, JSON.parse(e.target.value));
                } catch (x) {
                    setValue(k, e.target.value);
                }
            }

            function getLayoutDisplayValue(field) {
                var v = getValue(field.key || field.name);
                if (v != null && typeof v === 'object') return JSON.stringify(v, null, 2);
                return v != null ? String(v) : '';
            }

            function componentProps(field) {
                var ui = field.ui || {};
                var props = Object.assign({}, ui.props || {});
                var opts = optionsFor(field);
                if (opts.length) {
                    props.options = opts;
                    if (!props.optionLabel) props.optionLabel = 'label';
                    if (!props.optionValue) props.optionValue = 'value';
                }
                var key = field.key || field.name;
                if (field.placeholder !== undefined) props.placeholder = field.placeholder;
                if (field.required) props.required = true;
                return props;
            }

            return {
                schema: ref(schema),
                state,
                setValue,
                getValue,
                isUtility,
                isLayout,
                needsMultipleInputs,
                optionsFor,
                componentTag,
                getSerializedValue,
                setLayoutValue,
                getLayoutDisplayValue,
                componentProps,
            };
        },
        template: `
            <div class="modular-fields-app">
                <template v-for="(field, idx) in schema" :key="field.key || idx">
                    <div v-if="!field.key && !field.name" class="form-group"></div>
                    <div v-else-if="isUtility(field.type)" class="form-group form-group-utility">
                        <div v-if="field.type === 'message'" class="help-text" v-html="field.label || field.instructions"></div>
                        <hr v-else-if="field.type === 'divider'" class="section-divider">
                        <div v-else class="form-group-utility-label">{{ field.label }}</div>
                    </div>
                    <div v-else-if="field.type === 'hidden'" class="form-group form-group-hidden">
                        <input type="hidden" :name="'field_data[' + (field.key || field.name) + ']'" :value="getValue(field.key || field.name) || ''">
                    </div>
                    <div v-else-if="isLayout(field.type)" class="form-group form-group-layout">
                        <label>{{ field.label }}</label>
                        <textarea class="form-control p-inputtext p-component w-full" rows="4" :value="getLayoutDisplayValue(field)" @input="e => setLayoutValue(field, e)" placeholder="JSON"></textarea>
                        <input type="hidden" :name="'field_data[' + (field.key || field.name) + ']'" :value="typeof getValue(field.key || field.name) === 'object' ? JSON.stringify(getValue(field.key || field.name)) : (getValue(field.key || field.name) || '')">
                    </div>
                    <div v-else class="form-group" :data-field-type="field.type" :data-field-key="field.key || field.name">
                        <label :for="'field-' + (field.key || field.name)">
                            {{ field.label }}
                            <span v-if="field.required" class="required-asterisk" aria-label="Required">*</span>
                        </label>
                        <component
                            v-if="field.ui && field.ui.component"
                            :is="componentTag(field)"
                            :modelValue="getValue(field.key || field.name)"
                            @update:modelValue="v => setValue(field.key || field.name, v)"
                            v-bind="componentProps(field)"
                            :id="'field-' + (field.key || field.name)"
                            class="modular-field-control w-full"
                        />
                        <input v-else type="text" class="form-control p-inputtext p-component w-full" :value="getValue(field.key || field.name)" @input="setValue(field.key || field.name, $event.target.value)" :name="'field_data[' + (field.key || field.name) + ']'">
                        <div v-if="field.instructions" class="help-text">{{ field.instructions }}</div>
                        <template v-if="!needsMultipleInputs(field)">
                            <input type="hidden" :name="'field_data[' + (field.key || field.name) + ']'" :value="getSerializedValue(field)">
                        </template>
                        <template v-else>
                            <template v-for="(item, i) in (getValue(field.key || field.name) || [])" :key="i">
                                <input type="hidden" :name="'field_data[' + (field.key || field.name) + '][]'" :value="item">
                            </template>
                        </template>
                    </div>
                </template>
            </div>
        `,
    });

    // Register PrimeVue and components we use (kebab-case tag names)
    app.use(PrimeVue.Config);
    var p = window.PrimeVue;
    var components = [
        'InputText', 'Textarea', 'Editor', 'Dropdown', 'MultiSelect', 'AutoComplete',
        'Calendar', 'Checkbox', 'RadioButton', 'SelectButton', 'Password', 'InputNumber',
        'Slider', 'TreeSelect', 'FileUpload',
    ];
    components.forEach(function (name) {
        if (p[name]) {
            var kebab = name.replace(/([A-Z])/g, function (m) { return '-' + m.toLowerCase(); }).replace(/^-/, '');
            app.component('p-' + kebab, p[name]);
        }
    });

    var root = document.getElementById('post-edit-fields-app');
    if (root) app.mount(root);
})();
