/* ============================================================
   SHARED PRIMITIVES (§3) — the reusable layer the prototype lacks.
   Combobox · Calendar · SortableList · MediaPicker · Cropper.
   These power the field components (field-registry.js) and the
   FieldRenderer. MediaPicker/Cropper are functional stubs here and
   are fully wired to the media API in Fase 6.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;

  /* ---- Combobox: searchable single/multi select with drag-reorder ----
     Powers select-with-search and (later) relationship/post_object/taxonomy/user.
     options: [{value,label,meta?,icon?}] · modelValue: value | value[] */
  C.Combobox = {
    name: 'Combobox',
    props: {
      modelValue: { type: [String, Number, Array, null], default: null },
      options: { type: Array, default: () => [] },
      multiple: Boolean,
      placeholder: { type: String, default: 'Choose…' },
      searchPlaceholder: { type: String, default: 'Search…' },
      reorder: { type: Boolean, default: true },
    },
    emits: ['update:modelValue'],
    data() { return { open: false, q: '', drag: null }; },
    computed: {
      sel() { return this.multiple ? (Array.isArray(this.modelValue) ? this.modelValue.slice() : []) : (this.modelValue != null && this.modelValue !== '' ? [this.modelValue] : []); },
      byValue() { const m = {}; this.options.forEach(o => { m[o.value] = o; }); return m; },
      available() {
        const q = this.q.trim().toLowerCase();
        return this.options.filter(o => !this.sel.includes(o.value) && (!q || (o.label || '').toLowerCase().includes(q)));
      },
    },
    methods: {
      opt(v) { return this.byValue[v] || { value: v, label: String(v) }; },
      add(v) {
        if (this.multiple) { const next = this.sel.concat([v]); this.$emit('update:modelValue', next); }
        else { this.$emit('update:modelValue', v); this.open = false; }
        this.q = '';
      },
      remove(v) {
        if (this.multiple) this.$emit('update:modelValue', this.sel.filter(x => x !== v));
        else this.$emit('update:modelValue', null);
      },
      dStart(i, e) { this.drag = i; if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', String(i)); } catch (_) {} } },
      dDrop(i, e) { e.preventDefault(); if (this.drag == null) return; const a = this.sel.slice(); const it = a[this.drag]; a.splice(this.drag, 1); a.splice(i, 0, it); this.$emit('update:modelValue', a); this.drag = null; },
    },
    template: `
      <div class="rel">
        <div class="rel-list" v-if="sel.length">
          <div class="rel-item" v-for="(v,i) in sel" :key="v"
               :draggable="multiple && reorder" @dragstart="dStart(i,$event)" @dragover.prevent @drop="dDrop(i,$event)">
            <Icon v-if="multiple && reorder" name="drag" :size="14" class="drag-handle"/>
            <span v-if="opt(v).icon" class="rel-tax-ico"><Icon :name="opt(v).icon" :size="13"/></span>
            <div class="rel-item-main"><strong>{{ opt(v).label }}</strong><span class="t-meta" v-if="opt(v).meta">{{ opt(v).meta }}</span></div>
            <button class="iconbtn iconbtn--sm is-danger" @click="remove(v)"><Icon name="x" :size="13"/></button>
          </div>
        </div>
        <div class="rel-empty" v-else>{{ placeholder }}</div>
        <button class="rel-add-btn" v-if="multiple || !sel.length" @click="open=true"><Icon name="plus" :size="14"/> {{ placeholder }}</button>
        <template v-if="open">
          <div class="iconpick-scrim" @click="open=false"></div>
          <div class="rel-pop">
            <div class="fb-lib-search" style="margin:0 0 8px"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="q" :placeholder="searchPlaceholder" style="color:var(--ink)"></div>
            <div class="rel-pool">
              <button v-for="o in available" :key="o.value" class="rel-pool-item" @click="add(o.value)">
                <span v-if="o.icon" class="rel-tax-ico"><Icon :name="o.icon" :size="13"/></span>
                <div class="rel-item-main"><strong>{{ o.label }}</strong><span class="t-meta" v-if="o.meta">{{ o.meta }}</span></div>
                <Icon name="plus" :size="15" class="muted"/>
              </button>
              <div v-if="!available.length" class="t-meta" style="padding:10px;text-align:center">Nothing to add.</div>
            </div>
          </div>
        </template>
      </div>`,
  };

  /* ---- UiSelect: styled drop-in replacement for a native <select>.
     Usage: keep the <option> children, swap the tag:
       <UiSelect v-model="x"><option value="a">A</option></UiSelect>
     It parses the slotted <option> vnodes into a styled popover list so
     every dropdown in the admin shares one look. ---- */
  C.UiSelect = {
    name: 'UiSelect',
    props: { modelValue: { default: '' }, placeholder: { type: String, default: 'Choose…' } },
    emits: ['update:modelValue'],
    data() { return { open: false }; },
    computed: {
      options() {
        const out = [];
        const text = (ch) => Array.isArray(ch) ? ch.map(c => (typeof c === 'string' ? c : (c && typeof c.children === 'string' ? c.children : ''))).join('') : (typeof ch === 'string' ? ch : '');
        const walk = (nodes) => {
          (nodes || []).forEach(n => {
            if (!n) return;
            const ty = n.type;
            if (ty === 'option') {
              const val = (n.props && ('value' in n.props)) ? n.props.value : text(n.children);
              out.push({ value: val == null ? '' : val, label: text(n.children) || String(val) });
            } else if (Array.isArray(n.children)) { walk(n.children); }
          });
        };
        try { walk(this.$slots.default ? this.$slots.default() : []); } catch (e) {}
        return out;
      },
      current() { const c = this.options.find(o => String(o.value) === String(this.modelValue)); return c ? c.label : ''; },
    },
    methods: { pickV(v) { this.$emit('update:modelValue', v); this.open = false; } },
    template: `
      <div class="md-select">
        <button type="button" class="sel sel-trigger" :class="{'is-open':open}" @click="open=!open">
          <span :class="{muted:!current}">{{ current || placeholder }}</span>
          <Icon name="chevDown" :size="15" class="muted"/>
        </button>
        <template v-if="open">
          <div class="iconpick-scrim" @click="open=false"></div>
          <div class="md-select-pop">
            <button type="button" class="md-select-opt" v-for="(o,i) in options" :key="i" :class="{'is-on':String(modelValue)===String(o.value)}" @click="pickV(o.value)">
              <span>{{ o.label }}</span><Icon v-if="String(modelValue)===String(o.value)" name="check" :size="14"/>
            </button>
          </div>
        </template>
        <!-- keep the original <option> slot in the vnode tree (hidden) so options() can read it -->
        <span style="display:none"><slot/></span>
      </div>`,
  };

  /* ---- Calendar: date / datetime / time popover (extracted from the
     prototype's inline date control). Calendar math is plain JS here;
     date-fns is introduced for parsing/formatting in Fase 7. ----
     modelValue: ISO-ish string. mode: 'date' | 'datetime' | 'time'. */
  C.Calendar = {
    name: 'Calendar',
    props: { modelValue: { type: String, default: '' }, mode: { type: String, default: 'date' } },
    emits: ['update:modelValue'],
    data() {
      const now = new Date(2026, 4, 21);
      const d = this._parse(this.modelValue) || now;
      return { open: false, month: d.getMonth(), year: d.getFullYear(), sel: d.getDate(), time: this._timeOf(this.modelValue) || '10:00' };
    },
    computed: {
      cells() {
        const first = new Date(this.year, this.month, 1).getDay(); const lead = (first + 6) % 7;
        const dim = new Date(this.year, this.month + 1, 0).getDate(); const cells = [];
        for (let i = 0; i < lead; i++) cells.push(null);
        for (let d = 1; d <= dim; d++) cells.push(d);
        return cells;
      },
      display() {
        if (this.mode === 'time') return this.time;
        const mn = new Date(this.year, this.month, 1).toLocaleDateString('en-US', { month: 'short' });
        const base = this.sel + ' ' + mn + ' ' + this.year;
        return this.mode === 'datetime' ? base + ' · ' + this.time : base;
      },
    },
    methods: {
      _parse(s) { if (!s) return null; const m = String(s).match(/(\d{4})-(\d{2})-(\d{2})/); return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null; },
      _timeOf(s) { const m = String(s || '').match(/(\d{2}:\d{2})/); return m ? m[1] : null; },
      monthName() { return new Date(this.year, this.month, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }); },
      pick(d) { this.sel = d; this.emit(); if (this.mode === 'date') this.open = false; },
      emit() {
        const mm = String(this.month + 1).padStart(2, '0'); const dd = String(this.sel).padStart(2, '0');
        let out = this.year + '-' + mm + '-' + dd;
        if (this.mode === 'time') out = this.time;
        else if (this.mode === 'datetime') out = out + ' ' + this.time;
        this.$emit('update:modelValue', out);
      },
    },
    template: `
      <div class="dtf">
        <button type="button" class="dt-control" :class="{'is-open':open}" @click="open=!open">
          <Icon :name="mode==='time'?'clock':'calendar'" :size="15" class="muted"/>
          <span>{{ display }}</span>
          <Icon name="chevDown" :size="15" class="muted" style="margin-left:auto"/>
        </button>
        <template v-if="open">
          <div class="iconpick-scrim" @click="open=false"></div>
          <div class="dt-pop">
            <div class="cal" v-if="mode!=='time'">
              <div class="cal-head"><button class="iconbtn iconbtn--sm" @click="month=(month+11)%12"><Icon name="chevLeft" :size="16"/></button><strong>{{ monthName() }}</strong><button class="iconbtn iconbtn--sm" @click="month=(month+1)%12"><Icon name="chevRight" :size="16"/></button></div>
              <div class="cal-dow"><span v-for="d in ['Mo','Tu','We','Th','Fr','Sa','Su']" :key="d">{{ d }}</span></div>
              <div class="cal-grid"><template v-for="(c,i) in cells" :key="i"><span v-if="c===null" class="cal-cell cal-cell--empty"></span><button v-else type="button" class="cal-cell" :class="{'is-sel':sel===c}" @click="pick(c)">{{ c }}</button></template></div>
            </div>
            <div class="cal-time" v-if="mode!=='date'" :style="mode==='time'?{borderTop:0,paddingTop:0}:{}"><Icon name="clock" :size="15" class="muted"/><input class="inp" style="max-width:110px" v-model="time" @input="emit"><span class="t-meta">24h</span></div>
            <div class="dt-foot" v-if="mode!=='date'"><button class="btn btn--accent btn--sm" @click="open=false">Done</button></div>
          </div>
        </template>
      </div>`,
  };

  /* ---- SortableList: thin drag-reorder wrapper over a v-model array.
     Renders each item via the default slot ({ item, index }). ---- */
  C.SortableList = {
    name: 'SortableList',
    props: { modelValue: { type: Array, default: () => [] }, handle: { type: Boolean, default: true }, itemClass: { type: String, default: '' } },
    emits: ['update:modelValue'],
    data() { return { drag: null }; },
    methods: {
      start(i, e) { this.drag = i; if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', String(i)); } catch (_) {} } },
      drop(i, e) { e.preventDefault(); if (this.drag == null || this.drag === i) { this.drag = null; return; } const a = this.modelValue.slice(); const it = a[this.drag]; a.splice(this.drag, 1); a.splice(i, 0, it); this.$emit('update:modelValue', a); this.drag = null; },
    },
    template: `
      <div class="sortable">
        <div v-for="(item,i) in modelValue" :key="(item && item.id) || i" :class="itemClass"
             :draggable="!handle" @dragstart="!handle && start(i,$event)" @dragover.prevent @drop="drop(i,$event)">
          <slot :item="item" :index="i" :startDrag="(e)=>start(i,e)"></slot>
        </div>
      </div>`,
  };

  /* ---- MediaPicker: stub modal (Fase 6 wires it to the media API).
     Emits @pick with a media-like object; @close to dismiss. ---- */
  if (!C.MediaPicker) {
    C.MediaPicker = {
      name: 'MediaPicker',
      props: { multiple: Boolean, accept: { type: String, default: '' } },
      emits: ['pick', 'close'],
      data() { return { items: (window.__mdlr.data && window.__mdlr.data.MEDIA) || [] }; },
      methods: { choose(m) { this.$emit('pick', m); if (!this.multiple) this.$emit('close'); } },
      template: `
        <UiModal lg @close="$emit('close')">
          <div class="drawer-head"><strong style="font-size:15px;flex:1">Media library</strong><button class="iconbtn" @click="$emit('close')"><Icon name="x"/></button></div>
          <div class="drawer-body">
            <div class="media-grid" v-if="items.length">
              <button v-for="m in items" :key="m.id||m.name" class="media-cell" @click="choose(m)" :style="{background:'linear-gradient(135deg, hsl('+((m.hue||30))+' 28% 80%), hsl('+((m.hue||30)-8)+' 22% 60%))'}">
                <span class="media-cell-name mono">{{ m.name }}</span>
              </button>
            </div>
            <div v-else class="empty" style="padding:36px"><Icon name="image" :size="24"/><div>Media API wordt in Fase 6 aangesloten.</div></div>
          </div>
        </UiModal>`,
    };
  }

  /* ---- Cropper: stub modal (Fase 6 implements crop presets + variants). ---- */
  if (!C.ImageCropper) {
    C.ImageCropper = {
      name: 'ImageCropper',
      props: { img: Object },
      emits: ['close'],
      template: `
        <UiModal lg @close="$emit('close')">
          <div class="drawer-head"><strong style="font-size:15px;flex:1">Crop image</strong><button class="iconbtn" @click="$emit('close')"><Icon name="x"/></button></div>
          <div class="drawer-body"><div class="empty" style="padding:36px"><Icon name="crop" :size="24"/><div>Cropper komt in Fase 6 (presets + variants).</div></div></div>
          <div class="drawer-foot"><button class="btn" @click="$emit('close')">Done</button></div>
        </UiModal>`,
    };
  }
})();
