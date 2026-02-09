/**
 * PrimeVue-powered field UX for post edit (styled mode).
 * Requires: Vue 3, PrimeVue UMD, Quill (for Editor), optional Aura theme (PrimeUIX.Themes.Aura).
 * - WYSIWYG -> PrimeVue Editor (Quill)
 * - Relationship -> MultiSelect, Post Object -> Dropdown
 * - Other form components registered for future use.
 */
(function () {
    if (!window.Vue || !window.PrimeVue) return;

    const { createApp, ref, watch, computed } = Vue;
    const PV = window.PrimeVue;
    const PrimeUIX = window.PrimeUIX;

    function getPrimeVueConfig() {
        const config = { ripple: true };
        if (PrimeUIX && PrimeUIX.Themes && PrimeUIX.Themes.Aura) {
            config.theme = { preset: PrimeUIX.Themes.Aura };
        }
        return config;
    }

    var pComponentNames = {
        Editor: 'p-editor', InputText: 'p-inputtext', Textarea: 'p-textarea', Dropdown: 'p-dropdown',
        MultiSelect: 'p-multiselect', AutoComplete: 'p-autocomplete', Calendar: 'p-calendar',
        Checkbox: 'p-checkbox', RadioButton: 'p-radiobutton', SelectButton: 'p-selectbutton',
        InputNumber: 'p-inputnumber', Slider: 'p-slider', ToggleSwitch: 'p-toggleswitch',
        Password: 'p-password', FileUpload: 'p-fileupload', TreeSelect: 'p-treeselect',
        Select: 'p-select', Listbox: 'p-listbox', ColorPicker: 'p-colorpicker', Rating: 'p-rating'
    };

    function registerFormComponents(app) {
        if (PV.Config) app.use(PV.Config, getPrimeVueConfig());
        Object.keys(pComponentNames).forEach(function (name) {
            if (PV[name]) app.component(pComponentNames[name], PV[name]);
        });
    }

    function mountEditor(textarea) {
        if (!textarea || textarea.dataset.pvEditor === '1') return;
        if (!PV.Editor) return; /* requires PrimeVue Editor + Quill */
        textarea.dataset.pvEditor = '1';

        var parent = textarea.parentNode;
        if (!parent) return;

        var mountEl = document.createElement('div');
        mountEl.className = 'pv-wysiwyg-root pv-editor-mount';
        parent.insertBefore(mountEl, textarea);
        textarea.style.display = 'none';

        var initialValue = textarea.value || '';
        var rows = parseInt(textarea.getAttribute('data-rows') || '10', 10);
        var height = Math.max(200, rows * 24) + 'px';

        var app = createApp({
            setup: function () {
                var value = ref(initialValue);
                watch(value, function (v) {
                    textarea.value = v == null ? '' : String(v);
                }, { immediate: true });
                return { value: value, editorStyle: { height: height } };
            },
            template: '<div class="pv-editor-wrapper"><p-editor v-model="value" :editorStyle="editorStyle" placeholder="Write content..." /></div>'
        });
        registerFormComponents(app);
        app.component('p-editor', PV.Editor);
        app.mount(mountEl);
    }

    function mountRelationshipSelect(select, type) {
        if (!select || select.dataset.pvRelation === '1') return;
        select.dataset.pvRelation = '1';

        var parent = select.parentNode;
        if (!parent) return;

        var mountEl = document.createElement('div');
        mountEl.className = 'pv-relation-root';
        parent.insertBefore(mountEl, select);
        select.style.display = 'none';

        var options = Array.from(select.options).map(function (opt) {
            return { label: opt.textContent.trim(), value: opt.value };
        });
        var initialSelected = Array.from(select.options)
            .filter(function (o) { return o.selected; })
            .map(function (o) { return o.value; });
        var multiple = type === 'relationship';

        var app = createApp({
            setup: function () {
                var model = ref(multiple ? initialSelected : (initialSelected[0] || null));
                watch(model, function (v) {
                    var values = multiple ? (Array.isArray(v) ? v : []) : (v != null ? [v] : []);
                    Array.from(select.options).forEach(function (opt) {
                        opt.selected = values.indexOf(opt.value) !== -1;
                    });
                }, { immediate: true, deep: true });
                return { model: model, options: options };
            },
            template: multiple
                ? '<p-multiselect v-model="model" :options="options" optionLabel="label" optionValue="value" display="chip" class="w-full" :showToggleAll="false" :maxSelectedLabels="3" placeholder="Select posts" />'
                : '<p-dropdown v-model="model" :options="options" optionLabel="label" optionValue="value" class="w-full" showClear placeholder="Select a post" />'
        });
        registerFormComponents(app);
        if (PV.MultiSelect) app.component('p-multiselect', PV.MultiSelect);
        if (PV.Dropdown) app.component('p-dropdown', PV.Dropdown);
        app.mount(mountEl);
    }

    function initAll() {
        document.querySelectorAll('textarea.wysiwyg-textarea').forEach(mountEditor);
        document.querySelectorAll('.form-group[data-field-type="relationship"] select').forEach(function (sel) {
            mountRelationshipSelect(sel, 'relationship');
        });
        document.querySelectorAll('.form-group[data-field-type="post_object"] select').forEach(function (sel) {
            mountRelationshipSelect(sel, 'post_object');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
