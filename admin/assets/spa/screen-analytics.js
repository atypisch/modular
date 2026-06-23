/* ============================================================
   FULL ANALYTICS — the dedicated, deep analytics page.
   Opened from Dashboard > Analytics > "Open full analytics".
   All data is fetched from /api/v1/stats/analytics.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const cssVar = (n) => getComputedStyle(document.documentElement).getPropertyValue(n).trim() || '#888';

  C.ScreenAnalytics = {
    name: 'ScreenAnalytics',
    inject: ['go'],
    data() { return { a: null, charts: {}, range: '14d', metric: 'posts', loading: true }; },
    mounted() { this.load(); },
    beforeUnmount() { this.killAll(); },
    watch: {
      metric() { this.$nextTick(() => this.redrawMain()); },
      range() { this.load(); },
    },
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
      byStatus() { return (this.a && this.a.by_status) ? this.a.by_status : []; },
      authors() { return (this.a && this.a.authors) ? this.a.authors : []; },
      topPages() { return (this.a && this.a.top_pages) ? this.a.top_pages : []; },
      formStats() { return (this.a && this.a.form_stats) ? this.a.form_stats : []; },
      recentActivity() { return (this.a && this.a.recent_activity) ? this.a.recent_activity : []; },
      totalPosts() { return this.a ? (this.a.daily_posts || []).reduce((s, v) => s + v, 0) : 0; },
    },
    methods: {
      async load() {
        this.loading = true;
        const data = await window.__mdlr.store.loadAnalytics(this.rangeDays);
        if (data) { this.a = data; this.$nextTick(() => this.draw()); }
        this.loading = false;
      },
      killAll() { Object.keys(this.charts).forEach(k => this.kill(k)); },
      kill(name) {
        const c = this.charts[name];
        if (c) { try { c.destroy(); } catch (e) {} this.charts[name] = null; }
      },
      mk(name, ref, config) {
        const canvas = this.$refs[ref];
        if (!window.Chart || !canvas) return;
        this.kill(name);
        const existing = (Chart.getChart && Chart.getChart(canvas)) || null;
        if (existing) { try { existing.destroy(); } catch (e) {} }
        this.charts[name] = new Chart(canvas, config);
      },
      redrawMain() {
        if (!window.Chart || !this.$refs.line || !this.a) return;
        const accent = cssVar('--accent'), line = cssVar('--line');
        const labels = this.a.days || [];
        const series = this.metric === 'submissions' ? (this.a.daily_submissions || [])
                     : this.metric === 'media' ? (this.a.daily_media || [])
                     : (this.a.daily_posts || []);
        this.mk('main', 'line', {
          type: 'line',
          data: { labels, datasets: [{ data: series, borderColor: accent, backgroundColor: 'rgba(192,85,47,0.10)', fill: true, tension: 0.35, pointRadius: 0, borderWidth: 2 }] },
          options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 14 } }, y: { grid: { color: line }, border: { display: false }, beginAtZero: true } } },
        });
      },
      draw() {
        if (!window.Chart || !this.a) return;
        this.killAll();
        Chart.defaults.font.family = "'Hanken Grotesk', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = cssVar('--ink-3');
        const accent = cssVar('--accent'), line = cssVar('--line'), surface = cssVar('--surface');
        const palette = [accent, '#3E7C78', '#B0893F', '#5B7089', '#876089', '#6B4C6E'];
        this.redrawMain();
        // content by type doughnut
        if (this.$refs.bytype && this.a.by_type) {
          this.mk('bytype', 'bytype', {
            type: 'doughnut',
            data: { labels: this.a.by_type.labels, datasets: [{ data: this.a.by_type.data, backgroundColor: palette, borderWidth: 2, borderColor: surface }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'right', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } } } },
          });
        }
        // media by type doughnut
        if (this.$refs.mediatype && this.a.media_by_type && this.a.media_by_type.labels.length) {
          this.mk('mediatype', 'mediatype', {
            type: 'doughnut',
            data: { labels: this.a.media_by_type.labels, datasets: [{ data: this.a.media_by_type.data, backgroundColor: [accent, '#5B7089', '#B0893F', '#3E7C78', '#876089', '#6B4C6E'], borderWidth: 2, borderColor: surface }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'right', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } } } },
          });
        }
        // monthly bar chart
        if (this.$refs.monthly && this.a.monthly && this.a.monthly.labels.length) {
          this.mk('monthly', 'monthly', {
            type: 'bar',
            data: { labels: this.a.monthly.labels, datasets: [{ data: this.a.monthly.data, backgroundColor: accent, borderRadius: 5, maxBarThickness: 30 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { color: line }, border: { display: false }, beginAtZero: true } } },
          });
        }
        // users by role bar
        if (this.$refs.userroles && this.a.users_by_role && this.a.users_by_role.labels.length) {
          this.mk('userroles', 'userroles', {
            type: 'bar',
            data: { labels: this.a.users_by_role.labels, datasets: [{ data: this.a.users_by_role.data, backgroundColor: '#5B7089', borderRadius: 4, maxBarThickness: 18 }] },
            options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: line }, border: { display: false }, beginAtZero: true }, y: { grid: { display: false } } } },
          });
        }
      },
      statusClass(s) { return s === 'published' ? 'badge--ok' : s === 'scheduled' ? 'badge--info' : s === 'draft' ? '' : 'badge--warn'; },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px"><a class="mode-help-link" style="display:inline-flex" @click.prevent="go('dashboard',{tab:'analytics'})"><Icon name="chevLeft" :size="13"/> Dashboard \u00b7 Analytics</a></div>
          <h1 class="t-title t-h1">Analytics</h1>
          <p class="page-sub" style="margin-top:6px">Content production, form activity &amp; system overview.</p>
        </div>
        <div class="flex gap-2 items-center">
          <div class="seg"><button :class="{'is-on':range==='14d'}" @click="range='14d'">14 days</button><button :class="{'is-on':range==='30d'}" @click="range='30d'">30 days</button><button :class="{'is-on':range==='90d'}" @click="range='90d'">90 days</button></div>
        </div>
      </div>

      <div v-if="loading" class="empty" style="padding:64px"><div>Loading analytics\u2026</div></div>
      <template v-else-if="a">

        <!-- KPI cards -->
        <div class="an-kpis an-kpis--3" style="margin-top:8px">
          <div class="card card-pad" v-for="k in kpis" :key="k.label">
            <div class="t-meta">{{ k.label }}</div>
            <div class="stat-val t-display" style="font-size:24px">{{ k.value }}</div>
            <div class="an-delta" :class="k.up?'is-up':'is-down'"><Icon :name="k.up?'arrowUpRight':'arrowRight'" :size="13"/> {{ k.delta }}</div>
          </div>
        </div>

        <!-- main chart with metric switch -->
        <section class="card card-pad" style="margin-top:16px">
          <div class="panel-head" style="padding:0 0 14px">
            <h2 class="t-title t-h3" style="margin:0">Activity over time</h2>
            <div class="seg"><button :class="{'is-on':metric==='posts'}" @click="metric='posts'">New content</button><button :class="{'is-on':metric==='submissions'}" @click="metric='submissions'">Submissions</button><button :class="{'is-on':metric==='media'}" @click="metric='media'">Media</button></div>
          </div>
          <div class="chart-box" style="height:300px"><canvas ref="line"></canvas></div>
        </section>

        <!-- content by status strip -->
        <div class="an-eng" v-if="byStatus.length">
          <div class="an-eng-cell" v-for="s in byStatus" :key="s.status">
            <div class="t-meta">{{ s.status }}</div>
            <strong>{{ s.cnt }}</strong>
          </div>
        </div>

        <!-- breakdown grid: type + media type -->
        <div class="an-grid" style="margin-top:16px">
          <section class="card card-pad">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Content by type</h2></div>
            <div class="chart-box"><canvas ref="bytype"></canvas></div>
          </section>
          <section class="card card-pad">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Media by type</h2></div>
            <div class="chart-box"><canvas ref="mediatype"></canvas></div>
          </section>
          <section class="card card-pad an-wide">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Monthly production</h2><span class="t-meta">last 12 months</span></div>
            <div class="chart-box" style="height:220px"><canvas ref="monthly"></canvas></div>
          </section>
        </div>

        <!-- tables: authors + forms -->
        <div class="an-grid" style="margin-top:16px">
          <section class="card" v-if="authors.length">
            <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Authors</h2></div>
            <div class="an-bars">
              <div class="an-bar" v-for="a in authors" :key="a.name">
                <span class="an-bar-label">{{ a.name }}</span>
                <span class="an-bar-track"><span class="an-bar-fill" :style="{width: Math.min(100, Math.round(a.count / (authors[0].count || 1) * 100))+'%'}"></span></span>
                <span class="an-bar-val mono">{{ a.count }}</span>
              </div>
            </div>
          </section>
          <section class="card" v-if="formStats.length">
            <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Form submissions</h2></div>
            <table class="tbl">
              <thead><tr><th>Form</th><th style="width:100px">Submissions</th></tr></thead>
              <tbody>
                <tr v-for="f in formStats" :key="f.slug"><td><strong>{{ f.name }}</strong></td><td class="mono">{{ f.submissions }}</td></tr>
              </tbody>
            </table>
          </section>
        </div>

        <!-- users + recent activity -->
        <div class="an-grid" style="margin-top:16px">
          <section class="card card-pad">
            <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Users by role</h2></div>
            <div class="chart-box" style="height:180px"><canvas ref="userroles"></canvas></div>
          </section>
          <section class="card" v-if="recentActivity.length">
            <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Recent activity</h2></div>
            <div class="an-feed">
              <div class="an-feed-row" v-for="(l,i) in recentActivity" :key="i">
                <span class="an-feed-flag"><Icon name="zap" :size="13"/></span>
                <div class="an-feed-main"><span class="t-key">{{ l.description }}</span><span class="t-meta">{{ l.user_name || 'System' }}</span></div>
                <span class="t-meta">{{ l.created_at }}</span>
              </div>
            </div>
          </section>
          <section class="card" v-else>
            <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Recent activity</h2></div>
            <div class="empty" style="padding:36px"><div>No activity recorded yet.</div></div>
          </section>
        </div>

        <!-- recent content -->
        <div class="an-grid" style="margin-top:16px">
          <section class="card an-wide" v-if="topPages.length">
            <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Recent content</h2></div>
            <table class="tbl">
              <thead><tr><th>Title</th><th style="width:300px">Path</th></tr></thead>
              <tbody>
                <tr v-for="p in topPages" :key="p.path"><td><strong>{{ p.title }}</strong></td><td><span class="t-key">{{ p.path }}</span></td></tr>
              </tbody>
            </table>
          </section>
        </div>

      </template>
      <div v-else class="empty" style="padding:64px"><Icon name="sigma" :size="26"/><div>No analytics data available.</div></div>
    </div>`,
  };
})();
