/* ============================================================
   PERMALINKS — per-post-type URL structures, tokens, live
   preview, and the real .htaccess on disk.
   Rendered inside Settings -> Permalinks.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;
  const D = () => window.__mdlr.data;

  C.PermalinksPanel = {
    name: 'PermalinksPanel',
    data() {
      return {
        base: '',
        choice: {},
        custom: {},
        opts: { trailingSlash:false, lowercase:true, dropStopwords:false },
        htaccessPath: '',
        htaccessContent: '',
        htaccessWritable: true,
        htaccessExists: false,
        loading: true,
        saving: false,
        saved: false,
        copied: false,
        err: '',
        warn: '',
      };
    },
    created() {
      D().POST_TYPES.forEach(pt => {
        this.choice[pt.id] = 'plain';
        this.custom[pt.id] = '/{slug}';
      });
      this.load();
    },
    computed: {
      pts() { return D().POST_TYPES; },
      permalinkRules() {
        const lines = ['# BEGIN Modular Permalinks'];
        const rules = [];
        this.pts.forEach(pt => {
          const rule = this.rewriteRule(pt, this.pattern(pt));
          if (rule) rules.push(rule);
        });
        if (rules.length) lines.push(...rules);
        else lines.push('# Plain /{slug} types are handled by the front controller below.');
        lines.push('# END Modular Permalinks');
        return lines.join('\n');
      },
    },
    methods: {
      taxFor(pt) { return (pt.taxonomies || []).map(id => D().TAXONOMIES.find(t => t.id === id)).filter(Boolean); },
      options(pt) {
        const o = [
          { key:'plain',  pattern:'/{slug}', label:'plain slug' },
          { key:'prefix', pattern:'/'+pt.slug+'/{slug}', label:'post type prefix' },
          { key:'year',   pattern:'/{year}/{slug}', label:'year prefix' },
        ];
        const tax = this.taxFor(pt);
        if (tax.length) o.push({ key:'taxonomy', pattern:'/{'+tax[0].slug+'}/{slug}', label:tax[0].name.toLowerCase()+' + slug' });
        o.push({ key:'custom', pattern:null, label:'custom' });
        return o;
      },
      pattern(pt) {
        if (this.choice[pt.id] === 'custom') return this.custom[pt.id] || '/{slug}';
        const o = this.options(pt).find(x => x.key === this.choice[pt.id]);
        return o ? o.pattern : '/{slug}';
      },
      applyStructures(structures) {
        const map = structures || {};
        this.pts.forEach(pt => {
          const stored = map[pt.slug] || map[pt.id] || '/{slug}';
          const tax = this.options(pt).find(x => x.key === 'taxonomy');
          const prefixPat = '/' + pt.slug + '/{slug}';
          if (stored === '/{slug}') this.choice[pt.id] = 'plain';
          else if (stored === '/{post_type}/{slug}' || stored === prefixPat) this.choice[pt.id] = 'prefix';
          else if (stored === '/{year}/{slug}') this.choice[pt.id] = 'year';
          else if (stored === '/{category}/{slug}' || (tax && stored === tax.pattern)) this.choice[pt.id] = 'taxonomy';
          else {
            this.choice[pt.id] = 'custom';
            this.custom[pt.id] = stored;
          }
        });
      },
      collectStructures() {
        const out = {};
        this.pts.forEach(pt => { out[pt.slug] = this.pattern(pt); });
        return out;
      },
      rewriteRule(pt, structure) {
        let raw = String(structure || '').replace(/^\/+|\/+$/g, '');
        raw = raw.split('{post_type}').join(pt.slug);
        if (!raw || raw === '{slug}') return null;
        const captures = [];
        const parts = raw.split(/(\{[a-z_]+\})/);
        let pattern = '';
        parts.forEach(part => {
          if (!part) return;
          if (part === '{slug}') { captures.push('slug'); pattern += '([^/]+)'; return; }
          if (part === '{year}') { captures.push('year'); pattern += '([0-9]{4})'; return; }
          if (/^\{[a-z_]+\}$/.test(part)) { captures.push(part.slice(1, -1)); pattern += '([^/]+)'; return; }
          pattern += part.replace(/[.+?^$[\](){}|\\]/g, '\\$&');
        });
        const qs = ['post_type=' + pt.slug];
        captures.forEach((token, i) => {
          const n = i + 1;
          if (token === 'slug') { qs.push('slug=$' + n); qs.push('name=$' + n); }
          else if (token === 'year') qs.push('year=$' + n);
          else if (token === 'category') qs.push('term=$' + n);
        });
        return 'RewriteRule ^' + pattern + '/?$ index.php?' + qs.join('&') + ' [L,QSA]';
      },
      resolve(pt, pattern) {
        const tax = this.taxFor(pt);
        const term = tax.length && tax[0].terms && tax[0].terms[0] ? tax[0].terms[0].slug : 'general';
        let p = (pattern || '/{slug}')
          .replace('{slug}', 'sample-' + pt.slug)
          .replace('{post_type}', pt.slug)
          .replace('{year}', '2026')
          .replace('{category}', term)
          .replace('{type}', pt.slug);
        tax.forEach(t => { p = p.replace('{'+t.slug+'}', term); });
        if (this.opts.lowercase) p = p.toLowerCase();
        if (this.opts.trailingSlash && !p.endsWith('/')) p += '/';
        return p;
      },
      preview(pt) { return this.base + this.resolve(pt, this.pattern(pt)); },
      applyPayload(data) {
        if (!data || typeof data !== 'object') return;
        if (data.site_url) this.base = String(data.site_url).replace(/\/$/, '');
        this.applyStructures(data.structures || {});
        const ht = data.htaccess || {};
        this.htaccessPath = ht.path || '';
        this.htaccessContent = ht.content || '';
        this.htaccessWritable = ht.writable !== false;
        this.htaccessExists = !!ht.exists;
        this.warn = data.warning || '';
      },
      load() {
        const api = window.__mdlr.api;
        if (!api) { this.loading = false; this.err = 'API is not available.'; return; }
        this.loading = true; this.err = '';
        api.get('/settings/permalinks')
          .then(data => { this.applyPayload(data); })
          .catch(e => { this.err = (e && e.data && e.data.error) || 'Could not load permalinks.'; })
          .then(() => { this.loading = false; });
      },
      save() {
        const api = window.__mdlr.api;
        if (!api || this.saving) return;
        this.saving = true; this.err = ''; this.warn = ''; this.saved = false;
        api.put('/settings/permalinks', { structures: this.collectStructures() })
          .then(data => {
            this.applyPayload(data);
            this.saved = !this.warn;
            setTimeout(() => { this.saved = false; }, 2200);
          })
          .catch(e => { this.err = (e && e.data && e.data.error) || 'Saving permalinks failed.'; })
          .then(() => { this.saving = false; });
      },
      flush() { this.save(); },
      copy() {
        const text = this.htaccessContent || '';
        if (!text || !navigator.clipboard) return;
        navigator.clipboard.writeText(text).then(() => {
          this.copied = true;
          setTimeout(() => { this.copied = false; }, 1600);
        }).catch(() => {});
      },
    },
    template: `
    <div class="pl">
      <div class="page-head" style="margin-bottom:18px">
        <div>
          <h2 class="t-title t-h2" style="margin:0">Permalinks</h2>
          <p class="page-sub" style="margin-top:6px">How public URLs are built from content. Each post type can have its own structure. Saving writes rewrite rules into a nested Permalinks section of .htaccess and leaves auth, API, and uploads rules alone.</p>
        </div>
        <div class="flex gap-2 items-center">
          <span v-if="saved" class="badge badge--ok">Saved</span>
          <button class="btn btn--accent" :disabled="saving || loading" @click="save"><Icon name="save" :size="15"/> {{ saving ? 'Saving…' : 'Save permalinks' }}</button>
        </div>
      </div>

      <p v-if="err" class="t-meta" style="color:var(--danger);margin-bottom:12px">{{ err }}</p>
      <p v-if="warn" class="t-meta" style="color:var(--danger);margin-bottom:12px">{{ warn }}</p>
      <p v-if="loading" class="t-meta">Loading…</p>

      <div class="card" style="max-width:none">
        <div class="card-pad" style="padding-bottom:6px"><h3 class="t-title t-h3" style="margin:0">Post type URL structures</h3></div>
        <div class="pl-table">
          <div class="pl-row pl-row--head">
            <div>Post type</div><div>Structure</div><div>Preview</div>
          </div>
          <div class="pl-row" v-for="pt in pts" :key="pt.id">
            <div class="pl-type"><span class="dash-row-ico"><Icon :name="pt.icon" :size="15"/></span><strong>{{ pt.name }}</strong></div>
            <div class="pl-structs">
              <label class="pl-opt" v-for="o in options(pt)" :key="o.key" :class="{'is-on': choice[pt.id]===o.key}">
                <span class="pl-radio" :class="{'is-on': choice[pt.id]===o.key}"></span>
                <input type="radio" hidden :checked="choice[pt.id]===o.key" @change="choice[pt.id]=o.key">
                <template v-if="o.key==='custom'">
                  <span class="pl-optlabel">Custom:</span>
                  <input class="inp mono pl-custom" v-model="custom[pt.id]" placeholder="/{slug}" @focus="choice[pt.id]='custom'">
                  <span class="pl-tokens">tokens: <span class="t-key" v-for="tk in ['{slug}','{post_type}','{category}','{year}']" :key="tk">{{ tk }}</span></span>
                </template>
                <template v-else>
                  <span class="t-key pl-pat">{{ o.pattern }}</span><span class="pl-optlabel">— {{ o.label }}</span>
                </template>
              </label>
            </div>
            <div class="pl-preview"><span class="t-key">{{ preview(pt) }}</span></div>
          </div>
        </div>
      </div>

      <div class="card card-pad" style="margin-top:18px;max-width:none">
        <h3 class="t-title t-h3" style="margin:0 0 6px">Common rules</h3>
        <p class="page-sub" style="margin-bottom:8px">Applied to every generated URL.</p>
        <div class="opt-row" style="padding-top:8px"><div><strong>Force lowercase</strong><div class="field-hint">Normalise slugs to lowercase.</div></div><UiSwitch v-model="opts.lowercase"/></div>
        <div class="opt-row"><div><strong>Trailing slash</strong><div class="field-hint">Append a <span class="t-key">/</span> to every URL.</div></div><UiSwitch v-model="opts.trailingSlash"/></div>
        <div class="opt-row" style="padding-bottom:0"><div><strong>Strip stop words</strong><div class="field-hint">Drop “a, the, and…” when auto-generating slugs.</div></div><UiSwitch v-model="opts.dropStopwords"/></div>
      </div>

      <div class="card card-pad" style="margin-top:18px;max-width:none">
        <h3 class="t-title t-h3" style="margin:0 0 6px">Current .htaccess</h3>
        <p class="page-sub" style="margin-bottom:8px">
          Live contents of <span class="t-key">{{ htaccessPath || '.htaccess' }}</span>.
          <span v-if="!htaccessWritable"> This file is not writable, so save cannot update it.</span>
        </p>
        <pre class="pl-code">{{ htaccessContent || (loading ? 'Loading…' : '(file is empty or missing)') }}</pre>
        <div class="flex gap-2" style="margin-top:12px">
          <button class="btn btn--ghost btn--sm" :disabled="!htaccessContent" @click="copy"><Icon name="copy" :size="14"/> {{ copied ? 'Copied' : 'Copy' }}</button>
          <button class="btn btn--ghost btn--sm" :disabled="saving || loading" @click="flush"><Icon name="refresh" :size="14"/> Flush rewrite rules</button>
        </div>
      </div>

      <div class="card card-pad" style="margin-top:18px;max-width:none">
        <h3 class="t-title t-h3" style="margin:0 0 6px">Permalink rules to write</h3>
        <p class="page-sub" style="margin-bottom:14px">These lines are merged between <span class="t-key"># BEGIN Modular Permalinks</span> and <span class="t-key"># END Modular Permalinks</span>. Existing auth, API, uploads, and file-exists rules are not replaced.</p>
        <pre class="pl-code">{{ permalinkRules }}</pre>
      </div>
    </div>`,
  };
})();
