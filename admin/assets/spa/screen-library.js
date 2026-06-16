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
