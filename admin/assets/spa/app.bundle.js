/* file: icons.js */
/* Icon set — Lucide-style, 1.75 stroke. Vue global component <Icon name=".." :size="18" />.
   Registered on the app in app.js via window.__mdlr.Icon. */
(function () {
  const P = {
    dashboard: '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
    file: '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M5 3h9l5 5v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/>',
    files: '<path d="M15 2H9a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V7z"/><path d="M14 2v4a1 1 0 0 0 1 1h4"/><path d="M5 7v13a2 2 0 0 0 2 2h9"/>',
    image: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="1.6"/><path d="m21 15-4.5-4.5L7 20"/>',
    layers: '<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/>',
    box: '<path d="M21 8 12 3 3 8v8l9 5 9-5Z"/><path d="m3 8 9 5 9-5"/><path d="M12 13v8"/>',
    grid: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
    type: '<path d="M4 7V5h16v2"/><path d="M12 5v14"/><path d="M9 19h6"/>',
    sliders: '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
    zap: '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8Z"/>',
    form: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 8h10"/><path d="M7 12h6"/><path d="M7 16h8"/>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a7 7 0 0 1 14 0v1"/>',
    settings: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    unlink: '<path d="M18.84 12.25l1.72-1.71a4 4 0 0 0-5.66-5.66l-1.71 1.72"/><path d="M5.17 11.75l-1.72 1.71a4 4 0 0 0 5.66 5.66l1.71-1.72"/><line x1="8" y1="2" x2="8" y2="5"/><line x1="2" y1="8" x2="5" y2="8"/><line x1="16" y1="19" x2="16" y2="22"/><line x1="19" y1="16" x2="22" y2="16"/>',
    quote: '<path d="M3 21c3 0 7-1 7-8V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M15 21c3 0 7-1 7-8V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>',
    eraser: '<path d="m7 21-4.3-4.3a1 1 0 0 1 0-1.4L13 5l6 6-9.3 9.3a1 1 0 0 1-1.4 0Z"/><path d="M22 21H7"/><path d="m5 11 6 6"/>',
    undo: '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>',
    redo: '<path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/>',
    listOrdered: '<line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-1.5 2-2.5S5 14 4 14.5"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
    settings2: '<path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/>',
    copy: '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/>',
    trash: '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/>',
    drag: '<circle cx="9" cy="6" r="1.3"/><circle cx="15" cy="6" r="1.3"/><circle cx="9" cy="12" r="1.3"/><circle cx="15" cy="12" r="1.3"/><circle cx="9" cy="18" r="1.3"/><circle cx="15" cy="18" r="1.3"/>',
    chevDown: '<path d="m6 9 6 6 6-6"/>',
    chevRight: '<path d="m9 6 6 6-6 6"/>',
    chevLeft: '<path d="m15 6-6 6 6 6"/>',
    chevUp: '<path d="m6 15 6-6 6 6"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>',
    check: '<path d="M20 6 9 17l-5-5"/>',
    arrowRight: '<path d="M5 12h14M13 6l6 6-6 6"/>',
    arrowUpRight: '<path d="M7 17 17 7M8 7h9v9"/>',
    eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
    eyeOff: '<path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 8 10 8a18 18 0 0 1-2.16 3.19m-6.72-1.07A3 3 0 1 1 9.9 9.9"/><path d="M1 1l22 22"/><path d="M6.6 6.6A18 18 0 0 0 2 12s3.5 8 10 8a9 9 0 0 0 5.4-1.6"/>',
    link: '<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
    alert: '<path d="m10.3 3.9-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3L13.7 4a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
    lock: '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    unlock: '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 7.9-1"/>',
    pencil: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
    save: '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',
    filter: '<path d="M22 3H2l8 9.5V19l4 2v-8.5L22 3Z"/>',
    calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    globe: '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18Z"/>',
    folder: '<path d="M4 20a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5l2 3h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2Z"/>',
    upload: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 9l5-5 5 5"/><path d="M12 4v12"/>',
    crop: '<path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/>',
    text: '<path d="M17 6.1H3M21 12.1H3M15.1 18H3"/>',
    align: '<path d="M21 6H3M15 12H3M17 18H3"/>',
    hash: '<path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/>',
    mail: '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/>',
    list: '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    code: '<path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/>',
    palette: '<circle cx="13.5" cy="6.5" r="1.5"/><circle cx="17.5" cy="10.5" r="1.5"/><circle cx="8.5" cy="7.5" r="1.5"/><circle cx="6.5" cy="12.5" r="1.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c1.7 0 2.5-1.3 2.5-2.5 0-.6-.2-1.1-.6-1.5-.4-.4-.6-.9-.6-1.5 0-1.2.8-2 2-2H17c2.8 0 5-2.2 5-5C22 5.8 17.5 2 12 2Z"/>',
    toggle: '<rect x="2" y="6" width="20" height="12" rx="6"/><circle cx="8" cy="12" r="3"/>',
    radio: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3.2"/>',
    sigma: '<path d="M18 7V4H6l6 8-6 8h12v-3"/>',
    branch: '<circle cx="6" cy="6" r="2.2"/><circle cx="6" cy="18" r="2.2"/><circle cx="18" cy="8" r="2.2"/><path d="M6 8.2v7.6M8.2 6.5C13 7 13 10 13 12c0 .8.3 1.6 1 2"/>',
    bell: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
    home: '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/>',
    star: '<path d="m12 2 3 6.5 7 .9-5 4.7 1.3 7L12 18l-6.6 3.1L6.7 14l-5-4.7 7-.9Z"/>',
    sparkle: '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6.3 6.3l2.5 2.5M15.2 15.2l2.5 2.5M17.7 6.3l-2.5 2.5M8.8 15.2l-2.5 2.5"/>',
    eyebrow: '<path d="M2 12s3-6 10-6 10 6 10 6"/>',
    columns: '<rect x="3" y="4" width="7" height="16" rx="1"/><rect x="14" y="4" width="7" height="16" rx="1"/>',
    rows: '<rect x="3" y="4" width="18" height="7" rx="1"/><rect x="3" y="13" width="18" height="7" rx="1"/>',
    tab: '<path d="M4 20h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1h-7l-2-3H4a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1Z"/>',
    repeat: '<path d="m17 2 4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
    flexible: '<rect x="3" y="3" width="18" height="6" rx="1"/><rect x="3" y="11" width="11" height="4" rx="1"/><rect x="3" y="17" width="18" height="4" rx="1"/>',
    minus: '<path d="M5 12h14"/>',
    move: '<path d="M5 9 2 12l3 3M9 5l3-3 3 3M15 19l-3 3-3-3M19 9l3 3-3 3M2 12h20M12 2v20"/>',
    dot: '<circle cx="12" cy="12" r="4"/>',
    component: '<path d="M12 2 4 6v12l8 4 8-4V6l-8-4Z"/><path d="m4 6 8 4 8-4M12 10v12"/>',
    wand: '<path d="M15 4V2M15 16v-2M8 9h2M20 9h2M17.8 11.8 19 13M15 9h0M17.8 6.2 19 5M3 21l9-9M12.2 6.2 11 5"/>',
    git: '<line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>',
    play: '<path d="m6 3 14 9-14 9V3Z"/>',
    webhook: '<path d="M18 16.98h-5.99c-1.1 0-1.95.94-2.48 1.9A4 4 0 0 1 2 17c.01-.7.2-1.4.57-2"/><path d="m6 17 3.13-5.78c.53-.97.1-2.18-.5-3.1a4 4 0 1 1 6.89-4.06"/><path d="m12 6 3.13 5.73C15.66 12.7 16.9 13 18 13a4 4 0 0 1 0 8"/>',
    database: '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
    table: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/>',
    bars: '<line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>',
    refresh: '<path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>',
    external: '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    panelLeft: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/>',
    helpCircle: '<circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    pin: '<path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
  };
  window.__mdlr = window.__mdlr || { comps: {} };
  window.__mdlr.ICON_PATHS = P;
  window.__mdlr.Icon = {
    name: 'Icon',
    props: { name: String, size: { type: [Number, String], default: 18 } },
    computed: {
      html() { return P[this.name] || ''; },
    },
    template: `<svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" v-html="html"></svg>`,
  };
})();
;
/* file: ui.js */
/* Reusable UI primitives — registered globally. */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;

  C.UiSwitch = {
    name: 'UiSwitch',
    props: { modelValue: Boolean },
    emits: ['update:modelValue'],
    template: `
      <label class="switch" @click.prevent="$emit('update:modelValue', !modelValue)">
        <input type="checkbox" :checked="modelValue">
        <span class="track"></span><span class="thumb"></span>
      </label>`,
  };

  C.UiChk = {
    name: 'UiChk',
    props: { modelValue: Boolean },
    emits: ['update:modelValue'],
    template: `
      <span class="chk" :class="{'is-on': modelValue}" @click="$emit('update:modelValue', !modelValue)">
        <Icon v-if="modelValue" name="check"/>
      </span>`,
  };

  C.UiSeg = {
    name: 'UiSeg',
    props: { modelValue: [String, Boolean, Number], options: Array },
    emits: ['update:modelValue'],
    // options: [{value, label, icon?}]
    template: `
      <div class="seg">
        <button v-for="o in options" :key="String(o.value)"
                :class="{'is-on': modelValue === o.value}"
                @click="$emit('update:modelValue', o.value)">
          <Icon v-if="o.icon" :name="o.icon"/>{{ o.label }}
        </button>
      </div>`,
  };

  C.UiDrawer = {
    name: 'UiDrawer',
    props: { wide: Boolean },
    emits: ['close'],
    template: `
      <teleport to="body">
        <div class="scrim" @click="$emit('close')"></div>
        <aside class="drawer" :class="{'drawer--wide': wide}" role="dialog">
          <slot/>
        </aside>
      </teleport>`,
  };

  C.UiModal = {
    name: 'UiModal',
    props: { lg: Boolean },
    emits: ['close'],
    template: `
      <teleport to="body">
        <div class="scrim" @click="$emit('close')"></div>
        <div class="modal-wrap" @click.self="$emit('close')">
          <div class="modal" :class="{'modal--lg': lg}" role="dialog"><slot/></div>
        </div>
      </teleport>`,
  };

  /* Lightweight popover menu. items: [{label, icon, danger, sep, onClick}] */
  C.UiMenu = {
    name: 'UiMenu',
    props: { items: Array, align: { type: String, default: 'right' } },
    data() { return { open: false, x: 0, y: 0 }; },
    methods: {
      toggle(e) {
        if (this.open) { this.open = false; return; }
        const r = e.currentTarget.getBoundingClientRect();
        this.x = this.align === 'right' ? r.right - 190 : r.left;
        this.y = r.bottom + 6;
        this.open = true;
      },
      pick(it) { this.open = false; if (it.onClick) it.onClick(); },
    },
    template: `
      <span style="position:relative;display:inline-flex">
        <span @click.stop="toggle"><slot/></span>
        <teleport to="body">
          <template v-if="open">
            <div style="position:fixed;inset:0;z-index:69" @click="open=false"></div>
            <div class="pop" :style="{left: x+'px', top: y+'px'}">
              <template v-for="(it,i) in items" :key="i">
                <div v-if="it.sep" class="pop-sep"></div>
                <div v-else class="pop-item" :class="{'is-danger': it.danger}" @click="pick(it)">
                  <Icon v-if="it.icon" :name="it.icon"/>{{ it.label }}
                </div>
              </template>
            </div>
          </template>
        </teleport>
      </span>`,
  };

  /* Icon picker — pick a menu icon. Opens a popover grid. */
  C.IconPicker = {
    name: 'IconPicker',
    props: { modelValue: String },
    emits: ['update:modelValue'],
    data() { return { open: false, choices: window.__mdlr.data.ICON_CHOICES }; },
    methods: { pick(n) { this.$emit('update:modelValue', n); this.open = false; } },
    template: `
      <div class="iconpick">
        <button type="button" class="iconpick-trigger" :class="{'is-open': open}" @click="open=!open">
          <span class="iconpick-current"><Icon :name="modelValue||'file'" :size="18"/></span>
          <span class="iconpick-label">{{ modelValue||'file' }}</span>
          <Icon name="chevDown" :size="15" class="muted"/>
        </button>
        <template v-if="open">
          <div class="iconpick-scrim" @click="open=false"></div>
          <div class="iconpick-pop">
            <div class="iconpick-grid">
              <button type="button" v-for="n in choices" :key="n" class="iconpick-opt" :class="{'is-on': modelValue===n}" @click="pick(n)" :title="n">
                <Icon :name="n" :size="18"/>
              </button>
            </div>
          </div>
        </template>
      </div>`,
  };

  /* Type badge with category tint (mono) */
  C.TypeTag = {
    name: 'TypeTag',
    props: { type: String },
    computed: {
      meta() { return window.__mdlr.data.TYPE_MAP[this.type] || { label: this.type, cat: 'util' }; },
      color() { return window.__mdlr.data.CAT_VAR[this.meta.cat] || 'var(--ink-3)'; },
    },
    template: `<span class="typetag">{{ type }}</span>`,
  };

  /* Field-type icon disc, tinted by category */
  C.TypeIcon = {
    name: 'TypeIcon',
    props: { type: String, size: { type: Number, default: 28 } },
    computed: {
      meta() { return window.__mdlr.data.TYPE_MAP[this.type] || { icon: 'dot', cat: 'util' }; },
      color() { return window.__mdlr.data.CAT_VAR[this.meta.cat] || 'var(--ink-3)'; },
    },
    template: `
      <span :style="{width:size+'px',height:size+'px',borderRadius:'7px',display:'inline-flex',alignItems:'center',justifyContent:'center',flex:'none',color:color,background:'color-mix(in srgb,'+color+' 12%, transparent)'}">
        <Icon :name="meta.icon" :size="Math.round(size*0.56)"/>
      </span>`,
  };
})();
;
/* file: code-output.js */
/* ============================================================
   CODE OUTPUT — the "see the API" layer (Payload-style).
   A reusable modal that shows JSON / PHP / HTML for any item,
   plus serializers used by the visual editor and post editor.
   window.__mdlr.serialize = { layer, component, post, copy }
   Component: <UiCodeModal :tabs="[{id,label,lang,code}]" title=.. />
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const slugify = (s) => (s || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

  /* ---- serializers ---- */
  function layerJson(l) {
    const o = { type: l.kind };
    if (l.kind === 'heading') { o.level = l.level; o.text = l.text; }
    else if (l.kind === 'text') { o.text = l.text; }
    else if (l.kind === 'image') { o.src = 'uploads/' + (l.name || 'image.jpg'); o.alt = l.alt || ''; }
    else if (l.kind === 'button') { o.label = l.label; o.url = l.url || '#'; o.variant = l.variant; }
    else if (l.kind === 'video') { o.url = l.url || ''; o.poster = 'uploads/' + (l.name || 'poster.jpg'); }
    else if (l.kind === 'gallery') { o.cols = l.cols || 3; o.images = (l.images || [1,2,3]).map((x, i) => 'uploads/frame-' + (i+1) + '.jpg'); }
    else if (l.kind === 'stack') { o.height = l.height || 'md'; }
    else if (l.kind === 'repeater') { o.items = (l.items || []).map(it => ({ ...it })); }
    else if (l.kind === 'embed') { o.html = l.code || ''; }
    // overlay placement (only when this layer is itself an overlay)
    if (l._isOverlay) o.align = l.align;
    // responsive
    if (l.hideMobile) o.hideMobile = true;
    // nested overlays for containers
    if (l.overlays && l.overlays.length) {
      if (l.stackMobile) o.stackMobile = true;
      o.overlays = l.overlays.map(ov => layerJson(Object.assign({}, ov, { _isOverlay: true })));
    }
    return o;
  }
  function componentObj(comp, layers) {
    return {
      component: slugify(comp.name),
      version: comp.version,
      layers: (layers || []).map(layerJson),
    };
  }
  function componentPhp(comp, layers) {
    const slug = slugify(comp.name);
    return [
      '<?php',
      '/* Render the "' + comp.name + '" component (v' + comp.version + ') */',
      'echo Modular::component(\'' + slug + '\', [',
      '    \'data\' => $block[\'' + slug + '\'] ?? [],',
      ']);',
      '',
      '// …or in a template:',
      '// <?php the_component(\'' + slug + '\', $data); ?>',
    ].join('\n');
  }
  function leafHtml(l, indent) {
    const p = indent || '  ';
    if (l.kind === 'heading') return p + '<h' + l.level + '>' + (l.text||'') + '</h' + l.level + '>';
    if (l.kind === 'text') return p + '<p>' + (l.text||'') + '</p>';
    if (l.kind === 'image') return p + '<img src="uploads/' + (l.name||'image.jpg') + '" alt="' + (l.alt||'') + '">';
    if (l.kind === 'button') return p + '<a class="btn btn--' + l.variant + '" href="' + (l.url||'#') + '">' + (l.label||'') + '</a>';
    if (l.kind === 'video') return p + '<video src="' + (l.url||'') + '" controls></video>';
    if (l.kind === 'gallery') return p + '<div class="gallery cols-' + (l.cols||3) + '"><!-- ' + ((l.images||[1,2,3]).length) + ' images --></div>';
    if (l.kind === 'repeater') return p + '<ul class="repeater"><!-- ' + ((l.items||[]).length) + ' rows --></ul>';
    if (l.kind === 'embed') return p + (l.code||'');
    if (l.kind === 'divider') return p + '<hr>';
    return '';
  }
  function componentHtml(comp, layers) {
    const lines = ['<section class="mdlr-' + slugify(comp.name) + '">'];
    (layers || []).forEach(l => {
      if (l.overlays && l.overlays.length) {
        lines.push('  <div class="layer layer--' + l.kind + '">');
        lines.push('  ' + leafHtml(l, '  '));
        lines.push('    <div class="overlays">');
        l.overlays.forEach(o => lines.push('    ' + leafHtml(o, '    ').trim().replace(/^/, '      ')));
        lines.push('    </div>');
        lines.push('  </div>');
      } else {
        lines.push(leafHtml(l, '  '));
      }
    });
    lines.push('</section>');
    return lines.join('\n');
  }

  // Post → REST JSON (Payload-style), built from the live editor model
  function fieldValue(f) {
    if (f.type === 'true_false') return !!f.value;
    if (f.type === 'image') return f.value ? { src: 'uploads/' + (f.value.name || 'image.jpg'), alt: f.value.alt || '' } : null;
    if (f.type === 'gallery') return [];
    if (f.type === 'repeater') return [];
    if (['relationship','post_object'].includes(f.type)) return Array.isArray(f.value) ? f.value : [];
    if (f.type === 'number') return f.value != null ? Number(f.value) : null;
    return f.value != null && f.value !== '' ? f.value : null;
  }
  function postObj(post, tabs, blocks) {
    const fields = {};
    (tabs || []).forEach(t => (t.groups || []).forEach(g => (g.fields || []).forEach(f => {
      fields[f.name || slugify(f.label)] = fieldValue(f);
    })));
    const out = {
      id: typeof post.id === 'number' ? post.id : 0,
      type: post.type,
      slug: post.slug || slugify(post.title),
      status: (post.status || 'draft').toLowerCase(),
      title: post.title,
      updatedAt: '2026-06-04T09:12:00Z',
      fields,
    };
    if (blocks && blocks.length) {
      out.blocks = blocks.map(b => {
        const c = D().COMPONENTS.find(x => x.id === b.comp);
        return { type: c ? slugify(c.name) : b.comp, _summary: b.summary };
      });
    }
    return out;
  }
  function postPhp(post) {
    const slug = post.slug || slugify(post.title);
    return [
      '<?php',
      '/* Fetch this ' + post.type + ' */',
      '$post = Modular::query(\'' + post.type + '\')->slug(\'' + slug + '\')->first();',
      '',
      'echo $post->title;',
      'echo $post->field(\'body\');',
      '',
      '// REST:  GET /api/v1/' + post.type + '/' + slug,
      '// GraphQL:  { ' + post.type + '(slug: "' + slug + '") { title } }',
    ].join('\n');
  }

  window.__mdlr.serialize = {
    layer: layerJson,
    component: componentObj,
    componentPhp, componentHtml,
    post: postObj, postPhp,
    pretty: (o) => JSON.stringify(o, null, 2),
  };

  /* ---- the modal ---- */
  C.UiCodeModal = {
    name: 'UiCodeModal',
    props: { tabs: Array, title: { type: String, default: 'API output' }, subtitle: String, endpoint: String },
    emits: ['close'],
    data() { return { active: (this.tabs[0] || {}).id, copied: false }; },
    computed: { cur() { return this.tabs.find(t => t.id === this.active) || this.tabs[0]; } },
    methods: {
      copy() {
        const txt = this.cur.code;
        const done = () => { this.copied = true; setTimeout(() => { this.copied = false; }, 1400); };
        if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(txt).then(done, done);
        else done();
      },
    },
    template: `
    <teleport to="body">
      <div class="codemodal-wrap" @click.self="$emit('close')">
        <div class="codemodal" role="dialog">
          <div class="codemodal-head">
            <span class="codemodal-ico"><Icon name="code" :size="16"/></span>
            <div style="flex:1;min-width:0"><strong>{{ title }}</strong><div class="t-meta" v-if="subtitle">{{ subtitle }}</div></div>
            <button class="iconbtn" @click="$emit('close')"><Icon name="x"/></button>
          </div>
          <div class="codemodal-bar">
            <div class="codemodal-tabs">
              <button v-for="t in tabs" :key="t.id" :class="{'is-on': active===t.id}" @click="active=t.id">{{ t.label }}</button>
            </div>
            <div class="grow"></div>
            <span class="t-key codemodal-endpoint" v-if="endpoint && cur.id==='json'">{{ endpoint }}</span>
            <button class="btn btn--soft btn--sm" @click="copy"><Icon :name="copied?'check':'copy'" :size="13"/> {{ copied ? 'Copied' : 'Copy' }}</button>
          </div>
          <pre class="codemodal-code" :class="'lang-'+(cur.lang||'json')"><code>{{ cur.code }}</code></pre>
        </div>
      </div>
    </teleport>`,
  };
})();
;
/* file: data.js */
/* ============================================================
   SPA DATA REGISTRY — shaped like the real Modular schema.
   Mirrors: post_types.schema (JSON), component_definitions,
   shared field groups, posts.data / posts.layout.
   Static field/UI registries live here; all CMS entities load from the API via store.js.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };

  /* ---- Field-type registry (the WHAT layer) ----
     category drives the muted "drawer-label" color. */
  const FIELD_TYPES = [
    // Basic
    { type: 'text',        label: 'Text',         icon: 'type',     cat: 'basic',   catLabel: 'Basic' },
    { type: 'textarea',    label: 'Textarea',     icon: 'align',    cat: 'basic',   catLabel: 'Basic' },
    { type: 'number',      label: 'Number',       icon: 'hash',     cat: 'basic',   catLabel: 'Basic' },
    { type: 'email',       label: 'Email',        icon: 'mail',     cat: 'basic',   catLabel: 'Basic' },
    { type: 'url',         label: 'URL',          icon: 'link',     cat: 'basic',   catLabel: 'Basic' },
    { type: 'color',       label: 'Color',        icon: 'palette',  cat: 'basic',   catLabel: 'Basic' },
    { type: 'password',    label: 'Password',     icon: 'lock',     cat: 'basic',   catLabel: 'Basic' },
    { type: 'range',       label: 'Range',        icon: 'sliders',  cat: 'basic',   catLabel: 'Basic' },
    { type: 'hidden',      label: 'Hidden',       icon: 'eyeOff',   cat: 'basic',   catLabel: 'Basic' },
    // Content
    { type: 'wysiwyg',     label: 'Rich text',    icon: 'text',     cat: 'content', catLabel: 'Content' },
    { type: 'markdown',    label: 'Markdown',     icon: 'file',     cat: 'content', catLabel: 'Content' },
    { type: 'code',        label: 'Code',         icon: 'code',     cat: 'content', catLabel: 'Content' },
    // Choice
    { type: 'select',      label: 'Select',       icon: 'list',     cat: 'choice',  catLabel: 'Choice' },
    { type: 'checkbox',    label: 'Checkbox',     icon: 'check',    cat: 'choice',  catLabel: 'Choice' },
    { type: 'radio',       label: 'Radio',        icon: 'radio',    cat: 'choice',  catLabel: 'Choice' },
    { type: 'button_group',label: 'Button group', icon: 'grid',     cat: 'choice',  catLabel: 'Choice' },
    { type: 'true_false',  label: 'True / False', icon: 'toggle',   cat: 'choice',  catLabel: 'Choice' },
    // Relational
    { type: 'post_object', label: 'Post object',  icon: 'file',     cat: 'rel',     catLabel: 'Relational' },
    { type: 'relationship',label: 'Relationship', icon: 'link',     cat: 'rel',     catLabel: 'Relational' },
    { type: 'taxonomy',    label: 'Taxonomy',     icon: 'pin',      cat: 'rel',     catLabel: 'Relational' },
    { type: 'user',        label: 'User',         icon: 'user',     cat: 'rel',     catLabel: 'Relational' },
    // Media
    { type: 'image',       label: 'Image',        icon: 'image',    cat: 'media',   catLabel: 'Media' },
    { type: 'gallery',     label: 'Gallery',      icon: 'grid',     cat: 'media',   catLabel: 'Media' },
    { type: 'file',        label: 'File',         icon: 'file',     cat: 'media',   catLabel: 'Media' },
    // Date & Time
    { type: 'date',        label: 'Date',         icon: 'calendar', cat: 'date',    catLabel: 'Date & Time' },
    { type: 'datetime',    label: 'Date & time',  icon: 'clock',    cat: 'date',    catLabel: 'Date & Time' },
    { type: 'time',        label: 'Time',         icon: 'clock',    cat: 'date',    catLabel: 'Date & Time' },
    // Layout (HOW — presentation only)
    { type: 'tab',         label: 'Tab',          icon: 'tab',      cat: 'layout',  catLabel: 'Layout', hint: 'Splits the editor into tabs', formsOnly: true },
    { type: 'columns',     label: 'Columns',      icon: 'columns',  cat: 'layout',  catLabel: 'Layout', hint: 'Up to 3 columns, side by side', formsOnly: true },
    { type: 'accordion',   label: 'Accordion',    icon: 'rows',     cat: 'layout',  catLabel: 'Layout', formsOnly: true },
    { type: 'divider',     label: 'Divider',      icon: 'minus',    cat: 'layout',  catLabel: 'Layout' },
    { type: 'message',     label: 'Message',      icon: 'info',     cat: 'layout',  catLabel: 'Layout', formsOnly: true },
    // Components (the new unifying concept)
    { type: 'blocks',      label: 'Blocks',       icon: 'layers',   cat: 'component', catLabel: 'Components', hint: 'Author drops & reorders components' },
    { type: 'repeater',    label: 'Repeater',     icon: 'repeat',   cat: 'component', catLabel: 'Components' },
    { type: 'group',       label: 'Group',        icon: 'box',      cat: 'component', catLabel: 'Components' },
  ];
  const TYPE_MAP = {}; FIELD_TYPES.forEach(t => TYPE_MAP[t.type] = t);
  const CAT_ORDER = [
    { cat: 'basic',     label: 'Basic' },
    { cat: 'content',   label: 'Content' },
    { cat: 'choice',    label: 'Choice' },
    { cat: 'rel',       label: 'Relational' },
    { cat: 'media',     label: 'Media' },
    { cat: 'date',      label: 'Date & Time' },
    { cat: 'layout',    label: 'Layout' },
    { cat: 'component', label: 'Components' },
  ];
  const CAT_VAR = {
    basic: 'var(--cat-basic)', content: 'var(--cat-content)', choice: 'var(--cat-choice)',
    rel: 'var(--cat-rel)', media: 'var(--cat-media)', date: 'var(--cat-date)',
    layout: 'var(--cat-layout)', component: 'var(--accent)',
  };

  let _id = 100;
  const uid = (p) => (p || 'f') + '_' + (++_id);

  const f = (label, name, type, extra) => Object.assign({
    id: uid(), label, name, key: name, type,
    required: false, instructions: '', sharedTo: [], mode: 'fixed',
  }, extra || {});

  /* API-backed collections — populated by store.hydrate(), never seeded with demo rows. */
  const FIELD_GROUPS = Vue.reactive([]);
  const COMPONENTS = Vue.reactive([]);
  const POST_TYPES = Vue.reactive([]);
  const PROJECT_SCHEMA = [];
  const RULES = [];
  const SAMPLE_POST = { id: null, title: '', slug: '', type: '', status: 'draft' };
  const POSTS = Vue.reactive([]);
  const TAXONOMIES = Vue.reactive([]);
  const MEDIA = Vue.reactive([]);
  const MEDIA_FOLDERS = Vue.reactive([]);
  const RENAME_PATTERN = { enabled: true, pattern: '{folder}-{title}-{n}' };
  const NOTIFICATIONS = Vue.reactive([]);
  const IMAGE_PRESETS_DEFAULT = [
    { name:'thumbnail', w:400,  h:400,  ratio:'1:1',    crop:'cover', on:true, hue:32 },
    { name:'card',      w:720,  h:540,  ratio:'4:3',    crop:'cover', on:true, hue:28 },
    { name:'header',    w:1440, h:750,  ratio:'≈2:1',   crop:'cover', on:true, hue:24 },
    { name:'social-og', w:1200, h:630,  ratio:'1.91:1', crop:'cover', on:true, hue:36 },
  ];
  const IMAGE_PRESETS = Vue.reactive(JSON.parse(JSON.stringify(IMAGE_PRESETS_DEFAULT)));
  const FORMS = Vue.reactive([]);
  const SUBMISSIONS = Vue.reactive([]);
  const ACTIONS = Vue.reactive([]);
  const EXPORT_TABLES = Vue.reactive([]);
  const IMPORT_HISTORY = Vue.reactive([]);
  const PROFILE = Vue.reactive({
    username: '', name: '', email: '', role: '', status: '', memberSince: '',
    avatar: '', initials: '', lastActive: '', twoFactor: false, language: 'English',
    bio: '', timezone: 'Europe/Amsterdam',
  });
  const MODULES = Vue.reactive([]);
  const MODULES_AVAILABLE = Vue.reactive([]);
  const ROLES = Vue.reactive([]);
  const SYSTEM = Vue.reactive({
    version: '', latest: '', channel: 'stable', php: '', db: '', lastChecked: '',
    changelog: [], update_available: false,
  });
  const REPORT_ROWS = Vue.reactive([]);
  const THEMES = Vue.reactive([]);

  /* ---- Location-rule registry (ACF-style “where does this group show”) ---- */
  const LOCATION_PARAMS = [
    { id:'post_type',     label:'Post type',     valuesFrom:'post_types' },
    { id:'post',          label:'Post',          valuesFrom:'posts' },
    { id:'post_template', label:'Post template', values:[{id:'default',name:'Default'},{id:'full',name:'Full width'},{id:'landing',name:'Landing'}] },
    { id:'taxonomy',      label:'Taxonomy term',  valuesFrom:'terms' },
    { id:'user_role',     label:'User role',     values:[{id:'administrator',name:'Administrator'},{id:'editor',name:'Editor'},{id:'author',name:'Author'}] },
  ];
  const LOCATION_OPS = [ { id:'==', label:'is equal to' }, { id:'!=', label:'is not equal to' } ];

  const ICON_CHOICES = [
    'file','files','box','grid','zap','image','layers','database','form','star',
    'globe','calendar','clock','pin','users','user','folder','bars','sigma','table',
    'play','code','mail','list','component','dashboard','palette','webhook','repeat','branch',
  ];

  /* ---- Roles & capabilities (what a role can see / do) ---- */
  // Capability catalog — ids are the exact capability names enforced by the
  // backend (capabilities table). Grouped with friendly labels for the matrix.
  const CAP_GROUPS = [
    { group:'Content', icon:'files', caps:[
      { id:'post:create', label:'Create content', desc:'Add new items.' },
      { id:'post:edit:own', label:'Edit own', desc:'Edit items they authored.' },
      { id:'post:edit:any', label:'Edit others’', desc:'Edit anyone’s items.' },
      { id:'post:delete:own', label:'Delete own', desc:'Trash items they authored.' },
      { id:'post:delete:any', label:'Delete others’', desc:'Trash and permanently delete any item.' },
      { id:'post:publish', label:'Publish', desc:'Move items from draft to published.' },
    ]},
    { group:'Media', icon:'image', caps:[
      { id:'media:upload', label:'Upload', desc:'Add files to the library.' },
      { id:'media:edit', label:'Edit & crop', desc:'Rename, crop and re-derive presets.' },
      { id:'media:delete', label:'Delete', desc:'Remove files.' },
    ]},
    { group:'Users', icon:'users', caps:[
      { id:'user:create', label:'Create users', desc:'Add new accounts.' },
      { id:'user:edit', label:'Edit users', desc:'Update account details.' },
      { id:'user:delete', label:'Delete users', desc:'Remove accounts.' },
      { id:'user:promote', label:'Assign roles', desc:'Change a user’s role.' },
    ]},
    { group:'System', icon:'settings', caps:[
      { id:'plugin:activate', label:'Activate modules', desc:'Turn modules on.' },
      { id:'plugin:deactivate', label:'Deactivate modules', desc:'Turn modules off.' },
      { id:'settings:edit', label:'Edit settings', desc:'Change site configuration.' },
      { id:'theme:edit', label:'Edit theme', desc:'Change the design system tokens.' },
    ]},
  ];
  const ALL_CAPS = CAP_GROUPS.flatMap(g => g.caps.map(c => c.id));

  /* ---- Reports (BI) — types must match StatsController report endpoints ---- */
  const REPORTS = [
    { id:'production', name:'Content production', icon:'files', desc:'Items created & published over time, per author and type.' },
    { id:'cadence', name:'Publishing cadence', icon:'calendar', desc:'How regularly each type ships; gaps and streaks.' },
    { id:'forms', name:'Form submissions', icon:'form', desc:'Submission counts and spam stats per form.' },
    { id:'authors', name:'Author leaderboard', icon:'users', desc:'Output per team member in the selected period.' },
    { id:'health', name:'Content health', icon:'alert', desc:'Stale items, missing excerpts, old drafts, unused media.' },
  ];
  /* Populated from settings/API when wired; starts empty. */
  const SPA_META = Vue.reactive({ ready: false, postTypesVersion: 0 });

  window.__mdlr.data = {
    FIELD_TYPES, TYPE_MAP, CAT_ORDER, CAT_VAR,
    FIELD_GROUPS, COMPONENTS, POST_TYPES, PROJECT_SCHEMA, RULES,
    SAMPLE_POST, POSTS, TAXONOMIES, MEDIA, IMAGE_PRESETS, IMAGE_PRESETS_DEFAULT, FORMS, SUBMISSIONS, ACTIONS, uid,
    EXPORT_TABLES, IMPORT_HISTORY, PROFILE, ICON_CHOICES,
    LOCATION_PARAMS, LOCATION_OPS, MODULES, MODULES_AVAILABLE,
    CAP_GROUPS, ALL_CAPS, ROLES, SYSTEM,
    STATS_OVERVIEW: null, LIVE_ANALYTICS: null, LIVE_REPORTS: null, REPORTS, REPORT_ROWS, THEMES,
    MEDIA_FOLDERS, RENAME_PATTERN, NOTIFICATIONS, SPA_META,
  };
})();
;
/* file: theme.js */
/* ============================================================
   THEME — the real, editable design-system engine.
   Applies tokens to CSS variables, persists to localStorage,
   and exports/imports a JSON file so Modular can be customised
   for real. window.__mdlr.theme = { state, set, apply, save,
   reset, exportJson, importFile, presets }.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || {};
  const KEY = 'mdlr_theme_v1';
  const R = document.documentElement.style;

  const DEFAULTS = {
    accent:'#C0552F',
    paper:'#F4F1EA', surface:'#FCFBF8', ink:'#221F1A', ink2:'#5A534A', line:'#E4DECF',
    btn:'#2A2520',
    ok:'#4F7A52', warn:'#A9802F', danger:'#A8432F', info:'#5B7089',
    catBasic:'#5B7089', catContent:'#647A53', catChoice:'#876089', catRel:'#B0764A', catMedia:'#3E7C78', catDate:'#B0893F', catLayout:'#6A6258',
    fontDisplay:'Bricolage Grotesque', fontUi:'Hanken Grotesk',
    baseSize:14, radius:8,
  };

  const ACCENT_PRESETS = [
    { id:'terracotta', label:'Terracotta', hex:'#C0552F' },
    { id:'indigo', label:'Indigo', hex:'#4C50C0' },
    { id:'forest', label:'Forest', hex:'#2F6B4F' },
    { id:'ochre', label:'Ochre', hex:'#B07A1E' },
    { id:'plum', label:'Plum', hex:'#7A4A6E' },
    { id:'petrol', label:'Petrol', hex:'#2F6B6B' },
  ];
  const FONT_DISPLAY = ['Bricolage Grotesque','Hanken Grotesk','Fraunces','Playfair Display','Space Grotesk','Libre Franklin','Lora','Georgia','system-ui'];
  const FONT_UI = ['Hanken Grotesk','Bricolage Grotesque','Inter','Source Sans 3','IBM Plex Sans','Libre Franklin','Space Grotesk','Georgia','system-ui'];
  const FALLBACK = ", -apple-system, BlinkMacSystemFont, system-ui, sans-serif";
  // Google-Fonts families loaded on demand (Bricolage/Hanken/JetBrains already in styles.css; Georgia/system-ui are native)
  const GF = {
    'Fraunces':'Fraunces:opsz,wght@9..144,400;9..144,600',
    'Playfair Display':'Playfair+Display:wght@400;500;600;700',
    'Space Grotesk':'Space+Grotesk:wght@400;500;600;700',
    'Libre Franklin':'Libre+Franklin:wght@400;500;600;700',
    'Lora':'Lora:wght@400;500;600;700',
    'Inter':'Inter:wght@400;500;600;700',
    'Source Sans 3':'Source+Sans+3:wght@400;500;600;700',
    'IBM Plex Sans':'IBM+Plex+Sans:wght@400;500;600;700',
  };
  // Fonts are vendored locally (zero external dependencies). The design-system font
  // picker no longer fetches Google Fonts at runtime; non-vendored choices fall back
  // to a system font. Vendor a font under admin/assets/vendor/fonts to make it render.
  function ensureFont(fam) { /* no external font loading */ }

  // ---- colour helpers ----
  const clamp = n => Math.max(0, Math.min(255, n));
  function toRgb(h){ h=(h||'').replace('#',''); if(h.length===3) h=h.split('').map(c=>c+c).join(''); return [parseInt(h.slice(0,2),16),parseInt(h.slice(2,4),16),parseInt(h.slice(4,6),16)]; }
  function toHex(r,g,b){ return '#'+[r,g,b].map(x=>clamp(Math.round(x)).toString(16).padStart(2,'0')).join(''); }
  function darken(h,a){ const [r,g,b]=toRgb(h); return toHex(r*(1-a),g*(1-a),b*(1-a)); }
  function lighten(h,a){ const [r,g,b]=toRgb(h); return toHex(r+(255-r)*a,g+(255-g)*a,b+(255-b)*a); }
  function mix(h1,h2,t){ const a=toRgb(h1),b=toRgb(h2); return toHex(a[0]+(b[0]-a[0])*t,a[1]+(b[1]-a[1])*t,a[2]+(b[2]-a[2])*t); }
  function rgba(h,al){ const [r,g,b]=toRgb(h); return `rgba(${r},${g},${b},${al})`; }

  let stored = {};
  try { stored = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { stored = {}; }
  const state = (window.Vue ? Vue.reactive : (x=>x))(Object.assign({}, DEFAULTS, stored));

  function apply() {
    const t = state;
    // accent family (derived)
    R.setProperty('--accent', t.accent);
    R.setProperty('--accent-deep', darken(t.accent, 0.18));
    R.setProperty('--accent-soft', mix(t.accent, t.paper, 0.80));
    R.setProperty('--accent-tint', mix(t.accent, t.paper, 0.92));
    R.setProperty('--ring', rgba(t.accent, 0.32));
    // neutrals
    R.setProperty('--paper', t.paper);
    R.setProperty('--paper-2', mix(t.paper, t.ink, 0.05));
    R.setProperty('--paper-3', mix(t.paper, t.ink, 0.09));
    R.setProperty('--surface', t.surface);
    R.setProperty('--surface-2', lighten(t.surface, 0.5));
    R.setProperty('--ink', t.ink);
    R.setProperty('--ink-2', t.ink2);
    R.setProperty('--ink-3', mix(t.ink, t.paper, 0.42));
    R.setProperty('--ink-4', mix(t.ink, t.paper, 0.62));
    R.setProperty('--line', t.line);
    R.setProperty('--line-2', darken(t.line, 0.07));
    R.setProperty('--line-3', darken(t.line, 0.15));
    // action
    R.setProperty('--btn', t.btn);
    R.setProperty('--btn-hover', darken(t.btn, 0.25));
    R.setProperty('--on-btn', lighten(t.btn, 0.92));
    // status (+ soft tints)
    ['ok','warn','danger','info'].forEach(k => { R.setProperty('--' + k, t[k]); R.setProperty('--' + k + '-soft', mix(t[k], t.paper, 0.82)); });
    // field-category hues
    const catMap = { catBasic:'--cat-basic', catContent:'--cat-content', catChoice:'--cat-choice', catRel:'--cat-rel', catMedia:'--cat-media', catDate:'--cat-date', catLayout:'--cat-layout' };
    Object.keys(catMap).forEach(k => { if (t[k]) R.setProperty(catMap[k], t[k]); });
    // type
    ensureFont(t.fontDisplay); ensureFont(t.fontUi);
    R.setProperty('--font-display', "'" + t.fontDisplay + "'" + FALLBACK);
    R.setProperty('--font-ui', "'" + t.fontUi + "'" + FALLBACK);
    if (document.body) document.body.style.fontSize = t.baseSize + 'px';
    // shape
    R.setProperty('--r-md', t.radius + 'px');
    R.setProperty('--r-sm', Math.max(2, t.radius - 2) + 'px');
    R.setProperty('--r-lg', (t.radius + 4) + 'px');
    R.setProperty('--r-xs', Math.max(2, t.radius - 4) + 'px');
  }
  function save() { try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {} }
  function set(key, val) { state[key] = val; apply(); save(); }
  function setMany(obj) { Object.assign(state, obj); apply(); save(); }
  function reset() { Object.assign(state, DEFAULTS); apply(); try { localStorage.removeItem(KEY); } catch (e) {} }
  function exportJson() {
    const blob = new Blob([JSON.stringify(state, null, 2)], { type:'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob); a.download = 'modular-theme.json';
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  }
  function importFile(file) {
    return file.text().then(txt => { const obj = JSON.parse(txt); setMany(obj); return true; });
  }
  function isDefault() { return Object.keys(DEFAULTS).every(k => state[k] === DEFAULTS[k]); }

  window.__mdlr.theme = { state, DEFAULTS, ACCENT_PRESETS, FONT_DISPLAY, FONT_UI, apply, save, set, setMany, reset, exportJson, importFile, isDefault };

  // apply immediately on load (before mount) and again after DOM ready for body font-size
  apply();
  document.addEventListener('DOMContentLoaded', apply);
})();
;
/* file: mode-help.js */
/* ============================================================
   CONTENT MODEL — the single, canonical Fixed vs Modular story.
   One explanation, referenced everywhere via <ModeHelp/>.
   - ModeExplainerBody : the reusable content block
   - ModeHelp          : a compact inline trigger -> opens the modal
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;

  /* The canonical explanation, used in the Settings page and the modal. */
  C.ModeExplainerBody = {
    name: 'ModeExplainerBody',
    template: `
    <div class="me">
      <p class="me-lead">Every item in Modular is <strong>composable</strong>. Whether a single field is locked in or offered optionally is a property of <strong>the field</strong>, set once in the field builder — not of the post type.</p>

      <div class="mode-explain">
        <div class="mode-card">
          <div class="mode-card-h"><span class="mode-ico mode-ico--fixed"><Icon name="lock" :size="16"/></span><strong>Fixed fields</strong></div>
          <p>Locked into every item of a type. Always in the editor — editors fill them in but can't add, reorder or remove them. The structured backbone.</p>
        </div>
        <div class="mode-arrow"><Icon name="plus" :size="18"/></div>
        <div class="mode-card">
          <div class="mode-card-h"><span class="mode-ico mode-ico--modular"><Icon name="layers" :size="16"/></span><strong>Modular fields</strong></div>
          <p>Offered per item. Editors add, reorder and remove them themselves — including dropping in components. The composable layer on top.</p>
        </div>
      </div>

      <div class="me-prec">
        <div class="me-prec-h"><Icon name="branch" :size="15"/> How a field's availability is decided</div>
        <div class="prec" style="margin:0">
          <div class="prec-step"><span class="prec-num">1</span><div><strong>Field registry</strong><div class="t-meta">Typed definitions — the WHAT.</div></div></div>
          <Icon name="arrowRight" :size="16" class="prec-arrow"/>
          <div class="prec-step"><span class="prec-num">2</span><div><strong>Post-type default</strong><div class="t-meta">Bound as fixed or modular.</div></div></div>
          <Icon name="arrowRight" :size="16" class="prec-arrow"/>
          <div class="prec-step"><span class="prec-num">3</span><div><strong>Per-item override</strong><div class="t-meta">Editors add modular fields in the editor.</div></div></div>
        </div>
      </div>

      <div class="me-prov">
        <div class="flex items-center gap-2"><span class="prov prov--type"><Icon name="lock" :size="11"/> From post type</span> fixed, always shown</div>
        <div class="flex items-center gap-2"><span class="prov prov--post"><Icon name="plus" :size="11"/> Added here</span> modular, this item only</div>
        <div class="flex items-center gap-2"><span class="prov prov--comp"><Icon name="git" :size="11"/> Shared group</span> reused across types</div>
      </div>
    </div>`,
  };

  /* Compact inline trigger — drop this anywhere instead of repeating the explanation. */
  C.ModeHelp = {
    name: 'ModeHelp',
    props: { label: { type: String, default: 'What\u2019s Fixed vs Modular?' } },
    data() { return { open: false }; },
    template: `
    <span style="display:inline-flex">
      <button type="button" class="mode-help-link" @click="open=true">
        <Icon name="helpCircle" :size="14"/> {{ label }}
      </button>
      <UiModal v-if="open" lg @close="open=false">
        <div class="drawer-head">
          <span class="mode-ico mode-ico--modular"><Icon name="layers" :size="16"/></span>
          <div style="flex:1"><strong style="font-size:15px">Fixed vs Modular</strong><div class="t-meta">How Modular models content</div></div>
          <button class="iconbtn" @click="open=false"><Icon name="x"/></button>
        </div>
        <div class="drawer-body"><ModeExplainerBody/></div>
        <div class="drawer-foot">
          <button class="btn btn--ghost" @click="open=false">Close</button>
        </div>
      </UiModal>
    </span>`,
  };
})();
;
/* file: cmdk.js */
/* ============================================================
   COMMAND PALETTE — the "Search or jump to" / ⌘K launcher.
   Filters destinations, content and actions; arrow-key + enter.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const plural = (n) => /s$/i.test(n) ? n : n + 's';

  C.UiCmdK = {
    name: 'UiCmdK',
    inject: ['go', 'ui'],
    data() { return { q: '', sel: 0 }; },
    computed: {
      commands() {
        const go = this.go, list = [];
        const nav = [
          ['Dashboard','dashboard',()=>go('dashboard',{})],
          ['Updates','refresh',()=>go('dashboard',{tab:'updates'})],
          ['Analytics','sigma',()=>go('dashboard',{tab:'analytics'})],
          ['Reports','table',()=>go('dashboard',{tab:'reports'})],
          ['Media library','image',()=>go('media',{})],
          ['Field types','grid',()=>go('library',{})],
          ['Field groups','git',()=>go('binding',{})],
          ['Post types','database',()=>go('postTypes',{})],
          ['Taxonomies','pin',()=>go('taxonomies',{})],
          ['Components','layers',()=>go('components',{})],
          ['Forms','form',()=>go('forms',{})],
          ['Actions','zap',()=>go('actions',{})],
          ['Modules','layers',()=>go('modules',{})],
          ['Users','users',()=>go('users',{})],
          ['Roles & capabilities','lock',()=>go('roles',{})],
          ['Export / Import','refresh',()=>go('exportImport',{})],
          ['Profile','user',()=>go('profile',{})],
          ['Settings','settings',()=>go('settings',{})],
          ['Design system','palette',()=>go('settings',{tab:'design'})],
          ['Appearance · themes','palette',()=>go('settings',{tab:'appearance'})],
          ['Permalinks','link',()=>go('settings',{tab:'permalinks'})],
        ];
        nav.forEach(n => list.push({ group:'Go to', label:n[0], icon:n[1], run:n[2] }));
        D().POST_TYPES.forEach(pt => list.push({ group:'Content', label:'All ' + plural(pt.name).toLowerCase(), icon:pt.icon, hint:'list', run:()=>go('content',{type:pt.id}) }));
        list.push({ group:'Create', label:'New content', icon:'plus', run:()=>go('editor',{postId:'new',type:'project'}) });
        list.push({ group:'Create', label:'New field group', icon:'plus', run:()=>go('fieldBuilder',{group:'new'}) });
        list.push({ group:'Create', label:'New post type', icon:'plus', run:()=>go('postTypes',{}) });
        const tn = (t) => { const p = D().POST_TYPES.find(x=>x.id===t); return p?p.name:t; };
        D().POSTS.forEach(p => list.push({ group:'Content', label:p.title, icon:'file', hint:tn(p.type), run:()=>go('editor',{postId:p.id,type:p.type}) }));
        return list;
      },
      filtered() {
        const q = this.q.trim().toLowerCase();
        if (!q) return this.commands.filter(c => c.group === 'Go to' || c.group === 'Create');
        return this.commands.filter(c => c.label.toLowerCase().includes(q) || (c.hint && c.hint.toLowerCase().includes(q)) || c.group.toLowerCase().includes(q));
      },
      grouped() {
        const order = ['Go to','Create','Content']; const map = {};
        this.filtered.forEach(c => { (map[c.group] = map[c.group] || []).push(c); });
        return order.filter(g => map[g]).map(g => ({ group:g, items:map[g] }));
      },
      flat() { return this.grouped.flatMap(g => g.items); },
    },
    watch: { q() { this.sel = 0; } },
    mounted() { this.$nextTick(() => this.$refs.q && this.$refs.q.focus()); },
    methods: {
      close() { this.ui.cmdk = false; },
      run(c) { c.run(); this.close(); },
      onKey(e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); this.sel = (this.sel + 1) % Math.max(1, this.flat.length); this.scroll(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); this.sel = (this.sel - 1 + this.flat.length) % Math.max(1, this.flat.length); this.scroll(); }
        else if (e.key === 'Enter') { e.preventDefault(); const c = this.flat[this.sel]; if (c) this.run(c); }
        else if (e.key === 'Escape') { e.preventDefault(); this.close(); }
      },
      scroll() { this.$nextTick(() => { const el = this.$el.querySelector('.cmdk-item.is-sel'); if (el) el.scrollIntoView({ block:'nearest' }); }); },
      idx(c) { return this.flat.indexOf(c); },
    },
    template: `
    <teleport to="body">
      <div class="scrim" @click="close"></div>
      <div class="cmdk-wrap" @click.self="close">
        <div class="cmdk" role="dialog">
          <div class="cmdk-input">
            <Icon name="search" :size="17"/>
            <input ref="q" v-model="q" placeholder="Search or jump to…" @keydown="onKey">
            <span class="kbd">esc</span>
          </div>
          <div class="cmdk-results">
            <template v-for="g in grouped" :key="g.group">
              <div class="cmdk-group">{{ g.group }}</div>
              <button v-for="c in g.items" :key="c.group+c.label" class="cmdk-item" :class="{'is-sel': idx(c)===sel}" @click="run(c)" @mousemove="sel=idx(c)">
                <span class="cmdk-ico"><Icon :name="c.icon" :size="15"/></span>
                <span class="cmdk-label">{{ c.label }}</span>
                <span class="t-meta" v-if="c.hint">{{ c.hint }}</span>
                <Icon name="arrowRight" :size="14" class="cmdk-go"/>
              </button>
            </template>
            <div v-if="!flat.length" class="cmdk-empty"><Icon name="search" :size="22"/><div>No matches for “{{ q }}”.</div></div>
          </div>
          <div class="cmdk-foot">
            <span><span class="kbd">↑</span><span class="kbd">↓</span> navigate</span>
            <span><span class="kbd">↵</span> open</span>
            <div class="grow"></div>
            <span>{{ flat.length }} result{{ flat.length===1?'':'s' }}</span>
          </div>
        </div>
      </div>
    </teleport>`,
  };
})();
;
/* file: shell.js */
/* App shell: sidebar nav + topbar. Renders the active screen via <component :is>. */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;

  const D = () => window.__mdlr.data;
  const taxFor = (typeId) => {
    const pt = D().POST_TYPES.find(p => p.id === typeId);
    return ((pt && pt.taxonomies) || []).map(id => D().TAXONOMIES.find(t => t.id === id)).filter(Boolean);
  };
  const plural = (n) => /s$/i.test(n) ? n : n + 's';
  function buildContentItems() {
    return D().POST_TYPES.filter(t => t.inMenu).map(t => ({
      name:'content', label: plural(t.name), icon: t.icon, params:{ type:t.id },
      children:[
        { label:'All '+plural(t.name).toLowerCase(), name:'content', params:{ type:t.id } },
        { label:'Add new', name:'editor', params:{ postId:'new', type:t.id } },
        ...taxFor(t.id).map(tx => ({ label: tx.name, name:'taxonomies', params:{ tax: tx.id } })),
      ],
    }));
  }

  // Module menu icons come as FontAwesome ('fas fa-users'); map to our icon set.
  const FA_ICON = { users:'users', user:'user', cog:'settings', gear:'settings', sliders:'sliders', music:'layers', calendar:'calendar', 'calendar-alt':'calendar', map:'pin', 'map-marker-alt':'pin', 'map-pin':'pin', envelope:'mail', comments:'mail', 'comment-dots':'mail', 'chart-line':'sigma', 'puzzle-piece':'box', flag:'pin', star:'star', list:'list', cubes:'layers', boxes:'layers', 'layer-group':'layers', database:'database', image:'image', zap:'zap', bolt:'zap' };
  function mapModuleIcon(icon) { if (!icon) return 'box'; const m = String(icon).match(/fa-([a-z0-9-]+)/); if (m) return FA_ICON[m[1]] || 'box'; return icon; }

  // Distribute module-registered menu items. A module item may set `section`
  // (e.g. 'Content','Structure','System') to place itself anywhere in the nav;
  // otherwise it appears as a caption-less group at the end.
  function applyModuleNav(nav) {
    const menu = (window.__MDLR && window.__MDLR.menu) || [];
    if (!menu.length) return;
    const extras = [];
    menu.forEach(m => {
      const subs = (m.sub || []).map(s => ({ label: s.label, route: 'modulePage', params: { url: s.url, title: s.label } }));
      const item = { label: m.label, icon: mapModuleIcon(m.icon), _mod: true, route: 'modulePage', params: { url: m.url, title: m.label }, match: m.match, children: subs.length ? subs : undefined };
      const sec = (m.section || '').toLowerCase();
      const grp = sec ? nav.find(g => (g.label || '').toLowerCase() === sec) : null;
      if (grp) grp.items.push(item); else extras.push(item);
    });
    if (extras.length) nav.push({ label: '', items: extras });
  }

  function buildNav() {
    const nav = [
      { items: [ { name:'dashboard', label:'Dashboard', icon:'dashboard', children:[
        { label:'Overview', name:'dashboard' },
        { label:'Updates', name:'dashboard', params:{ tab:'updates' } },
        { label:'Analytics', name:'dashboard', params:{ tab:'analytics' } },
        { label:'Reports', name:'dashboard', params:{ tab:'reports' } },
      ] } ] },
      { label: 'Content', items: [
        ...buildContentItems(),
        { name:'media', label:'Media', icon:'image', children:[
          { label:'Library', name:'media' },
          { label:'Upload', name:'media', params:{ upload:1 } },
        ]},
      ]},
      { label: 'Structure', items: [
        // Post types first: they carry a type's own fields. Field groups only
        // add extra fields on top, conditionally.
        { name:'postTypes', label:'Post types', icon:'database', children:[
          { label:'All types', name:'postTypes' },
          { label:'Taxonomies', name:'taxonomies' },
        ]},
        { name:'components', label:'Blocks', icon:'layers' },
        // Field types = catalog (reference). Field groups live under it —
        // they compose those types into entry forms.
        { name:'library', label:'Field types', icon:'grid', children:[
          { label:'All field types', name:'library' },
          { label:'Field groups', name:'binding' },
        ]},
        { name:'forms', label:'Forms', icon:'form' },
      ]},
      { label: 'Automation', items: [
        { name:'actions', label:'Actions', icon:'zap' },
      ]},
      { label: 'System', items: [
        { name:'modules', label:'Modules', icon:'layers' },
        { name:'users', label:'Users', icon:'users', children:[
          { label:'All users', name:'users' },
          { label:'Roles & capabilities', name:'roles' },
          { label:'Profile', name:'profile' },
        ]},
        { name:'exportImport', label:'Export / Import', icon:'refresh', children:[
          { label:'Export', name:'exportImport' },
          { label:'Import', name:'exportImport', params:{ tab:'import' } },
          { label:'History', name:'exportImport', params:{ tab:'history' } },
        ]},
        { name:'settings', label:'Settings', icon:'settings', children:[
          { label:'General', name:'settings' },
          { label:'Appearance', name:'settings', params:{ tab:'appearance' } },
          { label:'Media images', name:'settings', params:{ tab:'media' } },
          { label:'Headless API', name:'settings', params:{ tab:'headless' } },
          { label:'Design system', name:'settings', params:{ tab:'design' } },
        ]},
        { name:'systemUpdate', label:'System update', icon:'refresh' },
      ]},
    ];
    applyModuleNav(nav);
    return nav;
  }

  C.AppShell = {
    name: 'AppShell',
    inject: ['route', 'go', 'tweaks', 'ui'],
    props: { screen: String },
    watch: { 'route.name'() { this.ui.sidebar = false; }, 'route.params'() { this.ui.sidebar = false; } },
    data() { return { collapsed: false, q: '', openMods: {}, boot: (window.__MDLR || {}),
      reEmail: ((((window.__MDLR || {}).user) || {}).email) || '', rePass: '', reErr: '', reBusy: false,
      markSvg: `<svg viewBox="0 0 86 78" width="24" height="22" aria-label="Modular"><defs><linearGradient id="mdlrM" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#221F1A"/><stop offset="1" stop-color="#C0552F"/></linearGradient></defs><path d="M1.44,75.812l8.08,-48.479l7.927,15.092l-5.336,33.386l-10.671,0Zm69.516,-74.852l2.744,16.617l-31.252,59.912l-31.404,-60.065l2.744,-16.464l28.66,55.186l28.508,-55.186Zm4.269,26.069l8.08,48.783l-10.671,0l-5.336,-33.539l7.927,-15.245Z" fill="url(#mdlrM)"/></svg>`,
    }; },
    computed: {
      nav() { return buildNav(); },
      ptName() {
        const t = this.route.params && this.route.params.type;
        const pt = window.__mdlr.data.POST_TYPES.find(p => p.id === t);
        return pt ? pt.name : '';
      },
      notifications() { return window.__mdlr.data.NOTIFICATIONS; },
      unreadCount() { return this.notifications.filter(n => !n.read).length; },
      sysVersion() { return (window.__mdlr.data.SYSTEM || {}).version || '1.0.0'; },
      me() {
        const u = (window.__MDLR && window.__MDLR.user) || {};
        const name = u.name || u.email || 'User';
        const role = u.role || '';
        return { name, email: u.email || '', role, roleLabel: role ? role.charAt(0).toUpperCase() + role.slice(1) : '' };
      },
      initials() {
        const n = (this.me.name || '').trim();
        if (!n) return 'U';
        const parts = n.split(/\s+/);
        return ((parts[0][0] || '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
      },
    },
    template: `
    <div class="shell" :class="{'shell--collapsed': collapsed, 'shell--drawer': ui.sidebar}">
      <!-- mobile drawer scrim -->
      <div class="side-scrim" @click="ui.sidebar=false"></div>
      <!-- SIDEBAR -->
      <aside class="side">
        <div class="side-brand">
          <span class="brand-mark" v-html="markSvg"></span>
          <span class="brand-word" v-show="!collapsed">Modular</span>
          <button class="iconbtn iconbtn--sm side-collapse" @click="collapsed=!collapsed" :title="collapsed?'Expand':'Collapse'">
            <Icon :name="collapsed?'chevRight':'chevLeft'"/>
          </button>
        </div>

        <nav class="side-nav">
          <div v-for="(sec,si) in nav" :key="si" class="side-sec">
            <div v-if="sec.label && !collapsed" class="side-sec-label">{{ sec.label }}</div>
            <div v-else-if="sec.label && collapsed" class="side-sec-rule"></div>
            <template v-for="(it,ii) in sec.items" :key="ii">
              <button class="side-item" :class="{'is-on': isOn(it), 'is-open': isOpen(it)}" @click="goItem(it)" :title="collapsed ? it.label : ''">
                <Icon :name="it.icon" :size="18"/>
                <span v-show="!collapsed">{{ it.label }}</span>
                <Icon v-if="it.children && !collapsed" :name="isOpen(it)?'chevDown':'chevRight'" :size="14" class="side-chev"/>
              </button>
              <div v-if="it.children && isOpen(it) && !collapsed" class="side-sub">
                <button v-for="(c,ci) in it.children" :key="ci" class="side-subitem" :class="{'is-on': subOn(c)}" @click="c.route ? go(c.route, c.params) : (c.href ? goHref(c.href) : go(c.name, c.params))">{{ c.label }}</button>
              </div>
            </template>
          </div>
        </nav>

        <div class="side-foot">
          <div class="side-user" v-show="!collapsed" @click="go('profile')">
            <span class="avatar">{{ initials }}</span>
            <span class="side-user-meta">
              <strong>{{ me.name }}</strong>
              <span class="muted">{{ me.roleLabel }}</span>
            </span>
            <Icon name="chevRight" :size="16" class="muted"/>
          </div>
          <span class="avatar" v-show="collapsed" @click="go('profile')" style="cursor:pointer">{{ initials }}</span>
        </div>
      </aside>

      <!-- MAIN -->
      <div class="main">
        <header class="topbar">
          <button class="iconbtn topbar-burger" @click="ui.sidebar=true" title="Menu"><Icon name="menu" :size="19"/></button>
          <div class="crumbs">
            <Icon name="home" :size="15" class="muted"/>
            <span class="crumb-sep">/</span>
            <template v-if="route.name==='binding' || route.name==='fieldBuilder'">
              <button class="crumb crumb--link" type="button" @click="go('library')">Field types</button>
              <span class="crumb-sep">/</span>
              <button v-if="route.name==='fieldBuilder'" class="crumb crumb--link" type="button" @click="go('binding')">Field groups</button>
              <span v-else class="crumb crumb--here">Field groups</span>
              <template v-if="route.name==='fieldBuilder'"><span class="crumb-sep">/</span><span class="crumb crumb--here">Edit group</span></template>
            </template>
            <template v-else>
              <span class="crumb">{{ ptName || crumbFor(route.name) }}</span>
              <template v-if="route.name==='editor'"><span class="crumb-sep">/</span><span class="crumb crumb--here">Edit</span></template>
            </template>
          </div>
          <div class="topbar-search" @click="ui.cmdk = true">
            <Icon name="search" :size="15"/>
            <span>Search or jump to…</span>
            <span class="kbd">⌘K</span>
          </div>
          <button class="iconbtn topbar-search-m" @click="ui.cmdk = true" title="Search"><Icon name="search" :size="18"/></button>
          <div class="topbar-actions">
            <button class="iconbtn" title="Design system" @click="go('settings',{tab:'design'})">
              <Icon name="sliders" :size="17"/>
            </button>
            <div class="topbar-pop-wrap">
              <button class="iconbtn topbar-bell" :class="{'is-on-accent': ui.notif}" title="Notifications" @click="ui.notif = !ui.notif; ui.acct=false">
                <Icon name="bell" :size="17"/>
                <span class="notif-dot" v-if="unreadCount">{{ unreadCount }}</span>
              </button>
              <div v-if="ui.notif" class="pop-scrim" @click="ui.notif=false"></div>
              <div v-if="ui.notif" class="notif-pop">
                <div class="notif-pop-head"><strong>Notifications</strong><button class="btn btn--text btn--sm" @click="markAllRead">Mark all read</button></div>
                <div class="notif-list">
                  <button v-for="n in notifications" :key="n.id" class="notif-item" :class="{'is-unread':!n.read}" @click="openNotif(n)">
                    <span class="notif-ico"><Icon :name="n.icon" :size="15"/></span>
                    <span class="notif-main"><strong>{{ n.title }}</strong><span class="notif-body">{{ n.body }}</span><span class="t-meta">{{ n.when }}</span></span>
                    <span class="notif-unread" v-if="!n.read"></span>
                  </button>
                </div>
                <button class="notif-pop-foot" @click="go('notifications');ui.notif=false">View all notifications <Icon name="arrowRight" :size="14"/></button>
              </div>
            </div>
            <div class="topbar-pop-wrap">
              <button class="iconbtn" :class="{'is-on-accent': ui.help}" title="Help" @click="ui.help = !ui.help; ui.acct=false; ui.notif=false"><Icon name="helpCircle" :size="17"/></button>
              <div v-if="ui.help" class="pop-scrim" @click="ui.help=false"></div>
              <div v-if="ui.help" class="help-pop">
                <a class="help-feature" href="https://modularcms.org/docs" target="_blank" rel="noopener" @click="ui.help=false">
                  <span class="help-feature-ico"><Icon name="external" :size="16"/></span>
                  <span class="help-feature-main"><strong>Documentation</strong><span class="t-meta">Full guides at modularcms.org/docs</span></span>
                  <Icon name="arrowUpRight" :size="15" class="muted"/>
                </a>
                <button class="acct-item" @click="ui.modeHelp=true; ui.help=false"><Icon name="layers" :size="15"/> Fixed vs Modular</button>
                <button class="acct-item" @click="ui.cmdk=true; ui.help=false"><Icon name="search" :size="15"/> Search &amp; commands <span class="kbd" style="margin-left:auto">⌘K</span></button>
                <button class="acct-item" @click="go('dashboard',{tab:'updates'}); ui.help=false"><Icon name="refresh" :size="15"/> What's new</button>
                <div class="acct-sep"></div>
                <div class="help-foot"><span class="t-meta">Modular v{{ sysVersion }}</span><a class="mode-help-link" href="https://modularcms.org" target="_blank" rel="noopener" style="display:inline-flex">modularcms.org</a></div>
              </div>
            </div>
            <div class="topbar-pop-wrap">
              <button class="topbar-acct" :class="{'is-on':ui.acct}" title="Account" @click="ui.acct = !ui.acct; ui.notif=false">
                <span class="avatar avatar--round" style="width:30px;height:30px;font-size:11px">{{ initials }}</span>
                <Icon name="chevDown" :size="14" class="muted hide-sm"/>
              </button>
              <div v-if="ui.acct" class="pop-scrim" @click="ui.acct=false"></div>
              <div v-if="ui.acct" class="acct-pop">
                <div class="acct-pop-head"><span class="avatar avatar--round" style="width:38px;height:38px;font-size:13px">{{ initials }}</span><div><strong>{{ me.name }}</strong><div class="t-meta">{{ me.email }}</div></div></div>
                <button class="acct-item" @click="go('profile');ui.acct=false"><Icon name="user" :size="15"/> Your profile</button>
                <button class="acct-item" @click="go('settings',{tab:'security'});ui.acct=false"><Icon name="lock" :size="15"/> Security</button>
                <button class="acct-item" @click="go('settings');ui.acct=false"><Icon name="settings" :size="15"/> Settings</button>
                <div class="acct-sep"></div>
                <a class="acct-item" :href="(boot.baseUrl||'') + '/'" target="_blank" rel="noopener"><Icon name="external" :size="15"/> View site</a>
                <a class="acct-item is-danger" :href="(boot.baseUrl||'') + '/admin/auth/logout.php'"><Icon name="logout" :size="15"/> Log out</a>
              </div>
            </div>
          </div>
        </header>

        <main class="canvas" :key="route.name + JSON.stringify(route.params||{})">
          <component :is="screen"/>
        </main>
      </div>

      <!-- COMMAND PALETTE -->
      <UiCmdK v-if="ui.cmdk"/>

      <!-- FIXED vs MODULAR explainer (from help menu) -->
      <UiModal v-if="ui.modeHelp" lg @close="ui.modeHelp=false">
        <div class="drawer-head">
          <span class="mode-ico mode-ico--modular"><Icon name="layers" :size="16"/></span>
          <div style="flex:1"><strong style="font-size:15px">Fixed vs Modular</strong><div class="t-meta">How Modular models content</div></div>
          <button class="iconbtn" @click="ui.modeHelp=false"><Icon name="x"/></button>
        </div>
        <div class="drawer-body"><ModeExplainerBody/></div>
        <div class="drawer-foot">
          <a class="btn btn--ghost" href="https://modularcms.org/docs" target="_blank" rel="noopener"><Icon name="external" :size="15"/> Full docs</a>
          <button class="btn" @click="ui.modeHelp=false">Got it</button>
        </div>
      </UiModal>

      <!-- SESSION EXPIRED — re-login modal (popped on API 401). Not dismissable; -->
      <!-- after login the failed requests retry so the user stays on the same page. -->
      <UiModal v-if="ui.loginModal" @close="()=>{}">
        <div class="drawer-head">
          <span class="mode-ico mode-ico--modular"><Icon name="lock" :size="16"/></span>
          <div style="flex:1"><strong style="font-size:15px">Session expired</strong><div class="t-meta">Log back in to continue where you left off</div></div>
        </div>
        <div class="drawer-body">
          <div class="field"><label class="field-label">Email</label><input class="inp" type="email" v-model="reEmail" autocomplete="username" @keyup.enter="doReauth"></div>
          <div class="field"><label class="field-label">Password</label><input class="inp" type="password" v-model="rePass" autocomplete="current-password" @keyup.enter="doReauth"></div>
          <div v-if="reErr" style="color:var(--danger);font-size:12.5px;margin-top:8px">{{ reErr }}</div>
        </div>
        <div class="drawer-foot">
          <button class="btn" :disabled="reBusy" @click="doReauth">{{ reBusy ? 'Signing in…' : 'Log in' }}</button>
        </div>
      </UiModal>
    </div>`,
    methods: {
      async doReauth() {
        if (this.reBusy) return;
        this.reErr = '';
        if (!this.reEmail || !this.rePass) { this.reErr = 'Enter your email and password.'; return; }
        this.reBusy = true;
        try {
          await window.__mdlr.reauth(this.reEmail, this.rePass);
          this.rePass = '';
        } catch (e) {
          this.reErr = (e && e.message) || 'Login failed';
        } finally {
          this.reBusy = false;
        }
      },
      openNotif(n) { n.read = true; this.ui.notif = false; if (n.go) this.go(n.go[0], n.go[1]); },
      markAllRead() { this.notifications.forEach(n => { n.read = true; }); },
      matches(t) {
        if (t.name !== this.route.name) return false;
        const rp = this.route.params || {}, tp = t.params || {};
        if ('type' in tp) return rp.type === tp.type;
        if ('tax' in tp) return rp.tax === tp.tax;
        if ('tab' in tp) return rp.tab === tp.tab;
        if ('upload' in tp) return !!rp.upload;
        if (t.name === 'media') return !rp.upload;
        if (t.name === 'settings') return !rp.tab;
        if (t.name === 'dashboard') return !rp.tab;
        if (t.name === 'exportImport') return !rp.tab || rp.tab === 'export';
        return true;
      },
      isOn(it) { return !it.children && this.matches(it); },
      subOn(c) {
        if (c.route) return this.route.name === 'modulePage' && this.route.params && this.route.params.url === c.params.url;
        if (c.name === 'binding' && this.route.name === 'fieldBuilder' && !(this.route.params && this.route.params.type)) return true;
        return this.matches(c);
      },
      isOpen(it) {
        if (!it.children) return false;
        if (it._mod) return !!this.openMods[it.label];
        if (this.matches(it) || it.children.some(c => this.subOn(c))) return true;
        if (it.name === 'library' && (this.route.name === 'fieldBuilder' || this.route.name === 'binding')) return true;
        if (it.name === 'postTypes' && (this.route.name === 'taxonomies' || (this.route.name === 'fieldBuilder' && this.route.params && this.route.params.type))) return true;
        return false;
      },
      goItem(it) {
        if (it._mod) { if (it.children && it.children.length) { this.openMods[it.label] = !this.openMods[it.label]; } else if (it.route) { this.go(it.route, it.params); } return; }
        if (it.href) { window.location.href = it.href; return; }
        this.go(it.name, it.params);
      },
      goHref(url) { window.location.href = url; },
      crumbFor(n) {
        const map = { dashboard:'Dashboard', postTypes:'Post types', taxonomies:'Taxonomies', components:'Blocks', binding:'Field groups',
          forms:'Forms', actions:'Actions', users:'Users', settings:'Settings', design:'Design system', exportImport:'Export / Import', profile:'Profile', modules:'Modules', roles:'Roles', analytics:'Analytics',
          media:'Media', editor:'Content', fieldBuilder:'Field groups', library:'Field types', notifications:'Notifications' };
        return map[n] || '';
      },
    },
  };
})();
;
/* file: primitives.js */
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
;
/* file: field-registry.js */
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
    computed: {
      // Affix already shows https:// — keep the input as host/path only.
      host() {
        return String(this.modelValue || '').trim().replace(/^https?:\/\//i, '').replace(/^\/\//, '');
      },
    },
    methods: {
      setHost(v) {
        this.set(String(v || '').trim().replace(/^https?:\/\//i, '').replace(/^\/\//, ''));
      },
    },
    template: `<div class="inp-group"><span class="affix">https://</span><input class="inp mono" :value="host" @input="setHost($event.target.value)" placeholder="example.com"></div>`,
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
        // While the HTML pane is open the textarea owns the value; pushing every
        // keystroke back into the hidden editor would fight the caret for nothing.
        if (this.sourceView) return;
        if (this.useTT) { if (this.tiptap && this.tiptap.getHTML() !== (v || '')) { try { this.tiptap.commands.setContent(v || '', false); } catch (e) {} } return; }
        // only rewrite when the editor isn't focused, to avoid caret jumps
        if (this.$refs.body && document.activeElement !== this.$refs.body && this.$refs.body.innerHTML !== (v || '')) {
          this.$refs.body.innerHTML = v || '';
        }
      },
    },
    methods: {
      async initTiptap(content) {
        try {
          const vbase = (window.__MDLR && window.__MDLR.baseUrl || '') + '/admin/assets/vendor';
          const tt = await import(vbase + '/tiptap/tiptap.bundle.js');   // vendored locally — no external deps
          const StarterKit = tt.StarterKit;
          if (this.$refs.tt) this.$refs.tt.innerHTML = ''; // drop any view left by a previous editor
          this.tiptap = new tt.Editor({ element: this.$refs.tt, extensions: [StarterKit], content: content != null ? content : (this.modelValue || ''), onUpdate: ({ editor }) => this.$emit('update:modelValue', editor.getHTML()) });
          this.useTT = true;
        } catch (e) { console.warn('[wysiwyg] Tiptap load failed; using basic editor', e); this.useTT = false; }
      },
      // Switch between visual and HTML editing. Going back to visual re-seeds the
      // editor from the value the textarea produced, which the watcher skipped.
      toggleSource() {
        const backToVisual = this.sourceView;
        this.sourceView = !this.sourceView;
        if (backToVisual) this.$nextTick(() => this.syncFromValue());
      },
      syncFromValue() {
        const v = this.modelValue || '';
        if (this.useTT && this.tiptap) {
          if (this.tiptap.getHTML() === v) return;
          try {
            this.tiptap.commands.setContent(v, false);
          } catch (e) {
            // ProseMirror rejects a transaction built against a state it no
            // longer holds ("Applying a mismatched transaction"), which is what
            // silently swallowed edits made in HTML view. Rebuilding the editor
            // around the new content is the reliable way back.
            this.rebuildTiptap(v);
          }
          return;
        }
        if (this.$refs.body && this.$refs.body.innerHTML !== v) this.$refs.body.innerHTML = v;
      },
      rebuildTiptap(content) {
        if (this.tiptap) { try { this.tiptap.destroy(); } catch (e) {} this.tiptap = null; }
        this.useTT = false;
        if (this.$refs.body) this.$refs.body.innerHTML = content;
        this.initTiptap(content);
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
          <button type="button" class="rte-b rte-source" :class="{'is-on':sourceView}" title="View HTML source" @mousedown.prevent @click="toggleSource" style="margin-left:auto"><Icon name="code" :size="15"/> HTML</button>
        </div>
        <!-- v-show, never v-if: v-if unmounts the node Tiptap is attached to, so one
             trip through HTML view left the editor permanently blank. -->
        <div v-show="!sourceView">
          <div v-show="!useTT" ref="body" class="rte-body" contenteditable="true" @input="onInput" @blur="emit"></div>
          <div v-show="useTT" ref="tt" class="rte-body tt-host"></div>
        </div>
        <textarea v-show="sourceView" class="ta mono" style="min-height:160px;border-radius:0 0 var(--r-sm) var(--r-sm)" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" placeholder="&lt;p&gt;HTML…&lt;/p&gt;"></textarea>
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
      imgBg() { const m = this.media; const u = (m && (m.preview || m.url || m.thumb)) || (this.modelValue && this.modelValue.url) || ''; return u; },
      cropImg() { const v = this.modelValue || {}; const m = this.media; return Object.assign({}, v, { url: v.url || (m && m.url), crop: (m && m.crop) || v.crop || null, w: v.w || (m && m.w), h: v.h || (m && m.h) }); },
      // Chosen image format (crop preset) name, shown as a preview hint on the post.
      formatName() { const m = this.media; return (m && m.crop && m.crop.preset) || ''; },
    },
    methods: {
      grad(h) { return 'linear-gradient(135deg, hsl(' + h + ' 30% 80%), hsl(' + (h - 8) + ' 24% 58%))'; },
      onCrop(crop) { const v = this.modelValue; if (!v || v.id == null) return; window.__mdlr.store.saveMediaCrop(v.id, crop).catch(e => console.error('[image] save crop failed', e)); },
      isMobile() { const v = this.modelValue; return !!(v && typeof v === 'object' && v.mobile); },
      setMobile(on) {
        const v = this.modelValue;
        if (!v) return;
        if (typeof v === 'object') this.set(Object.assign({}, v, { mobile: !!on }));
        else this.set({ id: v, mobile: !!on });
      },
    },
    template: `
      <div class="fc-img">
        <div v-if="modelValue && (modelValue.name || modelValue.id)" class="img-slot" :class="{'is-mobile': isMobile()}" :style="imgBg ? {background:'center / contain no-repeat url('+imgBg+')', backgroundColor:'var(--paper-2)'} : {backgroundImage: grad(modelValue.hue||34)}">
          <div class="img-slot-actions"><button class="btn btn--soft btn--xs" :class="{'is-on': isMobile()}" @click="setMobile(!isMobile())" title="Show in the mobile row on the public page"><Icon name="box" :size="13"/> Mobile</button><button class="btn btn--soft btn--xs" @click="cropOpen=true"><Icon name="crop" :size="13"/> Crop</button><button class="btn btn--soft btn--xs" @click="pickOpen=true"><Icon name="refresh" :size="13"/> Replace</button><button class="btn btn--soft btn--xs" @click="set(null)"><Icon name="x" :size="13"/> Remove</button></div>
          <span class="img-slot-meta mono">{{ modelValue.name }}<template v-if="modelValue.w"> · {{ modelValue.w }}×{{ modelValue.h }}</template><template v-if="formatName"> · {{ formatName }}</template><template v-if="isMobile()"> · mobile</template></span>
        </div>
        <button v-else class="form-file" @click="pickOpen=true"><Icon name="image" :size="15"/> Choose image…</button>
        <ImageCropper v-if="cropOpen" :img="cropImg" @save="onCrop" @close="cropOpen=false"/>
        <MediaPicker v-if="pickOpen" :accept="'image'" @close="pickOpen=false" @pick="m=>{ set({id:m.id,hue:m.hue,name:m.name,w:m.w,h:m.h,url:m.url,mobile:isMobile()}); pickOpen=false }"/>
      </div>`,
  });

  C.MdGallery = base({
    name: 'MdGallery',
    data() { return { pickOpen: false, dragI: null, dropAt: null, extra: {} }; },
    computed: {
      rawIds() {
        let raw = this.modelValue;
        if (raw && !Array.isArray(raw) && Array.isArray(raw.ids)) raw = raw.ids;
        if (!Array.isArray(raw)) return [];
        return raw.map(it => Number((it && typeof it === 'object') ? it.id : it)).filter(id => id > 0);
      },
      items() {
        let raw = this.modelValue;
        if (raw && !Array.isArray(raw) && Array.isArray(raw.ids)) raw = raw.ids;
        if (!Array.isArray(raw)) return [];
        return raw.map(it => {
          const id = Number((it && typeof it === 'object') ? it.id : it);
          if (!id) return null;
          const mobile = !!(it && typeof it === 'object' && it.mobile);
          const m = this.lookup(id);
          if (m) {
            return { id: m.id, hue: m.hue, name: m.name || (it && it.name) || '', w: m.w, h: m.h, url: m.url || (it && it.url) || '', thumb: m.thumb || m.preview || (it && it.thumb) || '', mobile };
          }
          const row = (it && typeof it === 'object') ? Object.assign({}, it) : { id };
          row.mobile = mobile;
          return row;
        }).filter(it => it && it.id != null);
      },
    },
    watch: { modelValue: { immediate: true, handler() { this.resolveMissing(); } } },
    methods: {
      lookup(id) {
        const nid = Number(id);
        if (this.extra[nid]) return this.extra[nid];
        const MEDIA = (window.__mdlr.data && window.__mdlr.data.MEDIA) || [];
        return MEDIA.find(x => Number(x.id) === nid) || null;
      },
      mapRow(r) {
        const v = (r.variants && typeof r.variants === 'object') ? r.variants : {};
        const thumb = r.thumb_url || (v.thumb && v.thumb.path) || (v.small && v.small.path) || r.url || r.file_path || '';
        return { id: Number(r.id), name: r.filename || r.name, hue: 30, w: Number(r.width) || null, h: Number(r.height) || null, url: r.url || r.file_path || '', thumb, preview: r.preview_url || r.url || thumb };
      },
      resolveMissing() {
        const M = window.__mdlr;
        if (!M.api) return;
        const missing = this.rawIds.filter(id => !this.lookup(id));
        if (!missing.length) return;
        M.api.get('/media?ids=' + missing.join(',')).then(rows => {
          const extra = Object.assign({}, this.extra);
          (Array.isArray(rows) ? rows : []).forEach(r => {
            if (!r || r.id == null) return;
            extra[Number(r.id)] = this.mapRow(r);
          });
          this.extra = extra;
        }).catch(() => {});
      },
      grad(h) { return 'linear-gradient(135deg, hsl(' + h + ' 26% 82%), hsl(' + (h - 6) + ' 22% 64%))'; },
      bg(it) {
        const m = this.lookup(it && it.id);
        const u = (m && (m.thumb || m.preview || m.url)) || (it && (it.thumb || it.url)) || '';
        return u ? ('center/cover no-repeat url(' + u + ')') : this.grad((it && it.hue) || 30);
      },
      addBatch(payload) { const arr = Array.isArray(payload) ? payload : [payload]; const add = arr.map(m => ({ id: m.id, hue: m.hue, name: m.name, w: m.w, h: m.h, url: m.url, mobile: !!m.mobile })); this.set(this.items.concat(add)); },
      toggleMobile(i) {
        const a = this.items.slice();
        if (!a[i]) return;
        const cur = a[i];
        const obj = (cur && typeof cur === 'object') ? Object.assign({}, cur) : { id: cur };
        obj.mobile = !obj.mobile;
        a[i] = obj;
        this.set(a);
      },
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
            <div class="gal-cell" :class="{'is-dragging':dragI===i, 'is-mobile': !!it.mobile}" :style="{ background: bg(it) }" draggable="true" @dragstart="dStart(i,$event)" @dragend="dragI=null;dropAt=null" @dragover="dOver(i,$event)" :title="it.name">
              <button class="iconbtn iconbtn--sm is-danger gal-rm" @click.stop="remove(i)"><Icon name="x" :size="12"/></button>
              <button class="gal-flag" :class="{'is-on': !!it.mobile}" @click.stop="toggleMobile(i)" title="Show in the mobile row on the public page">Mobile</button>
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
        const arr = Array.isArray(v) ? v : (v && Array.isArray(v.ids) ? v.ids : (v ? [v] : []));
        if (!arr.length) return [];
        const byId = {};
        this.available.forEach(p => { byId[p.id] = p; });
        return arr.map(s => {
          if (s && typeof s === 'object') return s;
          return byId[s] || this.resolvedById[s] || { id: s, _raw: true };
        });
      },
      selectedIds() { return this.selected.map(s => (s && typeof s === 'object') ? s.id : s); },
      max() { return Number(this.definition.max) || 0; },
      atMax() { return !this.single && this.max > 0 && this.selectedIds.length >= this.max; },
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
      ptCount() { return (M.data.POST_TYPES || []).length; },
    },
    mounted() { this.load(); },
    watch: {
      modelValue() { this.resolveMissing(); },
      search() { this.renderN = 30; },
      ptFilter() { this.renderN = 30; },
      ptCount() { this.load(); },
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
        const arr = Array.isArray(v) ? v : (v && Array.isArray(v.ids) ? v.ids : (v ? [v] : []));
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
        else {
          if (this.atMax || this.selectedIds.includes(p.id)) return;
          this.set(this.selectedIds.concat([p.id]));
        }
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
            <button type="button" v-for="p in visible" :key="p.id" class="acf-rel-item" @click="add(p)" :disabled="(single && selected.length) || atMax">
              <div class="rel-item-main"><strong>{{ p.title }}</strong><span class="t-meta">{{ p.typeName }} · {{ p.status }}</span></div>
              <Icon name="plus" :size="15" class="muted"/>
            </button>
            <div v-if="!loading && visible.length < filtered.length" class="t-meta" style="padding:8px;text-align:center">Showing {{ visible.length }} of {{ filtered.length }} — scroll for more</div>
            <div v-if="!loading && !filtered.length" class="t-meta" style="padding:10px;text-align:center">No matches.</div>
          </div>
        </div>
        <div class="acf-rel-pane">
          <div class="acf-rel-head"><span class="t-meta">{{ single ? 'Selected' : ('Selected (' + selectedIds.length + (max ? '/' + max : '') + ')') }}</span></div>
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
;
/* file: field-settings.js */
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
;
/* file: field-renderer.js */
/* ============================================================
   FieldRenderer (§3) — ONE recursive renderer that walks a schema
   tree, resolves each leaf to its registered component, recurses
   through container nodes (tab/columns/accordion/group/repeater/
   blocks), and centrally handles provenance badges + conditional
   visibility. Used by the content editor, forms and the field demo.

   Props:
     schema  : array of field nodes
     model   : reactive object of values keyed by field.name
     blocks  : map of componentId -> { name, icon, fields } (for `blocks`)
   ============================================================ */
(function () {
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const C = M.comps;
  const F = () => M.fields;

  /* provenance helpers (match the prototype's .prov badges) */
  const provLabel = (p) => p === 'post' ? 'Added here' : (p === 'comp' || p === 'shared') ? 'Shared group' : 'From post type';
  const provIcon = (p) => p === 'post' ? 'plus' : (p === 'comp' || p === 'shared') ? 'git' : 'lock';
  const provClass = (p) => p === 'post' ? 'prov--post' : (p === 'comp' || p === 'shared') ? 'prov--comp' : 'prov--type';

  /* evaluate a node's conditional visibility against the model */
  function visible(node, model) {
    if (!node) return false;
    if (node._conditional) { return !!model[node._conditional]; }
    const r = node.rules;
    if (r && Array.isArray(r.conds) && r.conds.length) {
      const test = (c) => {
        const v = model[c.field];
        switch (c.op) {
          case 'is': case '==': return String(v) === String(c.value);
          case 'isnot': case '!=': return String(v) !== String(c.value);
          case 'set': case 'is set': return v != null && v !== '' && !(Array.isArray(v) && !v.length);
          case 'empty': return v == null || v === '' || (Array.isArray(v) && !v.length);
          default: return true;
        }
      };
      return r.logic === 'any' ? r.conds.some(test) : r.conds.every(test);
    }
    return true;
  }

  const widthClass = (w) => w === 'half' ? 'fr-col-6' : w === 'third' ? 'fr-col-4' : 'fr-col-12';

  C.FieldRenderer = {
    name: 'FieldRenderer',
    props: {
      schema: { type: Array, default: () => [] },
      model: { type: Object, default: () => ({}) },
      blocks: { type: Object, default: () => ({}) },
      nested: Boolean,
      // when true, leaf/child fields don't render a provenance badge — the
      // parent container (group/repeater/blocks) already shows it once.
      hideProv: Boolean,
    },
    computed: {
      // split top-level schema into tab sections when `tab` nodes are present
      tabs() {
        const hasTabs = this.schema.some(n => F().canonical(n.type) === 'tab');
        if (!hasTabs || this.nested) return null;
        const sections = []; let cur = { label: 'General', name: '_default', items: [] };
        this.schema.forEach(n => {
          if (F().canonical(n.type) === 'tab') { if (cur.items.length || sections.length) sections.push(cur); cur = { label: n.label, name: n.name || n.label, items: [] }; }
          else cur.items.push(n);
        });
        sections.push(cur);
        return sections.filter(s => s.items.length);
      },
      flat() { return this.schema.filter(n => F().canonical(n.type) !== 'tab'); },
    },
    data() { return { activeTab: 0 }; },
    methods: {
      visible(n) { return visible(n, this.model); },
      provLabel, provIcon, provClass,
      isContainer(n) { return F().isContainer(n.type); },
      ensure(n) {
        // make sure the model has a sensible default for this field
        if (n.name && this.model[n.name] === undefined && F().get(n.type).stored !== false) {
          this.model[n.name] = F().defaultValue(n);
        }
        return this.model[n.name];
      },
      compFor(n) { return F().component(n.type); },
      // repeater helpers
      repItems(n) { if (!Array.isArray(this.model[n.name])) this.model[n.name] = []; return this.model[n.name]; },
      repAdd(n) { const row = {}; (n.sub_fields || []).forEach(sf => { row[sf.name] = F().defaultValue(sf); }); this.repItems(n).push(row); },
      repDel(n, i) { this.repItems(n).splice(i, 1); },
      repReorder(n, arr) { this.model[n.name] = arr; },
      // blocks helpers
      blkItems(n) { if (!Array.isArray(this.model[n.name])) this.model[n.name] = []; return this.model[n.name]; },
      blkDef(b) { return this.blocks[b.comp] || (window.__mdlr.data && (window.__mdlr.data.COMPONENTS || []).find(c => c.id === b.comp)) || { name: b.comp, fields: [] }; },
      groupModel(n) { if (typeof this.model[n.name] !== 'object' || this.model[n.name] == null || Array.isArray(this.model[n.name])) this.model[n.name] = {}; return this.model[n.name]; },
    },
    template: `
    <div class="fr">
      <!-- tabbed top level -->
      <template v-if="tabs">
        <div class="tabs" style="margin-bottom:18px">
          <button v-for="(t,ti) in tabs" :key="t.name" :class="{'is-on':activeTab===ti}" @click="activeTab=ti">{{ t.label }}</button>
        </div>
        <FieldRenderer :schema="tabs[activeTab].items" :model="model" :blocks="blocks" nested/>
      </template>

      <!-- flat / nested list -->
      <div v-else class="fr-grid">
        <template v-for="n in flat" :key="n.id || n.name">
          <div v-if="visible(n)" class="fr-cell" :class="widthClass(n.w)">

            <!-- divider / message (layout-only) -->
            <hr v-if="n.type==='divider'" class="fr-divider">
            <div v-else-if="n.type==='message'" class="fr-message">{{ n.instructions || n.label }}</div>

            <!-- GROUP -->
            <section v-else-if="n.type==='group'" class="fr-group">
              <div class="fr-field-head"><span class="ed-field-label">{{ n.label }}</span><span class="prov" :class="provClass(n.prov)" v-if="n.prov && !hideProv"><Icon :name="provIcon(n.prov)" :size="11"/> {{ provLabel(n.prov) }}</span></div>
              <div class="fr-group-body"><FieldRenderer :schema="n.fields||n.sub_fields||[]" :model="groupModel(n)" :blocks="blocks" nested hideProv/></div>
            </section>

            <!-- COLUMNS -->
            <section v-else-if="n.type==='columns'" class="fr-columns">
              <div v-for="(col,ci) in (n.cols||[])" :key="ci" class="fr-column"><FieldRenderer :schema="col" :model="model" :blocks="blocks" nested :hideProv="hideProv"/></div>
            </section>

            <!-- REPEATER -->
            <section v-else-if="n.type==='repeater'" class="fr-field">
              <div class="fr-field-head"><span class="ed-field-label">{{ n.label }}<span class="field-req" v-if="n.required">*</span></span><span class="prov" :class="provClass(n.prov)" v-if="n.prov && !hideProv"><Icon :name="provIcon(n.prov)" :size="11"/> {{ provLabel(n.prov) }}</span></div>
              <p class="field-hint" v-if="n.instructions" style="margin-top:0">{{ n.instructions }}</p>
              <SortableList :modelValue="repItems(n)" @update:modelValue="a=>repReorder(n,a)" itemClass="rep-row-wrap">
                <template #default="{ item, index, startDrag }">
                  <div class="rep-item">
                    <div class="rep-item-bar"><span class="drag-handle" draggable="true" @dragstart="startDrag" title="Drag to reorder"><Icon name="drag" :size="14"/></span><span class="t-meta">{{ (n.itemLabel||'Item') }} {{ index+1 }}</span><button class="iconbtn iconbtn--sm is-danger" @click="repDel(n,index)"><Icon name="x" :size="13"/></button></div>
                    <FieldRenderer :schema="n.sub_fields||[]" :model="item" :blocks="blocks" nested hideProv/>
                  </div>
                </template>
              </SortableList>
              <button class="btn btn--ghost btn--sm" @click="repAdd(n)"><Icon name="plus" :size="14"/> Add {{ (n.itemLabel||'row').toLowerCase() }}</button>
            </section>

            <!-- BLOCKS (basic; visual editor in Fase 5) -->
            <section v-else-if="n.type==='blocks'" class="ed-blocks-field">
              <div class="fr-field-head"><span class="ed-field-label">{{ n.label }}</span><span class="prov prov--post" v-if="n.prov==='post'"><Icon name="plus" :size="11"/> Added here</span></div>
              <p class="field-hint" v-if="n.instructions" style="margin-top:0">{{ n.instructions }}</p>
              <div v-for="(b,bi) in blkItems(n)" :key="b.id||bi" class="block">
                <div class="block-head"><span class="block-ico"><Icon :name="blkDef(b).icon||'layers'" :size="15"/></span><div class="block-head-main"><strong>{{ blkDef(b).name }}</strong></div><button class="iconbtn iconbtn--sm is-danger" @click="blkItems(n).splice(bi,1)"><Icon name="x" :size="13"/></button></div>
                <div class="block-body"><FieldRenderer :schema="blkDef(b).fields||[]" :model="b.data || (b.data={})" :blocks="blocks" nested hideProv/></div>
              </div>
              <div class="t-meta" v-if="!blkItems(n).length" style="padding:10px 0">No blocks yet.</div>
            </section>

            <!-- LEAF FIELD -->
            <div v-else class="fr-field">
              <div class="fr-field-head">
                <span class="ed-field-label">{{ n.label }}<span class="field-req" v-if="n.required">*</span></span>
                <span class="prov" :class="provClass(n.prov)" v-if="n.prov && !hideProv"><Icon :name="provIcon(n.prov)" :size="11"/> {{ provLabel(n.prov) }}</span>
              </div>
              <component :is="compFor(n)" :definition="n" :modelValue="ensure(n)" @update:modelValue="v=>{ model[n.name]=v }"/>
              <p class="field-hint" v-if="n.instructions">{{ n.instructions }}</p>
            </div>

          </div>
        </template>
      </div>
    </div>`,
    setup() { return { widthClass }; },
  };
})();
;
/* file: store.js */
/* ============================================================
   STORE — bridge between SPA screens and the JSON API.
   All CMS entity lists start empty in data.js and are filled here.
   ============================================================ */
(function () {
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const D = () => M.data;
  const api = () => M.api;

  const boundToFromLocation = (location) => {
    const out = new Set();
    (location || []).forEach(group => (group || []).forEach(r => { if (r.param === 'post_type' && r.value) out.add(r.value); }));
    return Array.from(out);
  };

  const fgFromApi = (r) => ({
    id: r.id, name: r.name, slug: r.slug, icon: r.icon || 'box', desc: r.description || '',
    active: !!Number(r.active), order: Number(r.order) || 0, position: r.position || 'normal',
    style: r.style || 'default', labelPlacement: r.label_placement || 'top',
    boundTo: r.bound_to || [], location: r.location || [], fields: r.fields || [],
    shared: Array.isArray(r.bound_to) && r.bound_to.length > 1,
  });
  const fgToApi = (g) => ({
    name: g.name, slug: g.slug || undefined, icon: g.icon || 'box', description: g.desc || '',
    location: g.location || [], position: g.position || 'normal', style: g.style || 'default',
    label_placement: g.labelPlacement || 'top', bound_to: g.boundTo && g.boundTo.length ? g.boundTo : boundToFromLocation(g.location),
    fields: g.fields || [], active: g.active ? 1 : 0, order: g.order || 0,
  });

  const cmpFromApi = (r) => ({
    id: r.id, name: r.name, slug: r.slug, icon: r.icon || 'layers', version: Number(r.current_version) || 1,
    usedIn: Number(r.used_in) || 0, updated: '', desc: r.description || '', fields: r.schema || [],
  });
  const cmpToApi = (c) => ({ name: c.name, slug: c.slug || undefined, icon: c.icon || 'layers', description: c.desc || '', schema: c.fields || [] });

  const toBool = (v, defaultVal = true) => {
    if (v === undefined || v === null) return defaultVal;
    if (typeof v === 'boolean') return v;
    return Number(v) !== 0;
  };

  const ptFromApi = (r) => ({
    id: r.slug, _dbId: r.id, name: r.name, slug: r.slug, icon: r.icon || 'file', desc: r.description || '',
    mode: r.mode || 'fixed', hierarchical: toBool(r.hierarchical, false), public: toBool(r.public, true),
    inMenu: toBool(r.show_in_menu, true), count: Number(r.post_count) || 0, fieldCount: Array.isArray(r.schema && r.schema.fields) ? r.schema.fields.length : (Array.isArray(r.schema) ? r.schema.length : 0),
    taxonomies: [], _schema: r.schema || null,
  });
  const ptToApi = (pt) => {
    const out = {
      name: pt.name, slug: pt.slug || undefined, description: pt.desc || '', icon: pt.icon || 'file',
      mode: pt.mode || 'fixed', hierarchical: pt.hierarchical ? 1 : 0,
      public: pt.public === false ? 0 : 1, show_in_menu: pt.inMenu === false ? 0 : 1,
    };
    if (pt.schema != null) out.schema = pt.schema;
    return out;
  };

  const postFromApiLight = (r) => ({
    id: r.id,
    title: r.title || '(untitled)',
    slug: r.slug || '',
    type: r.post_type_slug || '',
    status: r.status || 'draft',
    author: '',
    date: (r.updated_at || r.created_at || '').slice(0, 10),
  });

  const replaceInPlace = (arr, items) => { if (arr) arr.splice(0, arr.length, ...(items || [])); };

  const store = {
    ready: false,

    async loadFieldGroups() {
      try {
        const rows = await api().get('/field-groups');
        const mapped = (rows || []).map(fgFromApi);
        replaceInPlace(D().FIELD_GROUPS, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadFieldGroups failed', e && e.status); return null; }
    },
    async saveFieldGroup(g) {
      const payload = fgToApi(g);
      const isNew = !g.id || g.id === 'new' || String(g.id).startsWith('type_') || String(g.id).startsWith('fg_') || isNaN(Number(g.id));
      const saved = isNew ? await api().post('/field-groups', payload) : await api().put('/field-groups/' + g.id, payload);
      await this.loadFieldGroups();
      return saved ? fgFromApi(saved) : null;
    },
    async deleteFieldGroup(id) {
      if (!isNaN(Number(id))) await api().del('/field-groups/' + id);
      await this.loadFieldGroups();
    },
    async reorderFieldGroups(groups) {
      await Promise.all((groups || []).map((g, i) => (!isNaN(Number(g.id))) ? api().put('/field-groups/' + g.id, { order: i + 1 }) : Promise.resolve()));
    },

    async loadComponents() {
      try {
        const rows = await api().get('/components');
        const mapped = (rows || []).map(cmpFromApi);
        replaceInPlace(D().COMPONENTS, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadComponents failed', e && e.status); return null; }
    },
    async saveComponent(c) {
      const payload = cmpToApi(c);
      const isNew = !c.id || String(c.id).startsWith('cmp_') || isNaN(Number(c.id));
      const saved = isNew ? await api().post('/components', payload) : await api().put('/components/' + c.id, payload);
      await this.loadComponents();
      return saved ? cmpFromApi(saved) : null;
    },
    async deleteComponent(id) {
      if (!isNaN(Number(id))) await api().del('/components/' + id);
      await this.loadComponents();
    },

    async loadMedia() {
      try {
        const rows = await api().get('/media');
        const mapped = (rows || []).map(r => {
          const v = (r.variants && typeof r.variants === 'object') ? r.variants : {};
          const thumb = r.thumb_url || (v.thumb && v.thumb.path) || (v.small && v.small.path) || r.url || r.file_path || '';
          const preview = r.preview_url || (v.large && v.large.path) || (v.medium && v.medium.path) || thumb;
          const meta = (r.metadata && typeof r.metadata === 'object') ? r.metadata : {};
          return {
            id: r.id, name: r.filename, title: r.title || r.filename || '',
            w: Number(r.width) || null, h: Number(r.height) || null,
            kb: Math.round((Number(r.file_size) || 0) / 1024),
            alt: r.alt_text || '', caption: r.caption || '', folder: r.folder_path || '',
            hue: 30, uploaded: (r.created_at || '').slice(0, 10), usedIn: 0,
            seoTitle: '', seoDesc: '', url: r.url || r.file_path || '', thumb, preview, crop: meta.crop || null, mime: r.mime_type || '',
          };
        });
        replaceInPlace(D().MEDIA, mapped);
        const folders = [...new Set(mapped.map(m => m.folder).filter(Boolean))].sort();
        replaceInPlace(D().MEDIA_FOLDERS, folders);
        return mapped;
      } catch (e) { console.warn('[store] loadMedia failed', e && e.status); return null; }
    },
    async saveMediaCrop(id, crop) {
      if (id == null) throw new Error('saveMediaCrop: missing media id');
      const saved = await api().put('/media/' + id, { crop });
      await this.loadMedia();
      return saved || null;
    },
    async deleteMedia(id) {
      const nid = Number(id);
      if (isNaN(nid) || nid <= 0) throw new Error('Invalid media id');
      await api().post('/media/' + nid + '/delete', {});
      const list = D().MEDIA;
      const i = list.findIndex(m => Number(m.id) === nid);
      if (i >= 0) list.splice(i, 1);
    },

    async loadTaxonomies() {
      try {
        const rows = await api().get('/taxonomies');
        const mapped = await Promise.all((rows || []).map(async t => {
          let terms = [];
          try {
            const tr = await api().get('/taxonomies/' + t.id + '/terms');
            terms = (tr || []).map((x, i) => ({ id: x.id, name: x.name, slug: x.slug, count: 0, parent: x.parent_id, order: i }));
          } catch (e) {}
          return { id: t.slug || t.id, _dbId: t.id, name: t.name, slug: t.slug, hierarchical: !!Number(t.hierarchical), postTypes: [], terms };
        }));
        replaceInPlace(D().TAXONOMIES, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadTaxonomies failed', e && e.status); return null; }
    },
    async loadModules() {
      try {
        const rows = await api().get('/modules');
        const mapped = (rows || []).map(m => ({ id: m.id, name: m.name, slug: m.slug, author: m.author || '', version: m.version || '', icon: m.icon || 'box', active: !!Number(m.active), autoUpdate: false, desc: m.description || '' }));
        replaceInPlace(D().MODULES, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadModules failed', e && e.status); return null; }
    },
    toggleModule(m) { if (m && m.id != null && !isNaN(Number(m.id))) api().put('/modules/' + m.id, { active: m.active ? 1 : 0 }).catch(e => console.warn('[store] toggleModule failed', e && e.status)); },
    async loadForms() {
      try {
        const rows = await api().get('/forms');
        const mapped = (rows || []).map(f => ({ id: f.id, name: f.name, slug: f.slug, fields: Array.isArray(f.fields) ? f.fields.length : 0, submissions: Number(f.submission_count) || 0, updated: (f.updated_at || '').slice(0, 10), system: false, preview: '', desc: f.description || '' }));
        replaceInPlace(D().FORMS, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadForms failed', e && e.status); return null; }
    },
    async loadActions() {
      try {
        const rows = await api().get('/actions');
        const mapped = (rows || []).map(a => ({ id: a.id, name: a.name, trigger: a.trigger_type || '', enabled: !!Number(a.enabled), runs: Number(a.run_count) || 0, ops: (a.action_config && Array.isArray(a.action_config.ops)) ? a.action_config.ops : [{ type: a.action_type || 'action', icon: 'zap', label: a.action_type || 'Action' }] }));
        replaceInPlace(D().ACTIONS, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadActions failed', e && e.status); return null; }
    },
    async loadRoles() {
      try {
        const rows = await api().get('/roles');
        const builtin = ['admin', 'editor', 'author', 'user'];
        const mapped = (rows || []).map(r => {
          const isAdmin = (r.name || '').toLowerCase() === 'admin';
          return {
            id: r.name, _dbId: r.id, name: r.display_name || r.name, desc: r.description || '',
            users: Number(r.user_count) || 0, builtin: builtin.includes((r.name || '').toLowerCase()),
            caps: isAdmin ? ['*'] : (Array.isArray(r.capabilities) ? r.capabilities : []),
          };
        });
        replaceInPlace(D().ROLES, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadRoles failed', e && e.status); return null; }
    },
    async saveRoleCaps(role) {
      if (!role || role._dbId == null || (role.caps || []).includes('*')) return;
      try { await api().put('/roles/' + role._dbId + '/capabilities', { capabilities: role.caps || [] }); }
      catch (e) { console.warn('[store] saveRoleCaps failed', e && e.status); }
    },
    async createRole({ name, desc }) {
      const saved = await api().post('/roles', { display_name: name, description: desc || '' });
      await this.loadRoles();
      return saved;
    },
    async deleteRole(role) {
      if (!role || role._dbId == null) return;
      await api().del('/roles/' + role._dbId);
      await this.loadRoles();
    },
    async assignUserRole(userId, roleName) {
      try { await api().put('/users/' + userId, { role: roleName }); await this.loadRoles(); }
      catch (e) { console.warn('[store] assignUserRole failed', e && e.status); }
    },

    async loadPostTypes() {
      try {
        const rows = await api().get('/post-types');
        const list = Array.isArray(rows) ? rows : [];
        const mapped = list.map(ptFromApi);
        replaceInPlace(D().POST_TYPES, mapped);
        if (D().SPA_META) D().SPA_META.postTypesVersion = (D().SPA_META.postTypesVersion || 0) + 1;
        return mapped;
      } catch (e) { console.warn('[store] loadPostTypes failed', e && e.status); return null; }
    },
    async savePostType(pt) {
      const payload = ptToApi(pt);
      const saved = (pt._dbId == null) ? await api().post('/post-types', payload) : await api().put('/post-types/' + pt._dbId, payload);
      await this.loadPostTypes();
      return saved ? ptFromApi(saved) : null;
    },
    async deletePostType(pt) {
      if (!pt || pt._dbId == null) return;
      await api().del('/post-types/' + pt._dbId);
      await this.loadPostTypes();
    },
    async savePostTypeSchema(dbId, schema) {
      if (dbId == null) throw new Error('savePostTypeSchema: missing post type id');
      const saved = await api().put('/post-types/' + dbId, { schema });
      await this.loadPostTypes();
      return saved ? ptFromApi(saved) : null;
    },

    async loadPosts() {
      try {
        const rows = await api().get('/posts?fields=light&limit=500');
        const mapped = (rows || []).map(postFromApiLight);
        replaceInPlace(D().POSTS, mapped);
        return mapped;
      } catch (e) { console.warn('[store] loadPosts failed', e && e.status); return null; }
    },
    async deletePost(id) {
      if (id == null || isNaN(Number(id))) throw new Error('deletePost: invalid id');
      await api().del('/posts/' + id);
      await this.loadPosts();
    },
    async restorePost(id) {
      if (id == null || isNaN(Number(id))) throw new Error('restorePost: invalid id');
      const saved = await api().post('/posts/' + id + '/restore', {});
      await this.loadPosts();
      return saved;
    },
    async permanentlyDeletePost(id) {
      if (id == null || isNaN(Number(id))) throw new Error('permanentlyDeletePost: invalid id');
      await api().del('/posts/' + id + '?permanent=1');
      await this.loadPosts();
    },
    async updatePost(id, payload) {
      if (id == null || isNaN(Number(id))) throw new Error('updatePost: invalid id');
      const saved = await api().put('/posts/' + id, payload);
      await this.loadPosts();
      return saved;
    },

    async loadTheme() {
      try {
        const r = await api().get('/settings/theme');
        const t = r && r.tokens;
        if (t && M.theme && typeof t === 'object' && Object.keys(t).length) { Object.assign(M.theme.state, t); M.theme.apply(); }
      } catch (e) { console.warn('[store] loadTheme failed', e && e.status); }
    },
    saveTheme() {
      if (!M.theme) return;
      if (this._themeTimer) clearTimeout(this._themeTimer);
      this._themeTimer = setTimeout(() => { api().put('/settings/theme', { tokens: M.theme.state }).catch(e => console.warn('[store] saveTheme failed', e && e.status)); }, 600);
    },

    async loadProfile() {
      try {
        const me = await api().get('/me');
        if (!me || me.error) return null;
        const full = [me.first_name, me.last_name].filter(Boolean).join(' ').trim();
        const src = (full || me.username || me.email || '').trim();
        Object.assign(D().PROFILE, {
          username: me.username || '',
          name: full || me.username || '',
          email: me.email || '',
          role: me.role ? me.role.charAt(0).toUpperCase() + me.role.slice(1) : '',
          status: me.status ? me.status.charAt(0).toUpperCase() + me.status.slice(1) : '',
          memberSince: me.created_at ? String(me.created_at).slice(0, 10) : '',
          lastActive: me.last_login ? String(me.last_login).slice(0, 10) : '',
          bio: me.bio || '',
          initials: src ? src.split(/\s+/).map(s => s[0]).join('').slice(0, 2).toUpperCase() : '',
        });
        return D().PROFILE;
      } catch (e) { console.warn('[store] loadProfile failed', e && e.status); return null; }
    },

    async loadSystem() {
      try {
        const data = await api().get('/system/update');
        if (!data) return null;
        const cur = data.current || {};
        const lat = data.latest || {};
        Object.assign(D().SYSTEM, {
          version: cur.version || '',
          latest: (lat && lat.version) || cur.version || '',
          channel: data.mode || 'stable',
          php: cur.php || '',
          db: cur.db || '',
          lastChecked: new Date().toLocaleString(),
          update_available: !!data.update_available,
          changelog: lat && lat.version ? [{ v: lat.version, date: lat.published_at || '', notes: lat.body || lat.notes || '' }] : [],
          raw: data,
        });
        return D().SYSTEM;
      } catch (e) { console.warn('[store] loadSystem failed', e && e.status); return null; }
    },

    async loadStatsOverview() {
      try {
        const data = await api().get('/stats/overview');
        D().STATS_OVERVIEW = data;
        return data;
      } catch (e) { console.warn('[store] loadStatsOverview failed', e && e.status); return null; }
    },
    async loadAnalytics(range) {
      try {
        const data = await api().get('/stats/analytics' + (range ? '?range=' + range : ''));
        D().LIVE_ANALYTICS = data;
        return data;
      } catch (e) { console.warn('[store] loadAnalytics failed', e && e.status); return null; }
    },
    async loadReports(type, range) {
      try {
        const qs = '?type=' + (type || 'production') + '&range=' + (range || 30);
        const data = await api().get('/stats/reports' + qs);
        D().LIVE_REPORTS = data;
        return data;
      } catch (e) { console.warn('[store] loadReports failed', e && e.status); return null; }
    },

    async hydrate() {
      if (this._hydrateP) return this._hydrateP;
      this._hydrateP = this._hydrate().catch((e) => { this._hydrateP = null; throw e; });
      return this._hydrateP;
    },
    async _hydrate() {
      await Promise.all([
        this.loadPostTypes(),
        this.loadFieldGroups(),
        this.loadComponents(),
        this.loadMedia(),
        this.loadTaxonomies(),
        this.loadModules(),
        this.loadForms(),
        this.loadActions(),
        this.loadRoles(),
        this.loadPosts(),
        this.loadStatsOverview(),
        this.loadSystem(),
        this.loadProfile(),
      ]);
      if (D().SPA_META) D().SPA_META.ready = true;
      this.ready = true;
    },
  };

  M.store = store;
})();
;
/* file: screens-basic.js */
/* Dashboard + Users + Settings (lighter screens). */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenDashboard = {
    name: 'ScreenDashboard',
    inject: ['go', 'route'],
    computed: {
      tab() { return (this.route.params || {}).tab || 'overview'; },
      pts() { return D().POST_TYPES; },
      recent() { return D().POSTS.slice(0, 5); },
      updateCount() { return D().MODULES.filter(m => m.update).length + (D().SYSTEM.version !== D().SYSTEM.latest ? 1 : 0); },
      headTitle() { return { overview:'Good morning, Marten', updates:'Updates', analytics:'Analytics', reports:'Reports' }[this.tab]; },
      headSub() { return {
        overview:'Everything is quiet. 4 drafts are waiting and one release is scheduled for tomorrow.',
        updates:'Keep the CMS and its modules current.',
        analytics:'Traffic and content performance at a glance.',
        reports:'Build, export and schedule reports across your content.',
      }[this.tab]; },
      stats() {
        return [
          { label: 'Content items', value: '304', sub: '5 post types', icon: 'files' },
          { label: 'Published', value: '261', sub: '43 drafts', icon: 'check' },
          { label: 'Components', value: '10', sub: 'reused 137×', icon: 'layers' },
          { label: 'Media', value: '236', sub: '4 presets', icon: 'image' },
        ];
      },
    },
    methods: {
      typeName(t) { const p = this.pts.find(x => x.id === t); return p ? p.name : t; },
      statusClass(s) { return s === 'published' ? 'badge--ok' : s === 'scheduled' ? 'badge--info' : ''; },
    },
    template: `
    <div class="page fade-up">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px">Wednesday, 3 June</div>
          <h1 class="t-title t-h1">{{ headTitle }}</h1>
          <p class="page-sub" style="margin-top:6px">{{ headSub }}</p>
        </div>
        <div class="flex gap-2" v-if="tab==='overview'">
          <button class="btn btn--ghost" @click="go('binding')"><Icon name="settings2"/> Field groups</button>
          <button class="btn btn--accent" @click="go('editor',{postId:'new'})"><Icon name="plus"/> New content</button>
        </div>
      </div>

      <div class="tabs" style="margin-bottom:22px">
        <button :class="{'is-on':tab==='overview'}" @click="go('dashboard',{})"><Icon name="dashboard" :size="15"/> Overview</button>
        <button :class="{'is-on':tab==='updates'}" @click="go('dashboard',{tab:'updates'})"><Icon name="refresh" :size="15"/> Updates <span class="tab-count" v-if="updateCount" style="color:var(--accent)">{{ updateCount }}</span></button>
        <button :class="{'is-on':tab==='analytics'}" @click="go('dashboard',{tab:'analytics'})"><Icon name="sigma" :size="15"/> Analytics</button>
        <button :class="{'is-on':tab==='reports'}" @click="go('dashboard',{tab:'reports'})"><Icon name="table" :size="15"/> Reports</button>
      </div>

      <!-- OVERVIEW -->
      <template v-if="tab==='overview'">
        <div class="dash-stats">
          <div class="card card-pad stat" v-for="s in stats" :key="s.label">
            <div class="stat-top"><span class="stat-ico"><Icon :name="s.icon" :size="16"/></span><span class="t-meta">{{ s.label }}</span></div>
            <div class="stat-val t-display">{{ s.value }}</div>
            <div class="t-meta">{{ s.sub }}</div>
          </div>
        </div>
        <div class="dash-grid">
          <section class="panel">
            <div class="panel-head"><h2 class="t-title t-h3">Jump back in</h2><button class="btn btn--text btn--sm" @click="go('content',{type:'project'})">All content <Icon name="arrowRight" :size="14"/></button></div>
            <div class="dash-list">
              <button class="dash-row" v-for="p in recent" :key="p.id" @click="go('editor',{postId:p.id})">
                <span class="dash-row-main"><strong>{{ p.title }}</strong><span class="t-meta">{{ typeName(p.type) }} · {{ p.date }}</span></span>
                <span class="badge" :class="statusClass(p.status)">{{ p.status }}</span>
                <Icon name="chevRight" :size="16" class="muted"/>
              </button>
            </div>
          </section>
          <section class="panel">
            <div class="panel-head"><h2 class="t-title t-h3">Structure</h2><button class="btn btn--text btn--sm" @click="go('postTypes')">Post types <Icon name="arrowRight" :size="14"/></button></div>
            <div class="dash-list">
              <button class="dash-row" v-for="pt in pts" :key="pt.id" @click="go('content',{type:pt.id})">
                <span class="dash-row-ico"><Icon :name="pt.icon" :size="16"/></span>
                <span class="dash-row-main"><strong>{{ pt.name }}</strong><span class="t-meta mono">{{ pt.fieldCount }} fields · {{ pt.count }} items</span></span>
                <Icon name="chevRight" :size="16" class="muted"/>
              </button>
            </div>
          </section>
        </div>
      </template>

      <DashUpdates v-else-if="tab==='updates'"/>
      <DashAnalytics v-else-if="tab==='analytics'"/>
      <DashReports v-else/>
    </div>`,
  };

  C.ScreenUsers = {
    name: 'ScreenUsers',
    inject: ['go'],
    data() { return {
      sel: [], bulkRole: '', q: '', rows: [], loading: true, err: '',
      // Create dialog
      addOpen: false, addBusy: false, addErr: '',
      add: { first_name: '', last_name: '', email: '', role: 'user', password: '' },
      // Delete dialog: deleting is permanent, so the operator picks who inherits
      // everything the account owned before we let it through.
      delOpen: false, delBusy: false, delErr: '', delUser: null,
      delContent: null, delReassignTo: '',
    }; },
    created() { this.load(); },
    computed: {
      roles() {
        const r = (window.__mdlr.data && window.__mdlr.data.ROLES) || [];
        return r.length ? r.map(x => ({ id: x.id, label: x.name })) : [{ id:'admin', label:'Administrator' }, { id:'editor', label:'Editor' }, { id:'author', label:'Author' }, { id:'user', label:'User' }];
      },
      roleLabel() { const m = {}; this.roles.forEach(r => m[r.id] = r.label); return m; },
      filtered() {
        const q = this.q.trim().toLowerCase();
        if (!q) return this.rows;
        return this.rows.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || (this.roleLabel[u.role]||u.role).toLowerCase().includes(q));
      },
      allOn() { return this.filtered.length>0 && this.filtered.every(u => this.sel.includes(u.id)); },
      heirs() { const id = this.delUser && this.delUser.id; return this.rows.filter(r => r.id !== id); },
    },
    methods: {
      map(u){ return { id: u.id, name: u.name || u.username || u.email, email: u.email, role: (u.role || 'user').toLowerCase(), status: (u.status === 'active' ? 'Active' : (u.status || 'Active')), last: u.last_login ? String(u.last_login).slice(0, 10) : '—' }; },
      load(){
        const api = window.__mdlr.api;
        if (!api) { this.loading = false; return; }
        this.loading = true; this.err = '';
        api.get('/users')
          .then(rows => { this.rows = Array.isArray(rows) ? rows.map(this.map) : []; })
          .catch(e => { this.err = (e.data && e.data.error) || 'Could not load users.'; })
          .then(() => { this.loading = false; });
      },
      toggle(id){ const i=this.sel.indexOf(id); if(i>=0) this.sel.splice(i,1); else this.sel.push(id); },
      toggleAll(){ this.sel = this.allOn ? [] : this.filtered.map(r=>r.id); },
      onRole(u, v){ u.role = v; const s = window.__mdlr.store; if (s) s.assignUserRole(u.id, v); },
      applyRole(){ if(!this.bulkRole) return; const s = window.__mdlr.store; this.rows.forEach(r=>{ if(this.sel.includes(r.id)){ r.role=this.bulkRole; if (s) s.assignUserRole(r.id, this.bulkRole); } }); this.sel=[]; this.bulkRole=''; },
      bulkDeactivate(){
        const api = window.__mdlr.api;
        this.rows.forEach(r => { if (this.sel.includes(r.id)) { r.status = 'Inactive'; if (api) api.put('/users/' + r.id, { status: 'inactive' }).catch(()=>{}); } });
        this.sel = [];
      },
      toggleActive(u){
        const next = u.status === 'Active' ? 'Inactive' : 'Active';
        u.status = next;
        const api = window.__mdlr.api;
        if (api) api.put('/users/' + u.id, { status: next.toLowerCase() }).catch(() => { u.status = next === 'Active' ? 'Inactive' : 'Active'; });
      },

      /* ---- create ---- */
      openAdd(){ this.add = { first_name:'', last_name:'', email:'', role:'user', password:'' }; this.addErr=''; this.addOpen = true; },
      submitAdd(){
        const api = window.__mdlr.api;
        if (!api || this.addBusy) return;
        this.addBusy = true; this.addErr = '';
        api.post('/users', this.add)
          .then(u => { this.rows.unshift(this.map(u)); this.addOpen = false; })
          .catch(e => { this.addErr = (e.data && e.data.error) || 'Could not create this user.'; })
          .then(() => { this.addBusy = false; });
      },

      /* ---- delete (permanent, with hand-over) ---- */
      askDelete(u){
        this.delUser = u; this.delErr = ''; this.delContent = null;
        this.delReassignTo = String((this.rows.find(r => r.id !== u.id) || {}).id || '');
        this.delOpen = true;
        const api = window.__mdlr.api;
        if (api) api.get('/users/' + u.id + '/content').then(c => { this.delContent = c; }).catch(() => {});
      },
      confirmDelete(){
        const api = window.__mdlr.api;
        if (!api || this.delBusy || !this.delUser) return;
        if (!this.delReassignTo) { this.delErr = 'Choose who inherits this user\u2019s content.'; return; }
        this.delBusy = true; this.delErr = '';
        const id = this.delUser.id;
        api.del('/users/' + id, { reassign_to: Number(this.delReassignTo) })
          .then(() => {
            this.rows = this.rows.filter(r => r.id !== id);
            this.sel = this.sel.filter(x => x !== id);
            this.delOpen = false; this.delUser = null;
          })
          .catch(e => { this.delErr = (e.data && e.data.error) || 'Could not delete this user.'; })
          .then(() => { this.delBusy = false; });
      },
      initials(n){ return String(n || '?').split(' ').map(s=>s[0]).join('').slice(0,2).toUpperCase(); },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Users</h1><p class="page-sub" style="margin-top:6px">Accounts, roles and capabilities.</p></div>
        <div class="flex gap-2">
          <button class="btn btn--ghost" @click="go('roles')"><Icon name="lock"/> Roles &amp; capabilities</button>
          <button class="btn btn--accent" @click="openAdd"><Icon name="plus"/> Add user</button>
        </div>
      </div>
      <div class="card">
        <div class="list-toolbar">
          <div class="topbar-search" style="max-width:300px;flex:0 1 300px;cursor:text"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="q" placeholder="Search users by name, email or role…" style="color:var(--ink)"></div>
          <span class="t-meta">{{ filtered.length }} of {{ rows.length }}</span>
          <div class="grow"></div>
        </div>
        <div class="list-toolbar" v-if="sel.length" style="border-top:1px solid var(--line)">
          <span class="badge badge--info">{{ sel.length }} selected</span>
          <span class="t-meta">Change role to</span>
          <UiSelect style="width:auto" v-model="bulkRole"><option value="">Choose…</option><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.label }}</option></UiSelect>
          <button class="btn btn--sm" @click="applyRole" :disabled="!bulkRole"><Icon name="check" :size="14"/> Apply</button>
          <div class="grow"></div>
          <button class="btn btn--ghost btn--sm" @click="bulkDeactivate"><Icon name="eyeOff" :size="14"/> Deactivate</button>
        </div>
        <table class="tbl tbl--rows">
          <thead><tr><th style="width:36px"><UiChk :modelValue="allOn" @update:modelValue="toggleAll"/></th><th>User</th><th style="width:170px">Role</th><th style="width:110px">Status</th><th style="width:120px">Last active</th></tr></thead>
          <tbody>
            <tr v-for="u in filtered" :key="u.id" :class="{'row-sel': sel.includes(u.id)}">
              <td @click.stop><UiChk :modelValue="sel.includes(u.id)" @update:modelValue="toggle(u.id)"/></td>
              <td><div class="flex items-center gap-3"><span class="avatar">{{ initials(u.name) }}</span><div><strong>{{ u.name }}</strong><div class="t-meta">{{ u.email }}</div>
                <div class="row-actions">
                  <button>Edit</button>
                  <button>Reset password</button>
                  <button @click="toggleActive(u)">{{ u.status==='Active' ? 'Deactivate' : 'Activate' }}</button>
                  <button class="is-danger" @click="askDelete(u)">Delete</button>
                </div>
              </div></div></td>
              <td><UiSelect class="sel sel--bare" :modelValue="u.role" @update:modelValue="v => onRole(u, v)"><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.label }}</option></UiSelect></td>
              <td><span class="badge" :class="u.status==='Active'?'badge--ok':'badge--warn'">{{ u.status }}</span></td>
              <td class="muted">{{ u.last }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="!loading && !filtered.length" class="t-meta" style="padding:18px">{{ err || 'No users yet.' }}</div>
      </div>

      <UiModal v-if="addOpen" @close="addOpen=false">
        <div class="drawer-head"><Icon name="user" :size="18"/><strong style="font-size:15px;flex:1">Add user</strong><button class="iconbtn" @click="addOpen=false"><Icon name="x"/></button></div>
        <div class="drawer-body">
          <p class="t-meta" style="margin:0 0 14px">They can sign in straight away with the password you set here.</p>
          <div class="field"><label class="field-label">First name</label><input class="inp" v-model="add.first_name"></div>
          <div class="field"><label class="field-label">Last name</label><input class="inp" v-model="add.last_name"></div>
          <div class="field"><label class="field-label">Email <span class="field-req">*</span></label><input class="inp" type="email" v-model="add.email" placeholder="name@example.com"></div>
          <div class="field"><label class="field-label">Role</label><UiSelect v-model="add.role"><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.label }}</option></UiSelect></div>
          <div class="field" style="margin:0"><label class="field-label">Password <span class="field-req">*</span></label><input class="inp" type="password" v-model="add.password" placeholder="At least 8 characters"></div>
          <p v-if="addErr" class="t-meta" style="color:var(--danger);margin-top:10px">{{ addErr }}</p>
        </div>
        <div class="drawer-foot">
          <button class="btn btn--ghost" @click="addOpen=false">Cancel</button>
          <button class="btn btn--accent" :disabled="addBusy" @click="submitAdd"><Icon name="plus" :size="15"/> {{ addBusy ? 'Creating…' : 'Create user' }}</button>
        </div>
      </UiModal>

      <UiModal v-if="delOpen" @close="delOpen=false">
        <div class="drawer-head"><Icon name="trash" :size="18"/><strong style="font-size:15px;flex:1">Delete {{ delUser && delUser.name }}</strong><button class="iconbtn" @click="delOpen=false"><Icon name="x"/></button></div>
        <div class="drawer-body">
          <p class="t-meta" style="margin:0 0 14px">This removes the account for good. Everything it owns is handed to the user you pick, so nothing is left orphaned.</p>
          <p v-if="delContent" class="t-meta" style="margin:0 0 14px">
            <template v-if="delContent.total">Owns {{ delContent.total }} record<span v-if="delContent.total!==1">s</span>:
              <span v-for="(n,t) in delContent.by_table" :key="t">{{ t }} ({{ n }}) </span>
            </template>
            <template v-else>Owns no content.</template>
          </p>
          <div class="field" style="margin:0"><label class="field-label">Hand content over to <span class="field-req">*</span></label>
            <UiSelect v-model="delReassignTo">
              <option value="">Choose a user…</option>
              <option v-for="r in heirs" :key="r.id" :value="String(r.id)">{{ r.name }} ({{ r.email }})</option>
            </UiSelect>
          </div>
          <p v-if="delErr" class="t-meta" style="color:var(--danger);margin-top:10px">{{ delErr }}</p>
        </div>
        <div class="drawer-foot">
          <button class="btn btn--ghost" @click="delOpen=false">Cancel</button>
          <button class="btn btn--danger" :disabled="delBusy" @click="confirmDelete"><Icon name="trash" :size="15"/> {{ delBusy ? 'Deleting…' : 'Delete user' }}</button>
        </div>
      </UiModal>
    </div>`,
  };

  C.ScreenSettings = {
    name: 'ScreenSettings',
    inject: ['route', 'go'],
    data() {
      return {
        nav: [
          { group:'Site', items:[
            { id:'general', label:'General', icon:'settings' },
            { id:'appearance', label:'Appearance', icon:'palette' },
            { id:'permalinks', label:'Permalinks', icon:'link' },
            { id:'menus', label:'Menus', icon:'menu' },
          ]},
          { group:'Content', items:[
            { id:'media', label:'Media images', icon:'image' },
          ]},
          { group:'Delivery', items:[
            { id:'headless', label:'Headless API', icon:'webhook' },
          ]},
          { group:'Access', items:[
            { id:'roles', label:'Roles & capabilities', icon:'users' },
            { id:'security', label:'Security', icon:'lock' },
          ]},
          { group:'System', items:[
            { id:'design', label:'Design system', icon:'palette' },
            { id:'advanced', label:'Advanced', icon:'database' },
          ]},
        ],
        presets: () => window.__mdlr.data.IMAGE_PRESETS,
        gen: {
          title:'Atypisch', tagline:'EU-hosted web platforms', url:'https://atypisch.nl',
          email:'marten@atypisch.nl', delivery:'themed', language:'English', timezone:'Europe/Amsterdam',
          allowRegistration:false,
        },
        editPreset: null,
        flags: {
          debug:false, restApi:true, graphql:false,
          force2fa:true, rateLimit:true, audit:true,
          adminBar:true, compact:false, keepOriginal:true, webp:true,
        },
        roles: [
          { name:'Administrator', users:1, caps:'All capabilities' },
          { name:'Editor', users:2, caps:'Publish & manage all content' },
          { name:'Author', users:1, caps:'Publish own content' },
          { name:'Contributor', users:0, caps:'Write drafts only' },
        ],
        saving: false, saved: false,
      };
    },
    created() {
      if (!window.__mdlr.api) return;
      window.__mdlr.api.get('/settings').then(s => {
        if (!s || typeof s !== 'object') return;
        if (s.site_title != null || s.site_name != null) this.gen.title = s.site_title != null ? s.site_title : s.site_name;
        if (s.site_tagline != null) this.gen.tagline = s.site_tagline;
        if (s.site_url != null) this.gen.url = s.site_url;
        if (s.admin_email != null) this.gen.email = s.admin_email;
        if (s.default_language) this.gen.language = s.default_language;
        if (s.timezone) this.gen.timezone = s.timezone;
        if (s.allow_registration != null) this.gen.allowRegistration = ['1', 'true', 'on', 'yes', true, 1].includes(s.allow_registration);
      }).catch(() => {});
    },
    computed: {
      tab() { return (this.route.params || {}).tab || 'general'; },
      tabLabel() { for (const g of this.nav) { const it = g.items.find(i => i.id === this.tab); if (it) return it.label; } return 'General'; },
      imagePresets() { return window.__mdlr.data.IMAGE_PRESETS; },
      themes() { return window.__mdlr.data.THEMES; },
    },
    methods: {
      activateTheme(t) { window.__mdlr.data.THEMES.forEach(x => { x.active = (x === t); }); },
      addPreset() { const p = { name:'new-size', w:800, h:600, ratio:'4:3', crop:'cover', on:true, hue:30 }; window.__mdlr.data.IMAGE_PRESETS.push(p); this.editPreset = p; },
      removePreset(p) { const a = window.__mdlr.data.IMAGE_PRESETS; const i = a.indexOf(p); if (i >= 0) a.splice(i, 1); if (this.editPreset === p) this.editPreset = null; },
      restorePresets() {
        const a = window.__mdlr.data.IMAGE_PRESETS;
        a.splice(0, a.length, ...JSON.parse(JSON.stringify(window.__mdlr.data.IMAGE_PRESETS_DEFAULT)));
        this.editPreset = null;
      },
      presetsDirty() {
        return JSON.stringify(window.__mdlr.data.IMAGE_PRESETS) !== JSON.stringify(window.__mdlr.data.IMAGE_PRESETS_DEFAULT);
      },
      gradFor(h) { return 'linear-gradient(135deg, hsl(' + h + ' 32% 80%), hsl(' + (h - 8) + ' 26% 58%))'; },
      saveSettings() {
        if (!window.__mdlr.api) return;
        this.saving = true;
        const options = { site_title: this.gen.title, site_tagline: this.gen.tagline, site_url: this.gen.url, admin_email: this.gen.email, default_language: this.gen.language, timezone: this.gen.timezone, allow_registration: this.gen.allowRegistration ? '1' : '0' };
        window.__mdlr.api.put('/settings', { options })
          .then(() => { this.saving = false; this.saved = true; setTimeout(() => { this.saved = false; }, 2200); })
          .catch(e => { this.saving = false; console.error('[settings] save failed', e); alert('Saving settings failed.'); });
      },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head"><div><h1 class="t-title t-h1">Settings</h1><p class="page-sub" style="margin-top:6px">Everything that configures this install — site, delivery and access.</p></div><div class="flex items-center gap-2"><transition name="fade"><span v-if="saved" class="badge badge--ok"><Icon name="check" :size="12"/> Saved</span></transition><button class="btn" :disabled="saving" @click="saveSettings"><Icon name="save" :size="15"/> {{ saving ? 'Saving…' : 'Save' }}</button></div></div>
      <div class="settings-layout">
        <nav class="settings-nav">
          <div v-for="g in nav" :key="g.group" class="settings-nav-group">
            <div class="settings-nav-label">{{ g.group }}</div>
            <button v-for="it in g.items" :key="it.id" class="settings-nav-item" :class="{'is-on': tab===it.id}" @click="go('settings',{tab:it.id})">
              <Icon :name="it.icon" :size="15"/> {{ it.label }}
            </button>
          </div>
        </nav>

        <div class="settings-content">
          <!-- GENERAL -->
          <div v-if="tab==='general'" class="card card-pad">
            <h2 class="t-title t-h3" style="margin:0 0 20px">General</h2>
            <div class="field"><label class="field-label">Site title</label><input class="inp" v-model="gen.title"></div>
            <div class="field"><label class="field-label">Tagline</label><input class="inp" v-model="gen.tagline"></div>
            <div class="field"><label class="field-label">Site URL</label><input class="inp mono" v-model="gen.url"></div>
            <div class="field"><label class="field-label">Admin email</label><input class="inp mono" v-model="gen.email"></div>
            <div class="opt-row"><div><strong>Allow registration</strong><div class="field-hint">Let people create an account from the login screen. When off, the sign-up link is hidden and the registration page is blocked.</div></div><UiSwitch v-model="gen.allowRegistration"/></div>
            <div class="field" style="margin-top:14px">
              <label class="field-label">Delivery mode</label>
              <div class="seg seg--fill">
                <button :class="{'is-on':gen.delivery==='themed'}" @click="gen.delivery='themed'"><Icon name="image" :size="14"/> Themed</button>
                <button :class="{'is-on':gen.delivery==='headless'}" @click="gen.delivery='headless'"><Icon name="webhook" :size="14"/> Headless (API)</button>
                <button :class="{'is-on':gen.delivery==='hybrid'}" @click="gen.delivery='hybrid'"><Icon name="layers" :size="14"/> Hybrid</button>
              </div>
              <div class="field-hint">{{ gen.delivery==='themed' ? 'Modular renders a public theme from your content.' : gen.delivery==='headless' ? 'Content is served only through the read API — no rendered theme.' : 'Both a rendered theme and a read API, from the same content.' }}</div>
            </div>
            <div class="opt-row"><div><strong>Default language</strong><div class="field-hint">Used for new content.</div></div><UiSelect style="width:auto" v-model="gen.language"><option>English</option><option>Nederlands</option></UiSelect></div>
            <div class="opt-row" style="padding-bottom:0"><div><strong>Timezone</strong><div class="field-hint">For scheduling &amp; timestamps.</div></div><UiSelect style="width:auto" v-model="gen.timezone"><option>Europe/Amsterdam</option><option>UTC</option></UiSelect></div>
          </div>

          <!-- APPEARANCE -->
          <div v-else-if="tab==='appearance'" class="settings-embed">
            <div class="flex items-center justify-between" style="margin-bottom:6px;flex-wrap:wrap;gap:12px">
              <div><h2 class="t-title t-h2" style="margin:0">Appearance</h2><p class="page-sub" style="margin-top:4px">Choose the public site theme. The admin’s own look lives in the <a class="mode-help-link" style="display:inline-flex" @click.prevent="go('settings',{tab:'design'})">design system</a>.</p></div>
              <button class="btn btn--ghost btn--sm"><Icon name="upload" :size="14"/> Upload theme</button>
            </div>

            <div class="theme-grid">
              <div class="theme-card" v-for="t in themes" :key="t.id" :class="{'is-active': t.active}">
                <div class="theme-thumb" :class="{'is-dark': t.dark}" :style="{background:t.palette[0]}">
                  <span class="th-bar" :style="{background:t.palette[1], borderColor:t.palette[3]}"><span class="th-dot" :style="{background:t.palette[3]}"></span><span class="th-line" :style="{background:t.palette[2],opacity:.35}"></span></span>
                  <span class="th-hero"><span class="th-h" :style="{background:t.palette[2]}"></span><span class="th-h th-h--sm" :style="{background:t.palette[2],opacity:.5}"></span><span class="th-btn" :style="{background:t.palette[3]}"></span></span>
                  <span class="th-cards"><span :style="{background:t.palette[1],borderColor:t.palette[3]}"></span><span :style="{background:t.palette[1],borderColor:t.palette[3]}"></span><span :style="{background:t.palette[1],borderColor:t.palette[3]}"></span></span>
                  <span class="theme-active-flag" v-if="t.active"><Icon name="check" :size="13"/></span>
                </div>
                <div class="theme-meta">
                  <div><strong>{{ t.name }}</strong><div class="t-meta">{{ t.desc }}</div></div>
                </div>
                <div class="theme-foot">
                  <span class="t-key">by {{ t.author }}</span>
                  <div class="flex gap-2">
                    <button class="btn btn--ghost btn--sm">Preview</button>
                    <button v-if="t.active" class="btn btn--sm" disabled><Icon name="check" :size="13"/> Active</button>
                    <button v-else class="btn btn--soft btn--sm" @click="activateTheme(t)">Activate</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="card card-pad" style="margin-top:18px;max-width:760px">
              <h3 class="t-title t-h3" style="margin:0 0 14px">Admin preferences</h3>
              <div class="opt-row" style="padding-top:0"><div><strong>Show admin bar on site</strong><div class="field-hint">For logged-in editors.</div></div><UiSwitch v-model="flags.adminBar"/></div>
              <div class="opt-row" style="padding-bottom:0"><div><strong>Compact density</strong><div class="field-hint">Tighter rows across the admin.</div></div><UiSwitch v-model="flags.compact"/></div>
            </div>
          </div>

          <!-- PERMALINKS -->
          <div v-else-if="tab==='permalinks'" class="settings-embed">
            <PermalinksPanel/>
          </div>

          <!-- MENUS -->
          <div v-else-if="tab==='menus'" class="settings-embed">
            <MenusPanel/>
          </div>

          <!-- MEDIA -->
          <div v-else-if="tab==='media'" class="settings-embed">
            <div class="flex items-start justify-between" style="margin-bottom:16px;gap:16px;flex-wrap:wrap">
              <div>
                <h2 class="t-title t-h2" style="margin:0">Media images</h2>
                <p class="page-sub" style="margin-top:6px;max-width:62ch">Every uploaded image is re-cropped into these presets. Keep the set small — more presets means more storage and slower uploads.</p>
              </div>
              <div class="flex gap-2">
                <button class="btn btn--ghost btn--sm" :disabled="!presetsDirty()" @click="restorePresets"><Icon name="refresh" :size="14"/> Restore defaults</button>
                <button class="btn btn--accent btn--sm" @click="addPreset"><Icon name="plus" :size="14"/> Add preset</button>
              </div>
            </div>

            <div class="preset-grid">
              <div class="preset-card" v-for="p in imagePresets" :key="p.name" :class="{'is-off': !p.on, 'is-editing': editPreset===p}">
                <div class="preset-thumb" :style="{ aspectRatio: (p.w/p.h), background: gradFor(p.hue) }">
                  <span class="preset-dim mono">{{ p.w }}×{{ p.h }}</span>
                  <div class="preset-thumb-actions">
                    <button class="btn btn--soft btn--xs"><Icon name="image" :size="12"/> Sample</button>
                  </div>
                </div>
                <div class="preset-body">
                  <template v-if="editPreset!==p">
                    <div class="preset-meta">
                      <strong>{{ p.name }}</strong>
                      <span class="t-meta mono">{{ p.ratio }} · {{ p.crop }}</span>
                    </div>
                    <div class="preset-foot">
                      <label class="mod-toggle"><span class="t-meta">{{ p.on ? 'On' : 'Off' }}</span><UiSwitch v-model="p.on"/></label>
                      <div class="grow"></div>
                      <button class="iconbtn iconbtn--sm" @click="editPreset=p" title="Edit"><Icon name="pencil" :size="14"/></button>
                      <button class="iconbtn iconbtn--sm is-danger" @click="removePreset(p)" title="Delete"><Icon name="trash" :size="14"/></button>
                    </div>
                  </template>
                  <template v-else>
                    <div class="field" style="margin-bottom:10px"><label class="field-label">Name</label><input class="inp" v-model="p.name"></div>
                    <div class="cfg-2" style="margin-bottom:10px">
                      <div class="field" style="margin:0"><label class="field-label">Width</label><input class="inp mono" type="number" v-model.number="p.w"></div>
                      <div class="field" style="margin:0"><label class="field-label">Height</label><input class="inp mono" type="number" v-model.number="p.h"></div>
                    </div>
                    <div class="cfg-2" style="margin-bottom:12px">
                      <div class="field" style="margin:0"><label class="field-label">Ratio label</label><input class="inp mono" v-model="p.ratio"></div>
                      <div class="field" style="margin:0"><label class="field-label">Fit</label><UiSelect v-model="p.crop"><option value="cover">cover (crop)</option><option value="contain">contain</option></UiSelect></div>
                    </div>
                    <div class="flex gap-2" style="justify-content:flex-end">
                      <button class="btn btn--ghost btn--sm" @click="removePreset(p)"><Icon name="trash" :size="13"/> Delete</button>
                      <button class="btn btn--accent btn--sm" @click="editPreset=null"><Icon name="check" :size="14"/> Done</button>
                    </div>
                  </template>
                </div>
              </div>

              <button class="preset-add" @click="addPreset"><Icon name="plus" :size="20"/><span>Add preset</span></button>
            </div>

            <div class="card card-pad" style="margin-top:18px;max-width:760px">
              <div class="opt-row" style="padding-top:0"><div><strong>Keep original upload</strong><div class="field-hint">Store the full-resolution file alongside the presets.</div></div><UiSwitch v-model="flags.keepOriginal"/></div>
              <div class="opt-row"><div><strong>Convert to WebP</strong><div class="field-hint">Generate modern formats for each preset.</div></div><UiSwitch v-model="flags.webp"/></div>
              <div class="opt-row" style="padding-bottom:0"><div><strong>Max upload size</strong><div class="field-hint">Reject files larger than this.</div></div><UiSelect style="width:auto"><option>10 MB</option><option>25 MB</option><option>50 MB</option></UiSelect></div>
            </div>
          </div>

          <!-- HEADLESS -->
          <div v-else-if="tab==='headless'" class="card card-pad">
            <h2 class="t-title t-h3" style="margin:0 0 8px">Headless API</h2>
            <p class="page-sub" style="margin-bottom:16px">A read API over the same content. Same token model as templates.</p>
            <div class="opt-row" style="padding-top:0"><div><strong>Enable REST API</strong><div class="field-hint">Exposes <span class="t-key">/api/v1</span>.</div></div><UiSwitch v-model="flags.restApi"/></div>
            <div class="opt-row"><div><strong>Enable GraphQL</strong><div class="field-hint">Single endpoint at <span class="t-key">/graphql</span>.</div></div><UiSwitch v-model="flags.graphql"/></div>
            <div class="field" style="margin-top:16px"><label class="field-label">Base URL</label><input class="inp mono" value="https://atypisch.nl/api/v1"></div>
            <div class="field" style="margin:0"><label class="field-label">API key</label><div class="inp-group"><input class="inp mono" value="md_live_8f2c…a91" readonly><span class="affix" style="border:0;border-left:1px solid var(--line);cursor:pointer">copy</span></div></div>
          </div>

          <!-- ROLES -->
          <div v-else-if="tab==='roles'" class="card">
            <div class="card-pad" style="padding-bottom:6px"><h2 class="t-title t-h3" style="margin:0 0 4px">Roles &amp; capabilities</h2><p class="page-sub">Who can do what. Manage members in <a class="mode-help-link" style="display:inline-flex" @click.prevent="go('users')">Users</a>.</p></div>
            <table class="tbl">
              <thead><tr><th>Role</th><th style="width:90px">Users</th><th>Capabilities</th><th style="width:48px"></th></tr></thead>
              <tbody>
                <tr v-for="r in roles" :key="r.name">
                  <td><strong>{{ r.name }}</strong></td>
                  <td class="mono muted">{{ r.users }}</td>
                  <td class="muted">{{ r.caps }}</td>
                  <td style="text-align:right"><button class="iconbtn" @click="go('users')"><Icon name="chevRight" :size="15"/></button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- SECURITY -->
          <div v-else-if="tab==='security'" class="card card-pad">
            <h2 class="t-title t-h3" style="margin:0 0 18px">Security</h2>
            <div class="opt-row" style="padding-top:0"><div><strong>Force two-factor for admins</strong><div class="field-hint">Require an authenticator app.</div></div><UiSwitch v-model="flags.force2fa"/></div>
            <div class="opt-row"><div><strong>Login rate limiting</strong><div class="field-hint">Throttle after 5 failed attempts.</div></div><UiSwitch v-model="flags.rateLimit"/></div>
            <div class="opt-row"><div><strong>Audit log</strong><div class="field-hint">Record edits, logins and imports.</div></div><UiSwitch v-model="flags.audit"/></div>
            <div class="opt-row" style="padding-bottom:0"><div><strong>Session length</strong><div class="field-hint">Auto-logout after inactivity.</div></div><UiSelect style="width:auto"><option>8 hours</option><option>24 hours</option><option>7 days</option></UiSelect></div>
          </div>

          <!-- ADVANCED -->
          <div v-else-if="tab==='advanced'" class="card card-pad">
            <h2 class="t-title t-h3" style="margin:0 0 18px">Advanced</h2>
            <div class="opt-row" style="padding-top:0"><div><strong>Debug mode</strong><div class="field-hint">Verbose errors in the admin.</div></div><UiSwitch v-model="flags.debug"/></div>
            <div v-if="flags.debug" class="note-inline" style="margin:0 0 4px"><Icon name="info" :size="15"/><span>Debug mode is <strong>on</strong> — stack traces and query logs are shown in the admin. Don’t leave this on in production.</span></div>
            <div class="opt-row"><div><strong>Object cache</strong><div class="field-hint">Redis-backed.</div></div><span class="badge badge--ok"><span class="badge--dot"></span>connected</span></div>
            <div class="opt-row"><div><strong>Export / Import</strong><div class="field-hint">Move content between installs.</div></div><button class="btn btn--ghost btn--sm" @click="go('exportImport',{tab:'export'})"><Icon name="external" :size="14"/> Open</button></div>
            <div class="opt-row" style="padding-bottom:0"><div><strong>Database</strong><div class="field-hint">MySQL 8 · 1,284 rows across 19 tables.</div></div><span class="t-key">modular_atypisch</span></div>
          </div>

          <!-- DESIGN SYSTEM (embedded) -->
          <div v-else-if="tab==='design'" class="settings-embed">
            <ScreenDesignSystem/>
          </div>
        </div>
      </div>
    </div>`,
  };
})();
;
/* file: screen-dashboard.js */
/* Dashboard screen (Fase 1). Overview tab is ported 1:1 from the Atelier
   prototype (screens-basic.js · ScreenDashboard). The Updates/Analytics/Reports
   tabs are stubbed here and land in Fase 11 (analytics/insights). */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  const tabStub = (label) => ({
    name: 'DashStub' + label,
    template: `<div class="empty" style="padding:48px"><Icon name="box" :size="26"/><div>` + label + ` — coming in a later phase.</div></div>`,
  });
  C.DashUpdates  = C.DashUpdates  || tabStub('Updates');
  C.DashAnalytics = C.DashAnalytics || tabStub('Analytics');
  C.DashReports  = C.DashReports  || tabStub('Reports');

  C.ScreenDashboard = {
    name: 'ScreenDashboard',
    inject: ['go', 'route'],
    computed: {
      tab() { return (this.route.params || {}).tab || 'overview'; },
      pts() { return D().POST_TYPES; },
      recent() { return D().POSTS.slice(0, 5); },
      updateCount() { return D().MODULES.filter(m => m.update).length + (D().SYSTEM.version !== D().SYSTEM.latest ? 1 : 0); },
      headTitle() {
        if (this.tab === 'overview') {
          const h = new Date().getHours();
          const greeting = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
          const profile = D().PROFILE;
          const name = profile && profile.name ? profile.name.split(' ')[0] : '';
          return name ? greeting + ', ' + name : greeting;
        }
        return { updates:'Updates', analytics:'Analytics', reports:'Reports' }[this.tab];
      },
      headSub() {
        if (this.tab === 'overview') {
          const s = D().STATS_OVERVIEW;
          if (s) {
            const parts = [];
            if (s.drafts > 0) parts.push(s.drafts + ' draft' + (s.drafts !== 1 ? 's' : '') + ' waiting');
            if (s.submissions > 0) parts.push(s.submissions + ' form submission' + (s.submissions !== 1 ? 's' : ''));
            return parts.length ? parts.join(' · ') + '.' : 'Everything is up to date.';
          }
          return 'Loading statistics\u2026';
        }
        return {
          updates:'Keep the CMS and its modules current.',
          analytics:'Content production and activity at a glance.',
          reports:'Build, export and schedule reports across your content.',
        }[this.tab];
      },
      stats() {
        const s = D().STATS_OVERVIEW;
        if (!s) return [
          { label: 'Content items', value: '\u2026', sub: '', icon: 'files' },
          { label: 'Published', value: '\u2026', sub: '', icon: 'check' },
          { label: 'Components', value: '\u2026', sub: '', icon: 'layers' },
          { label: 'Media', value: '\u2026', sub: '', icon: 'image' },
        ];
        return [
          { label: 'Content items', value: String(s.content_items), sub: s.post_types + ' post type' + (s.post_types !== 1 ? 's' : ''), icon: 'files' },
          { label: 'Published', value: String(s.published), sub: s.drafts + ' draft' + (s.drafts !== 1 ? 's' : ''), icon: 'check' },
          { label: 'Components', value: String(s.components), sub: 'reused ' + s.component_usage + '\u00d7', icon: 'layers' },
          { label: 'Media', value: String(s.media), sub: s.users + ' user' + (s.users !== 1 ? 's' : ''), icon: 'image' },
        ];
      },
    },
    methods: {
      typeName(t) { const p = this.pts.find(x => x.id === t); return p ? p.name : t; },
      statusClass(s) { return s === 'published' ? 'badge--ok' : s === 'scheduled' ? 'badge--info' : ''; },
    },
    template: `
    <div class="page fade-up">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px">{{ new Date().toLocaleDateString('en-US', { weekday:'long', day:'numeric', month:'long' }) }}</div>
          <h1 class="t-title t-h1">{{ headTitle }}</h1>
          <p class="page-sub" style="margin-top:6px">{{ headSub }}</p>
        </div>
        <div class="flex gap-2" v-if="tab==='overview'">
          <button class="btn btn--ghost" @click="go('binding')"><Icon name="settings2"/> Field groups</button>
          <button class="btn btn--accent" @click="go('editor',{postId:'new'})"><Icon name="plus"/> New content</button>
        </div>
      </div>

      <div class="tabs" style="margin-bottom:22px">
        <button :class="{'is-on':tab==='overview'}" @click="go('dashboard',{})"><Icon name="dashboard" :size="15"/> Overview</button>
        <button :class="{'is-on':tab==='updates'}" @click="go('dashboard',{tab:'updates'})"><Icon name="refresh" :size="15"/> Updates <span class="tab-count" v-if="updateCount" style="color:var(--accent)">{{ updateCount }}</span></button>
        <button :class="{'is-on':tab==='analytics'}" @click="go('dashboard',{tab:'analytics'})"><Icon name="sigma" :size="15"/> Analytics</button>
        <button :class="{'is-on':tab==='reports'}" @click="go('dashboard',{tab:'reports'})"><Icon name="table" :size="15"/> Reports</button>
      </div>

      <!-- OVERVIEW -->
      <template v-if="tab==='overview'">
        <div class="dash-stats">
          <div class="card card-pad stat" v-for="s in stats" :key="s.label">
            <div class="stat-top"><span class="stat-ico"><Icon :name="s.icon" :size="16"/></span><span class="t-meta">{{ s.label }}</span></div>
            <div class="stat-val t-display">{{ s.value }}</div>
            <div class="t-meta">{{ s.sub }}</div>
          </div>
        </div>
        <div class="dash-grid">
          <section class="panel">
            <div class="panel-head"><h2 class="t-title t-h3">Jump back in</h2><button class="btn btn--text btn--sm" @click="go('content',{type:'project'})">All content <Icon name="arrowRight" :size="14"/></button></div>
            <div class="dash-list">
              <button class="dash-row" v-for="p in recent" :key="p.id" @click="go('editor',{postId:p.id})">
                <span class="dash-row-main"><strong>{{ p.title }}</strong><span class="t-meta">{{ typeName(p.type) }} · {{ p.date }}</span></span>
                <span class="badge" :class="statusClass(p.status)">{{ p.status }}</span>
                <Icon name="chevRight" :size="16" class="muted"/>
              </button>
            </div>
          </section>
          <section class="panel">
            <div class="panel-head"><h2 class="t-title t-h3">Structure</h2><button class="btn btn--text btn--sm" @click="go('postTypes')">Post types <Icon name="arrowRight" :size="14"/></button></div>
            <div class="dash-list">
              <button class="dash-row" v-for="pt in pts" :key="pt.id" @click="go('content',{type:pt.id})">
                <span class="dash-row-ico"><Icon :name="pt.icon" :size="16"/></span>
                <span class="dash-row-main"><strong>{{ pt.name }}</strong><span class="t-meta mono">{{ pt.fieldCount }} fields · {{ pt.count }} items</span></span>
                <Icon name="chevRight" :size="16" class="muted"/>
              </button>
            </div>
          </section>
        </div>
      </template>

      <DashUpdates v-else-if="tab==='updates'"/>
      <DashAnalytics v-else-if="tab==='analytics'"/>
      <DashReports v-else/>
    </div>`,
  };
})();
;
/* file: screen-posttypes.js */
/* ============================================================
   POST TYPES — list every content type, create/edit/delete them,
   and jump into the field builder. Backed by the /post-types API
   (PostTypesController). Fixed types keep a locked schema; modular
   types let editors compose per item.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const ICONS = ['file', 'files', 'image', 'database', 'layers', 'box', 'star', 'form', 'grid', 'users', 'settings', 'zap', 'link'];

  const blank = () => ({ _dbId: null, name: '', slug: '', desc: '', icon: 'file', mode: 'fixed', hierarchical: false, public: true, inMenu: true });

  C.ScreenPostTypes = {
    name: 'ScreenPostTypes',
    inject: ['go'],
    data() { return { showEdit: false, draft: blank(), saving: false, slugTouched: false, confirmDel: null, icons: ICONS }; },
    computed: {
      types() { return D().POST_TYPES; },
    },
    methods: {
      slugify(s) { return (s || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); },
      onName() { if (!this.slugTouched) this.draft.slug = this.slugify(this.draft.name); },
      openNew() { this.draft = blank(); this.slugTouched = false; this.showEdit = true; },
      openEdit(t) {
        this.draft = { _dbId: t._dbId, name: t.name, slug: t.slug, desc: t.desc, icon: t.icon || 'file', mode: t.mode || 'fixed', hierarchical: !!t.hierarchical, public: t.public !== false, inMenu: t.inMenu !== false };
        this.slugTouched = true; this.showEdit = true;
      },
      editFields(t) { this.go('fieldBuilder', { type: t.id }); },
      async save() {
        const d = this.draft;
        if (!d.name.trim()) return;
        if (!d.slug) d.slug = this.slugify(d.name);
        this.saving = true;
        try { await window.__mdlr.store.savePostType(d); this.showEdit = false; }
        catch (e) { alert('Saving the post type failed: ' + ((e && e.data && e.data.error) || (e && e.status) || 'error')); }
        this.saving = false;
      },
      async remove(t) {
        try { await window.__mdlr.store.deletePostType(t); this.confirmDel = null; }
        catch (e) { alert('Deleting the post type failed.'); }
      },
      modeLabel(m) { return m === 'modular' ? 'Modular' : 'Fixed'; },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <h1 class="t-title t-h1">Post types</h1>
          <p class="page-sub" style="margin-top:6px">The content types in this install. A <strong style="color:var(--ink-2)">fixed</strong> type keeps one schema for every item; a <strong style="color:var(--ink-2)">modular</strong> type lets editors compose fields per item. Field layout lives in the field builder.</p>
        </div>
        <button class="btn btn--accent" @click="openNew"><Icon name="plus"/> New type</button>
      </div>

      <div class="card">
        <table class="tbl tbl--rows">
          <thead><tr><th>Type</th><th style="width:110px">Mode</th><th style="width:130px">Fields</th><th style="width:150px">Visibility</th><th style="width:80px">Items</th></tr></thead>
          <tbody>
            <tr v-for="t in types" :key="t.id">
              <td>
                <div class="flex items-center gap-3">
                  <span class="dash-row-ico"><Icon :name="t.icon || 'file'" :size="16"/></span>
                  <div>
                    <a class="ce-title" @click="editFields(t)">{{ t.name }}</a>
                    <div class="t-meta mono">{{ t.slug }}</div>
                    <div class="row-actions">
                      <button @click="editFields(t)">Edit fields</button>
                      <button @click="openEdit(t)">Settings</button>
                      <button @click="go('content',{type:t.id})">View content</button>
                      <button class="is-danger" @click="confirmDel=t">Delete</button>
                    </div>
                  </div>
                </div>
              </td>
              <td><span class="badge" :class="t.mode==='modular'?'badge--info':''">{{ modeLabel(t.mode) }}</span></td>
              <td><span class="mono muted">{{ t.fieldCount }} {{ t.fieldCount===1?'field':'fields' }}</span></td>
              <td>
                <div class="flex gap-2 wrap">
                  <span class="badge" v-if="t.public"><Icon name="globe" :size="11"/> Public</span>
                  <span class="badge" v-if="t.inMenu"><Icon name="panelLeft" :size="11"/> In menu</span>
                  <span class="badge" v-if="t.hierarchical"><Icon name="git" :size="11"/> Nested</span>
                </div>
              </td>
              <td><span class="mono muted">{{ t.count }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- create / edit drawer -->
      <UiModal v-if="showEdit" @close="showEdit=false">
        <div class="drawer-head"><Icon name="database" :size="18"/><strong style="font-size:15px;flex:1">{{ draft._dbId==null ? 'New post type' : 'Edit post type' }}</strong><button class="iconbtn" @click="showEdit=false"><Icon name="x"/></button></div>
        <div class="drawer-body">
          <div class="field"><label class="field-label">Name <span class="field-req">*</span></label><input class="inp" v-model="draft.name" @input="onName" placeholder="e.g. Project"></div>
          <div class="field"><label class="field-label">Slug <span class="field-req">*</span></label><input class="inp mono" v-model="draft.slug" @input="slugTouched=true" placeholder="project"></div>
          <div class="field"><label class="field-label">Description</label><input class="inp" v-model="draft.desc" placeholder="What this type is for"></div>
          <div class="field">
            <label class="field-label">Icon</label>
            <UiSelect v-model="draft.icon"><option v-for="i in icons" :key="i" :value="i">{{ i }}</option></UiSelect>
          </div>
          <div class="field">
            <label class="field-label">Mode</label>
            <UiSelect v-model="draft.mode"><option value="fixed">Fixed — one schema for every item</option><option value="modular">Modular — composed per item</option></UiSelect>
          </div>
          <div class="opt-row"><div><strong>Hierarchical</strong><div class="field-hint">Items can have a parent (like Pages).</div></div><UiSwitch v-model="draft.hierarchical"/></div>
          <div class="opt-row"><div><strong>Public</strong><div class="field-hint">Exposed on the themed site and the API.</div></div><UiSwitch v-model="draft.public"/></div>
          <div class="opt-row" style="border:0"><div><strong>Show in menu</strong><div class="field-hint">Appears in the admin sidebar.</div></div><UiSwitch v-model="draft.inMenu"/></div>
        </div>
        <div class="drawer-foot">
          <button class="btn btn--ghost" @click="showEdit=false">Cancel</button>
          <button class="btn btn--accent" @click="save" :disabled="!draft.name.trim() || saving"><Icon name="check" :size="15"/> {{ saving ? 'Saving…' : (draft._dbId==null ? 'Create type' : 'Save changes') }}</button>
        </div>
      </UiModal>

      <!-- delete confirm -->
      <UiModal v-if="confirmDel" @close="confirmDel=null">
        <div class="drawer-head"><Icon name="trash" :size="18"/><strong style="font-size:15px;flex:1">Delete “{{ confirmDel.name }}”?</strong><button class="iconbtn" @click="confirmDel=null"><Icon name="x"/></button></div>
        <div class="drawer-body"><p class="page-sub" style="margin:0">This removes the post type. Its content is kept in the database but will no longer be editable here. This can’t be undone from the UI.</p></div>
        <div class="drawer-foot"><button class="btn btn--ghost" @click="confirmDel=null">Cancel</button><button class="btn btn--danger" @click="remove(confirmDel)"><Icon name="trash" :size="15"/> Delete type</button></div>
      </UiModal>
    </div>`,
  };
})();
;
/* file: screen-taxonomies.js */
/* ============================================================
   TAXONOMIES — manage taxonomies and their terms.
   - Drag to reorder terms; drag a term ONTO another (hierarchical)
     to nest it as a child. Drop on the "top" zone to un-nest.
   - Click a term (or Edit) to open the single-term editor drawer
     (name, slug, description, parent, SEO). Same drawer adds terms.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const slug = (s) => s.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

  C.ScreenTaxonomies = {
    name: 'ScreenTaxonomies',
    inject: ['go', 'route'],
    data() { return { taxes: [], selId: null, edit: null, isNew: false, drag: null, dropOnId: null, dropTop: false, taxEdit: null, taxIsNew: false }; },
    created() {
      this.taxes = JSON.parse(JSON.stringify(D().TAXONOMIES));
      // ensure every term has an order index for stable drag-sort
      this.taxes.forEach(t => t.terms.forEach((tm, i) => { if (tm.order == null) tm.order = i; }));
      const t = (this.route.params || {}).tax;
      this.selId = (t && this.taxes.some(x => x.id === t)) ? t : this.taxes[0].id;
    },
    computed: {
      sel() { return this.taxes.find(t => t.id === this.selId) || this.taxes[0]; },
      ptName() { return (id) => { const p = D().POST_TYPES.find(x => x.id === id); return p ? p.name : id; }; },
      ordered() { return this.sel.terms.slice().sort((a, b) => (a.order || 0) - (b.order || 0)); },
      roots() { return this.ordered.filter(t => !t.parent); },
      parentOptions() { return this.roots.filter(t => !this.edit || t.id !== this.edit.id); },
    },
    methods: {
      children(id) { return this.ordered.filter(t => t.parent === id); },
      // ---- term editor drawer ----
      openNew() { this.isNew = true; this.edit = { id: D().uid('t'), name: '', slug: '', desc: '', parent: null, count: 0, seoTitle: '', seoDesc: '', order: this.sel.terms.length }; },
      openEdit(t) { this.isNew = false; this.edit = JSON.parse(JSON.stringify(t)); },
      onName() { if (this.isNew || !this.edit._slugTouched) this.edit.slug = slug(this.edit.name); },
      saveTerm() {
        const e = this.edit; if (!e.name.trim()) return;
        if (!e.slug) e.slug = slug(e.name);
        const api = window.__mdlr.api; const taxDbId = this.sel && this.sel._dbId;
        const body = { name: e.name, slug: e.slug, description: e.desc, parent_id: (e.parent && !isNaN(Number(e.parent))) ? Number(e.parent) : null };
        if (this.isNew) {
          const local = JSON.parse(JSON.stringify(e)); this.sel.terms.push(local);
          if (api && taxDbId) api.post('/taxonomies/' + taxDbId + '/terms', body).then(r => { if (r && r.id) local.id = r.id; }).catch(() => {});
        } else {
          const i = this.sel.terms.findIndex(t => t.id === e.id); if (i >= 0) this.sel.terms[i] = JSON.parse(JSON.stringify(e));
          if (api && taxDbId && !isNaN(Number(e.id))) api.put('/taxonomies/' + taxDbId + '/terms/' + e.id, body).catch(() => {});
        }
        this.edit = null;
      },
      delTerm(id) {
        const api = window.__mdlr.api; const taxDbId = this.sel && this.sel._dbId;
        if (api && taxDbId && !isNaN(Number(id))) api.del('/taxonomies/' + taxDbId + '/terms/' + id).catch(() => {});
        this.sel.terms = this.sel.terms.filter(t => t.id !== id && t.parent !== id); if (this.edit && this.edit.id === id) this.edit = null;
      },
      // ---- drag & drop ----
      dStart(t, e) { this.drag = t; if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', t.id); } catch (_) {} } },
      dEnd() { this.drag = null; this.dropOnId = null; this.dropTop = false; },
      isDescendant(maybeChild, ofId) { let p = maybeChild.parent; const byId = (id) => this.sel.terms.find(x => x.id === id); while (p) { if (p === ofId) return true; const pt = byId(p); p = pt ? pt.parent : null; } return false; },
      onRow(t, e) {
        if (!this.drag || this.drag.id === t.id) return;
        e.preventDefault(); e.stopPropagation();
        this.dropOnId = t.id; this.dropTop = false;
      },
      onTopZone(e) { if (!this.drag) return; e.preventDefault(); this.dropTop = true; this.dropOnId = null; },
      dropOnRow(t) {
        if (!this.drag || this.drag.id === t.id) { this.dEnd(); return; }
        const d = this.drag;
        if (this.sel.hierarchical && !t.parent && !this.isDescendant(t, d.id)) {
          // nest under t (only one level deep, and not into its own descendant)
          d.parent = t.id;
          // place at end of t's children
          const kids = this.children(t.id);
          d.order = (kids.length ? Math.max(...kids.map(k => k.order || 0)) : (t.order || 0)) + 1;
        } else {
          // reorder as sibling of t at the same level
          d.parent = t.parent || null;
          this.reorderBefore(d, t);
        }
        this.normalize();
        this.dEnd();
      },
      dropOnTop() {
        if (!this.drag) { this.dEnd(); return; }
        this.drag.parent = null;
        this.drag.order = -1; // float to top, normalize fixes indices
        this.normalize();
        this.dEnd();
      },
      reorderBefore(d, target) {
        // give d an order just below target within the same parent group
        const group = this.ordered.filter(x => (x.parent || null) === (target.parent || null) && x.id !== d.id);
        const out = []; group.forEach(x => { if (x.id === target.id) out.push(d); out.push(x); });
        out.forEach((x, i) => { x.order = i; });
      },
      normalize() {
        // reassign clean order per parent group, roots first
        const groups = {};
        this.ordered.forEach(t => { const k = t.parent || '__root'; (groups[k] = groups[k] || []).push(t); });
        Object.keys(groups).forEach(k => groups[k].forEach((t, i) => { t.order = (k === '__root' ? 0 : 1000) + i; }));
      },
      rowClass(t) { return { 'is-dragging': this.drag && this.drag.id === t.id, 'is-dropon': this.dropOnId === t.id }; },
      // ---- taxonomy create / edit ----
      allPostTypes() { return D().POST_TYPES; },
      newTax() { this.taxIsNew = true; this.taxEdit = { id: D().uid('tx'), name: '', slug: '', desc: '', hierarchical: true, postTypes: [], terms: [] }; },
      editTax() { this.taxIsNew = false; this.taxEdit = { id: this.sel.id, name: this.sel.name, slug: this.sel.slug, desc: this.sel.desc || '', hierarchical: this.sel.hierarchical, postTypes: (this.sel.postTypes || []).slice() }; },
      onTaxName() { if (this.taxIsNew || !this.taxEdit._slugTouched) this.taxEdit.slug = slug(this.taxEdit.name); },
      togglePt(id) { const a = this.taxEdit.postTypes; const i = a.indexOf(id); if (i >= 0) a.splice(i, 1); else a.push(id); },
      saveTax() {
        const e = this.taxEdit; if (!e.name.trim()) return; if (!e.slug) e.slug = slug(e.name);
        const api = window.__mdlr.api;
        const body = { name: e.name, slug: e.slug, description: e.desc, hierarchical: e.hierarchical ? 1 : 0 };
        if (this.taxIsNew) {
          const local = { id: e.id, name: e.name, slug: e.slug, desc: e.desc, hierarchical: e.hierarchical, postTypes: e.postTypes.slice(), terms: [] };
          this.taxes.push(local); this.selId = e.id;
          if (api) api.post('/taxonomies', body).then(r => { if (r && r.id) local._dbId = r.id; }).catch(() => {});
        } else {
          const t = this.taxes.find(x => x.id === e.id); if (t) { t.name = e.name; t.slug = e.slug; t.desc = e.desc; t.hierarchical = e.hierarchical; t.postTypes = e.postTypes.slice(); if (api && t._dbId) api.put('/taxonomies/' + t._dbId, body).catch(() => {}); }
        }
        this.taxEdit = null;
      },
      delTax() {
        if (this.taxIsNew) { this.taxEdit = null; return; }
        const id = this.taxEdit.id; const t = this.taxes.find(x => x.id === id);
        if (window.__mdlr.api && t && t._dbId) window.__mdlr.api.del('/taxonomies/' + t._dbId).catch(() => {});
        this.taxes = this.taxes.filter(t => t.id !== id);
        this.selId = this.taxes.length ? this.taxes[0].id : null; this.taxEdit = null;
      },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Taxonomies</h1><p class="page-sub" style="margin-top:6px">Ways to group content — categories, tags, types. Bound to post types, exposed on the themed site and the API.</p></div>
        <button class="btn btn--accent" @click="newTax"><Icon name="plus"/> New taxonomy</button>
      </div>

      <div class="tax-layout">
        <aside class="tax-list">
          <button v-for="t in taxes" :key="t.id" class="tax-item" :class="{'is-on': selId===t.id}" @click="selId=t.id">
            <span class="dash-row-ico"><Icon name="pin" :size="15"/></span>
            <span class="tax-item-main"><strong>{{ t.name }}</strong><span class="t-key">{{ t.slug }}</span></span>
            <span class="badge">{{ t.terms.length }}</span>
          </button>
        </aside>

        <section class="tax-detail card">
          <div class="tax-detail-head">
            <div>
              <h2 class="t-title t-h3" style="margin:0">{{ sel.name }}</h2>
              <div class="flex items-center gap-2" style="margin-top:6px">
                <span class="badge" :class="sel.hierarchical?'badge--info':''"><Icon :name="sel.hierarchical?'layers':'minus'" :size="11"/>{{ sel.hierarchical?'hierarchical':'flat' }}</span>
                <span class="t-meta">on</span>
                <span class="badge" v-for="pt in sel.postTypes" :key="pt">{{ ptName(pt) }}</span>
              </div>
            </div>
            <div class="flex gap-2">
              <button class="btn btn--ghost btn--sm" @click="editTax"><Icon name="settings" :size="15"/> Settings</button>
              <button class="btn btn--accent btn--sm" @click="openNew"><Icon name="plus" :size="15"/> Add term</button>
            </div>
          </div>

          <div class="term-hint"><Icon name="info" :size="13"/> Drag to reorder.<template v-if="sel.hierarchical"> Drop a term onto another to nest it; drop on the line at the top to un-nest.</template></div>

          <div class="term-tree" @dragover.prevent @drop="dropOnTop">
            <div class="term-topzone" :class="{'is-on': dropTop}" @dragover="onTopZone" @drop.stop="dropOnTop"><span>Top level</span></div>
            <template v-for="t in roots" :key="t.id">
              <div class="term-row" :class="rowClass(t)" draggable="true"
                   @dragstart="dStart(t,$event)" @dragend="dEnd" @dragover="onRow(t,$event)" @drop.stop="dropOnRow(t)" @click="openEdit(t)">
                <Icon name="drag" :size="15" class="drag-handle"/>
                <Icon v-if="sel.hierarchical && children(t.id).length" name="chevDown" :size="14" class="muted"/>
                <span class="term-name">{{ t.name }}</span>
                <span class="t-key">{{ t.slug }}</span>
                <div class="grow"></div>
                <span class="badge">{{ t.count }} items</span>
                <div class="term-actions" @click.stop>
                  <button class="iconbtn iconbtn--sm" @click="openEdit(t)" title="Edit"><Icon name="pencil" :size="14"/></button>
                  <button class="iconbtn iconbtn--sm is-danger" @click="delTerm(t.id)" title="Delete"><Icon name="trash" :size="14"/></button>
                </div>
              </div>
              <div v-for="c in children(t.id)" :key="c.id" class="term-row term-row--child" :class="rowClass(c)" draggable="true"
                   @dragstart="dStart(c,$event)" @dragend="dEnd" @dragover="onRow(c,$event)" @drop.stop="dropOnRow(c)" @click="openEdit(c)">
                <Icon name="drag" :size="15" class="drag-handle"/>
                <span class="term-branch"></span>
                <span class="term-name">{{ c.name }}</span>
                <span class="t-key">{{ c.slug }}</span>
                <div class="grow"></div>
                <span class="badge">{{ c.count }} items</span>
                <div class="term-actions" @click.stop>
                  <button class="iconbtn iconbtn--sm" @click="openEdit(c)" title="Edit"><Icon name="pencil" :size="14"/></button>
                  <button class="iconbtn iconbtn--sm is-danger" @click="delTerm(c.id)" title="Delete"><Icon name="trash" :size="14"/></button>
                </div>
              </div>
            </template>
            <div v-if="!roots.length" class="empty" style="padding:32px"><Icon name="pin"/><div>No terms yet.</div></div>
          </div>
        </section>
      </div>

      <!-- single term editor drawer -->
      <UiDrawer v-if="edit" @close="edit=null">
        <div class="drawer-head">
          <span class="dash-row-ico"><Icon name="pin" :size="16"/></span>
          <div style="flex:1"><strong style="font-size:15px">{{ isNew ? 'New term' : 'Edit term' }}</strong><div class="t-meta">{{ sel.name }}</div></div>
          <button class="iconbtn" @click="edit=null"><Icon name="x"/></button>
        </div>
        <div class="drawer-body">
          <div class="field"><label class="field-label">Name <span class="field-req">*</span></label><input class="inp" v-model="edit.name" @input="onName" placeholder="e.g. Yoga"></div>
          <div class="field"><label class="field-label">Slug</label><div class="inp-group"><span class="affix">/{{ sel.slug }}/</span><input class="inp mono" v-model="edit.slug" @input="edit._slugTouched=true"></div></div>
          <div class="field" v-if="sel.hierarchical"><label class="field-label">Parent</label>
            <UiSelect v-model="edit.parent"><option :value="null">— None (top level) —</option><option v-for="p in parentOptions" :key="p.id" :value="p.id">{{ p.name }}</option></UiSelect>
          </div>
          <div class="field"><label class="field-label">Description</label><textarea class="ta" v-model="edit.desc" style="min-height:70px" placeholder="Optional. Shown on the term archive page."></textarea></div>
          <div class="md-sec-h" style="margin-top:6px"><Icon name="globe" :size="13"/> SEO</div>
          <div class="field"><label class="field-label">SEO title</label><input class="inp" v-model="edit.seoTitle" :placeholder="edit.name"></div>
          <div class="field" style="margin:0"><label class="field-label">Meta description</label><textarea class="ta" v-model="edit.seoDesc" style="min-height:60px"></textarea></div>
        </div>
        <div class="drawer-foot">
          <button v-if="!isNew" class="btn btn--danger" style="margin-right:auto" @click="delTerm(edit.id)"><Icon name="trash" :size="15"/> Delete</button>
          <button class="btn btn--ghost" @click="edit=null">Cancel</button>
          <button class="btn btn--accent" @click="saveTerm" :disabled="!edit.name.trim()"><Icon name="check" :size="15"/> {{ isNew ? 'Create term' : 'Save' }}</button>
        </div>
      </UiDrawer>

      <!-- taxonomy create / edit drawer -->
      <UiDrawer v-if="taxEdit" @close="taxEdit=null">
        <div class="drawer-head">
          <span class="dash-row-ico"><Icon name="pin" :size="16"/></span>
          <div style="flex:1"><strong style="font-size:15px">{{ taxIsNew ? 'New taxonomy' : 'Taxonomy settings' }}</strong><div class="t-meta">How content is grouped</div></div>
          <button class="iconbtn" @click="taxEdit=null"><Icon name="x"/></button>
        </div>
        <div class="drawer-body">
          <div class="field"><label class="field-label">Name <span class="field-req">*</span></label><input class="inp" v-model="taxEdit.name" @input="onTaxName" placeholder="e.g. Category"></div>
          <div class="field"><label class="field-label">API key</label><div class="inp-group"><span class="affix">slug</span><input class="inp mono" v-model="taxEdit.slug" @input="taxEdit._slugTouched=true"></div></div>
          <div class="field"><label class="field-label">Description</label><textarea class="ta" v-model="taxEdit.desc" style="min-height:60px"></textarea></div>
          <div class="opt-row"><div><strong>Hierarchical</strong><div class="field-hint">Terms can be nested (like categories). Off = flat tags.</div></div><UiSwitch v-model="taxEdit.hierarchical"/></div>
          <label class="field-label" style="margin-top:16px">Attach to post types</label>
          <div class="tax-pt-grid">
            <button v-for="pt in allPostTypes()" :key="pt.id" class="tax-pt" :class="{'is-on': taxEdit.postTypes.includes(pt.id)}" @click="togglePt(pt.id)">
              <span class="lib-ico"><Icon :name="pt.icon" :size="15"/></span>
              <span style="flex:1;text-align:left">{{ pt.name }}</span>
              <UiChk :modelValue="taxEdit.postTypes.includes(pt.id)"/>
            </button>
          </div>
        </div>
        <div class="drawer-foot">
          <button v-if="!taxIsNew" class="btn btn--danger" style="margin-right:auto" @click="delTax"><Icon name="trash" :size="15"/> Delete</button>
          <button class="btn btn--ghost" @click="taxEdit=null">Cancel</button>
          <button class="btn btn--accent" @click="saveTax" :disabled="!taxEdit.name.trim()"><Icon name="check" :size="15"/> {{ taxIsNew ? 'Create taxonomy' : 'Save' }}</button>
        </div>
      </UiDrawer>
    </div>`,
  };
})();
;
/* file: screen-forms.js */
/* ============================================================
   FORMS — list + builder (palette → canvas → settings).
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  const PALETTE = [
    { type:'text', label:'Text', icon:'type' },
    { type:'email', label:'Email', icon:'mail' },
    { type:'textarea', label:'Textarea', icon:'align' },
    { type:'select', label:'Select', icon:'list' },
    { type:'checkbox', label:'Checkbox', icon:'check' },
    { type:'radio', label:'Radio', icon:'radio' },
    { type:'number', label:'Number', icon:'hash' },
    { type:'date', label:'Date', icon:'calendar' },
    { type:'file', label:'File upload', icon:'upload' },
    { type:'heading', label:'Heading', icon:'text' },
  ];

  C.ScreenForms = {
    name: 'ScreenForms',
    data() {
      return {
        editing: null, sel: null, drag:null, dropAt:null, view:'build', entryOpen:null, nameToken:'{{name}}',
        fields: [],
        settings: { name:'Contact', slug:'contactform', submit:'Send message', success:"Thanks — we'll reply within a day.", notify:true, spam:true, store:true,
          confirm:false, confirmFromName:'Atypisch', confirmReplyTo:'hello@atypisch.nl', confirmSubject:'We received your message', confirmBody:"Hi {{name}},\n\nThanks for reaching out — we've received your message and will reply within one working day.\n\nWarm regards,\nThe Atypisch team" },
      };
    },
    computed: {
      forms() { return D().FORMS; }, palette() { return PALETTE; },
      submissions() { return D().SUBMISSIONS; },
      unread() { return this.submissions.filter(s => !s.read).length; },
    },
    methods: {
      preview(frm) { window.open((frm && frm.preview) || 'auth.html', '_blank'); },
      open(frm, view) {
        if (frm && frm.system) { this.preview(frm); return; }
        this.editing = frm;
        this.view = view || 'build';
        this.settings.name = frm.name; this.settings.slug = frm.slug;
        this.fields = [
          { id:D().uid(), type:'text', label:'Your name', required:true, ph:'Jane Doe', w:'half' },
          { id:D().uid(), type:'email', label:'Email', required:true, ph:'you@company.com', w:'half' },
          { id:D().uid(), type:'select', label:'Subject', required:false, ph:'', w:'full' },
          { id:D().uid(), type:'textarea', label:'Message', required:true, ph:'How can we help?', w:'full' },
        ];
      },
      meta(t){ return PALETTE.find(p=>p.type===t) || {icon:'type',label:t}; },
      add(t){ const m=this.meta(t); this.fields.push({ id:D().uid(), type:t, label:m.label, required:false, ph:'', w:'full' }); },
      del(i){ this.fields.splice(i,1); if(this.sel&&this.fields.indexOf(this.sel)<0) this.sel=null; },
      libStart(t,e){ this.drag={kind:'lib',type:t.type}; e.dataTransfer.effectAllowed='copy'; },
      rowStart(i,e){ this.drag={kind:'move',i}; e.dataTransfer.effectAllowed='move'; },
      over(i,e){ e.preventDefault(); const r=e.currentTarget.getBoundingClientRect(); this.dropAt=(e.clientY-r.top)>r.height/2?i+1:i; },
      drop(e){ e.preventDefault(); let at=this.dropAt==null?this.fields.length:this.dropAt; if(this.drag.kind==='lib'){ const m=this.meta(this.drag.type); this.fields.splice(at,0,{id:D().uid(),type:this.drag.type,label:m.label,required:false,ph:'',w:'full'}); } else { const it=this.fields[this.drag.i]; this.fields.splice(this.drag.i,1); if(this.drag.i<at)at-=1; this.fields.splice(at,0,it); } this.drag=null; this.dropAt=null; },
      markRead(s){ s.read = true; },
      exportCsv(){
        const rows = this.submissions;
        const cols = ['id','name','email','subject','message','date'];
        const esc = (v) => '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"';
        const csv = [cols.join(',')].concat(rows.map(r => cols.map(c => esc(r[c])).join(','))).join('\n');
        const blob = new Blob([csv], { type:'text/csv' });
        const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = this.settings.slug + '-submissions.csv';
        document.body.appendChild(a); a.click(); a.remove(); setTimeout(() => URL.revokeObjectURL(a.href), 1000);
      },
    },
    template: `
    <!-- LIST -->
    <div v-if="!editing" class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Forms</h1><p class="page-sub" style="margin-top:6px">Build forms, route submissions, and trigger Actions.</p></div>
        <button class="btn btn--accent" @click="open({name:'Untitled form',slug:'untitled',fields:0,submissions:0})"><Icon name="plus"/> New form</button>
      </div>
      <div class="card">
        <table class="tbl tbl--rows">
          <thead><tr><th>Form</th><th>API key</th><th>Fields</th><th>Submissions</th><th>Updated</th></tr></thead>
          <tbody>
            <tr v-for="frm in forms" :key="frm.id">
              <td>
                <div class="flex items-center gap-3">
                  <span class="dash-row-ico"><Icon :name="frm.system ? 'lock' : 'form'" :size="16"/></span>
                  <div>
                    <a class="ce-title" @click="open(frm)">{{ frm.name }}</a>
                    <span v-if="frm.system" class="badge badge--info" style="margin-left:8px"><Icon name="lock" :size="10"/> system</span>
                    <div class="t-meta" v-if="frm.desc">{{ frm.desc }}</div>
                    <div class="row-actions">
                      <button @click="open(frm)">Edit</button>
                      <template v-if="frm.system">
                        <button @click="preview(frm)"><Icon name="external" :size="12"/> Open screen</button>
                      </template>
                      <template v-else>
                        <button @click="open(frm,'entries')">Entries</button>
                        <button><Icon name="copy" :size="12"/> Duplicate</button>
                        <button class="is-danger">Trash</button>
                        <button><Icon name="eye" :size="12"/> View</button>
                      </template>
                    </div>
                  </div>
                </div>
              </td>
              <td><span class="t-key">{{ frm.slug }}</span></td>
              <td class="mono muted">{{ frm.fields }}</td>
              <td><span class="badge" v-if="!frm.system">{{ frm.submissions }} received</span><span class="t-meta" v-else>auth flow</span></td>
              <td class="muted">{{ frm.updated }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- BUILDER -->
    <div v-else class="fb">
      <div class="fb-top">
        <div class="fb-top-l">
          <button class="iconbtn" @click="editing=null"><Icon name="chevLeft"/></button>
          <span class="dash-row-ico"><Icon name="form" :size="16"/></span>
          <div><div class="t-eyebrow">Form</div><strong style="font-size:16px;font-family:var(--font-display)">{{ settings.name }}</strong></div>
        </div>
        <div class="fb-top-c">
          <div class="fb-vtabs">
            <button :class="{'is-on':view==='build'}" @click="view='build'"><Icon name="sliders" :size="15"/> Build</button>
            <button :class="{'is-on':view==='entries'}" @click="view='entries'"><Icon name="files" :size="15"/> Submissions <span class="tab-count">{{ submissions.length }}</span></button>
          </div>
        </div>
        <div class="fb-top-r">
          <button class="btn btn--ghost btn--sm" @click="preview({preview:'auth.html'})"><Icon name="eye" :size="15"/> Preview</button>
          <button class="btn"><Icon name="save" :size="15"/> Save form</button>
        </div>
      </div>
      <div class="fb-body" style="grid-template-columns: 248px 1fr 300px" v-if="view==='build'">
        <!-- palette -->
        <aside class="fb-lib">
          <div class="fb-lib-scroll">
            <div class="fb-lib-hint">Drag onto the form, or click to append.</div>
            <div class="fb-cat-items">
              <div v-for="t in palette" :key="t.type" class="fb-lib-item" draggable="true" @dragstart="libStart(t,$event)" @dragend="drag=null;dropAt=null" @click="add(t.type)">
                <Icon name="drag" :size="14" class="fb-lib-grip"/>
                <span class="lib-ico"><Icon :name="t.icon" :size="15"/></span>
                <span class="fb-lib-label">{{ t.label }}</span>
              </div>
            </div>
          </div>
        </aside>

        <!-- form canvas -->
        <section class="fb-canvas" @dragover.prevent @drop="drop">
          <div class="form-preview">
            <div class="form-prev-head"><h2 class="t-title t-h3" style="margin:0">{{ settings.name }}</h2><span class="t-key">/{{ settings.slug }}</span></div>
            <div class="form-grid">
              <div v-for="(f,i) in fields" :key="f.id" class="form-fieldwrap" :class="['w-'+f.w, {'is-sel': sel===f}]"
                   draggable="true" @dragstart="rowStart(i,$event)" @dragend="drag=null;dropAt=null" @dragover="over(i,$event)" @drop="drop" @click="sel=f">
                <div class="drop-line" v-if="dropAt===i"></div>
                <div class="form-field-top"><Icon name="drag" :size="14" class="drag-handle"/><label class="field-label" style="margin:0">{{ f.label }}<span class="field-req" v-if="f.required">*</span></label><div class="grow"></div><button class="iconbtn iconbtn--sm is-danger" @click.stop="del(i)"><Icon name="x" :size="13"/></button></div>
                <input v-if="['text','email','number','date'].includes(f.type)" class="inp" :placeholder="f.ph" disabled>
                <textarea v-else-if="f.type==='textarea'" class="ta" :placeholder="f.ph" disabled style="min-height:64px"></textarea>
                <UiSelect v-else-if="f.type==='select'" class="sel" disabled><option>Choose…</option></UiSelect>
                <div v-else-if="f.type==='checkbox'" class="flex items-center gap-2"><span class="chk"></span><span class="t-meta">Option</span></div>
                <div v-else-if="f.type==='radio'" class="flex items-center gap-2"><Icon name="radio" :size="16" class="muted"/><span class="t-meta">Option</span></div>
                <div v-else-if="f.type==='file'" class="form-file"><Icon name="upload" :size="15"/> Choose file…</div>
                <div v-else-if="f.type==='heading'" class="t-title t-h3">{{ f.label }}</div>
              </div>
              <div class="drop-line" v-if="dropAt===fields.length"></div>
            </div>
            <button class="btn btn--accent" style="margin-top:18px" disabled>{{ settings.submit }}</button>
          </div>
        </section>

        <!-- settings / field config -->
        <aside class="ed-rail">
          <template v-if="sel">
            <div class="rail-card">
              <div class="rail-head">Field · {{ meta(sel.type).label }}</div>
              <div class="rail-body">
                <div class="field"><label class="field-label">Label</label><input class="inp" v-model="sel.label"></div>
                <div class="field"><label class="field-label">Placeholder</label><input class="inp" v-model="sel.ph"></div>
                <div class="field"><label class="field-label">Width</label><div class="seg"><button :class="{'is-on':sel.w==='full'}" @click="sel.w='full'">Full</button><button :class="{'is-on':sel.w==='half'}" @click="sel.w='half'">Half</button></div></div>
                <div class="opt-row" style="border:0;padding:8px 0 0"><div><strong>Required</strong></div><UiSwitch v-model="sel.required"/></div>
              </div>
            </div>
          </template>
          <div class="rail-card">
            <div class="rail-head">Form settings</div>
            <div class="rail-body">
              <div class="field"><label class="field-label">Name</label><input class="inp" v-model="settings.name"></div>
              <div class="field"><label class="field-label">Submit label</label><input class="inp" v-model="settings.submit"></div>
              <div class="field" style="margin:0"><label class="field-label">Success message</label><textarea class="ta" v-model="settings.success" style="min-height:56px"></textarea></div>
            </div>
          </div>
          <div class="rail-card">
            <div class="rail-head">On submit</div>
            <div class="rail-body">
              <div class="opt-row" style="border:0;padding:6px 0"><div><strong>Store submission</strong></div><UiSwitch v-model="settings.store"/></div>
              <div class="opt-row" style="padding:10px 0"><div><strong>Email notification</strong><div class="field-hint">Notify the team of a new entry.</div></div><UiSwitch v-model="settings.notify"/></div>
              <div class="opt-row" style="padding:10px 0"><div><strong>Spam protection</strong></div><UiSwitch v-model="settings.spam"/></div>
              <button class="btn btn--ghost btn--sm" style="width:100%;margin-top:10px"><Icon name="zap" :size="14"/> Trigger an Action</button>
            </div>
          </div>
          <div class="rail-card">
            <div class="rail-head"><Icon name="mail" :size="14"/> Confirmation reply <span class="badge" style="margin-left:auto">optional</span></div>
            <div class="rail-body">
              <div class="opt-row" style="border:0;padding:2px 0 6px"><div><strong>Send confirmation email</strong><div class="field-hint">Auto-reply to whoever submitted the form. Off by default.</div></div><UiSwitch v-model="settings.confirm"/></div>
              <template v-if="settings.confirm">
                <div class="conf-note"><Icon name="info" :size="13"/> The reply goes to the form's <span class="t-key">email</span> field. Use <span class="t-key">{{ nameToken }}</span> to greet the sender.</div>
                <div class="field"><label class="field-label">From name</label><input class="inp" v-model="settings.confirmFromName"></div>
                <div class="field"><label class="field-label">Reply-to</label><input class="inp mono" v-model="settings.confirmReplyTo"></div>
                <div class="field"><label class="field-label">Subject</label><input class="inp" v-model="settings.confirmSubject"></div>
                <div class="field" style="margin:0"><label class="field-label">Message</label><textarea class="ta" v-model="settings.confirmBody" style="min-height:120px"></textarea></div>
              </template>
            </div>
          </div>
        </aside>
      </div>

      <!-- SUBMISSIONS -->
      <div class="fb-entries" v-else>
        <div class="entries-bar">
          <div><strong style="font-size:14px">{{ submissions.length }} submissions</strong><span class="t-meta" v-if="unread"> · {{ unread }} unread</span></div>
          <div class="grow"></div>
          <button class="btn btn--ghost btn--sm"><Icon name="filter" :size="14"/> Filter</button>
          <button class="btn btn--soft btn--sm" @click="exportCsv"><Icon name="files" :size="14"/> Export CSV</button>
        </div>
        <div class="card">
          <table class="tbl tbl--rows">
            <thead><tr><th style="width:170px">Name</th><th>Email</th><th>Subject</th><th style="width:160px">Received</th></tr></thead>
            <tbody>
              <tr v-for="s in submissions" :key="s.id" :class="{'is-unread-row':!s.read}" @click="entryOpen=s; markRead(s)" style="cursor:pointer">
                <td><span class="entry-dot" v-if="!s.read"></span><strong>{{ s.name }}</strong></td>
                <td><span class="t-key">{{ s.email }}</span></td>
                <td class="muted">{{ s.subject }}</td>
                <td class="muted mono">{{ s.date }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- single submission drawer -->
    <UiDrawer v-if="entryOpen" @close="entryOpen=null">
      <div class="drawer-head">
        <span class="dash-row-ico"><Icon name="mail" :size="16"/></span>
        <div style="flex:1"><strong style="font-size:15px">{{ entryOpen.subject }}</strong><div class="t-meta">{{ entryOpen.date }}</div></div>
        <button class="iconbtn" @click="entryOpen=null"><Icon name="x"/></button>
      </div>
      <div class="drawer-body">
        <div class="entry-field"><span class="field-label" style="margin:0">Name</span><span>{{ entryOpen.name }}</span></div>
        <div class="entry-field"><span class="field-label" style="margin:0">Email</span><a class="mode-help-link" :href="'mailto:'+entryOpen.email" style="display:inline-flex">{{ entryOpen.email }}</a></div>
        <div class="entry-field"><span class="field-label" style="margin:0">Subject</span><span>{{ entryOpen.subject }}</span></div>
        <div class="md-sec-h" style="margin-top:16px"><Icon name="align" :size="13"/> Message</div>
        <p style="font-size:13.5px;line-height:1.6;color:var(--ink-2);margin:0">{{ entryOpen.message }}</p>
      </div>
      <div class="drawer-foot">
        <button class="btn btn--danger" style="margin-right:auto"><Icon name="trash" :size="15"/> Delete</button>
        <a class="btn btn--ghost" :href="'mailto:'+entryOpen.email"><Icon name="mail" :size="15"/> Reply</a>
      </div>
    </UiDrawer>`,
  };
})();
;
/* file: screen-actions.js */
/* ============================================================
   ACTIONS — Directus-style automation flows.
   Trigger → operations chain, configurable per step.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  const TRIGGERS = [
    { id:'post_published', label:'Post published', icon:'file' },
    { id:'post_created', label:'Post created', icon:'plus' },
    { id:'form_submitted', label:'Form submitted', icon:'form' },
    { id:'user_registered', label:'User registered', icon:'user' },
    { id:'media_uploaded', label:'Media uploaded', icon:'image' },
    { id:'schedule', label:'On a schedule', icon:'clock' },
    { id:'webhook_in', label:'Incoming webhook', icon:'webhook' },
  ];
  const OPS = [
    { type:'condition', label:'Condition', icon:'branch', desc:'Branch on a value' },
    { type:'email', label:'Send email', icon:'mail', desc:'Notify a person or team' },
    { type:'webhook', label:'Call webhook', icon:'webhook', desc:'POST to an external URL' },
    { type:'create', label:'Create item', icon:'plus', desc:'Make a new post' },
    { type:'update', label:'Update item', icon:'pencil', desc:'Change fields or status' },
    { type:'tag', label:'Add tag', icon:'pin', desc:'Attach a taxonomy term' },
    { type:'transform', label:'Transform', icon:'code', desc:'Run a small script' },
    { type:'delay', label:'Wait', icon:'clock', desc:'Pause the flow' },
    { type:'hook', label:'Run hook', icon:'zap', desc:'Fire a PHP do_action' },
  ];

  C.ScreenActions = {
    name: 'ScreenActions',
    data() {
      return { editing:null, trigger:'post_published', steps:[], sel:null, addAt:null, picker:false };
    },
    computed: { actions() { return D().ACTIONS; }, triggers() { return TRIGGERS; }, ops() { return OPS; },
      triggerMeta() { return TRIGGERS.find(t=>t.id===this.trigger)||TRIGGERS[0]; } },
    methods: {
      open(a) {
        this.editing = a;
        this.steps = a.ops.map(o => ({ id:D().uid(), ...o }));
      },
      opMeta(t){ return OPS.find(o=>o.type===t)||{icon:'zap',label:t}; },
      openPicker(at){ this.addAt = at; this.picker = true; },
      addOp(t){ const m=this.opMeta(t); const step={ id:D().uid(), type:t, icon:m.icon, label:m.label }; const at=this.addAt==null?this.steps.length:this.addAt; this.steps.splice(at,0,step); this.picker=false; this.sel=step; },
      del(i){ this.steps.splice(i,1); },
    },
    template: `
    <!-- LIST -->
    <div v-if="!editing" class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Actions</h1><p class="page-sub" style="margin-top:6px">Automation flows: a trigger, then a chain of operations. The relational backbone of Directus, made readable.</p></div>
        <button class="btn btn--accent" @click="open({name:'Untitled flow',trigger:'Post published',enabled:true,runs:0,ops:[]})"><Icon name="plus"/> New action</button>
      </div>
      <div class="act-list">
        <div v-for="a in actions" :key="a.id" class="act-card" @click="open(a)">
          <div class="act-card-l">
            <span class="act-trig"><Icon name="zap" :size="16"/></span>
            <div>
              <strong>{{ a.name }}</strong>
              <div class="t-meta">when <strong style="color:var(--ink-2)">{{ a.trigger }}</strong></div>
            </div>
          </div>
          <div class="act-chain">
            <span v-for="(o,i) in a.ops" :key="i" class="act-chip" :title="o.label"><Icon :name="o.icon" :size="13"/></span>
          </div>
          <div class="act-card-r">
            <span class="t-meta mono">{{ a.runs }} runs</span>
            <span class="badge" :class="a.enabled?'badge--ok':''">{{ a.enabled?'on':'off' }}</span>
            <UiSwitch :modelValue="a.enabled"/>
          </div>
        </div>
      </div>
    </div>

    <!-- FLOW EDITOR -->
    <div v-else class="fb">
      <div class="fb-top">
        <div class="fb-top-l">
          <button class="iconbtn" @click="editing=null"><Icon name="chevLeft"/></button>
          <span class="act-trig" style="width:30px;height:30px"><Icon name="zap" :size="15"/></span>
          <div><div class="t-eyebrow">Action flow</div><strong style="font-size:16px;font-family:var(--font-display)">{{ editing.name }}</strong></div>
        </div>
        <div class="fb-top-r">
          <span class="flex items-center gap-2"><span class="t-meta">Enabled</span><UiSwitch :modelValue="editing.enabled"/></span>
          <button class="btn btn--ghost btn--sm"><Icon name="play" :size="15"/> Test run</button>
          <button class="btn"><Icon name="save" :size="15"/> Save flow</button>
        </div>
      </div>
      <div class="fb-body" style="grid-template-columns: 1fr 320px">
        <!-- flow canvas -->
        <section class="flow-canvas">
          <div class="flow">
            <!-- trigger -->
            <div class="flow-node flow-node--trigger" @click="sel={trigger:true}">
              <div class="flow-node-ico"><Icon :name="triggerMeta.icon" :size="17"/></div>
              <div class="flow-node-main"><div class="t-eyebrow" style="color:var(--accent-deep)">Trigger</div><strong>{{ triggerMeta.label }}</strong></div>
              <Icon name="settings" :size="16" class="muted"/>
            </div>

            <template v-for="(s,i) in steps" :key="s.id">
              <div class="flow-conn"><button class="flow-add" @click="openPicker(i)"><Icon name="plus" :size="13"/></button></div>
              <div class="flow-node" :class="{'is-sel': sel===s, 'flow-node--cond': s.type==='condition'}" @click="sel=s">
                <div class="flow-node-ico"><Icon :name="s.icon" :size="17"/></div>
                <div class="flow-node-main"><div class="t-eyebrow">{{ opMeta(s.type).label }}</div><strong>{{ s.label }}</strong></div>
                <div class="flow-node-actions" @click.stop><button class="iconbtn iconbtn--sm is-danger" @click="del(i)"><Icon name="trash" :size="14"/></button></div>
              </div>
            </template>

            <div class="flow-conn"><button class="flow-add" @click="openPicker(steps.length)"><Icon name="plus" :size="13"/></button></div>
            <button class="flow-end" @click="openPicker(steps.length)"><Icon name="plus" :size="15"/> Add operation</button>
          </div>
        </section>

        <!-- config rail -->
        <aside class="ed-rail">
          <div class="rail-card" v-if="sel && sel.trigger">
            <div class="rail-head">Trigger</div>
            <div class="rail-body">
              <div class="field"><label class="field-label">Event</label><UiSelect v-model="trigger"><option v-for="t in triggers" :key="t.id" :value="t.id">{{ t.label }}</option></UiSelect></div>
              <div class="field" style="margin:0"><label class="field-label">Scope</label><UiSelect><option>Any post type</option><option>Projects only</option><option>Products only</option></UiSelect></div>
            </div>
          </div>
          <div class="rail-card" v-else-if="sel">
            <div class="rail-head">{{ opMeta(sel.type).label }}</div>
            <div class="rail-body">
              <div class="field"><label class="field-label">Label</label><input class="inp" v-model="sel.label"></div>
              <template v-if="sel.type==='email'">
                <div class="field"><label class="field-label">To</label><input class="inp mono" value="team@atypisch.nl"></div>
                <div class="field" style="margin:0"><label class="field-label">Template</label><UiSelect><option>Publish notice</option><option>Custom…</option></UiSelect></div>
              </template>
              <template v-else-if="sel.type==='webhook'">
                <div class="field"><label class="field-label">Method</label><div class="seg"><button class="is-on">POST</button><button>GET</button><button>PUT</button></div></div>
                <div class="field" style="margin:0"><label class="field-label">URL</label><input class="inp mono" placeholder="https://…"></div>
              </template>
              <template v-else-if="sel.type==='condition'">
                <div class="field" style="margin:0"><label class="field-label">Continue only if</label>
                  <div class="cond-row" style="margin-top:8px"><UiSelect><option>Post type</option><option>Status</option></UiSelect><UiSelect style="max-width:80px"><option>is</option></UiSelect><input class="inp" value="Project"></div>
                </div>
              </template>
              <template v-else>
                <div class="field" style="margin:0"><label class="field-label">Configuration</label><textarea class="ta mono" placeholder="key: value" style="min-height:80px"></textarea></div>
              </template>
            </div>
          </div>
          <div class="rail-card rail-card--quiet" v-else>
            <div class="rail-body center" style="color:var(--ink-3);padding:28px 16px"><Icon name="branch" :size="26" style="color:var(--ink-4);margin-bottom:8px"/><div style="font-size:13px">Select the trigger or any step to configure it.</div></div>
          </div>
          <div class="rail-card rail-card--quiet">
            <div class="rail-head"><Icon name="info" :size="14"/> Data available</div>
            <div class="rail-body" style="font-size:12.5px;color:var(--ink-2);line-height:1.7">
              Each step can read <span class="t-key" v-pre>{{ trigger.post }}</span>, <span class="t-key" v-pre>{{ steps.* }}</span> and site settings — same token model as templates.
            </div>
          </div>
        </aside>
      </div>

      <!-- op picker -->
      <UiModal v-if="picker" @close="picker=false">
        <div class="drawer-head"><Icon name="plus" :size="18"/><strong style="font-size:15px;flex:1">Add operation</strong><button class="iconbtn" @click="picker=false"><Icon name="x"/></button></div>
        <div class="drawer-body">
          <div class="op-grid">
            <button v-for="o in ops" :key="o.type" class="op-pick" @click="addOp(o.type)">
              <span class="op-ico"><Icon :name="o.icon" :size="17"/></span>
              <div><strong>{{ o.label }}</strong><div class="t-meta">{{ o.desc }}</div></div>
            </button>
          </div>
        </div>
      </UiModal>
    </div>`,
  };
})();
;
/* file: screen-modules.js */
/* ============================================================
   MODULES — extend the CMS (WordPress-plugin idea, compact).
   Installed list with a single active toggle + tidy menu, and
   a Browse tab to add new modules. No cluttered button rows.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenModules = {
    name: 'ScreenModules',
    data() { return { tab: 'installed', q: '', bq: '', preview: null }; },
    computed: {
      modules() { const q = this.q.trim().toLowerCase(); return D().MODULES.filter(m => !q || m.name.toLowerCase().includes(q) || m.desc.toLowerCase().includes(q)); },
      available() { const q = this.bq.trim().toLowerCase(); return D().MODULES_AVAILABLE.filter(m => !q || m.name.toLowerCase().includes(q) || m.desc.toLowerCase().includes(q) || m.author.toLowerCase().includes(q)); },
      activeCount() { return D().MODULES.filter(m => m.active).length; },
      updatesAvailable() { return D().MODULES.filter(m => m.update).length; },
    },
    methods: {
      remove(m) { const a = D().MODULES; const i = a.indexOf(m); if (i >= 0) a.splice(i, 1); },
      updateMod(m) { m.version = m.update; m.update = null; m.updateNotes = null; },
      updateAll() { D().MODULES.filter(m => m.update).forEach(m => this.updateMod(m)); },
      menuFor(m) {
        const items = [
          { label:'Settings', icon:'settings' },
          { label:'View details', icon:'arrowUpRight', onClick: () => window.open(m.url, '_blank') },
          { label: m.autoUpdate ? 'Disable auto-updates' : 'Enable auto-updates', icon:'refresh', onClick: () => { m.autoUpdate = !m.autoUpdate; } },
          { sep:true },
          { label:'Remove', icon:'trash', danger:true, onClick: () => this.remove(m) },
        ];
        if (m.update) items.splice(1, 0, { label:'What\u2019s new', icon:'info', onClick: () => { this.preview = m; } });
        return items;
      },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <h1 class="t-title t-h1">Modules</h1>
          <p class="page-sub" style="margin-top:6px">Extend Modular with optional features. {{ modules.length }} installed · {{ activeCount }} active.</p>
        </div>
        <button class="btn btn--accent" @click="tab='browse'"><Icon name="plus"/> Add module</button>
      </div>

      <div class="tabs" style="margin-bottom:18px">
        <button :class="{'is-on':tab==='installed'}" @click="tab='installed'"><Icon name="layers" :size="15"/> Installed <span class="tab-count">{{ D().MODULES.length }}</span></button>
        <button :class="{'is-on':tab==='browse'}" @click="tab='browse'"><Icon name="search" :size="15"/> Browse</button>
      </div>

      <!-- INSTALLED -->
      <template v-if="tab==='installed'">
        <div class="mod-updbanner" v-if="updatesAvailable">
          <span class="upd-badge is-due" style="width:34px;height:34px"><Icon name="refresh" :size="16"/></span>
          <span><strong>{{ updatesAvailable }} update{{ updatesAvailable>1?'s':'' }} available</strong><span class="t-meta"> · keep your modules current.</span></span>
          <div class="grow"></div>
          <button class="btn btn--accent btn--sm" @click="updateAll"><Icon name="upload" :size="14"/> Update all</button>
        </div>
        <div class="list-toolbar" style="border:1px solid var(--line);border-bottom:0;border-radius:var(--r-md) var(--r-md) 0 0;background:var(--surface)">
          <div class="topbar-search" style="max-width:280px;flex:0 1 280px;cursor:text"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="q" placeholder="Search modules…" style="color:var(--ink)"></div>
        </div>
        <div class="mod-list card" style="border-radius:0 0 var(--r-md) var(--r-md)">
          <div class="mod-row" v-for="m in modules" :key="m.id" :class="{'is-active': m.active}">
            <span class="mod-ico" :class="{'is-on': m.active}"><Icon :name="m.icon" :size="18"/></span>
            <div class="mod-main">
              <div class="mod-name">
                <strong>{{ m.name }}</strong>
                <span class="badge" :class="m.active?'badge--ok':''"><span class="badge--dot" v-if="m.active"></span>{{ m.active ? 'active' : 'inactive' }}</span>
                <span class="mod-autoupd" v-if="m.autoUpdate" title="Auto-updates on"><Icon name="refresh" :size="11"/> auto</span>
              </div>
              <div class="t-meta mod-desc">{{ m.desc }}</div>
              <div class="mod-foot"><span class="t-key">v{{ m.version }} · by {{ m.author }}</span><a class="mod-link" :href="m.url" target="_blank" rel="noopener">Details <Icon name="arrowUpRight" :size="11"/></a></div>
            </div>
            <div class="mod-actions">
              <div class="mod-upd" v-if="m.update">
                <button class="mod-whatsnew" @click="preview=m" title="What’s new">v{{ m.update }} <Icon name="info" :size="12"/></button>
                <button class="btn btn--soft btn--sm" @click="updateMod(m)"><Icon name="upload" :size="13"/> Update</button>
              </div>
              <label class="mod-toggle"><span class="t-meta">{{ m.active ? 'On' : 'Off' }}</span><UiSwitch :modelValue="m.active" @update:modelValue="v=>{ m.active=v; window.__mdlr.store.toggleModule(m); }"/></label>
              <UiMenu :items="menuFor(m)"><button class="iconbtn"><Icon name="drag" :size="15"/></button></UiMenu>
            </div>
          </div>
          <div v-if="!modules.length" class="empty" style="padding:40px"><Icon name="layers"/><div>No modules match “{{ q }}”.</div></div>
        </div>
      </template>

      <!-- BROWSE -->
      <template v-else>
        <div class="list-toolbar" style="border:1px solid var(--line);border-radius:var(--r-md);background:var(--surface);margin-bottom:14px">
          <div class="topbar-search" style="max-width:320px;flex:0 1 320px;cursor:text"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="bq" placeholder="Search the module directory…" style="color:var(--ink)"></div>
          <span class="t-meta">{{ available.length }} module{{ available.length===1?'':'s' }}</span>
          <div class="grow"></div>
          <button class="btn btn--ghost btn--sm"><Icon name="upload" :size="14"/> Upload .zip</button>
        </div>
        <div class="mod-browse">
          <div class="mod-card" v-for="m in available" :key="m.id">
            <div class="mod-card-top">
              <span class="mod-ico"><Icon :name="m.icon" :size="20"/></span>
              <UiMenu :items="[{label:'View details',icon:'info'}]"><button class="iconbtn iconbtn--sm"><Icon name="drag" :size="15"/></button></UiMenu>
            </div>
            <strong class="mod-card-name">{{ m.name }}</strong>
            <p class="mod-card-desc">{{ m.desc }}</p>
            <div class="mod-card-foot">
              <span class="t-meta">by {{ m.author }} · {{ m.installs }} installs</span>
              <button class="btn btn--soft btn--sm"><Icon name="upload" :size="14"/> Install</button>
            </div>
          </div>
          <div v-if="!available.length" class="empty card" style="padding:40px;grid-column:1/-1"><Icon name="search"/><div>No modules match “{{ bq }}”.</div></div>
        </div>
      </template>

      <!-- update preview modal -->
      <UiModal v-if="preview" @close="preview=null">
        <div class="drawer-head">
          <span class="mod-ico is-on"><Icon :name="preview.icon" :size="18"/></span>
          <div style="flex:1"><strong style="font-size:15px">{{ preview.name }}</strong><div class="t-meta">Update available</div></div>
          <button class="iconbtn" @click="preview=null"><Icon name="x"/></button>
        </div>
        <div class="drawer-body">
          <div class="upd-ver" style="margin-bottom:14px"><span class="t-key">v{{ preview.version }}</span><Icon name="arrowRight" :size="13" class="muted"/><span class="t-key" style="color:var(--accent-deep)">v{{ preview.update }}</span></div>
          <label class="field-label">What’s new</label>
          <p style="font-size:13.5px;color:var(--ink-2);line-height:1.6;margin:0 0 16px">{{ preview.updateNotes }}</p>
          <a class="mode-help-link" :href="preview.url" target="_blank" rel="noopener">Full changelog &amp; details <Icon name="arrowUpRight" :size="13"/></a>
        </div>
        <div class="drawer-foot">
          <button class="btn btn--ghost" @click="preview=null">Close</button>
          <button class="btn btn--accent" @click="updateMod(preview); preview=null"><Icon name="upload" :size="15"/> Update now</button>
        </div>
      </UiModal>
    </div>`,
  };
  C.ScreenModules.methods.D = function () { return window.__mdlr.data; };
})();
;
/* file: screen-roles.js */
/* ============================================================
   ROLES & CAPABILITIES — submenu of Users.
   Pick a role on the left; toggle exactly what it can see & do
   on the right, grouped by area. Simple but complete.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenRoles = {
    name: 'ScreenRoles',
    data() { return { selId: 'editor', showNew: false, draft: { name:'', desc:'' } }; },
    computed: {
      roles() { return D().ROLES; },
      groups() { return D().CAP_GROUPS; },
      allCaps() { return D().ALL_CAPS; },
      sel() { return this.roles.find(r => r.id === this.selId) || this.roles[0]; },
      isAdmin() { return this.sel.caps.includes('*'); },
      grantedCount() { return this.isAdmin ? this.allCaps.length : this.sel.caps.filter(c => this.allCaps.includes(c)).length; },
    },
    methods: {
      has(cap) { return this.isAdmin || this.sel.caps.includes(cap); },
      persist() {
        const store = window.__mdlr.store; if (!store) return;
        const role = this.sel;
        clearTimeout(this._t);
        this._t = setTimeout(() => store.saveRoleCaps(role), 400);
      },
      toggle(cap) {
        if (this.isAdmin || this.sel.builtinLock) return;
        const i = this.sel.caps.indexOf(cap);
        if (i >= 0) this.sel.caps.splice(i, 1); else this.sel.caps.push(cap);
        this.persist();
      },
      groupAllOn(g) { return g.caps.every(c => this.has(c.id)); },
      toggleGroup(g) {
        if (this.isAdmin) return;
        const on = this.groupAllOn(g);
        g.caps.forEach(c => { const i = this.sel.caps.indexOf(c.id); if (on) { if (i>=0) this.sel.caps.splice(i,1); } else if (i<0) this.sel.caps.push(c.id); });
        this.persist();
      },
      async addRole() {
        const name = this.draft.name.trim(); if (!name) return;
        const store = window.__mdlr.store;
        if (store) { try { const saved = await store.createRole({ name, desc: this.draft.desc }); if (saved && saved.name) this.selId = saved.name; } catch (e) {} }
        else { const id = name.toLowerCase().replace(/[^a-z0-9]+/g,'_'); this.roles.push({ id, name, desc: this.draft.desc, users:0, builtin:false, caps:[] }); this.selId = id; }
        this.showNew = false; this.draft = { name:'', desc:'' };
      },
      async del(r) {
        if (r.builtin) return;
        const store = window.__mdlr.store;
        if (store) { try { await store.deleteRole(r); } catch (e) {} }
        else { const i = this.roles.indexOf(r); this.roles.splice(i,1); }
        this.selId = (this.roles[0] || {}).id;
      },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Roles &amp; capabilities</h1><p class="page-sub" style="margin-top:6px">Define what each role can see and do. Capabilities are grouped by area — toggle them on or off.</p></div>
        <button class="btn btn--accent" @click="showNew=true"><Icon name="plus"/> New role</button>
      </div>

      <div class="roles-layout">
        <!-- role list -->
        <aside class="roles-list">
          <button v-for="r in roles" :key="r.id" class="role-item" :class="{'is-on': selId===r.id}" @click="selId=r.id">
            <span class="role-item-main">
              <strong>{{ r.name }}</strong>
              <span class="t-meta">{{ r.users }} {{ r.users===1?'user':'users' }}</span>
            </span>
            <span class="badge" v-if="r.caps.includes('*')">all</span>
            <span class="badge" v-else>{{ r.caps.filter(c=>allCaps.includes(c)).length }}</span>
          </button>
        </aside>

        <!-- role detail -->
        <section class="role-detail">
          <div class="card card-pad">
            <div class="flex items-center justify-between gap-3" style="flex-wrap:wrap">
              <div style="flex:1;min-width:220px">
                <input class="role-name" v-model="sel.name" :disabled="sel.builtin">
                <input class="inp" v-model="sel.desc" style="margin-top:8px" placeholder="Describe this role">
              </div>
              <div class="role-meta">
                <div class="role-meta-stat"><strong>{{ grantedCount }}</strong><span class="t-meta">of {{ allCaps.length }} capabilities</span></div>
                <button class="btn btn--danger btn--sm" v-if="!sel.builtin" @click="del(sel)"><Icon name="trash" :size="14"/> Delete role</button>
                <span class="badge badge--info" v-else><Icon name="lock" :size="11"/> built-in</span>
              </div>
            </div>
          </div>

          <div class="role-note" v-if="isAdmin"><Icon name="info" :size="15"/> Administrators always have every capability — including ones added by future modules. This can’t be narrowed.</div>

          <div class="caps">
            <div class="caps-group" v-for="g in groups" :key="g.group">
              <div class="caps-group-head">
                <span class="caps-group-ico"><Icon :name="g.icon" :size="15"/></span>
                <strong>{{ g.group }}</strong>
                <span class="t-meta">{{ g.caps.filter(c=>has(c.id)).length }}/{{ g.caps.length }}</span>
                <div class="grow"></div>
                <label class="caps-alltoggle" :class="{'is-dim': isAdmin}"><span class="t-meta">All</span><UiSwitch :modelValue="groupAllOn(g)" @update:modelValue="toggleGroup(g)"/></label>
              </div>
              <div class="caps-rows">
                <button v-for="c in g.caps" :key="c.id" class="cap-row" :class="{'is-on': has(c.id), 'is-locked': isAdmin}" @click="toggle(c.id)">
                  <span class="cap-check" :class="{'is-on': has(c.id)}"><Icon v-if="has(c.id)" name="check" :size="12"/></span>
                  <span class="cap-main"><strong>{{ c.label }}</strong><span class="t-meta">{{ c.desc }}</span></span>
                  <span class="cap-state">{{ has(c.id) ? 'Allowed' : 'Denied' }}</span>
                </button>
              </div>
            </div>
          </div>
        </section>
      </div>

      <UiModal v-if="showNew" @close="showNew=false">
        <div class="drawer-head"><Icon name="lock" :size="18"/><strong style="font-size:15px;flex:1">New role</strong><button class="iconbtn" @click="showNew=false"><Icon name="x"/></button></div>
        <div class="drawer-body">
          <div class="field"><label class="field-label">Name <span class="field-req">*</span></label><input class="inp" v-model="draft.name" placeholder="e.g. Translator"></div>
          <div class="field" style="margin:0"><label class="field-label">Description</label><input class="inp" v-model="draft.desc" placeholder="What this role is for"></div>
        </div>
        <div class="drawer-foot"><button class="btn btn--ghost" @click="showNew=false">Cancel</button><button class="btn btn--accent" @click="addRole" :disabled="!draft.name.trim()"><Icon name="plus" :size="15"/> Create role</button></div>
      </UiModal>
    </div>`,
  };
})();
;
/* file: screen-profile.js */
/* ============================================================
   PROFILE — the current user's own account.
   View + inline edit. Account info, security, preferences.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenProfile = {
    name: 'ScreenProfile',
    data() {
      const p = D().PROFILE;
      return { editing: false, draft: null, p };
    },
    created() {
      const u = (window.__MDLR && window.__MDLR.user) || {};
      if (u.name) this.p.name = u.name;
      if (u.email) this.p.email = u.email;
      if (u.role) this.p.role = u.role.charAt(0).toUpperCase() + u.role.slice(1);
      // Pull the real account from the API so username/initials/bio/dates aren't mock.
      const api = window.__mdlr && window.__mdlr.api;
      if (!api) return;
      api.get('/me').then(me => {
        if (!me || me.error) return;
        const full = [me.first_name, me.last_name].filter(Boolean).join(' ').trim();
        if (me.username) this.p.username = me.username;
        if (full) this.p.name = full; else if (me.username) this.p.name = me.username;
        if (me.email) this.p.email = me.email;
        if (me.role) this.p.role = me.role.charAt(0).toUpperCase() + me.role.slice(1);
        if (me.status) this.p.status = me.status.charAt(0).toUpperCase() + me.status.slice(1);
        if ('bio' in me) this.p.bio = me.bio || '';
        if (me.created_at) this.p.memberSince = String(me.created_at).slice(0, 10);
        if (me.last_login) this.p.lastActive = String(me.last_login).slice(0, 10);
        const src = (full || me.username || me.email || '').trim();
        if (src) this.p.initials = src.split(/\s+/).map(s => s[0]).join('').slice(0, 2).toUpperCase();
      }).catch(() => {});
    },
    methods: {
      edit() { this.draft = JSON.parse(JSON.stringify(this.p)); this.editing = true; },
      save() { Object.assign(this.p, this.draft); this.editing = false; },
      cancel() { this.editing = false; },
      logout() { window.location.href = ((window.__MDLR && window.__MDLR.baseUrl) || '') + '/admin/auth/logout.php'; },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px">Your account</div>
          <h1 class="t-title t-h1">My profile</h1>
          <p class="page-sub" style="margin-top:6px">Your account details, security and editor preferences.</p>
        </div>
        <div class="flex gap-2" v-if="!editing">
          <button class="btn btn--ghost" @click="logout"><Icon name="logout" :size="15"/> Log out</button>
          <button class="btn btn--accent" @click="edit"><Icon name="pencil" :size="15"/> Edit profile</button>
        </div>
        <div class="flex gap-2" v-else>
          <button class="btn btn--ghost" @click="cancel">Cancel</button>
          <button class="btn" @click="save"><Icon name="check" :size="15"/> Save changes</button>
        </div>
      </div>

      <!-- identity banner -->
      <div class="card card-pad prof-banner">
        <span class="prof-avatar">{{ p.initials }}</span>
        <div class="prof-banner-main">
          <strong class="t-title t-h2" style="display:block">{{ p.name }}</strong>
          <div class="flex items-center gap-2" style="margin-top:6px">
            <span class="t-key">{{ p.email }}</span>
            <span class="badge badge--info">{{ p.role }}</span>
            <span class="badge badge--ok"><span class="badge--dot"></span>{{ p.status }}</span>
          </div>
        </div>
        <button class="btn btn--soft btn--sm" v-if="editing"><Icon name="image" :size="14"/> Change avatar</button>
        <div class="prof-banner-meta" v-else>
          <div><span class="t-meta">Member since</span><strong>{{ p.memberSince }}</strong></div>
          <div><span class="t-meta">Last active</span><strong>{{ p.lastActive }}</strong></div>
        </div>
      </div>

      <div class="prof-grid">
        <!-- Account information -->
        <section class="card card-pad">
          <h2 class="t-title t-h3" style="margin:0 0 16px">Account information</h2>
          <div class="field"><label class="field-label">Username</label>
            <input v-if="editing" class="inp" v-model="draft.username">
            <div v-else class="prof-readonly">{{ p.username }}</div>
          </div>
          <div class="field"><label class="field-label">Full name</label>
            <input v-if="editing" class="inp" v-model="draft.name">
            <div v-else class="prof-readonly">{{ p.name }}</div>
          </div>
          <div class="field"><label class="field-label">Email</label>
            <input v-if="editing" class="inp" v-model="draft.email">
            <div v-else class="prof-readonly">{{ p.email }}</div>
          </div>
          <div class="field" style="margin:0"><label class="field-label">Bio</label>
            <textarea v-if="editing" class="ta" v-model="draft.bio" style="min-height:72px"></textarea>
            <div v-else class="prof-readonly" style="line-height:1.55">{{ p.bio }}</div>
          </div>
        </section>

        <!-- Account details -->
        <section class="card card-pad">
          <h2 class="t-title t-h3" style="margin:0 0 16px">Account details</h2>
          <div class="prof-detail"><span class="field-label" style="margin:0">Role</span><span class="badge badge--info">{{ p.role }}</span></div>
          <div class="prof-detail"><span class="field-label" style="margin:0">Status</span><span class="badge badge--ok"><span class="badge--dot"></span>{{ p.status }}</span></div>
          <div class="prof-detail"><span class="field-label" style="margin:0">Member since</span><span>{{ p.memberSince }}</span></div>
          <div class="prof-detail">
            <span class="field-label" style="margin:0">Language</span>
            <UiSelect v-if="editing" class="sel" style="width:auto" v-model="draft.language"><option>English</option><option>Nederlands</option></UiSelect>
            <span v-else>{{ p.language }}</span>
          </div>
          <div class="prof-detail" style="border:0">
            <span class="field-label" style="margin:0">Timezone</span>
            <UiSelect v-if="editing" class="sel" style="width:auto" v-model="draft.timezone"><option>Europe/Amsterdam</option><option>UTC</option><option>Europe/London</option></UiSelect>
            <span v-else>{{ p.timezone }}</span>
          </div>
        </section>

        <!-- Security -->
        <section class="card card-pad">
          <h2 class="t-title t-h3" style="margin:0 0 16px">Security</h2>
          <div class="opt-row" style="border:0;padding-top:0"><div><strong>Password</strong><div class="field-hint">Last changed 2 months ago.</div></div><button class="btn btn--ghost btn--sm"><Icon name="lock" :size="14"/> Change</button></div>
          <div class="opt-row"><div><strong>Two-factor authentication</strong><div class="field-hint">Authenticator app.</div></div><UiSwitch :modelValue="editing ? draft.twoFactor : p.twoFactor" @update:modelValue="v=>{ (editing?draft:p).twoFactor=v }"/></div>
          <div class="opt-row" style="padding-bottom:0"><div><strong>Active sessions</strong><div class="field-hint">2 devices signed in.</div></div><button class="btn btn--ghost btn--sm">Manage</button></div>
        </section>

        <!-- Danger / sign out -->
        <section class="card card-pad">
          <h2 class="t-title t-h3" style="margin:0 0 16px">Session</h2>
          <p class="page-sub" style="margin-bottom:14px">Signed in as <strong style="color:var(--ink-2)">{{ p.username }}</strong> on this device.</p>
          <button class="btn btn--ghost" style="width:100%" onclick="window.open('auth.html','_blank')"><Icon name="logout" :size="15"/> Log out everywhere</button>
        </section>
      </div>
    </div>`,
  };
})();
;
/* file: screen-permalinks.js */
/* ============================================================
   PERMALINKS — per-post-type URL structures, tokens, live
   preview, and the real .htaccess on disk.
   Rendered inside Settings -> Permalinks.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.PermalinksPanel = {
    name: 'PermalinksPanel',
    data() {
      return {
        base: '',
        choice: {},
        custom: {},
        opts: { trailingSlash:false, lowercase:true, dropStopwords:false },
        htaccessPath: '',
        htaccessContent: '',
        htaccessWritable: true,
        htaccessExists: false,
        loading: true,
        saving: false,
        saved: false,
        copied: false,
        err: '',
        warn: '',
      };
    },
    created() {
      D().POST_TYPES.forEach(pt => {
        this.choice[pt.id] = 'plain';
        this.custom[pt.id] = '/{slug}';
      });
      this.load();
    },
    computed: {
      pts() { return D().POST_TYPES; },
      permalinkRules() {
        const lines = ['# BEGIN Modular Permalinks'];
        const rules = [];
        this.pts.forEach(pt => {
          const rule = this.rewriteRule(pt, this.pattern(pt));
          if (rule) rules.push(rule);
        });
        if (rules.length) lines.push(...rules);
        else lines.push('# Plain /{slug} types are handled by the front controller below.');
        lines.push('# END Modular Permalinks');
        return lines.join('\n');
      },
    },
    methods: {
      taxFor(pt) { return (pt.taxonomies || []).map(id => D().TAXONOMIES.find(t => t.id === id)).filter(Boolean); },
      options(pt) {
        const o = [
          { key:'plain',  pattern:'/{slug}', label:'plain slug' },
          { key:'prefix', pattern:'/'+pt.slug+'/{slug}', label:'post type prefix' },
          { key:'year',   pattern:'/{year}/{slug}', label:'year prefix' },
        ];
        const tax = this.taxFor(pt);
        if (tax.length) o.push({ key:'taxonomy', pattern:'/{'+tax[0].slug+'}/{slug}', label:tax[0].name.toLowerCase()+' + slug' });
        o.push({ key:'custom', pattern:null, label:'custom' });
        return o;
      },
      pattern(pt) {
        if (this.choice[pt.id] === 'custom') return this.custom[pt.id] || '/{slug}';
        const o = this.options(pt).find(x => x.key === this.choice[pt.id]);
        return o ? o.pattern : '/{slug}';
      },
      applyStructures(structures) {
        const map = structures || {};
        this.pts.forEach(pt => {
          const stored = map[pt.slug] || map[pt.id] || '/{slug}';
          const tax = this.options(pt).find(x => x.key === 'taxonomy');
          const prefixPat = '/' + pt.slug + '/{slug}';
          if (stored === '/{slug}') this.choice[pt.id] = 'plain';
          else if (stored === '/{post_type}/{slug}' || stored === prefixPat) this.choice[pt.id] = 'prefix';
          else if (stored === '/{year}/{slug}') this.choice[pt.id] = 'year';
          else if (stored === '/{category}/{slug}' || (tax && stored === tax.pattern)) this.choice[pt.id] = 'taxonomy';
          else {
            this.choice[pt.id] = 'custom';
            this.custom[pt.id] = stored;
          }
        });
      },
      collectStructures() {
        const out = {};
        this.pts.forEach(pt => { out[pt.slug] = this.pattern(pt); });
        return out;
      },
      rewriteRule(pt, structure) {
        let raw = String(structure || '').replace(/^\/+|\/+$/g, '');
        raw = raw.split('{post_type}').join(pt.slug);
        if (!raw || raw === '{slug}') return null;
        const captures = [];
        const parts = raw.split(/(\{[a-z_]+\})/);
        let pattern = '';
        parts.forEach(part => {
          if (!part) return;
          if (part === '{slug}') { captures.push('slug'); pattern += '([^/]+)'; return; }
          if (part === '{year}') { captures.push('year'); pattern += '([0-9]{4})'; return; }
          if (/^\{[a-z_]+\}$/.test(part)) { captures.push(part.slice(1, -1)); pattern += '([^/]+)'; return; }
          pattern += part.replace(/[.+?^$[\](){}|\\]/g, '\\$&');
        });
        const qs = ['post_type=' + pt.slug];
        captures.forEach((token, i) => {
          const n = i + 1;
          if (token === 'slug') { qs.push('slug=$' + n); qs.push('name=$' + n); }
          else if (token === 'year') qs.push('year=$' + n);
          else if (token === 'category') qs.push('term=$' + n);
        });
        return 'RewriteRule ^' + pattern + '/?$ index.php?' + qs.join('&') + ' [L,QSA]';
      },
      resolve(pt, pattern) {
        const tax = this.taxFor(pt);
        const term = tax.length && tax[0].terms && tax[0].terms[0] ? tax[0].terms[0].slug : 'general';
        let p = (pattern || '/{slug}')
          .replace('{slug}', 'sample-' + pt.slug)
          .replace('{post_type}', pt.slug)
          .replace('{year}', '2026')
          .replace('{category}', term)
          .replace('{type}', pt.slug);
        tax.forEach(t => { p = p.replace('{'+t.slug+'}', term); });
        if (this.opts.lowercase) p = p.toLowerCase();
        if (this.opts.trailingSlash && !p.endsWith('/')) p += '/';
        return p;
      },
      preview(pt) { return this.base + this.resolve(pt, this.pattern(pt)); },
      applyPayload(data) {
        if (!data || typeof data !== 'object') return;
        if (data.site_url) this.base = String(data.site_url).replace(/\/$/, '');
        this.applyStructures(data.structures || data.permalink_structure || {});
        const ht = data.htaccess || {};
        this.htaccessPath = ht.path || '';
        this.htaccessContent = ht.content || '';
        this.htaccessWritable = ht.writable !== false;
        this.htaccessExists = !!ht.exists;
        this.warn = data.warning || '';
      },
      load() {
        const api = window.__mdlr.api;
        if (!api) { this.loading = false; this.err = 'API is not available.'; return; }
        this.loading = true; this.err = '';
        api.get('/settings?permalinks=1')
          .then(data => { this.applyPayload(data); })
          .catch(e => { this.err = (e && e.data && e.data.error) || 'Could not load permalinks.'; })
          .then(() => { this.loading = false; });
      },
      save() {
        const api = window.__mdlr.api;
        if (!api || this.saving) return;
        this.saving = true; this.err = ''; this.warn = ''; this.saved = false;
        api.put('/settings', { structures: this.collectStructures() })
          .then(data => {
            this.applyPayload(data);
            this.saved = !this.warn;
            setTimeout(() => { this.saved = false; }, 2200);
          })
          .catch(e => { this.err = (e && e.data && e.data.error) || 'Saving permalinks failed.'; })
          .then(() => { this.saving = false; });
      },
      flush() { this.save(); },
      copy() {
        const text = this.htaccessContent || '';
        if (!text || !navigator.clipboard) return;
        navigator.clipboard.writeText(text).then(() => {
          this.copied = true;
          setTimeout(() => { this.copied = false; }, 1600);
        }).catch(() => {});
      },
    },
    template: `
    <div class="pl">
      <div class="page-head" style="margin-bottom:18px">
        <div>
          <h2 class="t-title t-h2" style="margin:0">Permalinks</h2>
          <p class="page-sub" style="margin-top:6px">How public URLs are built from content. Each post type can have its own structure. Saving writes rewrite rules into a nested Permalinks section of .htaccess and leaves auth, API, and uploads rules alone.</p>
        </div>
        <div class="flex gap-2 items-center">
          <span v-if="saved" class="badge badge--ok">Saved</span>
          <button class="btn btn--accent" :disabled="saving || loading" @click="save"><Icon name="save" :size="15"/> {{ saving ? 'Saving…' : 'Save permalinks' }}</button>
        </div>
      </div>

      <p v-if="err" class="t-meta" style="color:var(--danger);margin-bottom:12px">{{ err }}</p>
      <p v-if="warn" class="t-meta" style="color:var(--danger);margin-bottom:12px">{{ warn }}</p>
      <p v-if="loading" class="t-meta">Loading…</p>

      <div class="card" style="max-width:none">
        <div class="card-pad" style="padding-bottom:6px"><h3 class="t-title t-h3" style="margin:0">Post type URL structures</h3></div>
        <div class="pl-table">
          <div class="pl-row pl-row--head">
            <div>Post type</div><div>Structure</div><div>Preview</div>
          </div>
          <div class="pl-row" v-for="pt in pts" :key="pt.id">
            <div class="pl-type"><span class="dash-row-ico"><Icon :name="pt.icon" :size="15"/></span><strong>{{ pt.name }}</strong></div>
            <div class="pl-structs">
              <label class="pl-opt" v-for="o in options(pt)" :key="o.key" :class="{'is-on': choice[pt.id]===o.key}">
                <span class="pl-radio" :class="{'is-on': choice[pt.id]===o.key}"></span>
                <input type="radio" hidden :checked="choice[pt.id]===o.key" @change="choice[pt.id]=o.key">
                <template v-if="o.key==='custom'">
                  <span class="pl-optlabel">Custom:</span>
                  <input class="inp mono pl-custom" v-model="custom[pt.id]" placeholder="/{slug}" @focus="choice[pt.id]='custom'">
                  <span class="pl-tokens">tokens: <span class="t-key" v-for="tk in ['{slug}','{post_type}','{category}','{year}']" :key="tk">{{ tk }}</span></span>
                </template>
                <template v-else>
                  <span class="t-key pl-pat">{{ o.pattern }}</span><span class="pl-optlabel">— {{ o.label }}</span>
                </template>
              </label>
            </div>
            <div class="pl-preview"><span class="t-key">{{ preview(pt) }}</span></div>
          </div>
        </div>
      </div>

      <div class="card card-pad" style="margin-top:18px;max-width:none">
        <h3 class="t-title t-h3" style="margin:0 0 6px">Common rules</h3>
        <p class="page-sub" style="margin-bottom:8px">Applied to every generated URL.</p>
        <div class="opt-row" style="padding-top:8px"><div><strong>Force lowercase</strong><div class="field-hint">Normalise slugs to lowercase.</div></div><UiSwitch v-model="opts.lowercase"/></div>
        <div class="opt-row"><div><strong>Trailing slash</strong><div class="field-hint">Append a <span class="t-key">/</span> to every URL.</div></div><UiSwitch v-model="opts.trailingSlash"/></div>
        <div class="opt-row" style="padding-bottom:0"><div><strong>Strip stop words</strong><div class="field-hint">Drop “a, the, and…” when auto-generating slugs.</div></div><UiSwitch v-model="opts.dropStopwords"/></div>
      </div>

      <div class="card card-pad" style="margin-top:18px;max-width:none">
        <h3 class="t-title t-h3" style="margin:0 0 6px">Current .htaccess</h3>
        <p class="page-sub" style="margin-bottom:8px">
          Live contents of <span class="t-key">{{ htaccessPath || '.htaccess' }}</span>.
          <span v-if="!htaccessWritable"> This file is not writable, so save cannot update it.</span>
        </p>
        <pre class="pl-code">{{ htaccessContent || (loading ? 'Loading…' : '(file is empty or missing)') }}</pre>
        <div class="flex gap-2" style="margin-top:12px">
          <button class="btn btn--ghost btn--sm" :disabled="!htaccessContent" @click="copy"><Icon name="copy" :size="14"/> {{ copied ? 'Copied' : 'Copy' }}</button>
          <button class="btn btn--ghost btn--sm" :disabled="saving || loading" @click="flush"><Icon name="refresh" :size="14"/> Flush rewrite rules</button>
        </div>
      </div>

      <div class="card card-pad" style="margin-top:18px;max-width:none">
        <h3 class="t-title t-h3" style="margin:0 0 6px">Permalink rules to write</h3>
        <p class="page-sub" style="margin-bottom:14px">These lines are merged between <span class="t-key"># BEGIN Modular Permalinks</span> and <span class="t-key"># END Modular Permalinks</span>. Existing auth, API, uploads, and file-exists rules are not replaced.</p>
        <pre class="pl-code">{{ permalinkRules }}</pre>
      </div>
    </div>`,
  };
})();
;
/* file: screen-menus.js */
/* ============================================================
   MENUS — the site navigations (main, mobile and the footer
   columns). Each location is a settings row `site_menu_{slug}`
   holding an ordered JSON array of {label, url, type, post_id}.
   The location list itself lives in the `navigations` setting,
   whose `name` doubles as the footer column heading.
   Rendered inside Settings -> Menus.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;

  const DEFAULT_LOCATIONS = [
    { slug: 'main',           name: 'Main navigation' },
    { slug: 'mobile',         name: 'Mobile navigation' },
    { slug: 'footer_music',   name: 'Music' },
    { slug: 'footer_network', name: 'Network' },
    { slug: 'footer_shows',   name: 'Shows' },
    { slug: 'footer_about',   name: 'About' },
    { slug: 'footer_legal',   name: 'Legal' },
  ];

  C.MenusPanel = {
    name: 'MenusPanel',
    data() {
      return {
        locations: [],
        menus: {},          // slug -> [{label,url,type,post_id}]
        active: 'main',
        pages: [],
        loading: true,
        saving: false,
        saved: false,
        err: '',
        addKind: 'page',    // 'page' | 'custom'
        addPageId: '',
        addLabel: '',
        addUrl: '',
      };
    },
    created() { this.load(); },
    computed: {
      items() { return this.menus[this.active] || []; },
      activeName() { const l = this.locations.find(x => x.slug === this.active); return l ? l.name : this.active; },
      canAdd() {
        if (this.addKind === 'page') return !!this.addPageId;
        return this.addLabel.trim() !== '' && this.addUrl.trim() !== '';
      },
    },
    methods: {
      load() {
        const api = window.__mdlr.api;
        if (!api) { this.loading = false; return; }
        this.loading = true; this.err = '';
        Promise.all([
          api.get('/settings').catch(() => ({})),
          // ?fields=light returns id/title/slug/post_type_slug only; the endpoint
          // filters on numeric post_type_id, so narrow to pages on this side.
          api.get('/posts?fields=light&limit=500').catch(() => null),
        ]).then(([settings, pages]) => {
          const navs = settings && settings.navigations;
          this.locations = Array.isArray(navs) && navs.length ? navs : DEFAULT_LOCATIONS;
          const menus = {};
          this.locations.forEach(loc => {
            const raw = settings ? settings['site_menu_' + loc.slug] : null;
            menus[loc.slug] = Array.isArray(raw) ? raw.map(this.normalize) : [];
          });
          this.menus = menus;
          if (!menus[this.active]) this.active = this.locations[0] ? this.locations[0].slug : 'main';
          const list = pages && (pages.items || pages.data || pages);
          this.pages = Array.isArray(list)
            ? list.filter(p => (p.post_type_slug || 'page') === 'page' && !p.deleted_at)
                  .map(p => ({ id: p.id, title: p.title || p.slug, slug: p.slug }))
            : [];
        }).catch(e => {
          this.err = (e && e.data && e.data.error) || 'Could not load the menus.';
        }).then(() => { this.loading = false; });
      },
      normalize(it) {
        return {
          label: String((it && it.label) || ''),
          url: String((it && it.url) || ''),
          type: (it && it.type) || 'custom',
          post_id: (it && it.post_id) || null,
        };
      },
      move(i, delta) {
        const arr = this.menus[this.active];
        const j = i + delta;
        if (j < 0 || j >= arr.length) return;
        arr.splice(j, 0, arr.splice(i, 1)[0]);
      },
      remove(i) { this.menus[this.active].splice(i, 1); },
      addItem() {
        if (!this.canAdd) return;
        const arr = this.menus[this.active];
        if (this.addKind === 'page') {
          const p = this.pages.find(x => String(x.id) === String(this.addPageId));
          if (!p) return;
          arr.push({ label: this.addLabel.trim() || p.title, url: '/' + p.slug, type: 'post', post_id: p.id });
        } else {
          let url = this.addUrl.trim();
          if (!/^(https?:)?\/\//.test(url) && url[0] !== '/') url = '/' + url;
          arr.push({ label: this.addLabel.trim(), url, type: 'custom', post_id: null });
        }
        this.addPageId = ''; this.addLabel = ''; this.addUrl = '';
      },
      save() {
        const api = window.__mdlr.api;
        if (!api || this.saving) return;
        this.saving = true; this.err = '';
        const options = { navigations: this.locations };
        this.locations.forEach(loc => { options['site_menu_' + loc.slug] = this.menus[loc.slug] || []; });
        api.put('/settings', { options })
          .then(() => { this.saved = true; setTimeout(() => { this.saved = false; }, 2200); })
          .catch(e => { this.err = (e && e.data && e.data.error) || 'Saving the menus failed.'; })
          .then(() => { this.saving = false; });
      },
    },
    template: `
    <div>
      <div class="flex items-start justify-between" style="margin-bottom:16px;gap:16px;flex-wrap:wrap">
        <div>
          <h2 class="t-title t-h2" style="margin:0">Menus</h2>
          <p class="page-sub" style="margin-top:6px;max-width:62ch">The site navigations. Every location is rendered by the theme: the main and mobile bars at the top, the rest as footer columns using the location name as the heading.</p>
        </div>
        <div class="flex gap-2 items-center">
          <span v-if="saved" class="badge badge--ok">Saved</span>
          <button class="btn btn--accent" :disabled="saving || loading" @click="save"><Icon name="check" :size="15"/> {{ saving ? 'Saving…' : 'Save menus' }}</button>
        </div>
      </div>

      <p v-if="err" class="t-meta" style="color:var(--danger)">{{ err }}</p>
      <p v-if="loading" class="t-meta">Loading…</p>

      <div v-if="!loading" class="tabs" style="flex-wrap:wrap;margin-bottom:16px">
        <button v-for="l in locations" :key="l.slug" :class="{'is-on': active===l.slug}" @click="active=l.slug">
          {{ l.name }} <span class="tab-count">{{ (menus[l.slug]||[]).length }}</span>
        </button>
      </div>

      <div v-if="!loading" class="card card-pad">
        <h3 class="t-h3" style="margin:0 0 4px">{{ activeName }}</h3>
        <p class="t-meta" style="margin:0 0 14px">Stored as <code>site_menu_{{ active }}</code>. Page items take both their label and their link from the page itself, so they keep working when a page is renamed or moved. Use a custom link for anything else.</p>

        <table class="tbl tbl--rows" v-if="items.length">
          <thead><tr><th>Label</th><th>Link</th><th style="width:150px">Order</th><th style="width:60px"></th></tr></thead>
          <tbody>
            <tr v-for="(it,i) in items" :key="i">
              <td>
                <input class="inp" v-model="it.label" :disabled="it.type==='post'">
                <div v-if="it.type==='post'" class="t-meta" style="margin-top:4px">Follows the page title</div>
              </td>
              <td>
                <template v-if="it.type === 'post'">
                  <span class="mono">{{ it.url }}</span>
                  <div class="t-meta" style="margin-top:4px">Generated from the page{{ it.post_id ? ' (#' + it.post_id + ')' : '' }}</div>
                </template>
                <template v-else>
                  <input class="inp" v-model="it.url">
                  <div class="t-meta" style="margin-top:4px">Custom link</div>
                </template>
              </td>
              <td>
                <button class="iconbtn" :disabled="i===0" @click="move(i,-1)" title="Move up"><Icon name="chevUp" :size="15"/></button>
                <button class="iconbtn" :disabled="i===items.length-1" @click="move(i,1)" title="Move down"><Icon name="chevDown" :size="15"/></button>
              </td>
              <td><button class="iconbtn" @click="remove(i)" title="Remove"><Icon name="trash" :size="15"/></button></td>
            </tr>
          </tbody>
        </table>
        <p v-else class="t-meta" style="margin:0 0 14px">This menu is empty. The theme falls back to its built-in defaults while a location has no items.</p>

        <div style="border-top:1px solid var(--line);margin-top:16px;padding-top:16px">
          <div class="flex gap-2" style="margin-bottom:10px">
            <button class="btn btn--sm" :class="addKind==='page' ? 'btn--accent' : 'btn--ghost'" @click="addKind='page'">Page</button>
            <button class="btn btn--sm" :class="addKind==='custom' ? 'btn--accent' : 'btn--ghost'" @click="addKind='custom'">Custom link</button>
          </div>
          <div class="flex gap-2 items-end" style="flex-wrap:wrap">
            <div class="field" style="margin:0;min-width:220px" v-if="addKind==='page'">
              <label class="field-label">Page</label>
              <UiSelect v-model="addPageId">
                <option value="">Choose a page…</option>
                <option v-for="p in pages" :key="p.id" :value="String(p.id)">{{ p.title }}</option>
              </UiSelect>
            </div>
            <div class="field" style="margin:0;min-width:220px" v-else>
              <label class="field-label">URL</label>
              <input class="inp" v-model="addUrl" placeholder="/shows?type=festival">
            </div>
            <div class="field" style="margin:0;min-width:200px" v-if="addKind==='custom'">
              <label class="field-label">Label <span class="field-req">*</span></label>
              <input class="inp" v-model="addLabel" placeholder="Festivals">
            </div>
            <button class="btn btn--ghost" :disabled="!canAdd" @click="addItem"><Icon name="plus" :size="15"/> Add item</button>
          </div>
        </div>
      </div>
    </div>`,
  };
})();
;
/* file: screen-notifications.js */
/* ============================================================
   NOTIFICATIONS + DOCS
   - Notifications: full list with read/unread, filters, mark-all.
   - Docs: in-app documentation hub (where the ? button lands),
     including the canonical Fixed-vs-Modular explanation.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenNotifications = {
    name: 'ScreenNotifications',
    inject: ['go'],
    data() { return { filter: 'all' }; },
    computed: {
      all() { return D().NOTIFICATIONS; },
      items() { return this.filter === 'unread' ? this.all.filter(n => !n.read) : this.all; },
      unread() { return this.all.filter(n => !n.read).length; },
    },
    methods: {
      open(n) { n.read = true; if (n.go) this.go(n.go[0], n.go[1]); },
      markAll() { this.all.forEach(n => { n.read = true; }); },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Notifications</h1><p class="page-sub" style="margin-top:6px">{{ unread }} unread · {{ all.length }} total.</p></div>
        <button class="btn btn--ghost" @click="markAll" :disabled="!unread"><Icon name="check"/> Mark all read</button>
      </div>
      <div class="tabs" style="margin-bottom:16px">
        <button :class="{'is-on':filter==='all'}" @click="filter='all'">All <span class="tab-count">{{ all.length }}</span></button>
        <button :class="{'is-on':filter==='unread'}" @click="filter='unread'">Unread <span class="tab-count" v-if="unread">{{ unread }}</span></button>
      </div>
      <div class="card">
        <div class="notif-list">
          <button v-for="n in items" :key="n.id" class="notif-item notif-item--lg" :class="{'is-unread':!n.read}" @click="open(n)">
            <span class="notif-ico"><Icon :name="n.icon" :size="16"/></span>
            <span class="notif-main"><strong>{{ n.title }}</strong><span class="notif-body">{{ n.body }}</span></span>
            <span class="t-meta" style="flex:none">{{ n.when }}</span>
            <span class="notif-unread" v-if="!n.read"></span>
          </button>
          <div v-if="!items.length" class="empty" style="padding:48px"><Icon name="bell"/><div>You're all caught up.</div></div>
        </div>
      </div>
    </div>`,
  };

  const DOC_SECTIONS_UNUSED = null;
})();
;
/* file: screen-exportimport.js */
/* ============================================================
   EXPORT / IMPORT — move content between Modular installs.
   Tabs: Export · Import · History (driven by route.params.tab).
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenExportImport = {
    name: 'ScreenExportImport',
    inject: ['go', 'route'],
    data() {
      return {
        includeMedia: false,
        importMode: 'skip',
        dryRun: false,
        fileName: '',
        file: null,
        running: false,
        result: null,
        error: '',
        sourceFilter: 'all',
        exportProfile: 'full',
        exportPostType: '',
      };
    },
    computed: {
      tab() { return (this.route.params || {}).tab || 'export'; },
      tables() { return D().EXPORT_TABLES; },
      history() { return D().IMPORT_HISTORY; },
      postTypes() { return D().POST_TYPES || []; },
      effectivePostType() {
        const pts = this.postTypes;
        if (!pts.length) return '';
        if (this.exportPostType && pts.some(p => p.id === this.exportPostType)) return this.exportPostType;
        return pts[0].id;
      },
      selectedPostTypeLabel() {
        const pt = this.postTypes.find(p => p.id === this.effectivePostType);
        return pt ? pt.name : this.effectivePostType;
      },
      exportScopeLabel() {
        if (this.exportProfile === 'post_type' && this.effectivePostType) {
          return 'post type ' + this.effectivePostType + (this.selectedPostTypeLabel ? ' (' + this.selectedPostTypeLabel + ')' : '');
        }
        return 'all registered tables';
      },
      sources() { return ['all', ...new Set(this.tables.map(t => t.source))]; },
      shownTables() { return this.sourceFilter === 'all' ? this.tables : this.tables.filter(t => t.source === this.sourceFilter); },
      totalRows() { return this.tables.reduce((n, t) => n + t.rows, 0).toLocaleString(); },
      resultRows() {
        if (!this.result || !this.result.summary) return [];
        return Object.keys(this.result.summary)
          .filter(k => k.charAt(0) !== '_')
          .map(k => Object.assign({ table: k }, this.result.summary[k]));
      },
      resultErrors() { return (this.result && this.result.summary && this.result.summary._errors) || []; },
    },
    methods: {
      sourceClass(s) { return s === 'core' ? '' : 'badge--info'; },
      onFile(e) {
        this.file = (e.target.files && e.target.files[0]) || null;
        this.fileName = this.file ? this.file.name : '';
        this.result = null; this.error = '';
      },
      downloadBundle() {
        const base = (window.__mdlr.api && window.__mdlr.api.base) || '/api/v1';
        const q = new URLSearchParams();
        if (this.includeMedia) q.set('include_media', '1');
        if (this.exportProfile === 'post_type' && this.effectivePostType) {
          q.set('profile', 'post_type');
          q.set('post_type', this.effectivePostType);
        }
        const qs = q.toString();
        window.location.href = base + '/export' + (qs ? '?' + qs : '');
      },
      async runImport() {
        if (!this.file || this.running) return;
        this.running = true; this.result = null; this.error = '';
        try {
          const fd = new FormData();
          fd.append('bundle', this.file);
          fd.append('mode', this.dryRun ? 'dryrun' : this.importMode);
          fd.append('duplicate_policy', this.importMode === 'overwrite' ? 'overwrite' : 'skip');
          this.result = await window.__mdlr.api.upload('/import', fd);
        } catch (err) {
          this.error = (err && err.data && err.data.error) ? err.data.error : (err.message || 'Import failed');
        } finally {
          this.running = false;
        }
      },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <h1 class="t-title t-h1">Export / Import</h1>
          <p class="page-sub" style="margin-top:6px">Move content between Modular installs. Bundles are portable <span class="t-key">.zip</span> archives — schema, content and (optionally) media in one file.</p>
        </div>
      </div>

      <div class="tabs" style="margin-bottom:22px">
        <button :class="{'is-on':tab==='export'}" @click="go('exportImport',{tab:'export'})"><Icon name="upload" :size="15"/> Export</button>
        <button :class="{'is-on':tab==='import'}" @click="go('exportImport',{tab:'import'})"><Icon name="folder" :size="15"/> Import</button>
        <button :class="{'is-on':tab==='history'}" @click="go('exportImport',{tab:'history'})"><Icon name="clock" :size="15"/> History</button>
      </div>

      <!-- EXPORT -->
      <template v-if="tab==='export'">
        <div class="card card-pad" style="margin-bottom:18px">
          <h2 class="t-title t-h3" style="margin:0 0 8px">Download a content bundle</h2>
          <p class="page-sub" style="max-width:70ch">Export registered tables into a single <span class="t-key">.zip</span> bundle that can be imported into another Modular install.</p>

          <label class="field-label" style="margin-top:16px">Export scope</label>
          <div class="ei-modes" style="margin-top:8px">
            <button class="ei-mode" :class="{'is-on': exportProfile==='full'}" @click="exportProfile='full'">
              <span class="ei-radio" :class="{'is-on': exportProfile==='full'}"></span>
              <span><strong>Full site</strong> — all registered tables (core + active modules).</span>
            </button>
            <button class="ei-mode" :class="{'is-on': exportProfile==='post_type'}" @click="exportProfile='post_type'">
              <span class="ei-radio" :class="{'is-on': exportProfile==='post_type'}"></span>
              <span><strong>Single post type</strong> — one content type and its posts, taxonomies, featured images and revisions. Authors are linked by email to existing users on import, never exported as user records.</span>
            </button>
          </div>

          <template v-if="exportProfile==='post_type'">
            <label class="field-label" style="margin-top:14px">Post type</label>
            <UiSelect v-model="exportPostType" style="max-width:320px;margin-top:6px">
              <option v-if="!postTypes.length" value="" disabled>No post types loaded</option>
              <option v-for="pt in postTypes" :key="pt.id" :value="pt.id">{{ pt.name }} ({{ pt.id }})</option>
            </UiSelect>
            <p class="t-meta" style="margin-top:8px;max-width:70ch">Exports the post type definition, its fields and all content rows for <span class="t-key">{{ effectivePostType || '…' }}</span>.</p>
          </template>

          <label class="ei-check" :class="{'is-on': includeMedia}">
            <UiChk :modelValue="includeMedia" @update:modelValue="includeMedia=!includeMedia"/>
            <span><strong>Include media files</strong> — <template v-if="exportProfile==='post_type'">bundle featured images referenced by exported posts.</template><template v-else>bundle <span class="t-key">public/uploads/media/</span> into the zip (larger, but self-sufficient).</template></span>
          </label>

          <div class="flex items-center gap-3" style="margin-top:18px">
            <button class="btn btn--accent" @click="downloadBundle"><Icon name="upload" :size="15"/> Download bundle</button>
            <span class="t-meta">{{ exportScopeLabel }}{{ includeMedia ? ' · + media files' : '' }}</span>
          </div>
        </div>

        <div class="card">
          <div class="card-pad" style="padding-bottom:0">
            <div class="flex items-center justify-between" style="gap:16px;flex-wrap:wrap">
              <div>
                <h2 class="t-title t-h3" style="margin:0 0 6px">Tables included in this export</h2>
                <p class="t-meta" style="max-width:64ch">Built from <span class="t-key">md_export_register()</span> calls across core and active modules. Tables not in this list are not exported.</p>
              </div>
              <UiSelect style="width:auto" v-model="sourceFilter"><option v-for="s in sources" :key="s" :value="s">{{ s==='all'?'All sources':s }}</option></UiSelect>
            </div>
          </div>
          <table class="tbl" style="margin-top:14px">
            <thead><tr><th>Name</th><th>Table</th><th style="width:120px">Source</th><th style="width:120px">Rows</th><th>Natural key</th></tr></thead>
            <tbody>
              <tr v-for="t in shownTables" :key="t.table">
                <td><strong>{{ t.name }}</strong></td>
                <td><span class="t-key">{{ t.table }}</span></td>
                <td><span class="badge" :class="sourceClass(t.source)">{{ t.source }}</span></td>
                <td class="mono muted">{{ t.rows.toLocaleString() }}</td>
                <td><span class="t-key">{{ t.key }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- IMPORT -->
      <template v-else-if="tab==='import'">
        <div class="card card-pad" style="max-width:960px">
          <h2 class="t-title t-h3" style="margin:0 0 8px">Upload a bundle</h2>
          <p class="page-sub" style="max-width:70ch">Upload a <span class="t-key">.zip</span> bundle exported from another Modular install. Choose how to handle rows that already exist.</p>

          <label class="field-label" style="margin-top:16px">Bundle file</label>
          <label class="ei-file">
            <span class="btn btn--soft btn--sm"><Icon name="folder" :size="14"/> Browse…</span>
            <span class="t-meta">{{ fileName || 'No file selected.' }}</span>
            <input type="file" accept=".zip" hidden @change="onFile">
          </label>

          <div class="ei-modes" style="margin-top:18px">
            <button class="ei-mode" :class="{'is-on': importMode==='skip'}" @click="importMode='skip'">
              <span class="ei-radio" :class="{'is-on': importMode==='skip'}"></span>
              <span><strong>Skip duplicates</strong> — existing rows (matched by natural key) are kept; only new rows are inserted.</span>
            </button>
            <button class="ei-mode" :class="{'is-on': importMode==='overwrite'}" @click="importMode='overwrite'">
              <span class="ei-radio" :class="{'is-on': importMode==='overwrite'}"></span>
              <span><strong>Overwrite duplicates</strong> — existing rows are updated with the imported values. Destructive but useful for syncing.</span>
            </button>
          </div>

          <label class="ei-check ei-check--warn" :class="{'is-on': dryRun}" style="margin-top:14px">
            <UiChk :modelValue="dryRun" @update:modelValue="dryRun=!dryRun"/>
            <span><strong>Dry run</strong> — report what would happen without writing to the database. Wraps the import in a transaction and rolls back at the end.</span>
          </label>

          <div class="flex items-center gap-3" style="margin-top:20px">
            <button class="btn btn--accent" :disabled="!file || running" @click="runImport"><Icon name="upload" :size="15"/> {{ running ? 'Running…' : 'Run import' }}</button>
            <span class="t-meta" v-if="!file">Choose a bundle to enable import.</span>
          </div>

          <div v-if="error" class="badge badge--warn" style="margin-top:18px;display:block;padding:12px">{{ error }}</div>

          <div v-if="result" style="margin-top:18px">
            <h3 class="t-title t-h4" style="margin:0 0 8px">
              {{ result.mode === 'dryrun' ? 'Dry run — nothing was written' : 'Import complete' }}
            </h3>
            <table class="tbl">
              <thead><tr><th>Table</th><th style="width:90px">Created</th><th style="width:90px">Updated</th><th style="width:90px">Skipped</th></tr></thead>
              <tbody>
                <tr v-for="r in resultRows" :key="r.table">
                  <td><span class="t-key">{{ r.table }}</span></td>
                  <td class="mono">{{ r.created || 0 }}</td>
                  <td class="mono">{{ r.updated || 0 }}</td>
                  <td class="mono muted">{{ r.skipped || 0 }}</td>
                </tr>
              </tbody>
            </table>
            <div v-if="resultErrors.length" class="badge badge--warn" style="margin-top:12px;display:block;padding:12px">
              <strong>{{ resultErrors.length }} issue(s):</strong>
              <div v-for="(e,i) in resultErrors" :key="i" class="mono" style="margin-top:4px">{{ e }}</div>
            </div>
          </div>
        </div>
      </template>

      <!-- HISTORY -->
      <template v-else>
        <div class="card">
          <div class="card-pad" style="padding-bottom:6px"><h2 class="t-title t-h3" style="margin:0">Import history</h2></div>
          <table class="tbl">
            <thead><tr><th style="width:180px">When</th><th>Bundle</th><th>Source</th><th style="width:120px">Rows</th><th style="width:120px">Mode</th><th style="width:120px">By</th></tr></thead>
            <tbody>
              <tr v-for="h in history" :key="h.id">
                <td class="mono muted">{{ h.when }}</td>
                <td><span class="t-key">{{ h.bundle }}</span></td>
                <td class="muted">{{ h.source }}</td>
                <td class="mono muted">{{ h.rows.toLocaleString() }}</td>
                <td><span class="badge" :class="h.mode==='overwrite'?'badge--warn':''">{{ h.mode }}</span></td>
                <td><div class="flex items-center gap-2"><span class="avatar" style="width:22px;height:22px;font-size:9px">{{ h.by.slice(0,2).toUpperCase() }}</span>{{ h.by }}</div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>`,
  };
})();
;
/* file: screen-library.js */
/* ============================================================
   FIELD & COMPONENT LIBRARY — bespoke Atelier design showcase.
   Demonstrates the shared primitives and field components in
   every state (default · hover · focus · selected · empty ·
   error · disabled · loading). Design reference, not backend.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  const SECTIONS = [
    { id:'registry', label:'All field types', icon:'grid' },
    { id:'primitives', label:'Primitives', icon:'box' },
    { id:'relation', label:'Relation picker', icon:'git' },
    { id:'media', label:'Media fields', icon:'image' },
    { id:'datetime', label:'Date & time', icon:'calendar' },
    { id:'dynamic', label:'Embed & dynamic', icon:'webhook' },
    { id:'containers', label:'Containers', icon:'layers' },
    { id:'composer', label:'Block composer', icon:'grid' },
    { id:'richtext', label:'Rich text', icon:'type' },
    { id:'settings', label:'Settings panel', icon:'sliders' },
  ];

  C.ScreenLibrary = {
    name: 'ScreenLibrary',
    inject: ['go'],
    data() {
      return {
        sec: 'registry', sections: SECTIONS,
        cbQuery:'', cbOpen:false, cbSel:null, cbMulti:['p2'],
        pool: [
          { id:'p1', title:'Digital Twins Helpdesk', type:'Project' },
          { id:'p2', title:'Headless without the headache', type:'Post' },
          { id:'p3', title:'EU-hosted by default', type:'Post' },
          { id:'p4', title:'MydayPrice', type:'Product' },
          { id:'p5', title:'Living-in.EU', type:'Project' },
        ],
        sortItems:[ {id:'s1',label:'Intro paragraph'},{id:'s2',label:'Feature grid'},{id:'s3',label:'Testimonial'},{id:'s4',label:'Call to action'} ],
        sortDrag:null, sortOver:null,
        calMonth:5, calYear:2026, calSel:12, calTime:'18:00',
        relChips:['p1','p5'], relDrag:null, relOver:null, relMin:1, relMax:4,
        embedUrl:'https://www.youtube.com/watch?v=dQw4',
        lp:{ type:'post', tax:'all', limit:3, sort:'date_desc' },
        repItems:[ {id:'r1',open:true,q:'Do I need a contract?',a:'No — book per week, cancel anytime.'},{id:'r2',open:false,q:'How do payouts work?',a:'Weekly, minus the platform fee.'} ],
        blocks:[ {id:'b1',comp:'cmp_hero',open:false},{id:'b2',comp:'cmp_richtext',open:true},{id:'b3',comp:'cmp_gallery',open:false} ],
        blkDrag:null, blkOver:null, blkPicker:false,
        allowed:['cmp_hero','cmp_richtext','cmp_imgtext','cmp_gallery','cmp_quote','cmp_features'],
        settingsType:'text', tab:0, accOpen:1, cols:2,
      };
    },
    computed: {
      cbResults() { const q=this.cbQuery.trim().toLowerCase(); return this.pool.filter(p=>!q||p.title.toLowerCase().includes(q)); },
      cbSelObj() { return this.pool.find(p=>p.id===this.cbSel); },
      relTooMany() { return this.relChips.length>this.relMax; },
      comps() { return D().COMPONENTS; },
      regCats() { const types=D().FIELD_TYPES; return D().CAT_ORDER.map(c=>({ ...c, items: types.filter(t=>t.cat===c.cat) })).filter(c=>c.items.length); },
      allowedComps() { return this.allowed.map(id=>this.comp(id)); },
      calCells() {
        const first=new Date(this.calYear,this.calMonth,1).getDay(); const lead=(first+6)%7;
        const dim=new Date(this.calYear,this.calMonth+1,0).getDate(); const cells=[];
        for(let i=0;i<lead;i++)cells.push(null); for(let d=1;d<=dim;d++)cells.push(d); return cells;
      },
    },
    methods: {
      hue(id){ const map={p1:32,p2:210,p3:150,p4:280,p5:18}; return map[id]||30; },
      grad(h){ return `linear-gradient(135deg, hsl(${h} 32% 80%), hsl(${h-10} 26% 56%))`; },
      catColor(cat){ return (D().CAT_VAR||{})[cat] || 'var(--ink-3)'; },
      comp(id){ return D().COMPONENTS.find(c=>c.id===id)||{name:id,icon:'box',fields:[],version:1}; },
      byId(id){ return this.pool.find(p=>p.id===id); },
      pick(p){ this.cbSel=p.id; this.cbOpen=false; this.cbQuery=''; },
      toggleMulti(p){ const i=this.cbMulti.indexOf(p.id); if(i>=0)this.cbMulti.splice(i,1); else this.cbMulti.push(p.id); },
      sStart(i){ this.sortDrag=i; }, sOver(i,e){ e.preventDefault(); this.sortOver=i; },
      sDrop(){ if(this.sortDrag==null)return; const it=this.sortItems.splice(this.sortDrag,1)[0]; this.sortItems.splice(this.sortOver,0,it); this.sortDrag=null; this.sortOver=null; },
      rStart(i){ this.relDrag=i; }, rOver(i,e){ e.preventDefault(); this.relOver=i; },
      rDrop(){ if(this.relDrag==null)return; const it=this.relChips.splice(this.relDrag,1)[0]; this.relChips.splice(this.relOver,0,it); this.relDrag=null; this.relOver=null; },
      rRemove(id){ this.relChips=this.relChips.filter(x=>x!==id); },
      monthName(){ return new Date(this.calYear,this.calMonth,1).toLocaleDateString('en-US',{month:'long',year:'numeric'}); },
      repAdd(){ this.repItems.push({id:'r'+Date.now(),open:true,q:'',a:''}); },
      repDel(i){ this.repItems.splice(i,1); }, repDup(i){ const c=JSON.parse(JSON.stringify(this.repItems[i])); c.id='r'+Date.now(); this.repItems.splice(i+1,0,c); },
      blkAdd(id){ this.blocks.push({id:'b'+Date.now(),comp:id,open:false}); this.blkPicker=false; },
      blkDel(i){ this.blocks.splice(i,1); }, blkDup(i){ const c=JSON.parse(JSON.stringify(this.blocks[i])); c.id='b'+Date.now(); this.blocks.splice(i+1,0,c); },
      blkStart(i){ this.blkDrag=i; }, blkOverFn(i,e){ e.preventDefault(); this.blkOver=i; },
      blkDrop(){ if(this.blkDrag==null)return; const it=this.blocks.splice(this.blkDrag,1)[0]; this.blocks.splice(this.blkOver,0,it); this.blkDrag=null; this.blkOver=null; },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px">Design reference · read-only</div>
          <h1 class="t-title t-h1">Field types</h1>
          <p class="page-sub" style="margin-top:6px">A catalog of every field type and shared control, in all its states. This is a <strong style="color:var(--ink-2)">reference</strong> — you build with these fields in <span class="t-key">Field groups</span> (entry forms) and <span class="t-key">Blocks</span> (visual page sections).</p>
        </div>
      </div>

      <div class="settings-layout">
        <nav class="settings-nav">
          <div class="settings-nav-group">
            <div class="settings-nav-label">Components</div>
            <button v-for="s in sections" :key="s.id" class="settings-nav-item" :class="{'is-on': sec===s.id}" @click="sec=s.id"><Icon :name="s.icon" :size="15"/> {{ s.label }}</button>
          </div>
          <div class="lib-legend">
            <div class="settings-nav-label">States shown</div>
            <span v-for="st in ['default','hover','focus','selected','empty','error','disabled','loading']" :key="st" class="lib-state-tag">{{ st }}</span>
          </div>
        </nav>

        <div class="settings-content lib-content">

          <!-- ============ REGISTRY (the one shared source) ============ -->
          <template v-if="sec==='registry'">
            <div class="lib-head"><h2 class="t-title t-h2">The field registry</h2><p class="page-sub">One shared set of field types. <strong style="color:var(--ink-2)">Field groups</strong> use them to build entry forms; <strong style="color:var(--ink-2)">Blocks</strong> use the same ones, composed visually. This page is the source of truth for what exists.</p></div>
            <div class="flex gap-2 wrap" style="margin-bottom:18px">
              <button class="btn btn--soft btn--sm" @click="go('binding')"><Icon name="git" :size="14"/> Build a field group</button>
              <button class="btn btn--soft btn--sm" @click="go('components')"><Icon name="layers" :size="14"/> Build a block</button>
            </div>
            <div class="reg-cat" v-for="c in regCats" :key="c.cat">
              <div class="reg-cat-h"><span class="fb-cat-dot" :style="{background:catColor(c.cat)}"></span>{{ c.label }}<span class="t-meta">{{ c.items.length }}</span></div>
              <div class="reg-grid">
                <div class="reg-row" v-for="t in c.items" :key="t.type">
                  <span class="lib-ico" :style="{color:catColor(c.cat)}"><Icon :name="t.icon" :size="15"/></span>
                  <div class="reg-main"><strong>{{ t.label }}</strong><span class="t-key">{{ t.type }}</span></div>
                  <div class="reg-avail">
                    <span class="reg-badge is-on" title="Available in field groups"><Icon name="git" :size="10"/> Forms</span>
                    <span class="reg-badge" :class="{'is-on':!t.formsOnly}" :title="t.formsOnly?'Form-only (structural)':'Available in blocks'"><Icon name="layers" :size="10"/> Blocks</span>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <!-- ============ PRIMITIVES ============ -->
          <template v-if="sec==='primitives'">
            <div class="lib-head"><h2 class="t-title t-h2">Primitives</h2><p class="page-sub">Shared controls that every field reuses. <span class="lib-flag">custom design</span></p></div>

            <!-- COMBOBOX -->
            <section class="lib-block">
              <div class="lib-block-h"><h3 class="t-title t-h3">Combobox</h3><span class="t-meta">async search · single &amp; multi</span></div>
              <div class="lib-grid">
                <div class="lib-spec"><span class="lib-spec-l">Default (single)</span>
                  <div class="cbx">
                    <button class="cbx-control" :class="{'is-open':cbOpen}" @click="cbOpen=!cbOpen">
                      <template v-if="cbSelObj"><span class="rel-thumb" :style="{background:grad(hue(cbSelObj.id))}"></span><span class="cbx-val">{{ cbSelObj.title }}</span></template>
                      <span v-else class="cbx-ph">Search content…</span>
                      <Icon name="chevDown" :size="15" class="muted"/>
                    </button>
                    <div v-if="cbOpen" class="cbx-pop">
                      <div class="cbx-search"><Icon name="search" :size="15"/><input v-model="cbQuery" placeholder="Type to search…" autofocus></div>
                      <div class="cbx-list">
                        <button v-for="p in cbResults" :key="p.id" class="cbx-opt" :class="{'is-sel':cbSel===p.id}" @click="pick(p)">
                          <span class="rel-thumb" :style="{background:grad(hue(p.id))}"></span>
                          <span class="cbx-opt-main"><strong>{{ p.title }}</strong><span class="t-meta">{{ p.type }}</span></span>
                          <Icon v-if="cbSel===p.id" name="check" :size="15" class="cbx-check"/>
                        </button>
                        <div v-if="!cbResults.length" class="cbx-empty">No matches for “{{ cbQuery }}”.</div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="lib-spec"><span class="lib-spec-l">Focus</span>
                  <div class="cbx"><div class="cbx-control is-focus"><span class="cbx-ph">Search content…</span><Icon name="chevDown" :size="15" class="muted"/></div></div>
                </div>

                <div class="lib-spec"><span class="lib-spec-l">Loading</span>
                  <div class="cbx"><div class="cbx-control"><span class="cbx-spin"></span><span class="cbx-ph">Searching…</span></div></div>
                </div>

                <div class="lib-spec"><span class="lib-spec-l">Empty result</span>
                  <div class="cbx"><div class="cbx-pop cbx-pop--static"><div class="cbx-search"><Icon name="search" :size="15"/><input value="zzz" disabled></div><div class="cbx-empty"><Icon name="search" :size="18"/><div>No matches for “zzz”.</div></div></div></div>
                </div>

                <div class="lib-spec"><span class="lib-spec-l">Error</span>
                  <div class="cbx"><div class="cbx-control is-error"><span class="cbx-ph" style="color:var(--danger)">Required</span><Icon name="chevDown" :size="15" class="muted"/></div><span class="lib-err">Pick at least one item.</span></div>
                </div>

                <div class="lib-spec"><span class="lib-spec-l">Disabled</span>
                  <div class="cbx"><div class="cbx-control is-disabled"><span class="cbx-ph">Locked</span><Icon name="chevDown" :size="15" class="muted"/></div></div>
                </div>
              </div>

              <div class="lib-spec" style="margin-top:14px"><span class="lib-spec-l">Multi-select with chips</span>
                <div class="cbx-multi">
                  <span v-for="id in cbMulti" :key="id" class="rel-chip"><span class="rel-thumb sm" :style="{background:grad(hue(id))}"></span>{{ byId(id).title }}<button @click="toggleMulti(byId(id))"><Icon name="x" :size="12"/></button></span>
                  <button class="cbx-addchip" @click="cbOpen2=!cbOpen2"><Icon name="plus" :size="13"/> Add</button>
                </div>
              </div>
            </section>

            <!-- SORTABLE -->
            <section class="lib-block">
              <div class="lib-block-h"><h3 class="t-title t-h3">SortableList</h3><span class="t-meta">drag to reorder</span></div>
              <div class="sortable" @dragover.prevent @drop="sDrop">
                <div v-for="(it,i) in sortItems" :key="it.id" class="sort-row" :class="{'is-dragging':sortDrag===i,'is-over':sortOver===i&&sortDrag!==i}"
                     draggable="true" @dragstart="sStart(i)" @dragend="sortDrag=null;sortOver=null" @dragover="sOver(i,$event)" @drop="sDrop">
                  <span class="sort-grip"><Icon name="drag" :size="16"/></span>
                  <span class="sort-num">{{ i+1 }}</span>
                  <span class="sort-label">{{ it.label }}</span>
                  <button class="iconbtn iconbtn--sm is-danger"><Icon name="trash" :size="14"/></button>
                </div>
              </div>
              <p class="lib-note"><Icon name="info" :size="13"/> Whole row is the drop target; a 2px accent rule marks the insertion point and the dragged row dims to 40%.</p>
            </section>

            <!-- CALENDAR -->
            <section class="lib-block">
              <div class="lib-block-h"><h3 class="t-title t-h3">Calendar</h3><span class="t-meta">date · time</span></div>
              <div class="cal">
                <div class="cal-head"><button class="iconbtn iconbtn--sm" @click="calMonth=(calMonth+11)%12"><Icon name="chevLeft" :size="16"/></button><strong>{{ monthName() }}</strong><button class="iconbtn iconbtn--sm" @click="calMonth=(calMonth+1)%12"><Icon name="chevRight" :size="16"/></button></div>
                <div class="cal-dow"><span v-for="d in ['Mo','Tu','We','Th','Fr','Sa','Su']" :key="d">{{ d }}</span></div>
                <div class="cal-grid">
                  <template v-for="(c,i) in calCells" :key="i"><span v-if="c===null" class="cal-cell cal-cell--empty"></span><button v-else class="cal-cell" :class="{'is-sel':calSel===c,'is-today':c===4}" @click="calSel=c">{{ c }}</button></template>
                </div>
                <div class="cal-time"><Icon name="clock" :size="15" class="muted"/><input class="inp" style="max-width:120px" v-model="calTime"><span class="t-meta">24h</span></div>
              </div>
            </section>

            <!-- POPOVER / MODAL -->
            <section class="lib-block">
              <div class="lib-block-h"><h3 class="t-title t-h3">Popover &amp; Modal</h3><span class="t-meta">overlay layer</span></div>
              <div class="lib-grid">
                <div class="lib-spec"><span class="lib-spec-l">Popover</span>
                  <div class="lib-pop-demo"><div class="lib-pop"><div class="lib-pop-arrow"></div><strong style="font-size:13px">Field settings</strong><p class="t-meta" style="margin:4px 0 0">Anchored, dismiss on outside-click. Shadow-md, 8px radius.</p></div></div>
                </div>
                <div class="lib-spec"><span class="lib-spec-l">Modal</span>
                  <div class="lib-modal-demo"><div class="lib-modal"><div class="lib-modal-bar"><strong style="font-size:13px">Crop image</strong><Icon name="x" :size="14" class="muted"/></div><div class="lib-modal-body"></div></div></div>
                </div>
              </div>
            </section>
          </template>

          <!-- ============ RELATION ============ -->
          <template v-else-if="sec==='relation'">
            <div class="lib-head"><h2 class="t-title t-h2">Relation picker</h2><p class="page-sub"><span class="t-key">post_object</span> · <span class="t-key">relationship</span> · <span class="t-key">taxonomy</span> · <span class="t-key">user</span>. Built on Combobox. <span class="lib-flag">custom design</span></p></div>

            <section class="lib-block">
              <div class="lib-block-h"><h3 class="t-title t-h3">relationship</h3><span class="t-meta">chips · drag-reorder · min {{ relMin }} / max {{ relMax }}</span></div>
              <div class="rel" :class="{'is-error':relTooMany}">
                <div class="rel-list" @dragover.prevent @drop="rDrop">
                  <div v-for="(id,i) in relChips" :key="id" class="rel-item" :class="{'is-dragging':relDrag===i,'is-over':relOver===i&&relDrag!==i}"
                       draggable="true" @dragstart="rStart(i)" @dragend="relDrag=null;relOver=null" @dragover="rOver(i,$event)" @drop="rDrop">
                    <Icon name="drag" :size="14" class="drag-handle"/>
                    <span class="rel-thumb" :style="{background:grad(hue(id))}"></span>
                    <div class="rel-item-main"><strong>{{ byId(id).title }}</strong><span class="t-meta">{{ byId(id).type }}</span></div>
                    <button class="iconbtn iconbtn--sm is-danger" @click="rRemove(id)"><Icon name="x" :size="13"/></button>
                  </div>
                </div>
                <button class="rel-add-btn"><Icon name="plus" :size="14"/> Add related items</button>
                <div class="rel-foot"><span class="t-meta">{{ relChips.length }} selected</span><span v-if="relTooMany" class="lib-err">Max {{ relMax }} — remove {{ relChips.length-relMax }}.</span><span v-else-if="relChips.length<relMin" class="lib-err">Select at least {{ relMin }}.</span></div>
              </div>
            </section>

            <div class="lib-grid">
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">post_object (single)</h4></div>
                <div class="rel-item" style="cursor:default"><span class="rel-thumb" :style="{background:grad(32)}"></span><div class="rel-item-main"><strong>Digital Twins Helpdesk</strong><span class="t-meta">Project</span></div><button class="btn btn--ghost btn--sm">Change</button></div>
              </section>
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">user</h4></div>
                <div class="rel-item" style="cursor:default"><span class="avatar" style="width:30px;height:30px;font-size:11px">MT</span><div class="rel-item-main"><strong>Marten Timan</strong><span class="t-meta">marten@atypisch.nl</span></div><button class="btn btn--ghost btn--sm">Change</button></div>
              </section>
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">taxonomy</h4></div>
                <div class="flex gap-2 wrap"><span class="tag-chip is-on">Yoga</span><span class="tag-chip is-on">Breathwork</span><span class="tag-chip">Mindfulness</span><span class="tag-chip">Wellness</span></div>
              </section>
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">Empty</h4></div>
                <div class="rel-empty">No related items yet.</div>
              </section>
            </div>
          </template>

          <!-- ============ MEDIA ============ -->
          <template v-else-if="sec==='media'">
            <div class="lib-head"><h2 class="t-title t-h2">Media fields</h2><p class="page-sub"><span class="t-key">image</span> · <span class="t-key">gallery</span> · <span class="t-key">file</span> · <span class="t-key">audio</span> · <span class="t-key">video</span>. Via MediaPicker + Cropper. <span class="lib-flag">custom design</span></p></div>
            <div class="lib-grid">
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">image · filled</h4></div>
                <div class="mf-image" :style="{backgroundImage:grad(32)}"><div class="mf-image-actions"><button class="btn btn--soft btn--xs"><Icon name="search" :size="12"/> Crop</button><button class="btn btn--soft btn--xs"><Icon name="x" :size="12"/> Remove</button></div></div>
              </section>
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">image · empty</h4></div>
                <button class="mf-drop"><Icon name="image" :size="22"/><strong>Drop an image</strong><span class="t-meta">or choose from the library</span></button>
              </section>
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">gallery (multi · sortable)</h4></div>
                <div class="mf-gallery"><span v-for="h in [28,32,18,210]" :key="h" class="mf-gcell" :style="{backgroundImage:grad(h)}"><span class="mf-gcell-grip"><Icon name="drag" :size="13"/></span></span><button class="mf-gadd"><Icon name="plus" :size="18"/></button></div>
              </section>
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">file · loading</h4></div>
                <div class="mf-file"><span class="cbx-spin"></span><div class="rel-item-main"><strong>brochure.pdf</strong><span class="t-meta">Uploading… 64%</span></div></div>
                <div class="mf-progress"><span style="width:64%"></span></div>
              </section>
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">audio</h4></div>
                <div class="mf-audio"><button class="mf-play"><Icon name="play" :size="15"/></button><div class="mf-wave"><span v-for="n in 28" :key="n" :style="{height:(20+Math.abs(Math.sin(n))*60)+'%'}"></span></div><span class="t-meta mono">2:14</span></div>
              </section>
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">video</h4></div>
                <div class="mf-video" :style="{backgroundImage:grad(210)}"><span class="ve-play" style="width:46px;height:46px"><Icon name="play" :size="18"/></span></div>
              </section>
            </div>
          </template>

          <!-- ============ DATETIME ============ -->
          <template v-else-if="sec==='datetime'">
            <div class="lib-head"><h2 class="t-title t-h2">Date &amp; time</h2><p class="page-sub"><span class="t-key">date</span> · <span class="t-key">time</span> · <span class="t-key">datetime</span>. Via Calendar. <span class="lib-flag">custom design</span></p></div>
            <div class="lib-grid">
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">date · closed</h4></div>
                <div class="dt-control"><Icon name="calendar" :size="15" class="muted"/><span>12 June 2026</span><Icon name="chevDown" :size="15" class="muted" style="margin-left:auto"/></div>
              </section>
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">time</h4></div>
                <div class="dt-control"><Icon name="clock" :size="15" class="muted"/><span>18:00</span></div>
              </section>
              <section class="lib-block"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">empty</h4></div>
                <div class="dt-control is-empty"><Icon name="calendar" :size="15" class="muted"/><span class="cbx-ph">Pick a date…</span></div>
              </section>
            </div>
            <section class="lib-block" style="max-width:320px"><div class="lib-block-h"><h4 class="t-title" style="font-size:14px;margin:0">datetime · open</h4></div>
              <div class="cal">
                <div class="cal-head"><button class="iconbtn iconbtn--sm" @click="calMonth=(calMonth+11)%12"><Icon name="chevLeft" :size="16"/></button><strong>{{ monthName() }}</strong><button class="iconbtn iconbtn--sm" @click="calMonth=(calMonth+1)%12"><Icon name="chevRight" :size="16"/></button></div>
                <div class="cal-dow"><span v-for="d in ['Mo','Tu','We','Th','Fr','Sa','Su']" :key="d">{{ d }}</span></div>
                <div class="cal-grid"><template v-for="(c,i) in calCells" :key="i"><span v-if="c===null" class="cal-cell cal-cell--empty"></span><button v-else class="cal-cell" :class="{'is-sel':calSel===c}" @click="calSel=c">{{ c }}</button></template></div>
                <div class="cal-time"><Icon name="clock" :size="15" class="muted"/><input class="inp" style="max-width:120px" v-model="calTime"></div>
              </div>
            </section>
          </template>

          <!-- ============ DYNAMIC ============ -->
          <template v-else-if="sec==='dynamic'">
            <div class="lib-head"><h2 class="t-title t-h2">Embed &amp; dynamic</h2><p class="page-sub"><span class="t-key">embed</span> (oEmbed) · <span class="t-key">latest_posts</span> (query). <span class="lib-flag">custom design</span></p></div>
            <section class="lib-block"><div class="lib-block-h"><h3 class="t-title t-h3">embed</h3><span class="t-meta">URL → live preview</span></div>
              <div class="field" style="max-width:520px"><label class="field-label">URL</label><div class="inp-group"><span class="affix"><Icon name="link" :size="14"/></span><input class="inp mono" v-model="embedUrl"></div></div>
              <div class="embed-card">
                <div class="embed-thumb" :style="{backgroundImage:grad(0)}"><span class="ve-play" style="width:46px;height:46px"><Icon name="play" :size="18"/></span></div>
                <div class="embed-meta"><span class="badge"><Icon name="play" :size="10"/> YouTube</span><strong>Never Gonna Give You Up</strong><span class="t-meta">youtube.com · 3:33</span></div>
              </div>
            </section>
            <section class="lib-block"><div class="lib-block-h"><h3 class="t-title t-h3">latest_posts</h3><span class="t-meta">query → preview</span></div>
              <div class="lp">
                <div class="lp-query">
                  <div class="field"><label class="field-label">Post type</label><select class="sel" v-model="lp.type"><option value="post">Posts</option><option value="project">Projects</option><option value="product">Products</option></select></div>
                  <div class="field"><label class="field-label">Taxonomy</label><select class="sel" v-model="lp.tax"><option value="all">Any</option><option>Yoga</option><option>Wellness</option></select></div>
                  <div class="field"><label class="field-label">Limit</label><input class="inp mono" type="number" v-model.number="lp.limit"></div>
                  <div class="field"><label class="field-label">Sort</label><select class="sel" v-model="lp.sort"><option value="date_desc">Newest first</option><option value="date_asc">Oldest first</option><option value="title">Title A–Z</option></select></div>
                </div>
                <div class="lp-preview">
                  <div class="lp-preview-h"><Icon name="eye" :size="13"/> Preview · {{ lp.limit }} {{ lp.type }}s</div>
                  <div v-for="n in lp.limit" :key="n" class="lp-row"><span class="rel-thumb" :style="{background:grad(28+n*22)}"></span><div class="rel-item-main"><strong>{{ ['Headless without the headache','EU-hosted by default','Living-in.EU','MydayPrice','Calm by default'][n-1] }}</strong><span class="t-meta">Jun {{ 4-n }}, 2026</span></div></div>
                </div>
              </div>
            </section>
          </template>

          <!-- ============ CONTAINERS ============ -->
          <template v-else-if="sec==='containers'">
            <div class="lib-head"><h2 class="t-title t-h2">Containers</h2><p class="page-sub">Nesting chrome. <span class="t-key">tab</span> · <span class="t-key">columns</span> · <span class="t-key">accordion</span> · <span class="t-key">group</span> · <span class="t-key">repeater</span>. <span class="lib-flag">custom design</span></p></div>

            <section class="lib-block"><div class="lib-block-h"><h3 class="t-title t-h3">tab</h3><span class="t-meta">splits the editor into sections</span></div>
              <div class="ctab"><div class="ctab-bar"><button v-for="(t,i) in ['Content','Media','SEO']" :key="t" :class="{'is-on':tab===i}" @click="tab=i">{{ t }}</button></div><div class="ctab-body"><div class="field" style="margin:0"><label class="field-label">{{ ['Title','Featured image','Meta description'][tab] }}</label><input class="inp" :placeholder="['Add a title…','—','For search engines…'][tab]"></div></div></div>
            </section>

            <section class="lib-block"><div class="lib-block-h"><h3 class="t-title t-h3">columns</h3><div class="seg" style="margin-left:auto"><button v-for="n in [1,2,3]" :key="n" :class="{'is-on':cols===n}" @click="cols=n">{{ n }}</button></div></div>
              <div class="ccols" :style="{gridTemplateColumns:'repeat('+cols+',1fr)'}"><div v-for="n in cols" :key="n" class="ccol"><div class="field" style="margin:0"><label class="field-label">Column {{ n }}</label><input class="inp" placeholder="Field…"></div></div></div>
              <p class="lib-note"><Icon name="info" :size="13"/> Below 640px columns stack to a single column.</p>
            </section>

            <section class="lib-block"><div class="lib-block-h"><h3 class="t-title t-h3">accordion</h3></div>
              <div class="cacc"><div v-for="(s,i) in ['General','Layout','Advanced']" :key="s" class="cacc-item"><button class="cacc-head" :class="{'is-open':accOpen===i}" @click="accOpen=accOpen===i?-1:i"><Icon name="chevRight" :size="15" class="cacc-caret"/>{{ s }}</button><div v-if="accOpen===i" class="cacc-body"><div class="field" style="margin:0"><label class="field-label">A field in {{ s }}</label><input class="inp"></div></div></div></div>
            </section>

            <section class="lib-block"><div class="lib-block-h"><h3 class="t-title t-h3">group</h3><span class="t-meta">fixed subset as one block</span></div>
              <div class="cgroup"><div class="cgroup-h"><Icon name="box" :size="14"/> Address</div><div class="cgroup-body cfg-2"><div class="field" style="margin:0"><label class="field-label">Street</label><input class="inp"></div><div class="field" style="margin:0"><label class="field-label">City</label><input class="inp"></div></div></div>
            </section>

            <section class="lib-block"><div class="lib-block-h"><h3 class="t-title t-h3">repeater</h3><span class="t-meta">add · reorder · collapse · duplicate · delete</span></div>
              <div class="rep">
                <div v-for="(it,i) in repItems" :key="it.id" class="rep-item">
                  <div class="rep-bar"><Icon name="drag" :size="15" class="drag-handle"/><button class="rep-caret" :class="{'is-open':it.open}" @click="it.open=!it.open"><Icon name="chevRight" :size="14"/></button><span class="rep-title">{{ it.q || 'Untitled row' }}</span><span class="rep-n">{{ i+1 }}</span><div class="rep-tools"><button class="iconbtn iconbtn--sm" @click="repDup(i)"><Icon name="copy" :size="13"/></button><button class="iconbtn iconbtn--sm is-danger" @click="repDel(i)"><Icon name="trash" :size="13"/></button></div></div>
                  <div v-if="it.open" class="rep-body"><div class="field"><label class="field-label">Question</label><input class="inp" v-model="it.q"></div><div class="field" style="margin:0"><label class="field-label">Answer</label><textarea class="ta" v-model="it.a" style="min-height:56px"></textarea></div></div>
                </div>
                <button class="rep-add" @click="repAdd"><Icon name="plus" :size="15"/> Add row</button>
              </div>
            </section>
          </template>

          <!-- ============ COMPOSER ============ -->
          <template v-else-if="sec==='composer'">
            <div class="lib-head"><h2 class="t-title t-h2">Block composer <span class="lib-flag lib-flag--star">pronkstuk</span></h2><p class="page-sub">The <span class="t-key">blocks</span> field — drop allowed components, reorder, collapse, duplicate. One concept replaces repeater + flexible content + clone.</p></div>
            <section class="lib-block">
              <div class="bc" @dragover.prevent @drop="blkDrop">
                <div v-for="(b,i) in blocks" :key="b.id" class="bc-block" :class="{'is-dragging':blkDrag===i,'is-over':blkOver===i&&blkDrag!==i}"
                     draggable="true" @dragstart="blkStart(i)" @dragend="blkDrag=null;blkOver=null" @dragover="blkOverFn(i,$event)" @drop="blkDrop">
                  <div class="bc-bar"><Icon name="drag" :size="15" class="drag-handle"/><span class="bc-ico"><Icon :name="comp(b.comp).icon" :size="15"/></span><button class="rep-caret" :class="{'is-open':b.open}" @click="b.open=!b.open"><Icon name="chevRight" :size="14"/></button><span class="bc-name">{{ comp(b.comp).name }}</span><span class="badge"><Icon name="component" :size="10"/> v{{ comp(b.comp).version }}</span><div class="grow"></div><div class="bc-tools"><button class="iconbtn iconbtn--sm" @click="blkDup(i)" title="Duplicate"><Icon name="copy" :size="13"/></button><button class="iconbtn iconbtn--sm is-danger" @click="blkDel(i)"><Icon name="trash" :size="13"/></button></div></div>
                  <div v-if="b.open" class="bc-body"><div class="field" style="margin:0"><label class="field-label">{{ comp(b.comp).name }} fields</label><div class="bc-fieldrow" v-for="f in (comp(b.comp).fields||[]).slice(0,3)" :key="f.id||f.label"><span class="t-meta">{{ f.label||'Field' }}</span><span class="inp inp--ghost"></span></div><div v-if="!(comp(b.comp).fields||[]).length" class="t-meta">No fields on this component.</div></div></div>
                </div>
                <button class="bc-add" @click="blkPicker=true"><Icon name="plus" :size="16"/> Add block</button>
              </div>
            </section>
            <p class="lib-note"><Icon name="info" :size="13"/> Empty state shows the dashed “Add block” target full-width; while dragging, a 2px accent rule marks the drop slot and the row dims.</p>

            <!-- allowed picker -->
            <template v-if="blkPicker">
              <div class="scrim" @click="blkPicker=false" style="z-index:120"></div>
              <div class="bc-picker">
                <div class="drawer-head"><Icon name="grid" :size="18"/><strong style="font-size:15px;flex:1">Add a block</strong><button class="iconbtn" @click="blkPicker=false"><Icon name="x"/></button></div>
                <div class="bc-picker-grid">
                  <button v-for="c in allowedComps" :key="c.id" class="bc-pick" @click="blkAdd(c.id)"><span class="bc-pick-ico"><Icon :name="c.icon" :size="18"/></span><strong>{{ c.name }}</strong><span class="t-meta">{{ (c.fields||[]).length }} fields</span></button>
                </div>
                <div class="bc-picker-foot t-meta"><Icon name="info" :size="12"/> Only components on the field's <span class="t-key">allowed</span> list appear here.</div>
              </div>
            </template>
          </template>

          <!-- ============ RICHTEXT ============ -->
          <template v-else-if="sec==='richtext'">
            <div class="lib-head"><h2 class="t-title t-h2">Rich text</h2><p class="page-sub">Tiptap engine, Atelier chrome. <span class="lib-flag">custom design</span></p></div>
            <section class="lib-block">
              <div class="rt">
                <div class="rt-toolbar">
                  <button class="rt-btn is-on" style="font-weight:700">B</button><button class="rt-btn" style="font-style:italic">I</button><button class="rt-btn" style="text-decoration:underline">U</button>
                  <span class="rt-sep"></span>
                  <button class="rt-btn">H2</button><button class="rt-btn">H3</button><button class="rt-btn"><Icon name="list" :size="15"/></button>
                  <span class="rt-sep"></span>
                  <button class="rt-btn"><Icon name="link" :size="15"/></button><button class="rt-btn"><Icon name="image" :size="15"/></button><button class="rt-btn"><Icon name="code" :size="15"/></button>
                </div>
                <div class="rt-body">
                  <h3>The control room</h3>
                  <p>One screen, every twin. <span class="rt-selection">Calm by default</span>, loud only when it matters.</p>
                  <!-- bubble menu over the selection -->
                  <div class="rt-bubble"><button style="font-weight:700">B</button><button style="font-style:italic">I</button><button><Icon name="link" :size="13"/></button><button>H2</button></div>
                </div>
              </div>
              <div class="lib-grid" style="margin-top:16px">
                <div class="lib-spec"><span class="lib-spec-l">Slash menu (insert)</span>
                  <div class="rt-slash"><div class="rt-slash-q">/ <span class="cbx-ph">image</span></div><button class="rt-slash-opt is-sel"><span class="lib-ico"><Icon name="image" :size="14"/></span> Image</button><button class="rt-slash-opt"><span class="lib-ico"><Icon name="play" :size="14"/></span> Video embed</button><button class="rt-slash-opt"><span class="lib-ico"><Icon name="code" :size="14"/></span> Code block</button></div>
                </div>
                <div class="lib-spec"><span class="lib-spec-l">Link popover</span>
                  <div class="rt-linkpop"><div class="inp-group"><span class="affix"><Icon name="link" :size="13"/></span><input class="inp mono" value="/work/digital-twins"></div><div class="flex gap-2" style="margin-top:8px"><button class="btn btn--accent btn--sm">Apply</button><button class="btn btn--ghost btn--sm">Remove</button></div></div>
                </div>
              </div>
            </section>
          </template>

          <!-- ============ SETTINGS PANEL ============ -->
          <template v-else-if="sec==='settings'">
            <div class="lib-head"><h2 class="t-title t-h2">Generated settings panel</h2><p class="page-sub">A field's gear panel is generated from a list of sub-fields, so it scales to any type. Same pattern, different schema.</p></div>
            <div class="flex gap-2" style="margin-bottom:16px"><button v-for="t in ['text','relationship','blocks']" :key="t" class="btn btn--sm" :class="settingsType===t?'btn--accent':'btn--ghost'" @click="settingsType=t"><span class="t-key">{{ t }}</span></button></div>
            <section class="lib-block" style="max-width:420px">
              <div class="sp">
                <div class="sp-head"><span class="lib-ico"><Icon :name="settingsType==='blocks'?'grid':settingsType==='relationship'?'git':'type'" :size="15"/></span><strong>{{ settingsType }} settings</strong></div>
                <div class="sp-body">
                  <div class="field"><label class="field-label">Label</label><input class="inp" value="My field"></div>
                  <div class="field"><label class="field-label">API key</label><div class="inp-group"><span class="affix">name</span><input class="inp mono" value="my_field"></div></div>
                  <div class="field"><label class="field-label">Instructions</label><textarea class="ta" style="min-height:48px"></textarea></div>
                  <template v-if="settingsType==='text'">
                    <div class="cfg-2"><div class="field" style="margin:0"><label class="field-label">Max length</label><input class="inp mono" value="120"></div><div class="field" style="margin:0"><label class="field-label">Default</label><input class="inp"></div></div>
                  </template>
                  <template v-else-if="settingsType==='relationship'">
                    <div class="field"><label class="field-label">Related type</label><select class="sel"><option>Project</option><option>Post</option></select></div>
                    <div class="cfg-2"><div class="field" style="margin:0"><label class="field-label">Min</label><input class="inp mono" value="1"></div><div class="field" style="margin:0"><label class="field-label">Max</label><input class="inp mono" value="4"></div></div>
                  </template>
                  <template v-else>
                    <div class="field" style="margin:0"><label class="field-label">Allowed blocks</label><div class="flex gap-2 wrap"><span class="tag-chip is-on">Hero</span><span class="tag-chip is-on">Rich text</span><span class="tag-chip is-on">Gallery</span><span class="tag-chip">Quote</span><span class="tag-chip">+ add</span></div></div>
                  </template>
                  <div class="opt-row" style="padding:12px 0 0;border-top:1px solid var(--line);margin-top:6px"><div><strong>Required</strong></div><UiSwitch :modelValue="settingsType==='relationship'"/></div>
                </div>
              </div>
              <p class="lib-note"><Icon name="info" :size="13"/> The first three rows (Label, API key, Instructions, Required) are shared; only the middle block is type-specific.</p>
            </section>
          </template>

        </div>
      </div>
    </div>`,
  };
})();
;
/* file: screen-design.js */
/* ============================================================
   DESIGN SYSTEM — "Atelier", now a real, live editor.
   Every token edits the running admin and saves to your browser;
   export/import a .json file to version or share the theme.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const TH = () => window.__mdlr.theme;

  C.ScreenDesignSystem = {
    name: 'ScreenDesignSystem',
    data() {
      return {
        t: TH().state,
        presets: TH().ACCENT_PRESETS,
        fontsDisplay: TH().FONT_DISPLAY,
        fontsUi: TH().FONT_UI,
        neutrals: [ ['paper','Canvas'], ['surface','Card'], ['ink','Text'], ['ink2','Text 2'], ['line','Hairline'] ],
        statusTokens: [ ['ok','Success'], ['warn','Warning'], ['danger','Danger'], ['info','Info'] ],
        catTokens: [ ['catBasic','Basic'], ['catContent','Content'], ['catChoice','Choice'], ['catRel','Relational'], ['catMedia','Media'], ['catDate','Date'], ['catLayout','Layout'] ],
        saved: false, _t: null,
      };
    },
    computed: {
      dirty() { return !TH().isDefault(); },
    },
    methods: {
      set(k, v) { TH().set(k, v); this.flash(); },
      pickAccent(hex) { this.set('accent', hex); },
      flash() { this.saved = true; clearTimeout(this._t); this._t = setTimeout(() => { this.saved = false; }, 1400); },
      exportJson() { TH().exportJson(); },
      triggerImport() { this.$refs.file.click(); },
      onImport(e) { const f = e.target.files[0]; if (f) TH().importFile(f).then(() => this.flash()).catch(() => alert('Could not read that theme file.')); e.target.value = ''; },
      reset() { TH().reset(); this.flash(); },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px">Design system · this admin</div>
          <h1 class="t-title t-h1">Atelier</h1>
          <p class="page-sub" style="margin-top:6px">The live design system for the <strong style="color:var(--ink-2)">Modular admin</strong>. Every value below edits the running interface and is saved to this browser. Export a <span class="t-key">.json</span> file to version or share it.</p>
        </div>
        <div class="flex gap-2 items-center">
          <span class="ds-saved" v-if="saved"><Icon name="check" :size="14"/> Saved</span>
          <input ref="file" type="file" accept="application/json,.json" hidden @change="onImport">
          <button class="btn btn--ghost btn--sm" @click="triggerImport"><Icon name="upload" :size="14"/> Import</button>
          <button class="btn btn--ghost btn--sm" @click="exportJson"><Icon name="files" :size="14"/> Export JSON</button>
          <button class="btn btn--ghost btn--sm" :disabled="!dirty" @click="reset"><Icon name="refresh" :size="14"/> Reset</button>
        </div>
      </div>

      <div class="ds-edit">
        <!-- BRAND / COLOR -->
        <section class="ds-card card">
          <div class="ds-card-h"><Icon name="palette" :size="16"/><strong>Brand &amp; colour</strong></div>
          <div class="ds-card-b">
            <label class="field-label">Accent</label>
            <div class="ds-accent-row">
              <button v-for="p in presets" :key="p.id" class="ds-sw" :class="{'is-on': t.accent.toLowerCase()===p.hex.toLowerCase()}" :style="{background:p.hex}" :title="p.label" @click="pickAccent(p.hex)"><Icon v-if="t.accent.toLowerCase()===p.hex.toLowerCase()" name="check" :size="14"/></button>
              <label class="ds-custom"><input type="color" :value="t.accent" @input="set('accent',$event.target.value)"><span class="t-key">{{ t.accent }}</span></label>
            </div>

            <label class="field-label" style="margin-top:18px">Neutrals</label>
            <div class="ds-tokens">
              <div class="ds-token" v-for="n in neutrals" :key="n[0]">
                <label class="ds-token-sw" :style="{background:t[n[0]]}"><input type="color" :value="t[n[0]]" @input="set(n[0],$event.target.value)"></label>
                <div class="ds-token-meta"><strong>{{ n[1] }}</strong><input class="inp mono ds-hex" :value="t[n[0]]" @change="set(n[0],$event.target.value)"></div>
              </div>
            </div>

            <label class="field-label" style="margin-top:18px">Primary action</label>
            <div class="ds-tokens">
              <div class="ds-token">
                <label class="ds-token-sw" :style="{background:t.btn}"><input type="color" :value="t.btn" @input="set('btn',$event.target.value)"></label>
                <div class="ds-token-meta"><strong>Button</strong><input class="inp mono ds-hex" :value="t.btn" @change="set('btn',$event.target.value)"></div>
              </div>
            </div>
          </div>
        </section>

        <!-- TYPOGRAPHY -->
        <section class="ds-card card">
          <div class="ds-card-h"><Icon name="type" :size="16"/><strong>Typography</strong></div>
          <div class="ds-card-b">
            <div class="cfg-2">
              <div class="field"><label class="field-label">Display font</label><UiSelect :modelValue="t.fontDisplay" @update:modelValue="v=>set('fontDisplay',v)"><option v-for="f in fontsDisplay" :key="f">{{ f }}</option></UiSelect></div>
              <div class="field"><label class="field-label">Body font</label><UiSelect :modelValue="t.fontUi" @update:modelValue="v=>set('fontUi',v)"><option v-for="f in fontsUi" :key="f">{{ f }}</option></UiSelect></div>
            </div>
            <label class="field-label" style="margin-top:6px">Base size · {{ t.baseSize }}px</label>
            <input type="range" min="13" max="17" step="1" :value="t.baseSize" @input="set('baseSize',+$event.target.value)" style="width:100%;accent-color:var(--accent)">
            <div class="ds-type-spec">
              <span class="t-display" style="font-size:40px">Modeling, made calm</span>
              <span class="t-title t-h3" style="margin-top:10px;display:block">Section heading</span>
              <span style="display:block;margin-top:6px">Body — the resolved, effective fieldset for this item, with provenance.</span>
            </div>
          </div>
        </section>

        <!-- SHAPE -->
        <section class="ds-card card">
          <div class="ds-card-h"><Icon name="box" :size="16"/><strong>Shape</strong></div>
          <div class="ds-card-b">
            <label class="field-label">Corner radius · {{ t.radius }}px</label>
            <input type="range" min="0" max="16" step="1" :value="t.radius" @input="set('radius',+$event.target.value)" style="width:100%;accent-color:var(--accent)">
            <div class="flex gap-3 wrap" style="margin-top:16px">
              <button class="btn btn--accent">Accent</button>
              <button class="btn">Primary</button>
              <button class="btn btn--ghost">Ghost</button>
              <span class="badge badge--ok">published</span>
              <span class="chk is-on"><Icon name="check"/></span>
            </div>
            <div class="flex gap-3 wrap" style="margin-top:14px;align-items:flex-end">
              <div style="width:200px"><input class="inp" placeholder="Text input"></div>
              <div class="radii-box" style="width:48px;height:48px;background:var(--accent-soft);border:1px solid var(--line-2)"></div>
            </div>
          </div>
        </section>

        <!-- STATUS COLOURS -->
        <section class="ds-card card">
          <div class="ds-card-h"><Icon name="info" :size="16"/><strong>Status colours</strong></div>
          <div class="ds-card-b">
            <div class="ds-tokens">
              <div class="ds-token" v-for="s in statusTokens" :key="s[0]">
                <label class="ds-token-sw" :style="{background:t[s[0]]}"><input type="color" :value="t[s[0]]" @input="set(s[0],$event.target.value)"></label>
                <div class="ds-token-meta"><strong>{{ s[1] }}</strong><input class="inp mono ds-hex" :value="t[s[0]]" @change="set(s[0],$event.target.value)"></div>
                <span class="badge" :class="'badge--'+s[0]">{{ s[1].toLowerCase() }}</span>
              </div>
            </div>
          </div>
        </section>

        <!-- FIELD CATEGORY HUES -->
        <section class="ds-card card">
          <div class="ds-card-h"><Icon name="type" :size="16"/><strong>Field categories</strong></div>
          <div class="ds-card-b">
            <p class="field-hint" style="margin:0 0 12px">The muted drawer-label hues used across the field builder.</p>
            <div class="ds-tokens">
              <div class="ds-token" v-for="c in catTokens" :key="c[0]">
                <label class="ds-token-sw" :style="{background:t[c[0]]}"><input type="color" :value="t[c[0]]" @input="set(c[0],$event.target.value)"></label>
                <div class="ds-token-meta"><strong>{{ c[1] }}</strong><input class="inp mono ds-hex" :value="t[c[0]]" @change="set(c[0],$event.target.value)"></div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- LIVE PREVIEW: a real card UI built from the tokens -->
      <section class="ds-sec">
        <h2 class="t-title t-h3">Live preview</h2>
        <div class="card card-pad">
          <div class="flex items-center justify-between" style="margin-bottom:14px">
            <div><div class="t-eyebrow">Project</div><strong style="font-family:var(--font-display);font-size:18px">Digital Twins Helpdesk</strong></div>
            <span class="badge badge--ok"><span class="badge--dot"></span>published</span>
          </div>
          <p style="color:var(--ink-2);max-width:60ch;margin:0 0 16px">A grounded helpdesk built on living digital twins — written for the people who run the building. This whole card recolours and reshapes as you edit the tokens above.</p>
          <div class="flex gap-2">
            <button class="btn btn--accent"><Icon name="plus" :size="15"/> New</button>
            <button class="btn btn--ghost">Preview</button>
            <span class="prov prov--type"><Icon name="lock" :size="11"/> From post type</span>
            <span class="modepill modepill--modular"><Icon name="layers" :size="11"/> Modular</span>
          </div>
        </div>
      </section>
    </div>`,
  };
})();
;
/* file: screen-module-page.js */
/* Module admin page host — renders a module's PHP admin page (module-page.php)
   inside the SPA via an embed-mode iframe, so the new sidebar/topbar surround
   it and the module page picks up the Atelier design tokens. */
(function () {
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const C = M.comps;

  C.ScreenModulePage = {
    name: 'ScreenModulePage',
    inject: ['route', 'go'],
    computed: {
      src() {
        const u = (this.route.params || {}).url || '';
        if (!u) return '';
        return u + (u.indexOf('?') >= 0 ? '&' : '?') + 'embed=1';
      },
      title() { return (this.route.params || {}).title || 'Module'; },
    },
    template: `
    <div class="page page--wide fade-up mod-host">
      <div class="page-head" style="margin-bottom:14px">
        <div><div class="t-eyebrow" style="margin-bottom:8px">Module</div><h1 class="t-title t-h1">{{ title }}</h1></div>
        <a class="btn btn--ghost btn--sm" :href="src" target="_blank" rel="noopener"><Icon name="external" :size="15"/> Open full page</a>
      </div>
      <iframe v-if="src" :src="src" class="mod-frame" title="Module page"></iframe>
      <div v-else class="empty" style="padding:64px"><Icon name="box" :size="28"/><div>No module page.</div></div>
    </div>`,
  };
})();
;
/* file: screen-system-update.js */
/* ============================================================
   SYSTEM UPDATE — one central upgrade point for the Modular core.
   Shows the installed version, checks the GitHub release (the
   deployable SSOT), and runs the update with one button. Backed by
   GET/POST /api/v1/system/update (UpdateController + Modular\Update).
   ============================================================ */
(function () {
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const C = M.comps;

  C.ScreenSystemUpdate = {
    name: 'ScreenSystemUpdate',
    data() { return { loading: true, checking: false, updating: false, error: '', done: '', status: null }; },
    mounted() { this.load(); },
    computed: {
      current() { return (this.status && this.status.current) || {}; },
      latest() { return this.status && this.status.latest; },
      available() { return !!(this.status && this.status.update_available); },
      history() { return (this.status && this.status.history) || []; },
    },
    methods: {
      async load() {
        this.loading = true; this.error = '';
        try { this.status = await M.api.get('/system/update'); }
        catch (e) { this.error = (e && e.data && e.data.error) || 'Could not load update status.'; }
        this.loading = false;
      },
      async check() {
        this.checking = true; this.done = '';
        await this.load();
        this.checking = false;
      },
      async runUpdate() {
        if (this.updating) return;
        if (!window.confirm('Update the Modular core to v' + (this.latest && this.latest.version) + '? A backup is taken first; the app will reload when done.')) return;
        this.updating = true; this.error = ''; this.done = '';
        try {
          const r = await M.api.post('/system/update', {});
          this.done = 'Updated to v' + (r.version_to || (this.latest && this.latest.version)) + '. Reloading…';
          setTimeout(() => window.location.reload(), 1400);
        } catch (e) {
          this.error = (e && e.data && e.data.error) || 'Update failed. Check the server logs.';
          this.updating = false;
        }
      },
      fmt(d) { return d ? String(d).slice(0, 16).replace('T', ' ') : ''; },
    },
    template: `
    <div class="page page--wide fade-up" style="max-width:860px">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px">System</div>
          <h1 class="t-title t-h1">System update</h1>
          <p class="page-sub" style="margin-top:6px">One central upgrade point. The GitHub release is the source of truth; update the whole core from here.</p>
        </div>
        <button class="btn btn--ghost" :disabled="checking||loading" @click="check"><Icon name="refresh" :size="15"/> {{ checking ? 'Checking…' : 'Check for updates' }}</button>
      </div>

      <div v-if="loading" class="card card-pad t-meta">Loading update status…</div>

      <template v-else>
        <!-- installed version + status -->
        <div class="card card-pad" style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">
          <div style="flex:1;min-width:200px">
            <div class="t-meta">Installed version</div>
            <div style="font-family:var(--font-display);font-weight:600;font-size:30px;letter-spacing:-0.02em;line-height:1.1">v{{ current.version || '—' }}</div>
            <div class="t-meta" style="margin-top:4px">{{ current.name || 'Modular CMS' }}<span v-if="current.build_date"> · built {{ current.build_date }}</span><span v-if="status.repo"> · {{ status.repo }}</span></div>
          </div>
          <div>
            <span v-if="available" class="badge badge--warn" style="font-size:13px"><Icon name="zap" :size="13"/> Update available</span>
            <span v-else-if="status.check_error" class="badge" style="font-size:13px"><Icon name="info" :size="13"/> Couldn’t check</span>
            <span v-else class="badge badge--ok" style="font-size:13px"><Icon name="check" :size="13"/> Up to date</span>
          </div>
        </div>

        <div v-if="error" class="card card-pad" style="margin-top:14px;border-color:var(--danger);color:var(--danger)"><Icon name="info" :size="15"/> {{ error }}</div>
        <div v-if="done" class="card card-pad" style="margin-top:14px;border-color:var(--ok);color:var(--ok)"><Icon name="check" :size="15"/> {{ done }}</div>

        <!-- update-available panel -->
        <div v-if="available && latest" class="card card-pad" style="margin-top:14px">
          <div class="flex items-center justify-between gap-3" style="flex-wrap:wrap">
            <div>
              <strong class="t-title t-h2" style="display:block">v{{ latest.version }} <span class="t-meta" style="font-weight:400">{{ latest.name }}</span></strong>
              <div class="t-meta" style="margin-top:4px" v-if="latest.published_at">Released {{ fmt(latest.published_at) }}</div>
            </div>
            <button class="btn btn--accent" :disabled="updating" @click="runUpdate"><Icon name="upload" :size="15"/> {{ updating ? 'Updating…' : 'Update to v' + latest.version }}</button>
          </div>
          <div v-if="latest.body" class="rel-notes" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--line);white-space:pre-wrap;font-size:13px;color:var(--ink-2);max-height:260px;overflow:auto">{{ latest.body }}</div>
          <div v-if="latest.html_url" style="margin-top:10px"><a :href="latest.html_url" target="_blank" rel="noopener" class="mode-help-link" style="font-size:13px">View release on GitHub →</a></div>
        </div>

        <!-- check error detail -->
        <div v-if="status.check_error" class="card card-pad" style="margin-top:14px">
          <strong>Update check unavailable</strong>
          <p class="page-sub" style="margin:6px 0 0">{{ status.check_error }}</p>
          <p class="t-meta" style="margin-top:8px">Repo: <span class="mono">{{ status.repo || '(not configured)' }}</span> · mode: <span class="mono">{{ status.mode }}</span></p>
        </div>

        <!-- history -->
        <div class="card" style="margin-top:14px" v-if="history.length">
          <div class="list-toolbar"><strong>Update history</strong></div>
          <table class="tbl tbl--rows">
            <thead><tr><th>Change</th><th style="width:120px">Status</th><th style="width:160px">Started</th><th style="width:160px">Completed</th></tr></thead>
            <tbody>
              <tr v-for="h in history" :key="h.id">
                <td><span class="mono">v{{ h.version_from }}</span> → <span class="mono">v{{ h.version_to }}</span></td>
                <td><span class="badge" :class="h.status==='success'?'badge--ok':(h.status==='failed'?'':'')">{{ h.status }}</span></td>
                <td class="t-meta">{{ fmt(h.started_at) }}</td>
                <td class="t-meta">{{ fmt(h.completed_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>`,
  };
})();
;
/* file: screen-insights.js */
/* ============================================================
   DASHBOARD INSIGHTS — Updates, Analytics (Chart.js), Reports.
   Rendered inside ScreenDashboard's tabs.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const cssVar = (n) => getComputedStyle(document.documentElement).getPropertyValue(n).trim() || '#888';

  /* ---------- UPDATES ---------- */
  C.DashUpdates = {
    name: 'DashUpdates',
    data() { return { sys: D().SYSTEM, busy: false }; },
    computed: {
      modUpdates() { return D().MODULES.filter(m => m.update); },
      cmsCurrent() { return this.sys.version !== this.sys.latest; },
    },
    methods: {
      updateCms() { this.sys.version = this.sys.latest; },
      updateMod(m) { m.version = m.update; m.update = null; },
      updateAll() { this.modUpdates.slice().forEach(m => this.updateMod(m)); if (this.cmsCurrent) this.updateCms(); },
    },
    template: `
    <div>
      <!-- CMS core -->
      <div class="card card-pad upd-core" :class="{'is-ok': !cmsCurrent}">
        <div class="upd-core-l">
          <span class="upd-badge" :class="cmsCurrent?'is-due':'is-ok'"><Icon :name="cmsCurrent?'refresh':'check'" :size="20"/></span>
          <div>
            <div class="flex items-center gap-2"><strong style="font-size:16px">Modular core</strong><span class="t-key">v{{ sys.version }}</span><span class="badge badge--info" v-if="cmsCurrent">v{{ sys.latest }} available</span><span class="badge badge--ok" v-else><span class="badge--dot"></span>up to date</span></div>
            <div class="t-meta" style="margin-top:4px">{{ sys.channel }} channel · PHP {{ sys.php }} · {{ sys.db }} · checked {{ sys.lastChecked.toLowerCase() }}</div>
          </div>
        </div>
        <div class="flex gap-2">
          <button class="btn btn--ghost btn--sm"><Icon name="refresh" :size="14"/> Check again</button>
          <button class="btn btn--accent" v-if="cmsCurrent" @click="updateCms"><Icon name="upload" :size="15"/> Update to {{ sys.latest }}</button>
        </div>
      </div>

      <!-- module updates -->
      <div class="flex items-center justify-between" style="margin:24px 0 12px">
        <h2 class="t-title t-h3" style="margin:0">Module updates <span class="t-meta" style="font-weight:400">({{ modUpdates.length }})</span></h2>
        <button class="btn btn--ghost btn--sm" v-if="modUpdates.length || cmsCurrent" @click="updateAll"><Icon name="upload" :size="14"/> Update everything</button>
      </div>
      <div class="card" v-if="modUpdates.length">
        <div class="upd-row" v-for="m in modUpdates" :key="m.id">
          <span class="mod-ico"><Icon :name="m.icon" :size="18"/></span>
          <div class="mod-main">
            <strong>{{ m.name }}</strong>
            <div class="t-meta">{{ m.desc }}</div>
          </div>
          <span class="upd-ver"><span class="t-key">v{{ m.version }}</span><Icon name="arrowRight" :size="13" class="muted"/><span class="t-key" style="color:var(--accent-deep)">v{{ m.update }}</span></span>
          <button class="btn btn--soft btn--sm" @click="updateMod(m)"><Icon name="upload" :size="14"/> Update</button>
        </div>
      </div>
      <div class="empty card" v-else style="padding:36px"><Icon name="check"/><div>All modules are up to date.</div></div>

      <!-- changelog -->
      <h2 class="t-title t-h3" style="margin:28px 0 12px">Recent releases</h2>
      <div class="card">
        <div class="upd-log" v-for="c in sys.changelog" :key="c.v">
          <div class="upd-log-v"><span class="badge badge--info">v{{ c.v }}</span><span class="t-meta">{{ c.date }}</span></div>
          <p>{{ c.notes }}</p>
        </div>
      </div>
    </div>`,
  };

  /* ---------- ANALYTICS ---------- */
  C.DashAnalytics = {
    name: 'DashAnalytics',
    inject: ['go'],
    data() { return { a: null, charts: [], range: '14d', loading: true }; },
    mounted() { this.load(); },
    beforeUnmount() { this.charts.forEach(c => c && c.destroy()); },
    watch: { range() { this.load(); } },
    computed: {
      rangeDays() { return this.range === '90d' ? 90 : this.range === '30d' ? 30 : 14; },
      kpis() {
        if (!this.a || !this.a.kpis) return [];
        return this.a.kpis.map(k => ({
          label: k.label,
          value: k.value >= 1000 ? (k.value / 1000).toFixed(1) + 'k' : String(k.value),
          delta: (k.delta >= 0 ? '+' : '') + k.delta + '%',
          up: k.up,
        }));
      },
    },
    methods: {
      async load() {
        this.loading = true;
        const data = await window.__mdlr.store.loadAnalytics(this.rangeDays);
        if (data) { this.a = data; this.$nextTick(() => this.draw()); }
        this.loading = false;
      },
      drawLine() {
        if (!window.Chart || !this.$refs.line || !this.a) return;
        if (this.charts[0]) this.charts[0].destroy();
        const accent = cssVar('--accent'), ink3 = cssVar('--ink-3'), line = cssVar('--line');
        const labels = this.a.days || [];
        const posts = this.a.daily_posts || [];
        const subs = this.a.daily_submissions || [];
        this.charts[0] = new Chart(this.$refs.line, {
          type:'line',
          data:{ labels, datasets:[
            { label:'New content', data:posts, borderColor:accent, backgroundColor:'rgba(192,85,47,0.10)', fill:true, tension:0.35, pointRadius:0, borderWidth:2 },
            { label:'Submissions', data:subs, borderColor:ink3, backgroundColor:'transparent', fill:false, tension:0.35, pointRadius:0, borderWidth:1.5, borderDash:[4,4] },
          ]},
          options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:true, position:'bottom', labels:{ boxWidth:10, boxHeight:10, usePointStyle:true } } }, scales:{ x:{ grid:{ display:false }, ticks:{ autoSkip:true, maxTicksLimit:14 } }, y:{ grid:{ color:line }, border:{ display:false }, beginAtZero:true } } },
        });
      },
      draw() {
        if (!window.Chart || !this.a) return;
        this.charts.forEach(c => c && c.destroy());
        this.charts = [];
        const accent = cssVar('--accent'), ink3 = cssVar('--ink-3'), line = cssVar('--line');
        Chart.defaults.font.family = "'Hanken Grotesk', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = ink3;
        const ctxStyle = { responsive:true, maintainAspectRatio:false };
        this.charts[0] = null; this.drawLine();
        // users by role doughnut
        const palette = [accent, '#3E7C78', '#B0893F', '#5B7089', '#876089'];
        if (this.$refs.roles && this.a.users_by_role) {
          this.charts.push(new Chart(this.$refs.roles, {
            type:'doughnut',
            data:{ labels:this.a.users_by_role.labels, datasets:[{ data:this.a.users_by_role.data, backgroundColor:palette, borderWidth:2, borderColor:cssVar('--surface') }] },
            options:{ ...ctxStyle, cutout:'62%', plugins:{ legend:{ position:'right', labels:{ boxWidth:10, boxHeight:10, usePointStyle:true } } } },
          }));
        }
        // content by type bar
        if (this.$refs.bytype && this.a.by_type) {
          this.charts.push(new Chart(this.$refs.bytype, {
            type:'bar',
            data:{ labels:this.a.by_type.labels, datasets:[{ data:this.a.by_type.data, backgroundColor:accent, borderRadius:6, maxBarThickness:38 }] },
            options:{ ...ctxStyle, plugins:{ legend:{ display:false } }, scales:{ x:{ grid:{ display:false } }, y:{ grid:{ color:line }, border:{ display:false }, beginAtZero:true } } },
          }));
        }
      },
    },
    template: `
    <div>
      <div class="flex items-center gap-3" style="margin-bottom:16px">
        <span class="t-meta">Range</span>
        <div class="seg"><button :class="{'is-on':range==='14d'}" @click="range='14d'">14 days</button><button :class="{'is-on':range==='30d'}" @click="range='30d'">30 days</button><button :class="{'is-on':range==='90d'}" @click="range='90d'">90 days</button></div>
        <div class="grow"></div>
        <button class="btn btn--ghost btn--sm" @click="go('analytics',{})"><Icon name="external" :size="14"/> Open full analytics</button>
      </div>

      <div v-if="loading" class="empty" style="padding:48px"><div>Loading analytics\u2026</div></div>
      <template v-else-if="a">
        <div class="an-kpis">
          <div class="card card-pad" v-for="k in kpis" :key="k.label">
            <div class="t-meta">{{ k.label }}</div>
            <div class="stat-val t-display" style="font-size:26px">{{ k.value }}</div>
            <div class="an-delta" :class="k.up?'is-up':'is-down'"><Icon :name="k.up?'arrowUpRight':'arrowRight'" :size="13"/> {{ k.delta }}</div>
          </div>
        </div>

        <div class="an-grid">
          <section class="card card-pad an-wide">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Activity</h2><span class="t-meta">new content \u00b7 form submissions</span></div>
            <div class="chart-box"><canvas ref="line"></canvas></div>
          </section>
          <section class="card card-pad">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Users by role</h2></div>
            <div class="chart-box"><canvas ref="roles"></canvas></div>
          </section>
          <section class="card card-pad">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Content by type</h2></div>
            <div class="chart-box"><canvas ref="bytype"></canvas></div>
          </section>
          <section class="card an-wide">
            <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Recent content</h2></div>
            <table class="tbl">
              <thead><tr><th>Page</th><th style="width:200px">Path</th></tr></thead>
              <tbody>
                <tr v-for="p in a.top_pages" :key="p.path"><td><strong>{{ p.title }}</strong></td><td><span class="t-key">{{ p.path }}</span></td></tr>
              </tbody>
            </table>
          </section>
        </div>
      </template>
      <div v-else class="empty" style="padding:48px"><Icon name="sigma" :size="26"/><div>No analytics data available yet.</div></div>
    </div>`,
  };

  /* ---------- REPORTS ---------- */
  C.DashReports = {
    name: 'DashReports',
    data() { return { selId: 'production', range: '30d', reportData: null, loading: true }; },
    computed: {
      reports() { return D().REPORTS; },
      sel() { return this.reports.find(r => r.id === this.selId); },
      kpis() { return (this.reportData && this.reportData.kpis) ? this.reportData.kpis : []; },
      rows() { return (this.reportData && this.reportData.rows) ? this.reportData.rows : []; },
      rangeDays() { return this.range === '90d' ? 90 : this.range === '7d' ? 7 : 30; },
    },
    mounted() { this.load(); },
    watch: {
      selId() { this.load(); },
      range() { this.load(); },
    },
    methods: {
      async load() {
        this.loading = true;
        const data = await window.__mdlr.store.loadReports(this.selId, this.rangeDays);
        if (data) this.reportData = data;
        this.loading = false;
      },
    },
    template: `
    <div class=”rep-layout”>
      <aside class=”rep-list”>
        <button v-for=”r in reports” :key=”r.id” class=”rep-item” :class=”{'is-on': selId===r.id}” @click=”selId=r.id”>
          <span class=”rep-item-ico”><Icon :name=”r.icon” :size=”16”/></span>
          <span class=”rep-item-main”><strong>{{ r.name }}</strong><span class=”t-meta”>{{ r.desc }}</span></span>
        </button>
      </aside>

      <section class=”rep-detail”>
        <div class=”rep-head”>
          <div><h2 class=”t-title t-h2” style=”margin:0”>{{ sel.name }}</h2><p class=”page-sub” style=”margin-top:4px”>{{ sel.desc }}</p></div>
          <div class=”flex gap-2 wrap”>
            <div class=”seg”><button :class=”{'is-on':range==='7d'}” @click=”range='7d'”>7d</button><button :class=”{'is-on':range==='30d'}” @click=”range='30d'”>30d</button><button :class=”{'is-on':range==='90d'}” @click=”range='90d'”>90d</button></div>
          </div>
        </div>

        <div v-if=”loading” class=”empty” style=”padding:36px”><div>Loading report\u2026</div></div>
        <template v-else>
          <div class=”rep-kpis”>
            <div class=”rep-kpi” v-for=”k in kpis” :key=”k[0]”><div class=”t-meta”>{{ k[0] }}</div><strong>{{ k[1] }}</strong></div>
          </div>

          <div class=”card” v-if=”rows.length”>
            <div class=”panel-head”><h3 class=”t-title t-h3” style=”margin:0”>Breakdown</h3><span class=”t-meta”>last {{ range }}</span></div>
            <table class=”tbl”>
              <thead><tr><th>Item</th><th style=”width:110px”>Type</th><th style=”width:110px”>Author</th><th style=”width:110px”>Published</th><th style=”width:100px”>Status</th></tr></thead>
              <tbody>
                <tr v-for=”r in rows” :key=”r.name”><td><strong>{{ r.name }}</strong></td><td><span class=”badge”>{{ r.type }}</span></td><td class=”muted”>{{ r.author }}</td><td class=”muted”>{{ r.published }}</td><td class=”muted”>{{ r.status }}</td></tr>
              </tbody>
            </table>
          </div>
          <div v-else class=”empty card” style=”padding:36px”><Icon name=”table” :size=”22”/><div>No data for this report in the selected period.</div></div>

          <div class=”rep-actions”>
            <button class=”btn btn--ghost btn--sm”><Icon name=”files” :size=”14”/> Export CSV</button>
            <button class=”btn btn--ghost btn--sm”><Icon name=”file” :size=”14”/> Export PDF</button>
            <div class=”grow”></div>
          </div>
        </template>
      </section>
    </div>`,
  };
})();
;
/* file: screen-analytics.js */
/* ============================================================
   FULL ANALYTICS — the dedicated, deep analytics page.
   Opened from Dashboard > Analytics > "Open full analytics".
   All data is fetched from /api/v1/stats/analytics.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const cssVar = (n) => getComputedStyle(document.documentElement).getPropertyValue(n).trim() || '#888';

  C.ScreenAnalytics = {
    name: 'ScreenAnalytics',
    inject: ['go'],
    data() { return { a: null, charts: {}, range: '14d', metric: 'posts', loading: true }; },
    mounted() { this.load(); },
    beforeUnmount() { this.killAll(); },
    watch: {
      metric() { this.$nextTick(() => this.redrawMain()); },
      range() { this.load(); },
    },
    computed: {
      rangeDays() { return this.range === '90d' ? 90 : this.range === '30d' ? 30 : 14; },
      kpis() {
        if (!this.a || !this.a.kpis) return [];
        return this.a.kpis.map(k => ({
          label: k.label,
          value: k.value >= 1000 ? (k.value / 1000).toFixed(1) + 'k' : String(k.value),
          delta: (k.delta >= 0 ? '+' : '') + k.delta + '%',
          up: k.up,
        }));
      },
      byStatus() { return (this.a && this.a.by_status) ? this.a.by_status : []; },
      authors() { return (this.a && this.a.authors) ? this.a.authors : []; },
      topPages() { return (this.a && this.a.top_pages) ? this.a.top_pages : []; },
      formStats() { return (this.a && this.a.form_stats) ? this.a.form_stats : []; },
      recentActivity() { return (this.a && this.a.recent_activity) ? this.a.recent_activity : []; },
      totalPosts() { return this.a ? (this.a.daily_posts || []).reduce((s, v) => s + v, 0) : 0; },
    },
    methods: {
      async load() {
        this.loading = true;
        const data = await window.__mdlr.store.loadAnalytics(this.rangeDays);
        if (data) { this.a = data; this.$nextTick(() => this.draw()); }
        this.loading = false;
      },
      killAll() { Object.keys(this.charts).forEach(k => this.kill(k)); },
      kill(name) {
        const c = this.charts[name];
        if (c) { try { c.destroy(); } catch (e) {} this.charts[name] = null; }
      },
      mk(name, ref, config) {
        const canvas = this.$refs[ref];
        if (!window.Chart || !canvas) return;
        this.kill(name);
        const existing = (Chart.getChart && Chart.getChart(canvas)) || null;
        if (existing) { try { existing.destroy(); } catch (e) {} }
        this.charts[name] = new Chart(canvas, config);
      },
      redrawMain() {
        if (!window.Chart || !this.$refs.line || !this.a) return;
        const accent = cssVar('--accent'), line = cssVar('--line');
        const labels = this.a.days || [];
        const series = this.metric === 'submissions' ? (this.a.daily_submissions || [])
                     : this.metric === 'media' ? (this.a.daily_media || [])
                     : (this.a.daily_posts || []);
        this.mk('main', 'line', {
          type: 'line',
          data: { labels, datasets: [{ data: series, borderColor: accent, backgroundColor: 'rgba(192,85,47,0.10)', fill: true, tension: 0.35, pointRadius: 0, borderWidth: 2 }] },
          options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 14 } }, y: { grid: { color: line }, border: { display: false }, beginAtZero: true } } },
        });
      },
      draw() {
        if (!window.Chart || !this.a) return;
        this.killAll();
        Chart.defaults.font.family = "'Hanken Grotesk', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = cssVar('--ink-3');
        const accent = cssVar('--accent'), line = cssVar('--line'), surface = cssVar('--surface');
        const palette = [accent, '#3E7C78', '#B0893F', '#5B7089', '#876089', '#6B4C6E'];
        this.redrawMain();
        // content by type doughnut
        if (this.$refs.bytype && this.a.by_type) {
          this.mk('bytype', 'bytype', {
            type: 'doughnut',
            data: { labels: this.a.by_type.labels, datasets: [{ data: this.a.by_type.data, backgroundColor: palette, borderWidth: 2, borderColor: surface }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'right', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } } } },
          });
        }
        // media by type doughnut
        if (this.$refs.mediatype && this.a.media_by_type && this.a.media_by_type.labels.length) {
          this.mk('mediatype', 'mediatype', {
            type: 'doughnut',
            data: { labels: this.a.media_by_type.labels, datasets: [{ data: this.a.media_by_type.data, backgroundColor: [accent, '#5B7089', '#B0893F', '#3E7C78', '#876089', '#6B4C6E'], borderWidth: 2, borderColor: surface }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'right', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } } } },
          });
        }
        // monthly bar chart
        if (this.$refs.monthly && this.a.monthly && this.a.monthly.labels.length) {
          this.mk('monthly', 'monthly', {
            type: 'bar',
            data: { labels: this.a.monthly.labels, datasets: [{ data: this.a.monthly.data, backgroundColor: accent, borderRadius: 5, maxBarThickness: 30 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { color: line }, border: { display: false }, beginAtZero: true } } },
          });
        }
        // users by role bar
        if (this.$refs.userroles && this.a.users_by_role && this.a.users_by_role.labels.length) {
          this.mk('userroles', 'userroles', {
            type: 'bar',
            data: { labels: this.a.users_by_role.labels, datasets: [{ data: this.a.users_by_role.data, backgroundColor: '#5B7089', borderRadius: 4, maxBarThickness: 18 }] },
            options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: line }, border: { display: false }, beginAtZero: true }, y: { grid: { display: false } } } },
          });
        }
      },
      statusClass(s) { return s === 'published' ? 'badge--ok' : s === 'scheduled' ? 'badge--info' : s === 'draft' ? '' : 'badge--warn'; },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px"><a class="mode-help-link" style="display:inline-flex" @click.prevent="go('dashboard',{tab:'analytics'})"><Icon name="chevLeft" :size="13"/> Dashboard \u00b7 Analytics</a></div>
          <h1 class="t-title t-h1">Analytics</h1>
          <p class="page-sub" style="margin-top:6px">Content production, form activity &amp; system overview.</p>
        </div>
        <div class="flex gap-2 items-center">
          <div class="seg"><button :class="{'is-on':range==='14d'}" @click="range='14d'">14 days</button><button :class="{'is-on':range==='30d'}" @click="range='30d'">30 days</button><button :class="{'is-on':range==='90d'}" @click="range='90d'">90 days</button></div>
        </div>
      </div>

      <div v-if="loading" class="empty" style="padding:64px"><div>Loading analytics\u2026</div></div>
      <template v-else-if="a">

        <!-- KPI cards -->
        <div class="an-kpis an-kpis--3" style="margin-top:8px">
          <div class="card card-pad" v-for="k in kpis" :key="k.label">
            <div class="t-meta">{{ k.label }}</div>
            <div class="stat-val t-display" style="font-size:24px">{{ k.value }}</div>
            <div class="an-delta" :class="k.up?'is-up':'is-down'"><Icon :name="k.up?'arrowUpRight':'arrowRight'" :size="13"/> {{ k.delta }}</div>
          </div>
        </div>

        <!-- main chart with metric switch -->
        <section class="card card-pad" style="margin-top:16px">
          <div class="panel-head" style="padding:0 0 14px">
            <h2 class="t-title t-h3" style="margin:0">Activity over time</h2>
            <div class="seg"><button :class="{'is-on':metric==='posts'}" @click="metric='posts'">New content</button><button :class="{'is-on':metric==='submissions'}" @click="metric='submissions'">Submissions</button><button :class="{'is-on':metric==='media'}" @click="metric='media'">Media</button></div>
          </div>
          <div class="chart-box" style="height:300px"><canvas ref="line"></canvas></div>
        </section>

        <!-- content by status strip -->
        <div class="an-eng" v-if="byStatus.length">
          <div class="an-eng-cell" v-for="s in byStatus" :key="s.status">
            <div class="t-meta">{{ s.status }}</div>
            <strong>{{ s.cnt }}</strong>
          </div>
        </div>

        <!-- breakdown grid: type + media type -->
        <div class="an-grid" style="margin-top:16px">
          <section class="card card-pad">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Content by type</h2></div>
            <div class="chart-box"><canvas ref="bytype"></canvas></div>
          </section>
          <section class="card card-pad">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Media by type</h2></div>
            <div class="chart-box"><canvas ref="mediatype"></canvas></div>
          </section>
          <section class="card card-pad an-wide">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Monthly production</h2><span class="t-meta">last 12 months</span></div>
            <div class="chart-box" style="height:220px"><canvas ref="monthly"></canvas></div>
          </section>
        </div>

        <!-- tables: authors + forms -->
        <div class="an-grid" style="margin-top:16px">
          <section class="card" v-if="authors.length">
            <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Authors</h2></div>
            <div class="an-bars">
              <div class="an-bar" v-for="a in authors" :key="a.name">
                <span class="an-bar-label">{{ a.name }}</span>
                <span class="an-bar-track"><span class="an-bar-fill" :style="{width: Math.min(100, Math.round(a.count / (authors[0].count || 1) * 100))+'%'}"></span></span>
                <span class="an-bar-val mono">{{ a.count }}</span>
              </div>
            </div>
          </section>
          <section class="card" v-if="formStats.length">
            <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Form submissions</h2></div>
            <table class="tbl">
              <thead><tr><th>Form</th><th style="width:100px">Submissions</th></tr></thead>
              <tbody>
                <tr v-for="f in formStats" :key="f.slug"><td><strong>{{ f.name }}</strong></td><td class="mono">{{ f.submissions }}</td></tr>
              </tbody>
            </table>
          </section>
        </div>

        <!-- users + recent activity -->
        <div class="an-grid" style="margin-top:16px">
          <section class="card card-pad">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Users by role</h2></div>
            <div class="chart-box" style="height:180px"><canvas ref="userroles"></canvas></div>
          </section>
          <section class="card" v-if="recentActivity.length">
            <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Recent activity</h2></div>
            <div class="an-feed">
              <div class="an-feed-row" v-for="(l,i) in recentActivity" :key="i">
                <span class="an-feed-flag"><Icon name="zap" :size="13"/></span>
                <div class="an-feed-main"><span class="t-key">{{ l.description }}</span><span class="t-meta">{{ l.user_name || 'System' }}</span></div>
                <span class="t-meta">{{ l.created_at }}</span>
              </div>
            </div>
          </section>
          <section class="card" v-else>
            <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Recent activity</h2></div>
            <div class="empty" style="padding:36px"><div>No activity recorded yet.</div></div>
          </section>
        </div>

        <!-- recent content -->
        <div class="an-grid" style="margin-top:16px">
          <section class="card an-wide" v-if="topPages.length">
            <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Recent content</h2></div>
            <table class="tbl">
              <thead><tr><th>Title</th><th style="width:300px">Path</th></tr></thead>
              <tbody>
                <tr v-for="p in topPages" :key="p.path"><td><strong>{{ p.title }}</strong></td><td><span class="t-key">{{ p.path }}</span></td></tr>
              </tbody>
            </table>
          </section>
        </div>

      </template>
      <div v-else class="empty" style="padding:64px"><Icon name="sigma" :size="26"/><div>No analytics data available.</div></div>
    </div>`,
  };
})();
;
/* file: visual-editor.js */
/* ============================================================
   VISUAL BLOCK EDITOR  (components only — the one real
   front-end surface in Modular).

   FOOLPROOF MODEL
   - The component is a vertical FLOW of blocks (top-level layers).
   - A CONTAINER block (image / video / stack) can hold OVERLAY
     layers as its children. Overlays are positioned *inside the
     container's own box* via a 9-point grid — never ambiguous.
   - One level of nesting only. No implicit "overlap the layer
     above"; overlapping is an explicit parent→child relationship.
   - Responsive: a container can flow its overlays vertically on
     mobile; any layer can hide on mobile.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const uid = (p) => (p||'ly') + '_' + Math.random().toString(36).slice(2,8);
  const grad = (h) => `linear-gradient(135deg, hsl(${h} 32% 80%), hsl(${h-10} 26% 56%))`;

  const CONTAINERS = ['image','video','stack'];
  const OVERLAYABLE = ['heading','text','button','image','embed'];

  const PALETTE = [
    { kind:'heading',  label:'Heading', icon:'type' },
    { kind:'text',     label:'Text',    icon:'align' },
    { kind:'image',    label:'Image',   icon:'image' },
    { kind:'gallery',  label:'Gallery', icon:'grid' },
    { kind:'video',    label:'Video',   icon:'play' },
    { kind:'button',   label:'Button',  icon:'zap' },
    { kind:'stack',    label:'Stack',   icon:'layers' },
    { kind:'repeater', label:'Repeater',icon:'rows' },
    { kind:'embed',    label:'Embed',   icon:'code' },
    { kind:'divider',  label:'Divider', icon:'minus' },
  ];
  // finer placement grid: 5 columns x 3 rows = 15 anchor points (ax/ay fractions)
  const ALIGN_COLS = [0, 0.25, 0.5, 0.75, 1];
  const ALIGN_ROWS = [0, 0.5, 1];
  const ALIGNS_GRID = [];
  ALIGN_ROWS.forEach(ay => ALIGN_COLS.forEach(ax => ALIGNS_GRID.push({ ax, ay })));
  const ALIGNS = [
    ['tl','Top left'],['tc','Top'],['tr','Top right'],
    ['ml','Left'],['mc','Center'],['mr','Right'],
    ['bl','Bottom left'],['bc','Bottom'],['br','Bottom right'],
  ];

  function mkLayer(kind) {
    const b = { id: uid(), kind, hideMobile:false };
    if (kind === 'heading')  return { ...b, text:'New heading', level:2, align:'bl' };
    if (kind === 'text')     return { ...b, text:'Write something for the people who actually read it.', align:'bl' };
    if (kind === 'button')   return { ...b, label:'Learn more', url:'', variant:'primary', align:'bl' };
    if (kind === 'image')    return { ...b, hue: 34, name:'image.jpg', alt:'Describe the image', align:'mc', height:'auto', overlays:[], stackMobile:true };
    if (kind === 'video')    return { ...b, hue: 210, url:'', name:'video.mp4', align:'mc', height:'auto', overlays:[], stackMobile:true };
    if (kind === 'stack')    return { ...b, height:'md', overlays:[], stackMobile:true, align:'mc' };
    if (kind === 'gallery')  return { ...b, images:[28,34,22], cols:3 };
    if (kind === 'repeater') return { ...b, itemLabel:'Item', items:[ {title:'First item', text:'A short line.'}, {title:'Second item', text:'Another line.'} ] };
    if (kind === 'embed')    return { ...b, code:'<iframe src="…"></iframe>', align:'mc' };
    return { ...b, size:'md' };
  }

  function seedLayers(compId) {
    const hero = () => {
      const img = mkLayer('image'); img.hue = 32; img.name = 'studio-light.jpg'; img.alt = 'Studio light'; img.stackMobile = true;
      const h = mkLayer('heading'); h.text = 'Digital Twins Helpdesk'; h.level = 1; h.align = 'bl';
      const t = mkLayer('text'); t.text = 'A grounded helpdesk built on living digital twins — for the people who run the building.'; t.align = 'bl';
      const b1 = mkLayer('button'); b1.label = 'Contact sales'; b1.url = '/contact'; b1.variant = 'primary'; b1.align = 'bl';
      img.overlays = [h, t, b1];
      return [img];
    };
    const richtext = () => {
      const h = mkLayer('heading'); h.text = 'Why we rebuilt it'; h.level = 2;
      const t = mkLayer('text'); t.text = 'The old stack fought us at every turn. So we modelled the content first, then built the smallest CMS that could serve it.';
      return [h, t];
    };
    const imgtext = () => {
      const img = mkLayer('image'); img.hue = 28; img.name = 'control-room.jpg'; img.alt = 'The control room';
      const h = mkLayer('heading'); h.text = 'The control room'; h.level = 3;
      const t = mkLayer('text'); t.text = 'One screen, every twin. Calm by default, loud only when it matters.';
      return [img, h, t];
    };
    const gallery = () => { const g = mkLayer('gallery'); g.images = [30,36,24,32]; g.cols = 3; return [g]; };
    const map = { cmp_hero: hero, cmp_richtext: richtext, cmp_imgtext: imgtext, cmp_gallery: gallery };
    if (map[compId]) return map[compId]();
    const comp = D().COMPONENTS.find(c => c.id === compId);
    const h = mkLayer('heading'); h.text = comp ? comp.name : 'Block'; h.level = 2;
    const t = mkLayer('text'); t.text = 'Edit this block visually.';
    return [h, t];
  }

  window.__mdlr.mkLayer = mkLayer;
  window.__mdlr.seedLayers = seedLayers;

  C.VisualBlockEditor = {
    name: 'VisualBlockEditor',
    props: { block: Object, comp: Object },
    emits: ['close'],
    data() {
      return { selId: null, device: 'desktop', mPanel: null,
        dragKind: null, dragRef: null, dropTop: null, dropOnId: null,
        cropOpen:false, replaceOpen:false, codeOpen:false, palette: PALETTE, aligns: ALIGNS, alignsGrid: ALIGNS_GRID, alignCols: ALIGN_COLS.length };
    },
    created() {
      if (!this.block.layers || !this.block.layers.length) this.block.layers = seedLayers(this.block.comp);
      this.migrate(this.block.layers);
      this.selId = this.block.layers[0] ? this.block.layers[0].id : null;
    },
    computed: {
      layers() { return this.block.layers; },
      mobile() { return this.device === 'mobile'; },
      sel() { return this.findById(this.selId); },
      selParent() { return this.parentOf(this.selId); },
      selIsContainer() { return this.sel && CONTAINERS.includes(this.sel.kind); },
      selIsOverlay() { return !!this.selParent; },
      codeTabs() {
        const S = window.__mdlr.serialize;
        return [
          { id:'json', label:'JSON', lang:'json', code: S.pretty(S.component(this.comp, this.layers)) },
          { id:'php',  label:'PHP',  lang:'php',  code: S.componentPhp(this.comp, this.layers) },
          { id:'html', label:'HTML', lang:'html', code: S.componentHtml(this.comp, this.layers) },
        ];
      },
    },
    methods: {
      grad,
      meta(k) { return PALETTE.find(t => t.kind === k) || { label:k, icon:'dot' }; },
      isContainer(l) { return l && CONTAINERS.includes(l.kind); },
      migrate(arr) { arr.forEach(l => { if (l.hideMobile === undefined) l.hideMobile = false; if ((l.kind === 'image' || l.kind === 'video') && l.height === undefined) l.height = 'auto'; if (this.isContainer(l)) { if (!l.overlays) l.overlays = []; if (l.stackMobile === undefined) l.stackMobile = true; this.migrate(l.overlays); } if (!l.align) l.align = 'mc'; }); },
      // lookups
      findById(id, arr) { arr = arr || this.layers; for (const l of arr) { if (l.id === id) return l; if (l.overlays) { const f = this.findById(id, l.overlays); if (f) return f; } } return null; },
      parentOf(id, arr, parent) { arr = arr || this.layers; for (const l of arr) { if (l.id === id) return parent || null; if (l.overlays) { const f = this.parentOf(id, l.overlays, l); if (f !== undefined && f !== null) return f; if (l.overlays.some(o => o.id === id)) return l; } } return null; },
      select(l) { this.selId = l.id; if (window.matchMedia && window.matchMedia('(max-width: 880px)').matches) this.mPanel = 'inspector'; },
      isSel(l) { return this.selId === l.id; },
      // ---- add ----
      addTop(kind, at) { const l = mkLayer(kind); const pos = at == null ? this.layers.length : at; this.layers.splice(pos, 0, l); this.selId = l.id; return l; },
      addOverlay(container, kind) { const l = mkLayer(kind); l.align = 'bl'; container.overlays.push(l); this.selId = l.id; return l; },
      // ---- delete / dup ----
      del(l) {
        const p = this.parentOf(l.id);
        const arr = p ? p.overlays : this.layers;
        const i = arr.indexOf(l); if (i >= 0) arr.splice(i, 1);
        this.selId = (p && p.id) || (this.layers[0] && this.layers[0].id) || null;
      },
      dup(l) {
        const p = this.parentOf(l.id); const arr = p ? p.overlays : this.layers;
        const i = arr.indexOf(l); const c = JSON.parse(JSON.stringify(l)); this.reId(c);
        arr.splice(i+1, 0, c); this.selId = c.id;
      },
      reId(l) { l.id = uid(); if (l.overlays) l.overlays.forEach(o => this.reId(o)); },
      // overlay z-order
      forward(o) { const p = this.parentOf(o.id); const a = p.overlays; const i = a.indexOf(o); if (i < a.length-1) { a.splice(i,1); a.splice(i+1,0,o); } },
      backward(o) { const p = this.parentOf(o.id); const a = p.overlays; const i = a.indexOf(o); if (i > 0) { a.splice(i,1); a.splice(i-1,0,o); } },
      moveOut(o) { const p = this.parentOf(o.id); const i = p.overlays.indexOf(o); p.overlays.splice(i,1); const ti = this.layers.indexOf(p); this.layers.splice(ti+1, 0, o); this.selId = o.id; },
      // ---- drag ----
      palStart(kind, e) { this.dragKind = kind; this.dragRef = null; if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'copy'; try { e.dataTransfer.setData('text/plain', kind); } catch(_){} } },
      rowStart(l, e) { this.dragRef = l; this.dragKind = null; if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move'; },
      overTop(at, e) { e.preventDefault(); this.dropTop = at; this.dropOnId = null; },
      overContainer(l, e) { if (!this.isContainer(l)) return; e.preventDefault(); e.stopPropagation(); this.dropOnId = l.id; this.dropTop = null; },
      canDropOn(l) { if (this.dragKind) return this.isContainer(l) && OVERLAYABLE.includes(this.dragKind); if (this.dragRef) return this.isContainer(l) && OVERLAYABLE.includes(this.dragRef.kind) && this.dragRef.id !== l.id && !(l.overlays||[]).includes(this.dragRef); return false; },
      dropOnContainer(l) {
        if (!this.canDropOn(l)) { this.resetDrag(); return; }
        if (this.dragKind) { this.addOverlay(l, this.dragKind); }
        else if (this.dragRef) { const p = this.parentOf(this.dragRef.id); const from = p ? p.overlays : this.layers; const i = from.indexOf(this.dragRef); from.splice(i,1); if (!l.overlays) l.overlays = []; this.dragRef.align = this.dragRef.align || 'bl'; l.overlays.push(this.dragRef); this.selId = this.dragRef.id; }
        this.resetDrag();
      },
      dropTopAt(at) {
        const pos = at == null ? this.layers.length : at;
        if (this.dragKind) { this.addTop(this.dragKind, pos); }
        else if (this.dragRef) {
          const p = this.parentOf(this.dragRef.id); const from = p ? p.overlays : this.layers;
          const i = from.indexOf(this.dragRef); let target = pos;
          from.splice(i,1);
          if (!p && i < target) target -= 1;
          this.layers.splice(target, 0, this.dragRef); this.selId = this.dragRef.id;
        }
        this.resetDrag();
      },
      resetDrag() { this.dragKind = null; this.dragRef = null; this.dropTop = null; this.dropOnId = null; },
      // ---- render helpers ----
      // anchor model: {ax,ay} fractions placed inside an inset box, anchored by their own fraction
      anchorOf(o) {
        if (o.align && typeof o.align === 'object') return o.align;
        const a = o.align || 'mc', v = a[0], h = a[1];
        const ay = v === 't' ? 0 : v === 'b' ? 1 : 0.5;
        const ax = h === 'l' ? 0 : h === 'r' ? 1 : 0.5;
        return { ax, ay };
      },
      setAnchor(o, p) { o.align = { ax: p.ax, ay: p.ay }; },
      anchorActive(o, p) { const a = this.anchorOf(o); return Math.abs(a.ax - p.ax) < 0.01 && Math.abs(a.ay - p.ay) < 0.01; },
      overlayStyleA(o) {
        const a = this.anchorOf(o);
        return { position:'absolute', maxWidth:'84%', zIndex:2,
          left: 'calc(6% + ' + (a.ax * 88) + '%)', top: 'calc(7% + ' + (a.ay * 86) + '%)',
          transform: 'translate(' + (-a.ax * 100) + '%, ' + (-a.ay * 100) + '%)' };
      },
      overlayStyle(o) {
        const a = o.align || 'mc', v = a[0], h = a[1], s = { position:'absolute', maxWidth:'84%', zIndex: 2 };
        if (v === 't') s.top = '7%'; else if (v === 'b') s.bottom = '7%'; else s.top = '50%';
        if (h === 'l') s.left = '6%'; else if (h === 'r') s.right = '6%'; else s.left = '50%';
        const tx = h === 'c' ? '-50%' : '0', ty = v === 'm' ? '-50%' : '0';
        if (tx !== '0' || ty !== '0') s.transform = `translate(${tx},${ty})`;
        return s;
      },
      flows(container) { return this.mobile && container.stackMobile; },
      visible(l) { return !(l.hideMobile && this.mobile); },
    },
    template: `
    <teleport to="body">
      <div class="ve">
        <div class="ve-bar">
          <div class="ve-bar-l">
            <button class="iconbtn" @click="$emit('close')" title="Back"><Icon name="chevLeft"/></button>
            <span class="block-ico" style="width:30px;height:30px"><Icon :name="comp.icon" :size="16"/></span>
            <div class="ve-bar-title"><div class="t-eyebrow">Visual editor · component</div><strong>{{ comp.name }}</strong></div>
            <span class="prov prov--comp" style="margin-left:4px"><Icon name="component" :size="11"/> v{{ comp.version }}</span>
          </div>
          <div class="ve-bar-r">
            <button class="btn btn--ghost btn--sm ve-mtoggle" @click="mPanel = mPanel==='layers' ? null : 'layers'" :class="{'is-on-accent': mPanel==='layers'}" title="Layers"><Icon name="layers" :size="15"/></button>
            <button class="btn btn--ghost btn--sm ve-codebtn" @click="codeOpen=true"><Icon name="code" :size="14"/> <span class="hide-sm">Code &amp; API</span></button>
            <div class="seg ve-deviceseg"><button :class="{'is-on':device==='desktop'}" @click="device='desktop'"><Icon name="panelLeft" :size="14"/> <span class="hide-sm">Desktop</span></button><button :class="{'is-on':device==='mobile'}" @click="device='mobile'"><Icon name="box" :size="14"/> <span class="hide-sm">Mobile</span></button></div>
            <button class="btn btn--ghost btn--sm ve-mtoggle" @click="mPanel = mPanel==='inspector' ? null : 'inspector'" :class="{'is-on-accent': mPanel==='inspector'}" title="Inspector"><Icon name="sliders" :size="15"/></button>
            <button class="btn" @click="$emit('close')"><Icon name="check" :size="15"/> <span class="hide-sm">Done</span></button>
          </div>
        </div>

        <div class="ve-body">
          <div class="ve-msheet-scrim" v-if="mPanel" @click="mPanel=null"></div>
          <!-- LAYERS PANEL -->
          <aside class="ve-layers" :class="{'is-msheet': mPanel==='layers'}">
            <div class="ve-layers-head"><Icon name="layers" :size="14"/> Layers <button class="iconbtn iconbtn--sm ve-sheet-x" @click="mPanel=null"><Icon name="x" :size="16"/></button></div>
            <div class="ve-layers-list">
              <template v-for="(l,i) in layers" :key="l.id">
                <div class="ve-zrow" :class="{'is-on':dropTop===i}" @dragover="overTop(i,$event)" @drop="dropTopAt(i)"></div>
                <div class="ve-lrow"
                  :class="{'is-sel':isSel(l), 'is-hidden':l.hideMobile && mobile, 'is-drop':dropOnId===l.id}"
                  draggable="true" @dragstart="rowStart(l,$event)" @dragend="resetDrag"
                  @dragover="isContainer(l) ? overContainer(l,$event) : null" @drop="isContainer(l) ? dropOnContainer(l) : null"
                  @click="select(l)">
                  <Icon name="drag" :size="13" class="drag-handle"/>
                  <span class="ve-lrow-ico"><Icon :name="meta(l.kind).icon" :size="13"/></span>
                  <span class="ve-lrow-name">{{ meta(l.kind).label }}</span>
                  <span v-if="isContainer(l) && l.overlays.length" class="ve-lrow-count" title="overlays">{{ l.overlays.length }}</span>
                  <button class="ve-lrow-eye" @click.stop="l.hideMobile=!l.hideMobile" :title="l.hideMobile?'Hidden on mobile':'Visible'"><Icon :name="l.hideMobile?'eyeOff':'eye'" :size="13"/></button>
                </div>
                <!-- overlays nested -->
                <template v-if="isContainer(l)">
                  <div v-for="o in l.overlays.slice().reverse()" :key="o.id" class="ve-lrow ve-lrow--ov"
                    :class="{'is-sel':isSel(o), 'is-hidden':o.hideMobile && mobile}"
                    draggable="true" @dragstart="rowStart(o,$event)" @dragend="resetDrag" @click="select(o)">
                    <span class="ve-ov-elbow"></span>
                    <span class="ve-lrow-ico"><Icon :name="meta(o.kind).icon" :size="12"/></span>
                    <span class="ve-lrow-name">{{ meta(o.kind).label }}</span>
                    <button class="ve-lrow-eye" @click.stop="o.hideMobile=!o.hideMobile"><Icon :name="o.hideMobile?'eyeOff':'eye'" :size="12"/></button>
                  </div>
                </template>
              </template>
              <div class="ve-zrow" :class="{'is-on':dropTop===layers.length}" @dragover="overTop(layers.length,$event)" @drop="dropTopAt(layers.length)"></div>
            </div>
            <div class="ve-layers-foot"><Icon name="info" :size="12"/> Drag an item onto an <strong>image</strong>, <strong>video</strong> or <strong>stack</strong> to layer it on top.</div>
          </aside>

          <!-- STAGE -->
          <section class="ve-stagewrap">
            <div class="ve-palette-bar">
              <span class="ve-palette-hint"><Icon name="plus" :size="13"/> Drag onto the canvas</span>
              <button v-for="t in palette" :key="t.kind" class="ve-chip" draggable="true" @dragstart="palStart(t.kind,$event)" @dragend="resetDrag" @click="addTop(t.kind)">
                <Icon :name="t.icon" :size="14"/> {{ t.label }}
              </button>
            </div>

            <div class="ve-stage">
              <div class="ve-frame" :class="'ve-frame--'+device">
                <div class="ve-canvas">
                  <div class="ve-zone" :class="{'is-on':dropTop===0}" @dragover="overTop(0,$event)" @drop="dropTopAt(0)"></div>
                  <template v-for="(l,i) in layers" :key="l.id">
                    <div v-if="visible(l)" class="ve-block"
                      :class="{'is-sel':isSel(l), 'is-container':isContainer(l), 'is-droptarget':dropOnId===l.id}"
                      @click="select(l)"
                      @dragover="isContainer(l) ? overContainer(l,$event) : null" @drop="isContainer(l) ? dropOnContainer(l) : null">
                      <div class="ve-block-tag"><Icon :name="meta(l.kind).icon" :size="11"/> {{ meta(l.kind).label }}<span v-if="isContainer(l)" class="ve-block-tagx">· drop to layer</span></div>
                      <div class="ve-block-tools" @click.stop>
                        <button class="ve-tool" @click="dup(l)" title="Duplicate"><Icon name="copy" :size="13"/></button>
                        <button class="ve-tool is-danger" @click="del(l)" title="Delete"><Icon name="trash" :size="13"/></button>
                      </div>

                      <!-- container backdrop + overlays -->
                      <template v-if="isContainer(l)">
                        <div class="ve-backdrop" :class="['ve-backdrop--'+(l.kind), l.kind==='stack' ? 've-stackh--'+(l.height||'md') : ((l.height&&l.height!=='auto') ? 've-bh--'+l.height : '')]" :style="l.kind!=='stack' ? {backgroundImage: grad(l.hue)} : {}">
                          <span v-if="l.kind==='image'" class="ve-image-meta mono">{{ l.name }}</span>
                          <span v-else-if="l.kind==='video'" class="ve-play"><Icon name="play" :size="22"/></span>
                          <span v-else-if="l.kind==='stack' && !l.overlays.length" class="ve-stack-empty">Empty stack — drag items on top</span>
                        </div>
                        <div v-if="flows(l)" class="ve-flowcol">
                          <div v-for="o in l.overlays" :key="o.id" v-show="visible(o)" class="ve-ov ve-ov--flow" :class="{'is-sel':isSel(o)}" @click.stop="select(o)">
                            <div class="ve-ov-tools" @click.stop><button class="ve-tool is-danger" @click="del(o)"><Icon name="trash" :size="12"/></button></div>
                            <ve-render :l="o"/>
                          </div>
                        </div>
                        <template v-else>
                          <div v-for="o in l.overlays" :key="o.id" v-show="visible(o)" class="ve-ov" :class="{'is-sel':isSel(o)}" :style="overlayStyleA(o)" @click.stop="select(o)">
                            <div class="ve-ov-tools" @click.stop><button class="ve-tool is-danger" @click="del(o)"><Icon name="trash" :size="12"/></button></div>
                            <ve-render :l="o" over/>
                          </div>
                        </template>
                      </template>

                      <ve-render v-else :l="l"/>
                    </div>
                    <div class="ve-zone" :class="{'is-on':dropTop===i+1}" @dragover="overTop(i+1,$event)" @drop="dropTopAt(i+1)"></div>
                  </template>
                  <button v-if="!layers.length" class="ve-add-first" @click="addTop('heading')"><Icon name="plus" :size="16"/> Drag an item here, or click to add</button>
                </div>
              </div>
              <div class="ve-hint" v-if="mobile"><Icon name="info" :size="13"/> Mobile preview — containers can flow their overlays vertically; hidden layers are dropped.</div>
            </div>
          </section>

          <!-- INSPECTOR -->
          <aside class="ve-inspector" :class="{'is-msheet': mPanel==='inspector'}">
            <div class="ve-insp-head">
              <template v-if="sel">
                <span class="lib-ico"><Icon :name="meta(sel.kind).icon" :size="15"/></span>
                <div style="flex:1"><strong>{{ meta(sel.kind).label }}</strong><div class="t-meta">{{ selIsOverlay ? 'Overlay · inside '+meta(selParent.kind).label.toLowerCase() : (selIsContainer ? 'Container' : 'Block') }}</div></div>
                <button class="iconbtn iconbtn--sm is-danger" @click="del(sel)"><Icon name="trash" :size="15"/></button>
                <button class="iconbtn iconbtn--sm ve-sheet-x" @click="mPanel=null"><Icon name="x" :size="16"/></button>
              </template>
              <template v-else><strong>Inspector</strong><button class="iconbtn iconbtn--sm ve-sheet-x" style="margin-left:auto" @click="mPanel=null"><Icon name="x" :size="16"/></button></template>
            </div>

            <div class="ve-insp-body" v-if="sel">
              <!-- content -->
              <template v-if="sel.kind==='heading'">
                <div class="field"><label class="field-label">Text</label><input class="inp" v-model="sel.text"></div>
                <div class="field"><label class="field-label">Level</label><div class="seg"><button v-for="n in [1,2,3,4]" :key="n" :class="{'is-on':sel.level===n}" @click="sel.level=n">H{{ n }}</button></div></div>
              </template>
              <template v-else-if="sel.kind==='text'">
                <div class="field"><label class="field-label">Body</label><textarea class="ta" v-model="sel.text" style="min-height:120px"></textarea></div>
              </template>
              <template v-else-if="sel.kind==='image'">
                <div class="ve-img-prev" :style="{backgroundImage: grad(sel.hue)}"><div class="img-slot-actions"><button class="btn btn--soft btn--xs" @click="cropOpen=true"><Icon name="crop" :size="13"/> Crop</button><button class="btn btn--soft btn--xs" @click="replaceOpen=true"><Icon name="refresh" :size="13"/> Replace</button></div></div>
                <div class="field" style="margin-top:12px"><label class="field-label">File</label><input class="inp mono" v-model="sel.name"></div>
                <div class="field"><label class="field-label">Alt text</label><input class="inp" v-model="sel.alt"></div>
                <div class="field"><label class="field-label">Height</label><div class="seg"><button v-for="h in ['auto','sm','md','lg','xl']" :key="h" :class="{'is-on':(sel.height||'auto')===h}" @click="sel.height=h">{{ h==='auto'?'16:9':h }}</button></div></div>
                <ImageCropper v-if="cropOpen" :img="{name:sel.name,w:2400,h:1600,hue:sel.hue}" @close="cropOpen=false"/>
                <MediaPicker v-if="replaceOpen" @close="replaceOpen=false" @pick="m=>{ sel.hue=m.hue; sel.name=m.name }"/>
              </template>
              <template v-else-if="sel.kind==='gallery'">
                <div class="field"><label class="field-label">Images · {{ sel.images.length }}</label>
                  <div class="ve-gal-edit"><span v-for="(h,i) in sel.images" :key="i" class="ve-gal-cell" :style="{background:grad(h)}"><button class="ve-gal-x" @click="sel.images.splice(i,1)"><Icon name="x" :size="11"/></button></span>
                  <button class="ve-gal-add" @click="sel.images.push(20+Math.round(Math.random()*40))"><Icon name="plus" :size="15"/></button></div>
                </div>
                <div class="field"><label class="field-label">Columns</label><div class="seg"><button v-for="n in [2,3,4,5,6]" :key="n" :class="{'is-on':sel.cols===n}" @click="sel.cols=n">{{ n }}</button></div></div>
              </template>
              <template v-else-if="sel.kind==='video'">
                <div class="field"><label class="field-label">Video URL</label><input class="inp mono" v-model="sel.url" placeholder="https://…"></div>
                <div class="field"><label class="field-label">Poster</label><button class="btn btn--ghost btn--sm" @click="replaceOpen=true"><Icon name="image" :size="14"/> Choose poster</button></div>
                <div class="field"><label class="field-label">Height</label><div class="seg"><button v-for="h in ['auto','sm','md','lg','xl']" :key="h" :class="{'is-on':(sel.height||'auto')===h}" @click="sel.height=h">{{ h==='auto'?'16:9':h }}</button></div></div>
                <MediaPicker v-if="replaceOpen" @close="replaceOpen=false" @pick="m=>{ sel.hue=m.hue }"/>
              </template>
              <template v-else-if="sel.kind==='button'">
                <div class="field"><label class="field-label">Label</label><input class="inp" v-model="sel.label"></div>
                <div class="field"><label class="field-label">Link</label><div class="inp-group"><span class="affix">url</span><input class="inp mono" v-model="sel.url" placeholder="/contact"></div></div>
                <div class="field"><label class="field-label">Style</label><div class="seg"><button :class="{'is-on':sel.variant==='primary'}" @click="sel.variant='primary'">Primary</button><button :class="{'is-on':sel.variant==='ghost'}" @click="sel.variant='ghost'">Ghost</button></div></div>
              </template>
              <template v-else-if="sel.kind==='stack'">
                <div class="field"><label class="field-label">Backdrop height</label><div class="seg"><button v-for="s in ['sm','md','lg']" :key="s" :class="{'is-on':sel.height===s}" @click="sel.height=s">{{ s }}</button></div></div>
              </template>
              <template v-else-if="sel.kind==='repeater'">
                <div class="field"><label class="field-label">Item label</label><input class="inp" v-model="sel.itemLabel"></div>
                <label class="field-label">Rows · {{ sel.items.length }}</label>
                <div class="ve-rep-edit">
                  <div class="ve-rep-row" v-for="(it,i) in sel.items" :key="i"><input class="inp" v-model="it.title" placeholder="Title"><input class="inp" v-model="it.text" placeholder="Text"><button class="iconbtn iconbtn--sm is-danger" @click="sel.items.splice(i,1)"><Icon name="x" :size="13"/></button></div>
                  <button class="btn btn--ghost btn--sm" @click="sel.items.push({title:'New item',text:''})"><Icon name="plus" :size="13"/> Add row</button>
                </div>
                <div class="ve-resp-note"><Icon name="info" :size="12"/> A repeater outputs an array of rows in the JSON — loop it on the front-end.</div>
              </template>
              <template v-else-if="sel.kind==='embed'">
                <div class="field"><label class="field-label">Embed code</label><textarea class="ta mono" v-model="sel.code" style="min-height:120px"></textarea></div>
              </template>
              <template v-else-if="sel.kind==='divider'">
                <div class="field"><label class="field-label">Spacing</label><div class="seg"><button v-for="s in ['sm','md','lg']" :key="s" :class="{'is-on':sel.size===s}" @click="sel.size=s">{{ s }}</button></div></div>
              </template>

              <!-- OVERLAY controls -->
              <div class="ve-insp-sec" v-if="selIsOverlay">
                <div class="ve-insp-sec-h"><Icon name="layers" :size="13"/> Position over {{ meta(selParent.kind).label.toLowerCase() }}</div>
                <div class="ve-align ve-align--fine" :style="{gridTemplateColumns:'repeat('+alignCols+',1fr)'}"><button v-for="(p,i) in alignsGrid" :key="i" class="ve-align-dot" :class="{'is-on':anchorActive(sel,p)}" @click="setAnchor(sel,p)"></button></div>
                <div class="flex gap-2" style="margin-top:12px">
                  <button class="btn btn--ghost btn--sm" @click="backward(sel)"><Icon name="chevDown" :size="13"/> Back</button>
                  <button class="btn btn--ghost btn--sm" @click="forward(sel)"><Icon name="chevUp" :size="13"/> Front</button>
                  <button class="btn btn--ghost btn--sm" @click="moveOut(sel)" title="Move out of the container into the page flow"><Icon name="external" :size="13"/> Unstack</button>
                </div>
              </div>

              <!-- CONTAINER controls -->
              <div class="ve-insp-sec" v-if="selIsContainer">
                <div class="ve-insp-sec-h"><Icon name="layers" :size="13"/> Overlays <span class="t-meta" style="margin-left:auto">{{ sel.overlays.length }}</span></div>
                <div class="ve-ovlist" v-if="sel.overlays.length">
                  <button v-for="o in sel.overlays" :key="o.id" class="ve-ovlist-item" :class="{'is-sel':isSel(o)}" @click="select(o)"><Icon :name="meta(o.kind).icon" :size="13"/><span>{{ meta(o.kind).label }}</span><Icon name="chevRight" :size="13" class="muted"/></button>
                </div>
                <div class="ve-addov"><span class="t-meta">Add on top</span><button v-for="k in ['heading','text','button','image']" :key="k" class="ve-addov-btn" @click="addOverlay(sel,k)"><Icon :name="meta(k).icon" :size="13"/> {{ meta(k).label }}</button></div>
                <label class="ve-switchrow" style="margin-top:10px"><span><strong>Flow overlays on mobile</strong><span class="t-meta">Stack them vertically below instead of overlapping.</span></span><UiSwitch :modelValue="sel.stackMobile" @update:modelValue="v=>sel.stackMobile=v"/></label>
              </div>

              <!-- RESPONSIVE -->
              <div class="ve-insp-sec">
                <div class="ve-insp-sec-h"><Icon name="box" :size="13"/> Responsive</div>
                <label class="ve-switchrow"><span><strong>Hide on mobile</strong><span class="t-meta">Drop this layer on small screens.</span></span><UiSwitch :modelValue="sel.hideMobile" @update:modelValue="v=>sel.hideMobile=v"/></label>
                <div class="ve-resp-note"><Icon name="info" :size="12"/> Toggle <strong>Mobile</strong> at the top to preview scaling.</div>
              </div>
            </div>
            <div class="ve-insp-body ve-insp-empty" v-else>
              <Icon name="layers" :size="26"/>
              <div>Select a layer to edit it — or drag a new item from the palette. Drop onto an image, video or stack to layer it on top.</div>
            </div>
          </aside>
        </div>

        <UiCodeModal v-if="codeOpen" :tabs="codeTabs" :title="comp.name + ' · output'" subtitle="How this component serialises and renders" @close="codeOpen=false"/>
      </div>
    </teleport>`,
  };

  /* leaf renderer (also used for overlay content) */
  C['ve-render'] = {
    name: 've-render',
    props: { l: Object, over: Boolean },
    methods: { grad },
    template: `
      <component v-if="l.kind==='heading'" :is="'h'+l.level" class="ve-h" :class="['ve-h'+l.level, over?'ve-h--over':'']">{{ l.text || 'Heading' }}</component>
      <p v-else-if="l.kind==='text'" class="ve-p" :class="{'ve-p--over':over}">{{ l.text }}</p>
      <div v-else-if="l.kind==='image'" class="ve-image" :class="{'ve-image--ov':over}" :style="{backgroundImage: grad(l.hue)}"><span class="ve-image-meta mono">{{ l.name }}</span></div>
      <div v-else-if="l.kind==='gallery'" class="ve-gallery" :style="{gridTemplateColumns:'repeat('+(l.cols||3)+',1fr)'}"><span v-for="(h,i) in l.images" :key="i" class="ve-gallery-cell" :style="{backgroundImage: grad(h)}"></span></div>
      <div v-else-if="l.kind==='button'" class="ve-cta"><span class="ve-cta-btn" :class="'ve-cta-btn--'+l.variant">{{ l.label || 'Button' }}</span></div>
      <div v-else-if="l.kind==='repeater'" class="ve-rep">
        <div class="ve-rep-item" v-for="(it,i) in l.items" :key="i"><strong>{{ it.title }}</strong><span v-if="it.text">{{ it.text }}</span></div>
        <div class="ve-rep-tag">repeater · {{ l.items.length }} rows</div>
      </div>
      <div v-else-if="l.kind==='embed'" class="ve-embedbox"><Icon name="code" :size="16"/> <span class="mono">{{ (l.code||'').slice(0,40) }}…</span></div>
      <hr v-else-if="l.kind==='divider'" class="ve-divider">`,
  };
})();
;
/* file: screen-fieldbuilder.js */
/* ============================================================
   FIELD BUILDER — the centerpiece.
   Three calm layers: WHAT (typed field library) → schema canvas,
   with tabs/columns as the HOW layer and a readable Logic layer.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenFieldBuilder = {
    name: 'ScreenFieldBuilder',
    inject: ['go', 'route'],
    data() {
      return {
        view: 'fields',          // fields | components | logic
        saving: false,
        savedMsg: '', _flashTimer: null,
        mode: 'modular',
        q: '',
        openCats: { basic:true, content:true, choice:false, rel:false, media:true, date:false, layout:true, component:true },
        schema: [],
        sel: null,               // selected field for config
        cfgTab: 'general',
        drag: null,              // {kind:'lib'|'move', type?, index?}
        dropAt: null,            // insertion index
        showAllOnNew: true,
        rules: [],
        editRule: null,
        group: null, groupName: '', groupIcon: 'box', location: [],
        pt: null, ptMode: false,
        allowedComps: ['cmp_hero','cmp_richtext','cmp_imgtext','cmp_features','cmp_quote','cmp_gallery'],
      };
    },
    created() { this.bootstrap(); },
    beforeUnmount() { if (this._flashTimer) clearTimeout(this._flashTimer); },
    computed: {
      cats() {
        const types = D().FIELD_TYPES;
        const q = this.q.trim().toLowerCase();
        return D().CAT_ORDER.map(c => ({
          ...c,
          items: types.filter(t => t.cat === c.cat && (!q || t.label.toLowerCase().includes(q) || t.type.includes(q))),
        })).filter(c => c.items.length);
      },
      fieldCount() { return this.schema.filter(s => !s._section && s.type !== 'tab').length; },
      // group schema into tab-sections for rendering
      sections() {
        const out = []; let cur = { tab: null, items: [] };
        this.schema.forEach((it, i) => {
          if (it.type === 'tab') { if (cur.items.length || cur.tab) out.push(cur); cur = { tab: it, tabIndex: i, items: [] }; }
          else cur.items.push({ it, i });
        });
        if (cur.items.length || cur.tab) out.push(cur);
        if (out.length && !out[0].tab) out[0].tab = { label: 'General' };
        return out;
      },
      catVar() { return D().CAT_VAR; },
      locSummary() {
        return (this.location || []).map(grp => grp.map(r => this.ruleLabel(r)).join(' + '));
      },
    },
    methods: {
      async bootstrap() {
        const store = window.__mdlr.store;
        if (store && !store.ready) {
          if (store.hydrate) await store.hydrate();
          else await store.loadPostTypes({ keepMockIfEmpty: false });
        }
        const rp = this.route.params || {};
        const DB = D();
        if (rp.group) {
          const g = rp.group === 'new'
            ? { id:'new', name:'New field group', icon:'box', desc:'', active:true, fields:[], location:[[{param:'post_type',op:'==',value: rp.type || ''}]] }
            : DB.FIELD_GROUPS.find(x => String(x.id) === String(rp.group));
          if (!g) { alert('Field group not found — try again from Field groups.'); this.go('binding'); return; }
          this.group = g;
          this.groupName = g.name; this.groupIcon = g.icon;
          this.schema = (g.id === 'fg_project')
            ? JSON.parse(JSON.stringify(DB.PROJECT_SCHEMA))
            : JSON.parse(JSON.stringify((g.fields||[]).map(f => ({ ...f, mode: f.mode || 'fixed' }))));
          this.location = JSON.parse(JSON.stringify(g.location || [[{param:'post_type',op:'==',value:'page'}]]));
        } else {
          if (!rp.type) { this.go('postTypes'); return; }
          const pt = DB.POST_TYPES.find(p => p.id === rp.type);
          if (!pt) { alert('Post type not found — try again from Post types.'); this.go('postTypes'); return; }
          this.pt = pt; this.ptMode = true; this.mode = pt.mode;
          this.group = { id:'type_'+pt.id, name:pt.name+' fields', icon:pt.icon, active:true, order:1, position:'normal', style:'default', labelPlacement:'top', desc:'' };
          this.groupName = pt.name + ' fields'; this.groupIcon = pt.icon;
          const sc = pt._schema;
          const ptFields = (sc && Array.isArray(sc.fields)) ? sc.fields : (Array.isArray(sc) ? sc : []);
          this.schema = JSON.parse(JSON.stringify(ptFields.map(f => ({ ...f, mode: f.mode || 'fixed' }))));
          this.location = [[{ param:'post_type', op:'==', value:pt.id }]];
        }
        this.rules = JSON.parse(JSON.stringify(DB.RULES));
        if (rp.view) this.view = rp.view;
      },
      D() { return window.__mdlr.data; },
      tmeta(t) { return D().TYPE_MAP[t] || { label: t, icon: 'dot', cat: 'util' }; },
      tcolor(t) { return this.catVar[this.tmeta(t).cat] || 'var(--ink-3)'; },
      isShared(it) { return D().FIELD_GROUPS.some(g => g.fields.some(f => f.name === it.name)); },
      hasRule(it) { return this.rules.some(r => r.target.name === it.name); },
      // ---- drag from library ----
      libStart(t, e) { this.drag = { kind:'lib', type: t.type, label: t.label }; e.dataTransfer.effectAllowed = 'copy'; },
      // ---- drag to reorder ----
      rowStart(i, e) { this.drag = { kind:'move', index: i }; e.dataTransfer.effectAllowed = 'move'; e.target.closest('.fb-row') && e.target.closest('.fb-row').classList.add('is-dragging'); },
      rowEnd(e) { const el = e.target.closest && e.target.closest('.fb-row'); if (el) el.classList.remove('is-dragging'); this.drag = null; this.dropAt = null; },
      overAt(i, e) { e.preventDefault(); const r = e.currentTarget.getBoundingClientRect(); const after = (e.clientY - r.top) > r.height/2; this.dropAt = after ? i+1 : i; },
      overEnd(e) { e.preventDefault(); this.dropAt = this.schema.length; },
      makeField(type) {
        const m = this.tmeta(type);
        let n = (m.label || type).toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'');
        // ensure the field name is unique within this schema (avoids posts.data collisions)
        const taken = new Set((this.schema || []).map(f => f.name));
        if (taken.has(n)) { let i = 2; while (taken.has(n + '_' + i)) i++; n = n + '_' + i; }
        const base = { id: D().uid(), label: m.label, name: n, key: n, type, required:false, instructions:'', sharedTo:[] };
        if (type === 'tab') return { ...base, label:'New tab', _section:true };
        return base;
      },
      doDrop(e) {
        e.preventDefault();
        let at = this.dropAt == null ? this.schema.length : this.dropAt;
        if (this.drag && this.drag.kind === 'lib') {
          const fld = this.makeField(this.drag.type);
          this.schema.splice(at, 0, fld);
          this.sel = fld; this.cfgTab = 'general';
        } else if (this.drag && this.drag.kind === 'move') {
          const from = this.drag.index;
          const item = this.schema[from];
          this.schema.splice(from, 1);
          if (from < at) at -= 1;
          this.schema.splice(at, 0, item);
        }
        this.drag = null; this.dropAt = null;
      },
      addField(type) {
        const f = this.makeField(type);
        let at = this.schema.length;
        if (this.sel) {
          const i = this.schema.findIndex(x => x === this.sel || (this.sel.id && x.id === this.sel.id));
          if (i >= 0) at = i + 1;
        }
        this.schema.splice(at, 0, f);
        this.sel = f; this.cfgTab = 'general';
      },
      duplicate(i) { const c = JSON.parse(JSON.stringify(this.schema[i])); c.id = D().uid(); c.name += '_copy'; c.key = c.name; this.schema.splice(i+1, 0, c); },
      remove(i) { if (this.sel && this.schema[i] && this.sel.id === this.schema[i].id) this.sel = null; this.schema.splice(i, 1); },
      select(it) { this.sel = it; this.cfgTab = 'general'; },
      addTab() { this.schema.push(this.makeField('tab')); },
      // ---- rules ----
      newRule() { this.editRule = { id: D().uid(), target:{kind:'field', name:'', label:'Select…'}, action:'show', logic:'all', conds:[{ field:'', label:'', op:'is', value:'' }] }; },
      saveRule() { const i = this.rules.findIndex(r => r.id === this.editRule.id); if (i>=0) this.rules.splice(i,1,this.editRule); else this.rules.push(this.editRule); this.editRule = null; },
      saveGroup() {
        this.saving = true;
        if (this.ptMode && this.pt) {
          const ptRef = D().POST_TYPES.find(p => p.id === this.pt.id) || this.pt;
          const dbId = ptRef._dbId;
          if (dbId == null) {
            this.saving = false;
            alert('Post type is still loading — wait a moment and try again.');
            return;
          }
          const base = (ptRef._schema && typeof ptRef._schema === 'object') ? JSON.parse(JSON.stringify(ptRef._schema)) : {};
          const schema = { ...base, fields: JSON.parse(JSON.stringify(this.schema || [])) };
          window.__mdlr.store.savePostTypeSchema(dbId, schema)
            .then(() => { this.saving = false; this.flash('Schema saved'); })
            .catch((e) => { this.saving = false; console.error('[fieldbuilder] post type save failed', e); alert('Saving the post type fields failed. Check the console.'); });
          return;
        }
        const g = this.group || {};
        const payload = {
          id: g.id,
          name: this.groupName || g.name || 'Field group',
          icon: this.groupIcon || g.icon || 'box',
          desc: g.desc || '',
          position: g.position || 'normal', style: g.style || 'default', labelPlacement: g.labelPlacement || 'top',
          active: g.active !== false, order: g.order || 0,
          location: this.location || [], fields: this.schema || [],
        };
        window.__mdlr.store.saveFieldGroup(payload)
          .then((saved) => {
            this.saving = false;
            if (saved && saved.id) this.group = Object.assign({}, this.group || {}, saved);
            this.flash('Field group saved');
          })
          .catch((e) => { this.saving = false; console.error('[fieldbuilder] save failed', e); alert('Saving the field group failed. Check the console.'); });
      },
      flash(msg) { this.savedMsg = msg; if (this._flashTimer) clearTimeout(this._flashTimer); this._flashTimer = setTimeout(() => { this.savedMsg = ''; }, 2600); },
      delRule(id) { this.rules = this.rules.filter(r => r.id !== id); },
      fieldOptions() { return this.schema.filter(s => s.type!=='tab' && !s._section).map(s => ({ name:s.name, label:s.label })); },
      tabOptions() { return this.schema.filter(s => s.type==='tab').map(s => ({ name:s.name, label:s.label })); },
      addCond() { this.editRule.conds.push({ field:'', label:'', op:'is', value:'' }); },
      comp(id) { return D().COMPONENTS.find(c => c.id === id); },
      toggleComp(id) { const i = this.allowedComps.indexOf(id); if (i>=0) this.allowedComps.splice(i,1); else this.allowedComps.push(id); },
      // ---- location rules (ACF-style) ----
      locParams() { return D().LOCATION_PARAMS; },
      locOps() { return D().LOCATION_OPS; },
      locValues(param) {
        const p = D().LOCATION_PARAMS.find(x => x.id === param);
        if (!p) return [];
        if (p.values) return p.values;
        if (p.valuesFrom === 'post_types') return D().POST_TYPES.map(t => ({ id:t.id, name:t.name }));
        if (p.valuesFrom === 'posts') return D().POSTS.map(t => ({ id:String(t.id), name:t.title }));
        if (p.valuesFrom === 'terms') { const out=[]; D().TAXONOMIES.forEach(t => (t.terms||[]).forEach(tm => out.push({ id:tm.id, name:t.name+': '+tm.name }))); return out; }
        return [];
      },
      addRule(gi) { this.location[gi].push({ param:'post_type', op:'==', value:'page' }); },
      delRule(gi, ri) { this.location[gi].splice(ri,1); if (!this.location[gi].length) this.location.splice(gi,1); },
      addOrGroup() { this.location.push([{ param:'post_type', op:'==', value:'post' }]); },
      ptName(id) { const p = D().POST_TYPES.find(x => x.id === id); return p ? p.name : id; },
      termName(id) { for (const t of D().TAXONOMIES) { const x = (t.terms||[]).find(tm => tm.id === id); if (x) return t.name+': '+x.name; } return id; },
      ruleLabel(r) {
        if (r.param === 'post_type') { if (r.op === '!=' && !r.value) return 'All post types'; return (r.op==='!='?'Not ':'') + this.ptName(r.value); }
        if (r.param === 'post') { const p = D().POSTS.find(x => String(x.id) === String(r.value)); return 'Post: ' + (p ? p.title : r.value); }
        if (r.param === 'post_slug') return 'Slug: ' + (r.value || '');
        if (r.param === 'post_template') return 'Template: ' + r.value;
        if (r.param === 'taxonomy') return this.termName(r.value);
        if (r.param === 'user_role') return 'Role: ' + r.value;
        return String(r.value || '');
      },
    },
    template: `
    <div class="fb">
      <!-- top bar -->
      <div class="fb-top">
        <div class="fb-top-l">
          <button class="iconbtn" @click="go('binding')" title="Back to field groups"><Icon name="chevLeft"/></button>
          <span class="dash-row-ico"><Icon :name="groupIcon" :size="16"/></span>
          <div style="display:flex;flex-direction:column;line-height:1.2">
            <span class="t-eyebrow">Field group</span>
            <input class="fb-groupname" v-model="groupName">
          </div>
        </div>
        <div class="fb-top-c">
          <div class="fb-vtabs">
            <button :class="{'is-on':view==='fields'}" @click="view='fields'"><Icon name="type" :size="15"/> Fields <span class="tab-count">{{ fieldCount }}</span></button>
            <button :class="{'is-on':view==='location'}" @click="view='location'"><Icon name="pin" :size="15"/> Location <span class="tab-count">{{ location.length }}</span></button>
            <button :class="{'is-on':view==='logic'}" @click="view='logic'"><Icon name="branch" :size="15"/> Logic <span class="tab-count">{{ rules.length }}</span></button>
            <button :class="{'is-on':view==='settings'}" @click="view='settings'"><Icon name="settings" :size="15"/> Settings</button>
          </div>
        </div>
        <div class="fb-top-r">
          <span class="t-meta">{{ fieldCount }} fields</span>
          <transition name="fade"><span v-if="savedMsg" class="badge badge--ok ed-saved"><Icon name="check" :size="12"/> {{ savedMsg }}</span></transition>
          <button class="btn" :disabled="saving" @click="saveGroup"><Icon name="save" :size="15"/> {{ saving ? 'Saving…' : 'Save group' }}</button>
        </div>
      </div>

      <!-- FIELDS view -->
      <div class="fb-body" v-if="view==='fields'">
        <!-- library -->
        <aside class="fb-lib">
          <div class="fb-lib-search"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="q" placeholder="Search fields…"></div>
          <div class="fb-lib-scroll">
            <div class="fb-lib-hint">Drag onto the canvas, or click to append.</div>
            <div v-for="c in cats" :key="c.cat" class="fb-cat">
              <button class="fb-cat-h" @click="openCats[c.cat]=!openCats[c.cat]">
                <Icon :name="openCats[c.cat]?'chevDown':'chevRight'" :size="14"/>
                <span class="fb-cat-dot" :style="{background: catVar[c.cat]}"></span>
                {{ c.label }}
                <span class="tab-count" style="margin-left:auto">{{ c.items.length }}</span>
              </button>
              <div v-show="openCats[c.cat]" class="fb-cat-items">
                <div v-for="t in c.items" :key="t.type" class="fb-lib-item" draggable="true"
                     @dragstart="libStart(t,$event)" @dragend="drag=null;dropAt=null" @click="addField(t.type)">
                  <Icon name="drag" :size="14" class="fb-lib-grip"/>
                  <span class="lib-ico" :style="{color: catVar[c.cat]}"><Icon :name="t.icon" :size="15"/></span>
                  <span class="fb-lib-label">{{ t.label }}</span>
                  <span class="typetag">{{ t.type }}</span>
                </div>
              </div>
            </div>
          </div>
        </aside>

        <!-- canvas -->
        <section class="fb-canvas" @dragover="overEnd" @drop="doDrop">
          <div class="fb-canvas-head">
            <div>
              <h2 class="t-title t-h3" style="margin:0">Schema</h2>
              <p class="t-meta" style="margin-top:3px">The fields editors see. Tabs split the editor into sections; columns place fields side by side.</p>
            </div>
            <div class="flex gap-2">
              <button class="btn btn--ghost btn--sm" @click="addTab"><Icon name="tab" :size="15"/> Add tab</button>
              <button class="btn btn--ghost btn--sm" @click="go('binding')"><Icon name="git" :size="15"/> Add field group</button>
            </div>
          </div>

          <div class="fb-legend">
            <div class="fb-legend-item"><span class="modepill modepill--fixed"><Icon name="lock" :size="11"/> Fixed</span><span class="t-meta">Locked into every item — always in the editor.</span></div>
            <div class="fb-legend-item"><span class="modepill modepill--modular"><Icon name="layers" :size="11"/> Modular</span><span class="t-meta">Optional — editors add, reorder &amp; remove it per item.</span></div>
          </div>

          <div class="fb-schema">
            <template v-for="(sec,si) in sections" :key="si">
              <!-- tab section header -->
              <div class="fb-tabmark" v-if="sec.tab && sec.tab.type==='tab'"
                   draggable="true" @dragstart="rowStart(sec.tabIndex,$event)" @dragend="rowEnd"
                   @dragover="overAt(sec.tabIndex,$event)">
                <div class="drop-line" v-if="dropAt===sec.tabIndex"></div>
                <Icon name="drag" :size="15" class="drag-handle"/>
                <span class="fb-tabmark-ico"><Icon name="tab" :size="14"/></span>
                <input class="fb-tabmark-label" v-model="sec.tab.label">
                <span class="badge"><Icon name="tab" :size="11"/> tab</span>
                <div class="grow"></div>
                <button class="iconbtn iconbtn--sm" @click="select(sec.tab)"><Icon name="settings" :size="14"/></button>
                <button class="iconbtn iconbtn--sm is-danger" @click="remove(sec.tabIndex)"><Icon name="trash" :size="14"/></button>
              </div>
              <div class="fb-tabmark fb-tabmark--virtual" v-else-if="sec.tab"><span class="fb-tabmark-ico"><Icon name="tab" :size="14"/></span><span style="font-weight:600">{{ sec.tab.label }}</span><span class="t-meta">· default section</span></div>

              <!-- fields -->
              <div class="fb-rows">
                <div v-for="({it,i}) in sec.items" :key="it.id">
                  <div class="drop-line" v-if="dropAt===i"></div>
                  <div class="fb-row" :class="{'is-sel': sel && sel.id===it.id}"
                       draggable="true" @dragstart="rowStart(i,$event)" @dragend="rowEnd"
                       @dragover="overAt(i,$event)" @drop="doDrop" @click="select(it)">
                    <Icon name="drag" :size="16" class="drag-handle"/>
                    <span class="lib-ico fb-row-ico" :style="{color: tcolor(it.type), background:'color-mix(in srgb,'+tcolor(it.type)+' 12%,transparent)'}"><Icon :name="tmeta(it.type).icon" :size="15"/></span>
                    <div class="fb-row-main">
                      <div class="fb-row-label">{{ it.label }}<span class="field-req" v-if="it.required">*</span></div>
                      <div class="t-key">{{ it.key }}</div>
                    </div>
                    <span v-if="it.type==='blocks'" class="badge badge--info"><Icon name="layers" :size="11"/> {{ allowedComps.length }} components</span>
                    <span v-if="isShared(it)" class="prov prov--type"><Icon name="git" :size="11"/> shared</span>
                    <span v-if="hasRule(it)" class="prov prov--comp"><Icon name="branch" :size="11"/> logic</span>
                    <button class="modepill" :class="it.mode==='modular' ? 'modepill--modular' : 'modepill--fixed'"
                            @click.stop="it.mode = it.mode==='modular' ? 'fixed' : 'modular'"
                            :title="it.mode==='modular' ? 'Modular — editors add per item. Click to lock.' : 'Fixed — always in the editor. Click to make modular.'">
                      <Icon :name="it.mode==='modular' ? 'layers' : 'lock'" :size="11"/> {{ it.mode==='modular' ? 'Modular' : 'Fixed' }}
                    </button>
                    <span class="typetag">{{ it.type }}</span>
                    <div class="fb-row-actions" @click.stop>
                      <button class="iconbtn iconbtn--sm" @click="select(it)" title="Settings"><Icon name="settings" :size="15"/></button>
                      <button class="iconbtn iconbtn--sm" @click="duplicate(i)" title="Duplicate"><Icon name="copy" :size="15"/></button>
                      <button class="iconbtn iconbtn--sm is-danger" @click="remove(i)" title="Delete"><Icon name="trash" :size="15"/></button>
                    </div>
                  </div>
                </div>
              </div>
            </template>

            <!-- end drop zone -->
            <div class="fb-end drop-zone" :class="{'is-over': dropAt===schema.length}" @dragover="overEnd" @drop="doDrop">
              <div class="drop-line" v-if="dropAt===schema.length" style="position:absolute;left:14px;right:14px;top:-1px"></div>
              <Icon name="plus" :size="16"/> Drag a field here, or pick from the library
            </div>
          </div>
        </section>
      </div>

      <!-- LOCATION view -->
      <div class="fb-single" v-else-if="view==='location'">
        <div class="fb-single-inner">
          <div class="page-head">
            <div><h2 class="t-title t-h2" style="margin:0">Location rules</h2><p class="page-sub" style="margin-top:6px">Show this field group wherever the rules match. Rules inside a group are joined with <strong>and</strong>; separate groups are joined with <strong>or</strong>.</p></div>
          </div>
          <div class="loc-builder">
            <template v-for="(grp,gi) in location" :key="gi">
              <div v-if="gi>0" class="loc-or"><span>or</span></div>
              <div class="loc-group">
                <div class="loc-rule" v-for="(r,ri) in grp" :key="ri">
                  <span class="loc-lead" v-if="ri===0">Show if</span>
                  <span class="loc-lead loc-lead--and" v-else>and</span>
                  <UiSelect :modelValue="r.param" @update:modelValue="v=>{ r.param=v; r.value=(locValues(v)[0]||{}).id||'' }"><option v-for="p in locParams()" :key="p.id" :value="p.id">{{ p.label }}</option></UiSelect>
                  <UiSelect v-model="r.op" style="max-width:160px"><option v-for="o in locOps()" :key="o.id" :value="o.id">{{ o.label }}</option></UiSelect>
                  <UiSelect v-model="r.value"><option v-for="v in locValues(r.param)" :key="v.id" :value="v.id">{{ v.name }}</option></UiSelect>
                  <button class="iconbtn iconbtn--sm is-danger" @click="delRule(gi,ri)" title="Remove rule"><Icon name="x" :size="14"/></button>
                </div>
                <button class="btn btn--text btn--sm" @click="addRule(gi)" style="margin-top:4px"><Icon name="plus" :size="14"/> And</button>
              </div>
            </template>
            <button class="loc-addor" @click="addOrGroup"><Icon name="plus" :size="15"/> Add rule group (Or)</button>
          </div>

          <div class="loc-preview">
            <div class="loc-preview-h"><Icon name="eye" :size="14"/> This group will appear on</div>
            <div class="flex gap-2 wrap" v-if="locSummary.length"><span class="loc-chip" v-for="(c,i) in locSummary" :key="i"><Icon name="pin" :size="11"/>{{ c }}</span></div>
            <div class="t-meta" v-else>Nowhere yet — add a rule above.</div>
          </div>
        </div>
      </div>

      <!-- LOGIC view -->
      <div class="fb-single" v-else-if="view==='logic'">
        <div class="fb-single-inner">
          <div class="page-head">
            <div><h2 class="t-title t-h2" style="margin:0">Conditional logic</h2><p class="page-sub" style="margin-top:6px">A readable rule layer. Conditions reference field values and can show or hide fields, tabs and whole sections.</p></div>
            <button class="btn btn--accent" @click="newRule"><Icon name="plus" :size="15"/> New rule</button>
          </div>
          <div v-if="!rules.length" class="empty"><Icon name="branch"/><div>No rules yet. Everything is always visible.</div></div>
          <div class="rule-list">
            <div v-for="r in rules" :key="r.id" class="rule-card">
              <div class="rule-sentence">
                <span class="rule-kw">{{ r.action==='show'?'Show':'Hide' }}</span>
                <span class="rule-chip rule-chip--target"><Icon :name="r.target.kind==='tab'?'tab':'type'" :size="12"/> {{ r.target.label }}</span>
                <span class="rule-kw">when</span>
                <template v-for="(c,ci) in r.conds" :key="ci">
                  <span v-if="ci>0" class="rule-kw rule-kw--join">{{ r.logic==='all'?'and':'or' }}</span>
                  <span class="rule-chip"><strong>{{ c.label }}</strong> {{ c.op }} <em v-if="c.value">{{ c.value }}</em></span>
                </template>
              </div>
              <div class="rule-actions">
                <button class="iconbtn iconbtn--sm" @click="editRule=JSON.parse(JSON.stringify(r))"><Icon name="pencil" :size="14"/></button>
                <button class="iconbtn iconbtn--sm is-danger" @click="delRule(r.id)"><Icon name="trash" :size="14"/></button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SETTINGS view (ACF-style group settings) -->
      <div class="fb-single" v-else-if="view==='settings'">
        <div class="fb-single-inner" style="max-width:760px">
          <div class="page-head"><div><h2 class="t-title t-h2" style="margin:0">Group settings</h2><p class="page-sub" style="margin-top:6px">Where and how this group renders in the post editor.</p></div></div>

          <div class="card card-pad" style="margin-bottom:16px">
            <label class="field-label">Placement</label>
            <div class="field-hint" style="margin:0 0 12px">Where the group sits in the editor — the same idea as ACF's position setting.</div>
            <div class="set-cards">
              <button class="set-card" :class="{'is-on':group.position==='high'}" @click="group.position='high'">
                <span class="set-card-ico"><Icon name="rows" :size="16"/></span>
                <strong>High</strong><span class="t-meta">After the title, above the content.</span>
              </button>
              <button class="set-card" :class="{'is-on':group.position==='normal'}" @click="group.position='normal'">
                <span class="set-card-ico"><Icon name="align" :size="16"/></span>
                <strong>Normal</strong><span class="t-meta">After the content area.</span>
              </button>
              <button class="set-card" :class="{'is-on':group.position==='side'}" @click="group.position='side'">
                <span class="set-card-ico"><Icon name="panelLeft" :size="16"/></span>
                <strong>Side</strong><span class="t-meta">In the right sidebar rail.</span>
              </button>
            </div>
          </div>

          <div class="card card-pad" style="margin-bottom:16px">
            <div class="field"><label class="field-label">Style</label><div class="seg"><button :class="{'is-on':group.style==='default'}" @click="group.style='default'">Standard (boxed)</button><button :class="{'is-on':group.style==='seamless'}" @click="group.style='seamless'">Seamless</button></div></div>
            <div class="field"><label class="field-label">Label placement</label><div class="seg"><button :class="{'is-on':group.labelPlacement==='top'}" @click="group.labelPlacement='top'">Top</button><button :class="{'is-on':group.labelPlacement==='left'}" @click="group.labelPlacement='left'">Left</button></div></div>
            <div class="field" style="margin:0"><label class="field-label">Description</label><input class="inp" v-model="group.desc" placeholder="What this group is for"></div>
          </div>

          <div class="card card-pad">
            <div class="opt-row" style="padding-top:0"><div><strong>Active</strong><div class="field-hint">Inactive groups are hidden everywhere.</div></div><UiSwitch v-model="group.active"/></div>
            <div class="opt-row" style="padding-bottom:0"><div><strong>Order number</strong><div class="field-hint">Lower numbers render first.</div></div><input class="inp mono" type="number" v-model.number="group.order" style="width:84px"></div>
          </div>
        </div>
      </div>

      <!-- FIELD CONFIG slide-over -->
      <UiDrawer v-if="sel" @close="sel=null">
        <div class="drawer-head">
          <span class="lib-ico fb-row-ico" :style="{color: tcolor(sel.type), background:'color-mix(in srgb,'+tcolor(sel.type)+' 12%,transparent)'}"><Icon :name="tmeta(sel.type).icon" :size="15"/></span>
          <div style="flex:1"><strong style="font-size:15px">{{ sel.label }}</strong><div class="t-meta">{{ tmeta(sel.type).label }} field</div></div>
          <button class="iconbtn" @click="sel=null"><Icon name="x"/></button>
        </div>
        <div class="cfg-tabs tabs">
          <button :class="{'is-on':cfgTab==='general'}" @click="cfgTab='general'">General</button>
          <button :class="{'is-on':cfgTab==='validation'}" @click="cfgTab='validation'">Validation</button>
          <button :class="{'is-on':cfgTab==='display'}" @click="cfgTab='display'">Display</button>
          <button :class="{'is-on':cfgTab==='logic'}" @click="cfgTab='logic'"><Icon name="branch" :size="13"/> Logic</button>
        </div>
        <div class="drawer-body">
          <template v-if="cfgTab==='general'">
            <div class="field"><label class="field-label">Label</label><input class="inp" v-model="sel.label"></div>
            <div class="cfg-2">
              <div class="field"><label class="field-label">Name</label><input class="inp mono" v-model="sel.name"></div>
              <div class="field"><label class="field-label">Key</label><input class="inp mono" v-model="sel.key"></div>
            </div>
            <div class="field"><label class="field-label">Instructions</label><textarea class="ta" v-model="sel.instructions" placeholder="Shown under the field in the editor."></textarea></div>
            <div class="field" v-if="!['tab','message','divider'].includes(sel.type)"><label class="field-label">Default value</label><input class="inp" placeholder="—"></div>
            <div class="opt-row" v-if="!['tab','message','divider'].includes(sel.type)"><div><strong>Required</strong><div class="field-hint">Editors can't save without it.</div></div><UiSwitch v-model="sel.required"/></div>

            <FieldSettings :field="sel"/>

            <label class="field-label" style="margin-top:14px">Availability</label>
            <div class="avail-pick">
              <button class="avail-opt" :class="{'is-on': sel.mode!=='modular'}" @click="sel.mode='fixed'">
                <span class="modepill modepill--fixed"><Icon name="lock" :size="11"/> Fixed</span>
                <span class="t-meta">Locked into every item. Always shown, can't be removed per item.</span>
              </button>
              <button class="avail-opt" :class="{'is-on': sel.mode==='modular'}" @click="sel.mode='modular'">
                <span class="modepill modepill--modular"><Icon name="layers" :size="11"/> Modular</span>
                <span class="t-meta">Offered per item. Editors add, reorder &amp; remove it themselves.</span>
              </button>
            </div>
          </template>

          <template v-else-if="cfgTab==='validation'">
            <FieldValidation :field="sel"/>
          </template>

          <template v-else-if="cfgTab==='display'">
            <label class="field-label">Field width</label>
            <div class="seg" style="margin-bottom:18px">
              <button :class="{'is-on':(sel.w||'full')==='full'}" @click="sel.w='full'">Full</button>
              <button :class="{'is-on':sel.w==='half'}" @click="sel.w='half'">1/2</button>
              <button :class="{'is-on':sel.w==='third'}" @click="sel.w='third'">1/3</button>
            </div>
            <div class="field"><label class="field-label">Placeholder</label><input class="inp" v-model="sel.placeholder" placeholder="—"></div>
            <div class="field"><label class="field-label">Appearance</label><UiSelect><option>Default</option><option>Compact</option><option>Borderless</option></UiSelect></div>
            <div class="opt-row"><div><strong>Show in list view</strong><div class="field-hint">Surface this field as a column.</div></div><UiSwitch :modelValue="false"/></div>
          </template>

          <template v-else>
            <div class="cfg-logic-head">
              <p class="field-hint" style="margin:0 0 12px">Show this field only when conditions are met. Editors see <em>why</em> it's hidden.</p>
            </div>
            <div class="rule-mini">
              <span class="rule-kw">Show when</span>
              <UiSelect style="width:auto;display:inline-flex"><option>all</option><option>any</option></UiSelect>
              <span class="rule-kw">of:</span>
            </div>
            <div class="cond-row">
              <UiSelect><option>Featured on homepage</option><option v-for="o in fieldOptions()" :key="o.name">{{ o.label }}</option></UiSelect>
              <UiSelect style="max-width:110px"><option>is</option><option>is not</option><option>is set</option><option>contains</option></UiSelect>
              <input class="inp" placeholder="true">
              <button class="iconbtn iconbtn--sm is-danger"><Icon name="x" :size="14"/></button>
            </div>
            <button class="btn btn--text btn--sm" style="margin-top:10px"><Icon name="plus" :size="14"/> Add condition</button>
          </template>
        </div>
        <div class="drawer-foot">
          <button class="btn btn--danger" style="margin-right:auto" @click="remove(schema.findIndex(s=>s.id===sel.id))"><Icon name="trash" :size="15"/> Delete</button>
          <button class="btn btn--ghost" @click="sel=null">Close</button>
          <button class="btn" @click="sel=null"><Icon name="check" :size="15"/> Done</button>
        </div>
      </UiDrawer>

      <!-- RULE editor modal -->
      <UiModal v-if="editRule" @close="editRule=null">
        <div class="drawer-head"><Icon name="branch" :size="18"/><strong style="font-size:15px;flex:1">Conditional rule</strong><button class="iconbtn" @click="editRule=null"><Icon name="x"/></button></div>
        <div class="drawer-body">
          <div class="rule-build">
            <div class="rule-build-line">
              <UiSelect v-model="editRule.action" style="max-width:100px"><option value="show">Show</option><option value="hide">Hide</option></UiSelect>
              <UiSelect v-model="editRule.target.label">
                <optgroup label="Fields"><option v-for="o in fieldOptions()" :key="o.name" :value="o.label">{{ o.label }}</option></optgroup>
                <optgroup label="Tabs"><option v-for="o in tabOptions()" :key="o.name" :value="o.label">{{ o.label }}</option></optgroup>
              </UiSelect>
            </div>
            <div class="rule-build-when"><span class="rule-kw">when</span><UiSelect v-model="editRule.logic" style="width:auto"><option value="all">all</option><option value="any">any</option></UiSelect><span class="rule-kw">of these match:</span></div>
            <div class="cond-row" v-for="(c,ci) in editRule.conds" :key="ci">
              <UiSelect v-model="c.label"><option value="">Select field…</option><option v-for="o in fieldOptions()" :key="o.name" :value="o.label">{{ o.label }}</option></UiSelect>
              <UiSelect v-model="c.op" style="max-width:120px"><option>is</option><option>is not</option><option>is set</option><option>contains</option><option>&gt;</option><option>&lt;</option></UiSelect>
              <input class="inp" v-model="c.value" placeholder="value">
              <button class="iconbtn iconbtn--sm is-danger" @click="editRule.conds.splice(ci,1)"><Icon name="x" :size="14"/></button>
            </div>
            <button class="btn btn--text btn--sm" @click="addCond"><Icon name="plus" :size="14"/> Add condition</button>
          </div>
        </div>
        <div class="drawer-foot"><button class="btn btn--ghost" @click="editRule=null">Cancel</button><button class="btn btn--accent" @click="saveRule"><Icon name="check" :size="15"/> Save rule</button></div>
      </UiModal>
    </div>`,
  };
})();
;
/* file: screen-components.js */
/* ============================================================
   COMPONENTS — the unifying "block" concept.
   Library, the component editor, and a reusable picker.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  /* ---- reusable component picker (modal) ---- */
  C.ComponentPicker = {
    name: 'ComponentPicker',
    emits: ['close','pick'],
    data() { return { q: '' }; },
    computed: {
      list() { const q=this.q.trim().toLowerCase(); return D().COMPONENTS.filter(c => !q || c.name.toLowerCase().includes(q)); },
    },
    template: `
    <UiModal lg @close="$emit('close')">
      <div class="drawer-head"><Icon name="layers" :size="18"/><strong style="font-size:15px;flex:1">Add a component</strong><button class="iconbtn" @click="$emit('close')"><Icon name="x"/></button></div>
      <div style="padding:14px 18px 0">
        <div class="topbar-search" style="max-width:none"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="q" placeholder="Search components…" style="color:var(--ink)"></div>
      </div>
      <div class="drawer-body" style="padding-top:14px">
        <div class="picker-grid">
          <button v-for="c in list" :key="c.id" class="picker-card" @click="$emit('pick', c.id)">
            <span class="picker-ico"><Icon :name="c.icon" :size="20"/></span>
            <strong>{{ c.name }}</strong>
            <span class="t-meta">{{ c.desc }}</span>
            <span class="picker-meta"><span class="t-key">{{ c.fields.length }} fields</span><span class="badge"><Icon name="component" :size="10"/> v{{ c.version }}</span></span>
          </button>
        </div>
      </div>
    </UiModal>`,
  };

  /* ---- COMPONENTS screen: library + editor ---- */
  C.ScreenComponents = {
    name: 'ScreenComponents',
    inject: ['go'],
    data() { return { editing: null, sel: null, addMenu: false, q:'', visual: null, saving: false }; },
    computed: {
      list() { return D().COMPONENTS; },
      cats() {
        const types = D().FIELD_TYPES; const q=this.q.trim().toLowerCase();
        return D().CAT_ORDER.filter(c=>c.cat!=='component'||true).map(c => ({ ...c, items: types.filter(t=>t.cat===c.cat && (!q||t.label.toLowerCase().includes(q))) })).filter(c=>c.items.length);
      },
    },
    methods: {
      open(c){ this.editing = JSON.parse(JSON.stringify(c)); },
      openVisual(c){ if (c) this.open(c); const real = D().COMPONENTS.find(x => x.id === this.editing.id) || this.editing; if (!real._layers) real._layers = window.__mdlr.seedLayers(real.id); this.visual = { comp: real.id, layers: real._layers }; },
      tmeta(t){ return D().TYPE_MAP[t]||{icon:'dot',cat:'util'}; },
      tcolor(t){ return D().CAT_VAR[this.tmeta(t).cat]||'var(--ink-3)'; },
      addField(type){ const m=this.tmeta(type); const n=(m.label||type).toLowerCase().replace(/[^a-z0-9]+/g,'_'); this.editing.fields.push({ id:D().uid(), label:m.label, name:n, key:n, type, required:false }); this.addMenu=false; },
      del(i){ this.editing.fields.splice(i,1); },
      newComponent(){ this.editing = { id: 'cmp_' + Math.random().toString(36).slice(2,7), name: 'New block', icon: 'layers', version: 1, usedIn: 0, desc: '', fields: [] }; this.sel = null; },
      saveComponent(){
        this.saving = true;
        window.__mdlr.store.saveComponent(this.editing)
          .then(() => { this.saving = false; this.editing = null; })
          .catch((e) => { this.saving = false; console.error('[components] save failed', e); alert('Saving the component failed. Check the console.'); });
      },
    },
    template: `
    <!-- LIBRARY -->
    <div v-if="!editing" class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Blocks</h1><p class="page-sub" style="margin-top:6px">Named, reusable, versioned content blocks built from fields. Authors drop them into a <span class="t-key">blocks</span> field and arrange them in the visual editor. Same fields as field groups, composed visually instead of as a form.</p></div>
        <button class="btn btn--accent" @click="newComponent"><Icon name="plus"/> New block</button>
      </div>
      <div class="comp-lib">
        <div v-for="c in list" :key="c.id" class="comp-card" @click="open(c)">
          <div class="comp-card-top">
            <span class="comp-card-ico"><Icon :name="c.icon" :size="20"/></span>
            <UiMenu :items="[{label:'Edit',icon:'pencil',onClick:()=>open(c)},{label:'Duplicate',icon:'copy'},{sep:true},{label:'Delete',icon:'trash',danger:true}]"><button class="iconbtn iconbtn--sm" @click.stop><Icon name="drag" :size="15"/></button></UiMenu>
          </div>
          <strong class="comp-card-name">{{ c.name }}</strong>
          <p class="comp-card-desc">{{ c.desc }}</p>
          <div class="comp-card-foot">
            <span class="t-key">{{ c.fields.length }} fields</span>
            <span class="badge"><Icon name="component" :size="10"/> v{{ c.version }}</span>
            <span class="t-meta" style="margin-left:auto">used {{ c.usedIn }}×</span>
          </div>
          <button class="comp-card-visual" @click.stop="openVisual(c)"><Icon name="wand" :size="14"/> Edit visually</button>
        </div>
      </div>
    </div>

    <!-- EDITOR -->
    <div v-else class="fb">
      <div class="fb-top">
        <div class="fb-top-l">
          <button class="iconbtn" @click="editing=null"><Icon name="chevLeft"/></button>
          <span class="comp-card-ico" style="width:34px;height:34px"><Icon :name="editing.icon" :size="17"/></span>
          <div><div class="t-eyebrow">Component</div><strong style="font-size:16px;font-family:var(--font-display)">{{ editing.name }}</strong></div>
        </div>
        <div class="fb-top-r">
          <span class="badge"><Icon name="component" :size="11"/> v{{ editing.version }}</span>
          <button class="btn btn--ghost btn--sm" @click="openVisual()"><Icon name="wand" :size="14"/> Edit visually</button>
          <button class="btn btn--ghost btn--sm"><Icon name="git" :size="14"/> New version</button>
          <button class="btn" :disabled="saving" @click="saveComponent"><Icon name="save" :size="15"/> {{ saving ? 'Saving…' : 'Save component' }}</button>
        </div>
      </div>
      <div class="comp-edit">
        <div class="comp-edit-inner">
          <div class="comp-edit-meta card card-pad">
            <div class="cfg-2">
              <div class="field" style="margin:0"><label class="field-label">Name</label><input class="inp" v-model="editing.name"></div>
              <div class="field" style="margin:0"><label class="field-label">API key</label><div class="inp-group"><span class="affix">block</span><input class="inp mono" :value="editing.id.replace('cmp_','')"></div></div>
            </div>
            <div class="field" style="margin:14px 0 0"><label class="field-label">Description</label><input class="inp" v-model="editing.desc"></div>
          </div>

          <button class="comp-visual-cta" @click="openVisual()">
            <span class="comp-visual-ico"><Icon name="wand" :size="18"/></span>
            <span class="comp-visual-main"><strong>Design the layout visually</strong><span class="t-meta">Arrange images, text, CTAs and video as draggable layers — the live block preview.</span></span>
            <Icon name="chevRight" :size="18" class="muted"/>
          </button>

          <div class="flex items-center justify-between" style="margin:24px 0 12px">
            <h2 class="t-title t-h3" style="margin:0">Fields</h2>
            <UiMenu align="right" :items="cats.flatMap(c=>[{sep:true,label:c.label}].concat(c.items.map(t=>({label:t.label,icon:t.icon,onClick:()=>addField(t.type)})))).slice(1)">
              <button class="btn btn--ghost btn--sm"><Icon name="plus" :size="15"/> Add field</button>
            </UiMenu>
          </div>

          <div class="fb-rows">
            <div v-for="(f,i) in editing.fields" :key="f.id" class="fb-row" @click="sel=f">
              <Icon name="drag" :size="16" class="drag-handle"/>
              <span class="lib-ico fb-row-ico" :style="{color: tcolor(f.type), background:'color-mix(in srgb,'+tcolor(f.type)+' 12%,transparent)'}"><Icon :name="tmeta(f.type).icon" :size="15"/></span>
              <div class="fb-row-main"><div class="fb-row-label">{{ f.label }}<span class="field-req" v-if="f.required">*</span></div><div class="t-key">{{ f.key }}</div></div>
              <span class="typetag">{{ f.type }}</span>
              <div class="fb-row-actions" @click.stop>
                <button class="iconbtn iconbtn--sm"><Icon name="settings" :size="15"/></button>
                <button class="iconbtn iconbtn--sm is-danger" @click="del(i)"><Icon name="trash" :size="15"/></button>
              </div>
            </div>
          </div>
          <div class="empty" v-if="!editing.fields.length"><Icon name="box"/><div>No fields yet.</div></div>
        </div>
      </div>

      <!-- visual block editor -->
      <VisualBlockEditor v-if="visual" :block="visual" :comp="editing" @close="visual=null"/>
    </div>`,
  };
})();
;
/* file: screen-binding.js */
/* ============================================================
   FIELD GROUPS — ACF-style hub.
   List of field groups: order (drag + number), location rules,
   placement, fields mode, status. Duplicate / trash / reorder.
   Editing opens the field-group editor (fieldBuilder).
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const POS = { high:'High', normal:'Normal', side:'Side' };

  C.ScreenBinding = {
    name: 'ScreenBinding',
    inject: ['go'],
    data() { return { dragI: null, dropAt: null }; },
    computed: {
      groups() { return D().FIELD_GROUPS; },
    },
    methods: {
      ptName(id) { const p = D().POST_TYPES.find(x => x.id === id); return p ? p.name : id; },
      termName(id) { for (const t of D().TAXONOMIES) { const x = (t.terms||[]).find(tm => tm.id === id || tm.slug === id); if (x) return x.name; } return id; },
      posLabel(p) { return POS[p] || 'Normal'; },
      ruleLabel(r) {
        if (r.param === 'post_type') { if (r.op === '!=' && !r.value) return 'All post types'; return (r.op==='!='?'Not ':'') + this.ptName(r.value); }
        if (r.param === 'post') { const p = D().POSTS.find(x => String(x.id) === String(r.value)); return 'Post: ' + (p ? p.title : r.value); }
        if (r.param === 'post_template') return 'Template: ' + r.value;
        if (r.param === 'taxonomy') return this.termName(r.value);
        if (r.param === 'user_role') return 'Role: ' + r.value;
        return r.value;
      },
      locChips(g) { return (g.location || []).map(orGroup => orGroup.map(r => this.ruleLabel(r)).join(' + ')); },
      modeMix(g) {
        const mods = (g.fields || []).filter(f => f.mode === 'modular').length;
        if (mods === 0) return { label:'all fixed', cls:'' };
        if (mods === g.fields.length) return { label:'all modular', cls:'badge--info' };
        return { label: mods + ' modular', cls:'badge--info' };
      },
      renumber() { this.groups.forEach((g, i) => { g.order = i + 1; }); },
      duplicate(g) {
        if (!isNaN(Number(g.id))) {
          const copy = Object.assign(JSON.parse(JSON.stringify(g)), { name: g.name + ' copy', active: false });
          delete copy.id; delete copy.slug;
          window.__mdlr.store.saveFieldGroup(copy);
          return;
        }
        const i = this.groups.indexOf(g);
        const copy = JSON.parse(JSON.stringify(g));
        copy.id = 'fg_' + Math.random().toString(36).slice(2, 7);
        copy.name = g.name + ' copy';
        copy.active = false;
        this.groups.splice(i + 1, 0, copy);
        this.renumber();
      },
      trash(g) {
        if (!isNaN(Number(g.id))) { window.__mdlr.store.deleteFieldGroup(g.id); return; }
        const i = this.groups.indexOf(g); if (i >= 0) this.groups.splice(i, 1); this.renumber();
      },
      // drag reorder
      dStart(i, e) { this.dragI = i; if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', String(i)); } catch (_) {} } },
      dOver(i, e) { e.preventDefault(); const r = e.currentTarget.getBoundingClientRect(); this.dropAt = (e.clientY - r.top) > r.height/2 ? i+1 : i; },
      dDrop() { if (this.dragI == null) return; let at = this.dropAt == null ? this.groups.length : this.dropAt; const it = this.groups[this.dragI]; this.groups.splice(this.dragI, 1); if (this.dragI < at) at -= 1; this.groups.splice(at, 0, it); this.dragI = null; this.dropAt = null; this.renumber(); if (window.__mdlr.store) window.__mdlr.store.reorderFieldGroups(this.groups); },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <h1 class="t-title t-h1">Field groups</h1>
          <p class="page-sub" style="margin-top:6px">Reusable sets of fields. Each group decides <strong style="color:var(--ink-2)">where</strong> it shows (location), <strong style="color:var(--ink-2)">where on the screen</strong> (placement) and <strong style="color:var(--ink-2)">how</strong> each field behaves. <ModeHelp/></p>
        </div>
        <button class="btn btn--accent" @click="go('fieldBuilder',{group:'new'})"><Icon name="plus"/> New field group</button>
      </div>

      <div class="card">
        <table class="tbl tbl--rows" @dragover.prevent @drop="dDrop">
          <thead><tr><th style="width:72px">Order</th><th>Field group</th><th>Location</th><th style="width:120px">Placement</th><th style="width:140px">Fields</th><th style="width:96px">Status</th></tr></thead>
          <tbody>
            <tr v-for="(g,gi) in groups" :key="g.id" :class="{'is-dragging': dragI===gi, 'fg-drop': dropAt===gi}"
                draggable="true" @dragstart="dStart(gi,$event)" @dragend="dragI=null;dropAt=null" @dragover="dOver(gi,$event)" @drop="dDrop">
              <td>
                <div class="fg-order"><Icon name="drag" :size="15" class="drag-handle"/><span class="fg-num">{{ g.order }}</span></div>
              </td>
              <td>
                <div class="flex items-center gap-3">
                  <span class="dash-row-ico"><Icon :name="g.icon" :size="16"/></span>
                  <div>
                    <a class="ce-title" @click="go('fieldBuilder',{group:g.id})">{{ g.name }}</a>
                    <div class="t-meta">{{ g.desc }}</div>
                    <div class="row-actions">
                      <button @click="go('fieldBuilder',{group:g.id})">Edit</button>
                      <button @click="go('fieldBuilder',{group:g.id,view:'location'})">Location</button>
                      <button @click="duplicate(g)"><Icon name="copy" :size="12"/> Duplicate</button>
                      <button class="is-danger" @click="trash(g)">Trash</button>
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <div class="flex gap-2 wrap">
                  <span class="loc-chip" v-for="(c,i) in locChips(g)" :key="i"><Icon name="pin" :size="11"/>{{ c }}</span>
                </div>
              </td>
              <td><span class="badge"><Icon name="panelLeft" :size="11"/> {{ posLabel(g.position) }}</span></td>
              <td><span class="mono muted" style="margin-right:8px">{{ g.fields.length }}</span><span class="badge" :class="modeMix(g).cls">{{ modeMix(g).label }}</span></td>
              <td><span class="badge" :class="g.active?'badge--ok':''"><span class="badge--dot" v-if="g.active"></span>{{ g.active ? 'active' : 'inactive' }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="fg-aside">
        <Icon name="info" :size="15"/>
        <span>Drag the handle to set the order — groups render top-to-bottom by their number. A post type's editor shows the union of every <strong>active</strong> group whose location matches. Reusable blocks live under <a class="mode-help-link" style="display:inline-flex" @click.prevent="go('components')">Components</a>.</span>
      </div>
    </div>`,
  };
})();
;
/* file: screen-content.js */
/* ============================================================
   CONTENT: list + the per-post EDITOR.
   Shows the RESOLVED, effective fieldset with provenance,
   tabs (HOW), columns, and the drag-drop component block list.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  /* Column visibility, remembered between sessions. `order` is off by default:
     it only earns its place when you actually sort by manual order. */
  const COL_KEY = 'mdlr.contentCols';
  const COL_DEFAULTS = { status: true, author: true, date: true, slug: false, order: false };
  function loadCols() {
    try { return Object.assign({}, COL_DEFAULTS, JSON.parse(localStorage.getItem(COL_KEY) || '{}')); }
    catch (e) { return Object.assign({}, COL_DEFAULTS); }
  }
  function saveCols(cols) {
    try { localStorage.setItem(COL_KEY, JSON.stringify(cols)); } catch (e) {}
  }

  /* ---- a single field's control, rendered by type ---- */
  C.FieldControl = {
    name: 'FieldControl',
    props: { field: Object, dense: Boolean },
    data() {
      const rel = ['relationship','post_object','taxonomy','user'].includes(this.field.type);
      const pool = window.__mdlr.data.POSTS;
      const today = new Date(2026, 4, 21);
      return {
        v: this.field.value != null ? this.field.value : '',
        cropOpen:false, replaceOpen:false, img: { hue:34, name:'cover.jpg', w:2400, h:1600 },
        relSel: rel ? pool.filter(p=>p.type==='project').slice(0,2).map(p=>p.id) : [],
        relQ:'', relOpen:false, relDrag:null,
        // date/time
        calOpen:false, calMonth:today.getMonth(), calYear:today.getFullYear(), calSel:21, calTime:'10:00',
        // choice
        cbxSel: ['a'], radioSel:'a',
        // markdown
        mdTab:'write', mdText:'## Section\\n\\nWrite **markdown** here. Lists, _emphasis_, and `code` all work.',
      };
    },
    computed: {
      relPool() { return window.__mdlr.data.POSTS; },
      relAvailable() { const q=this.relQ.trim().toLowerCase(); return this.relPool.filter(p => !this.relSel.includes(p.id) && (!q || p.title.toLowerCase().includes(q))); },
      isTax() { return this.field.type==='taxonomy'; },
      isUser() { return this.field.type==='user'; },
      calCells() {
        const first=new Date(this.calYear,this.calMonth,1).getDay(); const lead=(first+6)%7;
        const dim=new Date(this.calYear,this.calMonth+1,0).getDate(); const cells=[];
        for(let i=0;i<lead;i++)cells.push(null); for(let d=1;d<=dim;d++)cells.push(d); return cells;
      },
      dateDisplay() {
        if (this.field.type==='time') return this.calTime;
        const mn = new Date(this.calYear,this.calMonth,1).toLocaleDateString('en-US',{month:'short'});
        const base = this.calSel+' '+mn+' '+this.calYear;
        return this.field.type==='datetime' ? base+' · '+this.calTime : base;
      },
    },
    methods: {
      grad(h){ return `linear-gradient(135deg, hsl(${h} 30% 80%), hsl(${h-8} 24% 58%))`; },
      single(){ return this.field.type==='post_object'; },
      postById(id){ return this.relPool.find(p=>p.id===id) || {}; },
      typeName(t){ const p=window.__mdlr.data.POST_TYPES.find(x=>x.id===t); return p?p.name:t; },
      addRel(id){ if(this.single()) this.relSel=[id]; else this.relSel.push(id); if(this.single()) this.relOpen=false; this.relQ=''; },
      removeRel(id){ this.relSel = this.relSel.filter(x=>x!==id); },
      relStart(i,e){ this.relDrag=i; e.dataTransfer.effectAllowed='move'; },
      relDrop(i,e){ e.preventDefault(); if(this.relDrag==null) return; const it=this.relSel[this.relDrag]; this.relSel.splice(this.relDrag,1); this.relSel.splice(i,0,it); this.relDrag=null; },
      monthName(){ return new Date(this.calYear,this.calMonth,1).toLocaleDateString('en-US',{month:'long',year:'numeric'}); },
      pickDay(d){ this.calSel=d; if(this.field.type==='date') this.calOpen=false; },
      cbxToggle(k){ const i=this.cbxSel.indexOf(k); if(i>=0)this.cbxSel.splice(i,1); else this.cbxSel.push(k); },
    },
    template: `
    <div class="fc">
      <!-- text-likes -->
      <input v-if="['text','email','number','password'].includes(field.type)" class="inp" :type="field.type==='number'?'number':'text'" v-model="v" :placeholder="field.placeholder||''">
      <div v-else-if="field.type==='url'" class="inp-group"><span class="affix">https://</span><input class="inp mono" v-model="v" placeholder="example.com"></div>
      <textarea v-else-if="field.type==='textarea'" class="ta" v-model="v" :placeholder="field.placeholder||''"></textarea>

      <!-- rich text -->
      <div v-else-if="field.type==='wysiwyg'" class="rte">
        <div class="rte-bar">
          <button class="rte-b"><strong>B</strong></button><button class="rte-b"><em>I</em></button><button class="rte-b" style="text-decoration:underline">U</button>
          <span class="rte-sep"></span>
          <button class="rte-b"><Icon name="list" :size="15"/></button><button class="rte-b"><Icon name="link" :size="15"/></button>
          <span class="rte-sep"></span><button class="rte-b"><Icon name="image" :size="15"/></button><button class="rte-b"><Icon name="code" :size="15"/></button>
        </div>
        <div class="rte-body">{{ v || 'A grounded helpdesk built on living digital twins — written for the people who actually run the building. Calm, legible, and fast to scan.' }}</div>
      </div>

      <!-- choice -->
      <select v-else-if="field.type==='select'" class="sel" v-model="v"><option value="">Choose…</option><option>Warm</option><option>Cool</option><option>Neutral</option></select>
      <div v-else-if="field.type==='true_false'" class="flex items-center gap-3"><UiSwitch v-model="v"/><span class="t-meta">{{ v ? 'On' : 'Off' }}</span></div>
      <div v-else-if="field.type==='button_group'" class="seg"><button :class="{'is-on':v==='left'}" @click="v='left'">Left</button><button :class="{'is-on':v==='right'}" @click="v='right'">Right</button></div>

      <!-- date / datetime / time -->
      <div v-else-if="['date','datetime','time'].includes(field.type)" class="dtf">
        <button type="button" class="dt-control" :class="{'is-open':calOpen}" @click="calOpen=!calOpen">
          <Icon :name="field.type==='time'?'clock':'calendar'" :size="15" class="muted"/>
          <span>{{ dateDisplay }}</span>
          <Icon name="chevDown" :size="15" class="muted" style="margin-left:auto"/>
        </button>
        <template v-if="calOpen">
          <div class="iconpick-scrim" @click="calOpen=false"></div>
          <div class="dt-pop">
            <div class="cal" v-if="field.type!=='time'">
              <div class="cal-head"><button class="iconbtn iconbtn--sm" @click="calMonth=(calMonth+11)%12"><Icon name="chevLeft" :size="16"/></button><strong>{{ monthName() }}</strong><button class="iconbtn iconbtn--sm" @click="calMonth=(calMonth+1)%12"><Icon name="chevRight" :size="16"/></button></div>
              <div class="cal-dow"><span v-for="d in ['Mo','Tu','We','Th','Fr','Sa','Su']" :key="d">{{ d }}</span></div>
              <div class="cal-grid"><template v-for="(c,i) in calCells" :key="i"><span v-if="c===null" class="cal-cell cal-cell--empty"></span><button v-else type="button" class="cal-cell" :class="{'is-sel':calSel===c}" @click="pickDay(c)">{{ c }}</button></template></div>
            </div>
            <div class="cal-time" v-if="field.type!=='date'" :style="field.type==='time'?{borderTop:0,paddingTop:0}:{}"><Icon name="clock" :size="15" class="muted"/><input class="inp" style="max-width:110px" v-model="calTime"><span class="t-meta">24h</span></div>
            <div class="dt-foot" v-if="field.type!=='date'"><button class="btn btn--accent btn--sm" @click="calOpen=false">Done</button></div>
          </div>
        </template>
      </div>

      <!-- markdown -->
      <div v-else-if="field.type==='markdown'" class="mdf">
        <div class="mdf-bar"><div class="seg"><button :class="{'is-on':mdTab==='write'}" @click="mdTab='write'">Write</button><button :class="{'is-on':mdTab==='preview'}" @click="mdTab='preview'">Preview</button></div><span class="t-meta mono">Markdown</span></div>
        <textarea v-if="mdTab==='write'" class="ta mono" v-model="mdText" style="min-height:120px;border-radius:0 0 var(--r-sm) var(--r-sm)"></textarea>
        <div v-else class="mdf-preview"><h3>Section</h3><p>Write <strong>markdown</strong> here. Lists, <em>emphasis</em>, and <span class="t-key">code</span> all work.</p></div>
      </div>

      <!-- checkbox -->
      <div v-else-if="field.type==='checkbox'" class="optset">
        <label v-for="o in [['a','Email updates'],['b','SMS reminders'],['c','Product news']]" :key="o[0]" class="optrow" :class="{'is-on':cbxSel.includes(o[0])}"><UiChk :modelValue="cbxSel.includes(o[0])" @update:modelValue="cbxToggle(o[0])"/><span>{{ o[1] }}</span></label>
      </div>

      <!-- radio -->
      <div v-else-if="field.type==='radio'" class="optset">
        <label v-for="o in [['a','Weekly'],['b','Monthly'],['c','Never']]" :key="o[0]" class="optrow" :class="{'is-on':radioSel===o[0]}" @click="radioSel=o[0]"><span class="optradio" :class="{'is-on':radioSel===o[0]}"></span><span>{{ o[1] }}</span></label>
      </div>

      <!-- image -->
      <div v-else-if="field.type==='image'" class="fc-img">
        <div class="img-slot" :style="{backgroundImage: grad(img.hue)}">
          <div class="img-slot-actions"><button class="btn btn--soft btn--xs" @click="cropOpen=true"><Icon name="crop" :size="13"/> Crop</button><button class="btn btn--soft btn--xs" @click="replaceOpen=true"><Icon name="refresh" :size="13"/> Replace</button></div>
          <span class="img-slot-meta mono">{{ img.name }} · {{ img.w }}×{{ img.h }}</span>
        </div>
        <ImageCropper v-if="cropOpen" :img="img" @close="cropOpen=false"/>
        <MediaPicker v-if="replaceOpen" @close="replaceOpen=false" @pick="m=>{ img={hue:m.hue,name:m.name,w:m.w,h:m.h} }"/>
      </div>

      <!-- gallery -->
      <div v-else-if="field.type==='gallery'" class="gal">
        <div class="gal-cell" v-for="i in 4" :key="i" :style="{background:'linear-gradient(135deg, hsl('+(28+i*6)+' 26% '+(82-i*3)+'%), hsl('+(24+i*4)+' 22% '+(66-i*2)+'%))'}"></div>
        <button class="gal-add"><Icon name="plus" :size="18"/></button>
      </div>

      <!-- repeater -->
      <div v-else-if="field.type==='repeater'" class="rep">
        <div class="rep-row" v-for="(r,ri) in 3" :key="ri"><Icon name="drag" :size="14" class="drag-handle"/><input class="inp" :value="['Role','Year','Stack'][ri]"><input class="inp" :value="['Design & build','2026','Vue · PHP'][ri]"><button class="iconbtn iconbtn--sm is-danger"><Icon name="x" :size="13"/></button></div>
        <button class="btn btn--ghost btn--sm"><Icon name="plus" :size="14"/> Add row</button>
      </div>

      <!-- code -->
      <textarea v-else-if="field.type==='code'" class="ta mono" style="min-height:100px" :value="'<iframe src=&quot;…&quot;></iframe>'"></textarea>

      <!-- relationship / post object / taxonomy / user — ACF-style picker -->
      <div v-else-if="['relationship','post_object','taxonomy','user'].includes(field.type)" class="rel">
        <div class="rel-list" v-if="relSel.length">
          <div class="rel-item" v-for="(id,i) in relSel" :key="id" draggable="true" @dragstart="relStart(i,$event)" @dragover.prevent @drop="relDrop(i,$event)">
            <Icon name="drag" :size="14" class="drag-handle"/>
            <span v-if="isUser" class="avatar" style="width:28px;height:28px;font-size:10px">{{ (postById(id).author||'MT').slice(0,2).toUpperCase() }}</span>
            <span v-else-if="isTax" class="rel-tax-ico"><Icon name="pin" :size="13"/></span>
            <span v-else class="rel-thumb" :style="{background: grad(28 + i*14)}"></span>
            <div class="rel-item-main"><strong>{{ postById(id).title }}</strong><span class="t-meta">{{ isTax ? 'Term' : isUser ? postById(id).author+'@atypisch.nl' : typeName(postById(id).type)+' · '+postById(id).status }}</span></div>
            <button class="iconbtn iconbtn--sm is-danger" @click="removeRel(id)"><Icon name="x" :size="13"/></button>
          </div>
        </div>
        <div class="rel-empty" v-else>{{ isTax ? 'No terms selected.' : isUser ? 'No user selected.' : 'No related items yet.' }}</div>
        <button class="rel-add-btn" @click="relOpen=true" v-if="!single() || !relSel.length"><Icon name="plus" :size="14"/> {{ isTax ? 'Add terms' : isUser ? 'Choose people' : (single() ? 'Choose an item' : 'Add related items') }}</button>
        <template v-if="relOpen">
          <div class="iconpick-scrim" @click="relOpen=false"></div>
          <div class="rel-pop">
            <div class="fb-lib-search" style="margin:0 0 8px"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="relQ" placeholder="Search content…" style="color:var(--ink)"></div>
            <div class="rel-pool">
              <button v-for="p in relAvailable" :key="p.id" class="rel-pool-item" @click="addRel(p.id)">
                <span class="rel-thumb"></span>
                <div class="rel-item-main"><strong>{{ p.title }}</strong><span class="t-meta">{{ typeName(p.type) }}</span></div>
                <Icon name="plus" :size="15" class="muted"/>
              </button>
              <div v-if="!relAvailable.length" class="t-meta" style="padding:10px;text-align:center">Nothing to add.</div>
            </div>
          </div>
        </template>
      </div>

      <!-- user -->
      <div v-else-if="field.type==='user'" class="rel-item" style="cursor:default"><span class="avatar" style="width:28px;height:28px;font-size:10px">MT</span><div class="rel-item-main"><strong>Marten Timan</strong><span class="t-meta">marten@atypisch.nl</span></div><button class="btn btn--ghost btn--sm">Change</button></div>

      <!-- color -->
      <div v-else-if="field.type==='color'" class="flex items-center gap-2"><span class="fc-swatch" :style="{background: v||'#C0552F'}"></span><input class="inp mono" style="max-width:150px" v-model="v" placeholder="#C0552F"></div>

      <!-- range -->
      <div v-else-if="field.type==='range'" class="flex items-center gap-3"><input type="range" v-model="v" style="flex:1;accent-color:var(--accent)"><span class="t-key" style="width:34px">{{ v||0 }}</span></div>

      <!-- file -->
      <div v-else-if="field.type==='file'" class="form-file"><Icon name="upload" :size="15"/> Choose file…</div>

      <input v-else class="inp" v-model="v">
    </div>`,
  };

  /* ---- a component block in the editor's block list ---- */
  C.BlockCard = {
    name: 'BlockCard',
    props: { block: Object, comp: Object, index: Number },
    emits: ['toggle','dup','del','visual','dragstart','dragend','over','drop'],
    template: `
    <div class="block" :class="{'is-collapsed': block.collapsed}"
         draggable="true" @dragstart="$emit('dragstart',$event)" @dragend="$emit('dragend',$event)"
         @dragover="$emit('over',$event)" @drop="$emit('drop',$event)">
      <div class="block-head" @click="$emit('visual')">
        <Icon name="drag" :size="16" class="drag-handle" @click.stop/>
        <span class="block-ico"><Icon :name="comp.icon" :size="15"/></span>
        <div class="block-head-main">
          <strong>{{ comp.name }}</strong>
          <span class="t-meta">{{ block.summary }}</span>
        </div>
        <span class="block-edithint"><Icon name="wand" :size="13"/> Edit visually</span>
        <span class="prov prov--comp"><Icon name="component" :size="11"/> v{{ comp.version }}</span>
        <div class="block-actions" @click.stop>
          <button class="iconbtn iconbtn--sm" @click="$emit('visual')" title="Edit visually"><Icon name="wand" :size="15"/></button>
          <button class="iconbtn iconbtn--sm" @click="$emit('dup')" title="Duplicate"><Icon name="copy" :size="15"/></button>
          <button class="iconbtn iconbtn--sm is-danger" @click="$emit('del')" title="Delete"><Icon name="trash" :size="15"/></button>
          <button class="iconbtn iconbtn--sm" @click="$emit('toggle')" :title="block.collapsed?'Show fields':'Hide fields'"><Icon :name="block.collapsed?'chevDown':'chevUp'" :size="16"/></button>
        </div>
      </div>
      <div class="block-body" v-show="!block.collapsed">
        <div class="field" v-for="f in comp.fields" :key="f.id">
          <label class="field-label">{{ f.label }}<span class="field-req" v-if="f.required">*</span></label>
          <FieldControl :field="f"/>
        </div>
      </div>
    </div>`,
  };

  /* ---- CONTENT LIST ---- */
  C.ScreenContentList = {
    name: 'ScreenContentList',
    inject: ['go','route'],
    data() { return { tab:'all', q:'', sel: [], items: [], quickId: null, qdraft: null,
      page: 1, perPage: 50, total: 0, countAll: 0, countTrash: 0, loading: false, _searchTimer: null,
      statuses:['published','draft','scheduled'], authors:[],
      // Sorting. Publication date first — that is the order editors think in,
      // and it is what the date column shows.
      orderby: 'date', order: 'desc',
      // Filter popover (author + publication window). Status lives in the tabs.
      filterOpen: false, fAuthor: '', fFrom: '', fTo: '',
      // Columns popover. Title is structural and always shown.
      colsOpen: false, cols: loadCols(),
      // Manual ordering: drag a row to a new position and the whole page is
      // renumbered server-side. Only offered on the unfiltered first page —
      // reordering a filtered or paginated slice would renumber the wrong set.
      dragI: null, dropAt: null, ordering: false }; },
    created() { this.load(); },
    computed: {
      pt() { return D().POST_TYPES.find(p => p.id === (this.route.params||{}).type) || D().POST_TYPES[0]; },
      // Server already applies status + search + pagination; only the optimistic _trash
      // toggle (trash/restore) is reflected client-side so rows appear/vanish instantly.
      filtered() {
        return this.tab === 'trash' ? this.items.filter(p => p._trash) : this.items.filter(p => !p._trash);
      },
      counts() { return { all: this.countAll, trash: this.countTrash }; },
      totalPages() { return Math.max(1, Math.ceil(this.total / this.perPage)); },
      allOn() { return this.filtered.length>0 && this.filtered.every(p => this.sel.includes(p.id)); },
      // Dragging renumbers `order`, so it only makes sense while the list is
      // actually sorted by manual order — on any other sort the rows would jump
      // straight back on reload.
      canReorder() { return this.orderby === 'order' && this.order === 'asc' && this.tab === 'all' && !this.q && !this.hasFilters && this.page === 1 && this.filtered.length > 1; },
      hasFilters() { return !!(this.fAuthor || this.fFrom || this.fTo); },
      filterCount() { return (this.fAuthor ? 1 : 0) + (this.fFrom ? 1 : 0) + (this.fTo ? 1 : 0); },
      // Title + checkbox are always there; the rest is what the Columns popover allows.
      colSpan() { return 2 + (this.canReorder ? 1 : 0) + ['status','author','date','slug','order'].filter(k => this.cols[k]).length; },
    },
    watch: {
      pt() { this.tab = 'all'; this.q = ''; this.page = 1; this.load(); },
      tab() { this.page = 1; this.sel = []; this.load(); },
      q() { clearTimeout(this._searchTimer); this._searchTimer = setTimeout(() => { this.page = 1; this.load(); }, 300); },
    },
    methods: {
      dStart(i, e) { if (!this.canReorder) return; this.dragI = i; if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', String(i)); } catch (_) {} } },
      dOver(i, e) { if (this.dragI == null) return; e.preventDefault(); const r = e.currentTarget.getBoundingClientRect(); this.dropAt = (e.clientY - r.top) > r.height / 2 ? i + 1 : i; },
      dEnd() { this.dragI = null; this.dropAt = null; },
      dDrop() {
        if (this.dragI == null) { this.dEnd(); return; }
        let at = this.dropAt == null ? this.filtered.length : this.dropAt;
        const rows = this.filtered.slice();
        const moved = rows[this.dragI];
        rows.splice(this.dragI, 1);
        if (this.dragI < at) at -= 1;
        rows.splice(at, 0, moved);
        this.dEnd();
        if (!moved) return;
        // Reflect the new order immediately, then persist it.
        const byId = {}; rows.forEach((r, n) => { byId[r.id] = n; });
        this.items.sort((a, b) => (byId[a.id] ?? 1e9) - (byId[b.id] ?? 1e9));
        const api = window.__mdlr.api;
        if (!api) return;
        this.ordering = true;
        api.post('/posts/reorder', { ids: rows.map(r => r.id) })
          .catch(() => { this.load(); })
          .then(() => { this.ordering = false; });
      },
      load() {
        const pt = this.pt;
        const dbId = pt && pt._dbId;
        const api = window.__mdlr.api;
        if (!dbId || !api) {
          this.items = [];
          this.total = 0;
          return;
        }
        const params = new URLSearchParams();
        params.set('post_type_id', dbId);
        params.set('fields', 'list');
        params.set('page', this.page);
        params.set('per_page', this.perPage);
        if (this.tab === 'trash') params.set('status', 'trash');
        else if (this.tab !== 'all') params.set('status', this.tab);
        const q = this.q.trim();
        if (q) params.set('q', q);
        params.set('orderby', this.orderby);
        params.set('order', this.order);
        if (this.fAuthor) params.set('author', this.fAuthor);
        if (this.fFrom) params.set('date_from', this.fFrom);
        if (this.fTo) params.set('date_to', this.fTo);
        this.loading = true;
        api.get('/posts?' + params.toString()).then(resp => {
          const rows = (resp && resp.items) || [];
          this.items = rows.map(p => ({
            id: p.id, title: p.title || '(untitled)',
            slug: p.slug || '', status: p.status || 'draft',
            date: (p.published_at || p.created_at || '').slice(0, 10),
            order: Number(p.order) || 0,
            type: pt.id, author: p.author_name || '', url: p.url || '',
            _trash: p.status === 'trash' || !!p.deleted_at,
          }));
          this.total = (resp && resp.total) || 0;
          const c = (resp && resp.counts) || {};
          this.countAll = c.all || 0;
          this.countTrash = c.trash || 0;
          this.authors = (resp && resp.authors) || [];
        }).catch((e) => { console.warn('[content] load posts failed', e && e.status); this.items = []; this.total = 0; })
          .finally(() => { this.loading = false; });
      },
      // Click a header to sort by it; click the active one again to flip direction.
      sortBy(key) {
        if (this.orderby === key) this.order = this.order === 'asc' ? 'desc' : 'asc';
        else { this.orderby = key; this.order = key === 'title' || key === 'order' ? 'asc' : 'desc'; }
        this.page = 1; this.sel = []; this.load();
      },
      sortIcon(key) { return this.orderby !== key ? '' : (this.order === 'asc' ? '↑' : '↓'); },
      toggleCol(key) { this.cols[key] = !this.cols[key]; saveCols(this.cols); },
      applyFilters() { this.filterOpen = false; this.page = 1; this.sel = []; this.load(); },
      clearFilters() { this.fAuthor = ''; this.fFrom = ''; this.fTo = ''; this.applyFilters(); },
      goPage(p) { const n = Math.min(this.totalPages, Math.max(1, p)); if (n !== this.page) { this.page = n; this.sel = []; this.load(); } },
      statusClass(s){ return s==='published'?'badge--ok':s==='scheduled'?'badge--info':s==='draft'?'badge--warn':''; },
      singular(){ return this.pt.name.replace(/s$/,'').toLowerCase(); },
      openEditor(p){ this.go('editor',{postId:p.id,type:this.pt.id}); },
      quickEdit(p){ this.quickId = p.id; this.qdraft = JSON.parse(JSON.stringify(p)); },
      saveQuick() {
        const store = window.__mdlr.store;
        if (!store || !this.qdraft || isNaN(Number(this.quickId))) return;
        store.updatePost(this.quickId, {
          title: this.qdraft.title,
          slug: this.qdraft.slug,
          status: this.qdraft.status,
        }).then(() => {
          const i = this.items.findIndex(x => x.id === this.quickId);
          if (i >= 0) Object.assign(this.items[i], this.qdraft);
          this.quickId = null; this.qdraft = null;
        }).catch((e) => { console.warn('[content] quick save failed', e && e.status); });
      },
      cancelQuick(){ this.quickId=null; this.qdraft=null; },
      trash(p) {
        const store = window.__mdlr.store;
        if (!store || isNaN(Number(p.id))) return;
        store.deletePost(p.id).then(() => this.load()).catch((e) => { console.warn('[content] trash failed', e && e.status); });
      },
      duplicate(p) {
        const api = window.__mdlr.api;
        const pt = this.pt;
        if (!api || !pt || !pt._dbId || isNaN(Number(p.id))) return;
        api.post('/posts', {
          post_type_id: pt._dbId,
          title: (p.title || 'Untitled') + ' copy',
          slug: ((p.slug || 'item') + '-copy').replace(/-copy(-copy)+$/g, '-copy'),
          status: 'draft',
        }).then(() => this.load()).catch((e) => { console.warn('[content] duplicate failed', e && e.status); });
      },
      restore(p) {
        const store = window.__mdlr.store;
        if (!store || isNaN(Number(p.id))) return;
        store.restorePost(p.id).then(() => this.load()).catch((e) => { console.warn('[content] restore failed', e && e.status); });
      },
      destroy(p) {
        const store = window.__mdlr.store;
        if (!store || isNaN(Number(p.id))) return;
        store.permanentlyDeletePost(p.id).then(() => this.load()).catch((e) => { console.warn('[content] permanent delete failed', e && e.status); });
      },
      view(p){ const u = (p && p.url) || ((window.__MDLR&&window.__MDLR.baseUrl||'') + '/' + this.pt.id + '/' + (p&&p.slug||'')); window.open(u, '_blank', 'noopener'); },
      toggle(id){ const i=this.sel.indexOf(id); if(i>=0)this.sel.splice(i,1); else this.sel.push(id); },
      toggleAll(){ this.sel = this.allOn ? [] : this.filtered.map(p=>p.id); },
      bulkTrash() {
        const store = window.__mdlr.store;
        if (!store) return;
        Promise.all(this.sel.filter(id => !isNaN(Number(id))).map(id => store.deletePost(id)))
          .then(() => { this.sel = []; this.load(); })
          .catch((e) => { console.warn('[content] bulk trash failed', e && e.status); });
      },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">{{ pt.name }}</h1><p class="page-sub" style="margin-top:6px">{{ counts.all }} items · <span class="t-key">{{ pt.slug }}</span></p></div>
        <button class="btn btn--accent" @click="go('editor',{postId:'new',type:pt.id})"><Icon name="plus"/> New {{ singular() }}</button>
      </div>
      <div class="tabs" style="margin-bottom:16px">
        <button :class="{'is-on':tab==='all'}" @click="tab='all'">All <span class="tab-count">{{ counts.all }}</span></button>
        <button :class="{'is-on':tab==='published'}" @click="tab='published'">Published</button>
        <button :class="{'is-on':tab==='draft'}" @click="tab='draft'">Drafts</button>
        <button :class="{'is-on':tab==='scheduled'}" @click="tab='scheduled'">Scheduled</button>
        <button :class="{'is-on':tab==='trash'}" @click="tab='trash'">Trash <span class="tab-count" v-if="counts.trash">{{ counts.trash }}</span></button>
      </div>
      <div class="card">
        <div class="list-toolbar">
          <div class="topbar-search" style="max-width:280px;flex:0 1 280px;cursor:text"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="q" :placeholder="'Search '+pt.name.toLowerCase()+'…'" style="color:var(--ink)"></div>
          <div v-if="filterOpen || colsOpen" class="ct-pop-dismiss" @click="filterOpen=false; colsOpen=false"></div>
          <div class="ct-pop-wrap">
            <button class="btn btn--ghost btn--sm" :class="{'is-on':hasFilters}" @click="filterOpen=!filterOpen; colsOpen=false">
              <Icon name="filter" :size="14"/> Filter<span v-if="filterCount" class="tab-count">{{ filterCount }}</span>
            </button>
            <div v-if="filterOpen" class="pop ct-pop">
              <div class="field" style="margin:0 0 10px">
                <label class="field-label">Author</label>
                <UiSelect v-model="fAuthor"><option value="">Any author</option><option v-for="a in authors" :key="a" :value="a">{{ a }}</option></UiSelect>
              </div>
              <div class="field" style="margin:0 0 10px">
                <label class="field-label">Published from</label>
                <input class="inp mono" type="date" v-model="fFrom">
              </div>
              <div class="field" style="margin:0 0 12px">
                <label class="field-label">Published until</label>
                <input class="inp mono" type="date" v-model="fTo">
              </div>
              <div class="flex gap-2">
                <button class="btn btn--ghost btn--sm" @click="clearFilters">Clear</button>
                <div class="grow"></div>
                <button class="btn btn--accent btn--sm" @click="applyFilters"><Icon name="check" :size="14"/> Apply</button>
              </div>
            </div>
          </div>
          <template v-if="sel.length">
            <span class="badge badge--info">{{ sel.length }} selected</span>
            <button class="btn btn--danger btn--sm" @click="bulkTrash"><Icon name="trash" :size="14"/> Move to trash</button>
          </template>
          <div class="grow"></div>
          <div class="ct-pop-wrap">
            <button class="btn btn--ghost btn--sm" @click="colsOpen=!colsOpen; filterOpen=false"><Icon name="sliders" :size="14"/> Columns</button>
            <div v-if="colsOpen" class="pop ct-pop ct-pop--right">
              <div class="pop-item" @click="toggleCol('status')"><UiChk :modelValue="cols.status"/> <span>Status</span></div>
              <div class="pop-item" @click="toggleCol('author')"><UiChk :modelValue="cols.author"/> <span>Author</span></div>
              <div class="pop-item" @click="toggleCol('date')"><UiChk :modelValue="cols.date"/> <span>Published</span></div>
              <div class="pop-item" @click="toggleCol('slug')"><UiChk :modelValue="cols.slug"/> <span>Slug</span></div>
              <div class="pop-item" @click="toggleCol('order')"><UiChk :modelValue="cols.order"/> <span>Order</span></div>
              <div class="pop-sep"></div>
              <div class="t-meta" style="padding:2px 10px 4px">Sort by Order to drag rows into place.</div>
            </div>
          </div>
        </div>
        <table class="tbl tbl--rows">
          <thead><tr>
            <th style="width:36px"><UiChk :modelValue="allOn" @update:modelValue="toggleAll"/></th>
            <th v-if="canReorder" style="width:34px"></th>
            <th><button class="ct-sort" @click="sortBy('title')">Title <span class="ct-sort-ar">{{ sortIcon('title') }}</span></button></th>
            <th v-if="cols.slug" style="width:180px">Slug</th>
            <th v-if="cols.status" style="width:120px"><button class="ct-sort" @click="sortBy('status')">Status <span class="ct-sort-ar">{{ sortIcon('status') }}</span></button></th>
            <th v-if="cols.author" style="width:140px"><button class="ct-sort" @click="sortBy('author')">Author <span class="ct-sort-ar">{{ sortIcon('author') }}</span></button></th>
            <th v-if="cols.date" style="width:150px"><button class="ct-sort" @click="sortBy('date')">Published <span class="ct-sort-ar">{{ sortIcon('date') }}</span></button></th>
            <th v-if="cols.order" style="width:90px"><button class="ct-sort" @click="sortBy('order')">Order <span class="ct-sort-ar">{{ sortIcon('order') }}</span></button></th>
          </tr></thead>
          <tbody @dragover.prevent @drop="dDrop">
            <template v-for="(p,i) in filtered" :key="p.id">
              <!-- normal row -->
              <tr v-if="quickId!==p.id" :class="{'row-sel': sel.includes(p.id), 'is-dragging': dragI===i, 'is-dropbefore': dragI!=null && dropAt===i}"
                  @dragover="dOver(i,$event)">
                <td @click.stop><UiChk :modelValue="sel.includes(p.id)" @update:modelValue="toggle(p.id)"/></td>
                <td v-if="canReorder" @click.stop>
                  <span class="drag-handle" draggable="true" @dragstart="dStart(i,$event)" @dragend="dEnd" title="Drag to reorder"><Icon name="drag" :size="14"/></span>
                </td>
                <td>
                  <div class="ce-title-cell">
                    <a class="ce-title" @click="openEditor(p)">{{ p.title }}</a>
                    <span v-if="p.status==='draft'" class="ce-draft">— Draft</span>
                    <div class="row-actions" v-if="!p._trash">
                      <button @click="openEditor(p)">Edit</button>
                      <button @click="quickEdit(p)">Quick edit</button>
                      <button @click="duplicate(p)"><Icon name="copy" :size="12"/> Duplicate</button>
                      <button class="is-danger" @click="trash(p)">Trash</button>
                      <button @click="view(p)"><Icon name="eye" :size="12"/> View</button>
                    </div>
                    <div class="row-actions" v-else>
                      <button @click="restore(p)">Restore</button>
                      <button class="is-danger" @click="destroy(p)">Delete permanently</button>
                    </div>
                  </div>
                </td>
                <td v-if="cols.slug" class="muted mono">{{ p.slug }}</td>
                <td v-if="cols.status"><span class="badge" :class="statusClass(p.status)">{{ p.status }}</span></td>
                <td v-if="cols.author" class="muted">{{ p.author }}</td>
                <td v-if="cols.date" class="muted">{{ p.date || '—' }}</td>
                <td v-if="cols.order" class="muted mono">{{ p.order }}</td>
              </tr>
              <!-- quick edit row -->
              <tr v-else class="quick-row">
                <td></td>
                <td v-if="canReorder"></td>
                <td :colspan="colSpan - 1 - (canReorder ? 1 : 0)">
                  <div class="quick-edit">
                    <div class="quick-edit-head"><Icon name="pencil" :size="14"/> Quick edit <span class="t-meta">— the essentials, without opening the editor.</span></div>
                    <div class="quick-grid">
                      <div class="field" style="margin:0"><label class="field-label">Title</label><input class="inp" v-model="qdraft.title"></div>
                      <div class="field" style="margin:0"><label class="field-label">Slug</label><div class="inp-group"><span class="affix">/{{ pt.slug }}/</span><input class="inp mono" v-model="qdraft.slug"></div></div>
                      <div class="field" style="margin:0"><label class="field-label">Status</label><UiSelect v-model="qdraft.status"><option v-for="s in statuses" :key="s" :value="s">{{ s }}</option></UiSelect></div>
                      <div class="field" style="margin:0"><label class="field-label">Author</label><UiSelect v-model="qdraft.author"><option v-for="a in authors" :key="a" :value="a">{{ a }}</option></UiSelect></div>
                      <div class="field" style="margin:0"><label class="field-label">Date</label><input class="inp mono" v-model="qdraft.date"></div>
                    </div>
                    <div class="quick-edit-foot">
                      <button class="btn btn--ghost btn--sm" @click="cancelQuick">Cancel</button>
                      <button class="btn btn--accent btn--sm" @click="saveQuick"><Icon name="check" :size="14"/> Update</button>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-if="!filtered.length"><td :colspan="colSpan"><div class="empty" style="padding:40px"><Icon name="files"/><div>No {{ pt.name.toLowerCase() }} here {{ tab!=='all' ? 'in '+tab : '' }}.</div></div></td></tr>
          </tbody>
        </table>
        <div class="list-toolbar list-pager" v-if="total > perPage">
          <span class="t-meta">{{ total }} items · page {{ page }} of {{ totalPages }}</span>
          <div class="grow"></div>
          <button class="btn btn--ghost btn--sm" :disabled="page<=1" @click="goPage(page-1)"><Icon name="chevLeft" :size="14"/> Prev</button>
          <button class="btn btn--ghost btn--sm" :disabled="page>=totalPages" @click="goPage(page+1)">Next <Icon name="chevRight" :size="14"/></button>
        </div>
      </div>
    </div>`,
  };

  /* ---- CONTENT EDITOR ---- */
  C.ScreenContentEditor = {
    name: 'ScreenContentEditor',
    inject: ['go','route','ui'],
    data() {
      return {
        post: JSON.parse(JSON.stringify(D().SAMPLE_POST)),
        activeTab: 'tab_content',
        tabs: [],
        blocks: [],
        picker: false,
        tray: false,
        visualBlock: null,
        jsonOpen: false,
        drag: null, dropAt: null,
        railOpen: true,
        allCollapsed: false,
        builderCollapsed: false,
        trayGroup: null,
        traySearch: '',
        editingSlug: false,
        terms: { project_type: ['t9'] },
        saving: false,
        _values: {}, _resolvedSchema: null,
      };
    },
    created() {
      const S = {};
      D().PROJECT_SCHEMA.forEach(f => { if (f.type !== 'tab') S[f.name] = f; });
      const fld = (name, w) => { const f = S[name] || { label:name, name, key:name, type:'text' }; return { ...f, w: w||'full', collapsed:false, prov: f.mode==='modular'?'post':'type', locked: f.mode!=='modular' }; };
      const seo = D().FIELD_GROUPS.find(g => g.id==='fg_seo').fields;
      this.tabs = [
        { id:'tab_content', label:'Content', icon:'text', hasBlocks:true, groups:[
          { id:'g_intro', label:'Intro', collapsed:false, prov:'type', fields:[ fld('tagline','full'), fld('body','full') ] },
        ]},
        { id:'tab_media', label:'Media', icon:'image', groups:[
          { id:'g_img', label:'Imagery', collapsed:false, prov:'type', fields:[ fld('cover','half'), fld('gradient_class','half'), fld('gallery','full') ] },
        ]},
        { id:'tab_details', label:'Details', icon:'list', groups:[
          { id:'g_facts', label:'Project facts', collapsed:false, prov:'type', fields:[ fld('client','third'), fld('year','third'), fld('launch_date','third'), fld('topics','full'), fld('project_url','full'), fld('meta_rows','full') ] },
          { id:'g_pub', label:'Publishing', collapsed:false, prov:'type', fields:[ fld('featured','half'), fld('press_quote','full') ] },
        ]},
        { id:'tab_seo', label:'SEO', icon:'globe', shared:true, groups:[
          { id:'g_seo', label:'Search & social', collapsed:false, prov:'shared', fields: seo.map(f => ({ ...f, w: (f.type==='image'||f.type==='true_false')?'half':'full', collapsed:false, prov:'shared', locked:true })) },
        ]},
      ];
      this.blocks = [
        { uid:D().uid(), comp:'cmp_hero', collapsed:false, summary:'Digital Twins Helpdesk' },
        { uid:D().uid(), comp:'cmp_richtext', collapsed:false, summary:'Why we rebuilt it' },
        { uid:D().uid(), comp:'cmp_imgtext', collapsed:true, summary:'The control room' },
        { uid:D().uid(), comp:'cmp_gallery', collapsed:true, summary:'6 images' },
      ];
      // Load the real post's meta + values from the resolved-fieldset API.
      const rp = this.route.params || {};
      const api = window.__mdlr.api;
      if (rp.postId && rp.postId !== 'new' && api) {
        api.get('/posts/' + rp.postId + '/resolved-fieldset').then(rf => {
          if (rf && rf.post) {
            this.post.id = rf.post.id;
            this.post.title = rf.post.title || '';
            this.post.slug = rf.post.slug || '';
            this.post.status = rf.post.status || 'draft';
            this.post.type = (rf.post.post_type && rf.post.post_type.slug) || this.post.type;
            this._values = rf.values || {};
            this._resolvedSchema = (rf.schema && rf.schema.fields) || [];
          }
        }).catch(e => console.warn('[editor] resolved-fieldset failed', e && e.status));
      } else if (rp.postId === 'new') {
        this.post.id = null; this.post.title = ''; this.post.slug = ''; this.post.status = 'draft';
        if (rp.type) this.post.type = rp.type;
      }
    },
    computed: {
      activeTabObj() { return this.tabs.find(t => t.id === this.activeTab) || this.tabs[0]; },
      jsonTabs() {
        const S = window.__mdlr.serialize;
        return [
          { id:'json', label:'JSON', lang:'json', code: S.pretty(S.post(this.post, this.tabs, this.blocks)) },
          { id:'php',  label:'PHP',  lang:'php',  code: S.postPhp(this.post) },
        ];
      },
      availableToAdd() {
        return [
          { label:'Video embed', type:'code', mode:'modular' },
          { label:'Download file', type:'file', mode:'modular' },
          { label:'Related projects', type:'relationship', mode:'modular' },
        ];
      },
      boundTaxonomies() {
        const pt = D().POST_TYPES.find(p => p.id === this.post.type);
        return ((pt && pt.taxonomies) || []).map(id => D().TAXONOMIES.find(t => t.id === id)).filter(Boolean);
      },
      trayCats() {
        const q = this.traySearch.trim().toLowerCase();
        return this.newFieldCats().map(c => ({ ...c, items: c.items.filter(t => !q || t.label.toLowerCase().includes(q) || t.type.includes(q)) })).filter(c => c.items.length);
      },
    },
    methods: {
      D(){ return window.__mdlr.data; },
      ptDbId(){ const pt = D().POST_TYPES.find(p => p.id === this.post.type); return pt && pt._dbId; },
      save(){
        const api = window.__mdlr.api; if (!api) return;
        this.saving = true;
        const body = { title: this.post.title, slug: this.post.slug, status: this.post.status };
        const rp = this.route.params || {};
        const isNew = !this.post.id || rp.postId === 'new';
        const req = isNew
          ? api.post('/posts', Object.assign({ post_type_id: this.ptDbId() }, body))
          : api.put('/posts/' + this.post.id, body);
        req.then(() => { this.saving = false; this.go('content', { type: this.post.type }); })
           .catch(e => { this.saving = false; console.error('[editor] save failed', e); alert('Saving the post failed. Check the console.'); });
      },
      comp(id){ return D().COMPONENTS.find(c => c.id === id); },
      tmeta(t){ return D().TYPE_MAP[t] || {icon:'dot',cat:'util'}; },
      tcolor(t){ return D().CAT_VAR[this.tmeta(t).cat] || 'var(--ink-3)'; },
      provLabel(p){ return p==='post' ? 'Added here' : p==='shared' ? 'Shared group' : 'From post type'; },
      provIcon(p){ return p==='post' ? 'plus' : p==='shared' ? 'git' : 'lock'; },
      provClass(p){ return p==='post' ? 'prov--post' : p==='shared' ? 'prov--comp' : 'prov--type'; },
      preview(f){ if(['image','gallery'].includes(f.type)) return 'set'; if(f.type==='true_false') return f.value?'on':'off'; return f.value || '—'; },
      toggleCollapseAll(){ const v = !this.allCollapsed; this.allCollapsed = v; this.activeTabObj.groups.forEach(g => { g.collapsed = v; }); if(this.activeTabObj.hasBlocks) this.builderCollapsed = v; },
      addGroup(){ this.activeTabObj.groups.push({ id:D().uid(), label:'New group', collapsed:false, prov:'post', fields:[] }); },
      ensureLooseGroup(){ let g = this.activeTabObj.groups.find(x => x._loose); if (!g) { g = { id:D().uid(), label:'Fields', _loose:true, collapsed:false, prov:'post', fields:[] }; this.activeTabObj.groups.unshift(g); } return g; },
      openTray(g){ this.trayGroup = g || null; this.tray = true; },
      // blocks dnd
      bStart(i,e){ this.drag={i}; e.dataTransfer.effectAllowed='move'; },
      bOver(i,e){ e.preventDefault(); const r=e.currentTarget.getBoundingClientRect(); this.dropAt=(e.clientY-r.top)>r.height/2?i+1:i; },
      bDrop(e){ e.preventDefault(); if(!this.drag) return; let at=this.dropAt==null?this.blocks.length:this.dropAt; const it=this.blocks[this.drag.i]; this.blocks.splice(this.drag.i,1); if(this.drag.i<at)at-=1; this.blocks.splice(at,0,it); this.drag=null; this.dropAt=null; },
      addBlock(id){ this.blocks.push({ uid:D().uid(), comp:id, collapsed:false, summary:'New block' }); this.picker=false; },
      dupBlock(i){ const c=JSON.parse(JSON.stringify(this.blocks[i])); c.uid=D().uid(); this.blocks.splice(i+1,0,c); },
      delBlock(i){ this.blocks.splice(i,1); },
      addModular(f){ (this.trayGroup || this.ensureLooseGroup()).fields.push({ ...f, id:D().uid(), name:f.label.toLowerCase().replace(/[^a-z0-9]+/g,'_'), prov:'post', locked:false, mode:'modular', w:'full', collapsed:false }); this.tray=false; this.trayGroup=null; },
      addNewField(t){ const m=this.tmeta(t.type||t); const type=t.type||t; const target=this.trayGroup || this.ensureLooseGroup(); const n=(m.label||type).toLowerCase().replace(/[^a-z0-9]+/g,'_'); target.fields.push({ id:D().uid(), label:m.label, name:n, key:n, type, required:false, instructions:'', prov:'post', locked:false, mode:'modular', w:'full', collapsed:false }); this.tray=false; this.trayGroup=null; },
      newFieldCats(){ return D().CAT_ORDER.filter(c=>['basic','content','choice','rel','media','date'].includes(c.cat)).map(c=>({ ...c, items: D().FIELD_TYPES.filter(t=>t.cat===c.cat) })); },
      removeField(g,i){ g.fields.splice(i,1); },
      toggleTerm(taxId, termId) {
        const a = this.terms[taxId] || (this.terms[taxId] = []);
        const i = a.indexOf(termId);
        if (i >= 0) a.splice(i, 1); else a.push(termId);
      },
    },
    template: `
    <div class="ed">
      <!-- editor top -->
      <div class="ed-top">
        <button class="iconbtn" @click="go('content',{type:post.type})"><Icon name="chevLeft"/></button>
        <div class="ed-top-ctx"><span class="dash-row-ico" style="width:26px;height:26px"><Icon :name="(D().POST_TYPES.find(p=>p.id===post.type)||{}).icon||'file'" :size="14"/></span><span class="t-meta">Editing {{ (D().POST_TYPES.find(p=>p.id===post.type)||{}).name || post.type }}</span></div>
        <div class="ed-top-actions">
          <span class="badge badge--ok"><span class="badge--dot" style="margin-right:2px"></span>{{ post.status }}</span>
          <button class="btn btn--ghost btn--sm" @click="jsonOpen=true" title="View API output"><Icon name="code" :size="15"/> <span class="hide-sm">JSON</span></button>
          <button class="btn btn--ghost btn--sm"><Icon name="eye" :size="15"/> Preview</button>
          <button class="btn" :disabled="saving" @click="save"><Icon name="save" :size="15"/> {{ saving ? 'Saving…' : 'Save' }}</button>
        </div>
      </div>

      <div class="ed-body">
        <!-- main column -->
        <div class="ed-main">
          <!-- title + editable permalink (top of content) -->
          <div class="ed-titlebar">
            <input class="ed-bigtitle" v-model="post.title" placeholder="Add title">
            <div class="ed-permalink">
              <span class="t-meta">Permalink</span>
              <span class="ed-permalink-url"><span class="muted">https://atypisch.nl/{{ post.type }}/</span><template v-if="!editingSlug"><span class="ed-slug-val">{{ post.slug }}</span></template><input v-else class="ed-slug-input mono" v-model="post.slug" @keydown.enter="editingSlug=false" @blur="editingSlug=false"></span>
              <button class="ed-permalink-btn" @click="editingSlug=!editingSlug">{{ editingSlug ? 'Done' : 'Edit' }}</button>
              <a class="ed-permalink-btn" href="#" @click.prevent><Icon name="external" :size="12"/> View</a>
            </div>
          </div>

          <!-- tabs (HOW layer) -->
          <div class="ed-tabs tabs">
            <button v-for="t in tabs" :key="t.id" :class="{'is-on':activeTab===t.id}" @click="activeTab=t.id">
              <Icon :name="t.icon" :size="15"/> {{ t.label }}
            </button>
            <div class="grow"></div>
            <button class="ed-collapse-all" @click="toggleCollapseAll"><Icon :name="allCollapsed?'chevDown':'chevUp'" :size="14"/> {{ allCollapsed ? 'Expand all' : 'Collapse all' }}</button>
          </div>

          <div class="ed-fields">
            <div class="ed-prov-banner" v-if="activeTabObj.shared">
              <Icon name="git" :size="14"/> These fields come from the shared <strong>{{ activeTabObj.label }}</strong> field group — edited once, used across types.
            </div>

            <!-- field groups -->
            <div v-for="g in activeTabObj.groups" :key="g.id" class="fgroup" :class="{'is-collapsed': g.collapsed, 'fgroup--loose': g._loose}">
              <div class="fgroup-head" @click="g.collapsed=!g.collapsed" v-if="!g._loose">
                <Icon name="drag" :size="15" class="drag-handle" @click.stop/>
                <Icon :name="g.collapsed?'chevRight':'chevDown'" :size="16" class="fgroup-caret"/>
                <input class="fgroup-title" v-model="g.label" @click.stop @keydown.enter.prevent="$event.target.blur()" title="Rename this group">
                <span class="prov" :class="provClass(g.prov)"><Icon :name="provIcon(g.prov)" :size="11"/> {{ provLabel(g.prov) }}</span>
                <span class="t-meta">{{ g.fields.length }} fields</span>
                <div class="grow"></div>
                <div class="fgroup-actions" @click.stop>
                  <button class="iconbtn iconbtn--sm" @click="g.collapsed=!g.collapsed" :title="g.collapsed?'Expand':'Collapse'"><Icon :name="g.collapsed?'chevDown':'chevUp'" :size="15"/></button>
                  <UiMenu :items="[{label:'Rename',icon:'pencil'},{label:'Duplicate group',icon:'copy'},{sep:true},{label:'Detach to shared group',icon:'git'}]"><button class="iconbtn iconbtn--sm"><Icon name="drag" :size="15"/></button></UiMenu>
                </div>
              </div>
              <div class="fgroup-body" v-show="!g.collapsed">
                <div class="fgrid">
                  <div v-for="(f,fi) in g.fields" :key="f.id" class="fcell" :class="'w-'+(f.w||'full')">
                    <div class="ed-field" :class="{'is-collapsed': f.collapsed, 'is-modular': f.mode==='modular'}">
                      <div class="ed-field-head" @click="f.collapsed=!f.collapsed">
                        <Icon v-if="f.mode==='modular'" name="drag" :size="14" class="drag-handle" @click.stop/>
                        <Icon v-else name="lock" :size="12" class="ed-lock" title="Fixed — locked by the post type"/>
                        <button class="ed-field-caret" @click.stop="f.collapsed=!f.collapsed"><Icon :name="f.collapsed?'chevRight':'chevDown'" :size="14"/></button>
                        <span class="ed-field-label">{{ f.label }}<span class="field-req" v-if="f.required">*</span></span>
                        <span v-if="f.collapsed" class="ed-field-preview">{{ preview(f) }}</span>
                        <div class="grow"></div>
                        <span class="modepill" :class="f.mode==='modular'?'modepill--modular':'modepill--fixed'" style="cursor:default"><Icon :name="f.mode==='modular'?'layers':'lock'" :size="10"/> {{ f.mode==='modular'?'Modular':'Fixed' }}</span>
                        <button v-if="f.mode==='modular'" class="iconbtn iconbtn--sm is-danger" @click.stop="removeField(g,fi)" title="Remove from this item"><Icon name="x" :size="13"/></button>
                      </div>
                      <div class="ed-field-control" v-show="!f.collapsed">
                        <FieldControl :field="f"/>
                        <p class="field-hint" v-if="f.instructions">{{ f.instructions }}</p>
                      </div>
                    </div>
                  </div>
                </div>
                <button class="group-add-field" @click="openTray(g)"><Icon name="plus" :size="14"/> {{ g._loose ? 'Add field' : 'Add field to '+g.label }}</button>
              </div>
            </div>

            <!-- BLOCKS builder (content tab) -->
            <div v-if="activeTabObj.hasBlocks" class="ed-blocks-field" :class="{'is-collapsed': builderCollapsed}">
              <div class="ed-field-head" @click="builderCollapsed=!builderCollapsed" style="cursor:pointer">
                <Icon :name="builderCollapsed?'chevRight':'chevDown'" :size="16" class="fgroup-caret"/>
                <div class="ed-field-label"><Icon name="layers" :size="15" style="color:var(--accent)"/> Page builder<span class="modepill modepill--modular" style="margin-left:8px"><Icon name="layers" :size="11"/> Modular</span></div>
                <div class="grow"></div>
                <span class="t-meta">{{ blocks.length }} blocks</span>
                <button class="iconbtn iconbtn--sm" @click.stop="builderCollapsed=!builderCollapsed"><Icon :name="builderCollapsed?'chevDown':'chevUp'" :size="15"/></button>
              </div>
              <div v-show="!builderCollapsed">
                <p class="field-hint" style="margin:0 0 12px">Drop &amp; reorder components to build the page.</p>
                <div class="blocks" @dragover.prevent @drop="bDrop">
                  <div v-for="(b,bi) in blocks" :key="b.uid">
                    <div class="drop-line" v-if="dropAt===bi"></div>
                    <BlockCard :block="b" :comp="comp(b.comp)" :index="bi"
                      @toggle="b.collapsed=!b.collapsed" @visual="visualBlock=b" @dup="dupBlock(bi)" @del="delBlock(bi)"
                      @dragstart="bStart(bi,$event)" @dragend="drag=null;dropAt=null" @over="bOver(bi,$event)" @drop="bDrop"/>
                  </div>
                  <div class="drop-line" v-if="dropAt===blocks.length"></div>
                  <button class="blocks-add" @click="picker=true"><Icon name="plus" :size="16"/> Add block</button>
                </div>
              </div>
            </div>

            <!-- add field / group to this item -->
            <div class="ed-addbar">
              <button class="ed-addbar-btn ed-addbar-btn--primary" @click="openTray(null)">
                <span class="ed-addbar-ico"><Icon name="plus" :size="18"/></span>
                <span class="ed-addbar-main"><strong>Add a field</strong><span class="t-meta">A single field — text, image, relation…</span></span>
                <span class="ed-addbar-count">{{ availableToAdd.length + 18 }} types</span>
              </button>
              <button class="ed-addbar-btn" @click="addGroup">
                <span class="ed-addbar-ico"><Icon name="git" :size="18"/></span>
                <span class="ed-addbar-main"><strong>Add a field group</strong><span class="t-meta">A titled, collapsible set of fields.</span></span>
              </button>
            </div>
          </div>
        </div>

        <!-- right rail -->
        <aside class="ed-rail" v-show="railOpen">
          <div class="rail-card">
            <div class="rail-head">Publish</div>
            <div class="rail-body">
              <div class="field"><label class="field-label">Status</label><select class="sel"><option>Published</option><option>Draft</option><option>Scheduled</option></select></div>
              <div class="field"><label class="field-label">Publish date</label><input class="inp mono" value="2026-05-21 10:00"></div>
              <div class="field"><label class="field-label">Visibility</label><select class="sel"><option>Public</option><option>Private</option><option>Password</option></select></div>
            </div>
          </div>
          <div class="rail-card">
            <div class="rail-head">Organise</div>
            <div class="rail-body">
              <div class="field"><label class="field-label">Template</label><select class="sel"><option>Default</option><option>Full width</option></select></div>
              <div class="field"><label class="field-label">Author</label><div class="flex items-center gap-2"><span class="avatar" style="width:24px;height:24px;font-size:10px">MT</span><span>Marten Timan</span></div></div>
              <div class="field" style="margin:0"><label class="field-label">Language</label><select class="sel"><option>English</option><option>Nederlands</option></select></div>
            </div>
          </div>
          <div class="rail-card" v-for="tax in boundTaxonomies" :key="tax.id">
            <div class="rail-head"><Icon name="pin" :size="14"/> {{ tax.name }}</div>
            <div class="rail-body">
              <template v-if="tax.hierarchical">
                <label class="tax-term" v-for="term in tax.terms" :key="term.id" :class="{'tax-term--child': term.parent}">
                  <UiChk :modelValue="(terms[tax.id]||[]).includes(term.id)" @update:modelValue="toggleTerm(tax.id, term.id)"/>
                  <span>{{ term.name }}</span>
                </label>
                <button class="btn btn--text btn--sm" style="margin-top:6px"><Icon name="plus" :size="13"/> Add {{ tax.name.toLowerCase() }}</button>
              </template>
              <template v-else>
                <div class="flex gap-2 wrap" style="margin-bottom:8px">
                  <button v-for="term in tax.terms" :key="term.id" class="tag-chip" :class="{'is-on':(terms[tax.id]||[]).includes(term.id)}" @click="toggleTerm(tax.id, term.id)">{{ term.name }}</button>
                </div>
                <input class="inp" placeholder="Add a tag…">
              </template>
            </div>
          </div>

          <div class="rail-card rail-card--quiet">
            <div class="rail-head"><Icon name="git" :size="14"/> Field sources</div>
            <div class="rail-body" style="font-size:12.5px;color:var(--ink-2);line-height:1.6">
              <div class="flex items-center gap-2" style="margin-bottom:6px"><span class="prov prov--type"><Icon name="lock" :size="11"/> Post type</span> fixed, always shown</div>
              <div class="flex items-center gap-2" style="margin-bottom:6px"><span class="prov prov--post"><Icon name="plus" :size="11"/> Added here</span> modular, this item only</div>
              <div class="flex items-center gap-2"><span class="prov prov--comp"><Icon name="git" :size="11"/> Shared group</span> reused across types</div>
            </div>
          </div>
        </aside>
      </div>

      <!-- component picker -->
      <ComponentPicker v-if="picker" @close="picker=false" @pick="addBlock"/>

      <!-- visual block editor (components only) -->
      <VisualBlockEditor v-if="visualBlock" :block="visualBlock" :comp="comp(visualBlock.comp)" @close="visualBlock=null"/>

      <!-- API / JSON output for this post -->
      <UiCodeModal v-if="jsonOpen" :tabs="jsonTabs" :title="post.title + ' · API output'" :subtitle="'GET /api/v1/' + post.type + '/' + (post.slug||'')" :endpoint="'/api/v1/' + post.type + '/' + (post.slug||'')" @close="jsonOpen=false"/>

      <!-- add-field tray -->
      <UiDrawer v-if="tray" @close="tray=false">
        <div class="drawer-head"><Icon name="plus" :size="18"/><strong style="font-size:15px;flex:1">{{ trayGroup ? 'Add field to '+trayGroup.label : 'Add a field' }}</strong><button class="iconbtn" @click="tray=false"><Icon name="x"/></button></div>
        <div class="drawer-body">
          <p class="field-hint" style="margin-top:0">Adding a field affects <em>this item only</em> — it shows as “Added here”. To add it to every item of this type, use the field builder.</p>

          <div class="tray-sec" v-if="availableToAdd.length">
            <div class="tray-sec-h">Offered by the {{ post.type }} type</div>
            <div class="tray-item" v-for="(f,i) in availableToAdd" :key="i" @click="addModular(f)">
              <span class="lib-ico" :style="{color: tcolor(f.type)}"><Icon :name="tmeta(f.type).icon" :size="15"/></span>
              <span style="flex:1"><strong>{{ f.label }}</strong></span>
              <span class="typetag">{{ f.type }}</span>
              <Icon name="plus" :size="16" class="muted"/>
            </div>
          </div>

          <div class="tray-sec">
            <div class="tray-sec-h">New field</div>
            <div class="fb-lib-search" style="margin-bottom:10px"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="traySearch" placeholder="Search field types…" style="color:var(--ink)"></div>
            <div v-for="c in trayCats" :key="c.cat" class="tray-typecat">
              <div class="tray-typecat-h"><span class="fb-cat-dot" :style="{background: tcolor(c.items[0].type)}"></span>{{ c.label }}</div>
              <div class="tray-typegrid">
                <button v-for="t in c.items" :key="t.type" class="tray-type" @click="addNewField(t)">
                  <span class="lib-ico" :style="{color: tcolor(t.type)}"><Icon :name="t.icon" :size="15"/></span>
                  <span>{{ t.label }}</span>
                </button>
              </div>
            </div>
          </div>

          <div class="hairline" style="margin:16px 0"></div>
          <button class="btn btn--ghost" style="width:100%" @click="tray=false;picker=true"><Icon name="layers" :size="15"/> Add a component instead</button>
        </div>
      </UiDrawer>
    </div>`,
  };
})();
;
/* file: screen-content-editor.js */
/* ============================================================
   CONTENT EDITOR — faithful to the Atelier prototype (#editor):
   fields live in collapsible field-group CARDS with provenance,
   tabs (HOW layer), draggable fields, and per-post "Add a field" /
   "Add a field group" (truly modular). Inputs render through the
   emitting field registry (real values), and Save persists
   posts.data + the per-post additions (posts.layout).
   ============================================================ */
(function () {
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const C = M.comps;
  const D = () => M.data;
  const F = () => M.fields;
  const { reactive } = Vue;

  const provLabel = (p) => p === 'post' ? 'Added here' : (p === 'shared' || p === 'comp') ? 'Shared group' : 'From post type';
  const provIcon = (p) => p === 'post' ? 'plus' : (p === 'shared' || p === 'comp') ? 'git' : 'lock';
  const provClass = (p) => p === 'post' ? 'prov--post' : (p === 'shared' || p === 'comp') ? 'prov--comp' : 'prov--type';
  const slugifyName = (s) => (s || 'field').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');

  C.ScreenContentEditor = {
    name: 'ScreenContentEditor',
    inject: ['go', 'route', 'ui'],
    data() {
      return {
        post: { id: null, title: '', slug: '', status: 'draft', order: 0, published_at: '', type: '', url: '' },
        savedStatus: 'draft',
        schema: [],            // post-type fields (prov type) + shared-group fields (prov shared, _group)
        fieldGroups: [],       // matched shared groups [{id,name,icon}]
        perPostGroups: [],     // per-post field-group containers (prov post), drag fields between them
        values: reactive({}),
        blocksMap: {},
        loading: true, saving: false, savedMsg: '', _flashTimer: null,
        editingSlug: false, jsonOpen: false,
        activeTab: 0,
        allCollapsed: false,
        collapsed: reactive({}),
        tray: false, trayGroup: null, traySearch: '',
        drag: null, dropAt: null, dropGid: null,   // field drag between per-post containers
        groupDrag: null, groupDropAt: null,        // whole-container drag (reorder)
      };
    },
    created() { this.load(); const f = (this.route.params || {}).flash; if (f) this.flash(f); },
    beforeUnmount() { if (this._flashTimer) clearTimeout(this._flashTimer); },
    computed: {
      ptObj() { return D().POST_TYPES.find(p => p.id === this.post.type) || {}; },
      statusClass() { return this.post.status === 'published' ? 'badge--ok' : this.post.status === 'scheduled' ? 'badge--info' : 'badge--warn'; },
      jsonCode() { return JSON.stringify({ title: this.post.title, slug: this.post.slug, status: this.post.status, published_at: this.post.published_at, data: this.values }, null, 2); },
      isPublishDateFuture() {
        const d = this.parsePublishAt();
        return !!(d && d.getTime() > Date.now() + 15000);
      },
      primaryAction() {
        if (this.savedStatus === 'published') return 'save';
        if (this.isPublishDateFuture) return 'schedule';
        return 'publish';
      },
      primaryActionLabel() {
        if (this.saving) return 'Saving…';
        const a = this.primaryAction;
        if (a === 'save') return 'Save';
        if (a === 'schedule') return 'Schedule';
        return 'Publish';
      },
      primaryActionIcon() {
        const a = this.primaryAction;
        if (a === 'save') return 'save';
        if (a === 'schedule') return 'clock';
        return 'globe';
      },
      showSaveDraft() { return this.savedStatus !== 'published'; },
      publishAtLocal: {
        get() {
          const d = this.parsePublishAt();
          if (!d) return '';
          const pad = (n) => String(n).padStart(2, '0');
          return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        },
        set(v) {
          if (!v) { this.post.published_at = ''; return; }
          const s = String(v);
          this.post.published_at = s.replace('T', ' ') + (s.length <= 16 ? ':00' : '');
        },
      },
      publishHint() {
        if (this.savedStatus === 'published') return 'Live. Changing the date updates the publication time; the post stays published.';
        if (this.isPublishDateFuture) return 'This date is in the future, so the primary action is Schedule.';
        return 'Leave empty to publish immediately. A future date turns Publish into Schedule.';
      },
      leftoverTypeNames() {
        return new Set(['tab_visuals', 'tab_header', 'image', 'gallery', 'featured_image', 'choose_header', 'header_image', 'header_gallery', 'header_movie']);
      },
      typeFields() { return this.schema.filter(f => f.prov !== 'shared'); },
      typeFieldsForCard() {
        const leftover = this.leftoverTypeNames;
        if (!this.fieldGroups.length) return this.typeFields;
        return this.typeFields.filter(f => !leftover.has(String(f.name || f.key || '')));
      },
      // tabs come from the post-type schema's tab nodes (fallback: single Content tab)
      tabs() {
        if (this.hideTypeCard) return [{ label: 'Content', icon: 'text', items: [] }];
        const source = this.typeFieldsForCard;
        const tabs = []; let cur = { label: 'Content', icon: 'text', items: [] };
        let hasTab = false;
        source.forEach(f => {
          if (F().canonical(f.type) === 'tab') { hasTab = true; if (cur.items.length || tabs.length) tabs.push(cur); cur = { label: f.label, icon: f.icon || 'text', items: [] }; }
          else cur.items.push(f);
        });
        tabs.push(cur);
        return hasTab ? tabs.filter(t => t.items.length) : [{ label: 'Content', icon: 'text', items: source.filter(f => F().canonical(f.type) !== 'tab') }];
      },
      typeFieldsUseful() {
        return this.typeFieldsForCard.filter(f => F().canonical(f.type) !== 'tab');
      },
      hideTypeCard() { return this.fieldGroups.length > 0 && this.typeFieldsUseful.length === 0; },
      activeTabObj() { return this.tabs[this.activeTab] || this.tabs[0] || { items: [] }; },
      groups() {
        const out = [];
        if (!this.hideTypeCard) {
          out.push({ id: 'type', label: this.activeTabObj.label, prov: 'type', loose: false, schemaForm: false, fields: (this.activeTabObj.items || []) });
        }
        this.fieldGroups.forEach(fg => {
          out.push({ id: 'fg' + fg.id, label: fg.name, icon: fg.icon, prov: 'shared', loose: false, schemaForm: true, fields: this.schema.filter(f => String(f._group) === String(fg.id)) });
        });
        this.perPostGroups.forEach(g => { out.push({ id: 'pp:' + g.id, ppId: g.id, label: g.label, prov: 'post', loose: true, schemaForm: false, fields: g.fields }); });
        return out;
      },
      newFieldCats() {
        return (D().CAT_ORDER || []).filter(c => ['basic', 'content', 'choice', 'rel', 'media', 'date'].includes(c.cat))
          .map(c => ({ ...c, items: (D().FIELD_TYPES || []).filter(t => t.cat === c.cat && (!this.traySearch.trim() || t.label.toLowerCase().includes(this.traySearch.trim().toLowerCase()))) }))
          .filter(c => c.items.length);
      },
    },
    methods: {
      provLabel, provIcon, provClass,
      tmeta(t) { return (D().TYPE_MAP && D().TYPE_MAP[t]) || { icon: 'dot', cat: 'util', label: t }; },
      tcolor(t) { return (D().CAT_VAR && D().CAT_VAR[this.tmeta(t).cat]) || 'var(--ink-3)'; },
      fieldComp(f) { return F().component(f.type); },
      isTab(f) { return F().canonical(f.type) === 'tab'; },
      isFieldContainer(f) { const t = F().canonical(f.type); return F().isContainer(f.type) && t !== 'tab' && t !== 'accordion' && t !== 'columns'; },
      isContainer(f) { return this.isFieldContainer(f); },
      ensure(f) { if (f.name && this.values[f.name] === undefined) this.values[f.name] = F().defaultValue(f); return this.values[f.name]; },
      gKey(g, i) { return g.id + ':' + i; },
      fCollapsed(g, i) { return !!this.collapsed[this.gKey(g, i)]; },
      toggleField(g, i) { const k = this.gKey(g, i); this.collapsed[k] = !this.collapsed[k]; },
      gCollapsed(g) { return !!this.collapsed['grp:' + g.id]; },
      toggleGroup(g) { const k = 'grp:' + g.id; this.collapsed[k] = !this.collapsed[k]; },
      toggleCollapseAll() { const v = !this.allCollapsed; this.allCollapsed = v; this.groups.forEach(g => { this.collapsed['grp:' + g.id] = v; }); },
      // preview text for a collapsed field
      preview(f) { const v = this.values[f.name]; if (['image', 'gallery', 'file'].includes(f.type)) return v ? 'set' : '—'; if (f.type === 'true_false') return v ? 'on' : 'off'; if (v == null || v === '') return '—'; return typeof v === 'object' ? '…' : String(v).slice(0, 40); },

      buildBlocksMap() { const map = {}; (D().COMPONENTS || []).forEach(c => { const e = { name: c.name, icon: c.icon || 'layers', fields: c.fields || [] }; map[c.id] = e; if (c.slug) map[c.slug] = e; }); return map; },
      ptDbId() { const pt = D().POST_TYPES.find(p => p.id === this.post.type); return pt && pt._dbId; },

      applyResolved(rf, isNew) {
        if (!rf) return;
        const ptSlug = (rf.post && rf.post.post_type && rf.post.post_type.slug) || this.post.type || '';
        if (isNew) {
          this.post = { id: null, title: '', slug: '', status: 'draft', order: 0, published_at: '', type: ptSlug, url: '' };
          this.savedStatus = 'draft';
          this.values = reactive({});
          this.perPostGroups = [];
        } else if (rf.post) {
          this.post = { id: rf.post.id, title: rf.post.title || '', slug: rf.post.slug || '', status: rf.post.status || 'draft', order: Number(rf.post.order) || 0, published_at: rf.post.published_at || '', type: ptSlug, url: rf.post.url || '' };
          this.savedStatus = this.post.status;
          this.values = reactive(rf.values && typeof rf.values === 'object' ? rf.values : {});
          const lay = rf.layout && typeof rf.layout === 'object' ? rf.layout : {};
          if (Array.isArray(lay._perPostGroups)) {
            this.perPostGroups = lay._perPostGroups.map(g => ({ id: g.id || D().uid(), label: g.label || 'Group', fields: (g.fields || []).map(f => Object.assign({ prov: 'post' }, f)) }));
          } else if (Array.isArray(lay._perPost) && lay._perPost.length) {
            this.perPostGroups = [{ id: D().uid(), label: 'Added to this item', fields: lay._perPost.map(f => Object.assign({ prov: 'post' }, f)) }];
          } else { this.perPostGroups = []; }
        }
        this.schema = ((rf.schema && rf.schema.fields) || []).map(f => Object.assign({ prov: 'type' }, f));
        this.fieldGroups = rf.field_groups || [];
      },
      loadLocalSchema(type) {
        if (type) this.post.type = type;
        const pt = D().POST_TYPES.find(p => p.id === this.post.type);
        const sc = pt && pt._schema;
        this.schema = ((sc && sc.fields) || (Array.isArray(sc) ? sc : [])).map(f => Object.assign({ prov: 'type' }, f));
        this.fieldGroups = [];
        this.values = reactive({});
        this.perPostGroups = [];
      },
      load() {
        const rp = this.route.params || {};
        const api = M.api;
        this.blocksMap = this.buildBlocksMap();
        if (rp.postId && rp.postId !== 'new' && api) {
          this.loading = true;
          api.get('/posts/' + rp.postId + '/resolved-fieldset').then(rf => {
            this.loading = false;
            this.applyResolved(rf, false);
          }).catch(e => { this.loading = false; console.warn('[editor] resolved-fieldset failed', e && e.status); });
          return;
        }
        const type = rp.type || this.post.type;
        if (type) this.post.type = type;
        if (api && type) {
          this.loading = true;
          api.get('/resolved-fieldset?post_type=' + encodeURIComponent(type))
            .then(rf => { this.loading = false; this.applyResolved(rf, true); })
            .catch(e => { this.loading = false; console.warn('[editor] resolved-fieldset preview failed', e && e.status); this.loadLocalSchema(type); });
          return;
        }
        this.loading = false;
        this.loadLocalSchema(type);
      },

      // ---- per-post containers (modular field groups on this item) ----
      openTray(g) { this.trayGroup = g || null; this.tray = true; this.traySearch = ''; },
      addPerPostGroup() { const g = { id: D().uid(), label: 'New group', fields: [] }; this.perPostGroups.push(g); return g; },
      ensureDefaultPPG() { return this.perPostGroups.length ? this.perPostGroups[this.perPostGroups.length - 1] : this.addPerPostGroup(); },
      addFieldQuick() { const g = this.ensureDefaultPPG(); this.openTray({ ppId: g.id, loose: true }); },
      ppById(ppId) { return this.perPostGroups.find(g => g.id === ppId); },
      renameG(g, val) { const grp = this.ppById(g.ppId); if (grp) grp.label = val; },
      removePPGroup(g) { const i = this.perPostGroups.findIndex(x => x.id === g.ppId); if (i >= 0) { this.perPostGroups[i].fields.forEach(f => { if (f.name) delete this.values[f.name]; }); this.perPostGroups.splice(i, 1); } },
      uniqueName(base) { const all = [].concat(this.schema, ...this.perPostGroups.map(g => g.fields)); const taken = new Set(all.map(f => f.name)); let n = base; if (taken.has(n)) { let i = 2; while (taken.has(n + '_' + i)) i++; n = n + '_' + i; } return n; },
      addNewField(t) {
        const type = t.type || t; const m = this.tmeta(type);
        const n = this.uniqueName(slugifyName(m.label || type));
        const fld = { id: D().uid(), label: m.label || type, name: n, key: n, type, required: false, instructions: '', prov: 'post', w: 'full' };
        const target = (this.trayGroup && this.trayGroup.ppId && this.ppById(this.trayGroup.ppId)) || this.ensureDefaultPPG();
        target.fields.push(fld);
        this.tray = false; this.trayGroup = null;
      },
      removePerPost(g, f) { const grp = this.ppById(g.ppId); if (!grp) return; const i = grp.fields.indexOf(f); if (i >= 0) grp.fields.splice(i, 1); if (f.name) delete this.values[f.name]; },

      // ---- field drag: move between per-post containers + reorder ----
      dStart(g, i, e) { if (!g.loose) return; this.drag = { ppId: g.ppId, index: i }; if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', String(i)); } catch (_) {} } },
      dOver(g, i, e) { if (!g.loose || !this.drag) return; e.preventDefault(); const r = e.currentTarget.getBoundingClientRect(); this.dropAt = (e.clientY - r.top) > r.height / 2 ? i + 1 : i; this.dropGid = g.ppId; },
      dOverGroup(g, e) { if (!g.loose || !this.drag) return; e.preventDefault(); if (this.dropGid !== g.ppId) { this.dropGid = g.ppId; this.dropAt = (this.ppById(g.ppId) || { fields: [] }).fields.length; } },
      dDropGroup(g, e) {
        if (e) e.preventDefault();
        if (!g.loose || !this.drag) { this.dEnd(); return; }
        const src = this.ppById(this.drag.ppId); const dst = this.ppById(g.ppId);
        if (!src || !dst) { this.dEnd(); return; }
        let at = this.dropAt == null ? dst.fields.length : this.dropAt;
        const it = src.fields[this.drag.index];
        src.fields.splice(this.drag.index, 1);
        if (src === dst && this.drag.index < at) at -= 1;
        dst.fields.splice(at, 0, it);
        this.dEnd();
      },
      dEnd() { this.drag = null; this.dropAt = null; this.dropGid = null; },

      // ---- whole-container drag (reorder per-post groups) ----
      gIndex(g) { return this.perPostGroups.findIndex(x => x.id === g.ppId); },
      gStart(g, e) { this.groupDrag = this.gIndex(g); if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', 'group'); } catch (_) {} } },
      gOver(g, e) { if (this.groupDrag == null || !g.loose) return; e.preventDefault(); const idx = this.gIndex(g); const r = e.currentTarget.getBoundingClientRect(); this.groupDropAt = (e.clientY - r.top) > r.height / 2 ? idx + 1 : idx; },
      gDrop() { if (this.groupDrag == null) { this.gEnd(); return; } let at = this.groupDropAt == null ? this.perPostGroups.length : this.groupDropAt; const it = this.perPostGroups[this.groupDrag]; this.perPostGroups.splice(this.groupDrag, 1); if (this.groupDrag < at) at -= 1; this.perPostGroups.splice(at, 0, it); this.gEnd(); },
      gEnd() { this.groupDrag = null; this.groupDropAt = null; },

      parsePublishAt() {
        const s = String(this.post.published_at || '').trim();
        if (!s) return null;
        const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?/);
        if (!m) return null;
        return new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +(m[6] || 0));
      },
      setPublishNow() {
        const d = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        this.post.published_at = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':00';
      },
      saveFlash(action) {
        if (action === 'publish') return 'Published';
        if (action === 'schedule') return 'Scheduled';
        if (action === 'draft') return 'Saved as draft';
        return 'Updated';
      },
      flash(msg) { this.savedMsg = msg; if (this._flashTimer) clearTimeout(this._flashTimer); this._flashTimer = setTimeout(() => { this.savedMsg = ''; }, 2600); },
      save(action) {
        const api = M.api; if (!api) return;
        if (action === 'schedule' && !this.isPublishDateFuture) {
          alert('Set a publication date in the future to schedule this post.');
          return;
        }
        if (action === 'publish') {
          this.post.status = 'published';
          if (!String(this.post.published_at || '').trim()) this.setPublishNow();
        } else if (action === 'schedule') {
          this.post.status = 'scheduled';
        } else if (action === 'draft') {
          this.post.status = 'draft';
        } else if (action === 'save' && this.savedStatus === 'published') {
          this.post.status = 'published';
        }
        this.saving = true;
        const rp = this.route.params || {};
        const layout = { _perPostGroups: this.perPostGroups.map(g => ({ id: g.id, label: g.label, fields: g.fields.map(f => ({ id: f.id, label: f.label, name: f.name, key: f.key, type: f.type, required: f.required, instructions: f.instructions, w: f.w })) })) };
        const body = { title: this.post.title, slug: this.post.slug, status: this.post.status, order: Number(this.post.order) || 0, published_at: this.post.published_at || null, data: this.values, layout };
        const isNew = !this.post.id || rp.postId === 'new';
        const req = isNew ? api.post('/posts', Object.assign({ post_type_id: this.ptDbId() }, body)) : api.put('/posts/' + this.post.id, body);
        return req.then((saved) => {
          this.saving = false;
          if (saved && saved.error) { alert(saved.error); return saved; }
          if (saved && saved.id) {
            this.post.id = saved.id;
            if (saved.slug) this.post.slug = saved.slug;
            if (saved.status) this.post.status = saved.status;
            if (saved.published_at !== undefined) this.post.published_at = saved.published_at || '';
            if (saved.url) this.post.url = saved.url;
            this.savedStatus = this.post.status;
          }
          if (isNew && saved && saved.id) this.go('editor', { postId: saved.id, type: this.post.type, flash: this.saveFlash(action) });
          else this.flash(this.saveFlash(action));
          return saved;
        }).catch(e => { this.saving = false; console.error('[editor] save failed', e); alert('Saving the post failed. Check the console.'); });
      },
      publicUrl() {
        return this.post.url || ((window.__MDLR && window.__MDLR.baseUrl || '') + '/' + this.post.type + '/' + this.post.slug);
      },
      withPreview(u) { return u + (u.indexOf('?') === -1 ? '?' : '&') + 'preview=1'; },
      // Open the post's public page in a new tab. Anything that isn't publicly live
      // opens in preview mode, so an admin can always look at a post whatever its
      // status instead of hitting the 404 a draft or scheduled item would give.
      viewPost() {
        const u = this.publicUrl();
        window.open(this.post.status === 'published' ? u : this.withPreview(u), '_blank', 'noopener');
      },
      // Preview the *unsaved* editor state. This deliberately does not save: asking
      // to look at a draft should never publish it. The values are stashed in the
      // session by the API and overlaid on the post by the front controller.
      previewChanges() {
        const api = M.api;
        const isNew = !this.post.id || (this.route.params || {}).postId === 'new';
        if (!api || isNew) {
          const r = this.save();
          if (r && typeof r.then === 'function') r.then((saved) => { if (saved && saved.id) this.previewChanges(); });
          return;
        }
        // Opened up front, on the click itself, so the popup blocker lets it through;
        // the URL is filled in once the draft has been stashed.
        const tab = window.open('about:blank', '_blank');
        api.post('/posts/' + this.post.id + '/preview', {
          title: this.post.title, slug: this.post.slug, status: this.post.status,
          data: this.values,
        }).then((r) => {
          const u = (r && r.url) || this.withPreview(this.publicUrl());
          if (tab) { tab.opener = null; tab.location.replace(u); }
          else window.open(u, '_blank', 'noopener');
        }).catch((e) => {
          if (tab) tab.close();
          console.error('[editor] preview failed', e);
          alert('Could not build the preview. Check the console.');
        });
      },
    },
    template: `
    <div class="ed">
      <div class="ed-top">
        <button class="iconbtn" @click="go('content',{type:post.type})"><Icon name="chevLeft"/></button>
        <div class="ed-top-ctx"><span class="dash-row-ico" style="width:26px;height:26px"><Icon :name="ptObj.icon||'file'" :size="14"/></span><span class="t-meta">Editing {{ ptObj.name || post.type }}</span></div>
        <div class="ed-top-actions">
          <transition name="fade"><span v-if="savedMsg" class="badge badge--ok ed-saved"><Icon name="check" :size="12"/> {{ savedMsg }}</span></transition>
          <span class="badge" :class="statusClass"><span class="badge--dot" style="margin-right:2px"></span>{{ post.status }}</span>
          <button class="btn btn--ghost btn--sm" @click="jsonOpen=true" title="View API output"><Icon name="code" :size="15"/> <span class="hide-sm">JSON</span></button>
          <button class="btn btn--ghost btn--sm" @click="viewPost" title="Open the public page"><Icon name="external" :size="15"/> <span class="hide-sm">View</span></button>
          <button v-if="showSaveDraft" class="btn btn--ghost" :disabled="saving" @click="save('draft')"><Icon name="file" :size="15"/> <span class="hide-sm">Save as draft</span></button>
          <button class="btn" :disabled="saving" @click="save(primaryAction)"><Icon :name="primaryActionIcon" :size="15"/> {{ primaryActionLabel }}</button>
        </div>
      </div>

      <div class="ed-body">
        <div class="ed-main">
          <div class="ed-titlebar">
            <input class="ed-bigtitle" v-model="post.title" placeholder="Add title">
            <div class="ed-permalink">
              <span class="t-meta">Permalink</span>
              <span class="ed-permalink-url"><span class="muted">/{{ post.type }}/</span><template v-if="!editingSlug"><span class="ed-slug-val">{{ post.slug || '…' }}</span></template><input v-else class="ed-slug-input mono" v-model="post.slug" @keydown.enter="editingSlug=false" @blur="editingSlug=false"></span>
              <button class="ed-permalink-btn" @click="editingSlug=!editingSlug">{{ editingSlug ? 'Done' : 'Edit' }}</button>
              <button class="ed-permalink-btn" @click="previewChanges" title="Open the public page with your unsaved changes"><Icon name="eye" :size="12"/> Preview changes</button>
              <button class="ed-permalink-btn" @click="viewPost" title="Open the saved version of the public page"><Icon name="external" :size="12"/> View</button>
            </div>
          </div>

          <!-- tabs (HOW layer) -->
          <div class="ed-tabs tabs" v-if="tabs.length>1">
            <button v-for="(t,ti) in tabs" :key="ti" :class="{'is-on':activeTab===ti}" @click="activeTab=ti"><Icon :name="t.icon" :size="15"/> {{ t.label }}</button>
            <div class="grow"></div>
            <button class="ed-collapse-all" @click="toggleCollapseAll"><Icon :name="allCollapsed?'chevDown':'chevUp'" :size="14"/> {{ allCollapsed ? 'Expand all' : 'Collapse all' }}</button>
          </div>
          <div v-else style="display:flex;justify-content:flex-end;margin-bottom:10px">
            <button class="ed-collapse-all" @click="toggleCollapseAll"><Icon :name="allCollapsed?'chevDown':'chevUp'" :size="14"/> {{ allCollapsed ? 'Expand all' : 'Collapse all' }}</button>
          </div>

          <div class="ed-fields">
            <div v-if="loading" class="empty" style="padding:48px"><Icon name="box" :size="24"/><div>Loading…</div></div>

            <template v-else>
              <!-- field-group cards -->
              <template v-for="g in groups" :key="g.id">
              <div class="fgroup-dropline" v-if="g.loose && groupDrag!=null && groupDropAt===gIndex(g)"></div>
              <div class="fgroup" :class="{'is-collapsed': gCollapsed(g), 'is-droptarget': g.loose && drag && dropGid===g.ppId, 'is-gdragging': g.loose && groupDrag===gIndex(g)}" @dragover="gOver(g,$event)" @drop="gDrop">
                <div class="fgroup-head">
                  <span v-if="g.loose" class="fgroup-ghandle" draggable="true" @dragstart="gStart(g,$event)" @dragend="gEnd" title="Drag to reorder this container"><Icon name="drag" :size="15"/></span>
                  <button class="ed-field-caret" @click="toggleGroup(g)"><Icon :name="gCollapsed(g)?'chevRight':'chevDown'" :size="16"/></button>
                  <input v-if="g.loose" class="fgroup-title" :value="g.label" @input="renameG(g,$event.target.value)" @click.stop placeholder="Group name">
                  <span v-else class="fgroup-title" style="cursor:pointer" @click="toggleGroup(g)">{{ g.label }}</span>
                  <span class="prov" :class="provClass(g.prov)"><Icon :name="provIcon(g.prov)" :size="11"/> {{ provLabel(g.prov) }}</span>
                  <span class="t-meta" v-if="!g.schemaForm">{{ g.fields.length }} field{{ g.fields.length===1?'':'s' }}</span>
                  <div class="grow"></div>
                  <button v-if="g.loose" class="iconbtn iconbtn--sm is-danger" @click.stop="removePPGroup(g)" title="Remove this container"><Icon name="x" :size="14"/></button>
                </div>
                <div class="fgroup-body" v-show="!gCollapsed(g)" @dragover="dOverGroup(g,$event)" @drop="dDropGroup(g,$event)">
                  <FieldRenderer v-if="g.schemaForm" :schema="g.fields" :model="values" :blocks="blocksMap" hideProv/>
                  <div class="fgrid" v-else>
                    <template v-for="(f,fi) in g.fields" :key="f.id || f.name">
                      <div v-if="isTab(f)" class="fr-tabsec-h" style="grid-column:1/-1">{{ f.label }}</div>
                      <div v-else class="fcell" :class="'w-'+(f.w||'full')">
                        <div class="drop-line" v-if="g.loose && drag && dropGid===g.ppId && dropAt===fi" style="margin:-4px 0 6px"></div>
                        <div class="ed-field" :class="{'is-collapsed': fCollapsed(g,fi), 'is-modular': f.prov==='post', 'is-dragging': drag && drag.ppId===g.ppId && drag.index===fi}"
                             @dragover="dOver(g,fi,$event)">
                          <div class="ed-field-head" @click="toggleField(g,fi)">
                            <span v-if="g.loose" class="drag-handle" draggable="true" @dragstart.stop="dStart(g,fi,$event)" @dragend="dEnd" @click.stop title="Drag into any container"><Icon name="drag" :size="14"/></span>
                            <Icon v-else name="lock" :size="12" class="ed-lock" title="Fixed — from the post type / shared group"/>
                            <button class="ed-field-caret" @click.stop="toggleField(g,fi)"><Icon :name="fCollapsed(g,fi)?'chevRight':'chevDown'" :size="14"/></button>
                            <span class="ed-field-label">{{ f.label }}<span class="field-req" v-if="f.required">*</span></span>
                            <span v-if="fCollapsed(g,fi)" class="ed-field-preview">{{ preview(f) }}</span>
                            <div class="grow"></div>
                            <span class="modepill" :class="f.prov==='post'?'modepill--modular':'modepill--fixed'" style="cursor:default"><Icon :name="f.prov==='post'?'layers':'lock'" :size="10"/> {{ f.prov==='post'?'Modular':'Fixed' }}</span>
                            <button v-if="g.loose" class="iconbtn iconbtn--sm is-danger" @click.stop="removePerPost(g,f)" title="Remove from this item"><Icon name="x" :size="13"/></button>
                          </div>
                          <div class="ed-field-control" v-show="!fCollapsed(g,fi)">
                            <FieldRenderer v-if="isContainer(f)" :schema="[f]" :model="values" :blocks="blocksMap" nested hideProv/>
                            <component v-else :is="fieldComp(f)" :definition="f" :modelValue="ensure(f)" @update:modelValue="v=>{ values[f.name]=v }"/>
                            <p class="field-hint" v-if="f.instructions">{{ f.instructions }}</p>
                          </div>
                        </div>
                      </div>
                    </template>
                  </div>
                  <div class="drop-line" v-if="g.loose && drag && dropGid===g.ppId && dropAt===g.fields.length"></div>
                  <div v-if="g.loose && !g.fields.length" class="ppg-empty">Drop fields here, or add one below.</div>
                  <button v-if="g.loose" class="group-add-field" @click="openTray(g)"><Icon name="plus" :size="14"/> Add field to this container</button>
                </div>
              </div>
              <div class="fgroup-dropline" v-if="g.loose && groupDrag!=null && groupDropAt===perPostGroups.length && gIndex(g)===perPostGroups.length-1"></div>
              </template>

              <!-- add field / field group to THIS post (modular) -->
              <div class="ed-addbar">
                <button class="ed-addbar-btn ed-addbar-btn--primary" @click="addFieldQuick">
                  <span class="ed-addbar-ico"><Icon name="plus" :size="18"/></span>
                  <span class="ed-addbar-main"><strong>Add a field</strong><span class="t-meta">A single field on this item only — shows as “Added here”.</span></span>
                </button>
                <button class="ed-addbar-btn" @click="addPerPostGroup">
                  <span class="ed-addbar-ico"><Icon name="git" :size="18"/></span>
                  <span class="ed-addbar-main"><strong>Add a field group</strong><span class="t-meta">A new container on this item — drag fields in or between groups.</span></span>
                </button>
              </div>
            </template>
          </div>
        </div>

        <aside class="ed-rail">
          <div class="rail-card">
            <div class="rail-head">Publish</div>
            <div class="rail-body">
              <div class="field"><label class="field-label">Publication date</label>
                <div class="rail-datetime">
                  <input class="inp" type="datetime-local" v-model="publishAtLocal">
                  <button type="button" class="btn btn--ghost btn--sm" @click="setPublishNow">Now</button>
                </div>
                <div class="t-meta" style="margin-top:6px">{{ publishHint }}</div>
              </div>
              <div class="field"><label class="field-label">Slug</label><input class="inp mono" v-model="post.slug" placeholder="auto"></div>
              <div class="field"><label class="field-label">Order</label>
                <input class="inp" type="number" step="1" v-model.number="post.order" placeholder="0">
                <div class="t-meta" style="margin-top:6px">Lowest first. Dragging rows in the overview writes this same number.</div>
              </div>
              <div class="rail-actions">
                <button class="btn" :disabled="saving" @click="save(primaryAction)"><Icon :name="primaryActionIcon" :size="15"/> {{ primaryActionLabel }}</button>
                <button v-if="showSaveDraft" class="btn btn--ghost" :disabled="saving" @click="save('draft')"><Icon name="file" :size="15"/> Save as draft</button>
              </div>
            </div>
          </div>
          <div class="rail-card" v-if="fieldGroups.length">
            <div class="rail-head"><Icon name="git" :size="14"/> Field groups</div>
            <div class="rail-body" style="font-size:12.5px;color:var(--ink-2)"><div class="flex items-center gap-2" v-for="g in fieldGroups" :key="g.id" style="margin-bottom:4px"><Icon :name="g.icon||'box'" :size="13" class="muted"/> {{ g.name }}</div></div>
          </div>
          <div class="rail-card rail-card--quiet">
            <div class="rail-head"><Icon name="git" :size="14"/> Field sources</div>
            <div class="rail-body" style="font-size:12.5px;color:var(--ink-2);line-height:1.6">
              <div class="flex items-center gap-2" style="margin-bottom:6px"><span class="prov prov--type"><Icon name="lock" :size="11"/> Post type</span> fixed, always shown</div>
              <div class="flex items-center gap-2" style="margin-bottom:6px"><span class="prov prov--post"><Icon name="plus" :size="11"/> Added here</span> modular, this item only</div>
              <div class="flex items-center gap-2"><span class="prov prov--comp"><Icon name="git" :size="11"/> Shared group</span> reused across types</div>
            </div>
          </div>
        </aside>
      </div>

      <!-- add-field tray (per-post) -->
      <UiDrawer v-if="tray" @close="tray=false">
        <div class="drawer-head"><Icon name="plus" :size="18"/><strong style="font-size:15px;flex:1">Add a field</strong><button class="iconbtn" @click="tray=false"><Icon name="x"/></button></div>
        <div class="drawer-body">
          <p class="field-hint" style="margin-top:0">Adding a field affects <em>this item only</em> — it shows as “Added here”. To add it to every item of this type, use the field builder.</p>
          <div class="fb-lib-search" style="margin-bottom:10px"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="traySearch" placeholder="Search field types…" style="color:var(--ink)"></div>
          <div v-for="c in newFieldCats" :key="c.cat" class="tray-typecat">
            <div class="tray-typecat-h"><span class="fb-cat-dot" :style="{background: tcolor(c.items[0].type)}"></span>{{ c.label }}</div>
            <div class="tray-typegrid">
              <button v-for="t in c.items" :key="t.type" class="tray-type" @click="addNewField(t)">
                <span class="lib-ico" :style="{color: tcolor(t.type)}"><Icon :name="t.icon" :size="15"/></span>
                <span>{{ t.label }}</span>
              </button>
            </div>
          </div>
        </div>
      </UiDrawer>

      <UiCodeModal v-if="jsonOpen" :tabs="[{id:'json',label:'JSON',lang:'json',code:jsonCode}]" :title="(post.title||'Untitled') + ' · stored data'" :subtitle="'posts.data for /' + post.type + '/' + (post.slug||'')" @close="jsonOpen=false"/>
    </div>`,
  };
})();
;
/* file: screen-media.js */
/* ============================================================
   MEDIA — library, a real drag-resize ImageCropper, MediaPicker.
   Crops feed every configured preset (md_image_url($id,'preset')).
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
  const grad = (hue) => `linear-gradient(135deg, hsl(${hue} 30% 80%), hsl(${hue-8} 24% 58%))`;
  function mapMediaRow(r) {
    const v = (r.variants && typeof r.variants === 'object') ? r.variants : {};
    const thumb = r.thumb_url || (v.thumb && v.thumb.path) || (v.small && v.small.path) || r.url || r.file_path || '';
    return { id: r.id, name: r.filename, title: r.title || r.filename, w: Number(r.width) || null, h: Number(r.height) || null, kb: Math.round((Number(r.file_size) || 0) / 1024), alt: r.alt_text || '', caption: r.caption || '', folder: r.folder_path || '', hue: 30, uploaded: (r.created_at || '').slice(0, 10), usedIn: 0, url: r.url || r.file_path || '', thumb, preview: r.preview_url || r.url || thumb, mime: r.mime_type || '' };
  }
  async function uploadMediaFiles(fileList) {
    const files = Array.from(fileList || []);
    if (!files.length) return [];
    if (!window.__mdlr.api) { alert('Upload failed: API client is not ready.'); return []; }
    const added = [];
    for (const f of files) {
      try {
        const fd = new FormData(); fd.append('file', f);
        const r = await window.__mdlr.api.upload('/media/upload', fd);
        if (r && r.id) { const m = mapMediaRow(r); D().MEDIA.unshift(m); added.push(m); }
        else { alert('Upload failed: ' + ((r && r.error) || 'the server did not return a media id.')); }
      } catch (err) { console.error('[upload] failed', err); alert('Upload failed: ' + (err && err.data && err.data.error || err.message)); }
    }
    if (added.length && window.__mdlr.store && window.__mdlr.store.loadMedia) {
      try { await window.__mdlr.store.loadMedia(); } catch (e) {}
    }
    return added;
  }

  /* ---- real crop tool ---- */
  C.ImageCropper = {
    name: 'ImageCropper',
    props: { img: Object },
    emits: ['close','save'],
    data() {
      const saved = this.img && this.img.crop;
      return {
        crop: (saved && saved.w && saved.h)
          ? { x: Number(saved.x) || 0, y: Number(saved.y) || 0, w: Number(saved.w), h: Number(saved.h) }
          : { x: 12, y: 14, w: 62, h: 60 },
        activeName: (saved && saved.preset) || 'Free',
        activeRatio: (saved && Array.isArray(saved.ratio)) ? saved.ratio : null,   // [w,h] derived from the chosen preset, or null = free
        drag: null,
        presets: D().IMAGE_PRESETS.map(p => ({ ...p, on: p.on })),
      };
    },
    computed: { grad() { return this.img && this.img.url ? ('url("' + this.img.url + '")') : grad(this.img.hue); }, ratio() { return this.activeRatio; } },
    methods: {
      stageRect() { return this.$refs.stage.getBoundingClientRect(); },
      down(mode, e) {
        e.preventDefault(); e.stopPropagation();
        this.drag = { mode, sx: e.clientX, sy: e.clientY, r: this.stageRect(), orig: { ...this.crop } };
        window.addEventListener('pointermove', this.move);
        window.addEventListener('pointerup', this.up);
      },
      move(e) {
        if (!this.drag) return;
        const { mode, sx, sy, r, orig } = this.drag;
        const dx = (e.clientX - sx) / r.width * 100;
        const dy = (e.clientY - sy) / r.height * 100;
        let c = { ...orig };
        if (mode === 'move') {
          c.x = clamp(orig.x + dx, 0, 100 - orig.w);
          c.y = clamp(orig.y + dy, 0, 100 - orig.h);
        } else {
          if (mode.includes('e')) c.w = clamp(orig.w + dx, 8, 100 - orig.x);
          if (mode.includes('s')) c.h = clamp(orig.h + dy, 8, 100 - orig.y);
          if (mode.includes('w')) { const nx = clamp(orig.x + dx, 0, orig.x + orig.w - 8); c.w = orig.w + (orig.x - nx); c.x = nx; }
          if (mode.includes('n')) { const ny = clamp(orig.y + dy, 0, orig.y + orig.h - 8); c.h = orig.h + (orig.y - ny); c.y = ny; }
          if (this.ratio) {
            const [rw, rh] = this.ratio;
            const wpx = c.w / 100 * r.width;
            let nh = (wpx * rh / rw) / r.height * 100;
            if (mode.includes('n')) { c.y = orig.y + orig.h - nh; if (c.y < 0) { c.y = 0; } }
            c.h = nh;
            if (c.y + c.h > 100) c.h = 100 - c.y;
          }
        }
        this.crop = c;
      },
      up() { this.drag = null; window.removeEventListener('pointermove', this.move); window.removeEventListener('pointerup', this.up); },
      pick(name, wh) {
        this.activeName = name; this.activeRatio = wh;
        if (wh) { const rect = this.stageRect(); const wpx = this.crop.w / 100 * rect.width; let nh = (wpx * wh[1] / wh[0]) / rect.height * 100; if (this.crop.y + nh > 100) nh = 100 - this.crop.y; this.crop.h = nh; }
      },
      openSettings() { if (window.__mdlr.go) window.__mdlr.go('settings', { tab: 'media' }); this.$emit('close'); },
      prevStyle(p) {
        const cx = this.crop.x + this.crop.w / 2, cy = this.crop.y + this.crop.h / 2;
        return { aspectRatio: p.w + '/' + p.h, backgroundImage: this.grad, backgroundSize: 'cover', backgroundPosition: cx + '% ' + cy + '%' };
      },
      reset() { this.crop = { x: 8, y: 8, w: 84, h: 84 }; this.pick('Free', null); },
      fmt(n) { return Math.round(n); },
    },
    template: `
    <UiModal lg @close="$emit('close')">
      <div class="drawer-head">
        <Icon name="crop" :size="18"/>
        <div style="flex:1"><strong style="font-size:15px">Crop {{ img.name }}</strong><div class="t-key">{{ img.w }}×{{ img.h }} px</div></div>
        <button class="iconbtn" @click="$emit('close')"><Icon name="x"/></button>
      </div>
      <div class="drawer-body" style="padding:0">
        <div class="crop2-wrap">
          <div class="crop2-main">
            <div class="crop2-ratios">
              <span class="t-meta" style="margin-right:4px">Cropping for</span>
              <strong style="font-size:13px">{{ activeName }}</strong>
            </div>
            <div class="crop2-stage" ref="stage" :style="{backgroundImage: grad}">
              <div class="crop2-box" :style="{left:crop.x+'%', top:crop.y+'%', width:crop.w+'%', height:crop.h+'%'}" @pointerdown="down('move',$event)">
                <span class="crop2-thirds"></span>
                <span class="crop2-h crop2-h--nw" @pointerdown="down('nw',$event)"></span>
                <span class="crop2-h crop2-h--ne" @pointerdown="down('ne',$event)"></span>
                <span class="crop2-h crop2-h--sw" @pointerdown="down('sw',$event)"></span>
                <span class="crop2-h crop2-h--se" @pointerdown="down('se',$event)"></span>
              </div>
            </div>
            <div class="crop2-foot">
              <span class="t-key">x {{ fmt(crop.x/100*img.w) }} · y {{ fmt(crop.y/100*img.h) }} · {{ fmt(crop.w/100*img.w) }}×{{ fmt(crop.h/100*img.h) }}</span>
              <span class="grow"></span>
              <button class="btn btn--text btn--sm" @click="reset"><Icon name="refresh" :size="14"/> Reset</button>
            </div>
          </div>
          <div class="crop2-side">
            <div class="crop2-side-head"><strong>Image presets</strong><span class="t-meta">{{ presets.filter(p=>p.on).length }} on</span></div>
            <p class="field-hint" style="margin:0 14px 10px">Click a preset to crop for its ratio; the toggle decides which sizes are generated. Presets come from <a class="mode-help-link" style="display:inline-flex" @click.prevent="openSettings">Settings → Media</a>.</p>
            <button type="button" class="preset-row preset-row--free" :class="{'is-active':activeName==='Free'}" @click="pick('Free',null)">
              <div class="preset-prev preset-prev--free"><Icon name="crop" :size="16"/></div>
              <div class="preset-meta"><strong>Free</strong><span class="t-key">any ratio</span></div>
            </button>
            <div class="preset-row" v-for="p in presets" :key="p.name" :class="{'is-off':!p.on, 'is-active':activeName===p.name}" @click="pick(p.name,[p.w,p.h])">
              <div class="preset-prev" :style="prevStyle(p)"></div>
              <div class="preset-meta"><strong>{{ p.name }}</strong><span class="t-key">{{ p.w }}×{{ p.h }}</span></div>
              <span @click.stop><UiSwitch v-model="p.on"/></span>
            </div>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <div class="flex items-center gap-2" style="margin-right:auto;color:var(--ink-3);font-size:12.5px"><Icon name="refresh" :size="14"/> {{ presets.filter(p=>p.on).length }} formats will regenerate</div>
        <button class="btn btn--ghost" @click="$emit('close')">Cancel</button>
        <button class="btn" @click="$emit('save', Object.assign({}, crop, { preset: activeName, ratio: activeRatio })); $emit('close')"><Icon name="check" :size="15"/> Save crops</button>
      </div>
    </UiModal>`,
  };

  /* ---- media picker (for Replace) ---- */
  C.MediaPicker = {
    name: 'MediaPicker',
    props: { multiple: Boolean, accept: { type: String, default: '' } },
    emits: ['close','pick'],
    data() { return { q:'', picked: [], uploading: false }; },
    computed: { items() { const q=this.q.trim().toLowerCase(); return D().MEDIA.filter(m=>!q||(m.name||'').toLowerCase().includes(q)); } },
    methods: {
      grad,
      isPicked(m) { return this.picked.some(x => x.id === m.id); },
      choose(m) {
        if (this.multiple) { const i = this.picked.findIndex(x => x.id === m.id); if (i >= 0) this.picked.splice(i, 1); else this.picked.push(m); }
        else { this.$emit('pick', m); this.$emit('close'); }
      },
      confirm() { this.$emit('pick', this.picked.slice()); this.$emit('close'); },
      onModalClose() { if (this.uploading) return; this.$emit('close'); },
      async onFiles(e) {
        const files = Array.from((e.target && e.target.files) || (e.dataTransfer && e.dataTransfer.files) || []);
        if (!files.length) return;
        this.uploading = true;
        const added = await uploadMediaFiles(files);
        if (this.multiple) added.forEach(m => this.picked.push(m));
        this.uploading = false;
        if (e.target) e.target.value = '';
        if (!this.multiple && added.length) { this.$emit('pick', added[added.length - 1]); this.$emit('close'); }
      },
    },
    template: `
    <UiModal lg @close="onModalClose">
      <div class="drawer-head"><Icon name="image" :size="18"/><strong style="font-size:15px;flex:1">{{ multiple ? 'Choose images' : 'Choose an image' }}</strong><button class="iconbtn" @click="onModalClose"><Icon name="x"/></button></div>
      <div style="padding:14px 18px 0;display:flex;gap:10px;align-items:center">
        <div class="topbar-search" style="flex:1;max-width:none"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="q" placeholder="Search media…" style="color:var(--ink)"></div>
        <label class="btn btn--ghost btn--sm" style="cursor:pointer;flex:none;position:relative;overflow:hidden"><Icon name="upload" :size="14"/> {{ uploading ? 'Uploading…' : 'Upload' }}<input type="file" multiple :accept="accept==='image'?'image/*':'image/jpeg,image/png,image/gif,image/webp,image/*,application/pdf,audio/*'" style="position:absolute;inset:0;opacity:0;cursor:pointer;font-size:64px" @change="onFiles"></label>
      </div>
      <div class="drawer-body" style="padding-top:14px" @dragover.prevent @drop.prevent="onFiles">
        <div class="mp-droptip" v-if="!items.length && !uploading"><Icon name="upload" :size="22"/><div>Drag files here or use Upload</div></div>
        <div class="media-grid" style="padding:0">
          <button v-for="m in items" :key="m.id" class="media-card" :class="{'is-picked':isPicked(m)}" @click="choose(m)">
            <span class="media-thumb" :style="{background: (m.thumb||m.url) ? ('center/cover no-repeat url('+(m.thumb||m.url)+')') : grad(m.hue)}">
              <span v-if="multiple && isPicked(m)" class="media-check"><Icon name="check" :size="14"/></span>
            </span>
            <span class="media-name">{{ m.name }}</span>
            <span class="t-meta mono">{{ m.w }}×{{ m.h }}</span>
          </button>
        </div>
      </div>
      <div v-if="multiple" class="drawer-foot"><span class="t-meta" style="margin-right:auto">{{ picked.length }} selected</span><button class="btn btn--ghost" @click="$emit('close')">Cancel</button><button class="btn" :disabled="!picked.length" @click="confirm"><Icon name="check" :size="15"/> Add selected</button></div>
    </UiModal>`,
  };

  C.ScreenMedia = {
    name: 'ScreenMedia',
    inject: ['go', 'route'],
    data() { return { view:'grid', q:'', folder:'all', detail:null, cropping:null, newFolderOpen:false, newFolderName:'', renameOpen:false, rename:null, uploading:false, sel:[], deleting:false }; },
    computed: {
      items() { const q=this.q.trim().toLowerCase(); return D().MEDIA.filter(m => (this.folder==='all'||m.folder===this.folder) && (!q||(m.name||'').toLowerCase().includes(q)||(m.title||'').toLowerCase().includes(q))); },
      folders() { return ['all', ...D().MEDIA_FOLDERS]; },
      presetCount() { return D().IMAGE_PRESETS.filter(p=>p.on).length; },
      renamePattern() { return D().RENAME_PATTERN; },
      allOn() { return this.items.length > 0 && this.items.every(m => this.sel.includes(m.id)); },
    },
    mounted() {
      if (this.route && this.route.params && this.route.params.upload) this.$nextTick(() => this.openFilePicker());
    },
    methods: {
      D(){ return window.__mdlr.data; }, grad,
      src(m){ return (m && (m.thumb || m.preview || m.url)) || ''; },
      isImage(m){ const mime = (m && m.mime) || ''; if (mime.indexOf('image/') === 0) return true; return /\.(jpe?g|png|gif|webp|svg|avif)$/i.test((m && m.name) || ''); },
      isSel(id){ return this.sel.indexOf(id) >= 0; },
      toggle(id){ const i = this.sel.indexOf(id); if (i >= 0) this.sel.splice(i, 1); else this.sel.push(id); },
      toggleAll(){ this.sel = this.allOn ? [] : this.items.map(m => m.id); },
      open(m){ this.detail = m; },
      viewFile(m){ const u = (m && m.url) || this.src(m); if (u) window.open(u, '_blank', 'noopener'); },
      async removeOne(m) {
        if (!m || m.id == null) return;
        if (!confirm('Delete “' + (m.name || 'this file') + '”? This cannot be undone.')) return;
        await this.removeIds([m.id]);
        if (this.detail && this.detail.id === m.id) this.detail = null;
        if (this.cropping && this.cropping.id === m.id) this.cropping = null;
      },
      async bulkDelete() {
        if (!this.sel.length) return;
        const n = this.sel.length;
        if (!confirm('Delete ' + n + ' file' + (n === 1 ? '' : 's') + '? This cannot be undone.')) return;
        await this.removeIds(this.sel.slice());
      },
      async removeIds(ids) {
        const store = window.__mdlr.store;
        if (!store || !store.deleteMedia) {
          alert('Delete is not available. Reload the admin and try again.');
          return;
        }
        this.deleting = true;
        const failed = [];
        for (const id of ids) {
          try { await store.deleteMedia(id); }
          catch (e) {
            console.error('[media] delete failed', id, e);
            failed.push(id);
          }
        }
        this.sel = this.sel.filter(id => failed.indexOf(id) >= 0);
        if (this.detail && ids.indexOf(this.detail.id) >= 0 && failed.indexOf(this.detail.id) < 0) this.detail = null;
        this.deleting = false;
        if (failed.length) {
          alert('Could not delete ' + failed.length + ' file' + (failed.length === 1 ? '' : 's') + '.');
        }
      },
      openFilePicker() { const el = this.$refs.fileInput; if (el) el.click(); },
      async onFiles(e) {
        const files = Array.from((e.target && e.target.files) || (e.dataTransfer && e.dataTransfer.files) || []);
        if (!files.length) return;
        this.uploading = true;
        const added = await uploadMediaFiles(files);
        this.uploading = false;
        if (e.target) e.target.value = '';
        if (added.length) this.detail = added[0];
      },
      slug(s){ return (s||'').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,''); },
      ext(name){ const i=name.lastIndexOf('.'); return i>=0?name.slice(i):''; },
      autoName(m, n){ const p = this.renamePattern.pattern; return p.replace('{folder}', m.folder).replace('{title}', this.slug(m.title||'image')).replace('{n}', n||1) + this.ext(m.name); },
      applyRename(){
        const list = D().MEDIA.filter(m => this.folder==='all' || m.folder===this.folder);
        const counts = {};
        list.forEach(m => { const base = (this.renamePattern.pattern.replace('{folder}',m.folder).replace('{title}',this.slug(m.title||'image')).replace('{n}','')); counts[base]=(counts[base]||0)+1; const n=counts[base]; m.name=this.autoName(m,n); });
        this.renameOpen=false;
      },
      createFolder(){ const n=this.slug(this.newFolderName); if(n && !D().MEDIA_FOLDERS.includes(n)){ D().MEDIA_FOLDERS.push(n); this.folder=n; } this.newFolderOpen=false; this.newFolderName=''; },
      onCrop(crop){ const m = this.cropping; if (!m || m.id == null) return; window.__mdlr.store.saveMediaCrop(m.id, crop).catch(e => { console.error('[media] save crop failed', e); alert('Saving the crop failed. Check the console.'); }); },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Media</h1><p class="page-sub" style="margin-top:6px">{{ D().MEDIA.length }} files. Crop once — every configured preset re-crops automatically.</p></div>
        <div class="flex gap-2">
          <button class="btn btn--ghost" @click="newFolderOpen=true"><Icon name="folder"/> New folder</button>
          <label class="btn btn--accent" style="cursor:pointer;position:relative;overflow:hidden"><Icon name="upload"/> {{ uploading ? 'Uploading…' : 'Upload' }}<input ref="fileInput" type="file" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/*,application/pdf,audio/*" style="position:absolute;inset:0;opacity:0;cursor:pointer;font-size:64px" @change="onFiles"></label>
        </div>
      </div>
      <div class="card">
        <div class="list-toolbar">
          <div class="topbar-search media-search"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="q" placeholder="Search media…" style="color:var(--ink)"></div>
          <UiSelect class="media-foldersel" v-model="folder"><option v-for="f in folders" :key="f" :value="f">{{ f==='all'?'All folders':f }}</option></UiSelect>
          <button class="btn btn--ghost btn--sm" @click="renameOpen=true" title="Auto-rename files in this view"><Icon name="refresh" :size="14"/> Auto-rename</button>
          <template v-if="sel.length">
            <span class="badge badge--info">{{ sel.length }} selected</span>
            <button class="btn btn--danger btn--sm" :disabled="deleting" @click="bulkDelete"><Icon name="trash" :size="14"/> {{ deleting ? 'Deleting…' : ('Delete ' + sel.length) }}</button>
          </template>
          <div class="grow"></div>
          <div class="seg"><button :class="{'is-on':view==='grid'}" @click="view='grid'"><Icon name="grid" :size="14"/></button><button :class="{'is-on':view==='list'}" @click="view='list'"><Icon name="list" :size="14"/></button></div>
        </div>
        <div v-if="view==='grid'" class="media-grid">
          <div v-for="m in items" :key="m.id" class="media-card media-card--lib" :class="{'is-picked':isSel(m.id)}" @click="open(m)">
            <span class="media-thumb" :style="{background: src(m) ? ('center/contain no-repeat url('+src(m)+')') : grad(m.hue), backgroundColor:'var(--paper-2)'}">
              <span class="media-lib-check" @click.stop><UiChk :modelValue="isSel(m.id)" @update:modelValue="toggle(m.id)"/></span>
              <span class="media-hover">
                <button type="button" class="btn btn--soft btn--xs" @click.stop="open(m)"><Icon name="pencil" :size="13"/> Edit</button>
                <button type="button" class="btn btn--soft btn--xs" @click.stop="cropping=m"><Icon name="crop" :size="13"/> Crop</button>
                <button type="button" class="btn btn--soft btn--xs" @click.stop="removeOne(m)"><Icon name="trash" :size="13"/> Delete</button>
              </span>
              <span class="media-badge mono">{{ presetCount }}</span>
            </span>
            <span class="media-name">{{ m.name }}</span>
            <span class="t-meta mono">{{ m.w }}×{{ m.h }} · {{ (m.kb/1024).toFixed(1) }} MB</span>
          </div>
        </div>
        <table v-else class="tbl tbl--rows">
          <thead><tr>
            <th style="width:36px"><UiChk :modelValue="allOn" @update:modelValue="toggleAll"/></th>
            <th>File</th><th style="width:130px">Folder</th><th style="width:130px">Dimensions</th><th style="width:90px">Size</th>
          </tr></thead>
          <tbody>
            <tr v-for="m in items" :key="m.id" :class="{'row-sel': isSel(m.id)}">
              <td @click.stop><UiChk :modelValue="isSel(m.id)" @update:modelValue="toggle(m.id)"/></td>
              <td>
                <div class="flex items-center gap-3">
                  <span class="media-thumb-sm" @click="open(m)">
                    <img v-if="isImage(m) && src(m)" :src="src(m)" :alt="m.name || ''">
                    <Icon v-else class="media-thumb-ico" name="file" :size="22"/>
                  </span>
                  <div>
                    <a class="ce-title" @click="open(m)">{{ m.name }}</a>
                    <div class="t-meta">{{ m.title }}</div>
                    <div class="row-actions">
                      <button @click="open(m)">Edit</button>
                      <button @click="cropping=m"><Icon name="crop" :size="12"/> Crop</button>
                      <button @click="viewFile(m)"><Icon name="eye" :size="12"/> View</button>
                      <button type="button" class="is-danger" @click="removeOne(m)">Delete</button>
                    </div>
                  </div>
                </div>
              </td>
              <td class="muted">{{ m.folder || '—' }}</td>
              <td class="mono muted">{{ m.w && m.h ? (m.w + '×' + m.h) : '—' }}</td>
              <td class="mono muted">{{ m.kb ? ((m.kb/1024).toFixed(1) + ' MB') : '—' }}</td>
            </tr>
            <tr v-if="!items.length"><td colspan="5"><div class="empty" style="padding:40px"><Icon name="image"/><div>No media in this view.</div></div></td></tr>
          </tbody>
        </table>
      </div>

      <MediaDetail v-if="detail" :m="detail" @close="detail=null" @crop="cropping=detail" @delete="removeOne"/>
      <ImageCropper v-if="cropping" :img="cropping" @save="onCrop" @close="cropping=null"/>

      <!-- new folder -->
      <UiModal v-if="newFolderOpen" @close="newFolderOpen=false">
        <div class="drawer-head"><Icon name="folder" :size="18"/><strong style="font-size:15px;flex:1">New folder</strong><button class="iconbtn" @click="newFolderOpen=false"><Icon name="x"/></button></div>
        <div class="drawer-body"><div class="field" style="margin:0"><label class="field-label">Folder name</label><input class="inp" v-model="newFolderName" placeholder="e.g. case-studies" @keydown.enter="createFolder"></div></div>
        <div class="drawer-foot"><button class="btn btn--ghost" @click="newFolderOpen=false">Cancel</button><button class="btn btn--accent" @click="createFolder" :disabled="!newFolderName.trim()"><Icon name="plus" :size="15"/> Create folder</button></div>
      </UiModal>

      <!-- auto-rename -->
      <UiModal v-if="renameOpen" @close="renameOpen=false">
        <div class="drawer-head"><Icon name="refresh" :size="18"/><strong style="font-size:15px;flex:1">Auto-rename files</strong><button class="iconbtn" @click="renameOpen=false"><Icon name="x"/></button></div>
        <div class="drawer-body">
          <p class="page-sub" style="margin-bottom:14px">Rename {{ items.length }} file{{ items.length===1?'':'s' }} in <strong style="color:var(--ink-2)">{{ folder==='all'?'all folders':folder }}</strong> using a consistent pattern.</p>
          <div class="field"><label class="field-label">Pattern</label><input class="inp mono" v-model="renamePattern.pattern"></div>
          <div class="field-hint" style="margin:-8px 0 14px">Tokens: <span class="t-key">{folder}</span> <span class="t-key">{title}</span> <span class="t-key">{n}</span></div>
          <label class="field-label">Preview</label>
          <div class="rename-prev">
            <div v-for="(m,i) in items.slice(0,4)" :key="m.id" class="rename-prev-row"><span class="mono muted">{{ m.name }}</span><Icon name="arrowRight" :size="13" class="muted"/><span class="t-key">{{ autoName(m, i+1) }}</span></div>
            <div class="t-meta" v-if="items.length>4">+ {{ items.length-4 }} more</div>
          </div>
        </div>
        <div class="drawer-foot"><button class="btn btn--ghost" @click="renameOpen=false">Cancel</button><button class="btn btn--accent" @click="applyRename"><Icon name="check" :size="15"/> Rename {{ items.length }} files</button></div>
      </UiModal>
    </div>`,
  };

  /* ---- media item detail page (modal-style full editor) ---- */
  C.MediaDetail = {
    name: 'MediaDetail',
    props: { m: Object },
    emits: ['close','crop','delete'],
    methods: { grad, fileType(n){ const e=(n.split('.').pop()||'').toUpperCase(); return e; } },
    template: `
    <UiModal lg @close="$emit('close')">
      <div class="drawer-head">
        <Icon name="image" :size="18"/>
        <div style="flex:1;min-width:0"><strong style="font-size:15px">{{ m.name }}</strong><div class="t-key">{{ m.w }}×{{ m.h }} · {{ (m.kb/1024).toFixed(1) }} MB · {{ fileType(m.name) }}</div></div>
        <button class="iconbtn" @click="$emit('close')"><Icon name="x"/></button>
      </div>
      <div class="drawer-body" style="padding:0">
        <div class="md-wrap">
          <div class="md-preview">
            <div class="md-image" :style="{background: (m.preview||m.url) ? ('center/contain no-repeat url('+(m.preview||m.url)+')') : grad(m.hue), backgroundColor:'var(--paper-2)'}"></div>
            <div class="md-preview-actions">
              <button class="btn btn--soft btn--sm" @click="$emit('crop')"><Icon name="crop" :size="14"/> Crop</button>
              <button class="btn btn--soft btn--sm" @click="m.url && window.open(m.url, '_blank', 'noopener')"><Icon name="external" :size="14"/> Open</button>
            </div>
            <div class="md-usage"><Icon name="git" :size="14"/> Used in {{ m.usedIn }} item{{ m.usedIn===1?'':'s' }}</div>
          </div>
          <div class="md-fields">
            <div class="md-sec">
              <div class="md-sec-h">Details</div>
              <div class="field"><label class="field-label">Title</label><input class="inp" v-model="m.title"></div>
              <div class="field"><label class="field-label">File name</label><input class="inp mono" v-model="m.name"></div>
              <div class="field"><label class="field-label">Alt text</label><input class="inp" v-model="m.alt" placeholder="Describe the image for screen readers"></div>
              <div class="field" style="margin:0"><label class="field-label">Caption</label><textarea class="ta" v-model="m.caption" style="min-height:60px" placeholder="Optional caption"></textarea></div>
            </div>
            <div class="md-sec">
              <div class="md-sec-h">Organisation</div>
              <div class="cfg-2">
                <div class="field" style="margin:0"><label class="field-label">Folder</label><UiSelect v-model="m.folder"><option v-for="f in D().MEDIA_FOLDERS" :key="f">{{ f }}</option></UiSelect></div>
                <div class="field" style="margin:0"><label class="field-label">Uploaded</label><input class="inp" :value="m.uploaded" readonly></div>
              </div>
            </div>
            <div class="md-sec">
              <div class="md-sec-h"><Icon name="globe" :size="14"/> SEO</div>
              <div class="field"><label class="field-label">SEO title</label><input class="inp" v-model="m.seoTitle" :placeholder="m.title"></div>
              <div class="field" style="margin:0"><label class="field-label">SEO description</label><textarea class="ta" v-model="m.seoDesc" style="min-height:60px" placeholder="How this image is described to search engines"></textarea></div>
            </div>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn--danger" style="margin-right:auto" @click="$emit('delete', m)"><Icon name="trash" :size="15"/> Delete</button>
        <button class="btn btn--ghost" @click="$emit('close')">Cancel</button>
        <button class="btn" @click="$emit('close')"><Icon name="check" :size="15"/> Save</button>
      </div>
    </UiModal>`,
  };
  C.MediaDetail.methods.D = function(){ return window.__mdlr.data; };
})();
;
/* file: app.js */
/* ============================================================
   MODULAR · Atelier admin SPA — boot.
   - Shared API client (same-origin session + CSRF) on window.__mdlr.api.
   - Reactive hash routing, provide/inject (route/go/tweaks/ui).
   - Mounts <AppShell> with the active screen; unbuilt screens fall back
     to ScreenStub so per-phase delivery degrades gracefully.
   Ported from the prototype app.js; data is mock until each phase wires
   the matching API endpoint.
   ============================================================ */
(function () {
  const { createApp, reactive, watch } = Vue;
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const C = M.comps;
  const BOOT = window.__MDLR || {};

  /* ---- Shared API client: same-origin, cookie session + CSRF for writes ---- */
  const api = {
    base: (BOOT.apiBase || '/api/v1').replace(/\/$/, ''),
    async request(method, path, body, _retry) {
      const headers = { 'Accept': 'application/json' };
      const opts = { method, headers, credentials: 'same-origin' };
      const mutating = method !== 'GET' && method !== 'HEAD';
      if (mutating) headers['X-CSRF-TOKEN'] = BOOT.csrf || '';
      if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body);
      }
      const res = await fetch(this.base + path, opts);
      // Session expired → pop the login modal, wait for re-auth, retry once.
      if (res.status === 401 && !_retry && path.indexOf('/auth/') !== 0) {
        await requireReauth();
        return this.request(method, path, body, true);
      }
      const text = await res.text();
      let data = null;
      try { data = text ? JSON.parse(text) : null; } catch (e) { data = { raw: text }; }
      if (!res.ok) throw Object.assign(new Error('API ' + res.status), { status: res.status, data });
      return data;
    },
    get(p) { return this.request('GET', p); },
    post(p, b) { return this.request('POST', p, b || {}); },
    put(p, b) { return this.request('PUT', p, b || {}); },
    del(p, b) { return this.request('DELETE', p, b); },
    async upload(path, formData, _retry) {
      const res = await fetch(this.base + path, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': BOOT.csrf || '' },
        body: formData,
      });
      if (res.status === 401 && !_retry) {
        await requireReauth();
        return this.upload(path, formData, true);
      }
      const text = await res.text();
      let data = null; try { data = text ? JSON.parse(text) : null; } catch (e) { data = { raw: text }; }
      if (!res.ok) throw Object.assign(new Error('API ' + res.status), { status: res.status, data });
      return data;
    },
  };
  M.api = api;
  M.boot = BOOT;

  const SCREEN = {
    dashboard: 'ScreenDashboard',
    content: 'ScreenContentList',
    editor: 'ScreenContentEditor',
    postTypes: 'ScreenPostTypes',
    taxonomies: 'ScreenTaxonomies',
    fieldBuilder: 'ScreenFieldBuilder',
    components: 'ScreenComponents',
    binding: 'ScreenBinding',
    media: 'ScreenMedia',
    forms: 'ScreenForms',
    actions: 'ScreenActions',
    users: 'ScreenUsers',
    settings: 'ScreenSettings',
    design: 'ScreenDesignSystem',
    exportImport: 'ScreenExportImport',
    profile: 'ScreenProfile',
    modules: 'ScreenModules',
    roles: 'ScreenRoles',
    analytics: 'ScreenAnalytics',
    notifications: 'ScreenNotifications',
    library: 'ScreenLibrary',
    fieldDemo: 'ScreenFieldDemo',
    modulePage: 'ScreenModulePage',
    systemUpdate: 'ScreenSystemUpdate',
  };

  C.ScreenStub = {
    name: 'ScreenStub',
    inject: ['route'],
    template: `<div class="page"><div class="empty" style="padding:64px"><Icon name="box" :size="28"/><div>Screen “{{ route.name }}” komt in een latere fase.</div></div></div>`,
  };

  const route = reactive({ name: 'dashboard', params: {} });
  const tweaks = reactive({ provStyle: 'chip', open: false });
  const ui = reactive({ focus: false, cmdk: false, sidebar: false, notif: false, acct: false, help: false, modeHelp: false, loginModal: false });

  // Session-expiry re-auth gate. When an API call 401s, requireReauth() opens the
  // login modal and returns a promise that resolves once the user logs back in;
  // all in-flight 401'd requests await the same promise and then retry, so the user
  // continues exactly where they were. reauth() is called by the modal's form.
  let _authGate = null, _authResolve = null;
  function requireReauth() {
    if (!_authGate) {
      ui.loginModal = true;
      _authGate = new Promise((resolve) => { _authResolve = resolve; });
    }
    return _authGate;
  }
  async function reauth(email, password) {
    const res = await fetch(api.base + '/auth/relogin', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email, password: password }),
    });
    let data = null; try { data = await res.json(); } catch (e) {}
    if (!res.ok || !data || !data.ok) {
      throw new Error((data && (data.error || data.message)) || 'Login failed');
    }
    if (data.csrf) BOOT.csrf = data.csrf;
    if (data.user) { BOOT.user = data.user; try { window.__MDLR.user = data.user; } catch (e) {} }
    ui.loginModal = false;
    const r = _authResolve; _authGate = null; _authResolve = null;
    if (r) r();
    return data;
  }
  M.reauth = reauth;

  let popping = false;
  // Short SPA address for a module page: keep the module-page query params as REAL
  // query params on the SPA URL (not one giant encoded blob) + #modulePage. e.g.
  //   /base/admin/module-page.php?module=x&page=board&edit=9  ->  /base/admin/?module=x&page=board&edit=9#modulePage
  function szModulePageSpaUrl(u) {
    const path = location.pathname || '/';
    const qi = String(u).indexOf('?');
    const q = qi >= 0 ? String(u).slice(qi + 1) : '';
    return path + (q ? '?' + q : '') + '#modulePage';
  }
  // Rebuild the full module-page.php URL from the SPA's query params on refresh.
  function szModulePageUrlFromQuery(search) {
    const q = String(search || '').replace(/^\?/, '');
    if (!q) return '';
    return (BOOT.baseUrl || '') + '/admin/module-page.php?' + q;
  }
  // Best-effort module-page title from the admin menu (so the header isn't 'Module').
  function szModulePageTitle(mod, page) {
    const menu = BOOT.menu || [];
    for (let i = 0; i < menu.length; i++) {
      const subs = menu[i].sub || [];
      for (let j = 0; j < subs.length; j++) {
        const su = subs[j].url || '';
        if (su.indexOf('module=' + mod) >= 0 && su.indexOf('page=' + page) >= 0) return subs[j].label;
      }
      const iu = menu[i].url || '';
      if (iu.indexOf('module=' + mod) >= 0 && iu.indexOf('page=' + page) >= 0) return menu[i].label;
    }
    return '';
  }
  // Build the address-bar URL for a route. The post editor gets a real
  // ?postid=<id> (or the temporary ?postid=new while unsaved) so a refresh /
  // reload lands back on the same post instead of an empty editor.
  function urlFor(name, params) {
    params = params || {};
    const path = location.pathname || '/';
    if (name === 'editor' && params.postId != null && params.postId !== '') {
      const q = new URLSearchParams();
      q.set('postid', params.postId);
      if (params.type) q.set('type', params.type);
      return path + '?' + q.toString() + '#editor';
    }
    if (name === 'fieldBuilder' && params.group) {
      return path + '?group=' + encodeURIComponent(params.group) + '#fieldBuilder';
    }
    if (name === 'fieldBuilder' && params.type) {
      return path + '?type=' + encodeURIComponent(params.type) + '#fieldBuilder';
    }
    // Module pages keep their module-page query params on the SPA URL (short, real
    // params) so a refresh lands back on the exact sub-page (e.g. ?page=board&edit=9).
    if (name === 'modulePage' && params.url) {
      return szModulePageSpaUrl(params.url);
    }
    // Drop stale ?postid= / ?type= when leaving editor / field builder.
    return path + '#' + name;
  }
  function go(name, params) {
    route.name = name;
    route.params = params || {};
    const main = document.querySelector('.canvas');
    if (main) main.scrollTop = 0;
    if (!popping) {
      const plain = { name: name, params: JSON.parse(JSON.stringify(params || {})) };
      try { history.pushState(plain, '', urlFor(name, params)); } catch (e) {}
    }
  }

  window.addEventListener('popstate', (e) => {
    const s = e.state;
    popping = true;
    if (s && s.name) { route.name = s.name; route.params = s.params || {}; }
    else { route.name = 'dashboard'; route.params = {}; }
    const main = document.querySelector('.canvas');
    if (main) main.scrollTop = 0;
    popping = false;
  });

  // A module page (iframe) reports its current URL as the user navigates inside it
  // (e.g. opening a board item). Mirror it to the address bar + history state ONLY —
  // we deliberately do NOT touch route.params, so ScreenModulePage's iframe is never
  // re-rendered/reloaded by internal navigation (which would snap it back to the
  // overview). A refresh reads the sub-page back from the address bar in the init below.
  window.addEventListener('message', (e) => {
    if (e.origin !== location.origin) return;
    const d = e.data || {};
    if (!d.__mdModulePage || typeof d.url !== 'string' || route.name !== 'modulePage') return;
    let u;
    try { const abs = new URL(d.url, location.origin); abs.searchParams.delete('embed'); u = abs.pathname + (abs.search || ''); }
    catch (err) { return; }
    if (!u) return;
    const title = (route.params && route.params.title) || 'Module';
    try { history.replaceState({ name: 'modulePage', params: { url: u, title: title } }, '', szModulePageSpaUrl(u)); } catch (err) {}
  });

  // initial route from hash (so deep-links / refresh land on the right screen)
  const hashRoute = (location.hash || '').replace(/^#/, '');
  try {
    const qs = new URLSearchParams(location.search);
    const postId = qs.get('postid') || qs.get('edit');
    if (hashRoute && SCREEN[hashRoute]) {
      route.name = hashRoute;
      route.params = {};
      if (hashRoute === 'fieldBuilder' && qs.get('group')) route.params.group = qs.get('group');
      else if (hashRoute === 'fieldBuilder' && qs.get('type')) route.params.type = qs.get('type');
      else if (hashRoute === 'editor' && postId) {
        route.params = { postId: postId };
        const type = qs.get('type'); if (type) route.params.type = type;
      }
      else if (hashRoute === 'modulePage' && qs.get('module') && qs.get('page')) {
        const u = szModulePageUrlFromQuery(location.search);
        if (u) route.params = { url: u, title: szModulePageTitle(qs.get('module'), qs.get('page')) || 'Module' };
      }
    } else if (postId) {
      route.name = 'editor';
      const params = { postId: postId };
      const type = qs.get('type'); if (type) params.type = type;
      route.params = params;
    } else if (hashRoute && SCREEN[hashRoute]) {
      route.name = hashRoute;
    }
  } catch (e) {}

  const app = createApp({
    provide() { return { route, go, tweaks, ui, api }; },
    data() { return { route, tweaks }; },
    computed: {
      screenComp() {
        const want = SCREEN[this.route.name];
        return (want && C[want]) ? want : 'ScreenStub';
      },
    },
    methods: { openCmdK() { ui.cmdk = true; } },
    template: `<AppShell :screen="screenComp"/>`,
  });

  app.component('Icon', M.Icon);
  Object.keys(C).forEach(name => app.component(name, C[name]));

  watch(() => tweaks.provStyle, (p) => { document.documentElement.setAttribute('data-prov', p); }, { immediate: true });
  app.config.errorHandler = (err, vm, info) => { console.error('[Vue error]', err, info); };

  M.app = app;
  M.go = go;
  try { history.replaceState({ name: route.name, params: JSON.parse(JSON.stringify(route.params || {})) }, '', urlFor(route.name, route.params)); } catch (e) {}
  window.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); ui.cmdk = !ui.cmdk; }
  });
  app.mount('#app');
  if (M.theme) M.theme.apply();
  // Hydrate post types / field groups / components from the API (dogfood).
  if (M.store && M.store.hydrate) M.store.hydrate();
  // Theme (design system): load persisted tokens, and persist every edit back.
  if (M.theme && M.store) {
    const _origSave = M.theme.save.bind(M.theme);
    M.theme.save = function () { _origSave(); M.store.saveTheme(); };
    M.store.loadTheme();
  }
})();
