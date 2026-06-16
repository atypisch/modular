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
    data() { return { a: D().ANALYTICS, charts: [], range: '14d' }; },
    mounted() { this.$nextTick(() => this.draw()); },
    beforeUnmount() { this.charts.forEach(c => c && c.destroy()); },
    watch: { range() { this.$nextTick(() => this.drawLine()); } },
    computed: {
      rangeDays() { return this.range === '90d' ? 90 : this.range === '30d' ? 30 : 14; },
      lineSeries() {
        const n = this.rangeDays, out = { labels: [], visitors: [], pageviews: [] };
        const today = new Date('2026-06-03');
        for (let i = n - 1; i >= 0; i--) {
          const d = new Date(today); d.setDate(d.getDate() - i);
          const idx = n - 1 - i, dow = d.getDay();
          const weekend = (dow === 0 || dow === 6) ? 0.62 : 1;
          const trend = 1 + (idx / n) * 0.45;
          const wobble = 1 + 0.12 * Math.sin(idx * 1.3) + 0.06 * Math.cos(idx * 0.7);
          const v = Math.round(900 * weekend * trend * wobble);
          out.labels.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
          out.visitors.push(v);
          out.pageviews.push(Math.round(v * (2.4 + (weekend < 1 ? 1.3 : 0) + 0.5 * Math.sin(idx * 0.9))));
        }
        return out;
      },
      kpis() {
        const vis = this.lineSeries.visitors.reduce((a, b) => a + b, 0);
        const pv = this.lineSeries.pageviews.reduce((a, b) => a + b, 0);
        const fmt = (x) => x >= 1000 ? (x / 1000).toFixed(1) + 'k' : String(x);
        return [
          { label:'Visitors', value:fmt(vis), delta:'+12%', up:true },
          { label:'Pageviews', value:fmt(pv), delta:'+8%', up:true },
          { label:'Avg. time', value:'2m 41s', delta:'+5%', up:true },
          { label:'Bounce rate', value:(38 - (this.rangeDays===90?3:this.rangeDays===30?1:0)) + '%', delta:'-3%', up:true },
        ];
      },
    },
    methods: {
      drawLine() {
        if (!window.Chart || !this.$refs.line) return;
        if (this.charts[0]) this.charts[0].destroy();
        const accent = cssVar('--accent'), ink3 = cssVar('--ink-3'), line = cssVar('--line');
        const s = this.lineSeries;
        const everyN = this.rangeDays > 40 ? Math.ceil(this.rangeDays / 14) : 1;
        this.charts[0] = new Chart(this.$refs.line, {
          type:'line',
          data:{ labels:s.labels, datasets:[
            { label:'Visitors', data:s.visitors, borderColor:accent, backgroundColor:'rgba(192,85,47,0.10)', fill:true, tension:0.35, pointRadius:0, borderWidth:2 },
            { label:'Pageviews', data:s.pageviews, borderColor:ink3, backgroundColor:'transparent', fill:false, tension:0.35, pointRadius:0, borderWidth:1.5, borderDash:[4,4] },
          ]},
          options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:true, position:'bottom', labels:{ boxWidth:10, boxHeight:10, usePointStyle:true } } }, scales:{ x:{ grid:{ display:false }, ticks:{ autoSkip:true, maxTicksLimit:14, callback:function(val,i){ return i % everyN === 0 ? this.getLabelForValue(val) : ''; } } }, y:{ grid:{ color:line }, border:{ display:false }, beginAtZero:true } } },
        });
      },
      draw() {
        if (!window.Chart) return;
        const accent = cssVar('--accent'), ink3 = cssVar('--ink-3'), line = cssVar('--line');
        Chart.defaults.font.family = "'Hanken Grotesk', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = ink3;
        const a = this.a;
        const ctxStyle = { responsive:true, maintainAspectRatio:false };
        this.charts[0] = null; this.drawLine();
        // sources doughnut
        const palette = [accent, '#3E7C78', '#B0893F', '#5B7089', '#876089'];
        this.charts.push(new Chart(this.$refs.sources, {
          type:'doughnut',
          data:{ labels:a.sources.labels, datasets:[{ data:a.sources.data, backgroundColor:palette, borderWidth:2, borderColor:cssVar('--surface') }] },
          options:{ ...ctxStyle, cutout:'62%', plugins:{ legend:{ position:'right', labels:{ boxWidth:10, boxHeight:10, usePointStyle:true } } } },
        }));
        // content by type bar
        this.charts.push(new Chart(this.$refs.bytype, {
          type:'bar',
          data:{ labels:a.byType.labels, datasets:[{ data:a.byType.data, backgroundColor:accent, borderRadius:6, maxBarThickness:38 }] },
          options:{ ...ctxStyle, plugins:{ legend:{ display:false } }, scales:{ x:{ grid:{ display:false } }, y:{ grid:{ color:line }, border:{ display:false }, beginAtZero:true } } },
        }));
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

      <div class="an-kpis">
        <div class="card card-pad" v-for="k in kpis" :key="k.label">
          <div class="t-meta">{{ k.label }}</div>
          <div class="stat-val t-display" style="font-size:26px">{{ k.value }}</div>
          <div class="an-delta" :class="k.up?'is-up':'is-down'"><Icon :name="k.up?'arrowUpRight':'arrowRight'" :size="13"/> {{ k.delta }}</div>
        </div>
      </div>

      <div class="an-grid">
        <section class="card card-pad an-wide">
          <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Traffic</h2><span class="t-meta">visitors · pageviews</span></div>
          <div class="chart-box"><canvas ref="line"></canvas></div>
        </section>
        <section class="card card-pad">
          <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Sources</h2></div>
          <div class="chart-box"><canvas ref="sources"></canvas></div>
        </section>
        <section class="card card-pad">
          <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Content by type</h2></div>
          <div class="chart-box"><canvas ref="bytype"></canvas></div>
        </section>
        <section class="card an-wide">
          <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Top pages</h2><button class="btn btn--text btn--sm">Export <Icon name="arrowRight" :size="13"/></button></div>
          <table class="tbl">
            <thead><tr><th>Page</th><th style="width:120px">Views</th><th style="width:100px">Change</th></tr></thead>
            <tbody>
              <tr v-for="p in a.topPages" :key="p.path"><td><span class="t-key">{{ p.path }}</span></td><td class="mono">{{ p.views.toLocaleString() }}</td><td><span class="an-delta" :class="p.change.startsWith('-')?'is-down':'is-up'">{{ p.change }}</span></td></tr>
            </tbody>
          </table>
        </section>
      </div>
    </div>`,
  };

  /* ---------- REPORTS ---------- */
  C.DashReports = {
    name: 'DashReports',
    data() { return { selId: 'production', range: '30d', rows: D().REPORT_ROWS,
      kpiMap: {
        production: [['Published', '23'], ['Avg / week', '5.8'], ['Drafts pending', '7'], ['Median time to publish', '3.2d']],
        cadence: [['Longest streak', '11d'], ['Current gap', '2d'], ['Most active type', 'Posts'], ['Quietest type', 'Products']],
        forms: [['Submissions', '804'], ['Conversion', '3.1%'], ['Top form', 'Newsletter'], ['Spam blocked', '142']],
        authors: [['Top author', 'marten'], ['Items', '186'], ['Avg views', '1.9k'], ['Contributors', '4']],
        search: [['Searches', '3.4k'], ['Zero-result', '11%'], ['Top query', '“hosting”'], ['CTR', '54%']],
        health: [['Stale (>1y)', '18'], ['Missing SEO', '7'], ['Broken links', '3'], ['Orphan pages', '5']],
      } }; },
    computed: {
      reports() { return D().REPORTS; },
      sel() { return this.reports.find(r => r.id === this.selId); },
      kpis() { return this.kpiMap[this.selId] || []; },
    },
    template: `
    <div class="rep-layout">
      <aside class="rep-list">
        <button v-for="r in reports" :key="r.id" class="rep-item" :class="{'is-on': selId===r.id}" @click="selId=r.id">
          <span class="rep-item-ico"><Icon :name="r.icon" :size="16"/></span>
          <span class="rep-item-main"><strong>{{ r.name }}</strong><span class="t-meta">{{ r.desc }}</span></span>
        </button>
      </aside>

      <section class="rep-detail">
        <div class="rep-head">
          <div><h2 class="t-title t-h2" style="margin:0">{{ sel.name }}</h2><p class="page-sub" style="margin-top:4px">{{ sel.desc }}</p></div>
          <div class="flex gap-2 wrap">
            <div class="seg"><button :class="{'is-on':range==='7d'}" @click="range='7d'">7d</button><button :class="{'is-on':range==='30d'}" @click="range='30d'">30d</button><button :class="{'is-on':range==='90d'}" @click="range='90d'">90d</button><button :class="{'is-on':range==='custom'}" @click="range='custom'">Custom</button></div>
          </div>
        </div>

        <div class="rep-kpis">
          <div class="rep-kpi" v-for="k in kpis" :key="k[0]"><div class="t-meta">{{ k[0] }}</div><strong>{{ k[1] }}</strong></div>
        </div>

        <div class="card">
          <div class="panel-head"><h3 class="t-title t-h3" style="margin:0">Breakdown</h3><span class="t-meta">{{ range==='custom' ? 'custom range' : 'last '+range }}</span></div>
          <table class="tbl">
            <thead><tr><th>Item</th><th style="width:110px">Type</th><th style="width:110px">Author</th><th style="width:110px">Published</th><th style="width:100px">Views</th><th style="width:90px">Conv.</th></tr></thead>
            <tbody>
              <tr v-for="r in rows" :key="r.name"><td><strong>{{ r.name }}</strong></td><td><span class="badge">{{ r.type }}</span></td><td class="muted">{{ r.author }}</td><td class="muted">{{ r.published }}</td><td class="mono">{{ r.views.toLocaleString() }}</td><td class="mono">{{ r.conv }}</td></tr>
            </tbody>
          </table>
        </div>

        <div class="rep-actions">
          <button class="btn btn--ghost btn--sm"><Icon name="files" :size="14"/> Export CSV</button>
          <button class="btn btn--ghost btn--sm"><Icon name="file" :size="14"/> Export PDF</button>
          <button class="btn btn--ghost btn--sm"><Icon name="clock" :size="14"/> Schedule email</button>
          <div class="grow"></div>
          <button class="btn btn--soft btn--sm"><Icon name="save" :size="14"/> Save this view</button>
        </div>
      </section>
    </div>`,
  };
})();
