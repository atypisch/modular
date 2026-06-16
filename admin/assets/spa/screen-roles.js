/* ============================================================
   ROLES & CAPABILITIES — submenu of Users.
   Pick a role on the left; toggle exactly what it can see & do
   on the right, grouped by area. Simple but complete.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenRoles = {
    name: 'ScreenRoles',
    data() { return { selId: 'editor', showNew: false, draft: { name:'', desc:'' } }; },
    computed: {
      roles() { return D().ROLES; },
      groups() { return D().CAP_GROUPS; },
      allCaps() { return D().ALL_CAPS; },
      sel() { return this.roles.find(r => r.id === this.selId) || this.roles[0]; },
      isAdmin() { return this.sel.caps.includes('*'); },
      grantedCount() { return this.isAdmin ? this.allCaps.length : this.sel.caps.filter(c => this.allCaps.includes(c)).length; },
    },
    methods: {
      has(cap) { return this.isAdmin || this.sel.caps.includes(cap); },
      persist() {
        const store = window.__mdlr.store; if (!store) return;
        const role = this.sel;
        clearTimeout(this._t);
        this._t = setTimeout(() => store.saveRoleCaps(role), 400);
      },
      toggle(cap) {
        if (this.isAdmin || this.sel.builtinLock) return;
        const i = this.sel.caps.indexOf(cap);
        if (i >= 0) this.sel.caps.splice(i, 1); else this.sel.caps.push(cap);
        this.persist();
      },
      groupAllOn(g) { return g.caps.every(c => this.has(c.id)); },
      toggleGroup(g) {
        if (this.isAdmin) return;
        const on = this.groupAllOn(g);
        g.caps.forEach(c => { const i = this.sel.caps.indexOf(c.id); if (on) { if (i>=0) this.sel.caps.splice(i,1); } else if (i<0) this.sel.caps.push(c.id); });
        this.persist();
      },
      async addRole() {
        const name = this.draft.name.trim(); if (!name) return;
        const store = window.__mdlr.store;
        if (store) { try { const saved = await store.createRole({ name, desc: this.draft.desc }); if (saved && saved.name) this.selId = saved.name; } catch (e) {} }
        else { const id = name.toLowerCase().replace(/[^a-z0-9]+/g,'_'); this.roles.push({ id, name, desc: this.draft.desc, users:0, builtin:false, caps:[] }); this.selId = id; }
        this.showNew = false; this.draft = { name:'', desc:'' };
      },
      async del(r) {
        if (r.builtin) return;
        const store = window.__mdlr.store;
        if (store) { try { await store.deleteRole(r); } catch (e) {} }
        else { const i = this.roles.indexOf(r); this.roles.splice(i,1); }
        this.selId = (this.roles[0] || {}).id;
      },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Roles &amp; capabilities</h1><p class="page-sub" style="margin-top:6px">Define what each role can see and do. Capabilities are grouped by area — toggle them on or off.</p></div>
        <button class="btn btn--accent" @click="showNew=true"><Icon name="plus"/> New role</button>
      </div>

      <div class="roles-layout">
        <!-- role list -->
        <aside class="roles-list">
          <button v-for="r in roles" :key="r.id" class="role-item" :class="{'is-on': selId===r.id}" @click="selId=r.id">
            <span class="role-item-main">
              <strong>{{ r.name }}</strong>
              <span class="t-meta">{{ r.users }} {{ r.users===1?'user':'users' }}</span>
            </span>
            <span class="badge" v-if="r.caps.includes('*')">all</span>
            <span class="badge" v-else>{{ r.caps.filter(c=>allCaps.includes(c)).length }}</span>
          </button>
        </aside>

        <!-- role detail -->
        <section class="role-detail">
          <div class="card card-pad">
            <div class="flex items-center justify-between gap-3" style="flex-wrap:wrap">
              <div style="flex:1;min-width:220px">
                <input class="role-name" v-model="sel.name" :disabled="sel.builtin">
                <input class="inp" v-model="sel.desc" style="margin-top:8px" placeholder="Describe this role">
              </div>
              <div class="role-meta">
                <div class="role-meta-stat"><strong>{{ grantedCount }}</strong><span class="t-meta">of {{ allCaps.length }} capabilities</span></div>
                <button class="btn btn--danger btn--sm" v-if="!sel.builtin" @click="del(sel)"><Icon name="trash" :size="14"/> Delete role</button>
                <span class="badge badge--info" v-else><Icon name="lock" :size="11"/> built-in</span>
              </div>
            </div>
          </div>

          <div class="role-note" v-if="isAdmin"><Icon name="info" :size="15"/> Administrators always have every capability — including ones added by future modules. This can’t be narrowed.</div>

          <div class="caps">
            <div class="caps-group" v-for="g in groups" :key="g.group">
              <div class="caps-group-head">
                <span class="caps-group-ico"><Icon :name="g.icon" :size="15"/></span>
                <strong>{{ g.group }}</strong>
                <span class="t-meta">{{ g.caps.filter(c=>has(c.id)).length }}/{{ g.caps.length }}</span>
                <div class="grow"></div>
                <label class="caps-alltoggle" :class="{'is-dim': isAdmin}"><span class="t-meta">All</span><UiSwitch :modelValue="groupAllOn(g)" @update:modelValue="toggleGroup(g)"/></label>
              </div>
              <div class="caps-rows">
                <button v-for="c in g.caps" :key="c.id" class="cap-row" :class="{'is-on': has(c.id), 'is-locked': isAdmin}" @click="toggle(c.id)">
                  <span class="cap-check" :class="{'is-on': has(c.id)}"><Icon v-if="has(c.id)" name="check" :size="12"/></span>
                  <span class="cap-main"><strong>{{ c.label }}</strong><span class="t-meta">{{ c.desc }}</span></span>
                  <span class="cap-state">{{ has(c.id) ? 'Allowed' : 'Denied' }}</span>
                </button>
              </div>
            </div>
          </div>
        </section>
      </div>

      <UiModal v-if="showNew" @close="showNew=false">
        <div class="drawer-head"><Icon name="lock" :size="18"/><strong style="font-size:15px;flex:1">New role</strong><button class="iconbtn" @click="showNew=false"><Icon name="x"/></button></div>
        <div class="drawer-body">
          <div class="field"><label class="field-label">Name <span class="field-req">*</span></label><input class="inp" v-model="draft.name" placeholder="e.g. Translator"></div>
          <div class="field" style="margin:0"><label class="field-label">Description</label><input class="inp" v-model="draft.desc" placeholder="What this role is for"></div>
        </div>
        <div class="drawer-foot"><button class="btn btn--ghost" @click="showNew=false">Cancel</button><button class="btn btn--accent" @click="addRole" :disabled="!draft.name.trim()"><Icon name="plus" :size="15"/> Create role</button></div>
      </UiModal>
    </div>`,
  };
})();
