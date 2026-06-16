/* ============================================================
   PERMALINKS — per-post-type URL structures, tokens, live
   preview, common rules and the generated .htaccess block.
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
        base: 'http://localhost/atypisch-mdlr',
        choice: {},            // ptId -> 'plain'|'prefix'|'year'|'taxonomy'|'custom'
        custom: {},            // ptId -> custom pattern string
        opts: { trailingSlash:false, lowercase:true, dropStopwords:false },
      };
    },
    created() {
      D().POST_TYPES.forEach(pt => { this.choice[pt.id] = pt.id === 'page' ? 'plain' : 'plain'; this.custom[pt.id] = '/{slug}'; });
    },
    computed: { pts() { return D().POST_TYPES; } },
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
      htaccess() {
        const lines = [
          '# BEGIN Modular',
          '<IfModule mod_rewrite.c>',
          'RewriteEngine On',
          'RewriteBase /atypisch-mdlr/',
          'RewriteRule ^index\\.php$ - [L]',
        ];
        this.pts.forEach(pt => {
          const pat = this.pattern(pt);
          if (this.choice[pt.id] === 'prefix') lines.push('RewriteRule ^'+pt.slug+'/([^/]+)/?$ index.php?post_type='+pt.slug+'&name=$1 [L,QSA]');
          else if (this.choice[pt.id] === 'year') lines.push('RewriteRule ^([0-9]{4})/([^/]+)/?$ index.php?post_type='+pt.slug+'&year=$1&name=$2 [L,QSA]');
          else if (this.choice[pt.id] === 'taxonomy') lines.push('RewriteRule ^([^/]+)/([^/]+)/?$ index.php?post_type='+pt.slug+'&term=$1&name=$2 [L,QSA]');
        });
        lines.push('RewriteCond %{REQUEST_FILENAME} !-f');
        lines.push('RewriteCond %{REQUEST_FILENAME} !-d');
        lines.push('RewriteRule ^([^/]+)/?$ index.php?name=$1 [L,QSA]');
        lines.push('RewriteRule ^ index.php [L,QSA]');
        lines.push('</IfModule>');
        lines.push('# END Modular');
        return lines.join('\n');
      },
    },
    template: `
    <div class="pl">
      <!-- intro -->
      <div class="page-head" style="margin-bottom:18px">
        <div><h2 class="t-title t-h2" style="margin:0">Permalinks</h2><p class="page-sub" style="margin-top:6px">How public URLs are built from content. Each post type can have its own structure. Changes regenerate the rewrite rules below.</p></div>
        <button class="btn btn--accent"><Icon name="save" :size="15"/> Save permalinks</button>
      </div>

      <!-- per type structures -->
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

      <!-- common rules -->
      <div class="card card-pad" style="margin-top:18px;max-width:none">
        <h3 class="t-title t-h3" style="margin:0 0 6px">Common rules</h3>
        <p class="page-sub" style="margin-bottom:8px">Applied to every generated URL.</p>
        <div class="opt-row" style="padding-top:8px"><div><strong>Force lowercase</strong><div class="field-hint">Normalise slugs to lowercase.</div></div><UiSwitch v-model="opts.lowercase"/></div>
        <div class="opt-row"><div><strong>Trailing slash</strong><div class="field-hint">Append a <span class="t-key">/</span> to every URL.</div></div><UiSwitch v-model="opts.trailingSlash"/></div>
        <div class="opt-row" style="padding-bottom:0"><div><strong>Strip stop words</strong><div class="field-hint">Drop “a, the, and…” when auto-generating slugs.</div></div><UiSwitch v-model="opts.dropStopwords"/></div>
      </div>

      <!-- htaccess -->
      <div class="card card-pad" style="margin-top:18px;max-width:none">
        <h3 class="t-title t-h3" style="margin:0 0 6px">Current .htaccess</h3>
        <p class="page-sub" style="margin-bottom:14px">This block is written to <span class="t-key">/Users/martentiman/Sites/atypisch-mdlr/.htaccess</span> when you save.</p>
        <pre class="pl-code">{{ htaccess() }}</pre>
        <div class="flex gap-2" style="margin-top:12px"><button class="btn btn--ghost btn--sm"><Icon name="copy" :size="14"/> Copy</button><button class="btn btn--ghost btn--sm"><Icon name="refresh" :size="14"/> Flush rewrite rules</button></div>
      </div>
    </div>`,
  };
})();
