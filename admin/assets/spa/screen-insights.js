/* ============================================================
   DASHBOARD INSIGHTS — Updates, Analytics (Chart.js), Reports.
   Rendered inside ScreenDashboard's tabs.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const cssVar = (n) => getComputedStyle(document.documentElement).getPropertyValue(n).trim() || '#888';

  /* ---------- UPDATES ---------- */
  C.DashUpdates = {
    name: 'DashUpdates',
    data() { return { sys: D().SYSTEM, busy: false }; },
    computed: {
      modUpdates() { return D().MODULES.filter(m => m.update); },
      cmsCurrent() { return this.sys.version !== this.sys.latest; },
    },
    methods: {
      updateCms() { this.sys.version = this.sys.latest; },
      updateMod(m) { m.version = m.update; m.update = null; },
      updateAll() { this.modUpdates.slice().forEach(m => this.updateMod(m)); if (this.cmsCurrent) this.updateCms(); },
    },
    template: `
    <div>
      <!-- CMS core -->
      <div class="card card-pad upd-core" :class="{'is-ok': !cmsCurrent}">
        <div class="upd-core-l">
          <span class="upd-badge" :class="cmsCurrent?'is-due':'is-ok'"><Icon :name="cmsCurrent?'refresh':'check'" :size="20"/></span>
          <div>
            <div class="flex items-center gap-2"><strong style="font-size:16px">Modular core</strong><span class="t-key">v{{ sys.version }}</span><span class="badge badge--info" v-if="cmsCurrent">v{{ sys.latest }} available</span><span class="badge badge--ok" v-else><span class="badge--dot"></span>up to date</span></div>
            <div class="t-meta" style="margin-top:4px">{{ sys.channel }} channel · PHP {{ sys.php }} · {{ sys.db }} · checked {{ sys.lastChecked.toLowerCase() }}</div>
          </div>
        </div>
        <div class="flex gap-2">
          <button class="btn btn--ghost btn--sm"><Icon name="refresh" :size="14"/> Check again</button>
          <button class="btn btn--accent" v-if="cmsCurrent" @click="updateCms"><Icon name="upload" :size="15"/> Update to {{ sys.latest }}</button>
        </div>
      </div>

      <!-- module updates -->
      <div class="flex items-center justify-between" style="margin:24px 0 12px">
        <h2 class="t-title t-h3" style="margin:0">Module updates <span class="t-meta" style="font-weight:400">({{ modUpdates.length }})</span></h2>
        <button class="btn btn--ghost btn--sm" v-if="modUpdates.length || cmsCurrent" @click="updateAll"><Icon name="upload" :size="14"/> Update everything</button>
      </div>
      <div class="card" v-if="modUpdates.length">
        <div class="upd-row" v-for="m in modUpdates" :key="m.id">
          <span class="mod-ico"><Icon :name="m.icon" :size="18"/></span>
          <div class="mod-main">
            <strong>{{ m.name }}</strong>
            <div class="t-meta">{{ m.desc }}</div>
          </div>
          <span class="upd-ver"><span class="t-key">v{{ m.version }}</span><Icon name="arrowRight" :size="13" class="muted"/><span class="t-key" style="color:var(--accent-deep)">v{{ m.update }}</span></span>
          <button class="btn btn--soft btn--sm" @click="updateMod(m)"><Icon name="upload" :size="14"/> Update</button>
        </div>
      </div>
      <div class="empty card" v-else style="padding:36px"><Icon name="check"/><div>All modules are up to date.</div></div>

      <!-- changelog -->
      <h2 class="t-title t-h3" style="margin:28px 0 12px">Recent releases</h2>
      <div class="card">
        <div class="upd-log" v-for="c in sys.changelog" :key="c.v">
          <div class="upd-log-v"><span class="badge badge--info">v{{ c.v }}</span><span class="t-meta">{{ c.date }}</span></div>
          <p>{{ c.notes }}</p>
        </div>
      </div>
    </div>`,
  };

  /* ---------- ANALYTICS ---------- */
  C.DashAnalytics = {
    name: 'DashAnalytics',
    inject: ['go'],
    data() { return { a: null, charts: [], range: '14d', loading: true }; },
    mounted() { this.load(); },
    beforeUnmount() { this.charts.forEach(c => c && c.destroy()); },
    watch: { range() { this.load(); } },
    computed: {
      rangeDays() { return this.range === '90d' ? 90 : this.range === '30d' ? 30 : 14; },
      kpis() {
        if (!this.a || !this.a.kpis) return [];
        return this.a.kpis.map(k => ({
          label: k.label,
          value: k.value >= 1000 ? (k.value / 1000).toFixed(1) + 'k' : String(k.value),
          delta: (k.delta >= 0 ? '+' : '') + k.delta + '%',
          up: k.up,
        }));
      },
    },
    methods: {
      async load() {
        this.loading = true;
        const data = await window.__mdlr.store.loadAnalytics(this.rangeDays);
        if (data) { this.a = data; this.$nextTick(() => this.draw()); }
        this.loading = false;
      },
      drawLine() {
        if (!window.Chart || !this.$refs.line || !this.a) return;
        if (this.charts[0]) this.charts[0].destroy();
        const accent = cssVar('--accent'), ink3 = cssVar('--ink-3'), line = cssVar('--line');
        const labels = this.a.days || [];
        const posts = this.a.daily_posts || [];
        const subs = this.a.daily_submissions || [];
        this.charts[0] = new Chart(this.$refs.line, {
          type:'line',
          data:{ labels, datasets:[
            { label:'New content', data:posts, borderColor:accent, backgroundColor:'rgba(192,85,47,0.10)', fill:true, tension:0.35, pointRadius:0, borderWidth:2 },
            { label:'Submissions', data:subs, borderColor:ink3, backgroundColor:'transparent', fill:false, tension:0.35, pointRadius:0, borderWidth:1.5, borderDash:[4,4] },
          ]},
          options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:true, position:'bottom', labels:{ boxWidth:10, boxHeight:10, usePointStyle:true } } }, scales:{ x:{ grid:{ display:false }, ticks:{ autoSkip:true, maxTicksLimit:14 } }, y:{ grid:{ color:line }, border:{ display:false }, beginAtZero:true } } },
        });
      },
      draw() {
        if (!window.Chart || !this.a) return;
        this.charts.forEach(c => c && c.destroy());
        this.charts = [];
        const accent = cssVar('--accent'), ink3 = cssVar('--ink-3'), line = cssVar('--line');
        Chart.defaults.font.family = "'Hanken Grotesk', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = ink3;
        const ctxStyle = { responsive:true, maintainAspectRatio:false };
        this.charts[0] = null; this.drawLine();
        // users by role doughnut
        const palette = [accent, '#3E7C78', '#B0893F', '#5B7089', '#876089'];
        if (this.$refs.roles && this.a.users_by_role) {
          this.charts.push(new Chart(this.$refs.roles, {
            type:'doughnut',
            data:{ labels:this.a.users_by_role.labels, datasets:[{ data:this.a.users_by_role.data, backgroundColor:palette, borderWidth:2, borderColor:cssVar('--surface') }] },
            options:{ ...ctxStyle, cutout:'62%', plugins:{ legend:{ position:'right', labels:{ boxWidth:10, boxHeight:10, usePointStyle:true } } } },
          }));
        }
        // content by type bar
        if (this.$refs.bytype && this.a.by_type) {
          this.charts.push(new Chart(this.$refs.bytype, {
            type:'bar',
            data:{ labels:this.a.by_type.labels, datasets:[{ data:this.a.by_type.data, backgroundColor:accent, borderRadius:6, maxBarThickness:38 }] },
            options:{ ...ctxStyle, plugins:{ legend:{ display:false } }, scales:{ x:{ grid:{ display:false } }, y:{ grid:{ color:line }, border:{ display:false }, beginAtZero:true } } },
          }));
        }
      },
    },
    template: `
    <div>
      <div class="flex items-center gap-3" style="margin-bottom:16px">
        <span class="t-meta">Range</span>
        <div class="seg"><button :class="{'is-on':range==='14d'}" @click="range='14d'">14 days</button><button :class="{'is-on':range==='30d'}" @click="range='30d'">30 days</button><button :class="{'is-on':range==='90d'}" @click="range='90d'">90 days</button></div>
        <div class="grow"></div>
        <button class="btn btn--ghost btn--sm" @click="go('analytics',{})"><Icon name="external" :size="14"/> Open full analytics</button>
      </div>

      <div v-if="loading" class="empty" style="padding:48px"><div>Loading analytics\u2026</div></div>
      <template v-else-if="a">
        <div class="an-kpis">
          <div class="card card-pad" v-for="k in kpis" :key="k.label">
            <div class="t-meta">{{ k.label }}</div>
            <div class="stat-val t-display" style="font-size:26px">{{ k.value }}</div>
            <div class="an-delta" :class="k.up?'is-up':'is-down'"><Icon :name="k.up?'arrowUpRight':'arrowRight'" :size="13"/> {{ k.delta }}</div>
          </div>
        </div>

        <div class="an-grid">
          <section class="card card-pad an-wide">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Activity</h2><span class="t-meta">new content \u00b7 form submissions</span></div>
            <div class="chart-box"><canvas ref="line"></canvas></div>
          </section>
          <section class="card card-pad">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Users by role</h2></div>
            <div class="chart-box"><canvas ref="roles"></canvas></div>
          </section>
          <section class="card card-pad">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Content by type</h2></div>
            <div class="chart-box"><canvas ref="bytype"></canvas></div>
          </section>
          <section class="card an-wide">
            <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Recent content</h2></div>
            <table class="tbl">
              <thead><tr><th>Page</th><th style="width:200px">Path</th></tr></thead>
              <tbody>
                <tr v-for="p in a.top_pages" :key="p.path"><td><strong>{{ p.title }}</strong></td><td><span class="t-key">{{ p.path }}</span></td></tr>
              </tbody>
            </table>
          </section>
        </div>
      </template>
      <div v-else class="empty" style="padding:48px"><Icon name="sigma" :size="26"/><div>No analytics data available yet.</div></div>
    </div>`,
  };

  /* ---------- REPORTS ---------- */
  C.DashReports = {
    name: 'DashReports',
    data() { return { selId: 'production', range: '30d', reportData: null, loading: true }; },
    computed: {
      reports() { return D().REPORTS; },
      sel() { return this.reports.find(r => r.id === this.selId); },
      kpis() { return (this.reportData && this.reportData.kpis) ? this.reportData.kpis : []; },
      rows() { return (this.reportData && this.reportData.rows) ? this.reportData.rows : []; },
      rangeDays() { return this.range === '90d' ? 90 : this.range === '7d' ? 7 : 30; },
    },
    mounted() { this.load(); },
    watch: {
      selId() { this.load(); },
      range() { this.load(); },
    },
    methods: {
      async load() {
        this.loading = true;
        const data = await window.__mdlr.store.loadReports(this.selId, this.rangeDays);
        if (data) this.reportData = data;
        this.loading = false;
      },
    },
    template: `
    <div class=”rep-layout”>
      <aside class=”rep-list”>
        <button v-for=”r in reports” :key=”r.id” class=”rep-item” :class=”{'is-on': selId===r.id}” @click=”selId=r.id”>
          <span class=”rep-item-ico”><Icon :name=”r.icon” :size=”16”/></span>
          <span class=”rep-item-main”><strong>{{ r.name }}</strong><span class=”t-meta”>{{ r.desc }}</span></span>
        </button>
      </aside>

      <section class=”rep-detail”>
        <div class=”rep-head”>
          <div><h2 class=”t-title t-h2” style=”margin:0”>{{ sel.name }}</h2><p class=”page-sub” style=”margin-top:4px”>{{ sel.desc }}</p></div>
          <div class=”flex gap-2 wrap”>
            <div class=”seg”><button :class=”{'is-on':range==='7d'}” @click=”range='7d'”>7d</button><button :class=”{'is-on':range==='30d'}” @click=”range='30d'”>30d</button><button :class=”{'is-on':range==='90d'}” @click=”range='90d'”>90d</button></div>
          </div>
        </div>

        <div v-if=”loading” class=”empty” style=”padding:36px”><div>Loading report\u2026</div></div>
        <template v-else>
          <div class=”rep-kpis”>
            <div class=”rep-kpi” v-for=”k in kpis” :key=”k[0]”><div class=”t-meta”>{{ k[0] }}</div><strong>{{ k[1] }}</strong></div>
          </div>

          <div class=”card” v-if=”rows.length”>
            <div class=”panel-head”><h3 class=”t-title t-h3” style=”margin:0”>Breakdown</h3><span class=”t-meta”>last {{ range }}</span></div>
            <table class=”tbl”>
              <thead><tr><th>Item</th><th style=”width:110px”>Type</th><th style=”width:110px”>Author</th><th style=”width:110px”>Published</th><th style=”width:100px”>Status</th></tr></thead>
              <tbody>
                <tr v-for=”r in rows” :key=”r.name”><td><strong>{{ r.name }}</strong></td><td><span class=”badge”>{{ r.type }}</span></td><td class=”muted”>{{ r.author }}</td><td class=”muted”>{{ r.published }}</td><td class=”muted”>{{ r.status }}</td></tr>
              </tbody>
            </table>
          </div>
          <div v-else class=”empty card” style=”padding:36px”><Icon name=”table” :size=”22”/><div>No data for this report in the selected period.</div></div>

          <div class=”rep-actions”>
            <button class=”btn btn--ghost btn--sm”><Icon name=”files” :size=”14”/> Export CSV</button>
            <button class=”btn btn--ghost btn--sm”><Icon name=”file” :size=”14”/> Export PDF</button>
            <div class=”grow”></div>
          </div>
        </template>
      </section>
    </div>`,
  };
})();
