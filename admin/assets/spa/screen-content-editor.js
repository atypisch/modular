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
        post: { id: null, title: '', slug: '', status: 'draft', type: '', url: '' },
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
      jsonCode() { return JSON.stringify({ title: this.post.title, slug: this.post.slug, status: this.post.status, data: this.values }, null, 2); },
      // tabs come from the post-type schema's tab nodes (fallback: single Content tab)
      tabs() {
        const tabs = []; let cur = { label: 'Content', icon: 'text', items: [] };
        let hasTab = false;
        this.typeFields.forEach(f => {
          if (F().canonical(f.type) === 'tab') { hasTab = true; if (cur.items.length || tabs.length) tabs.push(cur); cur = { label: f.label, icon: f.icon || 'text', items: [] }; }
          else cur.items.push(f);
        });
        tabs.push(cur);
        return hasTab ? tabs.filter(t => t.items.length) : [{ label: 'Content', icon: 'text', items: this.typeFields.filter(f => F().canonical(f.type) !== 'tab') }];
      },
      typeFields() { return this.schema.filter(f => f.prov !== 'shared'); },
      activeTabObj() { return this.tabs[this.activeTab] || this.tabs[0] || { items: [] }; },
      // the editor's group cards for the active tab
      groups() {
        const out = [];
        out.push({ id: 'type', label: this.activeTabObj.label, prov: 'type', loose: false, fields: (this.activeTabObj.items || []) });
        this.fieldGroups.forEach(fg => {
          out.push({ id: 'fg' + fg.id, label: fg.name, icon: fg.icon, prov: 'shared', loose: false, fields: this.schema.filter(f => f._group === fg.id) });
        });
        this.perPostGroups.forEach(g => { out.push({ id: 'pp:' + g.id, ppId: g.id, label: g.label, prov: 'post', loose: true, fields: g.fields }); });
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
      isContainer(f) { return F().isContainer(f.type); },
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

      load() {
        const rp = this.route.params || {};
        const api = M.api;
        this.blocksMap = this.buildBlocksMap();
        if (rp.postId && rp.postId !== 'new' && api) {
          this.loading = true;
          api.get('/posts/' + rp.postId + '/resolved-fieldset').then(rf => {
            this.loading = false;
            if (rf && rf.post) {
              this.post = { id: rf.post.id, title: rf.post.title || '', slug: rf.post.slug || '', status: rf.post.status || 'draft', type: (rf.post.post_type && rf.post.post_type.slug) || '', url: rf.post.url || '' };
              this.schema = ((rf.schema && rf.schema.fields) || []).map(f => Object.assign({ prov: 'type' }, f));
              this.fieldGroups = rf.field_groups || [];
              this.values = reactive(rf.values && typeof rf.values === 'object' ? rf.values : {});
              // per-post containers (+ migration from the old flat _perPost format)
              const lay = rf.layout && typeof rf.layout === 'object' ? rf.layout : {};
              if (Array.isArray(lay._perPostGroups)) {
                this.perPostGroups = lay._perPostGroups.map(g => ({ id: g.id || D().uid(), label: g.label || 'Group', fields: (g.fields || []).map(f => Object.assign({ prov: 'post' }, f)) }));
              } else if (Array.isArray(lay._perPost) && lay._perPost.length) {
                this.perPostGroups = [{ id: D().uid(), label: 'Added to this item', fields: lay._perPost.map(f => Object.assign({ prov: 'post' }, f)) }];
              } else { this.perPostGroups = []; }
            }
          }).catch(e => { this.loading = false; console.warn('[editor] resolved-fieldset failed', e && e.status); });
        } else {
          this.loading = false;
          if (rp.type) this.post.type = rp.type;
          const pt = D().POST_TYPES.find(p => p.id === this.post.type);
          const sc = pt && pt._schema;
          this.schema = ((sc && sc.fields) || (Array.isArray(sc) ? sc : [])).map(f => Object.assign({ prov: 'type' }, f));
          this.values = reactive({});
        }
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

      flash(msg) { this.savedMsg = msg; if (this._flashTimer) clearTimeout(this._flashTimer); this._flashTimer = setTimeout(() => { this.savedMsg = ''; }, 2600); },
      save() {
        const api = M.api; if (!api) return;
        this.saving = true;
        const rp = this.route.params || {};
        const layout = { _perPostGroups: this.perPostGroups.map(g => ({ id: g.id, label: g.label, fields: g.fields.map(f => ({ id: f.id, label: f.label, name: f.name, key: f.key, type: f.type, required: f.required, instructions: f.instructions, w: f.w })) })) };
        const body = { title: this.post.title, slug: this.post.slug, status: this.post.status, data: this.values, layout };
        const isNew = !this.post.id || rp.postId === 'new';
        const req = isNew ? api.post('/posts', Object.assign({ post_type_id: this.ptDbId() }, body)) : api.put('/posts/' + this.post.id, body);
        return req.then((saved) => {
          this.saving = false;
          if (saved && saved.id) { this.post.id = saved.id; if (saved.slug) this.post.slug = saved.slug; if (saved.status) this.post.status = saved.status; if (saved.url) this.post.url = saved.url; }
          if (isNew && saved && saved.id) this.go('editor', { postId: saved.id, type: this.post.type, flash: 'Created' });
          else this.flash('Updated');
          return saved;
        }).catch(e => { this.saving = false; console.error('[editor] save failed', e); alert('Saving the post failed. Check the console.'); });
      },
      // Open the post's public page in a new tab.
      viewPost() {
        const u = this.post.url || ((window.__MDLR && window.__MDLR.baseUrl || '') + '/' + this.post.type + '/' + this.post.slug);
        window.open(u, '_blank', 'noopener');
      },
      // Save the current edits first, then open the public page so the preview reflects them.
      previewChanges() {
        const r = this.save();
        if (r && typeof r.then === 'function') r.then(() => this.viewPost()); else this.viewPost();
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
          <button class="btn" :disabled="saving" @click="save"><Icon name="save" :size="15"/> {{ saving ? 'Saving…' : 'Save' }}</button>
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
              <button class="ed-permalink-btn" @click="previewChanges" title="Save changes and open the public page"><Icon name="eye" :size="12"/> Preview changes</button>
              <a class="ed-permalink-btn" :href="post.url || '#'" target="_blank" rel="noopener" @click="!post.url && $event.preventDefault()"><Icon name="external" :size="12"/> View</a>
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
                  <span class="t-meta">{{ g.fields.length }} field{{ g.fields.length===1?'':'s' }}</span>
                  <div class="grow"></div>
                  <button v-if="g.loose" class="iconbtn iconbtn--sm is-danger" @click.stop="removePPGroup(g)" title="Remove this container"><Icon name="x" :size="14"/></button>
                </div>
                <div class="fgroup-body" v-show="!gCollapsed(g)" @dragover="dOverGroup(g,$event)" @drop="dDropGroup(g,$event)">
                  <div class="fgrid">
                    <template v-for="(f,fi) in g.fields" :key="f.id || f.name">
                      <div class="fcell" :class="'w-'+(f.w||'full')">
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
              <div class="field"><label class="field-label">Status</label>
                <UiSelect v-model="post.status">
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
                  <option value="scheduled">Scheduled</option>
                  <option value="private">Private</option>
                </UiSelect>
              </div>
              <div class="field" style="margin:0"><label class="field-label">Slug</label><input class="inp mono" v-model="post.slug" placeholder="auto"></div>
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
