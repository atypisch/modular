/* ============================================================
   FRONTEND FieldRegistry (§3) — maps field `type` → Vue component,
   mirrored 1:1 on the PHP core/fields/FieldRegistry. PHP stays the
   source of truth for the type list (GET /api/v1/fields); this layer
   maps each type to a component + the field-component contract:
     defaultValue() · settingsSchema · validate() · serialize() ·
     toApi() · isContainer.
   Container types (tab/columns/accordion/group/repeater/blocks) are
   rendered by FieldRenderer, so here they only carry meta.
   Gap field types (search/audio/video/embed/latest_posts) and the
   heavy inputs (wysiwyg/code) get full components in Fase 7; until
   then they degrade to a basic input.
   ============================================================ */
(function () {
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const C = M.comps;

  /* ---- registry + contract --------------------------------------------- */
  const REG = {};
  const CONTAINERS = ['tab', 'columns', 'accordion', 'group', 'repeater', 'blocks'];
  const NO_STORE = ['tab', 'columns', 'accordion', 'divider', 'message']; // layout-only, not persisted

  // Reconcile prototype/PHP naming differences onto canonical types.
  const ALIAS = {
    date_picker: 'date', time_picker: 'time', date_time_picker: 'datetime',
    flexible_content: 'blocks', relation: 'relationship',
  };
  const canonical = (t) => ALIAS[t] || t;

  function register(type, def) {
    REG[type] = Object.assign({
      type,
      component: 'MdInput',
      isContainer: CONTAINERS.includes(type),
      stored: !NO_STORE.includes(type),
      defaultValue() { return ''; },
      settingsSchema: null, // filled per-family in Fase 8
      validate(value, field) {
        if (field && field.required && (value == null || value === '' || (Array.isArray(value) && !value.length))) {
          return { required: true };
        }
        return true;
      },
      serialize(value) { return value; },
      toApi(value) { return value; },
    }, def || {});
  }

  M.fields = {
    canonical,
    has(type) { return !!REG[canonical(type)]; },
    get(type) { return REG[canonical(type)] || REG.text; },
    isContainer(type) { const e = REG[canonical(type)]; return !!(e && e.isContainer); },
    component(type) { return this.get(type).component; },
    defaultValue(field) { const e = this.get(field.type); return field.default != null ? field.default : e.defaultValue(field); },
    all() { return REG; },
    register,
    /* Merge PHP metadata (label/icon/category/config_schema) onto the
       registry so the frontend mirrors the backend's authoritative list. */
    async hydrateFromApi() {
      try {
        const list = await M.api.get('/fields');
        const arr = Array.isArray(list) ? list : (list && list.fields) || [];
        arr.forEach((ft) => {
          const t = canonical(ft.type);
          if (!REG[t]) register(t, {}); // unknown-to-frontend → safe default input
          REG[t].label = ft.label || REG[t].label || t;
          REG[t].icon = ft.icon || REG[t].icon;
          REG[t].category = ft.category || REG[t].category;
          REG[t].configSchema = ft.config_schema || REG[t].configSchema || null;
        });
        M._fieldsHydrated = true;
      } catch (e) {
        console.warn('[fields] hydrate from API failed; using built-in registry', e && e.status);
      }
      return REG;
    },
  };

  /* Normalize choices to [{value,label}] — handles array of {value,label},
     array of strings, and ACF/PHP object maps {value: label}. */
  const choicesOf = (def) => {
    const c = def && def.choices;
    if (Array.isArray(c)) return c.map(x => (x && typeof x === 'object') ? { value: x.value, label: (x.label != null ? x.label : x.value) } : { value: x, label: String(x) });
    if (c && typeof c === 'object') return Object.keys(c).map(k => ({ value: k, label: c[k] }));
    return [];
  };
  M.choicesOf = choicesOf;

  /* ---- leaf field components (props: definition + modelValue) ----------- */
  const base = (extra) => {
    extra = extra || {};
    const merged = Object.assign({
      props: { definition: { type: Object, default: () => ({}) }, modelValue: { default: '' } },
      emits: ['update:modelValue'],
    }, extra);
    // merge (don't replace) methods so every field keeps the inherited set()
    merged.methods = Object.assign({ set(v) { this.$emit('update:modelValue', v); } }, extra.methods || {});
    return merged;
  };

  C.MdInput = base({
    name: 'MdInput',
    computed: { htype() { const t = this.definition.type; return t === 'number' ? 'number' : (t === 'email' ? 'email' : (t === 'password' ? 'password' : (t === 'search' ? 'search' : 'text'))); } },
    template: `<input class="inp" :type="htype" :value="modelValue" @input="set($event.target.value)" :placeholder="definition.placeholder||''">`,
  });

  C.MdUrl = base({
    name: 'MdUrl',
    template: `<div class="inp-group"><span class="affix">https://</span><input class="inp mono" :value="modelValue" @input="set($event.target.value)" placeholder="example.com"></div>`,
  });

  C.MdTextarea = base({
    name: 'MdTextarea',
    template: `<textarea class="ta" :value="modelValue" @input="set($event.target.value)" :placeholder="definition.placeholder||''"></textarea>`,
  });

  // custom dropdown (native <select> can't style its option list)
  C.MdSelect = base({
    name: 'MdSelect',
    data() { return { open: false }; },
    computed: {
      choices() { return choicesOf(this.definition); },
      current() { const c = this.choices.find(x => String(x.value) === String(this.modelValue)); return c ? (c.label || c.value) : ''; },
    },
    methods: { pick(v) { this.set(v); this.open = false; } },
    template: `
      <div class="md-select">
        <button type="button" class="sel sel-trigger" :class="{'is-open':open}" @click="open=!open">
          <span :class="{muted:!current}">{{ current || definition.placeholder || 'Choose…' }}</span>
          <Icon name="chevDown" :size="15" class="muted"/>
        </button>
        <template v-if="open">
          <div class="iconpick-scrim" @click="open=false"></div>
          <div class="md-select-pop">
            <button type="button" class="md-select-opt" v-for="c in choices" :key="c.value" :class="{'is-on':String(modelValue)===String(c.value)}" @click="pick(c.value)">
              <span>{{ c.label || c.value }}</span><Icon v-if="String(modelValue)===String(c.value)" name="check" :size="14"/>
            </button>
            <div v-if="!choices.length" class="t-meta" style="padding:8px 10px">No choices configured.</div>
          </div>
        </template>
      </div>`,
  });

  C.MdSwitch = base({
    name: 'MdSwitch',
    template: `<div class="flex items-center gap-3"><UiSwitch :modelValue="!!modelValue" @update:modelValue="set"/><span class="t-meta">{{ modelValue ? 'On' : 'Off' }}</span></div>`,
  });

  C.MdButtonGroup = base({
    name: 'MdButtonGroup',
    computed: { choices() { const c = choicesOf(this.definition); return c.length ? c : [{ value: 'left', label: 'Left' }, { value: 'right', label: 'Right' }]; } },
    template: `<div class="seg"><button v-for="c in choices" :key="c.value" :class="{'is-on':modelValue===c.value}" @click="set(c.value)">{{ c.label || c.value }}</button></div>`,
  });

  C.MdCheckbox = base({
    name: 'MdCheckbox',
    computed: { choices() { return choicesOf(this.definition); }, sel() { return Array.isArray(this.modelValue) ? this.modelValue : []; } },
    methods: { toggle(v) { const i = this.sel.indexOf(v); const a = this.sel.slice(); if (i >= 0) a.splice(i, 1); else a.push(v); this.set(a); } },
    template: `<div class="optset"><label v-for="o in choices" :key="o.value" class="optrow" :class="{'is-on':sel.includes(o.value)}" @click.prevent="toggle(o.value)"><UiChk :modelValue="sel.includes(o.value)"/><span>{{ o.label||o.value }}</span></label><div v-if="!choices.length" class="t-meta" style="padding:6px 2px">No choices configured.</div></div>`,
  });

  C.MdRadio = base({
    name: 'MdRadio',
    computed: { choices() { return choicesOf(this.definition); } },
    template: `<div class="optset"><label v-for="o in choices" :key="o.value" class="optrow" :class="{'is-on':modelValue===o.value}" @click="set(o.value)"><span class="optradio" :class="{'is-on':modelValue===o.value}"></span><span>{{ o.label||o.value }}</span></label><div v-if="!choices.length" class="t-meta" style="padding:6px 2px">No choices configured.</div></div>`,
  });

  C.MdColor = base({
    name: 'MdColor',
    data() { return { open: false, swatches: ['#C0552F', '#2A2520', '#4F7A52', '#5B7089', '#A9802F', '#876089', '#3E7C78', '#B0764A', '#221F1A', '#647A53', '#A8432F', '#FCFBF8'] }; },
    methods: { pick(c) { this.set(c); } },
    template: `
      <div class="fc-color">
        <button type="button" class="color-trigger" :class="{'is-open':open}" @click="open=!open">
          <span class="fc-swatch" :style="{background: modelValue||'#C0552F'}"></span>
          <span class="mono">{{ modelValue || '—' }}</span>
          <Icon name="chevDown" :size="14" class="muted" style="margin-left:auto"/>
        </button>
        <template v-if="open">
          <div class="iconpick-scrim" @click="open=false"></div>
          <div class="color-pop">
            <div class="color-swatches"><button type="button" v-for="c in swatches" :key="c" class="color-sw" :class="{'is-on':modelValue===c}" :style="{background:c}" @click="pick(c)" :title="c"></button></div>
            <div class="color-row">
              <input type="color" class="color-native" :value="modelValue||'#C0552F'" @input="set($event.target.value)">
              <input class="inp mono" :value="modelValue" @input="set($event.target.value)" placeholder="#C0552F">
            </div>
          </div>
        </template>
      </div>`,
  });

  // audio / video — MediaPicker (filtered) + inline player
  const mediaPlayer = (tag, accept, choose) => base({
    name: 'Md' + tag,
    data() { return { pickOpen: false }; },
    template: `
      <div>
        <div v-if="modelValue && modelValue.url">
          <` + tag.toLowerCase() + ` :src="modelValue.url" controls style="width:100%;border-radius:var(--r-sm)"></` + tag.toLowerCase() + `>
          <div class="flex gap-2 items-center" style="margin-top:6px"><span class="t-meta mono" style="flex:1">{{ modelValue.name }}</span><button class="btn btn--ghost btn--xs" @click="pickOpen=true">Replace</button><button class="btn btn--ghost btn--xs is-danger" @click="set(null)">Remove</button></div>
        </div>
        <button v-else class="form-file" @click="pickOpen=true"><Icon name="` + (tag === 'Audio' ? 'file' : 'image') + `" :size="15"/> ` + choose + `</button>
        <MediaPicker v-if="pickOpen" accept="` + accept + `" @close="pickOpen=false" @pick="m=>{ set({id:m.id,name:m.name,url:m.url}); pickOpen=false }"/>
      </div>`,
  });
  C.MdAudio = mediaPlayer('Audio', 'audio', 'Choose audio…');
  C.MdVideo = mediaPlayer('Video', 'video', 'Choose video…');

  // embed — URL in, oEmbed-style iframe preview (YouTube/Vimeo; CSP frame-src allows these)
  C.MdEmbed = base({
    name: 'MdEmbed',
    computed: {
      embedUrl() {
        const u = this.modelValue; if (!u) return '';
        let m;
        if ((m = String(u).match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([\w-]{6,})/))) return 'https://www.youtube-nocookie.com/embed/' + m[1];
        if ((m = String(u).match(/vimeo\.com\/(?:video\/)?(\d+)/))) return 'https://player.vimeo.com/video/' + m[1];
        return '';
      },
    },
    template: `
      <div>
        <div class="inp-group"><span class="affix"><Icon name="link" :size="13"/></span><input class="inp mono" :value="modelValue" @input="set($event.target.value)" placeholder="Paste a YouTube or Vimeo URL…"></div>
        <div v-if="embedUrl" class="embed-frame"><iframe :src="embedUrl" allowfullscreen loading="lazy"></iframe></div>
        <div v-else-if="modelValue" class="t-meta" style="margin-top:6px">No preview for this URL (YouTube/Vimeo supported).</div>
      </div>`,
  });

  // latest_posts — query builder (post type + limit) with live preview via /posts
  C.MdLatestPosts = base({
    name: 'MdLatestPosts',
    data() { return { preview: [], loading: false }; },
    computed: {
      cfg() { return Object.assign({ postType: '', limit: 5 }, this.modelValue || {}); },
      postTypes() { return (M.data && M.data.POST_TYPES) || []; },
    },
    mounted() { this.refresh(); },
    methods: {
      upd(k, v) { this.set(Object.assign({}, this.cfg, { [k]: v })); this.$nextTick(() => this.refresh()); },
      refresh() {
        const pt = this.postTypes.find(p => p.id === this.cfg.postType);
        if (!pt || !pt._dbId || !M.api) { this.preview = []; return; }
        this.loading = true;
        M.api.get('/posts?post_type_id=' + pt._dbId).then(rows => { this.loading = false; this.preview = (rows || []).slice(0, this.cfg.limit || 5); })
          .catch(() => { this.loading = false; this.preview = []; });
      },
    },
    template: `
      <div class="lp">
        <div class="field" style="margin:0 0 10px"><label class="field-label">Post type</label>
          <div class="acf-rel-chips" style="padding:0">
            <button type="button" v-for="p in postTypes" :key="p.id" class="acf-rel-chip" :class="{'is-on':cfg.postType===p.id}" @click="upd('postType',p.id)">{{ p.name }}</button>
          </div>
        </div>
        <div class="field" style="margin:0 0 10px;max-width:160px"><label class="field-label">Limit</label><input class="inp" type="number" min="1" :value="cfg.limit" @input="upd('limit', Number($event.target.value)||5)"></div>
        <div style="margin-top:10px">
          <div class="t-meta" style="margin-bottom:4px">Preview</div>
          <div v-if="loading" class="t-meta">Loading…</div>
          <div v-else-if="!preview.length" class="t-meta">No posts.</div>
          <div v-else class="dash-list">
            <div v-for="p in preview" :key="p.id" class="dash-row" style="cursor:default"><span class="dash-row-main"><strong>{{ p.title }}</strong><span class="t-meta">{{ p.status }}</span></span></div>
          </div>
        </div>
      </div>`,
  });

  C.MdRange = base({
    name: 'MdRange',
    template: `<div class="flex items-center gap-3"><input type="range" :min="definition.min||0" :max="definition.max||100" :step="definition.step||1" :value="modelValue||0" @input="set($event.target.value)" style="flex:1;accent-color:var(--accent)"><span class="t-key" style="width:34px">{{ modelValue||0 }}</span></div>`,
  });

  C.MdDate = base({
    name: 'MdDate',
    computed: { mode() { return this.definition.type === 'datetime' ? 'datetime' : (this.definition.type === 'time' ? 'time' : 'date'); } },
    template: `<Calendar :mode="mode" :modelValue="modelValue||''" @update:modelValue="set"/>`,
  });

  C.MdMarkdown = base({
    name: 'MdMarkdown',
    data() { return { tab: 'write' }; },
    template: `
      <div class="mdf">
        <div class="mdf-bar"><div class="seg"><button :class="{'is-on':tab==='write'}" @click="tab='write'">Write</button><button :class="{'is-on':tab==='preview'}" @click="tab='preview'">Preview</button></div><span class="t-meta mono">Markdown</span></div>
        <textarea v-if="tab==='write'" class="ta mono" :value="modelValue" @input="set($event.target.value)" style="min-height:120px;border-radius:0 0 var(--r-sm) var(--r-sm)"></textarea>
        <div v-else class="mdf-preview"><pre style="white-space:pre-wrap;font-family:var(--font-ui)">{{ modelValue }}</pre></div>
      </div>`,
  });

  /* Rich text — a usable TinyMCE-style editor on contentEditable + execCommand.
     Headless Tiptap (bubble/slash menus, per-field node/mark limits) replaces
     this in Fase 7; the field contract + markup stay the same. */
  C.MdRichText = base({
    name: 'MdRichText',
    data() { return { block: 'p', tiptap: null, useTT: false, sourceView: false, imgPick: false, _savedRange: null }; },
    mounted() { if (this.$refs.body) this.$refs.body.innerHTML = this.modelValue || ''; this.initTiptap(); },
    beforeUnmount() { if (this.tiptap) { try { this.tiptap.destroy(); } catch (e) {} } },
    watch: {
      modelValue(v) {
        if (this.useTT) { if (this.tiptap && this.tiptap.getHTML() !== (v || '')) { try { this.tiptap.commands.setContent(v || '', false); } catch (e) {} } return; }
        // only rewrite when the editor isn't focused, to avoid caret jumps
        if (this.$refs.body && document.activeElement !== this.$refs.body && this.$refs.body.innerHTML !== (v || '')) {
          this.$refs.body.innerHTML = v || '';
        }
      },
    },
    methods: {
      async initTiptap() {
        try {
          const vbase = (window.__MDLR && window.__MDLR.baseUrl || '') + '/admin/assets/vendor';
          const tt = await import(vbase + '/tiptap/tiptap.bundle.js');   // vendored locally — no external deps
          const StarterKit = tt.StarterKit;
          this.tiptap = new tt.Editor({ element: this.$refs.tt, extensions: [StarterKit], content: this.modelValue || '', onUpdate: ({ editor }) => this.$emit('update:modelValue', editor.getHTML()) });
          this.useTT = true;
        } catch (e) { console.warn('[wysiwyg] Tiptap load failed; using basic editor', e); this.useTT = false; }
      },
      ttRun(fn) { try { fn(this.tiptap.chain().focus()).run(); } catch (e) {} },
      emit() { this.$emit('update:modelValue', this.$refs.body ? this.$refs.body.innerHTML : ''); },
      exec(cmd, val) {
        if (this.useTT) {
          const T = { bold: c => c.toggleBold(), italic: c => c.toggleItalic(), underline: c => (c.toggleUnderline ? c.toggleUnderline() : c), strikeThrough: c => c.toggleStrike(), insertUnorderedList: c => c.toggleBulletList(), insertOrderedList: c => c.toggleOrderedList(), undo: c => c.undo(), redo: c => c.redo(), unlink: c => (c.unsetLink ? c.unsetLink() : c) };
          if (T[cmd]) this.ttRun(T[cmd]);
          return;
        }
        if (this.$refs.body) this.$refs.body.focus(); try { document.execCommand(cmd, false, val || null); } catch (e) {} this.emit();
      },
      setBlock(tag) {
        this.block = tag;
        if (this.useTT) {
          if (tag === 'blockquote') this.ttRun(c => c.toggleBlockquote());
          else if (tag === 'pre') this.ttRun(c => c.toggleCodeBlock());
          else if (/^h[2-4]$/.test(tag)) this.ttRun(c => c.toggleHeading({ level: Number(tag[1]) }));
          else this.ttRun(c => c.setParagraph());
          return;
        }
        this.exec('formatBlock', tag.toUpperCase());
      },
      link() {
        const url = window.prompt('Link URL', 'https://');
        if (!url) return;
        if (this.useTT) { this.ttRun(c => (c.setLink ? c.setLink({ href: url }) : c)); return; }
        this.exec('createLink', url);
      },
      codeInline() {
        if (this.useTT) { this.ttRun(c => c.toggleCode()); return; }
        const sel = window.getSelection();
        const text = sel && !sel.isCollapsed ? sel.toString() : '';
        // wrap the (preserved) selection in <code>; ​ gives a caret target when empty
        this.exec('insertHTML', '<code>' + (text || '​') + '</code>');
      },
      clearFormat() {
        if (this.useTT) { this.ttRun(c => c.unsetAllMarks().setParagraph()); return; }
        this.exec('removeFormat');
        this.exec('unlink');           // removeFormat leaves links intact
        this.exec('formatBlock', 'P'); // drop heading/quote/code blocks too
      },
      onInput() { this.emit(); },
      // Save the caret position before the MediaPicker steals focus, so the
      // chosen image lands where the cursor was (basic contentEditable path).
      saveRange() {
        const sel = window.getSelection();
        this._savedRange = (sel && sel.rangeCount && this.$refs.body && this.$refs.body.contains(sel.anchorNode))
          ? sel.getRangeAt(0) : null;
      },
      openImage() { this.saveRange(); this.imgPick = true; },
      insertImage(m) {
        const url = m && m.url; if (!url) return;
        const alt = String((m && m.name) || '').replace(/"/g, '');
        const html = '<img src="' + url + '" alt="' + alt + '">';
        if (this.useTT) { try { this.tiptap.chain().focus().insertContent(html).run(); } catch (e) {} return; }
        if (this.$refs.body) {
          this.$refs.body.focus();
          if (this._savedRange) { const sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(this._savedRange); }
        }
        try { document.execCommand('insertHTML', false, html); } catch (e) {}
        this.emit();
      },
    },
    template: `
      <div class="rte">
        <div class="rte-bar">
          <select class="rte-block" :value="block" @change="setBlock($event.target.value)" title="Paragraph format">
            <option value="p">Paragraph</option>
            <option value="h2">Heading 2</option>
            <option value="h3">Heading 3</option>
            <option value="h4">Heading 4</option>
            <option value="pre">Code block</option>
          </select>
          <span class="rte-sep"></span>
          <button type="button" class="rte-b" title="Bold" @mousedown.prevent @click="exec('bold')"><strong>B</strong></button>
          <button type="button" class="rte-b" title="Italic" @mousedown.prevent @click="exec('italic')"><em>I</em></button>
          <button type="button" class="rte-b" title="Underline" @mousedown.prevent @click="exec('underline')" style="text-decoration:underline">U</button>
          <button type="button" class="rte-b" title="Strikethrough" @mousedown.prevent @click="exec('strikeThrough')" style="text-decoration:line-through">S</button>
          <span class="rte-sep"></span>
          <button type="button" class="rte-b" title="Bulleted list" @mousedown.prevent @click="exec('insertUnorderedList')"><Icon name="list" :size="15"/></button>
          <button type="button" class="rte-b" title="Numbered list" @mousedown.prevent @click="exec('insertOrderedList')"><Icon name="listOrdered" :size="15"/></button>
          <button type="button" class="rte-b" title="Quote" @mousedown.prevent @click="setBlock('blockquote')"><Icon name="quote" :size="15"/></button>
          <span class="rte-sep"></span>
          <button type="button" class="rte-b" title="Link" @mousedown.prevent @click="link"><Icon name="link" :size="15"/></button>
          <button type="button" class="rte-b" title="Remove link" @mousedown.prevent @click="exec('unlink')"><Icon name="unlink" :size="15"/></button>
          <button type="button" class="rte-b" title="Inline code" @mousedown.prevent @click="codeInline"><Icon name="code" :size="15"/></button>
          <span class="rte-sep"></span>
          <button type="button" class="rte-b" title="Insert image" @mousedown.prevent @click="openImage"><Icon name="image" :size="15"/></button>
          <span class="rte-sep"></span>
          <button type="button" class="rte-b" title="Clear formatting" @mousedown.prevent @click="clearFormat"><Icon name="eraser" :size="15"/></button>
          <button type="button" class="rte-b" title="Undo" @mousedown.prevent @click="exec('undo')"><Icon name="undo" :size="15"/></button>
          <button type="button" class="rte-b" title="Redo" @mousedown.prevent @click="exec('redo')"><Icon name="redo" :size="15"/></button>
          <button type="button" class="rte-b rte-source" :class="{'is-on':sourceView}" title="View HTML source" @mousedown.prevent @click="sourceView=!sourceView" style="margin-left:auto"><Icon name="code" :size="15"/> HTML</button>
        </div>
        <template v-if="!sourceView">
          <div v-show="!useTT" ref="body" class="rte-body" contenteditable="true" @input="onInput" @blur="emit"></div>
          <div v-show="useTT" ref="tt" class="rte-body tt-host"></div>
        </template>
        <textarea v-else class="ta mono" style="min-height:160px;border-radius:0 0 var(--r-sm) var(--r-sm)" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" placeholder="&lt;p&gt;HTML…&lt;/p&gt;"></textarea>
        <MediaPicker v-if="imgPick" :accept="'image'" @close="imgPick=false" @pick="m=>{ insertImage(m); imgPick=false }"/>
      </div>`,
  });

  // code — CodeMirror 6 loaded on demand from CDN; falls back to a mono textarea.
  C.MdCode = base({
    name: 'MdCode',
    data() { return { ready: false, failed: false }; },
    mounted() { this.init(); },
    beforeUnmount() { if (this._view) this._view.destroy(); },
    methods: {
      async init() {
        try {
          const vbase = (window.__MDLR && window.__MDLR.baseUrl || '') + '/admin/assets/vendor';
          const CM = await import(vbase + '/codemirror/codemirror.bundle.js');   // vendored locally — no external deps
          let langExt = [];
          try { if (CM.javascript) langExt = [CM.javascript({ jsx: false, typescript: false })]; } catch (_) {}
          const listener = CM.EditorView.updateListener.of((u) => { if (u.docChanged && this._view) this.set(this._view.state.doc.toString()); });
          this._view = new CM.EditorView({ doc: this.modelValue || '', extensions: [CM.basicSetup, ...langExt, listener], parent: this.$refs.cm });
          this.ready = true;
        } catch (e) { console.warn('[code] CodeMirror load failed; using textarea', e); this.failed = true; }
      },
    },
    template: `
      <div class="cm-wrap">
        <div ref="cm" v-show="ready && !failed" class="cm-host"></div>
        <textarea v-if="failed || !ready" class="ta mono" style="min-height:120px" :value="modelValue" @input="set($event.target.value)" :placeholder="ready ? '' : 'Loading editor…'"></textarea>
      </div>`,
  });

  C.MdHidden = base({
    name: 'MdHidden',
    template: `<input class="inp mono" :value="modelValue" @input="set($event.target.value)" placeholder="(hidden value)">`,
  });

  /* image / gallery / file — wire to MediaPicker (real library in Fase 6) */
  C.MdImage = base({
    name: 'MdImage',
    data() { return { cropOpen: false, pickOpen: false }; },
    computed: {
      // Resolve the live media item so the preview reflects the global crop
      // (crop regenerates variants; we show the cropped variant, not the original).
      media() { const v = this.modelValue; if (!v || v.id == null) return null; return (window.__mdlr.data.MEDIA || []).find(x => x.id === v.id) || null; },
      imgBg() { const m = this.media; const u = (m && (m.preview || m.thumb)) || (this.modelValue && this.modelValue.url) || ''; return u; },
      cropImg() { const v = this.modelValue || {}; const m = this.media; return Object.assign({}, v, { url: v.url || (m && m.url), crop: (m && m.crop) || v.crop || null, w: v.w || (m && m.w), h: v.h || (m && m.h) }); },
      // Chosen image format (crop preset) name, shown as a preview hint on the post.
      formatName() { const m = this.media; return (m && m.crop && m.crop.preset) || ''; },
    },
    methods: {
      grad(h) { return 'linear-gradient(135deg, hsl(' + h + ' 30% 80%), hsl(' + (h - 8) + ' 24% 58%))'; },
      onCrop(crop) { const v = this.modelValue; if (!v || v.id == null) return; window.__mdlr.store.saveMediaCrop(v.id, crop).catch(e => console.error('[image] save crop failed', e)); },
    },
    template: `
      <div class="fc-img">
        <div v-if="modelValue && modelValue.name" class="img-slot" :style="imgBg ? {background:'center/cover no-repeat url('+imgBg+')'} : {backgroundImage: grad(modelValue.hue||34)}">
          <div class="img-slot-actions"><button class="btn btn--soft btn--xs" @click="cropOpen=true"><Icon name="crop" :size="13"/> Crop</button><button class="btn btn--soft btn--xs" @click="pickOpen=true"><Icon name="refresh" :size="13"/> Replace</button><button class="btn btn--soft btn--xs" @click="set(null)"><Icon name="x" :size="13"/> Remove</button></div>
          <span class="img-slot-meta mono">{{ modelValue.name }}<template v-if="modelValue.w"> · {{ modelValue.w }}×{{ modelValue.h }}</template><template v-if="formatName"> · {{ formatName }}</template></span>
        </div>
        <button v-else class="form-file" @click="pickOpen=true"><Icon name="image" :size="15"/> Choose image…</button>
        <ImageCropper v-if="cropOpen" :img="cropImg" @save="onCrop" @close="cropOpen=false"/>
        <MediaPicker v-if="pickOpen" :accept="'image'" @close="pickOpen=false" @pick="m=>{ set({id:m.id,hue:m.hue,name:m.name,w:m.w,h:m.h,url:m.url}); pickOpen=false }"/>
      </div>`,
  });

  C.MdGallery = base({
    name: 'MdGallery',
    data() { return { pickOpen: false, dragI: null, dropAt: null }; },
    computed: { items() { return Array.isArray(this.modelValue) ? this.modelValue : []; } },
    methods: {
      grad(h) { return 'linear-gradient(135deg, hsl(' + h + ' 26% 82%), hsl(' + (h - 6) + ' 22% 64%))'; },
      bg(it) {
        // Prefer the small, resolved thumbnail from the loaded media library so cells
        // are KB-light and never broken on relative paths; fall back to the stored url.
        const MEDIA = (window.__mdlr.data && window.__mdlr.data.MEDIA) || [];
        const m = (it && it.id != null) ? MEDIA.find(x => x.id === it.id) : null;
        const u = (m && (m.thumb || m.url)) || (it && it.url) || '';
        return u ? ('center/cover no-repeat url(' + u + ')') : this.grad((it && it.hue) || 30);
      },
      addBatch(payload) { const arr = Array.isArray(payload) ? payload : [payload]; const add = arr.map(m => ({ id: m.id, hue: m.hue, name: m.name, w: m.w, h: m.h, url: m.url })); this.set(this.items.concat(add)); },
      remove(i) { const a = this.items.slice(); a.splice(i, 1); this.set(a); },
      dStart(i, e) { this.dragI = i; if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', String(i)); } catch (_) {} } },
      dOver(i, e) { e.preventDefault(); const r = e.currentTarget.getBoundingClientRect(); this.dropAt = (e.clientX - r.left) > r.width / 2 ? i + 1 : i; },
      dDrop() { if (this.dragI == null) { this.dropAt = null; return; } let at = this.dropAt == null ? this.items.length : this.dropAt; const a = this.items.slice(); const it = a[this.dragI]; a.splice(this.dragI, 1); if (this.dragI < at) at -= 1; a.splice(at, 0, it); this.set(a); this.dragI = null; this.dropAt = null; },
    },
    template: `
      <div>
        <div class="gal" @dragover.prevent @drop="dDrop">
          <template v-for="(it,i) in items" :key="it.id||i">
            <div class="gal-dropline" v-if="dragI!=null && dropAt===i"></div>
            <div class="gal-cell" :class="{'is-dragging':dragI===i}" :style="{ background: bg(it) }" draggable="true" @dragstart="dStart(i,$event)" @dragend="dragI=null;dropAt=null" @dragover="dOver(i,$event)" :title="it.name">
              <button class="iconbtn iconbtn--sm is-danger gal-rm" @click.stop="remove(i)"><Icon name="x" :size="12"/></button>
            </div>
          </template>
          <div class="gal-dropline" v-if="dragI!=null && dropAt===items.length"></div>
          <button class="gal-add" @click="pickOpen=true"><Icon name="plus" :size="18"/></button>
        </div>
        <MediaPicker v-if="pickOpen" multiple accept="image" @close="pickOpen=false" @pick="addBatch"/>
      </div>`,
  });

  C.MdFile = base({
    name: 'MdFile',
    data() { return { pickOpen: false }; },
    template: `
      <div>
        <div v-if="modelValue && modelValue.name" class="rel-item" style="cursor:default"><span class="rel-tax-ico"><Icon name="file" :size="13"/></span><div class="rel-item-main"><strong>{{ modelValue.name }}</strong></div><button class="iconbtn iconbtn--sm is-danger" @click="set(null)"><Icon name="x" :size="13"/></button></div>
        <button v-else class="form-file" @click="pickOpen=true"><Icon name="upload" :size="15"/> Choose file…</button>
        <MediaPicker v-if="pickOpen" :accept="definition.accept||''" @close="pickOpen=false" @pick="m=>{ set({name:m.name,id:m.id}); pickOpen=false }"/>
      </div>`,
  });

  /* relationship / post_object / taxonomy / user — Combobox-powered.
     options come from the API in later phases; definition.options is a
     pass-through so the FieldRenderer/demo can supply them now. */
  // relationship / post_object — ACF-style two-pane picker (search + filter + select).
  C.MdRelation = base({
    name: 'MdRelation',
    data() { return { search: '', ptFilter: '', available: [], resolvedById: {}, loading: false, dragI: null, dropAt: null, renderN: 30 }; },
    computed: {
      single() { return this.definition.type === 'post_object' || this.definition.single; },
      allowedTypes() { return this.definition.post_type || this.definition.postType || []; },
      // Resolve stored values (which are raw post ids when saved with return_format=id)
      // into {id,title,…} objects so the selected pane shows titles, not "#id".
      selected() {
        const v = this.modelValue;
        const arr = Array.isArray(v) ? v : (v ? [v] : []);
        if (!arr.length) return [];
        const byId = {};
        this.available.forEach(p => { byId[p.id] = p; });
        return arr.map(s => {
          if (s && typeof s === 'object') return s;
          return byId[s] || this.resolvedById[s] || { id: s, _raw: true };
        });
      },
      selectedIds() { return this.selected.map(s => (s && typeof s === 'object') ? s.id : s); },
      filtered() {
        const q = this.search.trim().toLowerCase();
        return this.available.filter(p => !this.selectedIds.includes(p.id) && (!this.ptFilter || p.type === this.ptFilter) && (!q || (p.title || '').toLowerCase().includes(q)));
      },
      // Only render a window of the filtered list (grows on scroll) so a 100+ list
      // doesn't bloat the DOM. Search still runs over the full `filtered` set above.
      visible() { return this.filtered.slice(0, this.renderN); },
      ptChips() {
        const seen = {}; this.available.forEach(p => { if (p.type) seen[p.type] = p.typeName || p.type; });
        return Object.keys(seen).map(k => ({ value: k, label: seen[k] }));
      },
    },
    mounted() { this.load(); },
    watch: {
      modelValue() { this.resolveMissing(); },
      search() { this.renderN = 30; },
      ptFilter() { this.renderN = 30; },
    },
    methods: {
      load() {
        const pts = (M.data.POST_TYPES || []).filter(p => !this.allowedTypes.length || this.allowedTypes.includes(p.id) || this.allowedTypes.includes(p.slug));
        const list = pts.map(p => ({ pt: p, id: p._dbId })).filter(x => x.id);
        if (!M.api || !list.length) { this.available = []; return; }
        this.loading = true;
        // Lightweight + high cap so 100+ items are available for search without payload bloat.
        Promise.all(list.map(x => M.api.get('/posts?post_type_id=' + x.id + '&fields=light&limit=1000').catch(() => [])))
          .then(results => {
            this.loading = false;
            const flat = [];
            results.forEach((rows, i) => { const pt = list[i].pt; (rows || []).forEach(p => flat.push({ id: p.id, title: p.title || '(untitled)', type: pt.id, typeName: pt.name, status: p.status })); });
            this.available = flat;
            this.resolveMissing();
          }).catch(() => { this.loading = false; });
      },
      // Grow the rendered window as the list scrolls near the bottom (lazy render).
      onScroll(e) {
        const el = e.target;
        if (el.scrollTop + el.clientHeight >= el.scrollHeight - 60 && this.renderN < this.filtered.length) {
          this.renderN = Math.min(this.renderN + 30, this.filtered.length);
        }
      },
      // Fetch titles for any stored ids not present in `available` (e.g. a type
      // with >100 posts), so the selected pane never falls back to "#id".
      resolveMissing() {
        if (!M.api) return;
        const v = this.modelValue;
        const arr = Array.isArray(v) ? v : (v ? [v] : []);
        const byId = {}; this.available.forEach(p => { byId[p.id] = p; });
        const ptName = (slug, id) => { const t = (M.data.POST_TYPES || []).find(x => x.slug === slug || x._dbId === id || x.id === slug); return t ? t.name : ''; };
        [...new Set(arr.filter(s => s != null && typeof s !== 'object' && !byId[s] && !this.resolvedById[s]))].forEach(id => {
          M.api.get('/posts/' + id).then(p => {
            if (p && p.id) this.resolvedById = Object.assign({}, this.resolvedById, { [p.id]: { id: p.id, title: p.title || ('#' + p.id), type: p.post_type_slug || p.type, typeName: p.post_type_name || ptName(p.post_type_slug, p.post_type_id), status: p.status } });
          }).catch(() => {});
        });
      },
      label(s) { return (s && typeof s === 'object' && !s._raw && s.title != null) ? s.title : ('#' + ((s && s.id != null) ? s.id : s)); },
      sub(s) { return (s && typeof s === 'object') ? (s.typeName || s.type || '') : ''; },
      // Store raw post ids (return_format=id) so saved data stays consistent with
      // the migrated content and what the themed front-end reads. The selected
      // pane resolves ids → titles for display only.
      add(p) {
        if (this.single) this.set(p.id);
        else this.set(this.selectedIds.concat([p.id]));
      },
      remove(id) {
        const r = this.selectedIds.filter(x => x !== id);
        this.set(this.single ? (r.length ? r[0] : null) : r);
      },
      dStart(i, e) { this.dragI = i; if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', String(i)); } catch (_) {} } },
      dOver(i, e) { e.preventDefault(); const r = e.currentTarget.getBoundingClientRect(); this.dropAt = (e.clientY - r.top) > r.height / 2 ? i + 1 : i; },
      dEnd() { this.dragI = null; this.dropAt = null; },
      dDrop() {
        if (this.dragI == null) { this.dEnd(); return; }
        let at = this.dropAt == null ? this.selected.length : this.dropAt;
        const a = this.selectedIds.slice();
        const it = a[this.dragI];
        a.splice(this.dragI, 1);
        if (this.dragI < at) at -= 1;
        a.splice(at, 0, it);
        this.set(a);
        this.dEnd();
      },
    },
    template: `
      <div class="acf-rel">
        <div class="acf-rel-pane">
          <div class="acf-rel-toolbar">
            <div class="fb-lib-search" style="margin:0;flex:1"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="search" placeholder="Search…" style="color:var(--ink)"></div>
          </div>
          <div class="acf-rel-chips" v-if="ptChips.length>1">
            <button type="button" class="acf-rel-chip" :class="{'is-on':!ptFilter}" @click="ptFilter=''">All</button>
            <button type="button" v-for="c in ptChips" :key="c.value" class="acf-rel-chip" :class="{'is-on':ptFilter===c.value}" @click="ptFilter=c.value">{{ c.label }}</button>
          </div>
          <div class="acf-rel-list" @scroll="onScroll">
            <div v-if="loading" class="t-meta" style="padding:10px;text-align:center">Loading…</div>
            <button type="button" v-for="p in visible" :key="p.id" class="acf-rel-item" @click="add(p)" :disabled="single && selected.length">
              <div class="rel-item-main"><strong>{{ p.title }}</strong><span class="t-meta">{{ p.typeName }} · {{ p.status }}</span></div>
              <Icon name="plus" :size="15" class="muted"/>
            </button>
            <div v-if="!loading && visible.length < filtered.length" class="t-meta" style="padding:8px;text-align:center">Showing {{ visible.length }} of {{ filtered.length }} — scroll for more</div>
            <div v-if="!loading && !filtered.length" class="t-meta" style="padding:10px;text-align:center">No matches.</div>
          </div>
        </div>
        <div class="acf-rel-pane">
          <div class="acf-rel-head"><span class="t-meta">{{ single ? 'Selected' : 'Selected (' + selectedIds.length + ')' }}</span></div>
          <div class="acf-rel-list" @dragover.prevent @drop="dDrop">
            <template v-for="(item,i) in selected" :key="(item&&item.id)||i">
              <div class="acf-rel-dropline" v-if="dragI!=null && dropAt===i"></div>
              <div class="acf-rel-item is-selected" :class="{'is-dragging':dragI===i}" @dragover="dOver(i,$event)">
                <span v-if="!single" class="drag-handle" draggable="true" @dragstart="dStart(i,$event)" @dragend="dEnd" title="Drag to reorder"><Icon name="drag" :size="14"/></span>
                <div class="rel-item-main"><strong>{{ label(item) }}</strong><span class="t-meta">{{ sub(item) }}</span></div>
                <button type="button" class="iconbtn iconbtn--sm is-danger" @click="remove((item&&typeof item==='object')?item.id:item)"><Icon name="x" :size="13"/></button>
              </div>
            </template>
            <div class="acf-rel-dropline" v-if="dragI!=null && dropAt===selected.length"></div>
            <div v-if="!selected.length" class="acf-rel-empty t-meta">Nothing selected yet.</div>
          </div>
        </div>
      </div>`,
  });

  /* ---- register every canonical type ----------------------------------- */
  const V = (component, defaultValue, extra) => Object.assign({ component, defaultValue: () => (typeof defaultValue === 'function' ? defaultValue() : defaultValue) }, extra || {});

  register('text', V('MdInput', ''));
  register('textarea', V('MdTextarea', ''));
  register('number', V('MdInput', '', { serialize: (v) => (v === '' || v == null ? null : Number(v)) }));
  register('email', V('MdInput', ''));
  register('url', V('MdUrl', ''));
  register('password', V('MdInput', ''));
  register('search', V('MdInput', ''));
  register('color', V('MdColor', ''));
  register('range', V('MdRange', 0, { serialize: (v) => Number(v) }));
  register('hidden', V('MdHidden', ''));

  register('wysiwyg', V('MdRichText', ''));
  register('markdown', V('MdMarkdown', ''));
  register('code', V('MdCode', ''));

  register('select', V('MdSelect', ''));
  register('checkbox', V('MdCheckbox', () => []));
  register('radio', V('MdRadio', ''));
  register('button_group', V('MdButtonGroup', ''));
  register('true_false', V('MdSwitch', false, { serialize: (v) => !!v }));

  register('post_object', V('MdRelation', null));
  register('relationship', V('MdRelation', () => []));
  register('taxonomy', V('MdRelation', () => []));
  register('user', V('MdRelation', null));

  register('image', V('MdImage', null));
  register('gallery', V('MdGallery', () => []));
  register('file', V('MdFile', null));
  register('audio', V('MdAudio', null));
  register('video', V('MdVideo', null));
  register('embed', V('MdEmbed', ''));
  register('latest_posts', V('MdLatestPosts', () => ({ postType: '', limit: 5 })));

  register('date', V('MdDate', ''));
  register('datetime', V('MdDate', ''));
  register('time', V('MdDate', ''));

  // containers + layout-only (rendered by FieldRenderer)
  register('group', { isContainer: true, defaultValue: () => ({}) });
  register('repeater', { isContainer: true, defaultValue: () => [] });
  register('blocks', { isContainer: true, defaultValue: () => [] });
  register('tab', { isContainer: true, stored: false, defaultValue: () => undefined });
  register('columns', { isContainer: true, stored: false, defaultValue: () => undefined });
  register('accordion', { isContainer: true, stored: false, defaultValue: () => undefined });
  register('divider', { stored: false, defaultValue: () => undefined });
  register('message', { stored: false, defaultValue: () => undefined });
})();
