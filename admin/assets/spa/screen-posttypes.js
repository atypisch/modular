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
