/* Dashboard screen (Fase 1). Overview tab is ported 1:1 from the Atelier
   prototype (screens-basic.js · ScreenDashboard). The Updates/Analytics/Reports
   tabs are stubbed here and land in Fase 11 (analytics/insights). */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  const tabStub = (label) => ({
    name: 'DashStub' + label,
    template: `<div class="empty" style="padding:48px"><Icon name="box" :size="26"/><div>` + label + ` — coming in a later phase.</div></div>`,
  });
  C.DashUpdates  = C.DashUpdates  || tabStub('Updates');
  C.DashAnalytics = C.DashAnalytics || tabStub('Analytics');
  C.DashReports  = C.DashReports  || tabStub('Reports');

  C.ScreenDashboard = {
    name: 'ScreenDashboard',
    inject: ['go', 'route'],
    computed: {
      tab() { return (this.route.params || {}).tab || 'overview'; },
      pts() { return D().POST_TYPES; },
      recent() { return D().POSTS.slice(0, 5); },
      updateCount() { return D().MODULES.filter(m => m.update).length + (D().SYSTEM.version !== D().SYSTEM.latest ? 1 : 0); },
      headTitle() {
        if (this.tab === 'overview') {
          const h = new Date().getHours();
          const greeting = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
          const profile = D().PROFILE;
          const name = profile && profile.name ? profile.name.split(' ')[0] : '';
          return name ? greeting + ', ' + name : greeting;
        }
        return { updates:'Updates', analytics:'Analytics', reports:'Reports' }[this.tab];
      },
      headSub() {
        if (this.tab === 'overview') {
          const s = D().STATS_OVERVIEW;
          if (s) {
            const parts = [];
            if (s.drafts > 0) parts.push(s.drafts + ' draft' + (s.drafts !== 1 ? 's' : '') + ' waiting');
            if (s.submissions > 0) parts.push(s.submissions + ' form submission' + (s.submissions !== 1 ? 's' : ''));
            return parts.length ? parts.join(' · ') + '.' : 'Everything is up to date.';
          }
          return 'Loading statistics\u2026';
        }
        return {
          updates:'Keep the CMS and its modules current.',
          analytics:'Content production and activity at a glance.',
          reports:'Build, export and schedule reports across your content.',
        }[this.tab];
      },
      stats() {
        const s = D().STATS_OVERVIEW;
        if (!s) return [
          { label: 'Content items', value: '\u2026', sub: '', icon: 'files' },
          { label: 'Published', value: '\u2026', sub: '', icon: 'check' },
          { label: 'Components', value: '\u2026', sub: '', icon: 'layers' },
          { label: 'Media', value: '\u2026', sub: '', icon: 'image' },
        ];
        return [
          { label: 'Content items', value: String(s.content_items), sub: s.post_types + ' post type' + (s.post_types !== 1 ? 's' : ''), icon: 'files' },
          { label: 'Published', value: String(s.published), sub: s.drafts + ' draft' + (s.drafts !== 1 ? 's' : ''), icon: 'check' },
          { label: 'Components', value: String(s.components), sub: 'reused ' + s.component_usage + '\u00d7', icon: 'layers' },
          { label: 'Media', value: String(s.media), sub: s.users + ' user' + (s.users !== 1 ? 's' : ''), icon: 'image' },
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
          <div class="t-eyebrow" style="margin-bottom:8px">{{ new Date().toLocaleDateString('en-US', { weekday:'long', day:'numeric', month:'long' }) }}</div>
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
})();
