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
