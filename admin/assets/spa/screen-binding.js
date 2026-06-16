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
