/**
 * Field Builder — Vue 3 app (Library | Canvas | Settings panel)
 * Uses MODULAR_FIELD_BUILDER_CONFIG from window.
 */
(function () {
    'use strict';
    var config = window.MODULAR_FIELD_BUILDER_CONFIG;
    if (!config) {
        console.error('MODULAR_FIELD_BUILDER_CONFIG missing');
        return;
    }

    var TAB_KEYS = {
        general: ['label', 'name', 'key', 'instructions', 'default_value', 'sub_fields', 'layouts', 'button_label'],
        validation: ['required', 'min', 'max', 'step', 'maxlength', 'pattern', 'allow_null', 'multiple'],
        display: ['placeholder', 'prepend', 'append', 'rows', 'wrapper_class', 'input_class', 'ui', 'return_format', 'choices', 'layout', 'toolbar', 'media_upload', 'tabs', 'delay'],
        logic: ['conditional_logic']
    };

    function slugify(s) {
        return String(s).toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '') || 'field';
    }
    function nextUid() {
        return 'fb-' + Date.now() + '-' + Math.random().toString(36).slice(2, 9);
    }
    function ensureUids(fields) {
        return (fields || []).map(function (f, i) {
            var out = Object.assign({}, f);
            if (!out._uid) out._uid = out._uid || 'fb-init-' + i + '-' + Date.now();
            return out;
        });
    }

    function findFieldDef(type, fieldLibrary, categoryOrder) {
        for (var c = 0; c < categoryOrder.length; c++) {
            var list = fieldLibrary[categoryOrder[c]];
            if (!list) continue;
            for (var i = 0; i < list.length; i++) {
                if (list[i].type === type) return list[i];
            }
        }
        return null;
    }

    function choicesToLines(obj) {
        if (!obj || typeof obj !== 'object') return '';
        return Object.keys(obj).map(function (k) { return k + ' : ' + (obj[k] || k); }).join('\n');
    }
    function linesToChoices(text) {
        var obj = {};
        if (!text || typeof text !== 'string') return obj;
        text.split('\n').forEach(function (line) {
            line = line.trim();
            if (!line) return;
            var i = line.indexOf(' : ');
            if (i > 0) {
                obj[line.slice(0, i).trim()] = line.slice(i + 3).trim();
            } else {
                obj[line] = line;
            }
        });
        return obj;
    }

    var app = window.Vue.createApp({
        data: function () {
            return {
                config: config,
                activeFields: ensureUids(JSON.parse(JSON.stringify(config.initialFields || []))),
                selectedIndex: -1,
                librarySearch: '',
                expandedCategories: config.categoryOrder.slice(),
                settingsTab: 'general',
                isDropActive: false,
                libraryInsertIndex: 0,
                insertLineTop: 16,
                lastDragPayload: null,
                saving: false,
                sortableInstance: null,
                canvasListKey: 0
            };
        },
        computed: {
            visibleCategories: function () {
                var q = this.librarySearch.trim().toLowerCase();
                if (!q) return this.config.categoryOrder;
                return this.config.categoryOrder.filter(function (cat) {
                    var list = this.config.fieldLibrary[cat];
                    if (!list) return false;
                    return list.some(function (f) {
                        return (f.label || '').toLowerCase().indexOf(q) >= 0 || (f.type || '').toLowerCase().indexOf(q) >= 0;
                    });
                }.bind(this));
            },
            selectedField: function () {
                var i = this.selectedIndex;
                if (i < 0 || i >= this.activeFields.length) return {};
                return this.activeFields[i];
            },
            settingsVisible: function () {
                return this.selectedIndex >= 0 && this.selectedIndex < this.activeFields.length;
            },
            currentTabFormItems: function () {
                var field = this.selectedField;
                if (!field || !field.type) return [];
                var def = findFieldDef(field.type, this.config.fieldLibrary, this.config.categoryOrder);
                var schema = (def && def.config_schema) ? def.config_schema : {};
                var tabKeys = TAB_KEYS[this.settingsTab] || [];
                var items = [];
                for (var k = 0; k < tabKeys.length; k++) {
                    var key = tabKeys[k];
                    if (!schema[key]) continue;
                    var meta = schema[key];
                    var type = meta.type || 'string';
                    var inputType = type === 'boolean' ? 'boolean' : type === 'integer' || type === 'number' ? 'number' : 'text';
                    if (key === 'choices') inputType = 'choices';
                    else if (key === 'sub_fields' || key === 'layouts' || key === 'conditional_logic') inputType = 'json';
                    else if (key === 'post_type' && (field.type === 'post_object' || field.type === 'relationship')) inputType = 'post_type';
                    else if (key === 'taxonomy' && (field.type === 'post_object' || field.type === 'relationship')) inputType = 'taxonomy';
                    else if (key === 'return_format') {
                        inputType = 'select';
                        meta.options = [{ value: 'id', label: 'Post ID(s)' }, { value: 'object', label: 'Post object(s)' }];
                    } else if (key === 'toolbar' && field.type === 'wysiwyg') {
                        inputType = 'select';
                        meta.options = [{ value: 'full', label: 'Full' }, { value: 'basic', label: 'Basic' }];
                    } else if (key === 'tabs' && field.type === 'wysiwyg') {
                        inputType = 'select';
                        meta.options = [{ value: 'all', label: 'Visual & Text' }, { value: 'visual', label: 'Visual only' }, { value: 'text', label: 'Text only' }];
                    }
                    var label = key.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
                    items.push({ key: key, label: label, inputType: inputType, meta: meta, rows: key === 'instructions' || key === 'description' ? 2 : 1 });
                }
                if (this.settingsTab === 'general' && !schema.label) {
                    items.unshift({ key: 'label', label: 'Label', inputType: 'text', meta: {}, rows: 1 });
                }
                if (this.settingsTab === 'general' && !schema.name) {
                    items.unshift({ key: 'name', label: 'Name (key)', inputType: 'text', meta: {}, rows: 1 });
                }
                if (this.settingsTab === 'validation' && !schema.required) {
                    items.unshift({ key: 'required', label: 'Required', inputType: 'boolean', meta: {}, rows: 1 });
                }
                return items;
            },
            // For modular mode: master toggle for \"show on creation\" on all fields
            allShowOnCreationSelected: {
                get: function () {
                    if (!this.activeFields.length) return false;
                    // Default behaviour: if flag is undefined, treat as true (backwards compatible)
                    return this.activeFields.every(function (f) {
                        return f.show_on_creation !== false;
                    });
                },
                set: function (val) {
                    var enable = !!val;
                    this.activeFields.forEach(function (f) {
                        f.show_on_creation = enable;
                    });
                }
            }
        },
        methods: {
            getChoicesText: function (key) {
                var f = this.selectedField;
                return f && f[key] !== undefined ? choicesToLines(f[key]) : '';
            },
            setChoicesText: function (key, text) {
                var f = this.selectedField;
                if (f) f[key] = linesToChoices(text);
            },
            getJsonText: function (key) {
                var f = this.selectedField;
                if (!f) return '';
                var v = f[key];
                return typeof v === 'object' && v !== null ? JSON.stringify(v, null, 2) : (Array.isArray(v) ? JSON.stringify(v, null, 2) : '[]');
            },
            setJsonText: function (key, text) {
                var f = this.selectedField;
                if (!f) return;
                try {
                    f[key] = JSON.parse(text || '[]');
                } catch (e) {
                    f[key] = [];
                }
            },
            togglePostType: function (key, value, checked) {
                var f = this.selectedField;
                if (!f) return;
                if (!Array.isArray(f[key])) f[key] = [];
                var i = f[key].indexOf(value);
                if (checked && i < 0) f[key].push(value);
                else if (!checked && i >= 0) f[key].splice(i, 1);
            },
            toggleTaxonomy: function (key, value, checked) {
                var f = this.selectedField;
                if (!f) return;
                if (!Array.isArray(f[key])) f[key] = [];
                var i = f[key].indexOf(value);
                if (checked && i < 0) f[key].push(value);
                else if (!checked && i >= 0) f[key].splice(i, 1);
            },
            toggleCategory: function (cat) {
                var i = this.expandedCategories.indexOf(cat);
                if (i >= 0) this.expandedCategories.splice(i, 1);
                else this.expandedCategories.push(cat);
            },
            filteredLibraryByCategory: function (cat) {
                var list = this.config.fieldLibrary[cat] || [];
                var q = this.librarySearch.trim().toLowerCase();
                if (!q) return list;
                return list.filter(function (f) {
                    return (f.label || '').toLowerCase().indexOf(q) >= 0 || (f.type || '').toLowerCase().indexOf(q) >= 0;
                });
            },
            getFieldIcon: function (field) {
                if (!field || !field.type) return 'fas fa-circle';
                var def = findFieldDef(field.type, this.config.fieldLibrary, this.config.categoryOrder);
                return 'fas ' + (def && def.icon ? def.icon : 'fa-circle');
            },
            addField: function (f) {
                var newField = { _uid: nextUid(), type: f.type, label: f.label || f.type, name: slugify(f.label || f.type) };
                if (f.config_schema) {
                    Object.keys(f.config_schema).forEach(function (k) {
                        if (f.config_schema[k].default !== undefined) newField[k] = f.config_schema[k].default;
                    });
                }
                this.activeFields.push(newField);
                this.selectedIndex = this.activeFields.length - 1;
                this.settingsTab = 'general';
                var self = this;
                this.$nextTick(function () {
                    self.reinitSortable();
                });
            },
            onLibraryDragStart: function (e, f) {
                this.lastDragPayload = { type: f.type, label: f.label || f.type, icon: f.icon || 'fa-circle' };
                e.dataTransfer.setData('application/json', JSON.stringify(this.lastDragPayload));
                e.dataTransfer.effectAllowed = 'copy';
            },
            onCanvasDragOver: function (e) {
                this.isDropActive = true;
                var rect = this.$refs.dropZoneRef.getBoundingClientRect();
                var y = e.clientY - rect.top;
                var rows = this.$refs.canvasListRef ? this.$refs.canvasListRef.querySelectorAll('li') : [];
                if (rows.length === 0) {
                    this.libraryInsertIndex = 0;
                    this.insertLineTop = 16;
                    return;
                }
                var i = 0;
                for (; i < rows.length; i++) {
                    var r = rows[i].getBoundingClientRect();
                    if (e.clientY < r.top + r.height / 2) break;
                }
                this.libraryInsertIndex = i;
                if (i === 0) this.insertLineTop = rows[0].getBoundingClientRect().top - rect.top - 2;
                else this.insertLineTop = rows[i - 1].getBoundingClientRect().bottom - rect.top + 2;
            },
            onCanvasDragLeave: function () {
                this.isDropActive = false;
            },
            onCanvasDrop: function (e) {
                this.isDropActive = false;
                var data = null;
                try {
                    var raw = e.dataTransfer.getData('application/json') || e.dataTransfer.getData('text/plain');
                    if (raw) data = JSON.parse(raw);
                } catch (err) {}
                if (!data && this.lastDragPayload) data = this.lastDragPayload;
                if (!data || !data.type) return;
                var def = findFieldDef(data.type, this.config.fieldLibrary, this.config.categoryOrder);
                var newField = { _uid: nextUid(), type: data.type, label: data.label || data.type, name: slugify(data.label || data.type) };
                if (def && def.config_schema) {
                    Object.keys(def.config_schema).forEach(function (k) {
                        if (def.config_schema[k].default !== undefined) newField[k] = def.config_schema[k].default;
                    });
                }
                var idx = this.libraryInsertIndex;
                this.activeFields.splice(idx, 0, newField);
                this.selectedIndex = idx;
                this.settingsTab = 'general';
                this.lastDragPayload = null;
                var self = this;
                this.$nextTick(function () {
                    self.reinitSortable();
                });
            },
            selectField: function (index) {
                if (index >= 0 && index < this.activeFields.length) {
                    this.selectedIndex = index;
                    this.settingsTab = 'general';
                }
            },
            toggleSettings: function (index) {
                if (this.selectedIndex === index) {
                    this.selectedIndex = -1;
                } else {
                    this.selectedIndex = index;
                    this.settingsTab = 'general';
                }
            },
            closeSettings: function () {
                this.selectedIndex = -1;
            },
            duplicateField: function (index) {
                var copy = JSON.parse(JSON.stringify(this.activeFields[index]));
                copy._uid = nextUid();
                copy.name = (copy.name || copy.label || 'field') + '_copy';
                copy.label = (copy.label || 'Field') + ' (copy)';
                this.activeFields.splice(index + 1, 0, copy);
                this.selectedIndex = index + 1;
                this.settingsTab = 'general';
            },
            deleteField: function (index) {
                var self = this;
                var label = this.activeFields[index].label || this.activeFields[index].name || this.activeFields[index].type;
                var p = window.ModularModal && window.ModularModal.confirm
                    ? window.ModularModal.confirm('Delete field "' + label + '"? This cannot be undone.')
                    : Promise.resolve(confirm('Delete field "' + label + '"? This cannot be undone.'));
                Promise.resolve(p).then(function (confirmed) {
                    if (!confirmed) return;
                    self.activeFields.splice(index, 1);
                    self.selectedIndex = self.activeFields.length ? Math.min(index, self.activeFields.length - 1) : -1;
                    if (self.selectedIndex >= 0) self.settingsTab = 'general';
                });
            },
            saveSchema: function () {
                var self = this;
                if (this.saving) return;
                this.saving = true;
                var payload = new FormData();
                payload.append('id', this.config.postTypeId);
                payload.append('mode', this.config.mode);
                var fieldsForSave = this.activeFields.map(function (f) {
                    var o = {};
                    for (var k in f) if (k !== '_uid') o[k] = f[k];
                    return o;
                });
                payload.append('schema', JSON.stringify({ fields: fieldsForSave }));
                if (this.config.csrfToken) payload.append('_csrf_token', this.config.csrfToken);
                fetch(this.config.saveUrl, {
                    method: 'POST',
                    body: payload,
                    credentials: 'same-origin'
                })
                    .then(function (r) {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(function (data) {
                        self.saving = false;
                        if (data && data.error) {
                            (window.ModularModal && window.ModularModal.alert ? window.ModularModal.alert('Error: ' + data.error) : alert('Error: ' + data.error));
                        } else {
                            (window.ModularModal && window.ModularModal.alert ? window.ModularModal.alert('Schema saved.') : alert('Schema saved.'));
                        }
                    })
                    .catch(function (err) {
                        self.saving = false;
                        (window.ModularModal && window.ModularModal.alert ? window.ModularModal.alert('Request failed: ' + (err && err.message ? err.message : 'Check console.')) : alert('Request failed: ' + (err && err.message ? err.message : 'Check console.')));
                    });
            },
            reinitSortable: function () {
                var listEl = this.$refs.canvasListRef;
                if (!listEl || this.activeFields.length === 0) return;
                if (this.sortableInstance) {
                    this.sortableInstance.destroy();
                    this.sortableInstance = null;
                }
                if (typeof window.ModularSortable === 'undefined' || !window.ModularSortable.create) return;
                var self = this;
                this.sortableInstance = window.ModularSortable.create(listEl, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: function (evt) {
                        var oldIndex = (typeof evt.oldIndex === 'number') ? evt.oldIndex : parseInt((evt.item && evt.item.getAttribute('data-index')) || '-1', 10);
                        var newIndex;
                        if (typeof evt.newIndex === 'number') {
                            newIndex = evt.newIndex;
                        } else if (evt.item && evt.item.parentNode) {
                            newIndex = Array.prototype.indexOf.call(evt.item.parentNode.children, evt.item);
                        } else {
                            newIndex = -1;
                        }
                        if (isNaN(oldIndex)) oldIndex = -1;
                        if (oldIndex === newIndex) return;
                        var n = self.activeFields.length;
                        if (oldIndex < 0 || oldIndex >= n || newIndex < 0 || newIndex >= n) {
                            self.canvasListKey = (self.canvasListKey || 0) + 1;
                            self.$nextTick(function () { self.reinitSortable(); });
                            return;
                        }
                        var arr = self.activeFields.slice();
                        var item = arr.splice(oldIndex, 1)[0];
                        arr.splice(newIndex, 0, item);
                        self.activeFields = arr;
                        self.selectedIndex = -1;
                        self.canvasListKey = (self.canvasListKey || 0) + 1;
                        self.$nextTick(function () { self.reinitSortable(); });
                    }
                });
            }
        },
        mounted: function () {
            var self = this;
            this.$nextTick(function () {
                self.reinitSortable();
            });
        }
    });

    app.mount('#field-builder-vue-app');
})();
