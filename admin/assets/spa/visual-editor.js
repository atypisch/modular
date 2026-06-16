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
