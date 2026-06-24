/* ============================================================
   MODULAR · Atelier admin SPA — boot.
   - Shared API client (same-origin session + CSRF) on window.__mdlr.api.
   - Reactive hash routing, provide/inject (route/go/tweaks/ui).
   - Mounts <AppShell> with the active screen; unbuilt screens fall back
     to ScreenStub so per-phase delivery degrades gracefully.
   Ported from the prototype app.js; data is mock until each phase wires
   the matching API endpoint.
   ============================================================ */
(function () {
  const { createApp, reactive, watch } = Vue;
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const C = M.comps;
  const BOOT = window.__MDLR || {};

  /* ---- Shared API client: same-origin, cookie session + CSRF for writes ---- */
  const api = {
    base: (BOOT.apiBase || '/api/v1').replace(/\/$/, ''),
    async request(method, path, body) {
      const headers = { 'Accept': 'application/json' };
      const opts = { method, headers, credentials: 'same-origin' };
      if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
        headers['X-CSRF-TOKEN'] = BOOT.csrf || '';
        opts.body = JSON.stringify(body);
      }
      const res = await fetch(this.base + path, opts);
      const text = await res.text();
      let data = null;
      try { data = text ? JSON.parse(text) : null; } catch (e) { data = { raw: text }; }
      if (!res.ok) throw Object.assign(new Error('API ' + res.status), { status: res.status, data });
      return data;
    },
    get(p) { return this.request('GET', p); },
    post(p, b) { return this.request('POST', p, b || {}); },
    put(p, b) { return this.request('PUT', p, b || {}); },
    del(p) { return this.request('DELETE', p); },
    async upload(path, formData) {
      const res = await fetch(this.base + path, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': BOOT.csrf || '' },
        body: formData,
      });
      const text = await res.text();
      let data = null; try { data = text ? JSON.parse(text) : null; } catch (e) { data = { raw: text }; }
      if (!res.ok) throw Object.assign(new Error('API ' + res.status), { status: res.status, data });
      return data;
    },
  };
  M.api = api;
  M.boot = BOOT;

  const SCREEN = {
    dashboard: 'ScreenDashboard',
    content: 'ScreenContentList',
    editor: 'ScreenContentEditor',
    postTypes: 'ScreenPostTypes',
    taxonomies: 'ScreenTaxonomies',
    fieldBuilder: 'ScreenFieldBuilder',
    components: 'ScreenComponents',
    binding: 'ScreenBinding',
    media: 'ScreenMedia',
    forms: 'ScreenForms',
    actions: 'ScreenActions',
    users: 'ScreenUsers',
    settings: 'ScreenSettings',
    design: 'ScreenDesignSystem',
    exportImport: 'ScreenExportImport',
    profile: 'ScreenProfile',
    modules: 'ScreenModules',
    roles: 'ScreenRoles',
    analytics: 'ScreenAnalytics',
    notifications: 'ScreenNotifications',
    library: 'ScreenLibrary',
    fieldDemo: 'ScreenFieldDemo',
    modulePage: 'ScreenModulePage',
    systemUpdate: 'ScreenSystemUpdate',
  };

  C.ScreenStub = {
    name: 'ScreenStub',
    inject: ['route'],
    template: `<div class="page"><div class="empty" style="padding:64px"><Icon name="box" :size="28"/><div>Screen “{{ route.name }}” komt in een latere fase.</div></div></div>`,
  };

  const route = reactive({ name: 'dashboard', params: {} });
  const tweaks = reactive({ provStyle: 'chip', open: false });
  const ui = reactive({ focus: false, cmdk: false, sidebar: false, notif: false, acct: false, help: false, modeHelp: false });

  let popping = false;
  // Build the address-bar URL for a route. The post editor gets a real
  // ?postid=<id> (or the temporary ?postid=new while unsaved) so a refresh /
  // reload lands back on the same post instead of an empty editor.
  function urlFor(name, params) {
    params = params || {};
    const path = location.pathname || '/';
    if (name === 'editor' && params.postId != null && params.postId !== '') {
      const q = new URLSearchParams();
      q.set('postid', params.postId);
      if (params.type) q.set('type', params.type);
      return path + '?' + q.toString() + '#editor';
    }
    if (name === 'fieldBuilder' && params.type) {
      return path + '?type=' + encodeURIComponent(params.type) + '#fieldBuilder';
    }
    // Drop stale ?postid= / ?type= when leaving editor / field builder.
    return path + '#' + name;
  }
  function go(name, params) {
    route.name = name;
    route.params = params || {};
    const main = document.querySelector('.canvas');
    if (main) main.scrollTop = 0;
    if (!popping) {
      const plain = { name: name, params: JSON.parse(JSON.stringify(params || {})) };
      try { history.pushState(plain, '', urlFor(name, params)); } catch (e) {}
    }
  }

  window.addEventListener('popstate', (e) => {
    const s = e.state;
    popping = true;
    if (s && s.name) { route.name = s.name; route.params = s.params || {}; }
    else { route.name = 'dashboard'; route.params = {}; }
    const main = document.querySelector('.canvas');
    if (main) main.scrollTop = 0;
    popping = false;
  });

  // initial route from hash (so deep-links / refresh land on the right screen)
  const hashRoute = (location.hash || '').replace(/^#/, '');
  try {
    const qs = new URLSearchParams(location.search);
    const postId = qs.get('postid') || qs.get('edit');
    if (hashRoute && SCREEN[hashRoute]) {
      route.name = hashRoute;
      route.params = {};
      if (hashRoute === 'fieldBuilder' && qs.get('type')) route.params.type = qs.get('type');
      else if (hashRoute === 'editor' && postId) {
        route.params = { postId: postId };
        const type = qs.get('type'); if (type) route.params.type = type;
      }
    } else if (postId) {
      route.name = 'editor';
      const params = { postId: postId };
      const type = qs.get('type'); if (type) params.type = type;
      route.params = params;
    } else if (hashRoute && SCREEN[hashRoute]) {
      route.name = hashRoute;
    }
  } catch (e) {}

  const app = createApp({
    provide() { return { route, go, tweaks, ui, api }; },
    data() { return { route, tweaks }; },
    computed: {
      screenComp() {
        const want = SCREEN[this.route.name];
        return (want && C[want]) ? want : 'ScreenStub';
      },
    },
    methods: { openCmdK() { ui.cmdk = true; } },
    template: `<AppShell :screen="screenComp"/>`,
  });

  app.component('Icon', M.Icon);
  Object.keys(C).forEach(name => app.component(name, C[name]));

  watch(() => tweaks.provStyle, (p) => { document.documentElement.setAttribute('data-prov', p); }, { immediate: true });
  app.config.errorHandler = (err, vm, info) => { console.error('[Vue error]', err, info); };

  M.app = app;
  M.go = go;
  try { history.replaceState({ name: route.name, params: JSON.parse(JSON.stringify(route.params || {})) }, '', urlFor(route.name, route.params)); } catch (e) {}
  window.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); ui.cmdk = !ui.cmdk; }
  });
  app.mount('#app');
  if (M.theme) M.theme.apply();
  // Hydrate post types / field groups / components from the API (dogfood).
  if (M.store && M.store.hydrate) M.store.hydrate();
  // Theme (design system): load persisted tokens, and persist every edit back.
  if (M.theme && M.store) {
    const _origSave = M.theme.save.bind(M.theme);
    M.theme.save = function () { _origSave(); M.store.saveTheme(); };
    M.store.loadTheme();
  }
})();
