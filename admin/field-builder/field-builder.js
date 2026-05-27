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
        general: ['label', 'name', 'key', 'instructions', 'post_type', 'limit', 'default_value', 'sub_fields', 'layouts', 'button_label'],
        validation: ['required', 'min', 'max', 'step', 'maxlength', 'pattern', 'allow_null', 'multiple'],
        display: ['placeholder', 'prepend', 'append', 'rows', 'wrapper_class', 'input_class', 'ui', 'return_format', 'choices', 'layout', 'inner_tabs_layout', 'toolbar', 'media_upload', 'tabs', 'delay'],
        logic: ['conditional_logic']
    };

    function slugify(s) {
        return String(s).toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '') || 'field';
    }
    function nextUid() {
        return 'fb-' + Date.now() + '-' + Math.random().toString(36).slice(2, 9);
    }
    /** Remove builder-only _uid keys from schema payload (recursive). */
    function omitUidDeep(val) {
        if (val === null || typeof val !== 'object') return val;
        if (Array.isArray(val)) return val.map(omitUidDeep);
        var o = {};
        for (var k in val) {
            if (k === '_uid') continue;
            o[k] = omitUidDeep(val[k]);
        }
        return o;
    }
    /** Strip keys that must not trigger shared-field propagate prompts. */
    function snapshotComparableField(field) {
        var c = omitUidDeep(field);
        delete c._shared_id;
        delete c._share_with_post_types;
        delete c.attach_to_new_post_types;
        return c;
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

    function isTabField(f) {
        return !!(f && String(f.type || '').toLowerCase() === 'tab');
    }

    /** Segments for canvas grouping: fields before first tab + one segment per tab + following non-tab fields. */
    function computeBuilderSegments(activeFields) {
        var fields = activeFields || [];
        var n = fields.length;
        var segments = [];
        var leadingIdx = [];
        var i = 0;
        while (i < n && !isTabField(fields[i])) {
            leadingIdx.push(i);
            i++;
        }
        if (leadingIdx.length) {
            segments.push({ tabIdx: null, fieldIndices: leadingIdx });
        }
        while (i < n) {
            if (!isTabField(fields[i])) {
                i++;
                continue;
            }
            var tabIdx = i;
            i++;
            var chunk = [];
            while (i < n && !isTabField(fields[i])) {
                chunk.push(i);
                i++;
            }
            segments.push({ tabIdx: tabIdx, fieldIndices: chunk });
        }
        return segments;
    }

    function sameUidOrder(a, b) {
        if (!a || !b || a.length !== b.length) return false;
        for (var i = 0; i < a.length; i++) {
            if ((a[i] && a[i]._uid) !== (b[i] && b[i]._uid)) return false;
        }
        return true;
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
                /** Snapshot of shared fields at load time for change detection */
                sharedFieldSnapshots: {},
                sortableInstance: null,
                /** Nested Sortable when tab-grouped canvas is active */
                _fbGroupSortables: null,
                _fbSectionSortable: null,
                canvasListKey: 0,
                /** Flat index where the next library-drop field will be inserted */
                libraryInsertFlatIndex: 0
            };
        },
        computed: {
            canvasHasGroupedLayout: function () {
                return (this.activeFields || []).some(isTabField);
            },
            builderSegments: function () {
                return computeBuilderSegments(this.activeFields || []);
            },
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
                    if (field.type === 'group' && key === 'sub_fields') continue;
                    if (!schema[key]) continue;
                    var meta = schema[key];
                    var type = meta.type || 'string';
                    var inputType = type === 'boolean' ? 'boolean' : type === 'integer' || type === 'number' ? 'number' : 'text';
                    if (key === 'choices') inputType = 'choices';
                    else if (key === 'sub_fields' || key === 'layouts' || key === 'conditional_logic') inputType = 'json';
                    else if (key === 'post_type' && (field.type === 'post_object' || field.type === 'relationship' || field.type === 'latest_posts')) inputType = 'post_type';
                    else if (key === 'taxonomy' && (field.type === 'post_object' || field.type === 'relationship')) inputType = 'taxonomy';
                    else if (key === 'return_format' && field.type === 'latest_posts') {
                        inputType = 'select';
                        meta.options = [{ value: 'array', label: 'Array' }, { value: 'id', label: 'ID' }, { value: 'json', label: 'JSON' }];
                    }
                    else if (key === 'return_format') {
                        inputType = 'select';
                        meta.options = [{ value: 'id', label: 'Post ID(s)' }, { value: 'object', label: 'Post object(s)' }];
                    } else if (key === 'toolbar' && field.type === 'wysiwyg') {
                        inputType = 'select';
                        meta.options = [{ value: 'full', label: 'Full' }, { value: 'basic', label: 'Basic' }];
                    } else if (key === 'tabs' && field.type === 'wysiwyg') {
                        inputType = 'select';
                        meta.options = [{ value: 'all', label: 'Visual & Text' }, { value: 'visual', label: 'Visual only' }, { value: 'text', label: 'Text only' }];
                    } else if (key === 'inner_tabs_layout' && field.type === 'group') {
                        inputType = 'select';
                        meta.options = [
                            { value: 'stacked', label: 'Stacked section titles (classic)' },
                            { value: 'top', label: 'Top tabs (horizontal)' },
                            { value: 'sidebar', label: 'Left sidebar tabs' },
                        ];
                    }
                    var label = key.replace(/_/g, ' ');
                    if (key === 'inner_tabs_layout') {
                        label = 'Inner tabs layout';
                    } else {
                        label = label.replace(/\b\w/g, function (c) { return c.toUpperCase(); });
                    }
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
            visibleNonTabFlatIndices: function () {
                var out = [];
                (this.activeFields || []).forEach(function (f, i) {
                    if (!isTabField(f)) out.push(i);
                });
                return out;
            },
            /**
             * Maps library drop line position (0 = before first canvas row) to activeFields splice index.
             */
            flatIndexForLibraryRowInsert: function (rowSlot) {
                var vis = this.visibleNonTabFlatIndices();
                var af = this.activeFields || [];
                if (vis.length === 0) return af.length;
                if (rowSlot <= 0) return vis[0];
                if (rowSlot >= vis.length) return af.length;
                return vis[rowSlot];
            },
            addFieldGroupSection: function () {
                var tabDef = findFieldDef('tab', this.config.fieldLibrary, this.config.categoryOrder);
                if (!tabDef) return;
                this.addField(tabDef);
            },
            destroySortables: function () {
                if (this.sortableInstance) {
                    try { this.sortableInstance.destroy(); } catch (err) {}
                    this.sortableInstance = null;
                }
                if (this._fbSectionSortable) {
                    try { this._fbSectionSortable.destroy(); } catch (err2) {}
                    this._fbSectionSortable = null;
                }
                (this._fbGroupSortables || []).forEach(function (s) {
                    try { s.destroy(); } catch (err3) {}
                });
                this._fbGroupSortables = [];
            },
            syncActiveFieldsFromGroupedCanvas: function () {
                var root = this.$refs.canvasGroupsRef;
                if (!root) return;
                var uidMap = {};
                (this.activeFields || []).forEach(function (f) {
                    if (f && f._uid) uidMap[f._uid] = f;
                });
                var sections = root.querySelectorAll(':scope > .field-builder-section');
                var out = [];
                for (var s = 0; s < sections.length; s++) {
                    var sec = sections[s];
                    var isLeading = sec.getAttribute('data-segment-leading') === '1';
                    var tabUid = sec.getAttribute('data-tab-uid') || '';
                    if (!isLeading && tabUid) {
                        var tField = uidMap[tabUid];
                        if (tField) out.push(tField);
                    }
                    var lis = sec.querySelector('.field-builder-group-inner');
                    if (lis) {
                        lis.querySelectorAll(':scope > li[data-af-uid]').forEach(function (liEl) {
                            var uid = liEl.getAttribute('data-af-uid');
                            var fld = uidMap[uid];
                            if (fld) out.push(fld);
                        });
                    }
                }
                if (out.length !== (this.activeFields || []).length) {
                    if (typeof console !== 'undefined') {
                        console.warn('Field builder: DOM sync length mismatch, skip.');
                    }
                    return;
                }
                if (sameUidOrder(this.activeFields, out)) return;
                this.activeFields = out;
                this.canvasListKey = (this.canvasListKey || 0) + 1;
                this.selectedIndex = -1;
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
                this.canvasListKey = (this.canvasListKey || 0) + 1;
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
                var rows = this.$refs.dropZoneRef ? this.$refs.dropZoneRef.querySelectorAll('.active-field-row') : [];
                if (rows.length === 0) {
                    this.libraryInsertIndex = 0;
                    this.libraryInsertFlatIndex = this.flatIndexForLibraryRowInsert(0);
                    this.insertLineTop = 16;
                    return;
                }
                var i = 0;
                for (; i < rows.length; i++) {
                    var r = rows[i].getBoundingClientRect();
                    if (e.clientY < r.top + r.height / 2) break;
                }
                this.libraryInsertIndex = i;
                this.libraryInsertFlatIndex = this.flatIndexForLibraryRowInsert(i);
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
                var idx = this.libraryInsertFlatIndex;
                if (typeof idx !== 'number' || idx < 0 || idx > this.activeFields.length) idx = this.activeFields.length;
                this.activeFields.splice(idx, 0, newField);
                this.selectedIndex = idx;
                this.settingsTab = 'general';
                this.lastDragPayload = null;
                this.canvasListKey = (this.canvasListKey || 0) + 1;
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
                var self = this;
                var copy = JSON.parse(JSON.stringify(this.activeFields[index]));
                copy._uid = nextUid();
                copy.name = (copy.name || copy.label || 'field') + '_copy';
                copy.label = (copy.label || 'Field') + ' (copy)';
                this.activeFields.splice(index + 1, 0, copy);
                this.selectedIndex = index + 1;
                this.settingsTab = 'general';
                this.canvasListKey = (this.canvasListKey || 0) + 1;
                this.$nextTick(function () { self.reinitSortable(); });
            },
            /** Unique `name` among sibling sub_fields of a group. */
            uniqueNameInSubFields: function (subFields, base) {
                var names = {};
                (subFields || []).forEach(function (s) {
                    if (s && s.name) names[s.name] = true;
                });
                var n = base || 'field';
                var i = 2;
                while (names[n]) {
                    n = base + '_' + i;
                    i++;
                }
                return n;
            },
            ensureGroupSubFields: function (groupField) {
                if (!groupField || groupField.type !== 'group') return;
                if (!Array.isArray(groupField.sub_fields)) groupField.sub_fields = [];
            },
            onGroupSubFieldAddSelect: function (e) {
                var sel = e && e.target;
                var type = sel && sel.value;
                if (!type) return;
                var f = this.selectedField;
                if (!f || f.type !== 'group') {
                    sel.value = '';
                    return;
                }
                this.ensureGroupSubFields(f);
                var def = findFieldDef(type, this.config.fieldLibrary, this.config.categoryOrder);
                if (!def) {
                    sel.value = '';
                    return;
                }
                var baseName = slugify(def.label || def.type || 'field');
                var nf = {
                    _uid: nextUid(),
                    type: def.type,
                    label: def.label || def.type,
                    name: this.uniqueNameInSubFields(f.sub_fields, baseName)
                };
                if (def.config_schema) {
                    Object.keys(def.config_schema).forEach(function (k) {
                        if (def.config_schema[k].default !== undefined) nf[k] = def.config_schema[k].default;
                    });
                }
                this.ensureGroupSubFields(f);
                f.sub_fields.push(nf);
                sel.value = '';
            },
            removeGroupSubField: function (subIndex) {
                var f = this.selectedField;
                if (!f || f.type !== 'group' || !Array.isArray(f.sub_fields)) return;
                f.sub_fields.splice(subIndex, 1);
            },
            moveGroupSubField: function (subIndex, delta) {
                var f = this.selectedField;
                if (!f || f.type !== 'group' || !Array.isArray(f.sub_fields)) return;
                var j = subIndex + delta;
                if (j < 0 || j >= f.sub_fields.length) return;
                var arr = f.sub_fields;
                var t = arr[subIndex];
                arr.splice(subIndex, 1);
                arr.splice(j, 0, t);
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
                    self.canvasListKey = (self.canvasListKey || 0) + 1;
                    self.$nextTick(function () { self.reinitSortable(); });
                });
            },
            getSharedPostTypeNames: function (sharedId) {
                var ptIds = (this.config.sharedFieldPostTypes || {})[sharedId] || [];
                var allPts = this.config.otherPostTypes || [];
                var names = [];
                for (var i = 0; i < allPts.length; i++) {
                    if (ptIds.indexOf(allPts[i].id) >= 0 || ptIds.indexOf(String(allPts[i].id)) >= 0) {
                        names.push(allPts[i].name);
                    }
                }
                return names.length ? names.join(', ') : 'andere post types';
            },
            detachSharedField: function (index) {
                var f = this.activeFields[index];
                if (f && f._shared_id) {
                    delete f._shared_id;
                    this.activeFields.splice(index, 1, Object.assign({}, f));
                }
            },
            toggleShareWith: function (index, ptId, checked) {
                var f = this.activeFields[index];
                if (!f) return;
                if (!Array.isArray(f._share_with_post_types)) f._share_with_post_types = [];
                var i = f._share_with_post_types.indexOf(ptId);
                if (checked && i < 0) f._share_with_post_types.push(ptId);
                else if (!checked && i >= 0) f._share_with_post_types.splice(i, 1);
            },
            /** Build shared_actions for save: detect changed shared fields and prompt user */
            buildSharedActions: function () {
                var self = this;
                var sharedFields = this.activeFields.filter(function (f) { return f._shared_id; });
                if (!sharedFields.length) return Promise.resolve([]);

                var changedFields = [];
                for (var i = 0; i < sharedFields.length; i++) {
                    var f = sharedFields[i];
                    var sid = f._shared_id;
                    var snapshot = this.sharedFieldSnapshots[sid];
                    if (!snapshot) continue;
                    var current = snapshotComparableField(f);
                    if (JSON.stringify(current) !== JSON.stringify(snapshot)) {
                        changedFields.push(f);
                    }
                }

                if (!changedFields.length) return Promise.resolve([]);

                var actions = [];
                var chain = Promise.resolve();
                changedFields.forEach(function (f) {
                    chain = chain.then(function () {
                        var label = f.label || f.name || f.type;
                        var names = self.getSharedPostTypeNames(f._shared_id);
                        var msg = 'Het veld "' + label + '" is gedeeld met ' + names + '.\n\nWijzigingen doorvoeren naar alle gekoppelde post types?\n\n[OK] = Doorvoeren\n[Annuleren] = Lostrekken (dit veld wordt onafhankelijk)';
                        var p = window.ModularModal && window.ModularModal.confirm
                            ? window.ModularModal.confirm(msg)
                            : Promise.resolve(confirm(msg));
                        return Promise.resolve(p).then(function (ok) {
                            actions.push({
                                _shared_id: f._shared_id,
                                action: ok ? 'propagate' : 'detach'
                            });
                            if (!ok) {
                                delete f._shared_id;
                            }
                        });
                    });
                });
                return chain.then(function () { return actions; });
            },
            saveSchema: function () {
                var self = this;
                if (this.saving) return;
                this.saving = true;

                this.buildSharedActions().then(function (sharedActions) {
                    var payload = new FormData();
                    payload.append('id', self.config.postTypeId);
                    payload.append('mode', self.config.mode);
                    var fieldsForSave = self.activeFields.map(function (f) {
                        return omitUidDeep(f);
                    });
                    payload.append('schema', JSON.stringify({ fields: fieldsForSave }));
                    if (self.config.csrfToken) payload.append('_csrf_token', self.config.csrfToken);
                    if (sharedActions.length) {
                        payload.append('shared_actions', JSON.stringify(sharedActions));
                    }
                    return fetch(self.config.saveUrl, {
                        method: 'POST',
                        body: payload,
                        credentials: 'same-origin'
                    });
                })
                    .then(function (r) {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(function (data) {
                        self.saving = false;
                        if (data && data.error) {
                            (window.ModularModal && window.ModularModal.alert('Error: ' + data.error));
                        } else {
                            (window.ModularModal && window.ModularModal.alert('Schema saved.'));
                            self.snapshotSharedFields();
                        }
                    })
                    .catch(function (err) {
                        self.saving = false;
                        (window.ModularModal && window.ModularModal.alert('Request failed: ' + (err && err.message ? err.message : 'Check the browser console.')));
                    });
            },
            snapshotSharedFields: function () {
                var snapshots = {};
                (this.activeFields || []).forEach(function (f) {
                    if (f._shared_id) {
                        snapshots[f._shared_id] = snapshotComparableField(f);
                    }
                });
                this.sharedFieldSnapshots = snapshots;
            },
            applySharedAttachDefaultsFromConfig: function () {
                var defaults = this.config.sharedFieldAttachDefaults || {};
                (this.activeFields || []).forEach(function (f) {
                    if (!f._shared_id) return;
                    var sid = f._shared_id;
                    if (typeof f.attach_to_new_post_types === 'undefined') {
                        f.attach_to_new_post_types = Object.prototype.hasOwnProperty.call(defaults, sid)
                            ? !!defaults[sid]
                            : false;
                    }
                });
            },
            reinitSortable: function () {
                var self = this;
                this.destroySortables();
                if (!this.activeFields.length) return;
                if (typeof window.ModularSortable === 'undefined' || !window.ModularSortable.create) return;
                var commonGhost = {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    handle: '.drag-handle',
                    forceFallback: true,
                    fallbackOnBody: true
                };

                function endFlat(evt) {
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

                this.$nextTick(function () {
                    if (!self.canvasHasGroupedLayout) {
                        var listEl = self.$refs.canvasListRef;
                        if (!listEl) return;
                        self.sortableInstance = window.ModularSortable.create(listEl, Object.assign({}, commonGhost, {
                            group: 'field-builder-groups',
                            onEnd: endFlat
                        }));
                        return;
                    }
                    var groupRoot = self.$refs.canvasGroupsRef;
                    if (!groupRoot) return;
                    self._fbGroupSortables = [];
                    groupRoot.querySelectorAll('.field-builder-group-inner').forEach(function (ul) {
                        var inst = window.ModularSortable.create(ul, Object.assign({}, commonGhost, {
                            group: 'field-builder-groups',
                            onEnd: function () {
                                self.syncActiveFieldsFromGroupedCanvas();
                                self.$nextTick(function () { self.reinitSortable(); });
                            }
                        }));
                        self._fbGroupSortables.push(inst);
                    });
                    self._fbSectionSortable = window.ModularSortable.create(groupRoot, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        handle: '.field-builder-section-handle',
                        draggable: '> .field-builder-section',
                        forceFallback: true,
                        fallbackOnBody: true,
                        onEnd: function () {
                            self.syncActiveFieldsFromGroupedCanvas();
                            self.$nextTick(function () { self.reinitSortable(); });
                        }
                    });
                });
            }
        },
        mounted: function () {
            var self = this;
            this.applySharedAttachDefaultsFromConfig();
            this.snapshotSharedFields();
            this.$nextTick(function () {
                self.reinitSortable();
            });
        },
        unmounted: function () {
            this.destroySortables();
        }
    });

    app.mount('#field-builder-vue-app');
})();
