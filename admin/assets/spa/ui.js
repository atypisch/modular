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
