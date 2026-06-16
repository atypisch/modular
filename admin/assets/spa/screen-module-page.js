/* Module admin page host — renders a module's PHP admin page (module-page.php)
   inside the SPA via an embed-mode iframe, so the new sidebar/topbar surround
   it and the module page picks up the Atelier design tokens. */
(function () {
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const C = M.comps;

  C.ScreenModulePage = {
    name: 'ScreenModulePage',
    inject: ['route', 'go'],
    computed: {
      src() {
        const u = (this.route.params || {}).url || '';
        if (!u) return '';
        return u + (u.indexOf('?') >= 0 ? '&' : '?') + 'embed=1';
      },
      title() { return (this.route.params || {}).title || 'Module'; },
    },
    template: `
    <div class="page page--wide fade-up mod-host">
      <div class="page-head" style="margin-bottom:14px">
        <div><div class="t-eyebrow" style="margin-bottom:8px">Module</div><h1 class="t-title t-h1">{{ title }}</h1></div>
        <a class="btn btn--ghost btn--sm" :href="src" target="_blank" rel="noopener"><Icon name="external" :size="15"/> Open full page</a>
      </div>
      <iframe v-if="src" :src="src" class="mod-frame" title="Module page"></iframe>
      <div v-else class="empty" style="padding:64px"><Icon name="box" :size="28"/><div>No module page.</div></div>
    </div>`,
  };
})();
