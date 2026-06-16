/* Dashboard + Users + Settings (lighter screens). */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenDashboard = {
    name: 'ScreenDashboard',
    inject: ['go', 'route'],
    computed: {
      tab() { return (this.route.params || {}).tab || 'overview'; },
      pts() { return D().POST_TYPES; },
      recent() { return D().POSTS.slice(0, 5); },
      updateCount() { return D().MODULES.filter(m => m.update).length + (D().SYSTEM.version !== D().SYSTEM.latest ? 1 : 0); },
      headTitle() { return { overview:'Good morning, Marten', updates:'Updates', analytics:'Analytics', reports:'Reports' }[this.tab]; },
      headSub() { return {
        overview:'Everything is quiet. 4 drafts are waiting and one release is scheduled for tomorrow.',
        updates:'Keep the CMS and its modules current.',
        analytics:'Traffic and content performance at a glance.',
        reports:'Build, export and schedule reports across your content.',
      }[this.tab]; },
      stats() {
        return [
          { label: 'Content items', value: '304', sub: '5 post types', icon: 'files' },
          { label: 'Published', value: '261', sub: '43 drafts', icon: 'check' },
          { label: 'Components', value: '10', sub: 'reused 137×', icon: 'layers' },
          { label: 'Media', value: '236', sub: '4 presets', icon: 'image' },
        ];
      },
    },
    methods: {
      typeName(t) { const p = this.pts.find(x => x.id === t); return p ? p.name : t; },
      statusClass(s) { return s === 'published' ? 'badge--ok' : s === 'scheduled' ? 'badge--info' : ''; },
    },
    template: `
    <div class="page fade-up">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px">Wednesday, 3 June</div>
          <h1 class="t-title t-h1">{{ headTitle }}</h1>
          <p class="page-sub" style="margin-top:6px">{{ headSub }}</p>
        </div>
        <div class="flex gap-2" v-if="tab==='overview'">
          <button class="btn btn--ghost" @click="go('binding')"><Icon name="settings2"/> Field groups</button>
          <button class="btn btn--accent" @click="go('editor',{postId:'new'})"><Icon name="plus"/> New content</button>
        </div>
      </div>

      <div class="tabs" style="margin-bottom:22px">
        <button :class="{'is-on':tab==='overview'}" @click="go('dashboard',{})"><Icon name="dashboard" :size="15"/> Overview</button>
        <button :class="{'is-on':tab==='updates'}" @click="go('dashboard',{tab:'updates'})"><Icon name="refresh" :size="15"/> Updates <span class="tab-count" v-if="updateCount" style="color:var(--accent)">{{ updateCount }}</span></button>
        <button :class="{'is-on':tab==='analytics'}" @click="go('dashboard',{tab:'analytics'})"><Icon name="sigma" :size="15"/> Analytics</button>
        <button :class="{'is-on':tab==='reports'}" @click="go('dashboard',{tab:'reports'})"><Icon name="table" :size="15"/> Reports</button>
      </div>

      <!-- OVERVIEW -->
      <template v-if="tab==='overview'">
        <div class="dash-stats">
          <div class="card card-pad stat" v-for="s in stats" :key="s.label">
            <div class="stat-top"><span class="stat-ico"><Icon :name="s.icon" :size="16"/></span><span class="t-meta">{{ s.label }}</span></div>
            <div class="stat-val t-display">{{ s.value }}</div>
            <div class="t-meta">{{ s.sub }}</div>
          </div>
        </div>
        <div class="dash-grid">
          <section class="panel">
            <div class="panel-head"><h2 class="t-title t-h3">Jump back in</h2><button class="btn btn--text btn--sm" @click="go('content',{type:'project'})">All content <Icon name="arrowRight" :size="14"/></button></div>
            <div class="dash-list">
              <button class="dash-row" v-for="p in recent" :key="p.id" @click="go('editor',{postId:p.id})">
                <span class="dash-row-main"><strong>{{ p.title }}</strong><span class="t-meta">{{ typeName(p.type) }} · {{ p.date }}</span></span>
                <span class="badge" :class="statusClass(p.status)">{{ p.status }}</span>
                <Icon name="chevRight" :size="16" class="muted"/>
              </button>
            </div>
          </section>
          <section class="panel">
            <div class="panel-head"><h2 class="t-title t-h3">Structure</h2><button class="btn btn--text btn--sm" @click="go('postTypes')">Post types <Icon name="arrowRight" :size="14"/></button></div>
            <div class="dash-list">
              <button class="dash-row" v-for="pt in pts" :key="pt.id" @click="go('content',{type:pt.id})">
                <span class="dash-row-ico"><Icon :name="pt.icon" :size="16"/></span>
                <span class="dash-row-main"><strong>{{ pt.name }}</strong><span class="t-meta mono">{{ pt.fieldCount }} fields · {{ pt.count }} items</span></span>
                <Icon name="chevRight" :size="16" class="muted"/>
              </button>
            </div>
          </section>
        </div>
      </template>

      <DashUpdates v-else-if="tab==='updates'"/>
      <DashAnalytics v-else-if="tab==='analytics'"/>
      <DashReports v-else/>
    </div>`,
  };

  C.ScreenUsers = {
    name: 'ScreenUsers',
    inject: ['go'],
    data() { return { sel: [], bulkRole: '', q: '', rows: [
      { id:1, name:'Marten Timan', email:'marten@atypisch.nl', role:'admin', status:'Active', last:'2 min ago' },
      { id:2, name:'Lieke de Vries', email:'lieke@atypisch.nl', role:'editor', status:'Active', last:'Yesterday' },
    ] }; },
    created() {
      if (!window.__mdlr.api) return;
      window.__mdlr.api.get('/users').then(rows => {
        if (Array.isArray(rows) && rows.length) this.rows = rows.map(u => ({ id: u.id, name: u.name || u.username || u.email, email: u.email, role: (u.role || 'user').toLowerCase(), status: (u.status === 'active' ? 'Active' : (u.status || 'Active')), last: u.last_login ? String(u.last_login).slice(0, 10) : '—' }));
      }).catch(() => {});
    },
    computed: {
      roles() {
        const r = (window.__mdlr.data && window.__mdlr.data.ROLES) || [];
        return r.length ? r.map(x => ({ id: x.id, label: x.name })) : [{ id:'admin', label:'Administrator' }, { id:'editor', label:'Editor' }, { id:'author', label:'Author' }, { id:'user', label:'User' }];
      },
      roleLabel() { const m = {}; this.roles.forEach(r => m[r.id] = r.label); return m; },
      filtered() {
        const q = this.q.trim().toLowerCase();
        if (!q) return this.rows;
        return this.rows.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || (this.roleLabel[u.role]||u.role).toLowerCase().includes(q));
      },
      allOn() { return this.filtered.length>0 && this.filtered.every(u => this.sel.includes(u.id)); },
    },
    methods: {
      toggle(id){ const i=this.sel.indexOf(id); if(i>=0) this.sel.splice(i,1); else this.sel.push(id); },
      toggleAll(){ this.sel = this.allOn ? [] : this.filtered.map(r=>r.id); },
      onRole(u, v){ u.role = v; const s = window.__mdlr.store; if (s) s.assignUserRole(u.id, v); },
      applyRole(){ if(!this.bulkRole) return; const s = window.__mdlr.store; this.rows.forEach(r=>{ if(this.sel.includes(r.id)){ r.role=this.bulkRole; if (s) s.assignUserRole(r.id, this.bulkRole); } }); this.sel=[]; this.bulkRole=''; },
      bulkDelete(){ this.rows = this.rows.filter(r=>!this.sel.includes(r.id)); this.sel=[]; },
      bulkDeactivate(){ this.rows.forEach(r=>{ if(this.sel.includes(r.id)) r.status='Inactive'; }); this.sel=[]; },
      del(id){ this.rows = this.rows.filter(r=>r.id!==id); this.sel = this.sel.filter(x=>x!==id); },
      toggleActive(u){ u.status = u.status==='Active' ? 'Inactive' : 'Active'; },
      initials(n){ return n.split(' ').map(s=>s[0]).join(''); },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div><h1 class="t-title t-h1">Users</h1><p class="page-sub" style="margin-top:6px">Accounts, roles and capabilities.</p></div>
        <div class="flex gap-2">
          <button class="btn btn--ghost" @click="go('roles')"><Icon name="lock"/> Roles &amp; capabilities</button>
          <button class="btn btn--accent"><Icon name="plus"/> Invite user</button>
        </div>
      </div>
      <div class="card">
        <div class="list-toolbar">
          <div class="topbar-search" style="max-width:300px;flex:0 1 300px;cursor:text"><Icon name="search" :size="15"/><input class="fb-lib-input" v-model="q" placeholder="Search users by name, email or role…" style="color:var(--ink)"></div>
          <span class="t-meta">{{ filtered.length }} of {{ rows.length }}</span>
          <div class="grow"></div>
        </div>
        <div class="list-toolbar" v-if="sel.length" style="border-top:1px solid var(--line)">
          <span class="badge badge--info">{{ sel.length }} selected</span>
          <span class="t-meta">Change role to</span>
          <UiSelect style="width:auto" v-model="bulkRole"><option value="">Choose…</option><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.label }}</option></UiSelect>
          <button class="btn btn--sm" @click="applyRole" :disabled="!bulkRole"><Icon name="check" :size="14"/> Apply</button>
          <div class="grow"></div>
          <button class="btn btn--ghost btn--sm" @click="bulkDeactivate"><Icon name="eyeOff" :size="14"/> Deactivate</button>
          <button class="btn btn--danger btn--sm" @click="bulkDelete"><Icon name="trash" :size="14"/> Delete {{ sel.length }}</button>
        </div>
        <table class="tbl tbl--rows">
          <thead><tr><th style="width:36px"><UiChk :modelValue="allOn" @update:modelValue="toggleAll"/></th><th>User</th><th style="width:170px">Role</th><th style="width:110px">Status</th><th style="width:120px">Last active</th></tr></thead>
          <tbody>
            <tr v-for="u in filtered" :key="u.id" :class="{'row-sel': sel.includes(u.id)}">
              <td @click.stop><UiChk :modelValue="sel.includes(u.id)" @update:modelValue="toggle(u.id)"/></td>
              <td><div class="flex items-center gap-3"><span class="avatar">{{ initials(u.name) }}</span><div><strong>{{ u.name }}</strong><div class="t-meta">{{ u.email }}</div>
                <div class="row-actions">
                  <button>Edit</button>
                  <button>Reset password</button>
                  <button @click="toggleActive(u)">{{ u.status==='Active' ? 'Deactivate' : 'Activate' }}</button>
                  <button class="is-danger" @click="del(u.id)">Delete</button>
                </div>
              </div></div></td>
              <td><UiSelect class="sel sel--bare" :modelValue="u.role" @update:modelValue="v => onRole(u, v)"><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.label }}</option></UiSelect></td>
              <td><span class="badge" :class="u.status==='Active'?'badge--ok':'badge--warn'">{{ u.status }}</span></td>
              <td class="muted">{{ u.last }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>`,
  };

  C.ScreenSettings = {
    name: 'ScreenSettings',
    inject: ['route', 'go'],
    data() {
      return {
        nav: [
          { group:'Site', items:[
            { id:'general', label:'General', icon:'settings' },
            { id:'appearance', label:'Appearance', icon:'palette' },
            { id:'permalinks', label:'Permalinks', icon:'link' },
          ]},
          { group:'Content', items:[
            { id:'media', label:'Media images', icon:'image' },
          ]},
          { group:'Delivery', items:[
            { id:'headless', label:'Headless API', icon:'webhook' },
          ]},
          { group:'Access', items:[
            { id:'roles', label:'Roles & capabilities', icon:'users' },
            { id:'security', label:'Security', icon:'lock' },
          ]},
          { group:'System', items:[
            { id:'design', label:'Design system', icon:'palette' },
            { id:'advanced', label:'Advanced', icon:'database' },
          ]},
        ],
        presets: () => window.__mdlr.data.IMAGE_PRESETS,
        gen: {
          title:'Atypisch', tagline:'EU-hosted web platforms', url:'https://atypisch.nl',
          email:'marten@atypisch.nl', delivery:'themed', language:'English', timezone:'Europe/Amsterdam',
        },
        editPreset: null,
        flags: {
          debug:false, restApi:true, graphql:false,
          force2fa:true, rateLimit:true, audit:true,
          adminBar:true, compact:false, keepOriginal:true, webp:true,
        },
        roles: [
          { name:'Administrator', users:1, caps:'All capabilities' },
          { name:'Editor', users:2, caps:'Publish & manage all content' },
          { name:'Author', users:1, caps:'Publish own content' },
          { name:'Contributor', users:0, caps:'Write drafts only' },
        ],
        saving: false, saved: false,
      };
    },
    created() {
      if (!window.__mdlr.api) return;
      window.__mdlr.api.get('/settings').then(s => {
        if (!s || typeof s !== 'object') return;
        if (s.site_title != null || s.site_name != null) this.gen.title = s.site_title != null ? s.site_title : s.site_name;
        if (s.site_tagline != null) this.gen.tagline = s.site_tagline;
        if (s.site_url != null) this.gen.url = s.site_url;
        if (s.admin_email != null) this.gen.email = s.admin_email;
        if (s.default_language) this.gen.language = s.default_language;
        if (s.timezone) this.gen.timezone = s.timezone;
      }).catch(() => {});
    },
    computed: {
      tab() { return (this.route.params || {}).tab || 'general'; },
      tabLabel() { for (const g of this.nav) { const it = g.items.find(i => i.id === this.tab); if (it) return it.label; } return 'General'; },
      imagePresets() { return window.__mdlr.data.IMAGE_PRESETS; },
      themes() { return window.__mdlr.data.THEMES; },
    },
    methods: {
      activateTheme(t) { window.__mdlr.data.THEMES.forEach(x => { x.active = (x === t); }); },
      addPreset() { const p = { name:'new-size', w:800, h:600, ratio:'4:3', crop:'cover', on:true, hue:30 }; window.__mdlr.data.IMAGE_PRESETS.push(p); this.editPreset = p; },
      removePreset(p) { const a = window.__mdlr.data.IMAGE_PRESETS; const i = a.indexOf(p); if (i >= 0) a.splice(i, 1); if (this.editPreset === p) this.editPreset = null; },
      restorePresets() {
        const a = window.__mdlr.data.IMAGE_PRESETS;
        a.splice(0, a.length, ...JSON.parse(JSON.stringify(window.__mdlr.data.IMAGE_PRESETS_DEFAULT)));
        this.editPreset = null;
      },
      presetsDirty() {
        return JSON.stringify(window.__mdlr.data.IMAGE_PRESETS) !== JSON.stringify(window.__mdlr.data.IMAGE_PRESETS_DEFAULT);
      },
      gradFor(h) { return 'linear-gradient(135deg, hsl(' + h + ' 32% 80%), hsl(' + (h - 8) + ' 26% 58%))'; },
      saveSettings() {
        if (!window.__mdlr.api) return;
        this.saving = true;
        const options = { site_title: this.gen.title, site_tagline: this.gen.tagline, site_url: this.gen.url, admin_email: this.gen.email, default_language: this.gen.language, timezone: this.gen.timezone };
        window.__mdlr.api.put('/settings', { options })
          .then(() => { this.saving = false; this.saved = true; setTimeout(() => { this.saved = false; }, 2200); })
          .catch(e => { this.saving = false; console.error('[settings] save failed', e); alert('Saving settings failed.'); });
      },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head"><div><h1 class="t-title t-h1">Settings</h1><p class="page-sub" style="margin-top:6px">Everything that configures this install — site, delivery and access.</p></div><div class="flex items-center gap-2"><transition name="fade"><span v-if="saved" class="badge badge--ok"><Icon name="check" :size="12"/> Saved</span></transition><button class="btn" :disabled="saving" @click="saveSettings"><Icon name="save" :size="15"/> {{ saving ? 'Saving…' : 'Save' }}</button></div></div>
      <div class="settings-layout">
        <nav class="settings-nav">
          <div v-for="g in nav" :key="g.group" class="settings-nav-group">
            <div class="settings-nav-label">{{ g.group }}</div>
            <button v-for="it in g.items" :key="it.id" class="settings-nav-item" :class="{'is-on': tab===it.id}" @click="go('settings',{tab:it.id})">
              <Icon :name="it.icon" :size="15"/> {{ it.label }}
            </button>
          </div>
        </nav>

        <div class="settings-content">
          <!-- GENERAL -->
          <div v-if="tab==='general'" class="card card-pad">
            <h2 class="t-title t-h3" style="margin:0 0 20px">General</h2>
            <div class="field"><label class="field-label">Site title</label><input class="inp" v-model="gen.title"></div>
            <div class="field"><label class="field-label">Tagline</label><input class="inp" v-model="gen.tagline"></div>
            <div class="field"><label class="field-label">Site URL</label><input class="inp mono" v-model="gen.url"></div>
            <div class="field"><label class="field-label">Admin email</label><input class="inp mono" v-model="gen.email"></div>
            <div class="field">
              <label class="field-label">Delivery mode</label>
              <div class="seg seg--fill">
                <button :class="{'is-on':gen.delivery==='themed'}" @click="gen.delivery='themed'"><Icon name="image" :size="14"/> Themed</button>
                <button :class="{'is-on':gen.delivery==='headless'}" @click="gen.delivery='headless'"><Icon name="webhook" :size="14"/> Headless (API)</button>
                <button :class="{'is-on':gen.delivery==='hybrid'}" @click="gen.delivery='hybrid'"><Icon name="layers" :size="14"/> Hybrid</button>
              </div>
              <div class="field-hint">{{ gen.delivery==='themed' ? 'Modular renders a public theme from your content.' : gen.delivery==='headless' ? 'Content is served only through the read API — no rendered theme.' : 'Both a rendered theme and a read API, from the same content.' }}</div>
            </div>
            <div class="opt-row"><div><strong>Default language</strong><div class="field-hint">Used for new content.</div></div><UiSelect style="width:auto" v-model="gen.language"><option>English</option><option>Nederlands</option></UiSelect></div>
            <div class="opt-row" style="padding-bottom:0"><div><strong>Timezone</strong><div class="field-hint">For scheduling &amp; timestamps.</div></div><UiSelect style="width:auto" v-model="gen.timezone"><option>Europe/Amsterdam</option><option>UTC</option></UiSelect></div>
          </div>

          <!-- APPEARANCE -->
          <div v-else-if="tab==='appearance'" class="settings-embed">
            <div class="flex items-center justify-between" style="margin-bottom:6px;flex-wrap:wrap;gap:12px">
              <div><h2 class="t-title t-h2" style="margin:0">Appearance</h2><p class="page-sub" style="margin-top:4px">Choose the public site theme. The admin’s own look lives in the <a class="mode-help-link" style="display:inline-flex" @click.prevent="go('settings',{tab:'design'})">design system</a>.</p></div>
              <button class="btn btn--ghost btn--sm"><Icon name="upload" :size="14"/> Upload theme</button>
            </div>

            <div class="theme-grid">
              <div class="theme-card" v-for="t in themes" :key="t.id" :class="{'is-active': t.active}">
                <div class="theme-thumb" :class="{'is-dark': t.dark}" :style="{background:t.palette[0]}">
                  <span class="th-bar" :style="{background:t.palette[1], borderColor:t.palette[3]}"><span class="th-dot" :style="{background:t.palette[3]}"></span><span class="th-line" :style="{background:t.palette[2],opacity:.35}"></span></span>
                  <span class="th-hero"><span class="th-h" :style="{background:t.palette[2]}"></span><span class="th-h th-h--sm" :style="{background:t.palette[2],opacity:.5}"></span><span class="th-btn" :style="{background:t.palette[3]}"></span></span>
                  <span class="th-cards"><span :style="{background:t.palette[1],borderColor:t.palette[3]}"></span><span :style="{background:t.palette[1],borderColor:t.palette[3]}"></span><span :style="{background:t.palette[1],borderColor:t.palette[3]}"></span></span>
                  <span class="theme-active-flag" v-if="t.active"><Icon name="check" :size="13"/></span>
                </div>
                <div class="theme-meta">
                  <div><strong>{{ t.name }}</strong><div class="t-meta">{{ t.desc }}</div></div>
                </div>
                <div class="theme-foot">
                  <span class="t-key">by {{ t.author }}</span>
                  <div class="flex gap-2">
                    <button class="btn btn--ghost btn--sm">Preview</button>
                    <button v-if="t.active" class="btn btn--sm" disabled><Icon name="check" :size="13"/> Active</button>
                    <button v-else class="btn btn--soft btn--sm" @click="activateTheme(t)">Activate</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="card card-pad" style="margin-top:18px;max-width:760px">
              <h3 class="t-title t-h3" style="margin:0 0 14px">Admin preferences</h3>
              <div class="opt-row" style="padding-top:0"><div><strong>Show admin bar on site</strong><div class="field-hint">For logged-in editors.</div></div><UiSwitch v-model="flags.adminBar"/></div>
              <div class="opt-row" style="padding-bottom:0"><div><strong>Compact density</strong><div class="field-hint">Tighter rows across the admin.</div></div><UiSwitch v-model="flags.compact"/></div>
            </div>
          </div>

          <!-- PERMALINKS -->
          <div v-else-if="tab==='permalinks'" class="settings-embed">
            <PermalinksPanel/>
          </div>

          <!-- MEDIA -->
          <div v-else-if="tab==='media'" class="settings-embed">
            <div class="flex items-start justify-between" style="margin-bottom:16px;gap:16px;flex-wrap:wrap">
              <div>
                <h2 class="t-title t-h2" style="margin:0">Media images</h2>
                <p class="page-sub" style="margin-top:6px;max-width:62ch">Every uploaded image is re-cropped into these presets. Keep the set small — more presets means more storage and slower uploads.</p>
              </div>
              <div class="flex gap-2">
                <button class="btn btn--ghost btn--sm" :disabled="!presetsDirty()" @click="restorePresets"><Icon name="refresh" :size="14"/> Restore defaults</button>
                <button class="btn btn--accent btn--sm" @click="addPreset"><Icon name="plus" :size="14"/> Add preset</button>
              </div>
            </div>

            <div class="preset-grid">
              <div class="preset-card" v-for="p in imagePresets" :key="p.name" :class="{'is-off': !p.on, 'is-editing': editPreset===p}">
                <div class="preset-thumb" :style="{ aspectRatio: (p.w/p.h), background: gradFor(p.hue) }">
                  <span class="preset-dim mono">{{ p.w }}×{{ p.h }}</span>
                  <div class="preset-thumb-actions">
                    <button class="btn btn--soft btn--xs"><Icon name="image" :size="12"/> Sample</button>
                  </div>
                </div>
                <div class="preset-body">
                  <template v-if="editPreset!==p">
                    <div class="preset-meta">
                      <strong>{{ p.name }}</strong>
                      <span class="t-meta mono">{{ p.ratio }} · {{ p.crop }}</span>
                    </div>
                    <div class="preset-foot">
                      <label class="mod-toggle"><span class="t-meta">{{ p.on ? 'On' : 'Off' }}</span><UiSwitch v-model="p.on"/></label>
                      <div class="grow"></div>
                      <button class="iconbtn iconbtn--sm" @click="editPreset=p" title="Edit"><Icon name="pencil" :size="14"/></button>
                      <button class="iconbtn iconbtn--sm is-danger" @click="removePreset(p)" title="Delete"><Icon name="trash" :size="14"/></button>
                    </div>
                  </template>
                  <template v-else>
                    <div class="field" style="margin-bottom:10px"><label class="field-label">Name</label><input class="inp" v-model="p.name"></div>
                    <div class="cfg-2" style="margin-bottom:10px">
                      <div class="field" style="margin:0"><label class="field-label">Width</label><input class="inp mono" type="number" v-model.number="p.w"></div>
                      <div class="field" style="margin:0"><label class="field-label">Height</label><input class="inp mono" type="number" v-model.number="p.h"></div>
                    </div>
                    <div class="cfg-2" style="margin-bottom:12px">
                      <div class="field" style="margin:0"><label class="field-label">Ratio label</label><input class="inp mono" v-model="p.ratio"></div>
                      <div class="field" style="margin:0"><label class="field-label">Fit</label><UiSelect v-model="p.crop"><option value="cover">cover (crop)</option><option value="contain">contain</option></UiSelect></div>
                    </div>
                    <div class="flex gap-2" style="justify-content:flex-end">
                      <button class="btn btn--ghost btn--sm" @click="removePreset(p)"><Icon name="trash" :size="13"/> Delete</button>
                      <button class="btn btn--accent btn--sm" @click="editPreset=null"><Icon name="check" :size="14"/> Done</button>
                    </div>
                  </template>
                </div>
              </div>

              <button class="preset-add" @click="addPreset"><Icon name="plus" :size="20"/><span>Add preset</span></button>
            </div>

            <div class="card card-pad" style="margin-top:18px;max-width:760px">
              <div class="opt-row" style="padding-top:0"><div><strong>Keep original upload</strong><div class="field-hint">Store the full-resolution file alongside the presets.</div></div><UiSwitch v-model="flags.keepOriginal"/></div>
              <div class="opt-row"><div><strong>Convert to WebP</strong><div class="field-hint">Generate modern formats for each preset.</div></div><UiSwitch v-model="flags.webp"/></div>
              <div class="opt-row" style="padding-bottom:0"><div><strong>Max upload size</strong><div class="field-hint">Reject files larger than this.</div></div><UiSelect style="width:auto"><option>10 MB</option><option>25 MB</option><option>50 MB</option></UiSelect></div>
            </div>
          </div>

          <!-- HEADLESS -->
          <div v-else-if="tab==='headless'" class="card card-pad">
            <h2 class="t-title t-h3" style="margin:0 0 8px">Headless API</h2>
            <p class="page-sub" style="margin-bottom:16px">A read API over the same content. Same token model as templates.</p>
            <div class="opt-row" style="padding-top:0"><div><strong>Enable REST API</strong><div class="field-hint">Exposes <span class="t-key">/api/v1</span>.</div></div><UiSwitch v-model="flags.restApi"/></div>
            <div class="opt-row"><div><strong>Enable GraphQL</strong><div class="field-hint">Single endpoint at <span class="t-key">/graphql</span>.</div></div><UiSwitch v-model="flags.graphql"/></div>
            <div class="field" style="margin-top:16px"><label class="field-label">Base URL</label><input class="inp mono" value="https://atypisch.nl/api/v1"></div>
            <div class="field" style="margin:0"><label class="field-label">API key</label><div class="inp-group"><input class="inp mono" value="md_live_8f2c…a91" readonly><span class="affix" style="border:0;border-left:1px solid var(--line);cursor:pointer">copy</span></div></div>
          </div>

          <!-- ROLES -->
          <div v-else-if="tab==='roles'" class="card">
            <div class="card-pad" style="padding-bottom:6px"><h2 class="t-title t-h3" style="margin:0 0 4px">Roles &amp; capabilities</h2><p class="page-sub">Who can do what. Manage members in <a class="mode-help-link" style="display:inline-flex" @click.prevent="go('users')">Users</a>.</p></div>
            <table class="tbl">
              <thead><tr><th>Role</th><th style="width:90px">Users</th><th>Capabilities</th><th style="width:48px"></th></tr></thead>
              <tbody>
                <tr v-for="r in roles" :key="r.name">
                  <td><strong>{{ r.name }}</strong></td>
                  <td class="mono muted">{{ r.users }}</td>
                  <td class="muted">{{ r.caps }}</td>
                  <td style="text-align:right"><button class="iconbtn" @click="go('users')"><Icon name="chevRight" :size="15"/></button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- SECURITY -->
          <div v-else-if="tab==='security'" class="card card-pad">
            <h2 class="t-title t-h3" style="margin:0 0 18px">Security</h2>
            <div class="opt-row" style="padding-top:0"><div><strong>Force two-factor for admins</strong><div class="field-hint">Require an authenticator app.</div></div><UiSwitch v-model="flags.force2fa"/></div>
            <div class="opt-row"><div><strong>Login rate limiting</strong><div class="field-hint">Throttle after 5 failed attempts.</div></div><UiSwitch v-model="flags.rateLimit"/></div>
            <div class="opt-row"><div><strong>Audit log</strong><div class="field-hint">Record edits, logins and imports.</div></div><UiSwitch v-model="flags.audit"/></div>
            <div class="opt-row" style="padding-bottom:0"><div><strong>Session length</strong><div class="field-hint">Auto-logout after inactivity.</div></div><UiSelect style="width:auto"><option>8 hours</option><option>24 hours</option><option>7 days</option></UiSelect></div>
          </div>

          <!-- ADVANCED -->
          <div v-else-if="tab==='advanced'" class="card card-pad">
            <h2 class="t-title t-h3" style="margin:0 0 18px">Advanced</h2>
            <div class="opt-row" style="padding-top:0"><div><strong>Debug mode</strong><div class="field-hint">Verbose errors in the admin.</div></div><UiSwitch v-model="flags.debug"/></div>
            <div v-if="flags.debug" class="note-inline" style="margin:0 0 4px"><Icon name="info" :size="15"/><span>Debug mode is <strong>on</strong> — stack traces and query logs are shown in the admin. Don’t leave this on in production.</span></div>
            <div class="opt-row"><div><strong>Object cache</strong><div class="field-hint">Redis-backed.</div></div><span class="badge badge--ok"><span class="badge--dot"></span>connected</span></div>
            <div class="opt-row"><div><strong>Export / Import</strong><div class="field-hint">Move content between installs.</div></div><button class="btn btn--ghost btn--sm" @click="go('exportImport',{tab:'export'})"><Icon name="external" :size="14"/> Open</button></div>
            <div class="opt-row" style="padding-bottom:0"><div><strong>Database</strong><div class="field-hint">MySQL 8 · 1,284 rows across 19 tables.</div></div><span class="t-key">modular_atypisch</span></div>
          </div>

          <!-- DESIGN SYSTEM (embedded) -->
          <div v-else-if="tab==='design'" class="settings-embed">
            <ScreenDesignSystem/>
          </div>
        </div>
      </div>
    </div>`,
  };
})();
