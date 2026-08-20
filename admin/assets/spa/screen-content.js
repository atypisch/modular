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
          // fallback to mock while post types hydrate
          this.items = D().POSTS.filter(p => p.type === pt.id).map(p => ({ ...p, slug: p.title.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,''), _trash:false }));
          this.total = this.items.length;
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
      saveQuick(){ const i = this.items.findIndex(x => x.id===this.quickId); if(i>=0) Object.assign(this.items[i], this.qdraft); this.quickId=null; this.qdraft=null; },
      cancelQuick(){ this.quickId=null; this.qdraft=null; },
      trash(p){ p._trash = true; },
      duplicate(p){ const i=this.items.indexOf(p); const c=JSON.parse(JSON.stringify(p)); c.id='n'+Math.random().toString(36).slice(2,7); c.title=p.title+' copy'; c.slug=c.slug+'-copy'; c.status='draft'; c._trash=false; this.items.splice(i+1,0,c); },
      restore(p){ p._trash = false; },
      destroy(p){ this.items = this.items.filter(x => x.id !== p.id); },
      view(p){ const u = (p && p.url) || ((window.__MDLR&&window.__MDLR.baseUrl||'') + '/' + this.pt.id + '/' + (p&&p.slug||'')); window.open(u, '_blank', 'noopener'); },
      toggle(id){ const i=this.sel.indexOf(id); if(i>=0)this.sel.splice(i,1); else this.sel.push(id); },
      toggleAll(){ this.sel = this.allOn ? [] : this.filtered.map(p=>p.id); },
      bulkTrash(){ this.items.forEach(p=>{ if(this.sel.includes(p.id)) p._trash=true; }); this.sel=[]; },
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
