/* ============================================================
   EXPORT / IMPORT — move content between Modular installs.
   Tabs: Export · Import · History (driven by route.params.tab).
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.ScreenExportImport = {
    name: 'ScreenExportImport',
    inject: ['go', 'route'],
    data() {
      return {
        includeMedia: false,
        importMode: 'skip',
        dryRun: false,
        fileName: '',
        file: null,
        running: false,
        result: null,
        error: '',
        sourceFilter: 'all',
        exportProfile: 'full',
        exportPostType: '',
      };
    },
    computed: {
      tab() { return (this.route.params || {}).tab || 'export'; },
      tables() { return D().EXPORT_TABLES; },
      history() { return D().IMPORT_HISTORY; },
      postTypes() { return D().POST_TYPES || []; },
      effectivePostType() {
        const pts = this.postTypes;
        if (!pts.length) return '';
        if (this.exportPostType && pts.some(p => p.id === this.exportPostType)) return this.exportPostType;
        return pts[0].id;
      },
      selectedPostTypeLabel() {
        const pt = this.postTypes.find(p => p.id === this.effectivePostType);
        return pt ? pt.name : this.effectivePostType;
      },
      exportScopeLabel() {
        if (this.exportProfile === 'post_type' && this.effectivePostType) {
          return 'post type ' + this.effectivePostType + (this.selectedPostTypeLabel ? ' (' + this.selectedPostTypeLabel + ')' : '');
        }
        return 'all registered tables';
      },
      sources() { return ['all', ...new Set(this.tables.map(t => t.source))]; },
      shownTables() { return this.sourceFilter === 'all' ? this.tables : this.tables.filter(t => t.source === this.sourceFilter); },
      totalRows() { return this.tables.reduce((n, t) => n + t.rows, 0).toLocaleString(); },
      resultRows() {
        if (!this.result || !this.result.summary) return [];
        return Object.keys(this.result.summary)
          .filter(k => k.charAt(0) !== '_')
          .map(k => Object.assign({ table: k }, this.result.summary[k]));
      },
      resultErrors() { return (this.result && this.result.summary && this.result.summary._errors) || []; },
    },
    methods: {
      sourceClass(s) { return s === 'core' ? '' : 'badge--info'; },
      onFile(e) {
        this.file = (e.target.files && e.target.files[0]) || null;
        this.fileName = this.file ? this.file.name : '';
        this.result = null; this.error = '';
      },
      downloadBundle() {
        const base = (window.__mdlr.api && window.__mdlr.api.base) || '/api/v1';
        const q = new URLSearchParams();
        if (this.includeMedia) q.set('include_media', '1');
        if (this.exportProfile === 'post_type' && this.effectivePostType) {
          q.set('profile', 'post_type');
          q.set('post_type', this.effectivePostType);
        }
        const qs = q.toString();
        window.location.href = base + '/export' + (qs ? '?' + qs : '');
      },
      async runImport() {
        if (!this.file || this.running) return;
        this.running = true; this.result = null; this.error = '';
        try {
          const fd = new FormData();
          fd.append('bundle', this.file);
          fd.append('mode', this.dryRun ? 'dryrun' : this.importMode);
          fd.append('duplicate_policy', this.importMode === 'overwrite' ? 'overwrite' : 'skip');
          this.result = await window.__mdlr.api.upload('/import', fd);
        } catch (err) {
          this.error = (err && err.data && err.data.error) ? err.data.error : (err.message || 'Import failed');
        } finally {
          this.running = false;
        }
      },
    },
    template: `
    <div class="page page--wide fade-up">
      <div class="page-head">
        <div>
          <h1 class="t-title t-h1">Export / Import</h1>
          <p class="page-sub" style="margin-top:6px">Move content between Modular installs. Bundles are portable <span class="t-key">.zip</span> archives — schema, content and (optionally) media in one file.</p>
        </div>
      </div>

      <div class="tabs" style="margin-bottom:22px">
        <button :class="{'is-on':tab==='export'}" @click="go('exportImport',{tab:'export'})"><Icon name="upload" :size="15"/> Export</button>
        <button :class="{'is-on':tab==='import'}" @click="go('exportImport',{tab:'import'})"><Icon name="folder" :size="15"/> Import</button>
        <button :class="{'is-on':tab==='history'}" @click="go('exportImport',{tab:'history'})"><Icon name="clock" :size="15"/> History</button>
      </div>

      <!-- EXPORT -->
      <template v-if="tab==='export'">
        <div class="card card-pad" style="margin-bottom:18px">
          <h2 class="t-title t-h3" style="margin:0 0 8px">Download a content bundle</h2>
          <p class="page-sub" style="max-width:70ch">Export registered tables into a single <span class="t-key">.zip</span> bundle that can be imported into another Modular install.</p>

          <label class="field-label" style="margin-top:16px">Export scope</label>
          <div class="ei-modes" style="margin-top:8px">
            <button class="ei-mode" :class="{'is-on': exportProfile==='full'}" @click="exportProfile='full'">
              <span class="ei-radio" :class="{'is-on': exportProfile==='full'}"></span>
              <span><strong>Full site</strong> — all registered tables (core + active modules).</span>
            </button>
            <button class="ei-mode" :class="{'is-on': exportProfile==='post_type'}" @click="exportProfile='post_type'">
              <span class="ei-radio" :class="{'is-on': exportProfile==='post_type'}"></span>
              <span><strong>Single post type</strong> — one content type and its posts, taxonomies, authors, featured images and revisions.</span>
            </button>
          </div>

          <template v-if="exportProfile==='post_type'">
            <label class="field-label" style="margin-top:14px">Post type</label>
            <UiSelect v-model="exportPostType" style="max-width:320px;margin-top:6px">
              <option v-if="!postTypes.length" value="" disabled>No post types loaded</option>
              <option v-for="pt in postTypes" :key="pt.id" :value="pt.id">{{ pt.name }} ({{ pt.id }})</option>
            </UiSelect>
            <p class="t-meta" style="margin-top:8px;max-width:70ch">Exports the post type definition, its fields and all content rows for <span class="t-key">{{ effectivePostType || '…' }}</span>.</p>
          </template>

          <label class="ei-check" :class="{'is-on': includeMedia}">
            <UiChk :modelValue="includeMedia" @update:modelValue="includeMedia=!includeMedia"/>
            <span><strong>Include media files</strong> — <template v-if="exportProfile==='post_type'">bundle featured images referenced by exported posts.</template><template v-else>bundle <span class="t-key">public/uploads/media/</span> into the zip (larger, but self-sufficient).</template></span>
          </label>

          <div class="flex items-center gap-3" style="margin-top:18px">
            <button class="btn btn--accent" @click="downloadBundle"><Icon name="upload" :size="15"/> Download bundle</button>
            <span class="t-meta">{{ exportScopeLabel }}{{ includeMedia ? ' · + media files' : '' }}</span>
          </div>
        </div>

        <div class="card">
          <div class="card-pad" style="padding-bottom:0">
            <div class="flex items-center justify-between" style="gap:16px;flex-wrap:wrap">
              <div>
                <h2 class="t-title t-h3" style="margin:0 0 6px">Tables included in this export</h2>
                <p class="t-meta" style="max-width:64ch">Built from <span class="t-key">md_export_register()</span> calls across core and active modules. Tables not in this list are not exported.</p>
              </div>
              <UiSelect style="width:auto" v-model="sourceFilter"><option v-for="s in sources" :key="s" :value="s">{{ s==='all'?'All sources':s }}</option></UiSelect>
            </div>
          </div>
          <table class="tbl" style="margin-top:14px">
            <thead><tr><th>Name</th><th>Table</th><th style="width:120px">Source</th><th style="width:120px">Rows</th><th>Natural key</th></tr></thead>
            <tbody>
              <tr v-for="t in shownTables" :key="t.table">
                <td><strong>{{ t.name }}</strong></td>
                <td><span class="t-key">{{ t.table }}</span></td>
                <td><span class="badge" :class="sourceClass(t.source)">{{ t.source }}</span></td>
                <td class="mono muted">{{ t.rows.toLocaleString() }}</td>
                <td><span class="t-key">{{ t.key }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- IMPORT -->
      <template v-else-if="tab==='import'">
        <div class="card card-pad" style="max-width:960px">
          <h2 class="t-title t-h3" style="margin:0 0 8px">Upload a bundle</h2>
          <p class="page-sub" style="max-width:70ch">Upload a <span class="t-key">.zip</span> bundle exported from another Modular install. Choose how to handle rows that already exist.</p>

          <label class="field-label" style="margin-top:16px">Bundle file</label>
          <label class="ei-file">
            <span class="btn btn--soft btn--sm"><Icon name="folder" :size="14"/> Browse…</span>
            <span class="t-meta">{{ fileName || 'No file selected.' }}</span>
            <input type="file" accept=".zip" hidden @change="onFile">
          </label>

          <div class="ei-modes" style="margin-top:18px">
            <button class="ei-mode" :class="{'is-on': importMode==='skip'}" @click="importMode='skip'">
              <span class="ei-radio" :class="{'is-on': importMode==='skip'}"></span>
              <span><strong>Skip duplicates</strong> — existing rows (matched by natural key) are kept; only new rows are inserted.</span>
            </button>
            <button class="ei-mode" :class="{'is-on': importMode==='overwrite'}" @click="importMode='overwrite'">
              <span class="ei-radio" :class="{'is-on': importMode==='overwrite'}"></span>
              <span><strong>Overwrite duplicates</strong> — existing rows are updated with the imported values. Destructive but useful for syncing.</span>
            </button>
          </div>

          <label class="ei-check ei-check--warn" :class="{'is-on': dryRun}" style="margin-top:14px">
            <UiChk :modelValue="dryRun" @update:modelValue="dryRun=!dryRun"/>
            <span><strong>Dry run</strong> — report what would happen without writing to the database. Wraps the import in a transaction and rolls back at the end.</span>
          </label>

          <div class="flex items-center gap-3" style="margin-top:20px">
            <button class="btn btn--accent" :disabled="!file || running" @click="runImport"><Icon name="upload" :size="15"/> {{ running ? 'Running…' : 'Run import' }}</button>
            <span class="t-meta" v-if="!file">Choose a bundle to enable import.</span>
          </div>

          <div v-if="error" class="badge badge--warn" style="margin-top:18px;display:block;padding:12px">{{ error }}</div>

          <div v-if="result" style="margin-top:18px">
            <h3 class="t-title t-h4" style="margin:0 0 8px">
              {{ result.mode === 'dryrun' ? 'Dry run — nothing was written' : 'Import complete' }}
            </h3>
            <table class="tbl">
              <thead><tr><th>Table</th><th style="width:90px">Created</th><th style="width:90px">Updated</th><th style="width:90px">Skipped</th></tr></thead>
              <tbody>
                <tr v-for="r in resultRows" :key="r.table">
                  <td><span class="t-key">{{ r.table }}</span></td>
                  <td class="mono">{{ r.created || 0 }}</td>
                  <td class="mono">{{ r.updated || 0 }}</td>
                  <td class="mono muted">{{ r.skipped || 0 }}</td>
                </tr>
              </tbody>
            </table>
            <div v-if="resultErrors.length" class="badge badge--warn" style="margin-top:12px;display:block;padding:12px">
              <strong>{{ resultErrors.length }} issue(s):</strong>
              <div v-for="(e,i) in resultErrors" :key="i" class="mono" style="margin-top:4px">{{ e }}</div>
            </div>
          </div>
        </div>
      </template>

      <!-- HISTORY -->
      <template v-else>
        <div class="card">
          <div class="card-pad" style="padding-bottom:6px"><h2 class="t-title t-h3" style="margin:0">Import history</h2></div>
          <table class="tbl">
            <thead><tr><th style="width:180px">When</th><th>Bundle</th><th>Source</th><th style="width:120px">Rows</th><th style="width:120px">Mode</th><th style="width:120px">By</th></tr></thead>
            <tbody>
              <tr v-for="h in history" :key="h.id">
                <td class="mono muted">{{ h.when }}</td>
                <td><span class="t-key">{{ h.bundle }}</span></td>
                <td class="muted">{{ h.source }}</td>
                <td class="mono muted">{{ h.rows.toLocaleString() }}</td>
                <td><span class="badge" :class="h.mode==='overwrite'?'badge--warn':''">{{ h.mode }}</span></td>
                <td><div class="flex items-center gap-2"><span class="avatar" style="width:22px;height:22px;font-size:9px">{{ h.by.slice(0,2).toUpperCase() }}</span>{{ h.by }}</div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>`,
  };
})();
