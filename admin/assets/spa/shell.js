/* App shell: sidebar nav + topbar. Renders the active screen via <component :is>. */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;

  const D = () => window.__mdlr.data;
  const taxFor = (typeId) => {
    const pt = D().POST_TYPES.find(p => p.id === typeId);
    return ((pt && pt.taxonomies) || []).map(id => D().TAXONOMIES.find(t => t.id === id)).filter(Boolean);
  };
  const plural = (n) => /s$/i.test(n) ? n : n + 's';
  function buildContentItems() {
    return D().POST_TYPES.filter(t => t.inMenu).map(t => ({
      name:'content', label: plural(t.name), icon: t.icon, params:{ type:t.id },
      children:[
        { label:'All '+plural(t.name).toLowerCase(), name:'content', params:{ type:t.id } },
        { label:'Add new', name:'editor', params:{ postId:'new', type:t.id } },
        ...taxFor(t.id).map(tx => ({ label: tx.name, name:'taxonomies', params:{ tax: tx.id } })),
      ],
    }));
  }

  // Module menu icons come as FontAwesome ('fas fa-users'); map to our icon set.
  const FA_ICON = { users:'users', user:'user', cog:'settings', gear:'settings', sliders:'sliders', music:'layers', calendar:'calendar', 'calendar-alt':'calendar', map:'pin', 'map-marker-alt':'pin', 'map-pin':'pin', envelope:'mail', comments:'mail', 'comment-dots':'mail', 'chart-line':'sigma', 'puzzle-piece':'box', flag:'pin', star:'star', list:'list', cubes:'layers', boxes:'layers', 'layer-group':'layers', database:'database', image:'image', zap:'zap', bolt:'zap' };
  function mapModuleIcon(icon) { if (!icon) return 'box'; const m = String(icon).match(/fa-([a-z0-9-]+)/); if (m) return FA_ICON[m[1]] || 'box'; return icon; }

  // Distribute module-registered menu items. A module item may set `section`
  // (e.g. 'Content','Structure','System') to place itself anywhere in the nav;
  // otherwise it appears as a caption-less group at the end.
  function applyModuleNav(nav) {
    const menu = (window.__MDLR && window.__MDLR.menu) || [];
    if (!menu.length) return;
    const extras = [];
    menu.forEach(m => {
      const subs = (m.sub || []).map(s => ({ label: s.label, route: 'modulePage', params: { url: s.url, title: s.label } }));
      const item = { label: m.label, icon: mapModuleIcon(m.icon), _mod: true, route: 'modulePage', params: { url: m.url, title: m.label }, match: m.match, children: subs.length ? subs : undefined };
      const sec = (m.section || '').toLowerCase();
      const grp = sec ? nav.find(g => (g.label || '').toLowerCase() === sec) : null;
      if (grp) grp.items.push(item); else extras.push(item);
    });
    if (extras.length) nav.push({ label: '', items: extras });
  }

  function buildNav() {
    const nav = [
      { items: [ { name:'dashboard', label:'Dashboard', icon:'dashboard', children:[
        { label:'Overview', name:'dashboard' },
        { label:'Updates', name:'dashboard', params:{ tab:'updates' } },
        { label:'Analytics', name:'dashboard', params:{ tab:'analytics' } },
        { label:'Reports', name:'dashboard', params:{ tab:'reports' } },
      ] } ] },
      { label: 'Content', items: [
        ...buildContentItems(),
        { name:'media', label:'Media', icon:'image', children:[
          { label:'Library', name:'media' },
          { label:'Upload', name:'media', params:{ upload:1 } },
        ]},
      ]},
      { label: 'Structure', items: [
        { name:'binding', label:'Field groups', icon:'git' },
        { name:'postTypes', label:'Post types', icon:'database', children:[
          { label:'All types', name:'postTypes' },
          { label:'Taxonomies', name:'taxonomies' },
        ]},
        { name:'components', label:'Blocks', icon:'layers' },
        { name:'library', label:'Field types', icon:'grid' },
        { name:'forms', label:'Forms', icon:'form' },
      ]},
      { label: 'Automation', items: [
        { name:'actions', label:'Actions', icon:'zap' },
      ]},
      { label: 'System', items: [
        { name:'modules', label:'Modules', icon:'layers' },
        { name:'users', label:'Users', icon:'users', children:[
          { label:'All users', name:'users' },
          { label:'Roles & capabilities', name:'roles' },
          { label:'Profile', name:'profile' },
        ]},
        { name:'exportImport', label:'Export / Import', icon:'refresh', children:[
          { label:'Export', name:'exportImport' },
          { label:'Import', name:'exportImport', params:{ tab:'import' } },
          { label:'History', name:'exportImport', params:{ tab:'history' } },
        ]},
        { name:'settings', label:'Settings', icon:'settings', children:[
          { label:'General', name:'settings' },
          { label:'Appearance', name:'settings', params:{ tab:'appearance' } },
          { label:'Media images', name:'settings', params:{ tab:'media' } },
          { label:'Headless API', name:'settings', params:{ tab:'headless' } },
          { label:'Design system', name:'settings', params:{ tab:'design' } },
        ]},
        { name:'systemUpdate', label:'System update', icon:'refresh' },
      ]},
    ];
    applyModuleNav(nav);
    return nav;
  }

  C.AppShell = {
    name: 'AppShell',
    inject: ['route', 'go', 'tweaks', 'ui'],
    props: { screen: String },
    watch: { 'route.name'() { this.ui.sidebar = false; }, 'route.params'() { this.ui.sidebar = false; } },
    data() { return { collapsed: false, q: '', openMods: {}, boot: (window.__MDLR || {}),
      reEmail: ((((window.__MDLR || {}).user) || {}).email) || '', rePass: '', reErr: '', reBusy: false,
      markSvg: `<svg viewBox="0 0 86 78" width="24" height="22" aria-label="Modular"><defs><linearGradient id="mdlrM" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#221F1A"/><stop offset="1" stop-color="#C0552F"/></linearGradient></defs><path d="M1.44,75.812l8.08,-48.479l7.927,15.092l-5.336,33.386l-10.671,0Zm69.516,-74.852l2.744,16.617l-31.252,59.912l-31.404,-60.065l2.744,-16.464l28.66,55.186l28.508,-55.186Zm4.269,26.069l8.08,48.783l-10.671,0l-5.336,-33.539l7.927,-15.245Z" fill="url(#mdlrM)"/></svg>`,
    }; },
    computed: {
      nav() { return buildNav(); },
      ptName() {
        const t = this.route.params && this.route.params.type;
        const pt = window.__mdlr.data.POST_TYPES.find(p => p.id === t);
        return pt ? pt.name : '';
      },
      notifications() { return window.__mdlr.data.NOTIFICATIONS; },
      unreadCount() { return this.notifications.filter(n => !n.read).length; },
      sysVersion() { return (window.__mdlr.data.SYSTEM || {}).version || '1.0.0'; },
      me() {
        const u = (window.__MDLR && window.__MDLR.user) || {};
        const name = u.name || u.email || 'User';
        const role = u.role || '';
        return { name, email: u.email || '', role, roleLabel: role ? role.charAt(0).toUpperCase() + role.slice(1) : '' };
      },
      initials() {
        const n = (this.me.name || '').trim();
        if (!n) return 'U';
        const parts = n.split(/\s+/);
        return ((parts[0][0] || '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
      },
    },
    template: `
    <div class="shell" :class="{'shell--collapsed': collapsed, 'shell--drawer': ui.sidebar}">
      <!-- mobile drawer scrim -->
      <div class="side-scrim" @click="ui.sidebar=false"></div>
      <!-- SIDEBAR -->
      <aside class="side">
        <div class="side-brand">
          <span class="brand-mark" v-html="markSvg"></span>
          <span class="brand-word" v-show="!collapsed">Modular</span>
          <button class="iconbtn iconbtn--sm side-collapse" @click="collapsed=!collapsed" :title="collapsed?'Expand':'Collapse'">
            <Icon :name="collapsed?'chevRight':'chevLeft'"/>
          </button>
        </div>

        <nav class="side-nav">
          <div v-for="(sec,si) in nav" :key="si" class="side-sec">
            <div v-if="sec.label && !collapsed" class="side-sec-label">{{ sec.label }}</div>
            <div v-else-if="sec.label && collapsed" class="side-sec-rule"></div>
            <template v-for="(it,ii) in sec.items" :key="ii">
              <button class="side-item" :class="{'is-on': isOn(it), 'is-open': isOpen(it)}" @click="goItem(it)" :title="collapsed ? it.label : ''">
                <Icon :name="it.icon" :size="18"/>
                <span v-show="!collapsed">{{ it.label }}</span>
                <Icon v-if="it.children && !collapsed" :name="isOpen(it)?'chevDown':'chevRight'" :size="14" class="side-chev"/>
              </button>
              <div v-if="it.children && isOpen(it) && !collapsed" class="side-sub">
                <button v-for="(c,ci) in it.children" :key="ci" class="side-subitem" :class="{'is-on': c.route ? (route.name==='modulePage' && route.params && route.params.url===c.params.url) : matches(c)}" @click="c.route ? go(c.route, c.params) : (c.href ? goHref(c.href) : go(c.name, c.params))">{{ c.label }}</button>
              </div>
            </template>
          </div>
        </nav>

        <div class="side-foot">
          <div class="side-user" v-show="!collapsed" @click="go('profile')">
            <span class="avatar">{{ initials }}</span>
            <span class="side-user-meta">
              <strong>{{ me.name }}</strong>
              <span class="muted">{{ me.roleLabel }}</span>
            </span>
            <Icon name="chevRight" :size="16" class="muted"/>
          </div>
          <span class="avatar" v-show="collapsed" @click="go('profile')" style="cursor:pointer">{{ initials }}</span>
        </div>
      </aside>

      <!-- MAIN -->
      <div class="main">
        <header class="topbar">
          <button class="iconbtn topbar-burger" @click="ui.sidebar=true" title="Menu"><Icon name="menu" :size="19"/></button>
          <div class="crumbs">
            <Icon name="home" :size="15" class="muted"/>
            <span class="crumb-sep">/</span>
            <span class="crumb">{{ ptName || crumbFor(route.name) }}</span>
            <template v-if="route.name==='editor'"><span class="crumb-sep">/</span><span class="crumb crumb--here">Edit</span></template>
            <template v-if="route.name==='fieldBuilder'"><span class="crumb-sep">/</span><span class="crumb crumb--here">Edit group</span></template>
          </div>
          <div class="topbar-search" @click="ui.cmdk = true">
            <Icon name="search" :size="15"/>
            <span>Search or jump to…</span>
            <span class="kbd">⌘K</span>
          </div>
          <button class="iconbtn topbar-search-m" @click="ui.cmdk = true" title="Search"><Icon name="search" :size="18"/></button>
          <div class="topbar-actions">
            <button class="iconbtn" title="Design system" @click="go('settings',{tab:'design'})">
              <Icon name="sliders" :size="17"/>
            </button>
            <div class="topbar-pop-wrap">
              <button class="iconbtn topbar-bell" :class="{'is-on-accent': ui.notif}" title="Notifications" @click="ui.notif = !ui.notif; ui.acct=false">
                <Icon name="bell" :size="17"/>
                <span class="notif-dot" v-if="unreadCount">{{ unreadCount }}</span>
              </button>
              <div v-if="ui.notif" class="pop-scrim" @click="ui.notif=false"></div>
              <div v-if="ui.notif" class="notif-pop">
                <div class="notif-pop-head"><strong>Notifications</strong><button class="btn btn--text btn--sm" @click="markAllRead">Mark all read</button></div>
                <div class="notif-list">
                  <button v-for="n in notifications" :key="n.id" class="notif-item" :class="{'is-unread':!n.read}" @click="openNotif(n)">
                    <span class="notif-ico"><Icon :name="n.icon" :size="15"/></span>
                    <span class="notif-main"><strong>{{ n.title }}</strong><span class="notif-body">{{ n.body }}</span><span class="t-meta">{{ n.when }}</span></span>
                    <span class="notif-unread" v-if="!n.read"></span>
                  </button>
                </div>
                <button class="notif-pop-foot" @click="go('notifications');ui.notif=false">View all notifications <Icon name="arrowRight" :size="14"/></button>
              </div>
            </div>
            <div class="topbar-pop-wrap">
              <button class="iconbtn" :class="{'is-on-accent': ui.help}" title="Help" @click="ui.help = !ui.help; ui.acct=false; ui.notif=false"><Icon name="helpCircle" :size="17"/></button>
              <div v-if="ui.help" class="pop-scrim" @click="ui.help=false"></div>
              <div v-if="ui.help" class="help-pop">
                <a class="help-feature" href="https://modularcms.org/docs" target="_blank" rel="noopener" @click="ui.help=false">
                  <span class="help-feature-ico"><Icon name="external" :size="16"/></span>
                  <span class="help-feature-main"><strong>Documentation</strong><span class="t-meta">Full guides at modularcms.org/docs</span></span>
                  <Icon name="arrowUpRight" :size="15" class="muted"/>
                </a>
                <button class="acct-item" @click="ui.modeHelp=true; ui.help=false"><Icon name="layers" :size="15"/> Fixed vs Modular</button>
                <button class="acct-item" @click="ui.cmdk=true; ui.help=false"><Icon name="search" :size="15"/> Search &amp; commands <span class="kbd" style="margin-left:auto">⌘K</span></button>
                <button class="acct-item" @click="go('dashboard',{tab:'updates'}); ui.help=false"><Icon name="refresh" :size="15"/> What's new</button>
                <div class="acct-sep"></div>
                <div class="help-foot"><span class="t-meta">Modular v{{ sysVersion }}</span><a class="mode-help-link" href="https://modularcms.org" target="_blank" rel="noopener" style="display:inline-flex">modularcms.org</a></div>
              </div>
            </div>
            <div class="topbar-pop-wrap">
              <button class="topbar-acct" :class="{'is-on':ui.acct}" title="Account" @click="ui.acct = !ui.acct; ui.notif=false">
                <span class="avatar avatar--round" style="width:30px;height:30px;font-size:11px">{{ initials }}</span>
                <Icon name="chevDown" :size="14" class="muted hide-sm"/>
              </button>
              <div v-if="ui.acct" class="pop-scrim" @click="ui.acct=false"></div>
              <div v-if="ui.acct" class="acct-pop">
                <div class="acct-pop-head"><span class="avatar avatar--round" style="width:38px;height:38px;font-size:13px">{{ initials }}</span><div><strong>{{ me.name }}</strong><div class="t-meta">{{ me.email }}</div></div></div>
                <button class="acct-item" @click="go('profile');ui.acct=false"><Icon name="user" :size="15"/> Your profile</button>
                <button class="acct-item" @click="go('settings',{tab:'security'});ui.acct=false"><Icon name="lock" :size="15"/> Security</button>
                <button class="acct-item" @click="go('settings');ui.acct=false"><Icon name="settings" :size="15"/> Settings</button>
                <div class="acct-sep"></div>
                <a class="acct-item" :href="(boot.baseUrl||'') + '/'" target="_blank" rel="noopener"><Icon name="external" :size="15"/> View site</a>
                <a class="acct-item is-danger" :href="(boot.baseUrl||'') + '/admin/auth/logout.php'"><Icon name="logout" :size="15"/> Log out</a>
              </div>
            </div>
          </div>
        </header>

        <main class="canvas" :key="route.name + JSON.stringify(route.params||{})">
          <component :is="screen"/>
        </main>
      </div>

      <!-- COMMAND PALETTE -->
      <UiCmdK v-if="ui.cmdk"/>

      <!-- FIXED vs MODULAR explainer (from help menu) -->
      <UiModal v-if="ui.modeHelp" lg @close="ui.modeHelp=false">
        <div class="drawer-head">
          <span class="mode-ico mode-ico--modular"><Icon name="layers" :size="16"/></span>
          <div style="flex:1"><strong style="font-size:15px">Fixed vs Modular</strong><div class="t-meta">How Modular models content</div></div>
          <button class="iconbtn" @click="ui.modeHelp=false"><Icon name="x"/></button>
        </div>
        <div class="drawer-body"><ModeExplainerBody/></div>
        <div class="drawer-foot">
          <a class="btn btn--ghost" href="https://modularcms.org/docs" target="_blank" rel="noopener"><Icon name="external" :size="15"/> Full docs</a>
          <button class="btn" @click="ui.modeHelp=false">Got it</button>
        </div>
      </UiModal>

      <!-- SESSION EXPIRED — re-login modal (popped on API 401). Not dismissable; -->
      <!-- after login the failed requests retry so the user stays on the same page. -->
      <UiModal v-if="ui.loginModal" @close="()=>{}">
        <div class="drawer-head">
          <span class="mode-ico mode-ico--modular"><Icon name="lock" :size="16"/></span>
          <div style="flex:1"><strong style="font-size:15px">Session expired</strong><div class="t-meta">Log back in to continue where you left off</div></div>
        </div>
        <div class="drawer-body">
          <div class="field"><label class="field-label">Email</label><input class="inp" type="email" v-model="reEmail" autocomplete="username" @keyup.enter="doReauth"></div>
          <div class="field"><label class="field-label">Password</label><input class="inp" type="password" v-model="rePass" autocomplete="current-password" @keyup.enter="doReauth"></div>
          <div v-if="reErr" style="color:var(--danger);font-size:12.5px;margin-top:8px">{{ reErr }}</div>
        </div>
        <div class="drawer-foot">
          <button class="btn" :disabled="reBusy" @click="doReauth">{{ reBusy ? 'Signing in…' : 'Log in' }}</button>
        </div>
      </UiModal>
    </div>`,
    methods: {
      async doReauth() {
        if (this.reBusy) return;
        this.reErr = '';
        if (!this.reEmail || !this.rePass) { this.reErr = 'Enter your email and password.'; return; }
        this.reBusy = true;
        try {
          await window.__mdlr.reauth(this.reEmail, this.rePass);
          this.rePass = '';
        } catch (e) {
          this.reErr = (e && e.message) || 'Login failed';
        } finally {
          this.reBusy = false;
        }
      },
      openNotif(n) { n.read = true; this.ui.notif = false; if (n.go) this.go(n.go[0], n.go[1]); },
      markAllRead() { this.notifications.forEach(n => { n.read = true; }); },
      matches(t) {
        if (t.name !== this.route.name) return false;
        const rp = this.route.params || {}, tp = t.params || {};
        if ('type' in tp) return rp.type === tp.type;
        if ('tax' in tp) return rp.tax === tp.tax;
        if ('tab' in tp) return rp.tab === tp.tab;
        if ('upload' in tp) return !!rp.upload;
        if (t.name === 'media') return !rp.upload;
        if (t.name === 'settings') return !rp.tab;
        if (t.name === 'dashboard') return !rp.tab;
        if (t.name === 'exportImport') return !rp.tab || rp.tab === 'export';
        return true;
      },
      isOn(it) { return !it.children && this.matches(it); },
      isOpen(it) { if (!it.children) return false; if (it._mod) return !!this.openMods[it.label]; return this.matches(it) || it.children.some(c => this.matches(c)); },
      goItem(it) {
        if (it._mod) { if (it.children && it.children.length) { this.openMods[it.label] = !this.openMods[it.label]; } else if (it.route) { this.go(it.route, it.params); } return; }
        if (it.href) { window.location.href = it.href; return; }
        this.go(it.name, it.params);
      },
      goHref(url) { window.location.href = url; },
      crumbFor(n) {
        const map = { dashboard:'Dashboard', postTypes:'Post types', taxonomies:'Taxonomies', components:'Blocks', binding:'Field groups',
          forms:'Forms', actions:'Actions', users:'Users', settings:'Settings', design:'Design system', exportImport:'Export / Import', profile:'Profile', modules:'Modules', roles:'Roles', analytics:'Analytics',
          media:'Media', editor:'Content', fieldBuilder:'Field groups', library:'Field types', notifications:'Notifications' };
        return map[n] || '';
      },
    },
  };
})();
