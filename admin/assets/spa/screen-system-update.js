/* ============================================================
   SYSTEM UPDATE — one central upgrade point for the Modular core.
   Shows the installed version, checks the GitHub release (the
   deployable SSOT), and runs the update with one button. Backed by
   GET/POST /api/v1/system/update (UpdateController + Modular\Update).
   ============================================================ */
(function () {
  const M = window.__mdlr = window.__mdlr || { comps: {} };
  const C = M.comps;

  C.ScreenSystemUpdate = {
    name: 'ScreenSystemUpdate',
    data() { return { loading: true, checking: false, updating: false, error: '', done: '', status: null }; },
    mounted() { this.load(); },
    computed: {
      current() { return (this.status && this.status.current) || {}; },
      latest() { return this.status && this.status.latest; },
      available() { return !!(this.status && this.status.update_available); },
      history() { return (this.status && this.status.history) || []; },
    },
    methods: {
      async load() {
        this.loading = true; this.error = '';
        try { this.status = await M.api.get('/system/update'); }
        catch (e) { this.error = (e && e.data && e.data.error) || 'Could not load update status.'; }
        this.loading = false;
      },
      async check() {
        this.checking = true; this.done = '';
        await this.load();
        this.checking = false;
      },
      async runUpdate() {
        if (this.updating) return;
        if (!window.confirm('Update the Modular core to v' + (this.latest && this.latest.version) + '? A backup is taken first; the app will reload when done.')) return;
        this.updating = true; this.error = ''; this.done = '';
        try {
          const r = await M.api.post('/system/update', {});
          this.done = 'Updated to v' + (r.version_to || (this.latest && this.latest.version)) + '. Reloading…';
          setTimeout(() => window.location.reload(), 1400);
        } catch (e) {
          this.error = (e && e.data && e.data.error) || 'Update failed. Check the server logs.';
          this.updating = false;
        }
      },
      fmt(d) { return d ? String(d).slice(0, 16).replace('T', ' ') : ''; },
    },
    template: `
    <div class="page page--wide fade-up" style="max-width:860px">
      <div class="page-head">
        <div>
          <div class="t-eyebrow" style="margin-bottom:8px">System</div>
          <h1 class="t-title t-h1">System update</h1>
          <p class="page-sub" style="margin-top:6px">One central upgrade point. The GitHub release is the source of truth; update the whole core from here.</p>
        </div>
        <button class="btn btn--ghost" :disabled="checking||loading" @click="check"><Icon name="refresh" :size="15"/> {{ checking ? 'Checking…' : 'Check for updates' }}</button>
      </div>

      <div v-if="loading" class="card card-pad t-meta">Loading update status…</div>

      <template v-else>
        <!-- installed version + status -->
        <div class="card card-pad" style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">
          <div style="flex:1;min-width:200px">
            <div class="t-meta">Installed version</div>
            <div style="font-family:var(--font-display);font-weight:600;font-size:30px;letter-spacing:-0.02em;line-height:1.1">v{{ current.version || '—' }}</div>
            <div class="t-meta" style="margin-top:4px">{{ current.name || 'Modular CMS' }}<span v-if="current.build_date"> · built {{ current.build_date }}</span><span v-if="status.repo"> · {{ status.repo }}</span></div>
          </div>
          <div>
            <span v-if="available" class="badge badge--warn" style="font-size:13px"><Icon name="zap" :size="13"/> Update available</span>
            <span v-else-if="status.check_error" class="badge" style="font-size:13px"><Icon name="info" :size="13"/> Couldn’t check</span>
            <span v-else class="badge badge--ok" style="font-size:13px"><Icon name="check" :size="13"/> Up to date</span>
          </div>
        </div>

        <div v-if="error" class="card card-pad" style="margin-top:14px;border-color:var(--danger);color:var(--danger)"><Icon name="info" :size="15"/> {{ error }}</div>
        <div v-if="done" class="card card-pad" style="margin-top:14px;border-color:var(--ok);color:var(--ok)"><Icon name="check" :size="15"/> {{ done }}</div>

        <!-- update-available panel -->
        <div v-if="available && latest" class="card card-pad" style="margin-top:14px">
          <div class="flex items-center justify-between gap-3" style="flex-wrap:wrap">
            <div>
              <strong class="t-title t-h2" style="display:block">v{{ latest.version }} <span class="t-meta" style="font-weight:400">{{ latest.name }}</span></strong>
              <div class="t-meta" style="margin-top:4px" v-if="latest.published_at">Released {{ fmt(latest.published_at) }}</div>
            </div>
            <button class="btn btn--accent" :disabled="updating" @click="runUpdate"><Icon name="upload" :size="15"/> {{ updating ? 'Updating…' : 'Update to v' + latest.version }}</button>
          </div>
          <div v-if="latest.body" class="rel-notes" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--line);white-space:pre-wrap;font-size:13px;color:var(--ink-2);max-height:260px;overflow:auto">{{ latest.body }}</div>
          <div v-if="latest.html_url" style="margin-top:10px"><a :href="latest.html_url" target="_blank" rel="noopener" class="mode-help-link" style="font-size:13px">View release on GitHub →</a></div>
        </div>

        <!-- check error detail -->
        <div v-if="status.check_error" class="card card-pad" style="margin-top:14px">
          <strong>Update check unavailable</strong>
          <p class="page-sub" style="margin:6px 0 0">{{ status.check_error }}</p>
          <p class="t-meta" style="margin-top:8px">Repo: <span class="mono">{{ status.repo || '(not configured)' }}</span> · mode: <span class="mono">{{ status.mode }}</span></p>
        </div>

        <!-- history -->
        <div class="card" style="margin-top:14px" v-if="history.length">
          <div class="list-toolbar"><strong>Update history</strong></div>
          <table class="tbl tbl--rows">
            <thead><tr><th>Change</th><th style="width:120px">Status</th><th style="width:160px">Started</th><th style="width:160px">Completed</th></tr></thead>
            <tbody>
              <tr v-for="h in history" :key="h.id">
                <td><span class="mono">v{{ h.version_from }}</span> → <span class="mono">v{{ h.version_to }}</span></td>
                <td><span class="badge" :class="h.status==='success'?'badge--ok':(h.status==='failed'?'':'')">{{ h.status }}</span></td>
                <td class="t-meta">{{ fmt(h.started_at) }}</td>
                <td class="t-meta">{{ fmt(h.completed_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>`,
  };
})();
