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
        allowedComps: ['cmp_hero','cmp_richtext','cmp_imgtext','cmp_features','cmp_quote','cmp_gallery'],
      };
    },
    created() {
      const rp = this.route.params || {};
      const DB = D();
      if (rp.group) {
        const g = rp.group === 'new'
          ? { id:'new', name:'New field group', icon:'box', desc:'', active:true, fields:[], location:[[{param:'post_type',op:'==',value: rp.type || 'page'}]] }
          : (DB.FIELD_GROUPS.find(x => x.id === rp.group) || DB.FIELD_GROUPS[0]);
        this.group = g;
        this.groupName = g.name; this.groupIcon = g.icon;
        this.schema = (g.id === 'fg_project')
          ? JSON.parse(JSON.stringify(DB.PROJECT_SCHEMA))
          : JSON.parse(JSON.stringify((g.fields||[]).map(f => ({ ...f, mode: f.mode || 'fixed' }))));
        this.location = JSON.parse(JSON.stringify(g.location || [[{param:'post_type',op:'==',value:'page'}]]));
      } else {
        const pt = DB.POST_TYPES.find(p => p.id === rp.type) || DB.POST_TYPES[4];
        this.pt = pt; this.mode = pt.mode;
        this.group = { id:'type_'+pt.id, name:pt.name+' fields', icon:pt.icon, active:true, order:1, position:'normal', style:'default', labelPlacement:'top', desc:'' };
        this.groupName = pt.name + ' fields'; this.groupIcon = pt.icon;
        this.schema = JSON.parse(JSON.stringify(DB.PROJECT_SCHEMA));
        this.location = [[{ param:'post_type', op:'==', value:pt.id }]];
      }
      this.rules = JSON.parse(JSON.stringify(DB.RULES));
      if (rp.view) this.view = rp.view;
    },
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
      addField(type) { const f = this.makeField(type); this.schema.push(f); this.sel = f; this.cfgTab = 'general'; },
      duplicate(i) { const c = JSON.parse(JSON.stringify(this.schema[i])); c.id = D().uid(); c.name += '_copy'; c.key = c.name; this.schema.splice(i+1, 0, c); },
      remove(i) { if (this.sel && this.schema[i] && this.sel.id === this.schema[i].id) this.sel = null; this.schema.splice(i, 1); },
      select(it) { this.sel = it; this.cfgTab = 'general'; },
      addTab() { this.schema.push(this.makeField('tab')); },
      // ---- rules ----
      newRule() { this.editRule = { id: D().uid(), target:{kind:'field', name:'', label:'Select…'}, action:'show', logic:'all', conds:[{ field:'', label:'', op:'is', value:'' }] }; },
      saveRule() { const i = this.rules.findIndex(r => r.id === this.editRule.id); if (i>=0) this.rules.splice(i,1,this.editRule); else this.rules.push(this.editRule); this.editRule = null; },
      saveGroup() {
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
        this.saving = true;
        window.__mdlr.store.saveFieldGroup(payload)
          .then(() => { this.saving = false; this.go('binding'); })
          .catch((e) => { this.saving = false; console.error('[fieldbuilder] save failed', e); alert('Saving the field group failed. Check the console.'); });
      },
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
