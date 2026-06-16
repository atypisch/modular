/* ============================================================
   FULL ANALYTICS — the dedicated, deep analytics page.
   Opened from Dashboard › Analytics › "Open full analytics".
   More charts + breakdowns: traffic, real-time, devices,
   hourly, sources, countries, referrers, events, top pages.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;
  const cssVar = (n) => getComputedStyle(document.documentElement).getPropertyValue(n).trim() || '#888';

  C.ScreenAnalytics = {
    name: 'ScreenAnalytics',
    inject: ['go'],
    data() { return { a: D().ANALYTICS, charts: {}, range: '14d', metric: 'visitors' }; },
    mounted() { this.$nextTick(() => this.draw()); },
    beforeUnmount() { this.killAll(); },
    watch: {
      // metric & range BOTH only change the main line chart — never the
      // static secondary charts. Redraw just that one, guarded, so there's
      // no canvas-reuse race no matter how fast you toggle.
      metric() { this.$nextTick(() => this.redrawMain()); },
      range() { this.$nextTick(() => this.redrawMain()); },
    },
    computed: {
      flagBars() { return this.a.countries; },
      rangeDays() { return this.range === '90d' ? 90 : this.range === '30d' ? 30 : 14; },
      series() {
        const n = this.rangeDays;
        const out = { labels: [], visitors: [], pageviews: [], avg: [] };
        const today = new Date('2026-06-03');
        for (let i = n - 1; i >= 0; i--) {
          const d = new Date(today); d.setDate(d.getDate() - i);
          const idx = n - 1 - i;
          const dow = d.getDay();
          const weekend = (dow === 0 || dow === 6) ? 0.62 : 1;
          const trend = 1 + (idx / n) * 0.45;
          const wobble = 1 + 0.12 * Math.sin(idx * 1.3) + 0.06 * Math.cos(idx * 0.7);
          const v = Math.round(900 * weekend * trend * wobble);
          out.labels.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
          out.visitors.push(v);
          // pages/visit is HIGHER at weekends + own wobble → genuinely different shape
          const ppv = 2.4 + (weekend < 1 ? 1.3 : 0) + 0.5 * Math.sin(idx * 0.9);
          out.pageviews.push(Math.round(v * ppv));
          // avg session seconds: own curve, dips midweek, peaks weekend, no upward trend
          out.avg.push(Math.round(150 + 70 * (weekend < 1 ? 1 : 0) + 30 * Math.sin(idx * 0.5)));
        }
        return out;
      },
      kpis() {
        const vis = this.series.visitors.reduce((a, b) => a + b, 0);
        const pv = this.series.pageviews.reduce((a, b) => a + b, 0);
        const avg = Math.round(this.series.avg.reduce((a, b) => a + b, 0) / this.series.avg.length);
        const fmt = (x) => x >= 1000 ? (x / 1000).toFixed(1) + 'k' : String(x);
        const mmss = (s) => Math.floor(s / 60) + 'm ' + String(s % 60).padStart(2, '0') + 's';
        return [
          { label: 'Visitors', value: fmt(vis), delta: '+12%', up: true },
          { label: 'Pageviews', value: fmt(pv), delta: '+8%', up: true },
          { label: 'Avg. time', value: mmss(avg), delta: '+5%', up: true },
          { label: 'Bounce rate', value: (38 - (this.rangeDays === 90 ? 3 : this.rangeDays === 30 ? 1 : 0)) + '%', delta: '-3%', up: true },
        ];
      },
    },
    methods: {
      destroyAll() { this.killAll(); },
      killAll() { Object.keys(this.charts).forEach(k => this.kill(k)); },
      // destroy by name AND defensively clear any chart still bound to the canvas
      kill(name) {
        const c = this.charts[name];
        if (c) { try { c.destroy(); } catch (e) {} this.charts[name] = null; }
      },
      // guarded chart factory — never leaves a canvas double-bound
      mk(name, ref, config) {
        const canvas = this.$refs[ref];
        if (!window.Chart || !canvas) return;
        this.kill(name);
        const existing = (Chart.getChart && Chart.getChart(canvas)) || null;
        if (existing) { try { existing.destroy(); } catch (e) {} }
        this.charts[name] = new Chart(canvas, config);
      },
      kindClass(k) { return k === 'Search' ? 'badge--ok' : k === 'Social' ? 'badge--info' : ''; },
      redrawMain() {
        if (!window.Chart || !this.$refs.line) return;
        const accent = cssVar('--accent'), line = cssVar('--line');
        const s = this.series;
        const series = this.metric === 'pageviews' ? s.pageviews : this.metric === 'avg' ? s.avg : s.visitors;
        const everyN = this.rangeDays > 40 ? Math.ceil(this.rangeDays / 14) : 1;
        this.mk('main', 'line', {
          type: 'line',
          data: { labels: s.labels, datasets: [{ data: series, borderColor: accent, backgroundColor: 'rgba(192,85,47,0.10)', fill: true, tension: 0.35, pointRadius: 0, borderWidth: 2 }] },
          options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 14, callback: function(val, i) { return i % everyN === 0 ? this.getLabelForValue(val) : ''; } } }, y: { grid: { color: line }, border: { display: false }, beginAtZero: true } } },
        });
      },
      draw() {
        if (!window.Chart) return;
        this.killAll();
        Chart.defaults.font.family = "'Hanken Grotesk', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = cssVar('--ink-3');
        const accent = cssVar('--accent'), line = cssVar('--line'), surface = cssVar('--surface');
        const palette = [accent, '#3E7C78', '#B0893F', '#5B7089', '#876089'];
        const base = { responsive: true, maintainAspectRatio: false };
        this.redrawMain();
        // realtime sparkline
        this.mk('spark', 'spark', {
          type: 'line',
          data: { labels: this.a.realtime.spark.map((_, i) => i), datasets: [{ data: this.a.realtime.spark, borderColor: accent, backgroundColor: 'rgba(192,85,47,0.12)', fill: true, tension: 0.4, pointRadius: 0, borderWidth: 1.5 }] },
          options: { ...base, plugins: { legend: { display: false }, tooltip: { enabled: false } }, scales: { x: { display: false }, y: { display: false } } },
        });
        // hourly bars
        this.mk('hourly', 'hourly', {
          type: 'bar',
          data: { labels: this.a.hourly.map((_, i) => i), datasets: [{ data: this.a.hourly, backgroundColor: accent, borderRadius: 3, maxBarThickness: 14 }] },
          options: { ...base, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { callback: (v) => v % 6 === 0 ? v + ':00' : '' } }, y: { grid: { color: line }, border: { display: false }, beginAtZero: true } } },
        });
        // sources doughnut
        this.mk('sources', 'sources', {
          type: 'doughnut',
          data: { labels: this.a.sources.labels, datasets: [{ data: this.a.sources.data, backgroundColor: palette, borderWidth: 2, borderColor: surface }] },
          options: { ...base, cutout: '60%', plugins: { legend: { position: 'right', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } } } },
        });
        // devices doughnut
        this.mk('devices', 'devices', {
          type: 'doughnut',
          data: { labels: this.a.devices.labels, datasets: [{ data: this.a.devices.data, backgroundColor: [accent, '#5B7089', '#B0893F'], borderWidth: 2, borderColor: surface }] },
          options: { ...base, cutout: '60%', plugins: { legend: { position: 'right', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } } } },
        });
        // weekday bars
        this.mk('weekday', 'weekday', {
          type: 'bar',
          data: { labels: this.a.weekday.labels, datasets: [{ data: this.a.weekday.data, backgroundColor: accent, borderRadius: 5, maxBarThickness: 30 }] },
          options: { ...base, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { color: line }, border: { display: false }, beginAtZero: true } } },
        });
        // browsers horizontal bars
        this.mk('browsers', 'browsers', {
          type: 'bar',
          data: { labels: this.a.browsers.labels, datasets: [{ data: this.a.browsers.data, backgroundColor: accent, borderRadius: 4, maxBarThickness: 18 }] },
          options: { ...base, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: line }, border: { display: false }, beginAtZero: true }, y: { grid: { display: false } } } },
        });
        // OS horizontal bars
        this.mk('os', 'os', {
          type: 'bar',
          data: { labels: this.a.os.labels, datasets: [{ data: this.a.os.data, backgroundColor: '#5B7089', borderRadius: 4, maxBarThickness: 18 }] },
          options: { ...base, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: line }, border: { display: false }, beginAtZero: true }, y: { grid: { display: false } } } },
        });
      },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px"><a class="mode-help-link" style="display:inline-flex" @click.prevent="go('dashboard',{tab:'analytics'})"><Icon name="chevLeft" :size="13"/> Dashboard · Analytics</a></div>
          <h1 class="t-title t-h1">Analytics</h1>
          <p class="page-sub" style="margin-top:6px">Full traffic &amp; engagement breakdown for atypisch.nl.</p>
        </div>
        <div class="flex gap-2 items-center">
          <div class="seg"><button :class="{'is-on':range==='14d'}" @click="range='14d'">14 days</button><button :class="{'is-on':range==='30d'}" @click="range='30d'">30 days</button><button :class="{'is-on':range==='90d'}" @click="range='90d'">90 days</button></div>
          <button class="btn btn--ghost btn--sm"><Icon name="files" :size="14"/> Export</button>
        </div>
      </div>

      <!-- realtime + kpis -->
      <div class="an-toprow">
        <div class="card card-pad an-realtime">
          <div class="flex items-center gap-2"><span class="an-live"></span><span class="t-meta">Active right now</span></div>
          <div class="stat-val t-display" style="font-size:34px;margin:2px 0 0">{{ a.realtime.active }}</div>
          <div class="an-spark"><canvas ref="spark"></canvas></div>
        </div>
        <div class="an-kpis an-kpis--3">
          <div class="card card-pad" v-for="k in kpis" :key="k.label">
            <div class="t-meta">{{ k.label }}</div>
            <div class="stat-val t-display" style="font-size:24px">{{ k.value }}</div>
            <div class="an-delta" :class="k.up?'is-up':'is-down'"><Icon :name="k.up?'arrowUpRight':'arrowRight'" :size="13"/> {{ k.delta }}</div>
          </div>
        </div>
      </div>

      <!-- main traffic chart with metric switch -->
      <section class="card card-pad" style="margin-top:16px">
        <div class="panel-head" style="padding:0 0 14px">
          <h2 class="t-title t-h3" style="margin:0">Traffic over time</h2>
          <div class="seg"><button :class="{'is-on':metric==='visitors'}" @click="metric='visitors'">Visitors</button><button :class="{'is-on':metric==='pageviews'}" @click="metric='pageviews'">Pageviews</button><button :class="{'is-on':metric==='avg'}" @click="metric='avg'">Avg. session</button></div>
        </div>
        <div class="chart-box" style="height:300px"><canvas ref="line"></canvas></div>
      </section>

      <!-- engagement strip -->
      <div class="an-eng">
        <div class="an-eng-cell" v-for="e in a.engagement" :key="e.label">
          <div class="t-meta">{{ e.label }}</div>
          <strong>{{ e.value }}</strong>
          <span class="an-delta" :class="e.up?'is-up':'is-down'"><Icon :name="e.up?'arrowUpRight':'arrowRight'" :size="12"/> {{ e.delta }}</span>
        </div>
      </div>

      <!-- breakdown grid -->
      <div class="an-grid" style="margin-top:16px">
        <section class="card card-pad">
          <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Sources</h2></div>
          <div class="chart-box"><canvas ref="sources"></canvas></div>
        </section>
        <section class="card card-pad">
          <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Devices</h2></div>
          <div class="chart-box"><canvas ref="devices"></canvas></div>
        </section>
        <section class="card card-pad an-wide">
          <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Visitors by hour</h2><span class="t-meta">local time</span></div>
          <div class="chart-box" style="height:220px"><canvas ref="hourly"></canvas></div>
        </section>
      </div>

      <!-- tables row -->
      <div class="an-grid" style="margin-top:16px">
        <section class="card">
          <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Top countries</h2></div>
          <div class="an-bars">
            <div class="an-bar" v-for="c in flagBars" :key="c.code">
              <span class="an-bar-label">{{ c.name }}</span>
              <span class="an-bar-track"><span class="an-bar-fill" :style="{width: c.pct+'%'}"></span></span>
              <span class="an-bar-val mono">{{ c.visitors.toLocaleString() }}</span>
            </div>
          </div>
        </section>
        <section class="card">
          <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Top referrers</h2></div>
          <table class="tbl">
            <thead><tr><th>Source</th><th style="width:90px">Type</th><th style="width:100px">Visitors</th></tr></thead>
            <tbody>
              <tr v-for="r in a.referrers" :key="r.name"><td><span class="t-key">{{ r.name }}</span></td><td><span class="badge" :class="kindClass(r.kind)">{{ r.kind }}</span></td><td class="mono">{{ r.visitors.toLocaleString() }}</td></tr>
            </tbody>
          </table>
        </section>
      </div>

      <!-- conversion funnel + goals -->
      <div class="an-grid" style="margin-top:16px">
        <section class="card card-pad">
          <div class="panel-head" style="padding:0 0 14px"><h2 class="t-title t-h3" style="margin:0">Conversion funnel</h2><span class="t-meta">visitor → submitted</span></div>
          <div class="an-funnel">
            <div class="an-funnel-step" v-for="(f,i) in a.funnel" :key="f.step">
              <div class="an-funnel-bar" :style="{width: Math.max(f.pct,3)+'%'}"><span>{{ f.count.toLocaleString() }}</span></div>
              <div class="an-funnel-meta"><strong>{{ f.step }}</strong><span class="t-meta">{{ f.pct }}%<template v-if="i>0"> · {{ Math.round(f.count/a.funnel[i-1].count*100) }}% of prev</template></span></div>
            </div>
          </div>
        </section>
        <section class="card card-pad">
          <div class="panel-head" style="padding:0 0 14px"><h2 class="t-title t-h3" style="margin:0">Goals this period</h2><button class="btn btn--text btn--sm">Manage <Icon name="arrowRight" :size="13"/></button></div>
          <div class="an-goals">
            <div class="an-goal" v-for="g in a.goals" :key="g.name">
              <div class="an-goal-top"><strong>{{ g.name }}</strong><span class="mono" :class="g.value>=g.target?'an-goal-hit':''">{{ g.value }} / {{ g.target }}</span></div>
              <div class="an-goal-track"><span class="an-goal-fill" :class="{'is-hit': g.value>=g.target}" :style="{width: Math.min(100, Math.round(g.value/g.target*100))+'%'}"></span></div>
            </div>
          </div>
        </section>
      </div>

      <!-- browsers / os / weekday -->
      <div class="an-grid an-grid--3" style="margin-top:16px">
        <section class="card card-pad">
          <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Browsers</h2></div>
          <div class="chart-box" style="height:180px"><canvas ref="browsers"></canvas></div>
        </section>
        <section class="card card-pad">
          <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">Operating system</h2></div>
          <div class="chart-box" style="height:180px"><canvas ref="os"></canvas></div>
        </section>
        <section class="card card-pad">
          <div class="panel-head" style="padding:0 0 12px"><h2 class="t-title t-h3" style="margin:0">By weekday</h2></div>
          <div class="chart-box" style="height:180px"><canvas ref="weekday"></canvas></div>
        </section>
      </div>

      <!-- entry / exit / live -->
      <div class="an-grid an-grid--3" style="margin-top:16px">
        <section class="card">
          <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Entry pages</h2></div>
          <div class="an-bars an-bars--sm">
            <div class="an-bar" v-for="p in a.entryPages" :key="p.path"><span class="an-bar-label t-key">{{ p.path }}</span><span class="an-bar-track"><span class="an-bar-fill" :style="{width:(p.pct*2.5)+'%'}"></span></span><span class="an-bar-val mono">{{ p.pct }}%</span></div>
          </div>
        </section>
        <section class="card">
          <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Exit pages</h2></div>
          <div class="an-bars an-bars--sm">
            <div class="an-bar" v-for="p in a.exitPages" :key="p.path"><span class="an-bar-label t-key">{{ p.path }}</span><span class="an-bar-track"><span class="an-bar-fill an-bar-fill--alt" :style="{width:(p.pct*2.5)+'%'}"></span></span><span class="an-bar-val mono">{{ p.pct }}%</span></div>
          </div>
        </section>
        <section class="card">
          <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Live <span class="an-live" style="margin-left:4px"></span></h2><span class="t-meta">{{ a.realtime.active }} active</span></div>
          <div class="an-feed">
            <div class="an-feed-row" v-for="(l,i) in a.liveFeed" :key="i">
              <span class="an-feed-flag">{{ l.country }}</span>
              <div class="an-feed-main"><span class="t-key">{{ l.page }}</span><span class="t-meta">{{ l.city }}</span></div>
              <span class="t-meta">{{ l.ago }}</span>
            </div>
          </div>
        </section>
      </div>

      <!-- on-site search + tracked events + top pages -->
      <div class="an-grid an-grid--3" style="margin-top:16px">
        <section class="card">
          <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">On-site search</h2></div>
          <table class="tbl">
            <thead><tr><th>Query</th><th style="width:70px">Count</th><th style="width:80px"></th></tr></thead>
            <tbody>
              <tr v-for="s in a.searchQueries" :key="s.q"><td>{{ s.q }}</td><td class="mono">{{ s.count }}</td><td><span class="badge" :class="s.results?'':'badge--warn'">{{ s.results?'ok':'0 results' }}</span></td></tr>
            </tbody>
          </table>
        </section>
        <section class="card">
          <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Tracked events</h2><button class="btn btn--text btn--sm">Configure <Icon name="arrowRight" :size="13"/></button></div>
          <table class="tbl">
            <thead><tr><th>Event</th><th style="width:70px">Count</th><th style="width:80px">Change</th></tr></thead>
            <tbody>
              <tr v-for="e in a.events" :key="e.name"><td><strong>{{ e.name }}</strong></td><td class="mono">{{ e.count.toLocaleString() }}</td><td><span class="an-delta" :class="e.change.startsWith('-')?'is-down':'is-up'">{{ e.change }}</span></td></tr>
            </tbody>
          </table>
        </section>
        <section class="card">
          <div class="panel-head"><h2 class="t-title t-h3" style="margin:0">Top pages</h2><button class="btn btn--text btn--sm">Export <Icon name="arrowRight" :size="13"/></button></div>
          <table class="tbl">
            <thead><tr><th>Page</th><th style="width:70px">Views</th><th style="width:70px">Δ</th></tr></thead>
            <tbody>
              <tr v-for="p in a.topPages" :key="p.path"><td><span class="t-key">{{ p.path }}</span></td><td class="mono">{{ p.views.toLocaleString() }}</td><td><span class="an-delta" :class="p.change.startsWith('-')?'is-down':'is-up'">{{ p.change }}</span></td></tr>
            </tbody>
          </table>
        </section>
      </div>
    </div>`,
  };
})();
