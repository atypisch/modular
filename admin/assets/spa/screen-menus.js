/* ============================================================
   MENUS — the site navigations (main, mobile and the footer
   columns). Each location is a settings row `site_menu_{slug}`
   holding an ordered JSON array of {label, url, type, post_id}.
   The location list itself lives in the `navigations` setting,
   whose `name` doubles as the footer column heading.
   Rendered inside Settings -> Menus.
   ============================================================ */
(function () {
  window.__mdlr = window.__mdlr || { comps: {} };
  const C = window.__mdlr.comps;

  const DEFAULT_LOCATIONS = [
    { slug: 'main',           name: 'Main navigation' },
    { slug: 'mobile',         name: 'Mobile navigation' },
    { slug: 'footer_music',   name: 'Music' },
    { slug: 'footer_network', name: 'Network' },
    { slug: 'footer_shows',   name: 'Shows' },
    { slug: 'footer_about',   name: 'About' },
    { slug: 'footer_legal',   name: 'Legal' },
  ];

  C.MenusPanel = {
    name: 'MenusPanel',
    data() {
      return {
        locations: [],
        menus: {},          // slug -> [{label,url,type,post_id}]
        active: 'main',
        pages: [],
        loading: true,
        saving: false,
        saved: false,
        err: '',
        addKind: 'page',    // 'page' | 'custom'
        addPageId: '',
        addLabel: '',
        addUrl: '',
      };
    },
    created() { this.load(); },
    computed: {
      items() { return this.menus[this.active] || []; },
      activeName() { const l = this.locations.find(x => x.slug === this.active); return l ? l.name : this.active; },
      canAdd() {
        if (this.addKind === 'page') return !!this.addPageId;
        return this.addLabel.trim() !== '' && this.addUrl.trim() !== '';
      },
    },
    methods: {
      load() {
        const api = window.__mdlr.api;
        if (!api) { this.loading = false; return; }
        this.loading = true; this.err = '';
        Promise.all([
          api.get('/settings').catch(() => ({})),
          // ?fields=light returns id/title/slug/post_type_slug only; the endpoint
          // filters on numeric post_type_id, so narrow to pages on this side.
          api.get('/posts?fields=light&limit=500').catch(() => null),
        ]).then(([settings, pages]) => {
          const navs = settings && settings.navigations;
          this.locations = Array.isArray(navs) && navs.length ? navs : DEFAULT_LOCATIONS;
          const menus = {};
          this.locations.forEach(loc => {
            const raw = settings ? settings['site_menu_' + loc.slug] : null;
            menus[loc.slug] = Array.isArray(raw) ? raw.map(this.normalize) : [];
          });
          this.menus = menus;
          if (!menus[this.active]) this.active = this.locations[0] ? this.locations[0].slug : 'main';
          const list = pages && (pages.items || pages.data || pages);
          this.pages = Array.isArray(list)
            ? list.filter(p => (p.post_type_slug || 'page') === 'page' && !p.deleted_at)
                  .map(p => ({ id: p.id, title: p.title || p.slug, slug: p.slug }))
            : [];
        }).catch(e => {
          this.err = (e && e.data && e.data.error) || 'Could not load the menus.';
        }).then(() => { this.loading = false; });
      },
      normalize(it) {
        return {
          label: String((it && it.label) || ''),
          url: String((it && it.url) || ''),
          type: (it && it.type) || 'custom',
          post_id: (it && it.post_id) || null,
        };
      },
      move(i, delta) {
        const arr = this.menus[this.active];
        const j = i + delta;
        if (j < 0 || j >= arr.length) return;
        arr.splice(j, 0, arr.splice(i, 1)[0]);
      },
      remove(i) { this.menus[this.active].splice(i, 1); },
      addItem() {
        if (!this.canAdd) return;
        const arr = this.menus[this.active];
        if (this.addKind === 'page') {
          const p = this.pages.find(x => String(x.id) === String(this.addPageId));
          if (!p) return;
          arr.push({ label: this.addLabel.trim() || p.title, url: '/' + p.slug, type: 'post', post_id: p.id });
        } else {
          let url = this.addUrl.trim();
          if (!/^(https?:)?\/\//.test(url) && url[0] !== '/') url = '/' + url;
          arr.push({ label: this.addLabel.trim(), url, type: 'custom', post_id: null });
        }
        this.addPageId = ''; this.addLabel = ''; this.addUrl = '';
      },
      save() {
        const api = window.__mdlr.api;
        if (!api || this.saving) return;
        this.saving = true; this.err = '';
        const options = { navigations: this.locations };
        this.locations.forEach(loc => { options['site_menu_' + loc.slug] = this.menus[loc.slug] || []; });
        api.put('/settings', { options })
          .then(() => { this.saved = true; setTimeout(() => { this.saved = false; }, 2200); })
          .catch(e => { this.err = (e && e.data && e.data.error) || 'Saving the menus failed.'; })
          .then(() => { this.saving = false; });
      },
    },
    template: `
    <div>
      <div class="flex items-start justify-between" style="margin-bottom:16px;gap:16px;flex-wrap:wrap">
        <div>
          <h2 class="t-title t-h2" style="margin:0">Menus</h2>
          <p class="page-sub" style="margin-top:6px;max-width:62ch">The site navigations. Every location is rendered by the theme: the main and mobile bars at the top, the rest as footer columns using the location name as the heading.</p>
        </div>
        <div class="flex gap-2 items-center">
          <span v-if="saved" class="badge badge--ok">Saved</span>
          <button class="btn btn--accent" :disabled="saving || loading" @click="save"><Icon name="check" :size="15"/> {{ saving ? 'Saving…' : 'Save menus' }}</button>
        </div>
      </div>

      <p v-if="err" class="t-meta" style="color:var(--danger)">{{ err }}</p>
      <p v-if="loading" class="t-meta">Loading…</p>

      <div v-if="!loading" class="tabs" style="flex-wrap:wrap;margin-bottom:16px">
        <button v-for="l in locations" :key="l.slug" :class="{'is-on': active===l.slug}" @click="active=l.slug">
          {{ l.name }} <span class="tab-count">{{ (menus[l.slug]||[]).length }}</span>
        </button>
      </div>

      <div v-if="!loading" class="card card-pad">
        <h3 class="t-h3" style="margin:0 0 4px">{{ activeName }}</h3>
        <p class="t-meta" style="margin:0 0 14px">Stored as <code>site_menu_{{ active }}</code>. Page items take both their label and their link from the page itself, so they keep working when a page is renamed or moved. Use a custom link for anything else.</p>

        <table class="tbl tbl--rows" v-if="items.length">
          <thead><tr><th>Label</th><th>Link</th><th style="width:150px">Order</th><th style="width:60px"></th></tr></thead>
          <tbody>
            <tr v-for="(it,i) in items" :key="i">
              <td>
                <input class="inp" v-model="it.label" :disabled="it.type==='post'">
                <div v-if="it.type==='post'" class="t-meta" style="margin-top:4px">Follows the page title</div>
              </td>
              <td>
                <template v-if="it.type === 'post'">
                  <span class="mono">{{ it.url }}</span>
                  <div class="t-meta" style="margin-top:4px">Generated from the page{{ it.post_id ? ' (#' + it.post_id + ')' : '' }}</div>
                </template>
                <template v-else>
                  <input class="inp" v-model="it.url">
                  <div class="t-meta" style="margin-top:4px">Custom link</div>
                </template>
              </td>
              <td>
                <button class="iconbtn" :disabled="i===0" @click="move(i,-1)" title="Move up"><Icon name="chevUp" :size="15"/></button>
                <button class="iconbtn" :disabled="i===items.length-1" @click="move(i,1)" title="Move down"><Icon name="chevDown" :size="15"/></button>
              </td>
              <td><button class="iconbtn" @click="remove(i)" title="Remove"><Icon name="trash" :size="15"/></button></td>
            </tr>
          </tbody>
        </table>
        <p v-else class="t-meta" style="margin:0 0 14px">This menu is empty. The theme falls back to its built-in defaults while a location has no items.</p>

        <div style="border-top:1px solid var(--line);margin-top:16px;padding-top:16px">
          <div class="flex gap-2" style="margin-bottom:10px">
            <button class="btn btn--sm" :class="addKind==='page' ? 'btn--accent' : 'btn--ghost'" @click="addKind='page'">Page</button>
            <button class="btn btn--sm" :class="addKind==='custom' ? 'btn--accent' : 'btn--ghost'" @click="addKind='custom'">Custom link</button>
          </div>
          <div class="flex gap-2 items-end" style="flex-wrap:wrap">
            <div class="field" style="margin:0;min-width:220px" v-if="addKind==='page'">
              <label class="field-label">Page</label>
              <UiSelect v-model="addPageId">
                <option value="">Choose a page…</option>
                <option v-for="p in pages" :key="p.id" :value="String(p.id)">{{ p.title }}</option>
              </UiSelect>
            </div>
            <div class="field" style="margin:0;min-width:220px" v-else>
              <label class="field-label">URL</label>
              <input class="inp" v-model="addUrl" placeholder="/shows?type=festival">
            </div>
            <div class="field" style="margin:0;min-width:200px" v-if="addKind==='custom'">
              <label class="field-label">Label <span class="field-req">*</span></label>
              <input class="inp" v-model="addLabel" placeholder="Festivals">
            </div>
            <button class="btn btn--ghost" :disabled="!canAdd" @click="addItem"><Icon name="plus" :size="15"/> Add item</button>
          </div>
        </div>
      </div>
    </div>`,
  };
})();
