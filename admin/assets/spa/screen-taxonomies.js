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
