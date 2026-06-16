/* ============================================================
   FieldSettings (§5C) — per-type configuration generated from the
   field's family. Bound directly to the selected field object so
   the registry components (MdSelect.choices, MdRelation.post_type,
   MdRange.min/max, …) immediately reflect the config. Mirrors the
   PHP config_schema families. Rendered inside the field builder's
   General tab and reused wherever a field is configured.
   ============================================================ */
(function () {
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const C = M.comps;

  const FAMILY = {
    choice: ['select', 'radio', 'checkbox', 'button_group'],
    relation: ['post_object', 'relationship', 'taxonomy', 'user'],
    number: ['number', 'range'],
    media: ['image', 'gallery', 'file', 'audio', 'video'],
    date: ['date', 'datetime', 'time'],
    nested: ['repeater', 'group'],
    text: ['text', 'textarea', 'email', 'url', 'password', 'search'],
  };
  const slug = (s) => (s || 'field').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
  const famOf = (type) => { for (const k in FAMILY) if (FAMILY[k].includes(type)) return k; return null; };

  C.FieldSettings = {
    name: 'FieldSettings',
    props: { field: { type: Object, required: true } },
    computed: {
      fam() { return famOf(this.field.type); },
      postTypes() { return (M.data && M.data.POST_TYPES) || []; },
      isSingle() { return this.field.type === 'post_object' || this.field.type === 'user' || !!this.field.single; },
    },
    methods: {
      set(k, v) { this.field[k] = v; },
      // choices
      choices() { if (!Array.isArray(this.field.choices)) this.field.choices = []; return this.field.choices; },
      addChoice() { this.choices().push({ value: '', label: '' }); },
      removeChoice(i) { this.choices().splice(i, 1); },
      moveChoice(i, d) { const a = this.choices(); const j = i + d; if (j < 0 || j >= a.length) return; const t = a[i]; a.splice(i, 1); a.splice(j, 0, t); },
      // relation post types
      ptOn(id) { return Array.isArray(this.field.post_type) && this.field.post_type.includes(id); },
      togglePT(id) { if (!Array.isArray(this.field.post_type)) this.field.post_type = []; const a = this.field.post_type; const i = a.indexOf(id); if (i >= 0) a.splice(i, 1); else a.push(id); },
      // nested sub-fields (repeater / group)
      subs() { if (!Array.isArray(this.field.sub_fields)) this.field.sub_fields = []; return this.field.sub_fields; },
      subTypes() { return ((M.data && M.data.FIELD_TYPES) || []).filter(t => !['tab', 'accordion', 'columns', 'message', 'divider', 'blocks', 'repeater', 'group'].includes(t.type)); },
      addSub(type) { if (!type) return; const m = ((M.data && M.data.TYPE_MAP) || {})[type] || { label: type }; const n = slug(m.label || type); const uid = (M.data && M.data.uid) ? M.data.uid('sf') : ('sf_' + Math.random().toString(36).slice(2, 7)); this.subs().push({ id: uid, label: m.label || type, name: n, key: n, type, w: 'full' }); },
      removeSub(i) { this.subs().splice(i, 1); },
      moveSub(i, d) { const a = this.subs(); const j = i + d; if (j < 0 || j >= a.length) return; const t = a[i]; a.splice(i, 1); a.splice(j, 0, t); },
      typeMeta(t) { return ((M.data && M.data.TYPE_MAP) || {})[t] || { icon: 'dot', cat: 'util' }; },
      tcolorOf(t) { return ((M.data && M.data.CAT_VAR) || {})[this.typeMeta(t).cat] || 'var(--ink-3)'; },
    },
    template: `
    <div class="fset" v-if="fam">
      <!-- CHOICE -->
      <template v-if="fam==='choice'">
        <label class="field-label">Choices</label>
        <div class="fset-choices">
          <div class="fset-choice" v-for="(c,i) in choices()" :key="i">
            <span class="drag-handle" style="color:var(--ink-4)"><Icon name="drag" :size="13"/></span>
            <input class="inp" v-model="c.value" placeholder="value">
            <input class="inp" v-model="c.label" placeholder="Label">
            <button class="iconbtn iconbtn--sm" @click="moveChoice(i,-1)" title="Up"><Icon name="chevUp" :size="13"/></button>
            <button class="iconbtn iconbtn--sm" @click="moveChoice(i,1)" title="Down"><Icon name="chevDown" :size="13"/></button>
            <button class="iconbtn iconbtn--sm is-danger" @click="removeChoice(i)"><Icon name="x" :size="13"/></button>
          </div>
          <button class="btn btn--ghost btn--sm" @click="addChoice"><Icon name="plus" :size="14"/> Add choice</button>
        </div>
        <div class="field" style="margin-top:12px" v-if="['select','radio','button_group'].includes(field.type)">
          <label class="field-label">Default</label>
          <UiSelect :modelValue="field.default||''" @update:modelValue="v=>set('default',v)"><option value="">— none —</option><option v-for="c in choices()" :key="c.value" :value="c.value">{{ c.label||c.value }}</option></UiSelect>
        </div>
      </template>

      <!-- RELATION -->
      <template v-else-if="fam==='relation'">
        <label class="field-label" v-if="field.type!=='taxonomy' && field.type!=='user'">Selectable post types</label>
        <div class="acf-rel-chips" style="padding:0;margin-bottom:12px" v-if="field.type!=='taxonomy' && field.type!=='user'">
          <button type="button" v-for="p in postTypes" :key="p.id" class="acf-rel-chip" :class="{'is-on':ptOn(p.id)}" @click="togglePT(p.id)">{{ p.name }}</button>
          <span v-if="!postTypes.length" class="t-meta">No post types.</span>
        </div>
        <div class="opt-row" v-if="!['post_object','user'].includes(field.type)">
          <div><strong>Allow multiple</strong><div class="field-hint">Off = single selection.</div></div>
          <UiSwitch :modelValue="!field.single" @update:modelValue="v=>set('single', !v)"/>
        </div>
        <div class="cfg-2"><div class="field"><label class="field-label">Min</label><input class="inp" type="number" :value="field.min" @input="set('min', Number($event.target.value)||null)" placeholder="0"></div><div class="field"><label class="field-label">Max</label><input class="inp" type="number" :value="field.max" @input="set('max', Number($event.target.value)||null)" placeholder="—"></div></div>
        <div class="field"><label class="field-label">Return format</label>
          <UiSelect :modelValue="field.return_format||'id'" @update:modelValue="v=>set('return_format',v)"><option value="id">ID</option><option value="object">Object</option><option value="array">Array</option></UiSelect>
        </div>
      </template>

      <!-- NUMBER / RANGE -->
      <template v-else-if="fam==='number'">
        <div class="cfg-2"><div class="field"><label class="field-label">Min</label><input class="inp" type="number" :value="field.min" @input="set('min', $event.target.value===''?null:Number($event.target.value))" placeholder="—"></div><div class="field"><label class="field-label">Max</label><input class="inp" type="number" :value="field.max" @input="set('max', $event.target.value===''?null:Number($event.target.value))" placeholder="—"></div></div>
        <div class="cfg-2"><div class="field"><label class="field-label">Step</label><input class="inp" type="number" :value="field.step" @input="set('step', $event.target.value===''?null:Number($event.target.value))" placeholder="1"></div><div class="field"><label class="field-label">Prepend / append</label><div class="inp-group"><input class="inp" :value="field.prepend" @input="set('prepend',$event.target.value)" placeholder="$"><input class="inp" :value="field.append" @input="set('append',$event.target.value)" placeholder="kg"></div></div></div>
      </template>

      <!-- MEDIA -->
      <template v-else-if="fam==='media'">
        <div class="field"><label class="field-label">Return format</label>
          <UiSelect :modelValue="field.return_format||'object'" @update:modelValue="v=>set('return_format',v)"><option value="object">Object</option><option value="url">URL</option><option value="id">ID</option></UiSelect>
        </div>
        <div class="field" v-if="['file','audio','video'].includes(field.type)"><label class="field-label">Allowed types (comma-sep)</label><input class="inp mono" :value="field.accept" @input="set('accept',$event.target.value)" placeholder="pdf, mp3"></div>
      </template>

      <!-- NESTED (repeater / group) — sub-fields editor -->
      <template v-else-if="fam==='nested'">
        <label class="field-label">Sub-fields</label>
        <div class="fset-subs">
          <div class="fset-sub" v-for="(s,i) in subs()" :key="s.id||i">
            <span class="lib-ico" :style="{color: tcolorOf(s.type)}"><Icon :name="typeMeta(s.type).icon" :size="14"/></span>
            <input class="inp" v-model="s.label" placeholder="Label">
            <input class="inp mono" v-model="s.name" placeholder="name">
            <UiSelect v-model="s.type"><option v-for="t in subTypes()" :key="t.type" :value="t.type">{{ t.label }}</option></UiSelect>
            <button class="iconbtn iconbtn--sm" @click="moveSub(i,-1)"><Icon name="chevUp" :size="13"/></button>
            <button class="iconbtn iconbtn--sm" @click="moveSub(i,1)"><Icon name="chevDown" :size="13"/></button>
            <button class="iconbtn iconbtn--sm is-danger" @click="removeSub(i)"><Icon name="x" :size="13"/></button>
          </div>
          <div v-if="!subs().length" class="t-meta" style="padding:6px 2px">No sub-fields yet.</div>
          <UiSelect :modelValue="''" @update:modelValue="addSub" placeholder="+ Add sub-field"><option v-for="t in subTypes()" :key="t.type" :value="t.type">{{ t.label }}</option></UiSelect>
        </div>
        <p class="field-hint">A repeater with sub-fields renders as repeatable rows (use it for tables, specs, etc.).</p>
      </template>

      <!-- DATE -->
      <template v-else-if="fam==='date'">
        <div class="field"><label class="field-label">Display format</label><input class="inp mono" :value="field.display_format" @input="set('display_format',$event.target.value)" :placeholder="field.type==='time'?'HH:mm':'D MMM YYYY'"></div>
        <div class="field"><label class="field-label">Return format</label><input class="inp mono" :value="field.return_format" @input="set('return_format',$event.target.value)" :placeholder="field.type==='time'?'HH:mm':'YYYY-MM-DD'"></div>
      </template>
    </div>`,
  };

  /* Validation controls adapt to the field family. */
  C.FieldValidation = {
    name: 'FieldValidation',
    props: { field: { type: Object, required: true } },
    computed: { fam() { return famOf(this.field.type); } },
    methods: { set(k, v) { this.field[k] = v; } },
    template: `
    <div>
      <div class="opt-row"><div><strong>Required</strong><div class="field-hint">Editors can't save without it.</div></div><UiSwitch :modelValue="!!field.required" @update:modelValue="v=>set('required',v)"/></div>
      <template v-if="fam==='text'">
        <div class="cfg-2"><div class="field"><label class="field-label">Min length</label><input class="inp" type="number" :value="field.minlength" @input="set('minlength', $event.target.value===''?null:Number($event.target.value))" placeholder="0"></div><div class="field"><label class="field-label">Max length</label><input class="inp" type="number" :value="field.maxlength" @input="set('maxlength', $event.target.value===''?null:Number($event.target.value))" placeholder="—"></div></div>
        <div class="field"><label class="field-label">Pattern (regex)</label><input class="inp mono" :value="field.pattern" @input="set('pattern',$event.target.value)" placeholder="^[a-z0-9-]+$"></div>
      </template>
      <template v-else-if="fam==='number'">
        <div class="cfg-2"><div class="field"><label class="field-label">Min value</label><input class="inp" type="number" :value="field.min" @input="set('min', $event.target.value===''?null:Number($event.target.value))"></div><div class="field"><label class="field-label">Max value</label><input class="inp" type="number" :value="field.max" @input="set('max', $event.target.value===''?null:Number($event.target.value))"></div></div>
      </template>
      <template v-else-if="fam==='relation' || field.type==='repeater'">
        <div class="cfg-2"><div class="field"><label class="field-label">Min items</label><input class="inp" type="number" :value="field.min" @input="set('min', $event.target.value===''?null:Number($event.target.value))"></div><div class="field"><label class="field-label">Max items</label><input class="inp" type="number" :value="field.max" @input="set('max', $event.target.value===''?null:Number($event.target.value))"></div></div>
      </template>
      <template v-else-if="fam==='date'">
        <div class="cfg-2"><div class="field"><label class="field-label">After</label><input class="inp mono" :value="field.min_date" @input="set('min_date',$event.target.value)" placeholder="YYYY-MM-DD"></div><div class="field"><label class="field-label">Before</label><input class="inp mono" :value="field.max_date" @input="set('max_date',$event.target.value)" placeholder="YYYY-MM-DD"></div></div>
      </template>
      <div class="field"><label class="field-label">Error message</label><input class="inp" :value="field.error_message" @input="set('error_message',$event.target.value)" placeholder="Please enter a valid value."></div>
    </div>`,
  };
})();
